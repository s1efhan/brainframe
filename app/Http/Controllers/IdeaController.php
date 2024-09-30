<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Idea;
use App\Models\Contributor;
use App\Events\UserSentIdea;
use App\Models\Session;
use App\Models\Vote;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use App\Events\SwitchPhase;
use App\Models\ApiLog;
class IdeaController extends Controller
{
    public function get($sessionId){
        $session = Session::findOrFail($sessionId);
        $ideas = Idea::where('session_id', $sessionId)
        ->get();
        Log::info('contributors: '.json_encode($ideas));
        return response()->json([
            'ideas' => $ideas
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

        $contributorId = $request->input('contributor_id');
        $sessionId = $request->input('session_id');
        $imageFileUrl = null;
        $aiResponse = null;

        // Hole die Session und das Target
        $session = Session::find($sessionId);
        $target = $session->target;

        if ($request->hasFile('image_file')) {
            $imageFile = $request->file('image_file');

            // Speichern der Bild-URL
            $fileName = time() . '_' . $imageFile->getClientOriginalName();
            $filePath = $imageFile->storeAs('brainframe/images', $fileName, 'public');
            $imageFileUrl = 'storage/' . $filePath;

            // Bild komprimieren und skalieren
            $sourceImage = imagecreatefromstring(file_get_contents($imageFile->getRealPath()));
            $width = imagesx($sourceImage);
            $height = imagesy($sourceImage);

            // Neue Größe berechnen (max. 200px Breite)
            $newWidth = min($width, 100);
            $newHeight = ($height / $width) * $newWidth;

            $newImage = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            // In JPEG umwandeln und komprimieren
            ob_start();
            imagejpeg($newImage, null, 20);
            $imageData = ob_get_contents();
            ob_end_clean();

            // Ressourcen freigeben
            imagedestroy($sourceImage);
            imagedestroy($newImage);

            // Überprüfen der komprimierten Bildgröße
            $compressedSize = strlen($imageData);
            \Log::info('Compressed image size: ' . $compressedSize . ' bytes');

            // Base64-Kodierung des komprimierten Bildes
            $imageData = base64_encode($imageData);

            $apiKey = env('OPENAI_API_KEY');
            $client = new Client();

            // Überprüfen der gesendeten Daten
            Log::info('Sending request to OpenAI', [
                'imageDataLength' => strlen($imageData),
                'prompt' => "Bild zu: " . $target . ". Idee in 3 Sätzen?"
            ]);

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
                                "role" => "user",
                                "content" => [
                                    ["type" => "text", "text" => "Was siehst du. Was könnte das Bild für eine Idee darstellen? Beschreibe das Bild für einen blinden, falls nötig. Fasse in 3 Sätzen die idee zusammen"],
                                    [
                                        "type" => "image_url",
                                        "image_url" => [
                                            "url" => "data:image/jpeg;base64," . $imageData
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'max_tokens' => 50,
                        'temperature' => 0.3
                    ],
                ]);

                $responseBody = json_decode($response->getBody(), true);
                $aiResponse = $responseBody['choices'][0]['message']['content'] ?? null;

                ApiLog::create([
                    'session_id' => $sessionId,
                    'contributor_id' => $contributorId,
                    'request_data' => [
                        'image_url' => $imageFileUrl,
                        'prompt' => "Bild zu: " . $target . ". Idee in 3 Sätzen?"
                    ],
                    'response_data' => $responseBody,
                    'prompt_tokens' => $responseBody['usage']['prompt_tokens'] ?? 0,
                    'completion_tokens' => $responseBody['usage']['completion_tokens'] ?? 0,
                ]);

            } catch (\GuzzleHttp\Exception\ClientException $e) {
                $response = $e->getResponse();
                $responseBodyAsString = $response->getBody()->getContents();
                \Log::error('ClientException: ' . $responseBodyAsString);
                return response()->json(['message' => 'Fehler: ' . $responseBodyAsString], 500);
            } catch (\Exception $e) {
                \Log::error('Exception: ' . $e->getMessage());
                return response()->json(['message' => 'Fehler: ' . $e->getMessage()], 500);
            }
        }
        // Erstelle einen neuen Datensatz
        $idea = Idea::create([
            'text_input' => $aiResponse ?? $request->input('text_input'),
            'image_file_url' => $imageFileUrl,
            'session_id' => $sessionId,
            'contributor_id' => $contributorId,
            'round' => $request->input('round')
        ]);
        event(new UserSentIdea($idea, $sessionId));
        // Rückgabe einer erfolgreichen Antwort
        return response()->json(['message' => 'Idea stored successfully', 'idea' => $idea], 201);
    }
    /*
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


   
            */
}
