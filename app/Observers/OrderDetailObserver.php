<?php

namespace App\Observers;

use App\Events\OrderChanged;
use App\Models\OrderDetail;

class OrderDetailObserver
{
    /**
     * Status item dapur berubah (pending -> cooking -> done). Broadcast agar
     * layar Dapur & Kasir memperbarui secara real-time.
     */
    public function updated(OrderDetail $detail): void
    {
        $tenantId = $detail->tenant_id ?: optional($detail->order)->tenant_id;

        if (empty($tenantId)) {
            return;
        }

        OrderChanged::dispatch((string) $tenantId, (string) $detail->order_id, 'item-updated');
    }
}
