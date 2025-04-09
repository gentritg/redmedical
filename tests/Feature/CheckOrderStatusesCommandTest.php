<?php

namespace Tests\Feature;

use App\Jobs\UpdateOrderStatus;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use ReflectionProperty;
use Tests\TestCase;

class CheckOrderStatusesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function testCommandDispatchesJobsForNonCompletedOrders(): void
    {
        // Arrange: Fake the bus to track dispatched jobs
        Bus::fake();
        
        // Create orders with different statuses
        $orderedOrder = Order::create([
            'name' => 'Ordered Order',
            'type' => Order::TYPE_CONNECTOR,
            'status' => Order::STATUS_ORDERED,
            'external_id' => 'external-id-1',
        ]);
        
        $processingOrder = Order::create([
            'name' => 'Processing Order',
            'type' => Order::TYPE_CONNECTOR,
            'status' => Order::STATUS_PROCESSING,
            'external_id' => 'external-id-2',
        ]);
        
        $completedOrder = Order::create([
            'name' => 'Completed Order',
            'type' => Order::TYPE_CONNECTOR,
            'status' => Order::STATUS_COMPLETED,
            'external_id' => 'external-id-3',
        ]);
        
        $orderWithoutExternalId = Order::create([
            'name' => 'No External ID Order',
            'type' => Order::TYPE_CONNECTOR,
            'status' => Order::STATUS_ORDERED,
            'external_id' => null,
        ]);

        // Act: Run the command
        $this->artisan('orders:check-statuses')
            ->assertSuccessful();

        // Assert: Verify the jobs were dispatched only for non-completed orders with external IDs
        Bus::assertDispatched(UpdateOrderStatus::class, function ($job) use ($orderedOrder) {
            $orderProperty = new ReflectionProperty(UpdateOrderStatus::class, 'order');
            $orderProperty->setAccessible(true);
            $jobOrder = $orderProperty->getValue($job);
            return $jobOrder->id === $orderedOrder->id;
        });
        
        Bus::assertDispatched(UpdateOrderStatus::class, function ($job) use ($processingOrder) {
            $orderProperty = new ReflectionProperty(UpdateOrderStatus::class, 'order');
            $orderProperty->setAccessible(true);
            $jobOrder = $orderProperty->getValue($job);
            return $jobOrder->id === $processingOrder->id;
        });
        
        // The completed order and the one without external ID should not have jobs dispatched
        Bus::assertNotDispatched(UpdateOrderStatus::class, function ($job) use ($completedOrder) {
            $orderProperty = new ReflectionProperty(UpdateOrderStatus::class, 'order');
            $orderProperty->setAccessible(true);
            $jobOrder = $orderProperty->getValue($job);
            return $jobOrder->id === $completedOrder->id;
        });
        
        Bus::assertNotDispatched(UpdateOrderStatus::class, function ($job) use ($orderWithoutExternalId) {
            $orderProperty = new ReflectionProperty(UpdateOrderStatus::class, 'order');
            $orderProperty->setAccessible(true);
            $jobOrder = $orderProperty->getValue($job);
            return $jobOrder->id === $orderWithoutExternalId->id;
        });
    }

    public function testCommandOutputsCorrectInformation(): void
    {
        // Arrange: Fake the bus
        Bus::fake();
        
        // Create some test orders
        $order1 = Order::create([
            'name' => 'Test Order 1',
            'type' => Order::TYPE_CONNECTOR,
            'status' => Order::STATUS_ORDERED,
            'external_id' => 'external-id-1',
        ]);
        
        $order2 = Order::create([
            'name' => 'Test Order 2',
            'type' => Order::TYPE_VPN_CONNECTION,
            'status' => Order::STATUS_PROCESSING,
            'external_id' => 'external-id-2',
        ]);

        // Act and Assert: Run the command and check the output
        $this->artisan('orders:check-statuses')
            ->expectsOutput("Found 2 orders to check.")
            ->expectsOutput("Dispatched status check for order {$order1->id}")
            ->expectsOutput("Dispatched status check for order {$order2->id}")
            ->assertSuccessful();
        
        // Additional assertion to verify all jobs were dispatched
        Bus::assertDispatched(UpdateOrderStatus::class, 2);
    }

    public function testCommandWithNoOrdersToCheck(): void
    {
        // Arrange: Fake the bus
        Bus::fake();
        
        // Create only completed orders
        Order::create([
            'name' => 'Completed Order 1',
            'type' => Order::TYPE_CONNECTOR,
            'status' => Order::STATUS_COMPLETED,
            'external_id' => 'external-id-1',
        ]);

        // Act and Assert: Run the command and check the output
        $this->artisan('orders:check-statuses')
            ->expectsOutput("Found 0 orders to check.")
            ->assertSuccessful();
        
        // Additional assertion to verify no jobs were dispatched
        Bus::assertNotDispatched(UpdateOrderStatus::class);
    }
} 