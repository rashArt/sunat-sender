<?php

namespace RashArt\SunatSender;

use Illuminate\Support\ServiceProvider;
use RashArt\SunatSender\Contracts\ProviderInterface;
use RashArt\SunatSender\Contracts\SunatSenderInterface;
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

        $this->app->bind(SunatSenderInterface::class, function ($app) {
            $config   = $app['config']['sunat-sender'];
            $provider = $this->resolveProvider($config['provider'] ?? 'sunat', $config);

            return new SunatSenderService($provider, $config);
        });

        $this->app->alias(SunatSenderInterface::class, 'sunat-sender');
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
        // Primero busca en proveedores personalizados registrados en config
        $customProviders = $config['providers'] ?? [];
        if (isset($customProviders[$providerName])) {
            $class = $customProviders[$providerName];
            return new $class($config);
        }

        // Proveedores nativos del paquete
        return match ($providerName) {
            'ose'   => new OseProvider($config),
            'pse'   => new PseProvider($config),
            default => new SunatDirectProvider($config), // 'sunat' o cualquier valor
        };
    }
}