<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Contributor;
use App\Models\Session;
use Log;

class RotateContributorRoles implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $sessionId;
    public $contributorRoles;

    public function __construct(string $sessionId)
    {
        $this->sessionId = $sessionId;
        $session = Session::findOrFail($sessionId);
        $contributors = Contributor::where('session_id', $sessionId)->get();

        $this->contributorRoles = $contributors->map(function ($contributor) {
            return [
                'id' => $contributor->id,
                'icon' => $contributor->role->icon,
                'name' => $contributor->role->name,
            ];
        });
        Log::info('contributorRolesEvent: '.$this->contributorRoles);
    }

    public function broadcastOn(): Channel
    {
        return new Channel('session.' . $this->sessionId);
    }
}