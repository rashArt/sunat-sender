<?php

namespace RashArt\SunatSender\Providers;

use GuzzleHttp\Exception\GuzzleException;
use RashArt\SunatSender\DTOs\SendableDocument;
use RashArt\SunatSender\DTOs\SunatResponse;
use RashArt\SunatSender\Exceptions\ProviderException;

/**
 * Proveedor para envío a través de un PSE (Proveedor de Servicios Electrónicos).
 *
 * Implementa la comunicación con un PSE externo que actúa como intermediario
 * entre la aplicación y la SUNAT.
 */
class PseProvider extends AbstractSunatProvider
{
    public function getName(): string
    {
        return 'pse';
    }

    public function send(SendableDocument $document): SunatResponse
    {
        $endpoint = $this->getBaseUrl() . '/api/send';

        try {
            $response = $this->httpClient->post($endpoint, [
                'json' => [
                    'ruc'         => $this->config['ruc'],
                    'fileName'    => $document->getFileName(),
                    'contentFile' => $this->buildZipContent($document),
                    'documentType' => $document->type,
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->config['api_token'],
                    'X-Api-Key'     => $this->config['api_key'] ?? '',
                ],
            ]);

            $body = json_decode((string) $response->getBody(), true);

            if (! ($body['success'] ?? false)) {
                return SunatResponse::failure(
                    code: $body['code'] ?? -1,
                    message: $body['message'] ?? 'Error al enviar documento',
                    raw: $body,
                );
            }

            return SunatResponse::success(
                code: $body['code'] ?? 0,
                message: $body['message'] ?? 'Documento enviado correctamente',
                ticketNumber: $body['ticket'] ?? null,
                cdrContent: $body['cdr'] ?? null,
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

        $endpoint = $this->getBaseUrl() . '/api/send-batch';

        try {
            $items = array_map(fn (SendableDocument $doc) => [
                'fileName'     => $doc->getFileName(),
                'contentFile'  => $this->buildZipContent($doc),
                'documentType' => $doc->type,
            ], $documents);

            $response = $this->httpClient->post($endpoint, [
                'json' => [
                    'ruc'   => $this->config['ruc'],
                    'items' => $items,
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->config['api_token'],
                    'X-Api-Key'     => $this->config['api_key'] ?? '',
                ],
            ]);

            $body = json_decode((string) $response->getBody(), true);

            return SunatResponse::success(
                code: $body['code'] ?? 0,
                message: $body['message'] ?? 'Lote enviado correctamente',
                ticketNumber: $body['ticket'] ?? null,
                raw: $body,
            );
        } catch (GuzzleException $e) {
            throw ProviderException::connectionFailed($this->getName(), $e);
        }
    }

    public function getStatus(string $ticketNumber): SunatResponse
    {
        $endpoint = $this->getBaseUrl() . '/api/status/' . $ticketNumber;

        try {
            $response = $this->httpClient->get($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->config['api_token'],
                    'X-Api-Key'     => $this->config['api_key'] ?? '',
                ],
            ]);

            $body = json_decode((string) $response->getBody(), true);

            return SunatResponse::success(
                code: $body['code'] ?? 0,
                message: $body['message'] ?? 'Estado consultado',
                ticketNumber: $ticketNumber,
                cdrContent: $body['cdr'] ?? null,
                raw: $body,
            );
        } catch (GuzzleException $e) {
            throw ProviderException::statusQueryFailed($ticketNumber, $e);
        }
    }

    protected function getBaseUrl(): string
    {
        return rtrim($this->config['pse_url'] ?? $this->config['base_url'] ?? '', '/');
    }

    protected function requiredConfigKeys(): array
    {
        return ['ruc', 'api_token'];
    }
}
