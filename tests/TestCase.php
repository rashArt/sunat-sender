<?php

namespace RashArt\SunatSender\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use RashArt\SunatSender\SunatSenderServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            SunatSenderServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('sunat-sender.provider', 'ose');
        $app['config']->set('sunat-sender.ruc', '20000000001');
        $app['config']->set('sunat-sender.username', 'MODDATOS');
        $app['config']->set('sunat-sender.password', 'moddatos');
        $app['config']->set('sunat-sender.ose_url', 'https://e-beta.sunat.gob.pe');
        $app['config']->set('sunat-sender.timeout', 30);
        $app['config']->set('sunat-sender.connect_timeout', 10);
        $app['config']->set('sunat-sender.ssl_verify', false);
    }
}
