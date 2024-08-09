<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Session;
use App\Models\User;
use App\Models\Method;
use Illuminate\Support\Facades\Mail;
class SessionController extends Controller
{ 
    public function get($sessionId)
{
    if (!$sessionId) {
        return response()->json(['message' => 'Session ID is required'], 400);
    }

    $session = Session::with(['host', 'method'])->find($sessionId);

    if (!$session) {
        return response()->json(['message' => 'Session not found'], 404);
    }

    return response()->json([
        'id' => $session->id,
        'host_id' => $session->host_id,
        'method_id' => $session->method_id,
        'target' => $session->target,
        'method_name' => $session->method->name
    ], 200);
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
    $session = Session::firstOrNew(['id' => $sessionId]);
    $session->target = $request->input('session_target') ?: 'Kein Ziel festgelegt';
    $session->host_id = $request->input('host_id');
    $session->method_id = $request->input('method_id');
    $session->save();

    return response()->json([
        'message' => $session->wasRecentlyCreated ? 'Session created successfully.' : 'Session updated successfully.'
    ], 200);
}
    public function invite(Request $request)
    {
        $request->validate([
            'session_id' => 'required',
            'host_id' => 'required',
            'method_id' => 'required',
            'contributor_email_addresses' => 'required|array',
            'contributor_email_addresses.*' => 'email',
        ]);
    
        $sessionId = $request->input('session_id');
        $hostId = $request->input('host_id');
        $methodId = $request->input('method_id');
        $contributorEmailAddresses = $request->input('contributor_email_addresses');
    
        $host = User::findOrFail($hostId);
        $method = Method::findOrFail($methodId);
        $userName = explode('.', $host->email)[0];
        $methodName = $method->name;
    
        $emailMessage = "Hallo, <br> Du wurdest von {$userName} zu einer {$methodName}-Session eingeladen. <br> <br>
        Du kannst über folgenden Link beitreten: <a href='https://stefan-theissen.de/brainframe/{$sessionId}'>Brainframe</a>. <br> <br>
        Alternativ kannst du auch unter <a href='https://stefan-theissen.de/brainframe'>Brainframe</a> vorbeischauen und mit dem Code: {$sessionId} beitreten.";
    
        foreach ($contributorEmailAddresses as $email) {
            if($email){
            Mail::html($emailMessage, function ($message) use ($email, $userName, $methodName) {
                $message->to($email)
                    ->subject("{$userName} - Einladung zur {$methodName}-Session");
            });}
        }
    
        return response()->json(['message' => 'Invitations sent successfully.'], 200);
    }
}