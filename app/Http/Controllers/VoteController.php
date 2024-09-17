<?php
namespace App\Http\Controllers;
use App\Models\Vote;
use Illuminate\Http\Request;
use App\Events\LastVote;
use App\Models\Idea;
use App\Models\Contributor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Events\SwitchPhase;
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