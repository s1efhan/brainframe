<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contributor;
use App\Events\ContributorJoin;

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
                'role_id' => $roleId
            ]);
            $message = 'Contributor created successfully.';
        }

        ContributorJoin::dispatch($sessionId, $userId, $roleId);

        return response()->json(['success' => true, 'message' => $message, 'contributor' => $contributor]);
    }
    public function get($sessionId, $userId)
    {
        // Alle Contributors für die gegebene Session abrufen
        $contributors = Contributor::where('session_id', $sessionId)
            ->with('role:id,name,icon')  // Lade sowohl 'name' als auch 'icon'
            ->get()
            ->map(function ($contributor) {
                return [
                    'id' => $contributor->id,
                    'role_name' => $contributor->role->name,
                    'icon' => $contributor->role->icon, // Icon hinzufügen
                    'last_active' => $contributor->last_ping
                ];
            });

        // Den persönlichen Contributor für den gegebenen Benutzer abrufen
        $personalContributor = Contributor::where('session_id', $sessionId)
            ->where('user_id', $userId)
            ->with('role:id,name,icon')  // Lade sowohl 'name' als auch 'icon'
            ->first();


        $personalContributorDetails = null;
        if ($personalContributor) {
            $personalContributorDetails = [
                'id' => $personalContributor->id,
                'role_name' => $personalContributor->role->name,
                'icon' => $personalContributor->role->icon, // Icon hinzufügen
                'last_active' => $personalContributor->last_ping
            ];
        }

        // Beides zusammen in einer einzigen Response zurückgeben
        return response()->json([
            'contributors' => $contributors,
            'personal_contributor' => $personalContributorDetails
        ]);
    }


}
