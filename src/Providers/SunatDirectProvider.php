<?php

declare(strict_types=1);

namespace RashArt\SunatSender\Providers;

use RashArt\SunatSender\DTOs\DocumentData;
use RashArt\SunatSender\DTOs\SunatResponse;
use RashArt\SunatSender\Exceptions\ProviderException;

/**
 * Envío DIRECTO a SUNAT vía SOAP (WSDL oficial).
 *
 * Endpoints (producción):
 *   Factura/NC/ND : https://e-factura.sunat.gob.pe/ol-ti-itcpfegem/billService
 *   Boleta/RC/RA  : https://e-factura.sunat.gob.pe/ol-ti-itcpfegem/billService
 *   Piloto        : https://e-beta.sunat.gob.pe/ol-ti-itcpfegem/billService?wsdl
 *
 * Operaciones WSDL:
 *   sendBill    → documentos síncronos (Facturas, NC, ND, Boletas individuales)
 *   sendSummary → documentos asíncronos (RC, RA) — retorna ticket
 *   getStatus   → consulta ticket async
 *   getStatusCdr→ consulta CDR por número de comprobante
 */
class SunatDirectProvider extends AbstractSunatProvider
{
    // URLs de producción por defecto
    private const WSDL_BILL    = 'https://e-factura.sunat.gob.pe/ol-ti-itcpfegem/billService?wsdl';
    private const WSDL_SUMMARY = 'https://e-factura.sunat.gob.pe/ol-ti-itcpgem/billService?wsdl';
    private const WSDL_BETA    = 'https://e-beta.sunat.gob.pe/ol-ti-itcpfegem/billService?wsdl';

    public function getName(): string
    {
        return 'sunat';
    }

    // ------------------------------------------------------------------ //
    //  ProviderInterface
    // ------------------------------------------------------------------ //

    /**
     * Envía un comprobante síncrono (Factura, Boleta, NC, ND) vía SOAP sendBill.
     */
    public function sendBill(DocumentData $document): SunatResponse
    {
        $zipContent = $this->buildZip($document);
        $zipBase64  = base64_encode($zipContent);
        $fileName   = $document->getZipFileName();

        try {
            $client = $this->buildSoapClient($this->resolveWsdl('bill'));
            $result = $client->sendBill([
                'fileName'    => $fileName,
                'contentFile' => $zipBase64,
            ]);

            return $this->parseBillResponse($result);
        } catch (\SoapFault $e) {
            throw new ProviderException(
                "SUNAT SOAP error en sendBill [{$document->getFileName()}]: {$e->getMessage()}",
                (int) ($e->faultcode ?? 0),
                $e
            );
        }
    }

    /**
     * Consulta el CDR de un comprobante ya enviado por número.
     */
    public function getStatusCdr(string $ruc, string $documentType, string $series, int $correlative): SunatResponse
    {
        try {
            $client = $this->buildSoapClient($this->resolveWsdl('bill'));
            $result = $client->getStatusCdr([
                'rucComprobante'  => $ruc,
                'tipoComprobante' => $documentType,
                'serie'           => $series,
                'numero'          => $correlative,
            ]);

            $statusCode = (int) ($result->statusCdr->statusCode ?? -1);
            $statusMsg  = (string) ($result->statusCdr->statusMessage ?? '');
            $content    = $result->statusCdr->content ?? null;

            if ($content !== null) {
                return SunatResponse::accepted($statusCode, $statusMsg, (string) $content);
            }

            return SunatResponse::rejected($statusCode, $statusMsg);
        } catch (\SoapFault $e) {
            throw new ProviderException(
                "SUNAT SOAP error en getStatusCdr: {$e->getMessage()}",
                (int) ($e->faultcode ?? 0),
                $e
            );
        }
    }

    // ------------------------------------------------------------------ //
    //  Async (RC / RA) — implementación directa, no requiere AsyncProviderInterface
    //  porque SunatDirect también puede enviar resúmenes.
    // ------------------------------------------------------------------ //

    /**
     * Envía un resumen diario o comunicación de baja (async).
     * Retorna SunatResponse con $ticket poblado.
     */
    public function sendSummary(DocumentData $document): SunatResponse
    {
        $zipContent = $this->buildZip($document);
        $zipBase64  = base64_encode($zipContent);
        $fileName   = $document->getZipFileName();

        try {
            $client = $this->buildSoapClient($this->resolveWsdl('summary'));
            $result = $client->sendSummary([
                'fileName'    => $fileName,
                'contentFile' => $zipBase64,
            ]);

            $ticket = (string) ($result->ticket ?? '');

            if ($ticket === '') {
                throw new ProviderException('SUNAT no retornó ticket en sendSummary');
            }

            return SunatResponse::pending($ticket);
        } catch (\SoapFault $e) {
            throw new ProviderException(
                "SUNAT SOAP error en sendSummary [{$document->getFileName()}]: {$e->getMessage()}",
                (int) ($e->faultcode ?? 0),
                $e
            );
        }
    }

