<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Proveedor Activo
    |--------------------------------------------------------------------------
    |
    | Define la modalidad de envío a SUNAT:
    |
    |   'sunat' → Envío DIRECTO a SUNAT (web services oficiales, sin intermediario)
    |   'ose'   → A través de un OSE (Operador de Servicios Electrónicos autorizado)
    |   'pse'   → A través de un PSE (Proveedor de Servicios Electrónicos comercial)
    |
    | También puedes registrar un proveedor personalizado en el array 'providers'.
    |
    */

    'provider' => env('SUNAT_PROVIDER', 'sunat'),

    /*
    |--------------------------------------------------------------------------
    | Credenciales del Emisor
    |--------------------------------------------------------------------------
    |
    | RUC y credenciales SOL del emisor. Requerido para la modalidad 'sunat'.
    | En OSE/PSE, el RUC también puede ser requerido dependiendo del proveedor.
    |
    */

    'ruc'      => env('SUNAT_RUC'),
    'username' => env('SUNAT_SOL_USERNAME', 'MODDATOS'),
    'password' => env('SUNAT_SOL_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | URLs de los Servicios
    |--------------------------------------------------------------------------
    |
    | sunat_url → URL base para envío DIRECTO a SUNAT
    |   Beta:       https://e-beta.sunat.gob.pe
    |   Producción: https://e-factura.sunat.gob.pe
    |
    | ose_url   → URL base del API del OSE contratado
    | pse_url   → URL base del API del PSE contratado
    |
    */

    'sunat_url' => env('SUNAT_URL', 'https://e-beta.sunat.gob.pe'),
    'ose_url'   => env('SUNAT_OSE_URL'),
    'pse_url'   => env('SUNAT_PSE_URL'),

    /*
    |--------------------------------------------------------------------------
    | Credenciales OSE / PSE
    |--------------------------------------------------------------------------
    |
    | Token Bearer y API key para autenticarse con el OSE o PSE seleccionado.
    | Solo requeridos cuando provider = 'ose' o provider = 'pse'.
    |
    */

    'api_token' => env('SUNAT_API_TOKEN'),
    'api_key'   => env('SUNAT_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Configuración de Red
    |--------------------------------------------------------------------------
    */

    'timeout'         => env('SUNAT_TIMEOUT', 30),
    'connect_timeout' => env('SUNAT_CONNECT_TIMEOUT', 10),
    'ssl_verify'      => env('SUNAT_SSL_VERIFY', true),

    /*
    |--------------------------------------------------------------------------
    | Proveedores Personalizados
    |--------------------------------------------------------------------------
    |
    | Registra clases propias que implementen ProviderInterface.
    |
    | Ejemplo:
    |   'providers' => [
    |       'mi_proveedor' => \App\Services\MiProveedorPersonalizado::class,
    |   ],
    |
    */

    'providers' => [],

];