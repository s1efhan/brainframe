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
        return response()->json([
            'votes' => $votes
        ]);
    }
    public function vote(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|exists:bf_sessions,id',
            'idea_id' => 'required|exists:bf_ideas,id',
            'contributor_id' => 'required|exists:bf_contributors,id',
            'vote_round' => 'required|integer|min:1',
            'vote_type' => 'required|in:star,ranking,left_right,swipe',
            'vote_value' => 'required|integer|min:0|max:5',
        ]);
    
        $vote = Vote::updateOrCreate(
            [
                'session_id' => $validated['session_id'],
                'idea_id' => $validated['idea_id'],
                'contributor_id' => $validated['contributor_id'],
                'round' => $validated['vote_round'],
                'vote_type' => $validated['vote_type'],
            ],
            [
                'value' => $validated['vote_value'],
            ]
        );
        event(new UserSentVote($vote, $validated['session_id']));
        
        // Prüfung auf vollständige Abstimmung, um Phasenwechsel einzuleiten
        $session = Session::find($validated['session_id']);
        $contributorCount = Contributor::where('session_id', $validated['session_id'])->count();
        $votedIdeasCount = Vote::where('session_id', $validated['session_id'])
            ->where('round', $validated['vote_round'])
            ->where('contributor_id', $validated['contributor_id'])
            ->count();
    
        $maxVotes = $this->getMaxVotes($validated['vote_round'], $validated['vote_type'], $session);
    
        if ($votedIdeasCount >= $maxVotes) {
            $allVotesCount = Vote::where('session_id', $validated['session_id'])
                ->where('round', $validated['vote_round'])
                ->count();
    
            if ($allVotesCount >= $contributorCount * $maxVotes) {
                $stopRequest = new Request([
                    'session_id' => $session->id,
                    'vote_round' => $validated['vote_round'],
                    'collecting_round' => $session->collecting_round
                ]);
                app(SessionController::class)->stop($stopRequest);
            }
        }
        return response()->json(['message' => 'Vote processed successfully'], 200);
    }
    
    private function getMaxVotes($round, $voteType, $session)
    {
        $maxVotes = 0;
        if ($round == 1) {
            $maxVotes = Idea::where('session_id', $session->id)->whereNotNull('tag')->count();
        } else {
            switch ($voteType) {
                case 'ranking':
                    $maxVotes = 5;
                    break;
                case 'star':
                    $maxVotes = 15;
                    break;
                case 'swipe':
                case 'left_right':
                    $maxVotes = 30;
                    break;
            }
        }
        return $maxVotes;
    }
}