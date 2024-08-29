<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Session;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\User;
use App\Models\Method;
use App\Models\Contributor;
use App\Models\Idea;
use App\Models\SessionDetailsCache;
use App\Models\Vote;
use Illuminate\Support\Facades\Storage;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Events\StartCollecting;
use App\Events\StopCollecting;
use Log;
use GuzzleHttp\Client;

class SessionController extends Controller
{
    public function downloadSessionPDF($sessionId)
    {
        Log::info('download PDF: ' . $sessionId);
        $sessionDetails = SessionDetailsCache::findOrFail($sessionId);
       // Log::info($sessionDetails);
       $ideas = $sessionDetails->ideas;
       // Gruppiere die Ideen nach `round`
       $groupedIdeas = array_reduce($ideas, function ($result, $idea) {
           $round = $idea['round']; // Angenommene Struktur der Idee
           if (!isset($result[$round])) {
               $result[$round] = [];
           }
           $result[$round][] = $idea;
           return $result;
       }, []);
        //Log::info($groupedIdeas);
        $html = view('pdf.session_details', ['sessionDetails' => $sessionDetails, 'groupedIdeas' => $groupedIdeas])->render();
        Log::info('HTML generiert');
        
        $pdf = Pdf::loadHTML($html);
        $filename = $sessionDetails->target ?? 'session_details';
        $filename .= '.pdf';
        
        return $pdf->download($filename);
    }

    public function get($sessionId)
    {
        if (!$sessionId) {
            return response()->json(['message' => 'Session ID is required'], 400);
        }

        $session = Session::with(['host', 'method'])->find($sessionId);

        if (!$session) {
            return response()->json(['message' => 'Session not found'], 404);
        }

        $contributor = Contributor::where('user_id', $session->host_id)
            ->where('session_id', $session->id)
            ->first();

        $contributorsCount = Contributor::where('session_id', $session->id)->count();
        Log::info('ContributorsCount: ' . $contributorsCount);
        return response()->json([
            'id' => $session->id,
            'session_host' => $contributor->id,
            'method_id' => $session->method_id,
            'target' => $session->target,
            'method_name' => $session->method->name,
            'session_phase' => $session->active_phase,
            'contributors_count' => $contributorsCount, // Anzahl der Contributors mit der Session_ID
            'contributors_amount' => $session->contributors_amount // Erwartete Anzahl der Teilnehmer 
        ], 200);
    }

    public function getUserSessions($userId)
    {
        $contributors = Contributor::where('user_id', $userId)->get();

        $sessions = $contributors->map(function ($contributor) {
            $session = Session::find($contributor->session_id);
            $method = Method::find($session->method_id);
            $role = Role::find($contributor->role_id);

            return [
                'session_id' => $contributor->session_id,
                'target' => $session->target,
                'role' => $role->name,
                'updated_at' => $session->updated_at,
                'method_name' => $method->name,
            ];
        });

        Log::info('User Sessions:', $sessions->toArray());

        return response()->json($sessions, 200);
    }


    public function update(Request $request)
    {
        $request->validate([
            'session_id' => 'required',
            'host_id' => 'required',
            'method_id' => 'required',
            'session_target' => 'nullable|string|present',
            'contributors_amount' => 'required'
        ]);

        $sessionId = $request->input('session_id');
        $hostId = $request->input('host_id');
        $contributorsAmount = $request->input('contributors_amount');
        $session = Session::firstOrNew(['id' => $sessionId]);
        $isNewSession = !$session->exists;

        $session->target = $request->input('session_target') ?: 'Kein Ziel festgelegt';
        $session->host_id = $hostId;
        $session->contributors_amount = $contributorsAmount;
        $session->method_id = $request->input('method_id');
        $session->save();

        if ($isNewSession) {
            $defaultRoleId = 0; // Angenommen, 1 ist die ID für die "Unassigned" Rolle
            Contributor::create([
                'session_id' => $session->id,
                'user_id' => $hostId,
                'role_id' => $defaultRoleId
            ]);
        }

        return response()->json([
            'message' => $isNewSession ? 'Session created successfully.' : 'Session updated successfully.'
        ], 200);
    }
    public function startCollecting(Request $request)
    {
        $sessionId = $request->input('session_id');
        $round = $request->input('current_round');

        // Aktualisiere den Wert der Spalte `active_round` für die Session
        $session = Session::find($sessionId);
        if ($session) {
            $session->active_round = $round;
            $session->save();
        }

        // Event auslösen
        event(new StartCollecting($sessionId, $round));

        return response()->json(['message' => 'Collecting successfully started']);
    }


    public function stopCollecting(Request $request)
    {
        $sessionId = $request->input('session_id');
        $round = $request->input('current_round');

        // Aktualisiere den Wert der Spalte `active_round` für die Session
        $session = Session::find($sessionId);
        if ($session) {
            $session->active_round = $round;
            $session->save();
        }

        // Event auslösen
        event(new StopCollecting($sessionId, $round));

        return response()->json(['message' => 'Collecting successfully stopped']);
    }

