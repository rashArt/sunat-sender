<?php

declare(strict_types=1);

namespace RashArt\SunatSender\DTOs;

/**
 * Datos del documento electrónico listo para enviarse a SUNAT o proveedor.
 *
 * Contiene el XML firmado + metadatos mínimos para construir el nombre de archivo
 * y determinar el endpoint correcto.
 */
final readonly class DocumentData
{
    public function __construct(
        /**
         * RUC del emisor (11 dígitos).
         */
        public string $ruc,

        /**
         * Tipo de documento UBL:
         *   01 = Factura
         *   03 = Boleta
         *   07 = Nota de crédito
         *   08 = Nota de débito
         *   09 = Liquidación de compra
         *   RC = Resumen diario de boletas (async)
         *   RA = Comunicación de baja (async)
         */
        public string $type,

        /**
         * Serie del comprobante (ej: F001, B001, RC-20240101).
         */
        public string $series,

        /**
         * Número correlativo (ej: 1, 100, 9999).
         * Para documentos async (RC/RA) puede ser 1.
         */
        public int $correlative,

        /**
         * Contenido XML firmado (UTF-8, sin BOM).
         */
        public string $xmlSigned,

        /**
         * Credenciales SOL del emisor.
         */
        public SunatAccount $account,

        /**
         * Metadatos opcionales para el proveedor (OSE/PSE pueden necesitar campos extra).
         */
        public array $metadata = [],
    ) {}

    /**
     * Nombre del archivo XML según nomenclatura SUNAT:
     * {RUC}-{TIPO}-{SERIE}-{CORRELATIVO}.xml
     */
    public function getFileName(): string
    {
        return sprintf('%s-%s-%s-%d.xml', $this->ruc, $this->type, $this->series, $this->correlative);
    }

    /**
     * Nombre del ZIP que SUNAT espera recibir (misma base, extensión .zip).
     */
    public function getZipFileName(): string
    {
        return sprintf('%s-%s-%s-%d.zip', $this->ruc, $this->type, $this->series, $this->correlative);
    }

    /**
     * Indica si este documento se procesa de forma asíncrona (ticket).
     * SUNAT procesa RC y RA en diferido.
     */
    public function isAsync(): bool
    {
        return in_array(strtoupper($this->type), ['RC', 'RA'], true);
    }

    /**
     * Construye desde array. Útil para instanciar desde modelos del host app.
     *
     * @param array{
     *     ruc: string,
     *     type: string,
     *     series: string,
     *     correlative: int,
     *     xml_signed: string,
     *     account: array,
     *     metadata?: array,
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            ruc:         $data['ruc'],
            type:        $data['type'],
            series:      $data['series'],
            correlative: (int) $data['correlative'],
            xmlSigned:   $data['xml_signed'],
            account:     SunatAccount::fromArray($data['account']),
            metadata:    $data['metadata'] ?? [],
        );
    }
}