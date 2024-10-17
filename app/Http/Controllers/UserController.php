<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Contributor;
use App\Models\Session;
use App\Models\Idea;
use App\Models\Method;
use App\Models\Role;
use Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
class UserController extends Controller
{
    public function getStats(Request $request)
    {
        $user = User::where('id', $request->userId)->first();

        if (!$user) {
            return response()->json(['message' => 'Benutzer nicht gefunden'], 404);
        }

        $contributorCount = Contributor::where('user_id', $user->id)->count();

        $ideaCount = Idea::whereIn('contributor_id', function ($query) use ($user) {
            $query->select('id')
                 ->from('bf_contributors')
                 ->where('user_id', $user->id);
        })
        ->whereNotNull('tag')
        ->count();

        $lastIdea = Idea::where('contributor_id', $user->id)->orderBy('created_at', 'desc')->first();
        $lastContribution = Contributor::where('user_id', $user->id)->orderBy('created_at', 'desc')->first();
        $lastActivity = max($lastIdea->created_at ?? null, $lastContribution->created_at ?? null);
        $formattedLastActivity = $lastActivity ? $lastActivity->format('H:i') . ' Uhr (' . $lastActivity->format('d.m.Y') . ')' : null;

        $emailParts = explode('@', $user->email);
        $nameParts = explode('.', $emailParts[0]);
        $userName = ucfirst($nameParts[0]) . ' ' . ucfirst($nameParts[1] ?? '');

        return response()->json([
            'contributor_count' => $contributorCount,
            'idea_count' => $ideaCount,
            'last_activity' => $formattedLastActivity,
            'name' => $userName,
        ]);
    }
    public function getSessions($userId)
    {
        $contributors = Contributor::where('user_id', $userId)->get();
        $sessions = $contributors->map(function ($contributor) {
            $session = Session::find($contributor->session_id);
            $method = Method::find($session->method_id);
            $role = Role::find($contributor->role_id);
            return [
                'session_id' => $contributor->session_id,
                'target' => $session->target,
                'role' => $role->icon,
                'updated_at' => $session->updated_at,
                'method_name' => $method->name,
                'method_id' => $method->id,
                'host_id' => $session->host_id,
                'phase' => $session->phase,
                'vote_round' => $session->vote_round,
                'collecting_round'=> $session->collecting_round
            ];
        })->sortByDesc('updated_at')->values();

        return response()->json($sessions, 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
        ]);

        $userId = $request->input('user_id');

        $user = User::where('id', $userId)->first();

        if ($user) {
            return response()->json(['message' => 'User ID already exists.'], 200);
        } else {
            $token = Str::uuid()->toString();
            $newUser = User::create([
                'id' => $userId,
                'email' => null,
                'password' => null,
                'token' => $token,
            ]);
            return response()->json(['message' => 'User created successfully.', 'token' => $token], 201);
        }
    }
    public function register(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        $userId = $request->input('user_id');
        $email = $request->input('email');
        $password = $request->input('password');

        $user = User::where('id', $userId)->first();

        if ($user) {
            if ($user->email) {
                return $this->login($request);
            } else {
                $user->email = $email;
                $user->password = Hash::make($password);
                $user->save();

                return response()->json(['message' => 'User registered successfully.'], 200);
            }
        } else {
            $newUser = User::create([
                'id' => $userId,
                'email' => $email,
                'password' => Hash::make($password),
            ]);

            return response()->json(['message' => 'User created successfully.'], 201);
        }
    }
    public function logout(Request $request)
    {
        $user = User::where('id', $request->user_id)->first();
        if ($user) {
            $user->token = null;
            $user->save();
        }
        return response()->json(['message' => 'Logout successful'], 200);
    }
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();
        $token = Str::uuid()->toString();
        $user->token = $token;
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Login successful',
                'userId' => $user->id,
                'token' => $token,
            ], 200);
        }
        $user->save();

        return response()->json([
            'message' => 'Login successful',
            'userId' => $user->id,
            'authToken'=>  $token
        ], 200);
    }
}
