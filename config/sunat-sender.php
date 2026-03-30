<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Proveedor Activo
    |--------------------------------------------------------------------------
    |
    | Define el proveedor que se utilizará para el envío de documentos.
    | Puede ser 'ose' para envío directo como OSE, 'pse' para envío a través
    | de un Proveedor de Servicios Electrónicos, o el nombre de un proveedor
    | personalizado registrado en el array 'providers'.
    |
    */

    'provider' => env('SUNAT_PROVIDER', 'ose'),

    /*
    |--------------------------------------------------------------------------
    | Credenciales del Emisor
    |--------------------------------------------------------------------------
    |
    | RUC y credenciales del emisor de los documentos electrónicos.
    |
    */

    'ruc'      => env('SUNAT_RUC'),
    'username' => env('SUNAT_USERNAME', 'MODDATOS'),
    'password' => env('SUNAT_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | URLs de los Servicios
    |--------------------------------------------------------------------------
    |
    | URLs base para cada proveedor. En el caso del OSE, se utilizarán los
    | endpoints oficiales de SUNAT según el ambiente configurado.
    |
    */

    'ose_url' => env('SUNAT_OSE_URL', 'https://e-beta.sunat.gob.pe'),
    'pse_url' => env('SUNAT_PSE_URL'),

    /*
    |--------------------------------------------------------------------------
    | Credenciales PSE
    |--------------------------------------------------------------------------
    |
    | Token y clave de API para autenticarse con el PSE seleccionado.
    | Requeridos únicamente cuando provider = 'pse'.
    |
    */

    'api_token' => env('SUNAT_PSE_TOKEN'),
    'api_key'   => env('SUNAT_PSE_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Configuración de Red
    |--------------------------------------------------------------------------
    |
    | Tiempos de espera para las conexiones HTTP con los proveedores.
    |
    */

    'timeout'         => env('SUNAT_TIMEOUT', 30),
    'connect_timeout' => env('SUNAT_CONNECT_TIMEOUT', 10),
    'ssl_verify'      => env('SUNAT_SSL_VERIFY', true),

    /*
    |--------------------------------------------------------------------------
    | Proveedores Personalizados
    |--------------------------------------------------------------------------
    |
    | Registra clases proveedoras personalizadas. La clave del array será el
    | nombre con el que se identifica al proveedor en la opción 'provider'.
    |
    | Ejemplo:
    |   'providers' => [
    |       'mi_pse' => \App\Services\MiPseProvider::class,
    |   ],
    |
    */

    'providers' => [],

];
