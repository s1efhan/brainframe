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
use App\Events\UserJoinedSession;
use App\Events\UserLeftSession;
use App\Events\SwitchPhase;
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
            $newContributorsAmount = Contributor::where('session_id', $sessionId)->distinct('user_id')->count();
            event(new UserJoinedSession($sessionId, $userId, $newContributorsCount, $newContributorsAmount));

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
        
        $session = is_numeric($sessionId) ? Session::with(['host', 'method'])->find($sessionId) : null;
        
        if (!$session) {
            return response()->json(['message' => 'Session not found'], 404);
        }
        
        Log::info($sessionId);
        
        $ideasCount = Idea::where('session_id', $session->id)
            ->whereNull('tag')
            ->selectRaw('round, contributor_id, count(*) as count')
            ->groupBy('round', 'contributor_id')
            ->get()
            ->groupBy('round')
            ->map(function ($roundGroup) {
                return [
                    'round' => $roundGroup->first()->round,
                    'sum' => $roundGroup->sum('count'),
                    'contributors' => $roundGroup->pluck('count', 'contributor_id')->toArray()
                ];
            })
            ->values()
            ->keyBy('round');
        
        $votedIdeasCounts = Vote::where('session_id', $sessionId)
            ->where('voting_phase', $session->voting_phase)
            ->selectRaw('contributor_id, count(*) as count')
            ->groupBy('contributor_id')
            ->pluck('count', 'contributor_id');
        
        $totalIdeasToVoteCount = Idea::where('session_id', $sessionId)
            ->whereNotNull('tag')
            ->where('tag', '!=', '')
            ->count();
        
        $contributors = Contributor::where('session_id', $session->id)
            ->get()
            ->map(function ($contributor) use ($votedIdeasCounts, $totalIdeasToVoteCount) {
                return [
                    'id' => $contributor->id,
                    'role_name' => $contributor->role->name,
                    'last_active' => $contributor->last_ping,
                    'voted_ideas_count' => $votedIdeasCounts[$contributor->id] ?? 0
                ];
            });
        
        $contributorsCount = $contributors->count();
        $contributorsAmount = $contributors->unique('id')->count();
        
        Log::info('ContributorsCount: ' . $contributorsCount);
        Log::info($contributors->toJson());
        
        return response()->json([
            'id' => $session->id,
'session_host' => Contributor::where('session_id', $session->id)
                             ->where('user_id', $session->host_id)
                             ->value('id'),
            'method_id' => $session->method_id,
            'contributors' => $contributors,
            'target' => $session->target,
            'voting_phase' => $session->voting_phase,
            'previous_phase'=>$session->previous_phase,
            'method_name' => $session->method->name,
            'session_phase' => $session->active_phase,
            'current_round' => $session->active_round,
            'contributors_count' => $contributorsCount,
            'contributors_amount' => $contributorsAmount,
            'ideas_count' => $ideasCount,
            'total_ideas_to_vote_count' => $totalIdeasToVoteCount
        ], 200);
    }
    public function votingPhaseUpdate(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'voting_phase' => 'required|integer'
        ]);
    
        $sessionId = $request->input('session_id');
        $votingPhase = $request->input('voting_phase');
    
        $session = Session::findOrFail($sessionId);
        $session->voting_phase = $votingPhase;
        $session->save();
    
        event(new SwitchPhase($sessionId, $votingPhase));
    
        return response()->json([
            'success' => true,
            'message' => 'Voting phase updated successfully',
            'new_phase' => $votingPhase
        ]);
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
                'role' => $role->icon,
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

    public function invite(Request $request)
    {
        try {
            Log::info('Session Invite Request:', $request->all());
    
            $validated = $request->validate([
                'session_id' => 'required',
                'host_id' => 'required', // This is actually contributor_id
                'contributor_email_addresses' => 'required|array',
                'contributor_email_addresses.*' => 'email',
            ]);
    
            $sessionId = $validated['session_id'];
            $contributorId = $validated['host_id'];
            $contributorEmailAddresses = $validated['contributor_email_addresses'];
    
            // Find the contributor and get the associated user
            $host = User::whereIn('id', function($query) use ($contributorId) {
                $query->select('user_id')
                      ->from('bf_contributors')
                      ->where('id', $contributorId);
            })->first();

            if (!$host) {
                throw new \Exception("No user found for contributor ID: {$contributorId}");
            }
    
            $userName = explode('.', $host->email)[0];
            $emailMessage = "Hallo, <br> Du wurdest von {$userName} zu einer Ideen-Session eingeladen. <br> <br>
                Du kannst über folgenden Link beitreten: <a href='https://stefan-theissen.de/brainframe/{$sessionId}'>Brainframe</a>. <br> <br>
                Alternativ kannst du auch unter <a href='https://stefan-theissen.de/brainframe'>Brainframe</a> vorbeischauen und mit dem Code: {$sessionId} beitreten.";
    
            $sentCount = 0;
            foreach ($contributorEmailAddresses as $email) {
                if ($email) {
                    try {
                        Mail::html($emailMessage, function ($message) use ($email, $userName) {
                            $message->to($email)
                                ->subject("{$userName} - Einladung zur Ideen-Session");
                        });
                        $sentCount++;
                    } catch (\Exception $e) {
                        Log::error("Failed to send email to {$email}: " . $e->getMessage());
                    }
                }
            }
    
            Log::info("Invitations sent: {$sentCount} out of " . count($contributorEmailAddresses));
    
            return response()->json([
                'message' => 'Invitations sent successfully.',
                'sent_count' => $sentCount,
                'total_count' => count($contributorEmailAddresses)
            ], 200);
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation failed:', $e->errors());
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error in invite function: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred while processing your request.'], 500);
        }
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
                $inputIdeas = Idea::where('session_id', $session->id)
                ->whereNull('tag')
                ->get();
            $response = [
                'session_id' => $session->id,
                'target' => $session->target,
                'top_ideas' => $topIdeas,
                'ideas' => $inputIdeas->map(function ($idea) {
                    return [
                        'id' => $idea->id,
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
                'ideas' => $inputIdeas->map(function ($idea) {
                    return [
                        'id' => $idea->id,
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
        $wordCloudData = [];
        $stopWords = ['der', 'die', 'das', 'den', 'dem', 'des', 'ein', 'eine', 'einer', 'eines', 'für', 'und', 'oder', 'aber', 'doch', 'sondern', 'denn'];
        $allIdeas = $ideas->concat($topIdeas)->filter(function($idea) {
            return !empty($idea->tag);
        });
        
        foreach ($allIdeas as $idea) {
            $text = strtolower($idea->idea_title . ' ' . strip_tags($idea->idea_description));
            $text = preg_replace('/[^a-z0-9\s]/', '', $text);
            $words = explode(' ', $text);
            foreach ($words as $word) {
                $word = trim($word);
                if (strlen($word) > 3 && !in_array($word, $stopWords)) {
                    $wordCloudData[$word] = ($wordCloudData[$word] ?? 0) + 1;
                }
            }
        }
        
        $wordCloudData = array_filter($wordCloudData, function($count) {
            return $count >= 2;
        });
        
        $formattedWordCloudData = array_map(function($word, $count) {
            return ["word" => $word, "count" => (string)$count];
        }, array_keys($wordCloudData), $wordCloudData);
        
        $nextSteps = "<ul>
            <li>Ziele & Aufgaben definieren: Konkrete Ergebnisse festlegen, Verantwortlichkeiten zuweisen.</li>
            <li>Zeitplan & Ressourcen planen: Meilensteine setzen, benötigte Mittel zuordnen.</li>
            <li>Umsetzung & Kontrolle: Start der Implementierung, regelmäßige Fortschrittsprüfung.</li>
        </ul>";
        
        return [
            'wordCloudData' => $formattedWordCloudData,
            'nextSteps' => $nextSteps
        ];
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
        Log::info($userId);
        Log::info($session->host_id);

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