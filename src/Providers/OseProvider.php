<?php

namespace RashArt\SunatSender\Providers;

use GuzzleHttp\Exception\GuzzleException;
use RashArt\SunatSender\DTOs\SendableDocument;
use RashArt\SunatSender\DTOs\SunatResponse;
use RashArt\SunatSender\Exceptions\ProviderException;

/**
 * Proveedor para envío a través de un OSE
 * (Operador de Servicios Electrónicos autorizado por SUNAT).
 *
 * El OSE actúa como intermediario autorizado por SUNAT. Recibe el
 * XML del emisor, lo valida contra las reglas UBL/SUNAT y reenvía
 * la respuesta (CDR) de vuelta. Ejemplos: Efact, Nubefact, etc.
 *
 * Requiere en config:
 *   - ose_url    → URL base del API del OSE
 *   - api_token  → Bearer token del OSE
 */
class OseProvider extends AbstractSunatProvider
{
    public function getName(): string
    {
        return 'ose';
    }

    public function send(SendableDocument $document): SunatResponse
    {
        $endpoint = $this->getBaseUrl() . '/api/documents/send';

        try {
            $response = $this->httpClient->post($endpoint, [
                'headers' => $this->buildHeaders(),
                'json'    => [
                    'ruc'         => $this->config['ruc'],
                    'fileName'    => $document->getFileName(),
                    'contentFile' => base64_encode($document->xmlContent),
                    'metadata'    => $document->metadata,
                ],
            ]);

            $body = json_decode((string) $response->getBody(), true);

            return SunatResponse::success(
                code: $body['sunatCode'] ?? 0,
                message: $body['sunatDescription'] ?? 'Documento aceptado por OSE',
                cdrContent: $body['cdr'] ?? null,
                raw: $body,
            );
        } catch (GuzzleException $e) {
            throw ProviderException::connectionFailed($this->getName(), $e->getMessage());
        }
    }

    public function sendBatch(array $documents): SunatResponse
    {
        $endpoint = $this->getBaseUrl() . '/api/documents/batch';

        try {
            $response = $this->httpClient->post($endpoint, [
                'headers' => $this->buildHeaders(),
                'json'    => [
                    'ruc'       => $this->config['ruc'],
                    'documents' => array_map(fn ($d) => [
                        'fileName'    => $d->getFileName(),
                        'contentFile' => base64_encode($d->xmlContent),
                    ], $documents),
                ],
            ]);

            $body = json_decode((string) $response->getBody(), true);

            return SunatResponse::success(
                code: 0,
                message: 'Lote enviado al OSE',
                ticketNumber: $body['ticket'] ?? null,
                raw: $body,
            );
        } catch (GuzzleException $e) {
            throw ProviderException::connectionFailed($this->getName(), $e->getMessage());
        }
    }

    public function getStatus(string $ticketNumber): SunatResponse
    {
        $endpoint = $this->getBaseUrl() . '/api/documents/status/' . $ticketNumber;

        try {
            $response = $this->httpClient->get($endpoint, [
                'headers' => $this->buildHeaders(),
            ]);

            $body = json_decode((string) $response->getBody(), true);

            return SunatResponse::success(
                code: $body['sunatCode'] ?? 0,
                message: $body['sunatDescription'] ?? 'Consulta OK',
                cdrContent: $body['cdr'] ?? null,
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
            $response = $this->httpClient->get(
                $this->getBaseUrl() . '/health',
                ['headers' => $this->buildHeaders()]
            );
            return $response->getStatusCode() === 200;
        } catch (GuzzleException) {
            return false;
        }
    }

    // -----------------------------------------------------------------------
    // Internos
    // -----------------------------------------------------------------------

    protected function requiredConfigKeys(): array
    {
        return ['ruc', 'ose_url', 'api_token'];
    }

    protected function getBaseUrl(): string
    {
        return rtrim($this->config['ose_url'], '/');
    }

    protected function buildHeaders(): array
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->config['api_token'],
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ];

        if (!empty($this->config['api_key'])) {
            $headers['X-API-Key'] = $this->config['api_key'];
        }

        return $headers;
    }
}