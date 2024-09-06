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
use App\Events\UserJoinedSession;
use App\Events\UserLeftSession;
use Log;
use GuzzleHttp\Client;

class SessionController extends Controller
{
    public function sessionJoin(Request $request)
    {
        $sessionId = $request->input('session_id');
        $userId = $request->input('user_id');
        if ($sessionId && $userId) {
            Contributor::where('session_id', $sessionId)
                ->where('user_id', $userId)
                ->update(['is_active' => true, 'last_ping' => now()]);

            $newContributorsCount = $this->getActiveContributorsCount($sessionId);

            event(new UserJoinedSession($sessionId, $userId, $newContributorsCount));

            return response()->json([
                'session_id' => $sessionId,
                'user_id' => $userId,
                'contributors_count' => $newContributorsCount,
                'event' => 'join'
            ]);
        } else {
            return response()->json(['message' => 'Session or User not found'], 404);
        }
    }

    public function sessionLeave(Request $request)
    {
        $sessionId = $request->input('session_id');
        $userId = $request->input('user_id');
        if ($sessionId && $userId) {
            Contributor::where('session_id', $sessionId)
                ->where('user_id', $userId)
                ->update(['is_active' => false]);

            $newContributorsCount = $this->getActiveContributorsCount($sessionId);

            event(new UserLeftSession($sessionId, $userId, $newContributorsCount));

            return response()->json([
                'session_id' => $sessionId,
                'user_id' => $userId,
                'contributors_count' => $newContributorsCount,
                'event' => 'leave'
            ]);
        } else
            return response()->json(['message' => 'Session or User not found'], 404);
    }

    public function sessionPing(Request $request)
    {
        $sessionId = $request->input('session_id');
        $userId = $request->input('user_id');
        if ($sessionId && $userId) {
            Contributor::where('session_id', $sessionId)
                ->where('user_id', $userId)
                ->update(['is_active' => true, 'last_ping' => now()]);

            $this->checkInactiveUsers($sessionId);

            $newContributorsCount = $this->getActiveContributorsCount($sessionId);

            return response()->json([
                'session_id' => $sessionId,
                'user_id' => $userId,
                'contributors_count' => $newContributorsCount,
                'event' => 'ping'
            ]);
        }
        return response()->json(['message' => 'Session or User not found'], 404);
    }

    private function getActiveContributorsCount($sessionId)
    {
        return Contributor::where('session_id', $sessionId)
            ->where('is_active', true)
            ->count();
    }

    private function checkInactiveUsers($sessionId)
    {
        $inactiveThreshold = now()->subSeconds(35);

        $inactiveUsers = Contributor::where('session_id', $sessionId)
            ->where('is_active', true)
            ->where('last_ping', '<', $inactiveThreshold)
            ->get();

        foreach ($inactiveUsers as $user) {
            $user->update(['is_active' => false]);
            event(new UserLeftSession($sessionId, $user->user_id, $this->getActiveContributorsCount($sessionId)));
        }
    }

    public function downloadSessionPDF($sessionId)
    {
        $sessionDetails = SessionDetailsCache::findOrFail($sessionId);
        $ideas = $sessionDetails->ideas;
        $groupedIdeasByRound = array_reduce($ideas, function ($result, $idea) {
            $round = $idea['round'];
            if (!isset($result[$round])) {
                $result[$round] = [];
            }
            $result[$round][] = $idea;
            return $result;
        }, []);
        // Fügen Sie einen Parameter hinzu, um zwischen HTML und PDF zu unterscheiden
        $format = request('format', 'html');
        if ($format === 'pdf') {
            $html = view('pdf.session_details', [
                'sessionDetails' => $sessionDetails,
                'groupedIdeasByRound' => $groupedIdeasByRound
            ])->render();
            $pdf = Pdf::loadHTML($html);
            $filename = $sessionDetails->target ?? 'session_details';
            $filename .= '.pdf';
            return $pdf->download($filename);
        }
        // Standardmäßig HTML zurückgeben
        return view('pdf.session_details', [
            'sessionDetails' => $sessionDetails,
            'groupedIdeasByRound' => $groupedIdeasByRound
        ]);
    }

