<?php

namespace App\Providers;

use App\Contracts\OrderServiceInterface;
use App\Services\MockOrderService;
use App\Services\RedProviderPortalService;
use Illuminate\Support\ServiceProvider;

class OrderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OrderServiceInterface::class, function ($app) {
            return config('services.red_provider_portal.enabled', false)
                ? new RedProviderPortalService()
                : new MockOrderService();
        });
    }
} 