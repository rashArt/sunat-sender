<?php

namespace RashArt\SunatSender\Contracts;

use RashArt\SunatSender\DTOs\SendableDocument;
use RashArt\SunatSender\DTOs\SunatResponse;

interface ProviderInterface
{
    /**
     * Envía un documento al proveedor configurado.
     */
    public function send(SendableDocument $document): SunatResponse;

    /**
     * Envía un lote de documentos al proveedor configurado.
     *
     * @param  SendableDocument[]  $documents
     */
    public function sendBatch(array $documents): SunatResponse;

    /**
     * Consulta el estado de un ticket de envío en el proveedor.
     */
    public function getStatus(string $ticketNumber): SunatResponse;

    /**
     * Retorna el nombre del proveedor.
     */
    public function getName(): string;

    /**
     * Verifica que la conexión con el proveedor esté disponible.
     */
    public function healthCheck(): bool;
}
