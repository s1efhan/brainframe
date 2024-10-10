<?php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Session;
use App\Models\Idea;

class SessionStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $formattedSession;
    public $ideas;

    public function __construct(Session $session)
    { 
        $roundLimit = $session->method->name === '6-3-5'
        ? max($session->contributors()->count(), 2)
        : $session->method->round_limit;
        $this -> formattedSession = [
                'id' => $session->id,
                'method' => [
                    'id' => $session->method->id,
                    'name' => $session->method->name,
                    'description' => $session->method->description,
                    'time_limit' => $session->method->time_limit,
                    'round_limit' => $roundLimit,
                    'idea_limit'=>$session->method->idea_limit
                ],
                'target' => $session->target,
                'seconds_left' => $session->seconds_left,
                'collecting_round' => $session->collecting_round,
                'vote_round' => $session->vote_round,
                'phase' => $session->phase,
                'isPaused' => $session->is_paused
            ];
            $session = Session::findOrFail($session->id);
            $this -> ideas = Idea::where('session_id', $session->id)
            ->get();
    }

    public function broadcastOn(): Channel
    {
        return new Channel('session.' . $this->formattedSession['id']);
    }
}