<?php

declare(strict_types=1);

namespace RashArt\SunatSender\Contracts;

use RashArt\SunatSender\DTOs\DocumentData;
use RashArt\SunatSender\DTOs\SunatResponse;

/**
 * Contrato para proveedores de envío ASÍNCRONOS a SUNAT.
 *
 * Aplica a documentos que SUNAT NO procesa en tiempo real:
 *   - Resúmenes diarios de boletas (RC-…)
 *   - Comunicaciones de baja (RA-…)
 *   - Percepciones / Retenciones (P-…)
 *
 * Flujo:
 *   1. sendSummary()  → SUNAT retorna un ticket.
 *   2. getStatus()    → Se consulta el ticket hasta obtener el CDR.
 */
interface AsyncProviderInterface extends ProviderInterface
{
    /**
     * Envía un resumen o comunicación de baja de forma asíncrona.
     * SUNAT retorna un ticket (no un CDR inmediato).
     *
     * @param  DocumentData  $document
     * @return SunatResponse  Con $response->ticket poblado.
     *
     * @throws \RashArt\SunatSender\Exceptions\ProviderException
     */
    public function sendSummary(DocumentData $document): SunatResponse;

    /**
     * Consulta el estado de un ticket previamente obtenido con sendSummary().
     *
     * @param  string  $ticket  Ticket retornado por SUNAT.
     * @param  string  $ruc     RUC del emisor (necesario para autenticación).
     * @return SunatResponse    Con $response->cdrZip si ya está procesado.
     *
     * @throws \RashArt\SunatSender\Exceptions\ProviderException
     */
    public function getStatus(string $ticket, string $ruc): SunatResponse;
}