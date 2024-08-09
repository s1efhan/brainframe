<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
class UserController extends Controller
{
    /**
     * Speichere oder aktualisiere die User-ID.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Validierung der eingehenden Daten
        $request->validate([
            'user_id' => 'required|string',
        ]);

        // Abrufen der User-ID aus dem Request
        $userId = $request->input('user_id');

        // Überprüfen, ob der Benutzer bereits existiert
        $user = User::where('id', $userId)->first();

        if ($user) {
            // Der Benutzer existiert bereits
            return response()->json(['message' => 'User ID already exists.'], 200);
        } else {
            // Der Benutzer existiert nicht, du kannst einen neuen Benutzer erstellen
            $newUser = User::create([
                'id' => $userId,
                // Standardwerte für die nullable Felder
                'email' => null,
                'password' => null,
            ]);

            return response()->json(['message' => 'User created successfully.'], 201);
        }
    }
}
