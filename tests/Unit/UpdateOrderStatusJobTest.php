<?php

namespace Tests\Unit;

use App\Contracts\OrderServiceInterface;
use App\Jobs\UpdateOrderStatus;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class UpdateOrderStatusJobTest extends TestCase
{
    use RefreshDatabase;
    
    public function testHandleWithExistingExternalOrder(): void
    {
        // Create a real order model for the job
        $order = new Order();
        $order->fill([
            'name' => 'Test Order',
            'type' => Order::TYPE_CONNECTOR,
            'status' => Order::STATUS_ORDERED,
            'external_id' => 'external-123',
        ]);
        $order->save();

        // Create a mock for the OrderServiceInterface
        $this->mock(OrderServiceInterface::class, function (MockInterface $mock) use ($order) {
            $mock->shouldReceive('getOrder')
                ->once()
                ->with($order->external_id)
                ->andReturn([
                    'id' => $order->external_id,
                    'status' => Order::STATUS_PROCESSING, // Different status to trigger update
                    'type' => 'connector'
                ]);
        });

        // Create and dispatch the job
        $job = new UpdateOrderStatus($order);
        $job->handle(app(OrderServiceInterface::class));
        
        // Assert that the order status was updated
        $this->assertEquals(Order::STATUS_PROCESSING, $order->fresh()->status);
    }

    public function testHandleWithNonExistingExternalOrder(): void
    {
        // Create a real order model for the job
        $order = new Order();
        $order->fill([
            'name' => 'Test Order',
            'type' => Order::TYPE_CONNECTOR,
            'status' => Order::STATUS_ORDERED,
            'external_id' => 'external-123',
        ]);
        $order->save();

        // Remember the initial status
        $initialStatus = $order->status;

        // Create a mock for the OrderServiceInterface that returns null (order not found)
        $this->mock(OrderServiceInterface::class, function (MockInterface $mock) use ($order) {
            $mock->shouldReceive('getOrder')
                ->once()
                ->with($order->external_id)
                ->andReturn(null); // Order not found in external service
        });

        // Create and dispatch the job
        $job = new UpdateOrderStatus($order);
        $job->handle(app(OrderServiceInterface::class));
        
        // Assert that the order status remains unchanged
        $this->assertEquals($initialStatus, $order->fresh()->status);
    }

    public function testHandleWithSameStatus(): void
    {
        // Create a real order model for the job
        $order = new Order();
        $order->fill([
            'name' => 'Test Order',
            'type' => Order::TYPE_CONNECTOR,
            'status' => Order::STATUS_ORDERED,
            'external_id' => 'external-123',
        ]);
        $order->save();

        // Get the updated_at timestamp before running the job
        $order->save();
        $initialUpdatedAt = $order->updated_at;

        // Create a mock for the OrderServiceInterface that returns same status
        $this->mock(OrderServiceInterface::class, function (MockInterface $mock) use ($order) {
            $mock->shouldReceive('getOrder')
                ->once()
                ->with($order->external_id)
                ->andReturn([
                    'id' => $order->external_id,
                    'status' => Order::STATUS_ORDERED, // Same status, should not trigger update
                    'type' => 'connector'
                ]);
        });

        // Wait a second to ensure updated_at would change if the model is saved
        sleep(1);
        
        // Create and dispatch the job
        $job = new UpdateOrderStatus($order);
        $job->handle(app(OrderServiceInterface::class));
        
        // Assert that the order was not updated
        $this->assertEquals($initialUpdatedAt->toDateTimeString(), $order->fresh()->updated_at->toDateTimeString());
    }

    public function testHandleWithOrderWithoutExternalId(): void
    {
        // Create a real order model without external_id
        $order = new Order();
        $order->fill([
            'name' => 'Test Order',
            'type' => Order::TYPE_CONNECTOR,
            'status' => Order::STATUS_ORDERED,
            'external_id' => null,
        ]);
        $order->save();

        // We shouldn't even try to get the external order if there's no external_id
        $mock = $this->mock(OrderServiceInterface::class);
        $mock->shouldNotReceive('getOrder');

        // Create and dispatch the job
        $job = new UpdateOrderStatus($order);
        $job->handle(app(OrderServiceInterface::class));
        
        // No assertions needed as we're testing that getOrder isn't called
        $this->assertTrue(true);
    }
} 