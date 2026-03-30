<?php

declare(strict_types=1);

namespace RashArt\SunatSender\Providers;

use GuzzleHttp\Client;
use RashArt\SunatSender\Contracts\ProviderInterface;
use RashArt\SunatSender\DTOs\DocumentData;

/**
 * Base para todos los providers.
 *
 * Solo provee:
 *   - Construcción del cliente HTTP (Guzzle) con timeout configurable.
 *   - Helper buildZip(): empaqueta el XML firmado en un ZIP en memoria.
 *   - Helper getBaseUrl(): resuelve la URL del proveedor según config.
 *
 * Cada provider concreto implementa sendBill() y getStatusCdr()
 * de acuerdo a su protocolo (SOAP para SunatDirect, HTTP/JSON para OSE/PSE).
 */
abstract class AbstractSunatProvider implements ProviderInterface
{
    protected Client $httpClient;

    public function __construct(protected array $config = [])
    {
        $this->httpClient = new Client([
            'timeout'         => $config['timeout'] ?? 30,
            'connect_timeout' => $config['connect_timeout'] ?? 10,
            'verify'          => $config['verify_ssl'] ?? true,
        ]);
    }

    // ------------------------------------------------------------------ //
    //  Helpers protegidos para subclases
    // ------------------------------------------------------------------ //

    /**
     * Empaqueta el XML firmado en un ZIP en memoria y retorna el contenido binario.
     * El ZIP lleva el nombre de archivo requerido por SUNAT.
     */
    protected function buildZip(DocumentData $document): string
    {
        $zipPath = tempnam(sys_get_temp_dir(), 'sunat_') . '.zip';

        try {
            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException("No se pudo crear el ZIP temporal en {$zipPath}");
            }

            $zip->addFromString($document->getFileName(), $document->xmlSigned);
            $zip->close();

            return file_get_contents($zipPath);
        } finally {
            if (file_exists($zipPath)) {
                @unlink($zipPath);
            }
        }
    }

    /**
     * Retorna la URL base del proveedor según config.
     * Para OSE/PSE usa 'provider_url'; para SUNAT directo cada provider define la suya.
     */
    protected function getBaseUrl(): string
    {
        return rtrim($this->config['provider_url'] ?? '', '/');
    }
}