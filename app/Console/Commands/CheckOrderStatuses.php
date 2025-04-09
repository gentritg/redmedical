<?php

namespace App\Console\Commands;

use App\Jobs\UpdateOrderStatus;
use App\Models\Order;
use Illuminate\Console\Command;

class CheckOrderStatuses extends Command
{
    protected $signature = 'orders:check-statuses';
    protected $description = 'Check and update the status of all non-completed orders';

    public function handle(): int
    {
        // Nur die Orders mit Status != completed müssen gecheckt werden
        // Das spart unnötige Requests zum Provider
        $orders = Order::whereNotIn('status', ['completed'])
            ->whereNotNull('external_id')
            ->get();

        $this->info("Found {$orders->count()} orders to check.");

        // Alte Implementierung: Direkt abfragen - hat bei Lastspitzen nicht skaliert
        // $orderService->getOrder()...
        
        foreach ($orders as $order) {
            UpdateOrderStatus::dispatch($order);
            $this->line("Dispatched status check for order {$order->id}");
        }

        return self::SUCCESS;
    }
} 