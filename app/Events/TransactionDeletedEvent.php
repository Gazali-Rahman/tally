<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionDeletedEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $transactionId;
    public $groupId;
    public $amount;
    public $type;
    public $userName;

    public function __construct(int $transactionId, int $groupId, float $amount, string $type, string $userName)
    {
        $this->transactionId = $transactionId;
        $this->groupId = $groupId;
        $this->amount = $amount;
        $this->type = $type;
        $this->userName = $userName;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('group.' . $this->groupId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'transaction.deleted';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->transactionId,
            'group_id' => $this->groupId,
            'amount' => $this->amount,
            'type' => $this->type,
            'message' => $this->userName . ' menghapus satu catatan transaksi.',
        ];
    }
}
