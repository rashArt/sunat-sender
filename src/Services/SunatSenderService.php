<?php

declare(strict_types=1);

namespace RashArt\SunatSender\Services;

use RashArt\SunatSender\Contracts\AsyncProviderInterface;
use RashArt\SunatSender\Contracts\ProviderInterface;
use RashArt\SunatSender\DTOs\DocumentData;
use RashArt\SunatSender\DTOs\SunatResponse;
use RashArt\SunatSender\Exceptions\ProviderException;

/**
 * Servicio principal del paquete.
 *
 * Orquesta el envío delegando al provider activo.
 * Es la única clase que el host app necesita consumir directamente
 * (a través de inyección de dependencias o la Facade SunatSender).
 *
 * Flujo:
 *   - Documentos síncronos (01, 03, 07, 08, 09) → sendBill()
 *   - Documentos asíncronos (RC, RA)             → sendSummary() si el provider lo soporta
 *   - Consulta CDR por número                    → getStatusCdr()
 *   - Consulta ticket async                       → getTicketStatus()
 */
class SunatSenderService
{
    public function __construct(
        protected ProviderInterface $provider,
        protected array $config = [],
    ) {}

    // ------------------------------------------------------------------ //
    //  API pública
    // ------------------------------------------------------------------ //

    /**
     * Envía un comprobante.
     *
     * Si el documento es asíncrono (RC/RA) y el provider implementa
     * AsyncProviderInterface, usa sendSummary(); de lo contrario usa sendBill().
     */
    public function send(DocumentData $document): SunatResponse
    {
        if ($document->isAsync() && $this->provider instanceof AsyncProviderInterface) {
            return $this->provider->sendSummary($document);
        }

        return $this->provider->sendBill($document);
    }

    /**
     * Consulta el CDR de un comprobante por sus datos de identificación.
     */
    public function getStatusCdr(string $ruc, string $documentType, string $series, int $correlative): SunatResponse
    {
        return $this->provider->getStatusCdr($ruc, $documentType, $series, $correlative);
    }

    /**
     * Consulta el estado de un ticket async.
     * Solo disponible si el provider activo implementa AsyncProviderInterface.
     *
     * @throws ProviderException si el provider no soporta async.
     */
    public function getTicketStatus(string $ticket, string $ruc = ''): SunatResponse
    {
        if (! $this->provider instanceof AsyncProviderInterface) {
            throw new ProviderException(
                "El provider '{$this->provider->getName()}' no soporta consulta de tickets async."
            );
        }

        return $this->provider->getStatus($ticket, $ruc);
    }

    /**
     * Retorna el nombre del provider activo.
     */
    public function getProviderName(): string
    {
        return $this->provider->getName();
    }

    /**
     * Retorna una copia del servicio con un provider diferente (inmutable).
     */
    public function withProvider(ProviderInterface $provider): static
    {
        $clone = clone $this;
        $clone->provider = $provider;
        return $clone;
    }
}