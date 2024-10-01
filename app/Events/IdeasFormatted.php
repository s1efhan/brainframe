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
use App\Models\Idea;

class IdeasFormatted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $ideas;
    public $sessionId;

    public function __construct(array $ideas, int $sessionId)
    {
        $this->ideas = $ideas;
        $this->sessionId = $sessionId;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('session.' . $this->sessionId);
    }
}