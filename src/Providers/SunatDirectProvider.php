<?php

namespace RashArt\SunatSender\Providers;

use GuzzleHttp\Exception\GuzzleException;
use RashArt\SunatSender\DTOs\SendableDocument;
use RashArt\SunatSender\DTOs\SunatResponse;
use RashArt\SunatSender\Exceptions\ProviderException;

/**
 * Proveedor para envío DIRECTO a SUNAT.
 *
 * El emisor se conecta directamente a los web services oficiales de SUNAT
 * usando sus propias credenciales SOL (RUC + usuario + clave).
 * No interviene ningún intermediario externo.
 *
 * Endpoints oficiales:
 *   - Beta:        https://e-beta.sunat.gob.pe
 *   - Producción:  https://e-factura.sunat.gob.pe
 */
class SunatDirectProvider extends AbstractSunatProvider
{
    public function getName(): string
    {
        return 'sunat';
    }

    public function send(SendableDocument $document): SunatResponse
    {
        $endpoint = $this->resolveEndpoint($document->type);

        try {
            $response = $this->httpClient->post($endpoint, [
                'json' => [
                    'fileName'    => $document->getFileName(),
                    'contentFile' => $this->buildZipContent($document),
                ],
                'auth' => [
                    $this->config['ruc'] . $this->config['username'],
                    $this->config['password'],
                ],
            ]);

            $body = json_decode((string) $response->getBody(), true);

            return SunatResponse::success(
                code: $body['codigoRespuesta'] ?? 0,
                message: $body['descripcion'] ?? 'Documento aceptado',
                cdrContent: $body['arcCdr'] ?? null,
                raw: $body,
            );
        } catch (GuzzleException $e) {
            throw ProviderException::connectionFailed($this->getName(), $e->getMessage());
        }
    }

    public function sendBatch(array $documents): SunatResponse
    {
        // SUNAT directo no expone un endpoint de lote nativo;
        // se delega al envío individual en serie.
        $last = null;

        foreach ($documents as $document) {
            $last = $this->send($document);
        }

        return $last ?? SunatResponse::success(code: 0, message: 'Lote vacío');
    }

    public function getStatus(string $ticketNumber): SunatResponse
    {
        $endpoint = $this->getBaseUrl()
            . '/ol-ti-itconsults/olConsultaItService';

        try {
            $response = $this->httpClient->post($endpoint, [
                'json' => ['ticket' => $ticketNumber],
                'auth' => [
                    $this->config['ruc'] . $this->config['username'],
                    $this->config['password'],
                ],
            ]);

            $body       = json_decode((string) $response->getBody(), true);
            $statusCode = $body['codigoRespuesta'] ?? -1;

            if ($statusCode === 98) {
                return SunatResponse::success(
                    code: 98,
                    message: 'Ticket en proceso',
                    ticketNumber: $ticketNumber,
                    raw: $body,
                );
            }

            return SunatResponse::success(
                code: $statusCode,
                message: $body['descripcion'] ?? 'Consulta OK',
                cdrContent: $body['arcCdr'] ?? null,
                ticketNumber: $ticketNumber,
                raw: $body,
            );
        } catch (GuzzleException $e) {
            throw ProviderException::connectionFailed($this->getName(), $e->getMessage());
        }
    }

    public function healthCheck(): bool
    {
        try {
            $response = $this->httpClient->get($this->getBaseUrl());
            return $response->getStatusCode() < 500;
        } catch (GuzzleException) {
            return false;
        }
    }

    // -----------------------------------------------------------------------
    // Internos
    // -----------------------------------------------------------------------

    protected function requiredConfigKeys(): array
    {
        return ['ruc', 'username', 'password', 'sunat_url'];
    }

    protected function getBaseUrl(): string
    {
        return rtrim($this->config['sunat_url'], '/');
    }

    protected function resolveEndpoint(string $documentType): string
    {
        $base = $this->getBaseUrl();

        return match ($documentType) {
            '20' => $base . '/ol-ti-itcpe/oltitcpeService',     // Retenci��n
            '40' => $base . '/ol-ti-itpe/oltitpeService',       // Percepción
            default => $base . '/ol-ti-itcpgem/OLReceiveFile',  // Facturas, boletas, NC, ND
        };
    }

    protected function buildZipContent(SendableDocument $document): string
    {
        // Genera el ZIP en memoria y lo retorna como base64
        $zipPath  = sys_get_temp_dir() . '/' . $document->getFileName() . '.zip';
        $xmlPath  = sys_get_temp_dir() . '/' . $document->getFileName() . '.xml';

        file_put_contents($xmlPath, $document->xmlContent);

        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFile($xmlPath, $document->getFileName() . '.xml');
        $zip->close();

        $content = base64_encode(file_get_contents($zipPath));

        @unlink($xmlPath);
        @unlink($zipPath);

        return $content;
    }
}