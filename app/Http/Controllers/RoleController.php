<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\Session;

class RoleController extends Controller
{
    public function get($sessionId)
    {
        // Hole die Session und den zugehörigen Method-ID
        $session = Session::findOrFail($sessionId);
        $methodId = $session->method_id;

        // Lade alle Rollen und filtere nach den zugehörigen Methoden
        $roles = Role::with('methods')
            ->get()
            ->filter(function ($role) use ($methodId) {
                return $role->methods->contains('id', $methodId);
            })
            ->values()
            ->toArray();

        return response()->json($roles);
    }
}