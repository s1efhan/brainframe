<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Idea;
use App\Models\Contributor;
use App\Events\LastVote;
use App\Models\Session;
use App\Models\Vote;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use App\Events\SwitchPhase;
use App\Models\ApiLog;
class IdeaController extends Controller
{
    public function getPassedIdeas($sessionId, $personalContributorId, $currentRound)
    {
        Log::info("Start getPassedIdeas", ['sessionId' => $sessionId, 'personalContributorId' => $personalContributorId, 'currentRound' => $currentRound]);

        $contributors = Contributor::where('session_id', $sessionId)->orderBy('id')->get();
        $contributorCount = $contributors->count();
        $ownPosition = $contributors->search(function ($contributor) use ($personalContributorId) {
            return $contributor->id == $personalContributorId;
        });

        if ($ownPosition === false) {
            Log::warning("Personal Contributor not found", ['personalContributorId' => $personalContributorId]);
            return response()->json(['error' => 'Persönlicher Contributor nicht gefunden'], 404);
        }

        $passedIdeas = collect();

        for ($i = 1; $i < $currentRound; $i++) {
            $neighbourPosition = ($ownPosition - $i + $contributorCount) % $contributorCount;
            $neighbourContributor = $contributors[$neighbourPosition];

            $ideaRound = $currentRound - $i;

            Log::info("Processing neighbour", ['currentRound' => $currentRound, 'ideaRound' => $ideaRound, 'neighbourId' => $neighbourContributor->id]);

            $ideas = Idea::where('session_id', $sessionId)
                ->where('round', $ideaRound)
                ->whereNotNull('tag')
                ->where('contributor_id', $neighbourContributor->id)
                ->get();

            Log::info("Ideas found for neighbour", ['count' => $ideas->count(), 'neighbour' => $neighbourContributor->id]);

            $ideasWithIcon = $ideas->map(function ($idea) {
                $idea->contributorIcon = $idea->contributor->role->icon ?? 'default_icon';
                return $idea;
            });

            $passedIdeas = $passedIdeas->concat($ideasWithIcon);
        }

        if ($passedIdeas->isEmpty()) {
            Log::warning("No ideas found for session", ['sessionId' => $sessionId]);
            return response()->json(['error' => 'Keine Ideen gefunden'], 404);
        }

        Log::info("Finished getPassedIdeas", ['totalIdeasPassed' => $passedIdeas->count()]);
        return response()->json($passedIdeas);
    }
    public function sendIdeasToGPT(Request $request)
    {
        $methodName = $request->input("method_name");
        $round = $request->input("round");
        $sessionId = $request->input('session_id');
        $session = Session::find($sessionId);
        if ($methodName == "6-3-5") {
            $ideas = Idea::where('session_id', $sessionId)
                ->where('round', $round)
                ->select('id', 'text_input', 'contributor_id', 'round')
                ->get();
            Log::info('6-3-5 Ideas', ['ideas' => $ideas->toArray()]);
        } else {
            $ideas = Idea::where('session_id', $sessionId)
                ->select('id', 'text_input', 'contributor_id')
                ->get();
        }
        $apiKey = env('OPENAI_API_KEY');
        $client = new Client();
        // Überprüfen, ob Ideen gefunden wurden
        if ($ideas->isEmpty()) {
            Log::info('Keine ideen Fehler');
            return response()->json(['error' => 'Keine Ideen zum Senden gefunden'], 400);
        }
        \Log::info($ideas);
        if ($ideas) {
            $ideasFormatted = $ideas->map(function ($idea) use ($session) {
                return [
                    'target' => $session->target,
                    'contributor_id' => $idea->contributor_id,
                    'ideaTitle' => $idea->text_input,
                    'ideaDescription' => "<ul><li>" . implode("</li><li>", explode("\n", $idea->text_input)) . "</li></ul>",
                    'round' => $idea->round ?? null
                ];
            })->toJson(JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
                    Die Ideen verfolgen alle das Ziel des "target".
                       1) Erstelle einen umfassenden ideaTitle, der die Konzepte und Grundidee widerspiegelt.
                       2) Fülle ideaDescription mit mindestens 2 bis zu 3 Stichpunkten, die verschiedene Aspekte der Ideen abdecken.
                       3) Wähle einen thematischen tag, der die Kernessenz der Ideen erfasst und die Ideen zu Gruppen zuordnen lässt.
                       4) Formatiere die Ausgabe als JSON-Array mit den Feldern: contributor_id, ideaTitle, ideaDescription, tag wie im Beispiel.
                    
                       Beispiel:
                    {
                      "contributor_id": 1",
                      "ideaTitle": "Lifestyle-Marken spielen gegeneinander",
                      "ideaDescription": "<ul><li>Marken wie Nestle, Coca Cola oder McDonalds treten gegeneinander an</li><li>ähnlich zu Hunger Games</li><li>Kritik an Konsumgesellschaft</li></ul>",
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
                ApiLog::create([
                    'session_id' => $sessionId,
                    'contributor_id' => null, // oder der erste contributor_id aus $ideas, falls relevant
                    'request_data' => json_decode($ideasFormatted, true),
                    'response_data' => $responseBody,
                    'icebreaker_msg' => null, // nicht relevant für diese Funktion
                    'prompt_tokens' => $responseBody['usage']['prompt_tokens'] ?? 0,
                    'completion_tokens' => $responseBody['usage']['completion_tokens'] ?? 0,
                    'total_tokens' => $responseBody['usage']['total_tokens'] ?? 0,
                ]);

                $inputToken = $responseBody['usage']['prompt_tokens'] ?? 0;
                $outputToken = $responseBody['usage']['completion_tokens'] ?? 0;
                $session = Session::find($sessionId);
                if ($session) {
                    $session->input_token += $inputToken;
                    $session->output_token += $outputToken;
                    $session->save();
                }
                \Log::info('API Response:', $responseBody);

                if (isset($responseBody['choices'][0]['message']['content'])) {
                    $content = $responseBody['choices'][0]['message']['content'];
                    // Entferne mögliche JSON-Codeblock-Markierungen
                    $content = preg_replace('/```json\s*|\s*```/', '', $content);
                    $newIdeas = json_decode($content, true);

                    if (is_array($newIdeas)) {
                        foreach ($newIdeas as $idea) {
                            Idea::create([
                                'idea_title' => $idea['ideaTitle'] ?? '',
                                'idea_description' => $idea['ideaDescription'] ?? '',
                                'tag' => $idea['tag'] ?? '',
                                'contributor_id' => $idea['contributor_id'] ?? 0,
                                'session_id' => $sessionId,
                                'round' => $idea['round'] ?? 0
                            ]);
                        }
                        \Log::info('New ideas saved successfully');
                    } else {
                        \Log::error('Invalid format for new ideas', ['content' => $content]);
                    }
                } else {
                    \Log::error('Unexpected API response format', $responseBody);
                }
                return response()->json($responseBody);

            } catch (\GuzzleHttp\Exception\ClientException $e) {
                $response = $e->getResponse();
                $responseBodyAsString = $response->getBody()->getContents();
                \Log::error('ClientException: ' . $responseBodyAsString);
                return response()->json(['message' => 'Fehler: ' . $responseBodyAsString], 500);
            } catch (\Exception $e) {
                \Log::error('General Exception: ' . $e->getMessage());
                return response()->json(['message' => 'Fehler: ' . $e->getMessage()], 500);
            }
        }
    }

    public function get($sessionId, $votingPhase, $contributorId)
    {
        if ($votingPhase < 1 || $votingPhase > 4) {
            return response()->json([
                'success' => false,
                'message' => 'Ungültige Voting-Phase'
            ], 400);
        }
        Log::info("VotingPhase" . $votingPhase);
        // Alle Ideen mit Tags
        $ideas = Idea::where('session_id', $sessionId)
            ->whereNotNull('tag')
            ->where('tag', '!=', '')
            ->get();

        // Abgestimmte Ideen
        $votedIdeasCount = Vote::where('session_id', $sessionId)
            ->where('voting_phase', $votingPhase)
            ->where('contributor_id', $contributorId)
            ->count();

        // Nicht abgestimmte Ideen
        $unvotedIdeas = Idea::where('session_id', $sessionId)
            ->with('contributor')
            ->whereNotNull('tag')
            ->where('tag', '!=', '')
            ->whereDoesntHave('votes', function ($query) use ($votingPhase, $contributorId) {
                $query->where('voting_phase', $votingPhase)
                    ->where('contributor_id', $contributorId);
            });

        // Für Abstimmungsphasen > 1
        if ($votingPhase > 1) {
            $ideas = Idea::where('session_id', $sessionId)
                ->whereNotNull('tag')
                ->where('tag', '!=', '')
                ->whereHas('votes', function ($query) use ($votingPhase) {
                    $query->where('voting_phase', $votingPhase - 1);
                })
                ->with([
                    'votes' => function ($query) use ($votingPhase, $contributorId) {
                        $query->where('voting_phase', $votingPhase)
                            ->where('contributor_id', $contributorId);
                    }
                ])
                ->get();

            $votingMethod = $this->determineVotingMethod($votingPhase, $ideas->count(), $sessionId, $contributorId);

            $ideas = $this->sortIdeasByPreviousVotes($ideas, $votingPhase);

            $unvotedIdeas = $this->limitIdeasByVotingMethod($ideas, $votingMethod, $votingPhase, $contributorId);
            Log::info("limitedIdeas", [$unvotedIdeas->pluck('idea_title', 'id')->toJson(JSON_PRETTY_PRINT)]);
        } else {
            $votingMethod = $this->determineVotingMethod($votingPhase, $ideas->count(), $sessionId, $contributorId);
            $unvotedIdeas = $unvotedIdeas->get();
        }

        $formattedIdeas = $this->formatIdeas($unvotedIdeas);
        Log::info("Formatted ideas", [$formattedIdeas]);
        if ($unvotedIdeas->isEmpty()) {
            Log::info($unvotedIdeas);
            $this->checkAllVotesSubmitted($sessionId, $votingPhase);
            return response()->json([
                'success' => false,
                'message' => 'Keine weiteren Ideen zum Bewerten verfügbar'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'ideas' => $formattedIdeas,
            'ideasCount' => count($formattedIdeas),
            'votingMethod' => $votingMethod,
            'votedIdeasCount' => $votedIdeasCount
        ]);
    }

    private function checkAllVotesSubmitted($sessionId, $votingPhase)
    {
        Log::info("Überprüfe Stimmabgabe", ['session_id' => $sessionId, 'voting_phase' => $votingPhase]);

        $contributors = Contributor::where('session_id', $sessionId)->get();
        $ideasCount = Idea::where('session_id', $sessionId)
            ->whereNotNull('tag')
            ->where('tag', '!=', '')
            ->when($votingPhase > 1, function ($query) use ($votingPhase) {
                return $query->whereHas('votes', function ($subquery) use ($votingPhase) {
                    $subquery->where('voting_phase', $votingPhase);
                });
            })
            ->count();

        Log::info("Anzahl der Ideen und Teilnehmer", [
            'ideas_count' => $ideasCount,
            'contributors_count' => $contributors->count()
        ]);

        $allVotesSubmitted = $contributors->every(function ($contributor) use ($sessionId, $votingPhase, $ideasCount) {
            $votes = Vote::where('session_id', $sessionId)
                ->where('contributor_id', $contributor->id)
                ->where('voting_phase', $votingPhase)
                ->count();

            Log::debug("Stimmabgabe des Teilnehmers", [
                'contributor_id' => $contributor->id,
                'votes_count' => $votes,
                'required_votes' => $ideasCount
            ]);

            return $votes >= $ideasCount;
        });

        Log::info("Stimmabgabe abgeschlossen", ['all_votes_submitted' => $allVotesSubmitted]);

        if ($allVotesSubmitted) {
            Log::info("Alle Stimmen abgegeben", ['session_id' => $sessionId]);

            $voteType = Vote::where('session_id', $sessionId)
                ->where('voting_phase', $votingPhase)
                ->first()
                ->vote_type;

            Log:
            info($voteType . "voteType");
            if ($voteType === 'ranking') {
                event(new LastVote($sessionId, 0, true));
            } else {
                event(new LastVote($sessionId, $votingPhase + 1, false));
            }
        } else {
            Log::info("Noch nicht alle Stimmen abgegeben", ['session_id' => $sessionId]);
        }

        return $allVotesSubmitted;
    }
    private function sortIdeasByPreviousVotes($ideas, $votingPhase)
    {
        Log::info("Sorting ideas by previous votes for phase: {$votingPhase}");

        if ($votingPhase > 1) {
            return $ideas->map(function ($idea) use ($votingPhase) {
                $previousVotes = Vote::where('idea_id', $idea->id)
                    ->where('voting_phase', $votingPhase - 1)
                    ->get();

                $totalValue = $previousVotes->sum(function ($vote) {
                    return $vote->vote_value ?? ($vote->vote_boolean ? 1 : 0);
                });

                $averageVote = $previousVotes->count() > 0 ? $totalValue / $previousVotes->count() : 0;

                $idea->averageVote = $averageVote;

                /* Log::info("Idea sorting details", [
                     'idea_id' => $idea->id,
                     'previous_votes_count' => $previousVotes->count(),
                     'total_value' => $totalValue,
                     'average_vote' => $averageVote
                 ]);
                 */
                return $idea;
            })->sortByDesc('averageVote');

        }

        Log::info("No sorting applied (voting phase <= 1)");
        return $ideas;
    }

    private function determineVotingMethod($votingPhase, $ideasCount, $sessionId, $contributorId)
    {
        Log::info("votingPhase, ideasCount, sessionId, contributorId", [$votingPhase, $ideasCount, $sessionId, $contributorId]);
        if ($votingPhase == 1) {
            if ($ideasCount <= 5)
                return 'RankingVote';
            if ($ideasCount <= 15)
                return 'StarVote';
            if ($ideasCount <= 31)
                return 'SwipeVote';
            return 'LeftRightVote';
        } else {
            $previousVotes = Vote::where('session_id', $sessionId)
                ->where('voting_phase', $votingPhase - 1)
                ->where('contributor_id', $contributorId)
                ->count();
            Log::info("Previous votes for phase {$votingPhase} by contributor {$contributorId}: {$previousVotes}");

            if ($previousVotes <= 5)
                return 'End';
            if ($previousVotes <= 15)
                return 'RankingVote';
            if ($previousVotes <= 31)
                return 'StarVote';
            return 'SwipeVote';
        }
    }
    private function limitIdeasByVotingMethod($ideas, $votingMethod, $votingPhase, $contributorId)
    {
        switch ($votingMethod) {
            case 'LeftRightVote':
                $numberOfIdeas = min(intval($ideas->count() / 2), 32);
                $numberOfIdeas = max($numberOfIdeas - ($numberOfIdeas % 2), 2);
                $limitedIdeas = $ideas->slice(0, $numberOfIdeas);
                break;
            case 'SwipeVote':
                $numberOfIdeas = min(intval($ideas->count() / 2), 30);
                $limitedIdeas = $ideas->take($numberOfIdeas);
                break;
            case 'StarVote':
                $limitedIdeas = $ideas->take(15);
                break;
            case 'RankingVote':
                $limitedIdeas = $ideas->take(5);
                break;
            default:
                $limitedIdeas = $ideas;
        }

        return $limitedIdeas->filter(function ($idea) use ($votingPhase, $contributorId) {
            return !$idea->votes->where('voting_phase', $votingPhase)
                ->where('contributor_id', $contributorId)
                ->count();
        })->values();
    }

    private function getNextVotingMethod($votingMethods, $currentMethod)
    {
        $currentMethodIndex = array_search($currentMethod, $votingMethods);
        $nextMethodIndex = $currentMethodIndex + 1;
        return $nextMethodIndex < count($votingMethods) ? $votingMethods[$nextMethodIndex] : null;
    }

    private function formatIdeas($ideas): mixed
    {
        return $ideas->map(function ($idea) {
            return [
                'id' => $idea->id,
                'contributorIcon' => $idea->contributor->role->icon ?? 'default_icon',
                'ideaTitle' => $idea->idea_title,
                'ideaDescription' => $idea->idea_description,
                'tag' => $idea->tag,
                'averageVote' => $idea->averageVote ?? null,
            ];
        })->values()->all();
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
            \Log::info('OpenAI API Request:', ['request' => $requestData]);
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
            \Log::info('OpenAI API Response:', ['response' => $responseBody]);

            // Extrahiere die relevante Nachricht aus der API-Antwort
            $iceBreakerMsg = $responseBody['choices'][0]['message']['content'] ?? 'Keine Antwort erhalten';
            ApiLog::create([
                'session_id' => $sessionId,
                'contributor_id' => $contributorId,
                'request_data' => json_encode($requestData),
                'response_data' => json_encode($responseBody),
                'icebreaker_msg' => $iceBreakerMsg,
                'prompt_tokens' => $responseBody['usage']['prompt_tokens'] ?? 0,
                'completion_tokens' => $responseBody['usage']['completion_tokens'] ?? 0,
                'total_tokens' => $responseBody['usage']['total_tokens'] ?? 0,
            ]);
            // Update the database with the new token counts
            $responseData = json_decode($response->getBody(), true);
            $inputToken = $responseData['usage']['prompt_tokens'] ?? 0;
            $outputToken = $responseData['usage']['completion_tokens'] ?? 0;
            $session = Session::find($sessionId);
            if ($session) {
                $session->input_token += $inputToken;
                $session->output_token += $outputToken;
                $session->save();
            }
            return response()->json(['iceBreaker_msg' => $iceBreakerMsg]);

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            \Log::error('ClientException: ' . $responseBodyAsString);
            return response()->json(['iceBreaker_msg' => 'Fehler: ' . $responseBodyAsString], 500);
        } catch (\Exception $e) {
            \Log::error('General Exception: ' . $e->getMessage());
            return response()->json(['iceBreaker_msg' => 'Fehler: ' . $e->getMessage()], 500);
        }
    }


    public function store(Request $request)
    {
        // Validierung der eingehenden Anfrage
        $request->validate([
            'text_input' => 'nullable|string',
            'image_file' => 'nullable|file|mimes:png,jpeg,pdf|max:5000',
            'session_id' => 'required|exists:bf_sessions,id',
            'contributor_id' => 'required|exists:bf_contributors,id',
            'round' => 'required|integer'
        ]);
        $contributorId = $request->input('contributor_id');
        $sessionId = $request->input('session_id');
        // Verarbeite die Datei, falls vorhanden
        $imageFileUrl = null;
        $aiResponse = null;
        if ($request->hasFile('image_file')) {
            $imageFile = $request->file('image_file');
            $fileName = time() . '_' . $imageFile->getClientOriginalName();
            $filePath = $imageFile->storeAs('brainframe/images', $fileName, 'public');
            $imageFileUrl = 'storage/' . $filePath;

            $apiKey = env('OPENAI_API_KEY');
            $client = new Client();
            Log::info('submitted idea, validated', [
                'fileName' => $fileName,
                'filePath' => $filePath,
                'imageFileUrl' => $imageFileUrl
            ]);
            try {
                $response = $client->post('https://api.openai.com/v1/chat/completions', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'model' => "gpt-4o",
                        'messages' => [
                            [
                                "role" => "user",
                                "content" => [
                                    ["type" => "text", "text" => "What is in this image?"],
                                    [
                                        "type" => "image_url",
                                        "image_url" => [
                                            "url" => $imageFileUrl
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'max_tokens' => 300
                    ],
                ]);

                $responseBody = json_decode($response->getBody(), true);
                $aiResponse = $responseBody['choices'][0]['message']['content'] ?? null;
                ApiLog::create([
                    'session_id' => $sessionId,
                    'contributor_id' => $contributorId,
                    'request_data' => [
                        'image_url' => $imageFileUrl,
                        'prompt' => "What is in this image?"
                    ],
                    'response_data' => $responseBody,
                    'icebreaker_msg' => null, // nicht relevant für diese Funktion
                    'prompt_tokens' => $responseBody['usage']['prompt_tokens'] ?? 0,
                    'completion_tokens' => $responseBody['usage']['completion_tokens'] ?? 0,
                    'total_tokens' => $responseBody['usage']['total_tokens'] ?? 0,
                ]);
                $responseData = json_decode($response->getBody(), true);
                $inputToken = $responseData['usage']['prompt_tokens'] ?? 0;
                $outputToken = $responseData['usage']['completion_tokens'] ?? 0;
                $session = Session::find($sessionId);
                if ($session) {
                    $session->input_token += $inputToken;
                    $session->output_token += $outputToken;
                    $session->save();
                }
            } catch (\GuzzleHttp\Exception\ClientException $e) {
                $response = $e->getResponse();
                $responseBodyAsString = $response->getBody()->getContents();
                \Log::error('ClientException: ' . $responseBodyAsString);
                $responseData = json_decode($response->getBody(), true);
                $inputToken = $responseData['usage']['prompt_tokens'] ?? 0;
                $outputToken = $responseData['usage']['completion_tokens'] ?? 0;
                $session = Session::find($sessionId);
                if ($session) {
                    $session->input_token += $inputToken;
                    $session->output_token += $outputToken;
                    $session->save();
                }
                return response()->json(['message' => 'Fehler: ' . $responseBodyAsString], 500);
            } catch (\Exception $e) {
                \Log::error('General Exception: ' . $e->getMessage());
                return response()->json(['message' => 'Fehler: ' . $e->getMessage()], 500);
            }
        }

        $contributorId = $request->input('contributor_id');
        $sessionId = $request->input('session_id');

        // Erstelle einen neuen Datensatz
        $idea = Idea::create([
            'text_input' => $aiResponse ?? $request->input('text_input'),
            'image_file_url' => $imageFileUrl,
            'session_id' => $sessionId,
            'contributor_id' => $contributorId,
            'round' => $request->input('round')
        ]);

        // Rückgabe einer erfolgreichen Antwort
        return response()->json(['message' => 'Idea stored successfully', 'idea' => $idea], 201);
    }

}
