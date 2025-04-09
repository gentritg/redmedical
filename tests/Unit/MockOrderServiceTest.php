<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Services\MockOrderService;
use PHPUnit\Framework\TestCase;

class MockOrderServiceTest extends TestCase
{
    private MockOrderService $mockOrderService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockOrderService = new MockOrderService();
    }

    public function testCreateOrder(): void
    {
        // Arrange
        $order = new Order();
        $order->name = 'Test Order';
        $order->type = 'connector';
        $order->status = 'ordered';

        // Act
        $result = $this->mockOrderService->createOrder($order);

        // Assert
        $this->assertNotEmpty($result->external_id);
        $this->assertEquals('ordered', $result->status);
        $this->assertEquals('connector', $result->type);
    }

    public function testGetOrder(): void
    {
        // Arrange
        $order = new Order();
        $order->name = 'Test Order';
        $order->type = 'connector';
        $order->status = 'ordered';
        $createdOrder = $this->mockOrderService->createOrder($order);

        // Act
        $result = $this->mockOrderService->getOrder($createdOrder->external_id);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($createdOrder->external_id, $result['id']);
        $this->assertEquals('connector', $result['type']);
        $this->assertEquals('ordered', $result['status']);
    }

    public function testDeleteOrder(): void
    {
        // Arrange
        $order = new Order();
        $order->name = 'Test Order';
        $order->type = 'connector';
        $order->status = 'ordered';
        $createdOrder = $this->mockOrderService->createOrder($order);

        // Act
        $result = $this->mockOrderService->deleteOrder($createdOrder->external_id);
        $deletedOrder = $this->mockOrderService->getOrder($createdOrder->external_id);

        // Assert
        $this->assertTrue($result);
        $this->assertNull($deletedOrder);
    }

    public function testListOrders(): void
    {
        // Arrange
        $this->mockOrderService = new MockOrderService(); // Fresh instance to control test data
        
        $order1 = new Order();
        $order1->name = 'Test Order 1';
        $order1->type = 'connector';
        $order1->status = 'ordered';
        $this->mockOrderService->createOrder($order1);

        $order2 = new Order();
        $order2->name = 'Test Order 2';
        $order2->type = 'vpn_connection';
        $order2->status = 'ordered';
        $this->mockOrderService->createOrder($order2);

        // Act
        $result = $this->mockOrderService->listOrders();

        // Assert
        $this->assertCount(2, $result);
        $this->assertEquals('connector', $result[0]['type']);
        $this->assertEquals('vpn_connection', $result[1]['type']);
    }
} 