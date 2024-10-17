<?php
namespace App\Http\Controllers;
use Carbon\Carbon;
use App\Models\SurveyResponse;
use App\Models\User;
use App\Models\Session;
use App\Models\Idea;
use App\Models\Vote;
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
        if ($response) {
            return response()->json($response->toArray());
        }
        return response()->json([]);
    }

    public function verifyEmail(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'survey_verification_key' => 'required|string|size:6'
            ]);

            $user = User::findOrFail($request->input('user_id'));
            $cacheKey = 'survey_verification_' . $user->id;
            $cachedKey = Cache::get($cacheKey);

            if ($cachedKey !== $request->input('survey_verification_key')) {
                Log::warning('Invalid verification key or expired code.');
                return response()->json(['message' => 'Ungültiger Verifizierungsschlüssel oder Code abgelaufen.'], 400);
            }

            $user->survey_activated = true;
            $user->save();
            Cache::forget($cacheKey);
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
        Cache::put($cacheKey, $verificationCode, now()->addMinutes(10));

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

    public function getTopIdeas($sessionId)
    {
        $session = Session::findOrFail($sessionId);
        $maxRound = Vote::where('session_id', $sessionId)->max('round');
        $topIdeas = Idea::where('session_id', $sessionId)
            ->whereNotNull('tag')
            ->with([
                'votes' => function ($query) use ($maxRound) {
                    $query->where('round', $maxRound);
                }
            ])
            ->get()
            ->map(function ($idea) {
                $avgRating = $idea->votes->avg('value') ?? 0;
                $idea->avg_rating = $avgRating;
                return $idea;
            })
            ->sortByDesc('avg_rating')
            ->take(3)
            ->values();


        $firstIdeaTime = Idea::where('session_id', $sessionId)
            ->min('created_at');
        $lastVoteTime = Vote::where('session_id', $sessionId)
            ->max('created_at');

        $firstIdeaTime = $firstIdeaTime ? Carbon::parse($firstIdeaTime) : null;
        $lastVoteTime = $lastVoteTime ? Carbon::parse($lastVoteTime) : null;
      
        $duration = null;
        if ($firstIdeaTime && $lastVoteTime) {
            $duration = $firstIdeaTime->diffInMinutes($lastVoteTime);
            Log::debug("Calculated Duration: " . $duration);
        } else {
            Log::debug("Duration calculation skipped - missing time data");
        }

        $ideasCount = Idea::where('session_id', $sessionId)
            ->whereNull('tag')
            ->count();

        return response()->json([
            'top_ideas' => $topIdeas,
            'session' => [
                'target' => $session->target,
                'duration' => $duration,
                'ideas_count' => $ideasCount
            ]
        ]);
    }
}