    public function invite(Request $request)
    {
        $request->validate([
            'session_id' => 'required',
            'host_id' => 'required',
            'contributor_email_addresses' => 'required|array',
            'contributor_email_addresses.*' => 'email',
        ]);

        $sessionId = $request->input('session_id');
        $hostId = $request->input('host_id');
        $contributorEmailAddresses = $request->input('contributor_email_addresses');

        $host = User::findOrFail($hostId);
        $userName = explode('.', $host->email)[0];

        $emailMessage = "Hallo, <br> Du wurdest von {$userName} zu einer Ideen-Session eingeladen. <br> <br>
        Du kannst über folgenden Link beitreten: <a href='https://stefan-theissen.de/brainframe/{$sessionId}'>Brainframe</a>. <br> <br>
        Alternativ kannst du auch unter <a href='https://stefan-theissen.de/brainframe'>Brainframe</a> vorbeischauen und mit dem Code: {$sessionId} beitreten.";

        foreach ($contributorEmailAddresses as $email) {
            if ($email) {
                Mail::html($emailMessage, function ($message) use ($email, $userName) {
                    $message->to($email)
                        ->subject("{$userName} - Einladung zur Ideen-Session");
                });
            }
        }

        return response()->json(['message' => 'Invitations sent successfully.'], 200);
    }
    public function getClosingDetails(Request $request, $sessionId)
    {
        $cachedDetails = SessionDetailsCache::where('session_id', $sessionId)->first();
        if (!$cachedDetails) {
            $session = Session::findOrFail($sessionId);

            // Schritt 1: Berechne die durchschnittliche Bewertung für jede Idee und beschränke auf die Top 3
            $topVotes = Vote::where('session_id', $session->id)
                ->where('voting_phase', Vote::where('session_id', $session->id)
                    ->max('voting_phase'))
                ->select('idea_id', DB::raw('AVG(vote_value) as avg_vote_value'))
                ->groupBy('idea_id')
                ->orderBy('avg_vote_value', 'desc')
                ->take(3) // Begrenze auf die Top 3
                ->get();

            // Hole die IDs der Top 3 Ideen
            $ideaIds = $topVotes->pluck('idea_id');

            // Schritt 2: Hole die Ideen mit den spezifischen Feldern und das Icon des Contributors
            $topIdeas = Idea::whereIn('id', $ideaIds)
                ->select('id', 'idea_title', 'idea_description', 'contributor_id', 'tag') // ID hinzugefügt
                ->get()
                ->map(function ($idea) use ($topVotes) {
                    // Füge die durchschnittliche Bewertung zur Idee hinzu
                    $topVote = $topVotes->where('idea_id', $idea->id)->first();

                    if ($topVote) {
                        $idea->avg_vote_value = $topVote->avg_vote_value;
                    } else {
                        $idea->avg_vote_value = null; // Setze auf null, falls keine Bewertung vorhanden
                    }

                    // Hole das Icon des Contributors
                    $contributor = Contributor::find($idea->contributor_id);
                    if ($contributor && $contributor->role) {
                        $idea->contributor_icon = $contributor->role->icon;
                    } else {
                        $idea->contributor_icon = null; // Setze auf null, falls kein Contributor gefunden oder kein Role vorhanden
                    }

                    return $idea;
                });

            Log::info('Top votes and top ideas', [
                'topIdeas' => $topIdeas
            ]);

            $ideas = Idea::where('session_id', $session->id)->get();
            $wordCloudData = $this->generateWordCloud($ideas, $sessionId);
            $tagList = Idea::where('session_id', $session->id)
                ->whereNotNull('tag')
                ->groupBy('tag')
                ->selectRaw('tag, COUNT(*) as count')
                ->get();

            $nextSteps = $this->generateNextSteps($topIdeas, $sessionId);
            $firstIdeaTime = Carbon::parse(Idea::where('session_id', $session->id)->min('created_at'));
            $lastVoteTime = Carbon::parse(Vote::whereIn('idea_id', $ideas->pluck('id'))->max('updated_at'));
            $duration = abs($lastVoteTime->diffInMinutes($firstIdeaTime));

            $method = Method::where('id', $session->method_id)->first();
            $contributorsCount = Contributor::where('session_id', $sessionId)->count();
            $ideasCount = Idea::where('session_id', $sessionId)
                ->count();
            Log::info('contributorsCount: ' . $contributorsCount);
            Log::info('ideasCount: ' . $ideasCount);
            Log::info('duration: ' . $duration);
            Log::info('date: ' . $session->created_at->toDateString());
            Log::info('method: ' . ($method ? $method->name : 'N/A'));
            Log::info('inputToken: ' . ($session->input_token ?? 'N/A'));
            Log::info('outputToken: ' . ($session->output_token ?? 'N/A'));
            Log::info('tagList: ' . json_encode($tagList, JSON_PRETTY_PRINT));


            $response = [
                'id' => $session->id,
                'target' => $session->target,
                'topIdeas' => $topIdeas,
                'ideas' => $ideas->map(function ($idea) {
                    return [
                        'id' => $idea->id,
                        'title' => $idea->title,
                        'description' => $idea->description,
                        'round' => $idea->round,
                        'contributor_icon' => $idea->contributor->role->icon,
                    ];
                }),
                'contributorsCount' => $contributorsCount,
                'ideasCount' => $ideasCount,
                'duration' => $duration,
                'date' => $session->created_at->toDateString(),
                'method' => $method ? $method->name : 'N/A',
                'inputToken' => $session->input_token,
                'outputToken' => $session->output_token,
                'wordCloudData' => $wordCloudData,
                'tagList' => $tagList,
                'nextSteps' => $nextSteps,
            ];

            $sessionDetailsCache = SessionDetailsCache::create([
                'session_id' => $session->id,
                'target' => $session->target,
                'top_ideas' => $topIdeas,
                'ideas' => $ideas->map(function ($idea) {
                    return [
                        'id' => $idea->id,
                        'title' => $idea->title,
                        'description' => $idea->description,
                        'round' => $idea->round,
                        'contributor_icon' => $idea->contributor->role->icon,
                    ];
                }),
                'contributors_count' => $contributorsCount,
                'ideas_count' => $ideasCount,
                'duration' => $duration,
                'date' => $session->created_at->toDateString(),
                'method' => $method ? $method->name : 'N/A',
                'input_token' => $session->input_token,
                'output_token' => $session->output_token,
                'word_cloud_data' => $wordCloudData,
                'tag_list' => $tagList,
                'next_steps' => $nextSteps,
            ]);

            return response()->json($response);
        } else {
            return response()->json($cachedDetails);
        }
    }
    private function generateWordCloud($ideas, $sessionId)
    {
        $client = new Client();
        $apiKey = env('OPENAI_API_KEY');

        try {
            $response = $client->post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => "gpt-4o-mini",
                    'messages' => [
                            [
                                'role' => 'system',
                                'content' => 'Generiere eine Wortcloud basierend auf den folgenden Ideen. Antworte in JSON.
                                Beispiel:
                    "{
                      "word": "Kinderbücher",
                      "count": "2",
                      },
                      {
                      "word": "Baum",
                      "count": "1",
                      }
                      "
                                '
                            ],
                            [
                                'role' => 'user',
                                'content' => $ideas->toJson(),
                            ],
                        ],
                    'temperature' => 0.3,
                ],
            ]);

            $responseData = json_decode($response->getBody(), true);
            $content = $responseData['choices'][0]['message']['content'];
            // Entferne mögliche JSON-Codeblock-Markierungen
            $content = preg_replace('/```json\s*|\s*```/', '', $content);
            $content = json_decode($content, true);
            // Extract the tokens
            $inputToken = $responseData['usage']['prompt_tokens'] ?? 0;
            $outputToken = $responseData['usage']['completion_tokens'] ?? 0;

            // Update the database with the new token counts
            $session = Session::find($sessionId);
            if ($session) {
                $session->input_token += $inputToken;
                $session->output_token += $outputToken;
                $session->save();
            }
          
            // Log the next steps content and tokens
            $wordCloud = [
                'content' => $content,
                'input_token' => $inputToken,
                'output_token' => $outputToken
            ];
            Log::info(json_encode($wordCloud));
           
            return $wordCloud;
        } catch (\Exception $e) {
            Log::error('Error generating word cloud: ' . $e->getMessage());
            return null;
        }
    }

    private function generateNextSteps($topIdeas, $sessionId)
    {
        $client = new Client();
        $apiKey = env('OPENAI_API_KEY');

        try {
            $response = $client->post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => "gpt-4o-mini",
                    'messages' => [
                            [
                                'role' => 'system',
                                'content' => 'Du sollst die next Steps für die Gruppe nennen. Maximal 3, antworte kurz und in einer HTML Liste'
                            ],
                            [
                                'role' => 'user',
                                'content' => $topIdeas->toJson(),
                            ],
                        ],
                    'temperature' => 0.3,
                ],
            ]);

            $responseData = json_decode($response->getBody(), true);

            // Extract the tokens
            $inputToken = $responseData['usage']['prompt_tokens'] ?? 0;
            $outputToken = $responseData['usage']['completion_tokens'] ?? 0;

            // Update the database with the new token counts
            $session = Session::find($sessionId);
            if ($session) {
                $session->input_token += $inputToken;
                $session->output_token += $outputToken;
                $session->save();
            }

            // Log the next steps content and tokens
            $nextSteps = [
                'content' => $responseData['choices'][0]['message']['content'] ?? 'N/A',
                'input_token' => $inputToken,
                'output_token' => $outputToken
            ];
            Log::info(json_encode($nextSteps));

            return $nextSteps;
        } catch (\Exception $e) {
            Log::error('Error generating next steps: ' . $e->getMessage());
            return null;
        }
    }
}