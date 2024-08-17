<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Session;
use App\Models\User;
use App\Models\Method;
use App\Models\Contributor;
use App\Models\Role;
use Illuminate\Support\Facades\Mail;
use App\Events\StartCollecting;
use App\Events\StopCollecting;
use Log;

class SessionController extends Controller
{
    public function get($sessionId)
    {
        if (!$sessionId) {
            return response()->json(['message' => 'Session ID is required'], 400);
        }

        $session = Session::with(['host', 'method'])->find($sessionId);

        $contributor = Contributor::where('user_id', $session->host_id)->where('session_id', $session->id)->first();

        if (!$session) {
            return response()->json(['message' => 'Session not found'], 404);
        }

        return response()->json([
            'id' => $session->id,
            'session_host' => $contributor->id,
            'method_id' => $session->method_id,
            'target' => $session->target,
            'method_name' => $session->method->name
        ], 200);
    }

    public function getUserSessions($userId) {
        $contributors = Contributor::where('user_id', $userId)->get();
    
        $sessions = $contributors->map(function ($contributor) {
            $session = Session::find($contributor->session_id);
            $method = Method::find($session->method_id);
            $role = Role::find($contributor->role_id);
    
            return [
                'session_id' => $contributor->session_id,
                'target' => $session->target,
                'role' => $role->name,
                'updated_at' => $session->updated_at,
                'method_name' => $method->name,
            ];
        });
    
        Log::info('User Sessions:', $sessions->toArray());
    
        return response()->json($sessions, 200);
    }
    
    
    public function update(Request $request)
    {
        $request->validate([
            'session_id' => 'required',
            'host_id' => 'required',
            'method_id' => 'required',
            'session_target' => 'nullable|string|present'
        ]);
    
        $sessionId = $request->input('session_id');
        $hostId = $request->input('host_id');
    
        $session = Session::firstOrNew(['id' => $sessionId]);
        $isNewSession = !$session->exists;
    
        $session->target = $request->input('session_target') ?: 'Kein Ziel festgelegt';
        $session->host_id = $hostId;
        $session->method_id = $request->input('method_id');
        $session->save();
    
        if ($isNewSession) {
            $defaultRoleId = 0; // Angenommen, 1 ist die ID für die "Unassigned" Rolle
            Contributor::create([
                'session_id' => $session->id,
                'user_id' => $hostId,
                'role_id' => $defaultRoleId
            ]);
        }
    
        return response()->json([
            'message' => $isNewSession ? 'Session created successfully.' : 'Session updated successfully.'
        ], 200);
    }
    public function startCollecting(Request $request)
    {
        $sessionId = $request->input('session_id');
        $round = $request->input('current_round');
    
        // Aktualisiere den Wert der Spalte `active_round` für die Session
        $session = Session::find($sessionId);
        if ($session) {
            $session->active_round = $round;
            $session->save();
        }
    
        // Event auslösen
        event(new StartCollecting($sessionId, $round));
    
        return response()->json(['message' => 'Collecting successfully started']);
    }
    

    public function stopCollecting(Request $request)
    {
        $sessionId = $request->input('session_id');
        $round = $request->input('current_round');
    
        // Aktualisiere den Wert der Spalte `active_round` für die Session
        $session = Session::find($sessionId);
        if ($session) {
            $session->active_round = $round;
            $session->save();
        }
    
        // Event auslösen
        event(new StopCollecting($sessionId, $round));
    
        return response()->json(['message' => 'Collecting successfully stopped']);
    }
    
    public function invite(Request $request)
    {
        $request->validate([
            'session_id' => 'required',
            'host_id' => 'required',
            'contributor_email_addresses' => 'required|array',
            'contributor_email_addresses.*' => 'email',
        ]);

        $sessionId = $request->input('session_id');
        $hostId = $request->input('host_id');
        $contributorEmailAddresses = $request->input('contributor_email_addresses');

        $host = User::findOrFail($hostId);
        $userName = explode('.', $host->email)[0];

        $emailMessage = "Hallo, <br> Du wurdest von {$userName} zu einer Ideen-Session eingeladen. <br> <br>
        Du kannst über folgenden Link beitreten: <a href='https://stefan-theissen.de/brainframe/{$sessionId}'>Brainframe</a>. <br> <br>
        Alternativ kannst du auch unter <a href='https://stefan-theissen.de/brainframe'>Brainframe</a> vorbeischauen und mit dem Code: {$sessionId} beitreten.";

        foreach ($contributorEmailAddresses as $email) {
            if ($email) {
                Mail::html($emailMessage, function ($message) use ($email, $userName) {
                    $message->to($email)
                        ->subject("{$userName} - Einladung zur Ideen-Session");
                });
            }
        }

        return response()->json(['message' => 'Invitations sent successfully.'], 200);
    }
}