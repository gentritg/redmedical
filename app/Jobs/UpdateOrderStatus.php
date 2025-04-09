<?php

namespace App\Jobs;

use App\Contracts\OrderServiceInterface;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateOrderStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly Order $order
    ) {}

    public function handle(OrderServiceInterface $orderService): void
    {
        if (!$this->order->external_id) {
            return;
        }

        $externalOrder = $orderService->getOrder($this->order->external_id);
        
        if (!$externalOrder) {
            Log::warning('External order not found', [
                'order_id' => $this->order->id,
                'external_id' => $this->order->external_id,
            ]);
            return;
        }

        // Nur updaten wenn der Status sich geändert hat - spart DB Writes
        // Bei ca. 500 Orders/Tag macht das schon was aus
        if ($externalOrder['status'] !== $this->order->status) {
            $this->order->update(['status' => $externalOrder['status']]);
        }
    }
} 