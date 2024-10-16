<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Session;
use App\Models\ApiLog;
use App\Models\Idea;
use App\Models\Vote;
use App\Models\User;
use App\Models\Role;
use App\Models\Contributor;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Log;
use App\Events\SessionResumed;
use App\Events\RotateContributorRoles;
use App\Events\SessionStarted;
use App\Events\SessionPaused;
use App\Events\IdeasFormatted;
use App\Events\SessionStopped;
use GuzzleHttp\Client;

class SessionController extends Controller
{
    public function delete(Request $request)
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
    public function alter(Request $request)
    {
        $sessionId = $request->input('session_id');
        $userId = $request->input('user_id');
        $methodId = $request->input('method_id');
        $target = $request->input('target');
       
        $todaySessionCount = Session::where('host_id', $userId)
            ->whereDate('created_at', Carbon::today())
            ->count();

        if ($todaySessionCount >= 10) {
            return response()->json([
                'message' => 'Du hast heute schon dein Limit von 10 Sessions erreicht, bitte versuche es ein andermal wieder.'
            ], 403);
        }
        $session = Session::find($sessionId);

        if (!$session) {
            // Session existiert nicht, also erstellen wir eine neue
            $session = new Session();
            $session->id = $sessionId;
            $session->host_id = $userId;
            $session->method_id = $methodId;
            $session->target = $target;
            $session->phase = 'collecting';
            $session->is_paused = true;
            $session->save();

            return response()->json(['message' => 'Session erfolgreich erstellt'], 201);
        }

        // Session existiert, also überprüfen wir die Berechtigung zum Aktualisieren
        if ($userId == $session->host_id && ($session->phase === 'collecting' || $session->phase === null)) {
            $session->update([
                'method_id' => $methodId,
                'target' => $target
            ]);

            return response()->json(['message' => 'Session erfolgreich aktualisiert'], 200);
        }

        return response()->json(['message' => 'Keine Berechtigung zum Aktualisieren'], 403);
    }
    public function get($sessionId)
    {
        $session = Session::with(['host', 'method'])->findOrFail($sessionId);

        $timerKey = "timer_{$sessionId}";
        $endTime = Cache::get($timerKey);
        if ($endTime) {
            $secondsLeft = $endTime ? now()->diffInSeconds($endTime, false) : 0;
        } else {
            $secondsLeft = $session->seconds_left;
        }
        $roundedSecondsLeft = round($secondsLeft);
        $session->update(['seconds_left' => $roundedSecondsLeft]);
        $tokenCounts = ApiLog::where('session_id', $sessionId)
            ->selectRaw('SUM(prompt_tokens) as prompt_tokens, SUM(completion_tokens) as completion_tokens')
            ->first();
        $prompt_tokens = $tokenCounts->prompt_tokens ?? 0;
        $completion_tokens = $tokenCounts->completion_tokens ?? 0;
        $roundLimit = $session->method->name === '6-3-5'
            ? max($session->contributors()->count(), 2)
            : $session->method->round_limit;

        return response()->json([
            'session' => [
                'id' => $session->id,
                'method' => [
                    'id' => $session->method->id,
                    'name' => $session->method->name,
                    'description' => $session->method->description,
                    'time_limit' => $session->method->time_limit,
                    'idea_limit' => $session->method->idea_limit,
                    'round_limit' => $roundLimit
                ],
                'completion_tokens' => $completion_tokens,
                'prompt_tokens' => $prompt_tokens,
                'created_at' => $session->created_at,
                'target' => $session->target,
                'seconds_left' => max(0, $roundedSecondsLeft),
                'collecting_round' => $session->collecting_round,
                'vote_round' => $session->vote_round,
                'phase' => $session->phase,
                'isPaused' => $session->is_paused
            ]
        ]);
    }

    public function resume(Request $request)
    {
        $sessionId = $request->input('session_id');
        $session = Session::findOrFail($sessionId);
        $session->update(['is_paused' => false]);
        $this->updateCountdown($sessionId, $session->seconds_left);
        event(new SessionResumed($session));
        return response()->json(['message' => 'Session fortgesetzt']);
    }

