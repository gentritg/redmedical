<?php

namespace Tests\Unit;

use App\Models\Order;
use Tests\TestCase;

class OrderModelTest extends TestCase
{
    public function testGetTypes(): void
    {
        $types = Order::getTypes();
        
        $this->assertIsArray($types);
        $this->assertContains(Order::TYPE_CONNECTOR, $types);
        $this->assertContains(Order::TYPE_VPN_CONNECTION, $types);
    }

    public function testGetStatuses(): void
    {
        $statuses = Order::getStatuses();
        
        $this->assertIsArray($statuses);
        $this->assertContains(Order::STATUS_ORDERED, $statuses);
        $this->assertContains(Order::STATUS_PROCESSING, $statuses);
        $this->assertContains(Order::STATUS_COMPLETED, $statuses);
    }

    public function testOrderHasUuid(): void
    {
        $order = new Order();
        $order->name = 'Test Order';
        $order->type = Order::TYPE_CONNECTOR;
        $order->status = Order::STATUS_ORDERED;
        $order->save();

        $this->assertNotNull($order->id);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $order->id);
        
        // Clean up
        $order->delete();
    }

    public function testOrderCreationWithValidData(): void
    {
        $order = new Order();
        $order->name = 'Test Order Creation';
        $order->type = Order::TYPE_CONNECTOR;
        $order->status = Order::STATUS_ORDERED;
        $order->save();

        $savedOrder = Order::find($order->id);
        
        $this->assertNotNull($savedOrder);
        $this->assertEquals('Test Order Creation', $savedOrder->name);
        $this->assertEquals(Order::TYPE_CONNECTOR, $savedOrder->type);
        $this->assertEquals(Order::STATUS_ORDERED, $savedOrder->status);
        
        // Clean up
        $order->delete();
    }

    public function testOrderTimestamps(): void
    {
        $order = new Order();
        $order->name = 'Timestamp Test Order';
        $order->type = Order::TYPE_CONNECTOR;
        $order->status = Order::STATUS_ORDERED;
        $order->save();

        $this->assertNotNull($order->created_at);
        $this->assertNotNull($order->updated_at);
        
        $initialUpdatedAt = $order->updated_at;
        
        // Wait a moment and update
        sleep(1);
        $order->status = Order::STATUS_PROCESSING;
        $order->save();
        
        $this->assertNotEquals($initialUpdatedAt, $order->updated_at);
        
        // Clean up
        $order->delete();
    }
} 