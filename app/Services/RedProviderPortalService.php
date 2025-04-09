<?php

namespace App\Services;

use App\Contracts\OrderServiceInterface;
use App\Models\Order;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class RedProviderPortalService implements OrderServiceInterface
{
    private Client $client;
    private string $baseUrl;
    private string $clientId;
    private string $clientSecret;
    private ?string $accessToken = null;
    private int $tokenExpiresAt = 0;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.red_provider_portal.url'), '/');
        $this->clientId = config('services.red_provider_portal.client_id');
        $this->clientSecret = config('services.red_provider_portal.client_secret');

        // TODO: Besser wäre es, ein gültiges Zertifikat einzurichten
        // Quick-Fix für die Entwicklungsumgebung
        $sslCertPath = storage_path('app/ssl_cert.pem');
        $verifyOption = file_exists($sslCertPath) ? $sslCertPath : false;

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'verify' => $verifyOption,
        ]);
    }

    public function createOrder(Order $order): Order
    {
        $response = $this->request('POST', '/api/v1/orders', [
            'json' => [
                'type' => $order->type,
            ],
        ]);

        $order->external_id = $response['id'];
        $order->save();

        return $order;
    }

    public function getOrder(string $externalId): ?array
    {
        try {
            return $this->request('GET', "/api/v1/order/{$externalId}");
        } catch (GuzzleException $e) {
            Log::error('Failed to get order from RedProviderPortal', [
                'external_id' => $externalId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function deleteOrder(string $externalId): bool
    {
        try {
            $this->request('DELETE', "/api/v1/order/{$externalId}");
            return true;
        } catch (GuzzleException $e) {
            Log::error('Failed to delete order from RedProviderPortal', [
                'external_id' => $externalId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function listOrders(): array
    {
        return $this->request('GET', '/api/v1/orders');
    }

    private function request(string $method, string $uri, array $options = []): array
    {
        $token = $this->getAccessToken();
        
        $response = $this->client->request($method, $uri, array_merge($options, [
            'headers' => [
                'Authorization' => "Bearer {$token}",
                'Accept' => 'application/json',
            ],
        ]));

        return json_decode($response->getBody()->getContents(), true);
    }

    private function getAccessToken(): string
    {
        if ($this->accessToken && time() < $this->tokenExpiresAt) {
            return $this->accessToken;
        }

        // Hmm... bei hoher Last könnte das zu vielen Token-Requests führen.
        // Vielleicht sollten wir einen Buffer von 30 Sekunden einbauen?
        $response = $this->client->request('POST', '/api/v1/token', [
            'json' => [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        
        $this->accessToken = $data['access_token'];
        $this->tokenExpiresAt = time() + $data['ttl'];

        return $this->accessToken;
    }
} 