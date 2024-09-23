<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\Method;
use App\Models\Session;
use App\Events\SwitchPhase;
use App\Models\Contributor;
use App\Events\StartCollecting;
use App\Events\StopCollecting;
use Illuminate\Support\Facades\Cache;
use App\Events\UpdateCountdown;
class MethodController extends Controller
{
    public function get()
    {
        $methods = Method::all();
        return response()->json($methods);
    }

    public function getDetails($methodId)
    {

        if (!$methodId) {
            Log::warning('No method ID provided');
            return response()->json(['message' => 'Method ID is required'], 400);
        }

        // Finde die Methode nach ID
        $method = Method::find($methodId);

        if (!$method) {
            Log::error('Method not found', ['methodId' => $methodId]);
            return response()->json(['message' => 'Method not found'], 404);
        }

        return response()->json([
            'id' => $method->id,
            'name' => $method->name,
            'description' => $method->description,
        ], 200);
    }

    public function startCollecting(Request $request)
    {
        $sessionId = $request->input('session_id');
        $session = Session::findOrFail($sessionId);
        $phase = $session->active_phase;
        $round = $request->input('current_round') || 1;

        $secondsLeft = $request->input('collecting_timer');

        event(new StartCollecting($sessionId, $round));
        $this->startCountdown($sessionId, $phase, $round, $secondsLeft);

        return response()->json(['message' => 'Collecting successfully started']);
    }

    public function stopCollecting(Request $request)
    {
        $sessionId = $request->input('session_id');
        $session = Session::findOrFail($sessionId);
        $round = $request->input('current_round');

        $session->update([
            'active_phase' => 'collectingPhase',
            'active_round' => $round
        ]);

        if ($session->method_id === 4 && $round > 1 && $round < 7) {
            Contributor::where('session_id', $session->id)
                ->update(['role_id' => \DB::raw('(role_id % 6) + 1')]);
        }
        event(new StopCollecting($sessionId, $round));
        $this->stopCountdown($sessionId);

        return response()->json(['message' => 'Collecting successfully stopped']);
    }
    private function startCountdown($sessionId, $phase, $round, $secondsLeft)
    {
        $timerKey = "timer_{$sessionId}";
        $startTime = now()->timestamp;
        $endTime = $startTime + $secondsLeft;
        
        $timerData = [
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration' => $secondsLeft
        ];
        
        Cache::put($timerKey, $timerData, $secondsLeft);
        
        \Log::info("Timer started", [
            'key' => $timerKey,
            'data' => $timerData
        ]);
    }
    
    public function getCountdown($sessionId)
    {
        $session = Session::findOrFail($sessionId);
        $phase = $session->active_phase;
        $round = $session->active_round;
        $timerKey = "timer_{$sessionId}";
        
        $timerData = Cache::get($timerKey);
        
        \Log::info("getCountdown called", [
            'key' => $timerKey,
            'cached_data' => $timerData
        ]);
        
        if ($timerData) {
            $currentTime = now()->timestamp;
            $secondsLeft = max(0, $timerData['end_time'] - $currentTime);
            $isCollecting = $secondsLeft > 0;
            
            $response = [
                'seconds_left' => $secondsLeft,
                'current_round' => $round,
                'is_collecting' => $isCollecting
            ];
            
            \Log::info("Timer data calculated", $response);
            
            return response()->json($response);
        }
        
        $response = [
            'seconds_left' => 0,
            'current_round' => $round,
            'is_collecting' => false
        ];
        
        \Log::info("No timer data found", $response);
        
        return response()->json($response);
    }

    private function stopCountdown($sessionId)
    {
        $session = Session::findOrFail($sessionId);
        $timerKey = "timer_{$sessionId}";
        Cache::forget($timerKey);
    }
    public function switchPhase(Request $request)
    {
        $sessionId = $request->input('session_id');
        $newPhase = $request->input('switched_phase');

        $session = Session::find($sessionId);

        if (!$session) {
            return response()->json(['error' => 'Session not found'], 404);
        }

        if ($newPhase === "previousPhase") {
            if ($session->previous_phase != "lobby" && $session->previous_phase != null) {
                $newPhase = $session->previous_phase;
            } else {
                $newPhase = 'collectingPhase';
            }
        }

        $session->previous_phase = $session->active_phase;
        $session->active_phase = $newPhase;
        $session->save();

        event(new SwitchPhase($sessionId, $newPhase));

        return response()->json([
            'message' => 'Phase switched successfully',
            'previous_phase' => $session->previous_phase,
            'active_phase' => $session->active_phase
        ]);
    }
}
