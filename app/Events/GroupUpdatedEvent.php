<?php

namespace App\Events;

use App\Models\Group;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GroupUpdatedEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $group;

    public function __construct(Group $group)
    {
        $this->group = $group->load('users');
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('group.' . $this->group->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'group.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'group' => $this->group,
            'message' => 'Nama dompet diperbarui menjadi "' . $this->group->name . '"',
        ];
    }
}
