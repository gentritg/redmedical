<?php

namespace Tests\TestHelpers;

use App\Models\Order;

/**
 * Helper methods for order testing
 */
class OrderTestHelpers
{
    /**
     * Creates an order for testing with default values
     * 
     * @param array $overrides
     * @return Order
     */
    public static function createTestOrder(array $overrides = []): Order
    {
        // Standard-Werte für die Testfälle
        // TODO: Vielleicht factory method nutzen, wenn sich die Tests vermehren
        $defaults = [
            'name' => 'Test Order ' . rand(1000, 9999),
            'type' => Order::TYPE_CONNECTOR,
            'status' => Order::STATUS_ORDERED,
            'external_id' => null,
        ];

        return Order::create(array_merge($defaults, $overrides));
    }

    /**
     * Creates ordered, processing and completed orders for testing the different states
     * 
     * @param string $namePrefix Prefix für Testbenennung
     * @return array Array with the three orders [ordered, processing, completed]
     */
    public static function createOrdersInAllStatuses(string $namePrefix = 'Status Test'): array
    {
        $ordered = self::createTestOrder([
            'name' => "{$namePrefix} - Ordered",
            'status' => Order::STATUS_ORDERED,
        ]);
        
        $processing = self::createTestOrder([
            'name' => "{$namePrefix} - Processing",
            'status' => Order::STATUS_PROCESSING,
        ]);
        
        $completed = self::createTestOrder([
            'name' => "{$namePrefix} - Completed",
            'status' => Order::STATUS_COMPLETED,
        ]);
        
        // Vorsicht: Reihenfolge ist wichtig für einige Tests!
        return [$ordered, $processing, $completed];
    }
    
    /**
     * Quickly check that an order's important fields match expected values
     * 
     * @param Order $order
     * @param array $expectedValues
     * @return bool
     */
    public static function orderMatchesExpectedValues(Order $order, array $expectedValues): bool
    {
        // Für lokales Debugging - auskommentiert lassen
        // var_dump($order->toArray(), $expectedValues);
        
        foreach ($expectedValues as $field => $value) {
            if ($order->$field !== $value) {
                return false;
            }
        }
        
        return true;
    }
} 