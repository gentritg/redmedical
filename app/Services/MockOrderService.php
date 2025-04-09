<?php

namespace App\Services;

use App\Contracts\OrderServiceInterface;
use App\Models\Order;
use Illuminate\Support\Collection;

class MockOrderService implements OrderServiceInterface
{
    private Collection $orders;

    public function __construct()
    {
        $this->orders = collect();
    }

    public function createOrder(Order $order): Order
    {
        // Mocked external ID - muss eindeutig sein und UUID-Format haben
        $order->external_id = fake()->uuid();
        $this->orders->push([
            'id' => $order->external_id,
            'type' => $order->type,
            'status' => 'ordered',
        ]);

        return $order;
    }

    // Zum Testen könnte man hier eine Verzögerung einbauen:
    // sleep(1); // Simulation der Netzwerk-Latenz
    public function getOrder(string $externalId): ?array
    {
        return $this->orders->firstWhere('id', $externalId);
    }

    public function deleteOrder(string $externalId): bool
    {
        $this->orders = $this->orders->reject(fn ($order) => $order['id'] === $externalId);
        return true;
    }

    public function listOrders(): array
    {
        return $this->orders->all();
    }
} 