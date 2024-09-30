<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Session;
use App\Models\ApiLog;
use App\Models\Idea;
use App\Models\Vote;
use App\Models\User;
use App\Models\Contributor;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Log;
use App\Events\SessionResumed;
use App\Events\SessionStarted;
use App\Events\SessionPaused;
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
        return response()->json([
            'session' => [
                'id' => $session->id,
                'method' => [
                    'id' => $session->method->id,
                    'name' => $session->method->name,
                    'description' => $session->method->description,
                    'time_limit' => $session->method->time_limit,
                    'round_limit' => $session->method->round_limit
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
        Log::info("collectingRound - voteRound: " . $collectingRound . "-" . $voteRound);
        $session->update(['is_paused' => false, 'seconds_left' => $session->method->time_limit, 'collecting_round' => $collectingRound, 'vote_round' => $voteRound]);
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

        if ($collectingRound >= $session->method->round_limit) {
            $voteRound++;
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

        $ideas = Idea::where('session_id', $sessionId)
            ->where('round', $round)
            ->select('id', 'text_input', 'contributor_id', 'round')
            ->get();
        Log::info(message: '$round, $sessionId' . $round . $sessionId);
        // Überprüfen, ob Ideen gefunden wurden
        if ($ideas->isEmpty()) {
            Log::info('Keine ideen Fehler');
            return false;
        }
        $apiKey = env('OPENAI_API_KEY');
        $client = new Client();

        $ideasFormatted = $ideas->map(function ($idea) use ($session) {
            return [
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
                            'content' => 'Du bist verantwortlich für die Verarbeitung und Filterung von Brainstorming-Ergebnissen. Dein Ziel ist die Qualität und Verständlichkeit der eingesandten Ideen sicherzustellen.
                                    Folge diesen Anweisungen strikt:
                    Die Ideen verfolgen alle das Ziel: "' . $session->target . '".
                       1) Erstelle einen umfassenden title, der die Konzepte und Basis der Idea widerspiegelt.
                       2) Fülle description mit mindestens 2 bis zu 3 Stichpunkten, die verschiedene Aspekte der Idea abdeckt, sei kreativ.
                       3) Wähle einen thematischen tag, der die Kernessenz der Idea erfasst und die Ideen zu Gruppen zuordbar macht.
                       4) Formatiere die Ausgabe als JSON-Array mit den Feldern: contributor_id, title, description, tag wie im Beispiel.
                    
                       Beispiel:
                    {
                      "contributor_id": 1",
                      "title": "Lifestyle-Marken spielen gegeneinander",
                      "description": "<ul><li>Marken wie Nestle, Coca Cola oder McDonalds treten gegeneinander an</li><li>ähnlich zu Hunger Games</li><li>Kritik an Konsumgesellschaft</li></ul>",
                      "tag": "Gesellschaftskritik",
                      "round": "2"
                    }
                    '
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
                'contributor_id' => Contributor::where('user_id', $session->host_id)->where('session_id', $sessionId)->first()->id, //ich will die contributor_id des host
                'request_data' => json_decode($ideasFormatted, true),
                'response_data' => $responseBody,
                'prompt_tokens' => $responseBody['usage']['prompt_tokens'] ?? 0,
                'completion_tokens' => $responseBody['usage']['completion_tokens'] ?? 0
            ]);

            if (isset($responseBody['choices'][0]['message']['content'])) {
                $content = $responseBody['choices'][0]['message']['content'];
                // Entferne mögliche JSON-Codeblock-Markierungen
                $content = preg_replace('/```json\s*|\s*```/', '', $content);
                $newIdeas = json_decode($content, true);

                if (is_array($newIdeas)) {
                    foreach ($newIdeas as $idea) {
                        Idea::create([
                            'title' => $idea['title'] ?? '',
                            'description' => $idea['description'] ?? '',
                            'tag' => $idea['tag'] ?? '',
                            'contributor_id' => $idea['contributor_id'] ?? 0,
                            'session_id' => $sessionId,
                            'round' => $idea['round'] ?? 0
                        ]);
                    }
                    Log::info('New ideas saved successfully');
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

        // Filter Ideen nach session_id und ausschließen der Ideen des aktuellen Contributors
        $ideas = Idea::where('session_id', $sessionId)
            ->where('contributor_id', '!=', $contributorId)
            ->select('id', 'text_input')
            ->get();

        // Abrufen des Ziels der Sitzung
        $sessionTarget = Session::where('id', $sessionId)->value('target');

        // Standardnachricht
        $userContent = 'Es gibt noch keine Ideen, aber das Thema des Brainstorming Prozesses ist: ' . $sessionTarget . ". Sei also kreativ und denk um die Ecke.";

        if (!$ideas->isEmpty()) {
            // Formatieren der Ideen für die API-Anfrage
            $ideasFormatted = $ideas->map(function ($idea) {
                return ['id' => $idea->id, 'text' => $idea->text_input];
            })->toJson(JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $userContent = "Sei kreativ und denk um die Ecke. Das sind die bisherigen Ideen der anderen Teilnehmer zum Thema " . $sessionTarget . " ." . $ideasFormatted;
        }

        try {
            $requestData = [
                'model' => "gpt-4",
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Deine Aufgabe ist es, mir auf Grundlage der dir gezeigten Ideen einen einzigen kurzen Eisbrecher zu geben, damit ich inspiriert werde, weitere Ideen zu entwickeln. Antworte mit maximal 20 Worten, bestehend aus 2 Teilsätzen wobei der zweite Satz mit "z.B" beginnt um eine mögliche Idee zu nennen. Nenne auf keinen Fall eine bereits vorhandene Idee.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $userContent,
                    ],
                ],
                'temperature' => 0.3,
            ];
            Log::info('OpenAI API Request:', ['request' => $requestData]);
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
                            'content' => 'Deine Aufgabe ist es, mir auf Grundlage der dir gezeigten Ideen einen einzigen kurzen Eisbrecher zu geben, damit ich inspiriert werde, weitere Ideen zu entwickeln. Antworte mit maximal 20 Worten, bestehend aus 2 Teilsätzen wobei der zweite Satz mit "z.B" beginnt um eine mögliche Idee zu nennen. Nenne auf keinen Fall eine bereits vorhandene Idee.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $userContent,
                        ],
                    ],
                    'temperature' => 0.3,
                ],
            ]);

            $responseBody = json_decode($response->getBody(), true);
            Log::info('OpenAI API Response:', ['response' => $responseBody]);

            // Extrahiere die relevante Nachricht aus der API-Antwort
            $iceBreakerMsg = $responseBody['choices'][0]['message']['content'] ?? 'Keine Antwort erhalten';
            ApiLog::create([
                'session_id' => $sessionId,
                'contributor_id' => $contributorId,
                'request_data' => json_encode($requestData),
                'response_data' => json_encode($responseBody),
                'prompt_tokens' => $responseBody['usage']['prompt_tokens'] ?? 0,
                'completion_tokens' => $responseBody['usage']['completion_tokens'] ?? 0
            ]);
            return response()->json(['iceBreaker_msg' => $iceBreakerMsg]);

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            Log::error('ClientException: ' . $responseBodyAsString);
            return response()->json(['iceBreaker_msg' => 'Fehler: ' . $responseBodyAsString], 500);
        } catch (\Exception $e) {
            Log::error('General Exception: ' . $e->getMessage());
            return response()->json(['iceBreaker_msg' => 'Fehler: ' . $e->getMessage()], 500);
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
            $text = strtolower($idea->title . ' ' . strip_tags($idea->description));
            $text = preg_replace('/[^a-z0-9\s]/', '', $text);
            $words = explode(' ', $text);

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

        if ($format === 'pdf') {
            $html = view('pdf.session_details', $data)->render();
            $pdf = PDF::loadHTML($html);
            $filename = $session->target ?? 'session_details';
            $filename .= '.pdf';
            return $pdf->download($filename);
        }

        // Standardmäßig HTML zurückgeben
        return view('pdf.session_details', $data);
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
