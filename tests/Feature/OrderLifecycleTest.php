<?php

namespace Tests\Feature;

use App\Contracts\OrderServiceInterface;
use App\Models\Order;
use App\Services\MockOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\TestHelpers\OrderTestHelpers;

class OrderLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Sicherstellen, dass wir den Mock Service für alle Tests nutzen
        $this->app->bind(OrderServiceInterface::class, function () {
            return new MockOrderService();
        });
    }

    /**
     * Ein kompletter Order Lifecycle Test:
     * - Bestellung erstellen
     * - Status überprüfen
     * - Status ändern
     * - Bestellung löschen wenn completed
     */
    public function testCompleteOrderLifecycle(): void
    {
        // 1. Bestellung erstellen
        $orderData = [
            'name' => 'Lifecycle Test Order',
            'type' => Order::TYPE_CONNECTOR,
        ];

        $response = $this->postJson('/api/orders', $orderData);
        $response->assertStatus(201);
        
        $orderId = $response->json('id');
        $this->assertNotNull($orderId);
        
        // 2. Bestellung abrufen und überprüfen
        $getResponse = $this->getJson("/api/orders/{$orderId}");
        $getResponse->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Lifecycle Test Order',
                'type' => Order::TYPE_CONNECTOR,
                'status' => Order::STATUS_ORDERED, // Initial status
            ]);
        
        // 3. Status auf PROCESSING ändern
        $updateResponse = $this->putJson("/api/orders/{$orderId}", [
            'status' => Order::STATUS_PROCESSING,
        ]);
        
        $updateResponse->assertStatus(200)
            ->assertJsonFragment([
                'status' => Order::STATUS_PROCESSING,
            ]);
        
        // 4. Versuch die Bestellung zu löschen (sollte fehlschlagen weil nicht COMPLETED)
        $deleteFailResponse = $this->deleteJson("/api/orders/{$orderId}");
        $deleteFailResponse->assertStatus(422);
        
        // In der DB sollte die Bestellung noch existieren
        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
        ]);
        
        // 5. Status auf COMPLETED ändern
        $finalUpdateResponse = $this->putJson("/api/orders/{$orderId}", [
            'status' => Order::STATUS_COMPLETED,
        ]);
        
        $finalUpdateResponse->assertStatus(200)
            ->assertJsonFragment([
                'status' => Order::STATUS_COMPLETED,
            ]);
        
        // 6. Jetzt sollte das Löschen funktionieren
        $deleteResponse = $this->deleteJson("/api/orders/{$orderId}");
        $deleteResponse->assertStatus(204);
        
        // In der DB sollte die Bestellung nicht mehr existieren
        $this->assertDatabaseMissing('orders', [
            'id' => $orderId,
        ]);
    }

    /**
     * Test zum Filtern und Sortieren der Bestellungen
     */
    public function testOrderFilteringAndSorting(): void
    {
        // Test-Bestellungen mit dem Helper erstellen
        OrderTestHelpers::createTestOrder([
            'name' => 'Alpha Order',
            'type' => Order::TYPE_CONNECTOR,
            'created_at' => now()->subDays(1),
        ]);
        
        OrderTestHelpers::createTestOrder([
            'name' => 'Beta Order', 
            'type' => Order::TYPE_VPN_CONNECTION,
            'created_at' => now(),
        ]);
        
        OrderTestHelpers::createTestOrder([
            'name' => 'Alpha Special',
            'type' => Order::TYPE_CONNECTOR,
            'created_at' => now()->subDays(2),
        ]);
        
        // 1. Alle Bestellungen abrufen (sollten nach Name und dann created_at sortiert sein)
        $allResponse = $this->getJson("/api/orders");
        $allResponse->assertStatus(200)
            ->assertJsonCount(3);
        
        // Erste Bestellung sollte "Alpha Order" sein (sortiert nach Name)
        $this->assertEquals('Alpha Order', $allResponse->json()[0]['name']);
        $this->assertEquals('Alpha Special', $allResponse->json()[1]['name']);
        
        // 2. Bestellungen nach Namen filtern
        $filteredResponse = $this->getJson("/api/orders?name=Special");
        $filteredResponse->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment(['name' => 'Alpha Special']);
        
        // 3. Bestellungen mit leerem Filter (sollte alle zurückgeben)
        $emptyFilterResponse = $this->getJson("/api/orders?name=");
        $emptyFilterResponse->assertStatus(200)
            ->assertJsonCount(3);
    }

    /**
     * Test zur Validierung der Bestelltypen
     */
    public function testOrderTypeValidation(): void
    {
        // 1. Gültiger Typ: connector
        $validConnectorResponse = $this->postJson('/api/orders', [
            'name' => 'Valid Connector Order',
            'type' => Order::TYPE_CONNECTOR,
        ]);
        
        $validConnectorResponse->assertStatus(201);
        
        // 2. Gültiger Typ: vpn_connection
        $validVpnResponse = $this->postJson('/api/orders', [
            'name' => 'Valid VPN Order',
            'type' => Order::TYPE_VPN_CONNECTION,
        ]);
        
        $validVpnResponse->assertStatus(201);
        
        // 3. Ungültiger Typ
        $invalidTypeResponse = $this->postJson('/api/orders', [
            'name' => 'Invalid Type Order',
            'type' => 'unknown_type',
        ]);
        
        $invalidTypeResponse->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
        
        // 4. Fehlender Typ
        $missingTypeResponse = $this->postJson('/api/orders', [
            'name' => 'Missing Type Order',
        ]);
        
        $missingTypeResponse->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }
} 