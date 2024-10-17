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
    public function get($sessionId)
    {
        $session = Session::findOrFail($sessionId);
        $ideas = Idea::where('session_id', $sessionId)
            ->get();
        return response()->json([
            'ideas' => $ideas
        ]);
    }
    public function store(Request $request)
    {
        $request->validate([
            'text_input' => 'nullable|string',
            'image_file' => 'nullable|image|max:5000',
            'session_id' => 'required|exists:bf_sessions,id',
            'contributor_id' => 'required|exists:bf_contributors,id',
            'round' => 'required|integer'
        ]);

        $contributorId = $request->input('contributor_id');
        $sessionId = $request->input('session_id');
        $imageFileUrl = null;
        $aiResponse = null;

        $session = Session::find($sessionId);
        $target = $session->target;

        // Der folgende Codeabschnitt wurde mit Unterstützung von Claude 3.5 Sonnet erstellt
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

            // Base64-Kodierung des komprimierten Bildes
            $imageData = base64_encode($imageData);

            $apiKey = env('OPENAI_API_KEY');
            $client = new Client();

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
                                    ["type" => "text", "text" => "Was siehst du. Was könnte das Bild für eine Idee darstellen? Beschreibe das Bild für einen blinden, falls nötig. Fasse in 3 Sätzen die idee zusammen. Es hat vorraussichtlich mit: " . $session->target . " zu tun."],
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

        $idea = Idea::create([
            'text_input' => $aiResponse ?? $request->input('text_input'),
            'image_file_url' => $imageFileUrl,
            'session_id' => $sessionId,
            'contributor_id' => $contributorId,
            'round' => $request->input('round')
        ]);
        event(new UserSentIdea($idea, $sessionId));
        return response()->json(['message' => 'Idea stored successfully', 'idea' => $idea], 201);
    }
}
