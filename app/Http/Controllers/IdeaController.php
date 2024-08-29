<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Idea;
use App\Models\Contributor;
use App\Models\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
class IdeaController extends Controller
{


    public function getPassedIdeas($sessionId, $personalContributorId, $round)
    {
        // Log::info('sessionId', ['sessionId' => $sessionId]);
        // Log::info('personalContributorId', ['personalContributorId' => $personalContributorId]);
        Log::info('round', ['round' => $round]);

        // Alle Contributors der Session abrufen und nach ID sortieren
        $contributors = Contributor::where('session_id', $sessionId)
            ->orderBy('id')
            ->get();
        //  Log::info('contributors', ['contributors' => $contributors]);

        $contributorCount = $contributors->count();
        // Log::info('contributorCount', ['contributorCount' => $contributorCount]);

        // Eigene Position des $personalContributorId herausfinden
        $ownPosition = $contributors->search(function ($contributor) use ($personalContributorId) {
            return $contributor->id == $personalContributorId;
        });
        // Log::info('ownPosition', ['ownPosition' => $ownPosition]);

        if ($ownPosition === false) {
            return response()->json(['error' => 'Persönlicher Contributor nicht gefunden'], 404);
        }

        $passedIdeas = collect();

        for ($i = 1; $i < $round; $i++) {
            // Position des Nachbarn berechnen
            $neighbourPosition = ($ownPosition - $i + $contributorCount) % $contributorCount;
            $neighbourContributor = $contributors[$neighbourPosition];

            // Log::info('i', ['i' => $i]);
            Log::info('neighbourContributor', ['neighbourContributor' => $neighbourContributor->id]);
            //  Log::info('neighbourPosition', ['neighbourPosition' => $neighbourPosition]);

            // Ideas des Nachbarn aus der entsprechenden Runde abrufen
            $ideas = Idea::where('session_id', $sessionId)
                ->where('round', $round - $i)
                ->whereNotNull('tag')
                ->where('contributor_id', $neighbourContributor->id)
                ->get();
            Log::info('round', ['round' => $round - $i]);
            Log::info('ideas', ['ideas' => $ideas]);
            // Füge das contributor_icon für jede Idee hinzu
            $ideasWithIcon = $ideas->map(function ($idea) {
                $idea->contributorIcon = $idea->contributor->role->icon ?? 'default_icon';
                return $idea;
            });

            Log::info('round', ['round' => $round - $i]);
            Log::info('ideas', ['ideas' => $ideasWithIcon]);

            $passedIdeas = $passedIdeas->concat($ideasWithIcon);
        }

        return response()->json($passedIdeas);
    }
    public function sendIdeasToGPT(Request $request)
    {
        $methodName = $request->input("method_name");
        $round = $request->input("round");
        $sessionId = $request->input('session_id');

        if ($methodName == "6-3-5") {
            $ideas = Idea::where('session_id', $sessionId)
                ->where('round', $round)
                ->select('id', 'text_input', 'contributor_id')
                ->get();
            Log::info('6-3-5 Ideas', ['ideas' => $ideas->toArray()]);
        } else {
            $ideas = Idea::where('session_id', $sessionId)
                ->select('id', 'text_input', 'contributor_id')
                ->get();
        }
        $apiKey = env('OPENAI_API_KEY');
        $client = new Client();
        //nur id, text_input, contributor_id ins Objekt packen
        \Log::info($ideas);
        $ideasFormatted = $ideas->map(function ($idea) {
            return [
                'contributor_id' => $idea->contributor_id,
                'ideaTitle' => $idea->text_input, // angenommen, text_input ist der Titel
                'ideaDescription' => "<ul><li>" . implode("</li><li>", explode("\n", $idea->text_input)) . "</li></ul>",
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
                    
                       1) Erstelle einen umfassenden ideaTitle, der die Konzepte und Grundidee widerspiegelt.
                       2) Fülle ideaDescription mit mindestens 2 bis zu 3 Stichpunkten, die verschiedene Aspekte der Ideen abdecken.
                       3) Wähle einen thematischen tag, der die Kernessenz der Ideen erfasst und die Ideen zu Gruppen zuordnen lässt.
                       4) Formatiere die Ausgabe als JSON-Array mit den Feldern: contributor_id, ideaTitle, ideaDescription, tag wie im Beispiel.
                    
