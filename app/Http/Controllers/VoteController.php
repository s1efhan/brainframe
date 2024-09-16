<?php
namespace App\Http\Controllers;
use App\Models\Vote;
use Illuminate\Http\Request;
use App\Events\LastVote;
use App\Models\Idea;
use App\Models\Contributor;
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
            'votes.*.voting_phase' => 'required|integer|min:1|max:4',
        ]);

        Log::info('Received vote request', ['votes_count' => count($request->votes)]);

        DB::transaction(function () use ($request) {
            foreach ($request->votes as $voteData) {
                $this->processVote($voteData);
            }
            $this->checkAllVotesSubmitted($request->votes[0]);
        });

        return response()->json(['message' => 'Votes processed successfully'], 200);
    }

    private function checkAllVotesSubmitted($sampleVote)
    {
        $sessionId = $sampleVote['session_id'];
        $votingPhase = $sampleVote['voting_phase'];
        $voteType = $sampleVote['vote_type'];
    
        $ideas = Idea::where('session_id', $sessionId)
            ->whereNotNull('tag')
            ->where('tag', '!=', '')
            ->get();
        $contributors = Contributor::where('session_id', $sessionId)->get();
    
        // Begrenzen der Ideen basierend auf dem Abstimmungstyp
        switch ($voteType) {
            case 'left_right':
                $numberOfIdeas = min(intval($ideas->count() / 2), 32);
                $numberOfIdeas = max($numberOfIdeas - ($numberOfIdeas % 2), 2);
                $limitedIdeas = $ideas->slice(0, $numberOfIdeas);
                break;
            case 'swipe':
                $limitedIdeas = $ideas->take(30);
                break;
            case 'star':
                $limitedIdeas = $ideas->take(15);
                break;
            case 'ranking':
                $limitedIdeas = $ideas->take(5);
                break;
            default:
                $limitedIdeas = $ideas;
        }
    
        $totalIdeasCount = $limitedIdeas->count();
    
        Log::info('Checking all votes submitted', [
            'session_id' => $sessionId,
            'voting_phase' => $votingPhase,
            'vote_type' => $voteType,
            'total_ideas_with_tags' => $ideas->count(),
            'limited_ideas_count' => $totalIdeasCount,
            'total_contributors' => $contributors->count()
        ]);
    
        $allVotesSubmitted = true;
        foreach ($contributors as $contributor) {
            $votedIdeasCount = Vote::where('session_id', $sessionId)
                ->where('contributor_id', $contributor->id)
                ->where('voting_phase', $votingPhase)
                ->whereIn('idea_id', $limitedIdeas->pluck('id'))
                ->count();
    
            $isContributorFinished = ($votedIdeasCount >= $totalIdeasCount);
    
            Log::info('Contributor voting status', [
                'contributor_id' => $contributor->id,
                'voted_ideas' => $votedIdeasCount,
                'total_ideas' => $totalIdeasCount,
                'is_finished' => $isContributorFinished
            ]);
    
            if (!$isContributorFinished) {
                $allVotesSubmitted = false;
                break;
            }
        }
    
        if ($allVotesSubmitted) {
            Log::info("All votes submitted for session", ['session_id' => $sessionId]);
            event(new LastVote($sessionId, $votingPhase + 1));
        } else {
            Log::info("Not all votes submitted yet for session", ['session_id' => $sessionId]);
        }
    }
    private function processVote($voteData)
    {
        Log::info('Processing vote', [
            'session_id' => $voteData['session_id'],
            'idea_id' => $voteData['idea_id'],
            'contributor_id' => $voteData['contributor_id'],
            'vote_type' => $voteData['vote_type'],
            'vote_value' => $voteData['vote_value'],
            'voting_phase' => $voteData['voting_phase']
        ]);

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
                'voting_phase' => $voteData['voting_phase'],
            ],
            $voteData
        );

        Log::info('Vote processed successfully');
    }
}