    /**
     * Consulta el estado de un ticket async.
     */
    public function getTicketStatus(string $ticket): SunatResponse
    {
        try {
            $client = $this->buildSoapClient($this->resolveWsdl('summary'));
            $result = $client->getStatus(['ticket' => $ticket]);

            $statusCode = (int) ($result->status->statusCode ?? -1);
            $statusMsg  = (string) ($result->status->statusMessage ?? '');
            $content    = $result->status->content ?? null;

            // statusCode 0 = procesado con CDR
            if ($statusCode === 0 && $content !== null) {
                return SunatResponse::accepted($statusCode, $statusMsg, (string) $content);
            }

            // statusCode 98 = en proceso, aún no listo
            if ($statusCode === 98) {
                return SunatResponse::pending($ticket);
            }

            return SunatResponse::rejected($statusCode, $statusMsg);
        } catch (\SoapFault $e) {
            throw new ProviderException(
                "SUNAT SOAP error en getStatus (ticket={$ticket}): {$e->getMessage()}",
                (int) ($e->faultcode ?? 0),
                $e
            );
        }
    }

    // ------------------------------------------------------------------ //
    //  Internals
    // ------------------------------------------------------------------ //

    private function buildSoapClient(string $wsdl): \SoapClient
    {
        // El usuario compuesto ya viene calculado desde SunatAccount
        $account = null;
        // Intentamos extraer la cuenta del config si viene como array
        if (isset($this->config['account']) && is_array($this->config['account'])) {
            $account = \RashArt\SunatSender\DTOs\SunatAccount::fromArray($this->config['account']);
        }

        $login    = $account?->composedUser() ?? ($this->config['ruc'] . ($this->config['sol_user'] ?? ''));
        $password = $account?->solPassword   ?? ($this->config['sol_password'] ?? '');

        return new \SoapClient($wsdl, [
            'login'              => $login,
            'password'           => $password,
            'trace'              => true,
            'exceptions'         => true,
            'cache_wsdl'         => WSDL_CACHE_NONE,
            'connection_timeout' => $this->config['connect_timeout'] ?? 10,
            'stream_context'     => $this->buildStreamContext(),
        ]);
    }

    private function buildStreamContext(): mixed
    {
        $opts = ['ssl' => ['verify_peer' => $this->config['verify_ssl'] ?? true]];

        // Si viene certificado PEM en config, lo añadimos para mTLS
        if (! empty($this->config['account']['certificate'])) {
            $certPath = $this->writeTempCert($this->config['account']['certificate']);
            $opts['ssl']['local_cert'] = $certPath;
        }

        return stream_context_create($opts);
    }

    private function writeTempCert(string $pemContent): string
    {
        $path = tempnam(sys_get_temp_dir(), 'sunat_cert_') . '.pem';
        file_put_contents($path, $pemContent);
        register_shutdown_function(fn () => @unlink($path));
        return $path;
    }

    private function resolveWsdl(string $type): string
    {
        if ($this->config['sandbox'] ?? false) {
            return self::WSDL_BETA;
        }

        return match ($type) {
            'summary' => self::WSDL_SUMMARY,
            default   => self::WSDL_BILL,
        };
    }

    private function parseBillResponse(mixed $result): SunatResponse
    {
        $applicationResponse = $result->applicationResponse ?? null;

        if ($applicationResponse === null) {
            throw new ProviderException('SUNAT no retornó applicationResponse en sendBill');
        }

        // applicationResponse es el CDR en base64 (ZIP)
        $cdrZip = (string) $applicationResponse;

        // Decodificamos para leer el statusCode del CDR (opcional, para el código de respuesta)
        [$code, $message] = $this->extractCdrStatus($cdrZip);

        return SunatResponse::accepted($code, $message, $cdrZip);
    }

    /**
     * Extrae código y descripción del CDR ZIP sin librerías externas.
     * El ZIP contiene un XML con <cbc:ResponseCode> y <cbc:Description>.
     */
    private function extractCdrStatus(string $cdrBase64): array
    {
        try {
            $zip     = base64_decode($cdrBase64);
            $tmpZip  = tempnam(sys_get_temp_dir(), 'cdr_') . '.zip';
            file_put_contents($tmpZip, $zip);

            $za = new \ZipArchive();
            if ($za->open($tmpZip) !== true) {
                return [0, 'CDR recibido'];
            }

            $xml = $za->getFromIndex(0);
            $za->close();
            @unlink($tmpZip);

            if ($xml === false) {
                return [0, 'CDR recibido'];
            }

            $dom = new \DOMDocument();
            $dom->loadXML($xml);
            $xpath = new \DOMXPath($dom);
            $xpath->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');

            $code = (int) ($xpath->evaluate('string(//cbc:ResponseCode)') ?: 0);
            $msg  = (string) ($xpath->evaluate('string(//cbc:Description)') ?: 'CDR recibido');

            return [$code, $msg];
        } catch (\Throwable) {
            return [0, 'CDR recibido'];
        }
    }
}