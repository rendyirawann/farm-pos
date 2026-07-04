<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast real-time saat pesanan berubah (dibuat / diperbarui / dibayar /
 * selesai / dihapus, atau status item dapur berubah). Menggantikan polling
 * di halaman Kasir & Dapur.
 *
 * - ShouldBroadcastNow: dikirim sinkron (tanpa queue worker).
 * - ShouldDispatchAfterCommit: bila dipanggil di dalam transaksi DB, broadcast
 *   ditunda sampai commit agar klien tidak refetch sebelum data tersimpan.
 */
class OrderChanged implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $tenantId,
        public string $orderId,
        public string $action,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('orders.' . $this->tenantId)];
    }

    public function broadcastAs(): string
    {
        return 'order.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->orderId,
            'action'   => $this->action,
        ];
    }
}
