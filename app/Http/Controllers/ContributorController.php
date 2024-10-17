<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contributor;
use App\Models\Session;
use App\Events\UserPickedRole;
use App\Events\UserJoinedSession;
use App\Events\UserLeftSession;
use Log;
class ContributorController extends Controller
{
    public function create(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|exists:bf_sessions,id',
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:bf_roles,id',
        ]);

        $contributor = Contributor::updateOrCreate(
            ['session_id' => $validated['session_id'], 'user_id' => $validated['user_id']],
            ['role_id' => $validated['role_id'], 'last_ping' => now(), 'is_active' => true]
        );

        $contributor->load('role');

        event(new UserPickedRole($validated['session_id'], $contributor));

        return response()->json([
            'success' => true,
            'message' => 'Contributor created or updated successfully.',
            'contributor' => $contributor
        ]);
    }

    public function get($sessionId, $userId)
    {
        $session = Session::findOrFail($sessionId);
        $contributors = Contributor::where('session_id', $sessionId)
            ->with(['user', 'role', 'ideas', 'votes'])
            ->get();
    
        
        $formattedContributors = $contributors->map(function ($contributor) use ($session, $userId) {
            if (!$contributor->user || !$contributor->role) {
                Log::error('Fehlende Beziehung für Contributor: '.json_encode($contributor));
                return null;
            }
            return [
                'id' => $contributor->id,
                'user_id'=> $contributor->user_id,
                'icon' => $contributor->role->icon,
                'name' => $contributor->role->name,
                'last_active' => $contributor->last_ping,
                'is_active'=> $contributor->last_ping > now()->subSeconds(40),
                'isHost' => $contributor->user_id === $session->host_id,
                'isMe' => $contributor->user_id == $userId,
                'survey_activated'=>$contributor->user->survey_activated,
                'ideas' => $contributor->ideas ? $contributor->ideas->map(function ($idea) {
                    return [
                        'contributor_id' => $idea->contributor_id,
                        'round' => $idea->round,
                        'title' => $idea->idea_title,
                        'description' => $idea->idea_description,
                        'tag' => $idea->tag
                    ];
                }) : [],
                'votes' => $contributor->votes ? $contributor->votes->map(function ($vote) {
                    return [
                        'idea_id' => $vote->idea_id,
                        'round' => $vote->round,
                        'value' => $vote->value,
                        'type' => $vote->type,
                    ];
                }) : [],
                'email' => $contributor->user->email ?? null,
            ];
        })->filter();
        return response()->json([
            'success' => true,
            'contributors' => $formattedContributors
        ]);
    }

    public function join(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|exists:bf_sessions,id',
            'user_id' => 'required|exists:users,id',
        ]);

        $contributor = Contributor::where('session_id', $validated['session_id'])
            ->where('user_id', $validated['user_id'])
            ->first();

        if (!$contributor) {
            return response()->json(['message' => 'Contributor not found'], 404);
        }

        $contributor->update(['is_active' => true, 'last_ping' => now()]);
        $activeCount = $this->getActiveContributorsCount($validated['session_id']);
        event(new UserJoinedSession($validated['session_id'], $contributor->id, $activeCount));

        return response()->json(['success' => true, 'message' => 'Joined session successfully']);
    }

    public function leave(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|exists:bf_sessions,id',
            'user_id' => 'required|exists:users,id',
        ]);

        $contributor = Contributor::where('session_id', $validated['session_id'])
            ->where('user_id', $validated['user_id'])
            ->first();

        if (!$contributor) {
            return response()->json(['message' => 'Contributor not found'], 404);
        }

        $contributor->update(['is_active' => false]);

        $activeCount = $this->getActiveContributorsCount($validated['session_id']);

        event(new UserLeftSession($validated['session_id'], $contributor->id, $activeCount));

        return response()->json(['success' => true, 'message' => 'Left session successfully']);
    }

    public function ping(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|exists:bf_sessions,id',
            'user_id' => 'required|exists:users,id',
        ]);

        $session = Session::findOrFail($validated['session_id']);
        $contributor = Contributor::where('session_id', $validated['session_id'])
            ->where('user_id', $validated['user_id'])
            ->first();

        if (!$contributor) {
            return response()->json(['message' => 'Contributor not found'], 404);
        }

        $contributor->update(['is_active' => true, 'last_ping' => now()]);

        if ($validated['user_id'] == $session->host_id) {
            $this->checkInactiveUsers($validated['session_id']);
        }

        return response()->json([
            'success' => true,
            'session_id' => $validated['session_id'],
            'user_id' => $validated['user_id']
        ]);
    }

    private function getActiveContributorsCount($sessionId)
    {
        return Contributor::where('session_id', $sessionId)
            ->where('is_active', true)
            ->count();
    }

    private function checkInactiveUsers($sessionId)
    {
        $inactiveUsers = Contributor::where('session_id', $sessionId)
        ->where('last_ping', '>', now()->subSeconds(55))
            ->where('last_ping', '<', now()->subSeconds(40))
            ->get();

        foreach ($inactiveUsers as $user) {
            $this->leave(new Request(['session_id' => $sessionId, 'user_id' => $user->user_id]));
        }
    }
}