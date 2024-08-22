<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\Session;
use App\Models\Contributor; // Füge das Modell für Contributor hinzu

class RoleController extends Controller
{
    public function get($sessionId)
    {
        // Hole die Session und den zugehörigen Method-ID
        $session = Session::findOrFail($sessionId);
        $methodId = $session->method_id;

        // Finde alle Contributor für die aktuelle Session
        $assignedRoleIds = Contributor::where('session_id', $sessionId)
            ->pluck('role_id')
            ->toArray();

        // Lade alle Rollen und filtere nach den zugehörigen Methoden
        $roles = Role::with('methods')
            ->get()
            ->filter(function ($role) use ($methodId, $assignedRoleIds) {
                // Filtere Rollen basierend auf Method-ID und Rolle, die nicht zugewiesen ist
                return $role->methods->contains('id', $methodId) && !in_array($role->id, $assignedRoleIds);
            })
            ->values()
            ->toArray();

        return response()->json($roles);
    }
}
