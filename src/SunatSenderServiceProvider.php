<?php

declare(strict_types=1);

namespace RashArt\SunatSender;

use Illuminate\Support\ServiceProvider;
use RashArt\SunatSender\Contracts\ProviderInterface;
use RashArt\SunatSender\Providers\OseProvider;
use RashArt\SunatSender\Providers\PseProvider;
use RashArt\SunatSender\Providers\SunatDirectProvider;
use RashArt\SunatSender\Services\SunatSenderService;

class SunatSenderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/sunat-sender.php',
            'sunat-sender'
        );

        // Bind concreto — SunatSenderService ya no implementa una interfaz propia
        $this->app->singleton(SunatSenderService::class, function ($app) {
            $config   = $app['config']['sunat-sender'];
            $provider = $this->resolveProvider($config['provider'] ?? 'sunat', $config);

            return new SunatSenderService($provider, $config);
        });

        // Alias para inyección de dependencias corta
        $this->app->alias(SunatSenderService::class, 'sunat-sender');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/sunat-sender.php' => config_path('sunat-sender.php'),
            ], 'sunat-sender-config');
        }
    }

    protected function resolveProvider(string $providerName, array $config): ProviderInterface
    {
        // Proveedores personalizados registrados en config
        $customProviders = $config['custom_providers'] ?? [];
        if (isset($customProviders[$providerName])) {
            $class = $customProviders[$providerName];
            return new $class($config);
        }

        // Proveedores nativos
        return match ($providerName) {
            'ose'   => new OseProvider($config),
            'pse'   => new PseProvider($config),
            default => new SunatDirectProvider($config), // 'sunat' o fallback
        };
    }
}