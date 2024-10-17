<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\Session;
use App\Models\Contributor;
use Log;

class RoleController extends Controller
{
    public function get($sessionId)
    {
        $session = Session::findOrFail($sessionId);
        $methodId = $session->method_id;
        $contributors = Contributor::where('session_id', $sessionId)->get();
        $contributorCount = $contributors->count();
        
        if ($methodId === 4 && $contributorCount >= 6) { // Six Thinking Hats
            $assignedRoles = $contributors->groupBy('role_id')
                ->map(function ($group) {
                    return $group->count();
                })
                ->toArray();
    
            $maxAssignments = $contributorCount >= 12 ? 3 : 2;
            $roles = Role::with('methods')
                ->whereHas('methods', function ($query) use ($methodId) {
                    $query->where('bf_methods.id', $methodId);
                })
                ->get()
                ->filter(function ($role) use ($assignedRoles, $maxAssignments) {
                    return !isset($assignedRoles[$role->id]) || $assignedRoles[$role->id] < $maxAssignments;
                })
                ->values()
                ->toArray();
        } else { // andere Methoden
            $assignedRoleIds = $contributors->pluck('role_id')->toArray();
    
            $roles = Role::with('methods')
                ->whereHas('methods', function ($query) use ($methodId) {
                    $query->where('bf_methods.id', $methodId);
                })
                ->whereNotIn('bf_roles.id', $assignedRoleIds)
                ->get()
                ->values()
                ->toArray();
        }
    
        return response()->json($roles);
    }
}
