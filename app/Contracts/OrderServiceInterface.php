<?php

namespace App\Contracts;

use App\Models\Order;

interface OrderServiceInterface
{
    public function createOrder(Order $order): Order;
    public function getOrder(string $externalId): ?array;
    public function deleteOrder(string $externalId): bool;
    public function listOrders(): array;
} 