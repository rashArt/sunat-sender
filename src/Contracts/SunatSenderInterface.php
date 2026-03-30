<?php

namespace RashArt\SunatSender\Contracts;

use RashArt\SunatSender\DTOs\SendableDocument;
use RashArt\SunatSender\DTOs\SunatResponse;

interface SunatSenderInterface
{
    /**
     * Envía un documento electrónico a SUNAT.
     */
    public function send(SendableDocument $document): SunatResponse;

    /**
     * Envía un lote de documentos electrónicos a SUNAT.
     *
     * @param  SendableDocument[]  $documents
     */
    public function sendBatch(array $documents): SunatResponse;

    /**
     * Consulta el estado de un ticket de envío.
     */
    public function getStatus(string $ticketNumber): SunatResponse;

    /**
     * Retorna el nombre del proveedor activo.
     */
    public function getProviderName(): string;
}
