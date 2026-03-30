<?php

namespace RashArt\SunatSender\Providers;

use GuzzleHttp\Exception\GuzzleException;
use RashArt\SunatSender\DTOs\SendableDocument;
use RashArt\SunatSender\DTOs\SunatResponse;
use RashArt\SunatSender\Exceptions\ProviderException;

/**
 * Proveedor para envío directo como OSE (Operador de Servicios Electrónicos).
 *
 * Implementa la comunicación con el servicio web de la SUNAT
 * mediante el protocolo SOAP definido en la especificación técnica.
 */
class OseProvider extends AbstractSunatProvider
{
    public function getName(): string
    {
        return 'ose';
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
                message: $body['descripcion'] ?? 'Documento enviado correctamente',
                cdrContent: $body['archivoCDR'] ?? null,
                raw: $body,
            );
        } catch (GuzzleException $e) {
            throw ProviderException::connectionFailed($this->getName(), $e);
        }
    }

    public function sendBatch(array $documents): SunatResponse
    {
        if (empty($documents)) {
            return SunatResponse::failure(0, 'No se proporcionaron documentos para el lote.');
        }

        $firstDoc = reset($documents);
        $endpoint = $this->getBaseUrl() . '/api/sunat/services/SunatIndirectaService';

        try {
            $response = $this->httpClient->post($endpoint, [
                'json' => [
                    'fileName'    => $firstDoc->getFileName(),
                    'contentFile' => $this->buildZipContent($firstDoc),
                ],
                'auth' => [
                    $this->config['ruc'] . $this->config['username'],
                    $this->config['password'],
                ],
            ]);

            $body = json_decode((string) $response->getBody(), true);

            return SunatResponse::success(
                code: $body['codigoRespuesta'] ?? 0,
                message: $body['descripcion'] ?? 'Lote enviado correctamente',
                ticketNumber: $body['ticket'] ?? null,
                raw: $body,
            );
        } catch (GuzzleException $e) {
            throw ProviderException::connectionFailed($this->getName(), $e);
        }
    }

    public function getStatus(string $ticketNumber): SunatResponse
    {
        $endpoint = $this->getBaseUrl() . '/api/sunat/services/SunatIndirectaService/getStatus';

        try {
            $response = $this->httpClient->post($endpoint, [
                'json' => ['ticket' => $ticketNumber],
                'auth' => [
                    $this->config['ruc'] . $this->config['username'],
                    $this->config['password'],
                ],
            ]);

            $body = json_decode((string) $response->getBody(), true);
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
                message: $body['descripcion'] ?? 'Estado consultado',
                ticketNumber: $ticketNumber,
                cdrContent: $body['archivoCDR'] ?? null,
                raw: $body,
            );
        } catch (GuzzleException $e) {
            throw ProviderException::statusQueryFailed($ticketNumber, $e);
        }
    }

    protected function getBaseUrl(): string
    {
        return rtrim($this->config['ose_url'] ?? $this->config['base_url'] ?? '', '/');
    }

    protected function requiredConfigKeys(): array
    {
        return ['ruc', 'username', 'password'];
    }

    protected function resolveEndpoint(string $documentType): string
    {
        return $this->getBaseUrl() . '/api/sunat/services/SunatIndirectaService';
    }
}