    public function get($sessionId)
    {
        if (!$sessionId) {
            return response()->json(['message' => 'Session ID is required'], 400);
        }

        if (is_numeric($sessionId)) {
            $session = Session::with(['host', 'method'])->find($sessionId);
        }
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
            'current_round' => $session->active_round,
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
                'method_id' => $method->id,
                'host_id' => $session->host_id,
                'active_phase' => $session->active_phase,
                'active_round' => $session->active_round
            ];
        })->sortByDesc('updated_at')->values();

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
        event(new StartCollecting($sessionId, $round));

        return response()->json(['message' => 'Collecting successfully started']);
    }


    public function stopCollecting(Request $request)
    {
        $sessionId = $request->input('session_id');
        $round = $request->input('current_round');
        $session = Session::find($sessionId);
        if ($session) {
            $session->active_round = $round;
            $session->active_phase = 'collectingPhase';
            $session->save();
        }
        // Aktualisiere den Wert der Spalte `active_round` für die Session
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

            $ideas = Idea::where('session_id', $session->id)
                ->whereNotNull('tag')
                ->get();

            $tagList = Idea::where('session_id', $session->id)
                ->whereNotNull('tag')
                ->groupBy('tag')
                ->selectRaw('tag, COUNT(*) as count')
                ->get();

            $WordCloudandNextSteps = $this->generateWordCloudandNextSteps($topIdeas, $ideas, $sessionId);
            $wordCloudData = $WordCloudandNextSteps['wordCloudData'] ?? [];
            $nextSteps = $WordCloudandNextSteps['nextSteps'] ?? '';
            $session = Session::find($sessionId)->fresh();
            $firstIdeaTime = Carbon::parse(Idea::where('session_id', $session->id)->min('created_at'));
            $lastVoteTime = Carbon::parse(Vote::whereIn('idea_id', $ideas->pluck('id'))->max('updated_at'));
            $duration = abs($lastVoteTime->diffInMinutes($firstIdeaTime));

            $method = Method::where('id', $session->method_id)->first();
            $contributorsCount = Contributor::where('session_id', $sessionId)->count();
            $ideasCount = Idea::where('session_id', $sessionId)
                ->whereNull('tag')
                ->count();
            Log::info('contributorsCount: ' . $contributorsCount);
            Log::info('ideasCount: ' . $ideasCount);
            Log::info('duration: ' . $duration);
            Log::info('date: ' . $session->created_at->toDateString());
            Log::info('method: ' . ($method ? $method->name : 'N/A'));
            Log::info('inputToken: ' . ($session->input_token ?? 'N/A'));
            Log::info('outputToken: ' . ($session->output_token ?? 'N/A'));
            Log::info('tagList: ' . json_encode($tagList, JSON_PRETTY_PRINT));

            $contributor_emails = User::select('users.email')
                ->join('bf_contributors', 'users.id', '=', 'bf_contributors.user_id')
                ->where('bf_contributors.session_id', $sessionId)
                ->whereNotNull('users.email')
                ->where('users.email', '!=', '')
                ->get()
                ->pluck('email');

            $response = [
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
                'input_token' => $session->input_token ?? 0,
                'output_token' => $session->output_token ?? 0,
                'word_cloud_data' => $wordCloudData,
                'tag_list' => $tagList,
                'contributor_emails' => $contributor_emails,
                'next_steps' => $nextSteps,
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
                'contributor_emails' => $contributor_emails,
                'next_steps' => $nextSteps,
            ]);

            return response()->json($response);
        } else {
            return response()->json($cachedDetails);
        }
    }
    private function generateWordCloudandNextSteps($topIdeas, $ideas, $sessionId)
    {
        $client = new Client();
        $apiKey = env('OPENAI_API_KEY');
        Log::info('Logging top ideas and ideas', ['topideas' => $topIdeas, 'ideas' => $ideas]);

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
                            Danach sollst du die next Steps für die Gruppe nennen nach dem Ideen Sammeln und Auswerten der Voting Ergebnosse. Maximal 3, antworte kurz im imperativ plural und in einer HTML Liste
                                Beispiel:
                    "wordcloud": {
                      "word": "Kinderbücher",
                      "count": "2",
                      },
                      {
                      "word": "Baum",
                      "count": "1",
                      }, 
                      "nextSteps": {
                      "html": "<ul>
<li>Ziele & Aufgaben definieren: Konkrete Ergebnisse festlegen, Verantwortlichkeiten zuweisen.</li>
<li>Zeitplan & Ressourcen planen: Meilensteine setzen, benötigte Mittel zuordnen.</li>
<li>Umsetzung & Kontrolle: Start der Implementierung, regelmäßige Fortschrittsprüfung.</li>
</ul>"
                        }
                      "
                                '
                        ],
                        [
                            'role' => 'user',
                            'content' => 'alle Ideen: ' . $ideas->toJson() .
                                'Top 5 Ideen: ' . $topIdeas->toJson()
                        ],
                    ],
                    'temperature' => 0.3,
                ],
            ]);
            $responseData = json_decode($response->getBody(), true);
            $content = $responseData['choices'][0]['message']['content'];
            $content = preg_replace('/```json\s*(.*?)\s*```/s', '$1', $content);
            Log::info("Content: " . $content);

            // Decode the JSON string into an associative array
            $decodedContent = json_decode($content, true);

            // Now use $decodedContent instead of $content
            $wordCloudData = $decodedContent['wordcloud'] ?? [];
            $nextSteps = $decodedContent['nextSteps']['html'] ?? '';
            $inputToken = $responseData['usage']['prompt_tokens'] ?? 0;
            $outputToken = $responseData['usage']['completion_tokens'] ?? 0;

            Log::info("wordCloudData: " . json_encode($wordCloudData));
            Log::info("nextSteps: " . $nextSteps);

            // Update the database with the new token counts
            $session = Session::find($sessionId);
            if ($session) {
                $session->input_token += $inputToken;
                $session->output_token += $outputToken;
                $session->save();
            }

            Log::info($wordCloudData);
            Log::info($nextSteps);
            return [
                'wordCloudData' => $wordCloudData,
                'nextSteps' => $nextSteps
            ];
        } catch (\Exception $e) {
            Log::error('Error generating word cloud: ' . $e->getMessage());
            return null;
        }
    }
    public function sendPDF(Request $request)
    {
        $contributorEmails = $request->input('contributor_emails', []);
        if (empty($contributorEmails)) {
            return response()->json(['error' => 'Keine E-Mail-Adressen angegeben.'], 400);
        }
        $sessionId = $request->input('session_id');
        $sessionDetails = SessionDetailsCache::findOrFail($sessionId);
        $ideas = $sessionDetails->ideas;
        
        $groupedIdeasByRound = collect($ideas)->groupBy('round')->toArray();
    
        $html = view('pdf.session_details', [
            'sessionDetails' => $sessionDetails,
            'groupedIdeasByRound' => $groupedIdeasByRound
        ])->render();
    
        $pdf = PDF::loadHTML($html);
        $filename = ($sessionDetails->target ?? 'session_details') . '.pdf';
    
        $emailMessage = "Hallo,<br><br>
            Du hast erfolgreich an der Ideen-Session \"{$sessionDetails->target}\" teilgenommen.<br><br>
            Hier ist dein Abschluss PDF mit allen wichtigen Informationen über die Session.<br><br>
            Viele Grüße<br>
            BrainFrame";
    
        foreach ($contributorEmails as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Mail::send([], [], function ($message) use ($email, $emailMessage, $pdf, $filename) {
                    $message->to($email)
                        ->subject("BrainFrame - Abschluss PDF zur Ideen-Session")
                        ->html($emailMessage)
                        ->attachData($pdf->output(), $filename);
                });
            }
        }
    
        return response()->json(['info' => 'Emails erfolgreich versendet.'], 200);
    }
    public function deleteSession(Request $request)
    {
        $sessionId = $request->input('session_id');
        $userId = $request->input('user_id');
        $session = Session::find($sessionId);

        if ($userId == $session->host_id) {
            $session->delete();
            return response()->json(['message' => 'Session erfolgreich gelöscht'], 200);
        }
        return response()->json(['message' => 'Keine Berechtigung zum Löschen'], 403);
    }
    public function alterSession(Request $request)
    {
        $sessionId = $request->input('session_id');
        $userId = $request->input('user_id');
        $newMethod = $request->input('new_method');
        $newTarget = $request->input('new_target');
        $session = Session::find($sessionId);

        if ($userId == $session->host_id && ($session->active_phase === null || $session->active_phase === 'collectingPhase')) {
            $session->update([
                'method_id' => $newMethod,
                'target' => $newTarget
            ]);
            return response()->json(['message' => 'Session erfolgreich aktualisiert'], 200);
        }
        return response()->json(['message' => 'Keine Berechtigung zum Aktualisieren'], 403);
    }
}