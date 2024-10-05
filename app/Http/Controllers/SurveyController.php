<?php
namespace App\Http\Controllers;

use App\Models\SurveyResponse;
use Illuminate\Http\Request;

use Log;

class SurveyController extends Controller
{
    public function get($sessionId, $userId)
    {
        $response = SurveyResponse::where('session_id', $sessionId)
            ->where('user_id', $userId)
            ->first();
            Log::info("Fetching survey data for session: $sessionId, user: $userId");
            Log::info("Response: " . json_encode($response));
        if ($response) {
            // Alle Attribute des Models zurückgeben, auch wenn sie null sind
            return response()->json($response->toArray());
        }
        return response()->json([]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'session_id' => 'required|int',
            'user_id' => 'required|int',
            'question_key' => 'required|string',
            'answer_value' => 'required',
        ]);

        $response = SurveyResponse::firstOrNew([
            'session_id' => $validatedData['session_id'],
            'user_id' => $validatedData['user_id'],
        ]);

        $response->{$validatedData['question_key']} = $validatedData['answer_value'];
        $response->save();

        return response()->json(['message' => 'Antwort gespeichert.', 'response' => $response]);
    }
}
