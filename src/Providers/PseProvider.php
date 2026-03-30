<?php

declare(strict_types=1);

namespace RashArt\SunatSender\Providers;

use GuzzleHttp\Exception\GuzzleException;
use RashArt\SunatSender\DTOs\DocumentData;
use RashArt\SunatSender\DTOs\SunatResponse;
use RashArt\SunatSender\Exceptions\ProviderException;

/**
 * Envío a través de un PSE (Proveedor de Servicios Electrónicos).
 *
 * El PSE actúa como intermediario que gestiona la comunicación con SUNAT
 * por cuenta del emisor. A diferencia del OSE, el PSE no valida el documento
 * como SUNAT; simplemente lo reenvía y retorna la respuesta.
 *
 * La diferencia técnica con OSE en esta implementación es la estructura
 * del payload y los headers esperados por el API del PSE.
 *
 * Requiere en config:
 *   provider_url → URL base del API REST del PSE
 *   api_token    → Bearer token
 *   api_key      → API key (común en PSEs como Greenter, OpenInvoice, etc.)
 */
class PseProvider extends AbstractSunatProvider
{
    public function getName(): string
    {
        return 'pse';
    }

    // ------------------------------------------------------------------ //
    //  ProviderInterface
    // ------------------------------------------------------------------ //

    public function sendBill(DocumentData $document): SunatResponse
    {
        $endpoint = $this->getBaseUrl() . '/api/send';
        $zipBin   = $this->buildZip($document);

        try {
            $response = $this->httpClient->post($endpoint, [
                'headers' => $this->buildHeaders(),
                'json'    => [
                    'ruc'          => $document->ruc,
                    'fileName'     => $document->getZipFileName(),
                    'contentFile'  => base64_encode($zipBin),
                    'documentType' => $document->type,
                    'metadata'     => $document->metadata,
                ],
            ]);

            $body = json_decode((string) $response->getBody(), true);

            if (! ($body['success'] ?? false)) {
                return SunatResponse::rejected(
                    (int) ($body['code'] ?? -1),
                    $body['message'] ?? 'PSE rechazó el documento',
                    $body
                );
            }

            return SunatResponse::accepted(
                (int) ($body['code'] ?? 0),
                $body['message'] ?? 'Aceptado',
                $body['cdr'] ?? '',
                $body
            );
        } catch (GuzzleException $e) {
            throw new ProviderException("PSE HTTP error en sendBill: {$e->getMessage()}", 0, $e);
        }
    }

    public function getStatusCdr(string $ruc, string $documentType, string $series, int $correlative): SunatResponse
    {
        $endpoint = $this->getBaseUrl() . '/api/status';

        try {
            $response = $this->httpClient->get($endpoint, [
                'headers' => $this->buildHeaders(),
                'query'   => [
                    'ruc'           => $ruc,
                    'document_type' => $documentType,
                    'series'        => $series,
                    'correlative'   => $correlative,
                ],
            ]);

            $body = json_decode((string) $response->getBody(), true);

            if (! ($body['success'] ?? false)) {
                return SunatResponse::rejected(
                    (int) ($body['code'] ?? -1),
                    $body['message'] ?? 'No encontrado en PSE',
                    $body
                );
            }

            return SunatResponse::accepted(
                (int) ($body['code'] ?? 0),
                $body['message'] ?? 'CDR encontrado',
                $body['cdr'] ?? '',
                $body
            );
        } catch (GuzzleException $e) {
            throw new ProviderException("PSE HTTP error en getStatusCdr: {$e->getMessage()}", 0, $e);
        }
    }

    // ------------------------------------------------------------------ //
    //  Internals
    // ------------------------------------------------------------------ //

    private function buildHeaders(): array
    {
        $headers = [
            'Authorization' => 'Bearer ' . ($this->config['api_token'] ?? ''),
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ];

        if (! empty($this->config['api_key'])) {
            $headers['X-Api-Key'] = $this->config['api_key'];
        }

        return $headers;
    }
}