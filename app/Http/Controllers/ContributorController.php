<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contributor;
use App\Models\Session;
use App\Models\Idea;
use App\Models\Vote;
use App\Events\RolePick;
use App\Events\UserJoinedSession;
use Log;
class ContributorController extends Controller
{
    public function create(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required',
            'role_id' => 'required',
            'user_id' => 'required',
        ]);

        $sessionId = $request->input('session_id');
        $userId = $request->input('user_id');
        $roleId = $request->input('role_id');

        $contributor = Contributor::where('session_id', $sessionId)
            ->where('user_id', $userId)
            ->first();

        if ($contributor) {
            // Aktualisiere den bestehenden Contributor mit der Rolle
            $contributor->role_id = $roleId;
            $contributor->save();
            $message = 'Contributor updated successfully.';
        } else {
            // Erstelle einen neuen Contributor
            $contributor = Contributor::create([
                'session_id' => $sessionId,
                'user_id' => $userId,
                'role_id' => $roleId,
                'last_ping'=> now()
            ]);
            $message = 'Contributor created successfully.';
        }
        $newContributorsAmount = Contributor::where('session_id', $sessionId)->distinct('user_id')->count();
        Log::info("RolePick");
        event(new RolePick($sessionId));
        event(new UserJoinedSession($sessionId, $userId, $newContributorsAmount));
        return response()->json(['success' => true, 'message' => $message, 'contributor' => $contributor]);
    }
    public function get($sessionId, $userId)
    {
        $session = Session::findOrFail($sessionId);
    
        $votedIdeasCounts = Vote::where('session_id', $sessionId)
            ->where('voting_phase', $session->voting_phase)
            ->selectRaw('contributor_id, count(*) as count')
            ->groupBy('contributor_id')
            ->pluck('count', 'contributor_id');
    
        $totalIdeasToVoteCount = Idea::where('session_id', $sessionId)
            ->whereNotNull('tag')
            ->where('tag', '!=', '')
            ->count();
    
        $contributors = Contributor::where('session_id', $sessionId)
            ->with('role:id,name,icon')
            ->get()
            ->map(function ($contributor) use ($votedIdeasCounts, $totalIdeasToVoteCount) {
                return [
                    'id' => $contributor->id,
                    'role_name' => $contributor->role->name,
                    'icon' => $contributor->role->icon,
                    'last_active' => $contributor->last_ping,
                    'voted_ideas_count' => $votedIdeasCounts[$contributor->id] ?? 0,
                ];
            });
    
        $personalContributor = Contributor::where('session_id', $sessionId)
            ->where('user_id', $userId)
            ->with('role:id,name,icon')
            ->first();
    
        $personalContributorDetails = null;
        if ($personalContributor) {
            $personalContributorDetails = [
                'id' => $personalContributor->id,
                'role_name' => $personalContributor->role->name,
                'icon' => $personalContributor->role->icon,
                'last_active' => $personalContributor->last_ping,
                'voted_ideas_count' => $votedIdeasCounts[$personalContributor->id] ?? 0,
            ];
        }
    
        return response()->json([
            'contributors' => $contributors,
            'personal_contributor' => $personalContributorDetails
        ]);
    }

}
