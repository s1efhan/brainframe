<?php

namespace App\Http\Controllers;

use App\Models\Vote;
use Illuminate\Http\Request;

class VoteController extends Controller
{
    public function vote(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:bf_sessions,id',
            'idea_id' => 'required|exists:bf_ideas,id',
            'contributor_id' => 'required|exists:bf_contributors,id',
            'vote_type' => 'required|in:swipe,left_right,star,ranking',
            'vote_value' => 'required|numeric',
        ]);

        $voteData = [
            'session_id' => $request->session_id,
            'idea_id' => $request->idea_id,
            'contributor_id' => $request->contributor_id,
            'vote_type' => $request->vote_type,
        ];

        // Setze vote_value oder vote_boolean basierend auf vote_type
        if (in_array($request->vote_type, ['swipe', 'left_right'])) {
            $voteData['vote_boolean'] = (int)$request->vote_value;
            $voteData['vote_value'] = null;
        } else {
            $voteData['vote_value'] = $request->vote_value;
            $voteData['vote_boolean'] = null;
        }

        // Prüfe, ob bereits eine Stimme existiert
        $existingVote = Vote::where([
            'session_id' => $request->session_id,
            'idea_id' => $request->idea_id,
            'contributor_id' => $request->contributor_id,
        ])->first();

        if ($existingVote) {
            // Aktualisiere die bestehende Stimme
            $existingVote->update($voteData);
            $message = 'Vote updated successfully';
        } else {
            // Erstelle eine neue Stimme
            Vote::create($voteData);
            $message = 'Vote created successfully';
        }

        return response()->json(['message' => $message], 200);
    }
}