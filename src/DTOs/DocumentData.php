<?php

declare(strict_types=1);

namespace RashArt\SunatSender\DTOs;

/**
 * DTO central del pipeline de envío.
 *
 * Transporta toda la información necesaria para generar, firmar,
 * empaquetar y enviar un documento electrónico a SUNAT (o intermediario).
 *
 * Inmutable por diseño (readonly).
 */
final readonly class DocumentData
{
    public function __construct(
        /** RUC del emisor. */
        public string $ruc,

        /**
         * Tipo de documento SUNAT.
         * Ej: '01' (Factura), '03' (Boleta), '07' (NC), '08' (ND),
         *     '09' (Guía), '20' (Retención), '40' (Percepción).
         */
        public string $documentType,

        /** Serie del documento. Ej: F001, B001. */
        public string $serie,

        /** Correlativo del documento. Ej: 1, 1234. */
        public int $correlativo,

        /** Contenido XML del documento (sin firmar o firmado). */
        public string $xml,

        /** Credenciales de la cuenta SUNAT asociada al emisor. */
        public SunatAccountData $account,

        /**
         * Contenido del ZIP en base64.
         * Se genera en el pipeline (pipe BuildZip), no se pasa al construir.
         */
        public string $zipBase64 = '',

        /**
         * Número de ticket devuelto por SUNAT para documentos asíncronos
         * (Boletas en lote, resúmenes, bajas, etc.).
         */
        public string $ticketNumber = '',
    ) {}

    /**
     * Nombre de archivo según nomenclatura SUNAT:
     * RUC-TipoDoc-Serie-Correlativo.xml
     * Ej: 20123456789-01-F001-1.xml
     */
    public function fileName(): string
    {
        return implode('-', [
            $this->ruc,
            $this->documentType,
            $this->serie,
            $this->correlativo,
        ]) . '.xml';
    }

    /**
     * Nombre del ZIP según nomenclatura SUNAT.
     * Ej: 20123456789-01-F001-1.zip
     */
    public function zipFileName(): string
    {
        return implode('-', [
            $this->ruc,
            $this->documentType,
            $this->serie,
            $this->correlativo,
        ]) . '.zip';
    }

    /**
     * Clona el DTO añadiendo el ZIP generado por el pipeline.
     */
    public function withZip(string $zipBase64): self
    {
        return new self(
            ruc:          $this->ruc,
            documentType: $this->documentType,
            serie:        $this->serie,
            correlativo:  $this->correlativo,
            xml:          $this->xml,
            account:      $this->account,
            zipBase64:    $zipBase64,
            ticketNumber: $this->ticketNumber,
        );
    }

    /**
     * Clona el DTO añadiendo el ticket de respuesta asíncrona.
     */
    public function withTicket(string $ticketNumber): self
    {
        return new self(
            ruc:          $this->ruc,
            documentType: $this->documentType,
            serie:        $this->serie,
            correlativo:  $this->correlativo,
            xml:          $this->xml,
            account:      $this->account,
            zipBase64:    $this->zipBase64,
            ticketNumber: $ticketNumber,
        );
    }
}