    public function start(Request $request)
    {
        $sessionId = $request->input('session_id');
        $session = Session::findOrFail($sessionId);
        $collectingRound = $request->input('collecting_round');
        $voteRound = $request->input('vote_round');

        $session->update([
            'is_paused' => false,
            'seconds_left' => $session->method->time_limit,
            'collecting_round' => $collectingRound,
            'vote_round' => $voteRound
        ]);
        Log::info('Method: ' . $session->method->name);
        Log::info('collectingRound: ' . $collectingRound);
        if ($session->method->name === "6 Thinking Hats" && $collectingRound > 1) {

            $this->rotateContributorRoles($session);
        }

        $this->updateCountdown($sessionId, $session->seconds_left);
        event(new SessionStarted($session));

        return response()->json(['message' => 'Session gestartet']);
    }


    public function stop(Request $request)
    {
        $sessionId = $request->input('session_id');
        $voteRound = $request->input('vote_round');
        $collectingRound = $request->input('collecting_round');
        Log::info($collectingRound);
        $session = Session::findOrFail($sessionId);
        event(new SessionPaused($session));
        if ($session->phase === 'collecting') {
            $ideasSent = $this->sendIdeasToGPT($sessionId);
            if (!$ideasSent) {
                return response()->json(['error' => 'Keine Ideen zum Senden gefunden'], 400);
            }
        } else if ($session->phase === 'voting') {
            $voteType = Vote::where('session_id', $sessionId)
                ->where('round', $session->vote_round)
                ->value('vote_type');

            if ($voteType === 'ranking') {
                $session->update(['phase' => 'closing', 'is_paused' => true, 'seconds_left' => 0, 'vote_round' => 0, 'collecting_round' => 0]);
            }
        }
        $roundLimit = $session->method->name === '6-3-5'
            ? max($session->contributors()->count(), 2)
            : $session->method->round_limit;

        if ($collectingRound >= $roundLimit) {
            $session->update(['phase' => 'voting', 'is_paused' => true, 'seconds_left' => 0, 'vote_round' => $voteRound, 'collecting_round' => 0]);
        } else {
            $session->update(['is_paused' => true, 'seconds_left' => 0, 'vote_round' => $voteRound, 'collecting_round' => $collectingRound]);
        }

        $this->stopCountdown($sessionId);
        event(new SessionStopped($session));

        return response()->json(['message' => 'Session gestoppt']);
    }
    private function sendIdeasToGPT($sessionId)
    {
        Log::info("sendToGPT");
        $session = Session::findOrFail($sessionId);
        $round = $session->collecting_round;
        Log::info("round: " . $round);
        $ideas = Idea::where('session_id', $sessionId)
            ->where('round', $round)
            ->select('id', 'text_input', 'contributor_id', 'round')
            ->get();
        Log::info(message: '$round, $sessionId' . $round . $sessionId);
        if ($ideas->isEmpty()) {
            Log::info('Keine ideen Fehler');
            return false;
        }
        $apiKey = env('OPENAI_API_KEY');
        $client = new Client();

        $ideasFormatted = $ideas->map(function ($idea) use ($session) {
            return [
                'id' => $idea->id,
                'target' => $session->target,
                'contributor_id' => $idea->contributor_id,
                'title' => $idea->text_input,
                'description' => $idea->text_input,
                'round' => $idea->round ?? null
            ];
        })->toJson(JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        Log::info(message: $ideasFormatted . " not Empty");
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
                            'content' => 'Du bist verantwortlich für die Verarbeitung von Brainstorming-Ergebnissen. Dein Ziel ist die Qualität und Verständlichkeit der eingesandten Ideen sicherzustellen. Behalte den Kern der Ideen bei und ergänze wenn nötig kreativ. Versuche Tippfehler zu korrigieren.
                                    Folge diesen Anweisungen strikt:
                    Die Ideen verfolgen alle das Ziel: "' . $session->target . '". Achte also darauf, dass die Ideen passend zum Ziel formuliert werden.
                       1) Erstelle einen umfassenden title, der die Konzepte der Idea wiederspiegelt.
                       2) Fülle description mit mindestens 2 bis zu 3 Stichpunkten, die verschiedene Aspekte der Idea abdeckt, sei kreativ.
                       3) Wähle einen thematischen tag, der die Kernessenz der Idea erfasst und die Ideen zu Gruppen zuordbar macht.
                       4) Formatiere die Ausgabe als JSON-Array mit den Feldern: id, contributor_id, title, description, tag wie im Beispiel.
                    
                  Beispiel:
    [
      {
        "id": 1,
        "contributor_id": "1",
        "title": "Lifestyle-Marken spielen gegeneinander",
        "description": "<ul><li>Marken wie Nestle, Coca Cola oder McDonalds treten gegeneinander an</li><li>ähnlich zu Hunger Games</li><li>Kritik an Konsumgesellschaft</li></ul>",
        "tag": "Gesellschaftskritik",
        "round": "2"
      }
    ]'
                        ],
                        [
                            'role' => 'user',
                            'content' => $ideasFormatted,
                        ],
                    ],
                    'temperature' => 0.3,
                ],
            ]);
            $responseBody = json_decode($response->getBody(), true);
            Log::info("Response Body: " . json_encode($responseBody));
            ApiLog::create([
                'session_id' => $sessionId,
                'contributor_id' => Contributor::where('user_id', $session->host_id)->where('session_id', $sessionId)->first()->id,
                'request_data' => json_decode($ideasFormatted, true),
                'response_data' => $responseBody,
                'prompt_tokens' => $responseBody['usage']['prompt_tokens'] ?? 0,
                'completion_tokens' => $responseBody['usage']['completion_tokens'] ?? 0
            ]);

            if (isset($responseBody['choices'][0]['message']['content'])) {
                $content = $responseBody['choices'][0]['message']['content'];
                $content = preg_replace('/```json\s*|\s*```/', '', $content);
                $newIdeas = json_decode($content, true);
                Log::info("Decoded new ideas:", $newIdeas);

                $createdIdeas = [];
                if (is_array($newIdeas)) {
                    foreach ($newIdeas as $idea) {
                        Log::info("Processing idea:", ['idea' => $idea]);
                        $originalIdea = $ideas->firstWhere('id', $idea['id']);
                        Log::info("Original idea found:", ['originalIdea' => $originalIdea ? $originalIdea->toArray() : 'null']);

                        if ($originalIdea) {
                            $createdIdea = Idea::create([
                                'title' => $idea['title'] ?? '',
                                'description' => $idea['description'] ?? '',
                                'tag' => $idea['tag'] ?? '',
                                'contributor_id' => $idea['contributor_id'] ?? 0,
                                'session_id' => $sessionId,
                                'round' => $session->collecting_round,
                                'original_idea_id' => $originalIdea->id
                            ]);
                            $createdIdeas[] = $createdIdea;
                            Log::info("Created new idea:", $createdIdea->toArray());
                        } else {
                            Log::warning("Original idea not found for id: " . $idea['id']);
                        }
                    }

                    event(new IdeasFormatted($createdIdeas, $sessionId));
                    Log::info('New ideas saved and event broadcasted successfully. Created ideas count: ' . count($createdIdeas));
                } elseif (is_object($newIdeas) || (is_array($newIdeas) && !isset($newIdeas[0]))) {
                    $newIdeas = [$newIdeas];
                    foreach ($newIdeas as $idea) {
                        $originalIdea = $ideas->firstWhere('id', $idea['id']);
                        if ($originalIdea) {
                            $createdIdea = Idea::create([
                                'title' => $idea['title'] ?? '',
                                'description' => $idea['description'] ?? '',
                                'tag' => $idea['tag'] ?? '',
                                'contributor_id' => $idea['contributor_id'] ?? 0,
                                'session_id' => $sessionId,
                                'round' => $session->collecting_round,
                                'original_idea_id' => $originalIdea->id
                            ]);
                            $createdIdeas[] = $createdIdea;
                        }
                    }
                    event(new IdeasFormatted($createdIdeas, $sessionId));
                    Log::info('Single idea processed and event broadcasted. Created idea count: ' . count($createdIdeas));
                } else {
                    Log::error('Invalid format for new ideas', ['content' => $content]);
                }
            } else {
                Log::error('Unexpected API response format', $responseBody);
            }
            return response()->json($responseBody);

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            Log::error('ClientException: ' . $responseBodyAsString);
            return response()->json(['message' => 'Fehler: ' . $responseBodyAsString], 500);
        } catch (\Exception $e) {
            Log::error('General Exception: ' . $e->getMessage());
            return response()->json(['message' => 'Fehler: ' . $e->getMessage()], 500);
        }
    }


    public function pause(Request $request)
    {
        $sessionId = $request->input('session_id');
        $session = Session::findOrFail($sessionId);
        $timerKey = "timer_{$sessionId}";
        $endTime = Cache::get($timerKey);
        $secondsLeft = $endTime ? now()->diffInSeconds($endTime, false) : 0;
        $session->update(['is_paused' => true, 'seconds_left' => round($secondsLeft)]);
        $this->stopCountdown($sessionId);
        event(new SessionPaused($session));
        return response()->json(['message' => 'Session pausiert']);
    }

    private function updateCountdown($sessionId, $secondsLeft)
    {
        $timerKey = "timer_{$sessionId}";
        $endTime = now()->addSeconds($secondsLeft);
        Cache::put($timerKey, $endTime, $secondsLeft);
    }

    private function stopCountdown($sessionId)
    {
        $timerKey = "timer_{$sessionId}";
        Cache::forget($timerKey);
    }

    private function rotateContributorRoles(Session $session)
    {
        Log::info("Rotating roles for session {$session->id}, collecting round {$session->collecting_round}");

        $contributors = $session->contributors;
        $roles = Role::whereHas('methods', function ($query) use ($session) {
            $query->where('bf_methods.id', $session->method_id);
        })->orderBy('bf_roles.created_at')->get();
        $rolesCount = $roles->count();

        foreach ($contributors as $index => $contributor) {
            $oldRoleId = $contributor->role_id;
            $newRoleIndex = ($index + $session->collecting_round - 1) % $rolesCount;
            $newRole = $roles[$newRoleIndex];
            $contributor->update(['role_id' => $newRole->id]);

            Log::info("Contributor {$contributor->id} role rotated: {$oldRoleId} -> {$newRole->id}");
        }
        event(new RotateContributorRoles($session->id));
    }
    public function iceBreaker(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:bf_sessions,id',
            'contributor_id' => 'required|exists:bf_contributors,id',
        ]);

        $apiKey = env('OPENAI_API_KEY');
        $client = new Client();
        $sessionId = $request->input('session_id');
        $contributorId = $request->input('contributor_id');
        $iceBreakerCount = ApiLog::where('session_id', $sessionId)
        ->where('contributor_id', $contributorId)
        ->count();
    
    if ($iceBreakerCount >= 10) {
        return response()->json([
            'message' => 'Du hast das Limit von 10 IceBreakern für diese Session erreicht.'
        ], 403);
    }
        $ideas = Idea::where('session_id', $sessionId)
            ->select('id', 'text_input')
            ->get();

        $sessionTarget = Session::where('id', $sessionId)->value('target');

        $userContent = $ideas->isEmpty()
            ? "Es gibt noch keine Ideen zum Thema {$sessionTarget}."
            : "Bisherige Ideen zum Thema {$sessionTarget}: " . $ideas->pluck('text_input')->implode(', ');

        try {
            $response = $client->post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => "gpt-4",
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => "Ziel: Antwort auf die Frage/ das Ziel {$sessionTarget} finden. FORMAT: Zwei Teilsätze, max. 20 Wörter. 2. Satz: 'z.B.' + konkretes Beispiel. Wichtig: Keine vorhandenen Ideen wiederholen, gib nur neue Ideen als Antwort. Nur spezifische, präzise Antworten"
                        ],
                        [
                            'role' => 'user',
                            'content' => $userContent . " Gib eine neue, kreative Antwort auf: " . $sessionTarget . " im vorgegebenen Format."
                        ],
                    ],
                    'temperature' => 0.3,
                ],
            ]);

            $responseBody = json_decode($response->getBody(), true);
            $iceBreakerMsg = $responseBody['choices'][0]['message']['content'] ?? 'Keine Antwort erhalten';

            ApiLog::create([
                'session_id' => $sessionId,
                'contributor_id' => $contributorId,
                'request_data' => json_encode($userContent),
                'response_data' => json_encode($responseBody),
                'prompt_tokens' => $responseBody['usage']['prompt_tokens'] ?? 0,
                'completion_tokens' => $responseBody['usage']['completion_tokens'] ?? 0
            ]);

            return response()->json(['iceBreaker_msg' => $iceBreakerMsg]);

        } catch (\Exception $e) {
            Log::error('API Error: ' . $e->getMessage());
            return response()->json(['iceBreaker_msg' => 'Fehler bei der Ideengenerierung.'], 500);
        }
    }

    public function getClosing($sessionId)
    {
        $session = Session::findOrFail($sessionId);
        $ideas = Idea::where('session_id', $sessionId)->get();

        $wordCloudAndNextSteps = $this->generateWordCloudAndNextSteps($ideas);

        $tagList = Idea::where('session_id', $sessionId)
            ->whereNotNull('tag')
            ->groupBy('tag')
            ->selectRaw('tag, COUNT(*) as count')
            ->get();

        $tokenCounts = ApiLog::where('session_id', $sessionId)
            ->selectRaw('SUM(prompt_tokens) as prompt_tokens, SUM(completion_tokens) as completion_tokens')
            ->first();

        $prompt_tokens = $tokenCounts->prompt_tokens ?? 0;
        $completion_tokens = $tokenCounts->completion_tokens ?? 0;
        return response()->json([
            'wordCloud' => $wordCloudAndNextSteps['wordCloudData'],
            'nextSteps' => $wordCloudAndNextSteps['nextSteps'],
            'tagList' => $tagList,
            'prompt_tokens' => $prompt_tokens,
            'completion_tokens' => $completion_tokens,
        ]);
    }

    private function generateWordCloudAndNextSteps($ideas)
    {
        $wordCloudData = [];
        $stopWords = ['der', 'die', 'das', 'den', 'dem', 'des', 'ein', 'eine', 'einer', 'eines', 'für', 'und', 'oder', 'aber', 'doch', 'sondern', 'denn'];

        foreach ($ideas as $idea) {
            $text = mb_strtolower($idea->title . ' ' . strip_tags($idea->description), 'UTF-8');
            $text = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text);
            $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($words as $word) {
                $word = trim($word);
                if (strlen($word) > 3 && !in_array($word, $stopWords)) {
                    $wordCloudData[$word] = ($wordCloudData[$word] ?? 0) + 1;
                }
            }
        }

        $wordCloudData = array_filter($wordCloudData, function ($count) {
            return $count >= 2;
        });

        $formattedWordCloudData = array_map(function ($word, $count) {
            return ["word" => $word, "count" => (string) $count];
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
            $host = User::whereIn('id', function ($query) use ($contributorId) {
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
    public function downloadCSV($sessionId)
    {
        $session = Session::with(['method', 'host', 'contributors.role', 'contributors.user'])->findOrFail($sessionId);
        $ideas = Idea::where('session_id', $sessionId)->with('contributor.role')->get();
        $votes = Vote::where('session_id', $sessionId)->get();
        $contributors = $session->contributors;

        $hostContributor = $contributors->firstWhere('user_id', $session->host_id);
        $hostEmail = $session->host->email ?? 'N/A';
        $hostRole = $hostContributor ? $hostContributor->role->name : 'N/A';

        $endTime = $votes->max('created_at');
        $maxRound = $ideas->max('round');

        $csvData = [
            ['Session Information'],
            ['Target', $session->target],
            ['Method', $session->method->name],
            ['Host', $hostEmail . ' (' . $hostRole . ')'],
            ['Start Time', $session->created_at],
            ['End Time', $endTime],
            ['Total Contributors', $contributors->count()],
            [''],
            ['Ideas'],
            ['Round', 'Idea ID', 'Title', 'Description', 'Tag', 'Contributor', 'Highest Round', 'Avg Rating', 'Votes Count', 'Created At']
        ];

        $groupedIdeas = $ideas->groupBy(function ($idea) {
            return $idea->original_idea_id ?? $idea->id;
        });

        foreach ($groupedIdeas as $originalIdeaId => $relatedIdeas) {
            $originalIdea = $relatedIdeas->firstWhere('original_idea_id', null) ?? $relatedIdeas->first();
            $taggedIdea = $relatedIdeas->firstWhere('tag', '!=', null);

            $highestRound = $relatedIdeas->max('round');
            $ideaVotes = $votes->whereIn('idea_id', $relatedIdeas->pluck('id'))
                ->where('round', $highestRound);
            $avgRating = $ideaVotes->avg('value');

            $csvData[] = [
                $originalIdea->round,
                $originalIdea->id,
                $taggedIdea ? $taggedIdea->title : $originalIdea->title,
                $taggedIdea ? strip_tags($taggedIdea->description) : strip_tags($originalIdea->description),
                $taggedIdea ? $taggedIdea->tag : 'N/A',
                $originalIdea->contributor->role->name,
                $highestRound,
                number_format($avgRating, 2),
                $ideaVotes->count(),
                $originalIdea->created_at
            ];
        }

        $csvData[] = [''];
        $csvData[] = ['Votes'];
        $csvData[] = ['Round', 'Idea ID', 'Idea Title', 'Contributor', 'Value', 'Vote Type'];

        foreach ($votes as $vote) {
            $idea = $ideas->find($vote->idea_id);
            $contributor = $contributors->find($vote->contributor_id);
            $csvData[] = [
                $vote->round,
                $vote->idea_id,
                $idea->title,
                $contributor->role->name,
                $vote->value,
                $vote->vote_type
            ];
        }

        $csvData[] = [''];
        $csvData[] = ['Session Summary'];
        $csvData[] = ['Total Ideas', $ideas->whereNull('tag')->count()];
        $csvData[] = ['Total Votes', $votes->count()];

        $phases = [
            'Collecting' => [
                'start' => $ideas->min('created_at'),
                'end' => $ideas->max('created_at')
            ],
            'Voting' => [
                'start' => $votes->min('created_at'),
                'end' => $votes->max('created_at')
            ]
        ];

        foreach ($phases as $phaseName => $phase) {
            $duration = ceil($phase['start']->diffInMinutes($phase['end']));
            $csvData[] = [$phaseName . ' Phase Duration (minutes)', $duration];
        }

        $totalDuration = ceil($phases['Collecting']['start']->diffInMinutes($phases['Voting']['end']));
        $csvData[] = ['Total Session Duration (minutes)', $totalDuration];

        $tokenCounts = ApiLog::where('session_id', $sessionId)
            ->selectRaw('SUM(prompt_tokens) as prompt_tokens, SUM(completion_tokens) as completion_tokens')
            ->first();
        $csvData[] = ['Prompt Tokens', $tokenCounts->prompt_tokens ?? 0];
        $csvData[] = ['Completion Tokens', $tokenCounts->completion_tokens ?? 0];
        $csvData[] = ['Estimated OpenAI API Cost (cents)', number_format(($tokenCounts->prompt_tokens * 0.00003 + $tokenCounts->completion_tokens * 0.00006), 2, '.', '')];

        $output = fopen('php://temp', 'w');
        foreach ($csvData as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $session->target . '_summary.csv"');
    }
    public function sendSummary(Request $request)
    {
        $sessionId = $request->input('session_id');
        $contributorEmails = $request->input('contributor_emails', []);
        if (empty($contributorEmails)) {
            return response()->json(['error' => 'Keine E-Mail-Adressen angegeben.'], 400);
        }
        $session = Session::findOrFail($sessionId);
        $ideas = Idea::where('session_id', $sessionId)->get();
        $votes = Vote::where('session_id', $sessionId)->get();
        $contributors = Contributor::where('session_id', $sessionId)->get();
        $personalContributor = $contributors->where('isMe', true)->first();

        $closingData = $this->getClosingPdf($sessionId);
        Log::info(json_encode($closingData));

        $data = [
            'session' => $session,
            'ideas' => $ideas,
            'votes' => $votes,
            'contributors' => $contributors,
            'personalContributor' => $personalContributor,
            'wordCloud' => $closingData['wordCloud'],
            'tagList' => $closingData['tagList'],
            'nextSteps' => $closingData['nextSteps'],
            'completion_tokens' => $closingData['completion_tokens'],
            'prompt_tokens' => $closingData['prompt_tokens']
        ];

        $html = view('pdf.session_details', $data)->render();

        $pdf = PDF::loadHTML($html);
        $filename = ($sessionDetails->target ?? 'session_details') . '.pdf';

        $emailMessage = "Hallo,<br><br>
             Du hast erfolgreich an der Ideen-Session \"{$session->target}\" teilgenommen.<br><br>
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

    public function downloadSummary($sessionId)
    {
        $session = Session::findOrFail($sessionId);
        $ideas = Idea::where('session_id', $sessionId)->get();
        $votes = Vote::where('session_id', $sessionId)->get();
        $contributors = Contributor::where('session_id', $sessionId)->get();
        $personalContributor = $contributors->where('isMe', true)->first();

        $closingData = $this->getClosingPdf($sessionId);
        Log::info(json_encode($closingData));

        $data = [
            'session' => $session,
            'ideas' => $ideas,
            'votes' => $votes,
            'contributors' => $contributors,
            'personalContributor' => $personalContributor,
            'wordCloud' => $closingData['wordCloud'],
            'tagList' => $closingData['tagList'],
            'nextSteps' => $closingData['nextSteps'],
            'completion_tokens' => $closingData['completion_tokens'],
            'prompt_tokens' => $closingData['prompt_tokens']
        ];
        $format = request('format', 'html');

        $html = view('pdf.session_details', $data)->render();
        $pdf = PDF::loadHTML($html);
        $filename = $session->target ?? 'session_details';
        $filename .= '.pdf';
        return $pdf->download($filename);

        // Standardmäßig HTML zurückgeben
        //   return view('pdf.session_details', $data);
    }

    private function getClosingPdf($sessionId)
    {
        $ideas = Idea::where('session_id', $sessionId)->get();
        $wordCloudAndNextSteps = $this->generateWordCloudAndNextSteps($ideas);
        $tagList = Idea::where('session_id', $sessionId)
            ->whereNotNull('tag')
            ->groupBy('tag')
            ->selectRaw('tag, COUNT(*) as count')
            ->get();
        $tokenCounts = ApiLog::where('session_id', $sessionId)
            ->selectRaw('SUM(prompt_tokens) as prompt_tokens, SUM(completion_tokens) as completion_tokens')
            ->first();

        return [
            'wordCloud' => $wordCloudAndNextSteps['wordCloudData'],
            'nextSteps' => $wordCloudAndNextSteps['nextSteps'],
            'tagList' => $tagList,
            'prompt_tokens' => $tokenCounts->prompt_tokens ?? 0,
            'completion_tokens' => $tokenCounts->completion_tokens ?? 0,
        ];
    }
}
