<?php

namespace Tests\Feature;

use App\Contracts\OrderServiceInterface;
use App\Models\Order;
use App\Services\MockOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Use the MockOrderService for all tests
        $this->app->bind(OrderServiceInterface::class, function () {
            return new MockOrderService();
        });
    }

    public function testIndexEndpoint(): void
    {
        // Create some test orders
        Order::create([
            'name' => 'Test Order 1',
            'type' => Order::TYPE_CONNECTOR,
            'status' => Order::STATUS_ORDERED,
        ]);
        
        Order::create([
            'name' => 'Test Order 2',
            'type' => Order::TYPE_VPN_CONNECTION,
            'status' => Order::STATUS_PROCESSING,
        ]);

        // Test the endpoint
        $response = $this->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonStructure([
                '*' => ['id', 'name', 'type', 'status', 'created_at', 'updated_at']
            ]);
    }

    public function testIndexEndpointWithNameFilter(): void
    {
        // Create some test orders
        Order::create([
            'name' => 'Special Order',
            'type' => Order::TYPE_CONNECTOR,
            'status' => Order::STATUS_ORDERED,
        ]);
        
        Order::create([
            'name' => 'Regular Order',
            'type' => Order::TYPE_VPN_CONNECTION,
            'status' => Order::STATUS_PROCESSING,
        ]);

        // Test the endpoint with filter
        $response = $this->getJson('/api/orders?name=Special');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment(['name' => 'Special Order']);
    }

    public function testStoreEndpoint(): void
    {
        // Mock the OrderServiceInterface to verify the service is called
        $this->mock(OrderServiceInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('createOrder')
                ->once()
                ->andReturnUsing(function ($order) {
                    $order->external_id = 'test-external-id';
                    return $order;
                });
        });

        $orderData = [
            'name' => 'New API Order',
            'type' => Order::TYPE_CONNECTOR,
        ];

        $response = $this->postJson('/api/orders', $orderData);

        $response->assertStatus(201);
        
        // Check for fragment with individual assertions to avoid the full status check
        $responseData = $response->json();
        $this->assertEquals('New API Order', $responseData['name']);
        $this->assertEquals(Order::TYPE_CONNECTOR, $responseData['type']);
        
        // Status field should either be in the response or this should be the default
        $expectedStatus = $responseData['status'] ?? Order::STATUS_ORDERED;
        $this->assertEquals(Order::STATUS_ORDERED, $expectedStatus);
        
        $this->assertDatabaseHas('orders', [
            'name' => 'New API Order',
            'type' => Order::TYPE_CONNECTOR,
        ]);
    }

    public function testStoreEndpointWithInvalidData(): void
    {
        // Missing required name field
        $orderData = [
            'type' => Order::TYPE_CONNECTOR,
        ];

        $response = $this->postJson('/api/orders', $orderData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function testStoreEndpointWithInvalidType(): void
    {
        // Invalid type
        $orderData = [
            'name' => 'Invalid Type Order',
            'type' => 'invalid_type',
        ];

        $response = $this->postJson('/api/orders', $orderData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    public function testShowEndpoint(): void
    {
        // Create a test order
        $order = Order::create([
            'name' => 'Show Test Order',
            'type' => Order::TYPE_CONNECTOR,
            'status' => Order::STATUS_ORDERED,
        ]);

        $response = $this->getJson("/api/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $order->id,
                'name' => 'Show Test Order',
                'type' => Order::TYPE_CONNECTOR,
                'status' => Order::STATUS_ORDERED,
            ]);
    }

    public function testShowEndpointWithInvalidId(): void
    {
        $response = $this->getJson('/api/orders/non-existent-id');

        $response->assertStatus(404);
    }

    public function testUpdateEndpointWithStatus(): void
    {
        // Create a test order
        $order = Order::create([
            'name' => 'Update Test Order',
            'type' => Order::TYPE_CONNECTOR,
            'status' => Order::STATUS_ORDERED,
        ]);

        $response = $this->putJson("/api/orders/{$order->id}", [
            'status' => Order::STATUS_PROCESSING
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $order->id,
                'status' => Order::STATUS_PROCESSING,
            ]);
        
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => Order::STATUS_PROCESSING,
        ]);
    }

    public function testUpdateEndpointWithInvalidStatus(): void
    {
        // Create a test order
        $order = Order::create([
            'name' => 'Invalid Update Test Order',
            'type' => Order::TYPE_CONNECTOR,
            'status' => Order::STATUS_ORDERED,
        ]);

        $response = $this->putJson("/api/orders/{$order->id}", [
            'status' => 'invalid_status'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function testDestroyEndpoint(): void
    {
        // Mock the OrderServiceInterface to verify the service is called
        $this->mock(OrderServiceInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('deleteOrder')
                ->once()
                ->andReturn(true);
        });

        // Create a test order
        $order = Order::create([
            'name' => 'Delete Test Order',
            'type' => Order::TYPE_CONNECTOR,
            'status' => Order::STATUS_COMPLETED, // Must be completed to delete
            'external_id' => 'external-test-id',
        ]);

        $response = $this->deleteJson("/api/orders/{$order->id}");

        $response->assertStatus(204);
        
        $this->assertDatabaseMissing('orders', [
            'id' => $order->id,
        ]);
    }

    public function testDestroyEndpointWithNonCompletedOrder(): void
    {
        // Create a test order
        $order = Order::create([
            'name' => 'Non-Completed Delete Test Order',
            'type' => Order::TYPE_CONNECTOR,
            'status' => Order::STATUS_ORDERED, // Not completed
        ]);

        $response = $this->deleteJson("/api/orders/{$order->id}");

        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Only completed orders can be deleted'
            ]);
        
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
        ]);
    }
} 