<?php

namespace App\Observers;

use App\Events\OrderChanged;
use App\Models\Order;

class OrderObserver
{
    public function created(Order $order): void
    {
        $this->broadcast($order, 'created');
    }

    public function updated(Order $order): void
    {
        $this->broadcast($order, 'updated');
    }

    public function deleted(Order $order): void
    {
        $this->broadcast($order, 'deleted');
    }

    private function broadcast(Order $order, string $action): void
    {
        if (empty($order->tenant_id)) {
            return;
        }

        OrderChanged::dispatch((string) $order->tenant_id, (string) $order->getKey(), $action);
    }
}
