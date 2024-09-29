<?php
namespace App\Http\Controllers;
use App\Models\Vote;
use Illuminate\Http\Request;
use App\Models\Idea;
use App\Models\Session;
use App\Models\Contributor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Events\UserSentVote;
class VoteController extends Controller
{
    public function get($sessionId){
        $session = Session::findOrFail($sessionId);
        $votes = Vote::where('session_id', $sessionId)
        ->get();
        Log::info('contributors: '.json_encode($votes));
        return response()->json([
            'votes' => $votes
        ]);
    }
    public function vote(Request $request)
    {
        Log::info($request);
        $validated = $request->validate([
            'session_id' => 'required|exists:bf_sessions,id',
            'idea_id' => 'required|exists:bf_ideas,id',
            'contributor_id' => 'required|exists:bf_contributors,id',
            'vote_round' => 'required|integer|min:1',
            'vote_type' => 'required|in:star,ranking,left_right,swipe',
            'vote_value' => 'required|integer|min:1|max:5',
        ]);
    
        Vote::updateOrCreate(
            [
                'session_id' => $validated['session_id'],
                'idea_id' => $validated['idea_id'],
                'contributor_id' => $validated['contributor_id'],
                'round' => $validated['vote_round'],
                'vote_type' => $validated['vote_type'], // Hinzugefügt
            ],
            [
                'value' => $validated['vote_value'],
            ]
        );
    
        event(new UserSentVote($validated['session_id'], $validated['contributor_id'], $validated['vote_round']));
    
        return response()->json(['message' => 'Vote processed successfully'], 200);
    }
/*
    private function checkAllVotesSubmitted($sampleVote)
    {
        $sessionId = $sampleVote['session_id'];
        $votingPhase = $sampleVote['voting_phase'];
        $voteType = $sampleVote['vote_type'];
    
        $ideas = Idea::where('session_id', $sessionId)
            ->whereNotNull('tag')
            ->where('tag', '!=', '')
            ->get();
    
        
        
        $totalIdeas = Idea::where('session_id', $sessionId)
        ->whereNotNull('tag')
        ->where('tag', '!=', '')
        ->when($votingPhase > 1, function ($query) use ($votingPhase) {
            return $query->whereHas('votes', function ($subquery) use ($votingPhase) {
                $subquery->where('voting_phase', $votingPhase);
            });
        })
        ->get();
        $totalIdeasCount = $totalIdeas->count();

        Log::info('Überprüfe Stimmabgabe', [
            'session_id' => $sessionId,
            'voting_phase' => $votingPhase,
            'vote_type' => $voteType,
            'limited_ideas_count' => $totalIdeasCount,
        ]);
    
        $allVotesSubmitted = Contributor::where('session_id', $sessionId)
            ->get()
            ->every(function ($contributor) use ($sessionId, $votingPhase, $totalIdeas, $totalIdeasCount) {
                $votedIdeasCount = Vote::where('session_id', $sessionId)
                    ->where('contributor_id', $contributor->id)
                    ->where('voting_phase', $votingPhase)
                    ->whereIn('idea_id', $totalIdeas->pluck('id'))
                    ->count();
    
                $isContributorFinished = ($votedIdeasCount >= $totalIdeasCount);
                Log::debug('Teilnehmer-Abstimmungsstatus', [
                    'contributor_id' => $contributor->id,
                    'voted_ideas' => $votedIdeasCount,
                    'total_ideas' => $totalIdeasCount,
                    'is_finished' => $isContributorFinished
                ]);
    
                return $isContributorFinished;
            });
    
            if ($allVotesSubmitted) {
                Log::info("Alle Stimmen abgegeben", ['session_id' => $sessionId]);
                
                $voteType = Vote::where('session_id', $sessionId)
                ->where('voting_phase', $votingPhase)
                ->first()
                ->vote_type;
            
                
                if ($voteType === 'ranking') {
                    event(new LastVote($sessionId, 0, true));
                } else {
                    event(new LastVote($sessionId, $votingPhase + 1, false));
                }
            } else {
                Log::info("Noch nicht alle Stimmen abgegeben", ['session_id' => $sessionId]);
            }
    
        return $allVotesSubmitted;
    }
    private function limitIdeasByVoteType($ideas, $voteType)
    {
        switch ($voteType) {
            case 'left_right':
                $count = min(intval($ideas->count() / 2), 32);
                return $ideas->slice(0, max($count - ($count % 2), 2));
            case 'swipe':
                return $ideas->take(30);
            case 'star':
                return $ideas->take(15);
            case 'ranking':
                return $ideas->take(5);
            default:
                return $ideas;
        }
    }
        */
}