<?php

namespace App\Events;

use Illuminate\Support\Facades\Log;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Contributor;
use App\Models\Session;

class UserPickedRole implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $sessionId;
    public $formattedContributor;

    public function __construct(string $sessionId, Contributor $contributor)
    {
        $this->sessionId = $sessionId;
        $session = Session::findOrFail($sessionId);

        $this->formattedContributor = [
            'id' => $contributor->id,
            'user_id'=> $contributor->user_id,
            'icon' => $contributor->role->icon,
            'name' => $contributor->role->name,
            'last_active' => $contributor->last_ping,
            'isHost' => $contributor->user_id === $session->host_id,
            'isMe' => false, // This will be set on the client side
            'ideas' => $contributor->ideas->map(function ($idea) {
                return [
                    'contributor_id' => $idea->contributor_id,
                    'round' => $idea->round,
                    'title' => $idea->idea_title,
                    'description' => $idea->idea_description,
                    'tag' => $idea->tag
                ];
            }),
            'votes' => $contributor->votes->map(function ($vote) {
                return [
                    'idea_id' => $vote->idea_id,
                    'round' => $vote->round,
                    'value' => $vote->value,
                    'type' => $vote->type,
                ];
            }),
            'email' => $contributor->user->email ?? null,
        ];

        Log::info("RolePick: " . json_encode($this->formattedContributor));
    }

    public function broadcastOn(): Channel
    {
        return new Channel('session.' . $this->sessionId);
    }
}