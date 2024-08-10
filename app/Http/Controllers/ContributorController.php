<?php 
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contributor;
use App\Events\ContributorJoin;

class ContributorController extends Controller
{
    public function create(Request $request)
    {
        // Validierung der eingehenden Daten
        $validated = $request->validate([
            'session_id' => 'required',
            'role_id' => 'required',
            'user_id' => 'required',
        ]);

        $sessionId = $request->input('session_id');
        $userId = $request->input('user_id');
        $roleId = $request->input('role_id');

        // Überprüfe, ob ein Contributor mit der gleichen user_id und session_id existiert
        $existingContributor = Contributor::where('session_id', $sessionId)
                                         ->where('user_id', $userId)
                                         ->first();

        if ($existingContributor) {
            // Gib eine Fehlermeldung zurück, wenn der Contributor bereits existiert
            return response()->json(['success' => false, 'message' => 'Contributor already exists for this session and user.'], 400);
        }

        // Erstelle neuen Contributor
        $newContributor = Contributor::create([
            'session_id' => $sessionId,
            'user_id' => $userId,
            'role_id' => $roleId
        ]);
        ContributorJoin::dispatch($sessionId, $userId, $roleId);

        return response()->json(['success' => true, 'contributor' => $newContributor]);
    }public function get($sessionId, $userId)
    {
        // Alle Contributors für die gegebene Session abrufen
        $contributors = Contributor::where('session_id', $sessionId)
            ->with('role:id,name')
            ->get()
            ->map(function ($contributor) {
                return [
                    'id' => $contributor->id,
                    'role_name' => $contributor->role->name
                ];
            });
    
        // Persönlichen Contributor für die gegebene userId abrufen
        $personalContributor = Contributor::where('session_id', $sessionId)
            ->where('user_id', $userId)
            ->with('role:id,name')
            ->first();
    
        $personalContributorDetails = null;
        if ($personalContributor) {
            $personalContributorDetails = [
                'id' => $personalContributor->id,
                'role_name' => $personalContributor->role->name
            ];
        }
    
        // Beides zusammen in einer einzigen Response zurückgeben
        return response()->json([
            'contributors' => $contributors,
            'personal_contributor' => $personalContributorDetails
        ]);
    }
    
}