                       Beispiel:
                    {
                      "contributor_id": 1",
                      "round": 2,
                      "ideaTitle": "Lifestyle-Marken spielen gegeneinander",
                      "ideaDescription": "<ul><li>Marken wie Nestle, Coca Cola oder McDonalds treten gegeneinander an</li><li>ähnlich zu Hunger Games</li><li>Kritik an Konsumgesellschaft</li></ul>",
                      "tag": "Gesellschaftskritik"
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
                            'session_id' => $sessionId
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

    public function get($sessionId, $votingPhase, $contributorId)
    {
        if ($votingPhase >= 1 && $votingPhase <= 4) {

            if ($votingPhase <= 1) {
                $ideas = Idea::where('session_id', $sessionId)
                    ->with('contributor')
                    ->with([
                        'votes' => function ($query) use ($votingPhase) {
                            $query->where('voting_phase', $votingPhase - 1);
                        }
                    ])
                    ->whereNotNull('tag')
                    ->where('tag', '!=', '')
                    ->get();
            } else {
                $ideas = Idea::where('session_id', $sessionId)
                    ->with('contributor')
                    ->with([
                        'votes' => function ($query) use ($votingPhase) {
                            $query->where('voting_phase', $votingPhase - 1);
                        }
                    ])
                    ->whereNotNull('tag')
                    ->where('tag', '!=', '')
                    ->whereHas('votes', function ($query) use ($votingPhase) {
                        $query->where('voting_phase', $votingPhase - 1);
                    })
                    ->get();

                $ideas = $ideas->map(function ($idea) {
                    $totalVotes = $idea->votes->map(function ($vote) {
                        return $vote->vote_value !== null ? $vote->vote_value : $vote->vote_boolean;
                    });
                    $idea->averageVote = $totalVotes->avg() ?? 0;
                    return $idea;
                })->sortByDesc('averageVote');
                $ideasCount = $ideas->count();
                Log::info("else ideasCount: " . $ideasCount);
            }

            $ideasCount = $ideas->count();
            Log::info("Initial ideasCount: " . $ideasCount);
            Log::info("votingPhase: " . $votingPhase);
            if ($votingPhase > 1) {
                $votingMethod = $ideasCount > 32 ? 'LeftRightVote' : ($ideasCount > 15 ? 'StarVote' : 'RankingVote');
            } else {
                $votingMethod = $ideasCount > 15 ? 'SwipeVote' : ($ideasCount > 5 ? 'StarVote' : 'RankingVote');
            }
            switch ($votingMethod) {
                case 'LeftRightVote':
                    $numberOfIdeas = min(intval($ideasCount / 2), 30);
                    if ($numberOfIdeas % 2 !== 0) {
                        $numberOfIdeas--; // Reduce to the next even number
                    }
                    $numberOfIdeas = max($numberOfIdeas, 2);
                    $ideas = $ideas->slice(0, $numberOfIdeas);
                    break;
                case 'StarVote':
                    $ideas = $ideas->take(15);
                    break;
                case 'RankingVote':
                    $ideas = $ideas->take(5);
                    break;
            }
            $ideasCount = $ideas->count();
            Log::info("New ideasCount: " . $ideasCount);
            Log::info("New votingMethod: " . $votingMethod);
            $ideas = $ideas->map(function ($idea) use ($votingPhase) {
                return [
                    'id' => $idea->id,
                    'contributorIcon' => $idea->contributor->role->icon ?? 'default_icon',
                    'ideaTitle' => $idea->idea_title,
                    'ideaDescription' => $idea->idea_description,
                    'tag' => $idea->tag,
                    'averageVote' => $idea->averageVote ?? null,
                ];
            });
            $ideas = $ideas->values()->all();
            return response()->json([
                'success' => true,
                'ideas' => $ideas,
                'ideasCount' => $ideasCount,
                'votingMethod' => $votingMethod
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Ungültige Voting-Phase'
            ], 400);
        }
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
            // Anfrage an die OpenAI API
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

            // Extrahiere die relevante Nachricht aus der API-Antwort
            $iceBreakerMsg = $responseBody['choices'][0]['message']['content'] ?? 'Keine Antwort erhalten';

            \Log::info('API Response:', ['iceBreaker_msg' => $iceBreakerMsg]);
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
