<?php
namespace App\Http\Controllers;

use App\Models\SurveyResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
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
    
    public function verifyEmail(Request $request)
    {
        Log::info('Verifying email. Request data:', $request->all());
    
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'survey_verification_key' => 'required|string|size:6'
            ]);
    
            $user = User::findOrFail($request->input('user_id'));
            $cacheKey = 'survey_verification_' . $user->id;
            $cachedKey = Cache::get($cacheKey);
    
            Log::info("User ID: {$user->id}, Cached Key: {$cachedKey}, Submitted Key: {$request->input('survey_verification_key')}");
    
            if ($cachedKey !== $request->input('survey_verification_key')) {
                Log::warning('Invalid verification key or expired code.');
                return response()->json(['message' => 'Ungültiger Verifizierungsschlüssel oder Code abgelaufen.'], 400);
            }
    
            $user->survey_activated = true;
            $user->save();
            Cache::forget($cacheKey);
    
            Log::info('Email verified and survey activated for user: ' . $user->id);
            return response()->json(['message' => 'E-Mail verifiziert und Umfrage aktiviert.'], 200);
        } catch (\Exception $e) {
            Log::error('Error in email verification: ' . $e->getMessage());
            return response()->json(['message' => 'Ein Fehler ist bei der Verifizierung aufgetreten.'], 500);
        }
    }
    
    public function storeEmail(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'survey_email' => 'required|email'
        ]);
    
        $user = User::findOrFail($request->input('user_id'));
        $user->survey_email = $request->input('survey_email');
        $user->save();
    
        $verificationCode = sprintf('%06d', mt_rand(0, 999999));
        $cacheKey = 'survey_verification_' . $user->id;
        Cache::put($cacheKey, $verificationCode, now()->addMinutes(10)); // Verlängern Sie die Cache-Dauer auf 10 Minuten
    
        Log::info("Storing verification code for user {$user->id}: {$verificationCode}");
    
        $email = $user->survey_email;
        $emailMessage = "Dein Verifizierungscode lautet: " . $verificationCode;
    
        Mail::send([], [], function ($message) use ($email, $emailMessage) {
            $message->to($email)
                ->subject("BrainFrame - E-Mail-Verifizierungscode")
                ->html($emailMessage);
        });
    
        return response()->json([
            'message' => 'Umfrage-E-Mail gespeichert. Bitte überprüfen Sie Ihre E-Mail für den Verifizierungscode.',
        ]);
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