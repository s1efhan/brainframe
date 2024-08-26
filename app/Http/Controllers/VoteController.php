<?php

namespace App\Http\Controllers;

use App\Models\Vote;
use Illuminate\Http\Request;
use App\Events\VotingFinished;
use App\Models\Idea;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class VoteController extends Controller
{
    public function vote(Request $request)
    {
        $request->validate([
            'votes' => 'required|array',
            'votes.*.session_id' => 'required|exists:bf_sessions,id',
            'votes.*.idea_id' => 'required|exists:bf_ideas,id',
            'votes.*.contributor_id' => 'required|exists:bf_contributors,id',
            'votes.*.vote_type' => 'required|in:swipe,left_right,star,ranking',
            'votes.*.vote_value' => 'required|numeric',
            'votes.*.voting_phase' => 'required|integer|min:1|max:4',  // Hinzugefügte Validierung für voting_phase
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->votes as $voteData) {
                $this->processVote($voteData);
            }

            $this->checkVotingFinished($request->votes[0]);
        });

        return response()->json(['message' => 'Votes processed successfully'], 200);
    }

    private function processVote($voteData)
    {
        if (in_array($voteData['vote_type'], ['swipe', 'left_right'])) {
            $voteData['vote_boolean'] = (int) $voteData['vote_value'];
            $voteData['vote_value'] = null;
        } else {
            $voteData['vote_boolean'] = null;
        }

        Vote::updateOrCreate(
            [
                'session_id' => $voteData['session_id'],
                'idea_id' => $voteData['idea_id'],
                'contributor_id' => $voteData['contributor_id'],
                'vote_type' => $voteData['vote_type'],
                'voting_phase' => $voteData['voting_phase'],  // Stellt sicher, dass voting_phase gespeichert wird
            ],
            $voteData
        );
    }

    private function checkVotingFinished($sampleVote)
    {
        $votedIdeasCount = Vote::where([
            'session_id' => $sampleVote['session_id'],
            'contributor_id' => $sampleVote['contributor_id'],
            'vote_type' => $sampleVote['vote_type'],
            'voting_phase' => $sampleVote['voting_phase'],  // Prüft die Anzahl der Stimmen für die aktuelle Phase
        ])->count();

        $totalIdeasCount = Idea::where('session_id', $sampleVote['session_id'])
            ->whereNotNull('tag')
            ->count();

        Log::info('Voting progress', [
            'voted_ideas_count' => $votedIdeasCount,
            'total_ideas_count' => $totalIdeasCount,
            'session_id' => $sampleVote['session_id'],
            'contributor_id' => $sampleVote['contributor_id'],
            'vote_type' => $sampleVote['vote_type'],
            'voting_phase' => $sampleVote['voting_phase'],  // Loggen der voting_phase
        ]);

        $isVotingFinished = ($votedIdeasCount == $totalIdeasCount) ||
            ($sampleVote['vote_type'] == 'left_right' && $votedIdeasCount == $totalIdeasCount - 1);

        if ($isVotingFinished) {
            Log::info('Voting finished', [
                'session_id' => $sampleVote['session_id'],
                'contributor_id' => $sampleVote['contributor_id'],
                'vote_type' => $sampleVote['vote_type'],
                'voting_phase' => $sampleVote['voting_phase'],  // Loggen des Endes der Phase
            ]);
            event(new VotingFinished($sampleVote['session_id'], $sampleVote['contributor_id'], $sampleVote['vote_type']));
        }
    }
}
