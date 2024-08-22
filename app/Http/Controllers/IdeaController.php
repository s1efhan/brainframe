<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Idea;
use App\Models\Contributor;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
class IdeaController extends Controller
{
    public function sendIdeasToGPT(Request $request)
    {
        $apiKey = env('OPENAI_API_KEY');
        $client = new Client();
        $sessionId = $request->input('session_id');
        $ideas = Idea::where('session_id', $sessionId)
            ->select('id', 'text_input', 'contributor_id')
            ->get();
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
  "contributor_id": [1, 34]",
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
                            'contributor_id' => json_encode($idea['contributor_id'] ?? []),
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


    public function get($sessionId, $votingPhaseNumber)
    {
        Log::info('Received request to get ideas', ['sessionId' => $sessionId, 'votingPhaseNumber' => $votingPhaseNumber]);

        // Abfrage anpassen, um nur Ideen mit vorhandenem Tag abzurufen
        $ideas = Idea::where('session_id', $sessionId)
            ->whereNotNull('tag') // Sicherstellen, dass das Tag-Feld nicht NULL ist
            ->where('tag', '!=', '') // Sicherstellen, dass das Tag-Feld nicht leer ist
            ->with('contributor') // Laden Sie den zugehörigen Contributor
            ->get()
            ->map(function ($idea) {
                $contributorIcon = $idea->contributor->role->icon ?? 'default_icon';
                $ideaTitle = $idea->idea_title;
                $ideaDescription = $idea->idea_description;
                $ideaTag = $idea->tag;

                return [
                    'id' => $idea->id,
                    'contributorIcon' => $contributorIcon,
                    'ideaTitle' => $ideaTitle,
                    'ideaDescription' => $ideaDescription, // Tippfehler in der ursprünglichen Rückgabe behoben
                    'tag' => $ideaTag
                ];
            });

        $ideasCount = $ideas->count();

        return response()->json([
            'success' => true,
            'ideas' => $ideas,
            'ideasCount' => $ideasCount
        ]);
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
