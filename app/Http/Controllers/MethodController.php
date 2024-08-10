<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\Method;
use App\Models\Contributor;
use App\Events\SwitchPhase;
class MethodController extends Controller
{
    public function get()
    {
        $methods = Method::all();
        return response()->json($methods);
    }

    public function getDetails($methodId)
    {
        Log::info('Received request to get method details', ['methodId' => $methodId]);
    
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
    
        Log::info('Method found', [
            'id' => $method->id,
            'name' => $method->name,
            'description' => $method->description,
        ]);
    
        return response()->json([
            'id' => $method->id,
            'name' => $method->name,
            'description' => $method->description,
        ], 200);
    }
    public function switchPhase(Request $request)
    {
        $sessionId = $request->input('session_id');
        $phase = $request->input('switched_phase');
        
        event(new SwitchPhase($sessionId, $phase));
        
        return response()->json(['message' => 'Phase switched successfully']);
    }
}
