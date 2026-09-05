<?php

namespace App\Events;

use App\Models\Transaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionUpdatedEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $transaction;

    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction->load('user');
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('group.' . $this->transaction->group_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'transaction.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'transaction' => $this->transaction,
            'message' => ($this->transaction->user ? $this->transaction->user->name : 'Anggota') . ' memperbarui transaksi: ' . ($this->transaction->description ?: $this->transaction->category) . ' (Rp ' . number_format($this->transaction->amount, 0, ',', '.') . ')',
        ];
    }
}
