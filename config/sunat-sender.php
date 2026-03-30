<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Proveedor de envío activo
    |--------------------------------------------------------------------------
    | Valores nativos: 'sunat' | 'ose' | 'pse'
    | Para usar un proveedor personalizado, registra su clase en custom_providers
    | y pon su clave aquí.
    */
    'provider' => env('SUNAT_PROVIDER', 'sunat'),

    /*
    |--------------------------------------------------------------------------
    | Credenciales SOL del emisor
    |--------------------------------------------------------------------------
    | Estas credenciales se usan cuando el provider es 'sunat' (SOAP directo).
    | Para OSE/PSE las credenciales son del API externo (api_token / api_key).
    */
    'account' => [
        'ruc'          => env('SUNAT_RUC', ''),
        'sol_user'     => env('SUNAT_SOL_USER', ''),
        'sol_password' => env('SUNAT_SOL_PASSWORD', ''),
        'certificate'  => env('SUNAT_CERTIFICATE', ''),   // Contenido PEM
        'business_name'=> env('SUNAT_BUSINESS_NAME', ''),
        'trade_name'   => env('SUNAT_TRADE_NAME', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de conexión al proveedor externo (OSE / PSE)
    |--------------------------------------------------------------------------
    */
    'provider_url' => env('SUNAT_PROVIDER_URL', ''),
    'api_token'    => env('SUNAT_API_TOKEN', ''),
    'api_key'      => env('SUNAT_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Entorno
    |--------------------------------------------------------------------------
    | sandbox=true apunta a los endpoints Beta de SUNAT para pruebas.
    */
    'sandbox' => env('SUNAT_SANDBOX', false),

    /*
    |--------------------------------------------------------------------------
    | HTTP / SOAP timeouts (segundos)
    |--------------------------------------------------------------------------
    */
    'timeout'         => env('SUNAT_TIMEOUT', 30),
    'connect_timeout' => env('SUNAT_CONNECT_TIMEOUT', 10),
    'verify_ssl'      => env('SUNAT_VERIFY_SSL', true),

    /*
    |--------------------------------------------------------------------------
    | Proveedores personalizados
    |--------------------------------------------------------------------------
    | Registra implementaciones propias de ProviderInterface.
    | Ejemplo:
    |   'custom_providers' => [
    |       'mi_ose' => \App\Sunat\MiOseProvider::class,
    |   ],
    */
    'custom_providers' => [],

];