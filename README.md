# sunat-sender

[![Latest Version on Packagist](https://img.shields.io/packagist/v/rashArt/sunat-sender.svg?style=flat-square)](https://packagist.org/packages/rashArt/sunat-sender)
[![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

Paquete Laravel que conecta una aplicación con SUNAT utilizando envío directo como OSE o PSE, con posibilidad de extender proveedores.

---

## Tabla de Contenidos

- [Requisitos](#requisitos)
- [Instalación](#instalación)
- [Configuración](#configuración)
- [Uso Básico](#uso-básico)
- [Proveedores Disponibles](#proveedores-disponibles)
  - [OSE – Operador de Servicios Electrónicos](#ose--operador-de-servicios-electrónicos)
  - [PSE – Proveedor de Servicios Electrónicos](#pse--proveedor-de-servicios-electrónicos)
- [Proveedores Personalizados](#proveedores-personalizados)
- [DTOs](#dtos)
- [Excepciones](#excepciones)
- [Testing](#testing)

---

## Requisitos

| Dependencia | Versión mínima |
|---|---|
| PHP | 8.1 |
| Laravel | 10.x / 11.x |
| Guzzle HTTP | 7.x |

---

## Instalación

```bash
composer require rashArt/sunat-sender
```

Publica el archivo de configuración:

```bash
php artisan vendor:publish --tag=sunat-sender-config
```

---

## Configuración

Agrega las siguientes variables a tu archivo `.env`:

```env
# Proveedor activo: 'ose' o 'pse'
SUNAT_PROVIDER=ose

# Datos del emisor
SUNAT_RUC=20000000001
SUNAT_USERNAME=MODDATOS
SUNAT_PASSWORD=moddatos

# URL del servicio OSE (beta por defecto)
SUNAT_OSE_URL=https://e-beta.sunat.gob.pe

# Para PSE: URL y credenciales del proveedor externo
SUNAT_PSE_URL=https://api.tu-pse.com
SUNAT_PSE_TOKEN=tu-token-aqui
SUNAT_PSE_API_KEY=tu-api-key-aqui
```

El archivo de configuración publicado en `config/sunat-sender.php` expone todas las opciones disponibles con documentación en línea.

---

## Uso Básico

### Con inyección de dependencias

```php
use RashArt\SunatSender\Contracts\SunatSenderInterface;
use RashArt\SunatSender\DTOs\SendableDocument;

class FacturaController extends Controller
{
    public function __construct(
        private readonly SunatSenderInterface $sunat
    ) {}

    public function enviar(Request $request)
    {
        $document = new SendableDocument(
            type:       '01',         // 01=Factura, 03=Boleta, 07=NC, 08=ND
            series:     'F001',
            number:     '00000001',
            rucEmisor:  '20000000001',
            xmlContent: $request->xml_content,
        );

        $response = $this->sunat->send($document);

        if ($response->isAccepted()) {
            return response()->json(['cdr' => $response->cdrContent]);
        }

        return response()->json(['error' => $response->message], 422);
    }
}
```

### Con Facade

```php
use RashArt\SunatSender\Facades\SunatSender;
use RashArt\SunatSender\DTOs\SendableDocument;

$document = new SendableDocument(
    type:       '01',
    series:     'F001',
    number:     '00000001',
    rucEmisor:  '20000000001',
    xmlContent: $xmlString,
);

$response = SunatSender::send($document);
```

### Envío de lote (comunicación indirecta)

```php
$response = SunatSender::sendBatch([$document1, $document2]);

if ($response->isPending()) {
    // El ticket quedó en proceso, consultar estado después
    $status = SunatSender::getStatus($response->ticketNumber);
}
```

---

## Proveedores Disponibles

### OSE – Operador de Servicios Electrónicos

Envío directo a SUNAT mediante los web services oficiales.

```env
SUNAT_PROVIDER=ose
SUNAT_OSE_URL=https://e-beta.sunat.gob.pe    # beta
# SUNAT_OSE_URL=https://e-factura.sunat.gob.pe  # producción
```

### PSE – Proveedor de Servicios Electrónicos

Envío a través de un proveedor externo (intermediario).

```env
SUNAT_PROVIDER=pse
SUNAT_PSE_URL=https://api.mi-pse.com
SUNAT_PSE_TOKEN=eyJhbGci...
```

---

## Proveedores Personalizados

Puedes registrar tu propio proveedor implementando `ProviderInterface`:

```php
use RashArt\SunatSender\Contracts\ProviderInterface;
use RashArt\SunatSender\DTOs\SendableDocument;
use RashArt\SunatSender\DTOs\SunatResponse;

class MiProveedorPersonalizado implements ProviderInterface
{
    public function __construct(private array $config) {}

    public function send(SendableDocument $document): SunatResponse { /* ... */ }
    public function sendBatch(array $documents): SunatResponse { /* ... */ }
    public function getStatus(string $ticketNumber): SunatResponse { /* ... */ }
    public function getName(): string { return 'mi_proveedor'; }
    public function healthCheck(): bool { return true; }
}
```

Regístralo en `config/sunat-sender.php`:

```php
'provider'  => 'mi_proveedor',
'providers' => [
    'mi_proveedor' => \App\Services\MiProveedorPersonalizado::class,
],
```

---

## DTOs

### `SendableDocument`

| Propiedad | Tipo | Descripción |
|---|---|---|
| `type` | `string` | Tipo de documento (`01`, `03`, `07`, `08`, `09`, `20`, `40`) |
| `series` | `string` | Serie del documento (ej. `F001`) |
| `number` | `string` | Número correlativo (ej. `00000001`) |
| `rucEmisor` | `string` | RUC del emisor |
| `xmlContent` | `string` | Contenido XML del documento firmado |
| `metadata` | `array` | Datos adicionales opcionales |

### `SunatResponse`

| Propiedad | Tipo | Descripción |
|---|---|---|
| `success` | `bool` | Indica si la operación fue exitosa |
| `code` | `int` | Código de respuesta SUNAT |
| `message` | `string` | Descripción de la respuesta |
| `ticketNumber` | `string\|null` | Número de ticket (envío asíncrono) |
| `cdrContent` | `string\|null` | Contenido CDR en base64 |
| `raw` | `array` | Respuesta original del proveedor |

Métodos útiles:
- `isAccepted()` — `true` cuando `success = true` y `code = 0`
- `isPending()` — `true` cuando hay ticket pero aún no llegó el CDR
- `toArray()` — Serializa la respuesta a array

---

## Excepciones

| Excepción | Cuándo se lanza |
|---|---|
| `SunatSenderException::providerNotFound()` | El proveedor solicitado no existe |
| `SunatSenderException::configurationMissing()` | Falta una clave requerida en la config |
| `SunatSenderException::invalidDocument()` | El documento tiene datos inválidos |
| `ProviderException::connectionFailed()` | Error de red al conectar con el proveedor |
| `ProviderException::sendFailed()` | El proveedor rechazó el documento |
| `ProviderException::statusQueryFailed()` | Error al consultar el estado de un ticket |

---

## Testing

### Instalar dependencias de desarrollo

```bash
composer install
```

### Ejecutar pruebas

```bash
vendor/bin/phpunit
```

### Mockear el servicio en tus tests

```php
use RashArt\SunatSender\Contracts\SunatSenderInterface;
use RashArt\SunatSender\DTOs\SunatResponse;

$this->mock(SunatSenderInterface::class, function ($mock) {
    $mock->shouldReceive('send')
         ->once()
         ->andReturn(SunatResponse::success(0, 'Aceptado', cdrContent: 'base64cdr=='));
});
```

---

## Licencia

MIT — ver [LICENSE](LICENSE).
