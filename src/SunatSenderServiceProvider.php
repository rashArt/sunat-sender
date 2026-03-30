<?php

namespace RashArt\SunatSender;

use Illuminate\Support\ServiceProvider;
use RashArt\SunatSender\Contracts\SunatSenderInterface;
use RashArt\SunatSender\Contracts\ProviderInterface;
use RashArt\SunatSender\Providers\OseProvider;
use RashArt\SunatSender\Providers\PseProvider;
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
            $config = $app['config']['sunat-sender'];
            $provider = $this->resolveProvider($config['provider'] ?? 'ose', $config);

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
        $providers = $config['providers'] ?? [];

        if (isset($providers[$providerName])) {
            $providerClass = $providers[$providerName];
            return new $providerClass($config);
        }

        return match ($providerName) {
            'pse' => new PseProvider($config),
            default => new OseProvider($config),
        };
    }
}
