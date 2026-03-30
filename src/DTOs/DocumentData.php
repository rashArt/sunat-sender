<?php

declare(strict_types=1);

namespace RashArt\SunatSender\DTOs;

/**
 * DTO central del pipeline de envío.
 *
 * Transporta toda la información necesaria para:
 *   - Generar el XML del comprobante.
 *   - Firmarlo.
 *   - Empaquetarlo en ZIP.
 *   - Enviarlo al proveedor elegido (Directo / OSE / PSE).
 *
 * El campo $xmlContent se puebla durante el pipeline (no al construir).
 * El campo $zipContent se puebla justo antes del envío.
 */
final class DocumentData
{
    /**
     * XML firmado en UTF-8. Poblado por el paso XmlSigner del pipeline.
     */
    public ?string $xmlContent = null;

    /**
     * ZIP con el XML dentro. Poblado por el paso ZipPacker del pipeline.
     */
    public ?string $zipContent = null;

    /**
     * Nombre del archivo ZIP esperado por SUNAT: "{RUC}-{tipo}-{serie}-{correlativo}.zip"
     */
    public ?string $zipFilename = null;

    public function __construct(
        /** Cuenta del emisor (credenciales SOL + certificado). */
        public readonly SunatAccount $account,

        /**
         * Tipo de comprobante:
         *   '01' Factura, '03' Boleta, '07' NC, '08' ND,
         *   '09' Liquidación, 'RC' Resumen diario, 'RA' Baja.
         */
        public readonly string $documentType,

        /** Serie del comprobante (ej: F001, B001, RC-20241201). */
        public readonly string $series,

        /** Número correlativo. */
        public readonly int $correlative,

        /**
         * Datos crudos del documento (factura, boleta, etc.)
         * tal como los entrega el host app.
         * El normalizer se encarga de estandarizarlos.
         *
         * @var array<string, mixed>
         */
        public readonly array $rawData,

        /**
         * Proveedor a usar para este documento específico.
         * Si es null, se usa el proveedor configurado por defecto.
         */
        public readonly ?string $providerKey = null,
    ) {
    }

    /**
     * Nombre de archivo base (sin extensión) según nomenclatura SUNAT.
     * Ej: "20601234567-01-F001-00000001"
     */
    public function baseFilename(): string
    {
        return sprintf(
            '%s-%s-%s-%08d',
            $this->account->ruc,
            $this->documentType,
            $this->series,
            $this->correlative,
        );
    }

    /**
     * Nombre del XML dentro del ZIP.
     */
    public function xmlFilename(): string
    {
        return $this->baseFilename() . '.xml';
    }

    /**
     * Indica si el documento es de envío asíncrono
     * (resumen diario o comunicación de baja).
     */
    public function isAsync(): bool
    {
        return in_array($this->documentType, ['RC', 'RA'], true);
    }
}