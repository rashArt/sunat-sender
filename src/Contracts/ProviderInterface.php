<?php

declare(strict_types=1);

namespace RashArt\SunatSender\Contracts;

use RashArt\SunatSender\DTOs\DocumentData;
use RashArt\SunatSender\DTOs\SunatResponse;

/**
 * Contrato para proveedores de envío SÍNCRONOS a SUNAT.
 *
 * Implementaciones: SunatDirectProvider, OseProvider, PseProvider.
 *
 * Aplica a documentos que SUNAT procesa en tiempo real:
 *   - Facturas (01)
 *   - Boletas (03)
 *   - Notas de crédito (07)
 *   - Notas de débito (08)
 *   - Liquidaciones de compra (09)
 */
interface ProviderInterface
{
    /**
     * Envía un comprobante y retorna la respuesta (CDR) de SUNAT o del proveedor.
     *
     * @param  DocumentData  $document  Datos del documento a enviar (XML firmado + metadatos).
     * @return SunatResponse            Respuesta normalizada con código, descripción y CDR.
     *
     * @throws \RashArt\SunatSender\Exceptions\ProviderException
     * @throws \RashArt\SunatSender\Exceptions\AccountNotFoundException
     */
    public function sendBill(DocumentData $document): SunatResponse;

    /**
     * Consulta el CDR de un comprobante previamente enviado.
     * Útil cuando el primer envío no retornó CDR (timeout, error de red, etc.).
     *
     * @param  string  $ruc          RUC del emisor.
     * @param  string  $documentType Tipo de documento (01, 03, 07, 08…).
     * @param  string  $series       Serie del comprobante (ej: F001).
     * @param  int     $correlative  Número correlativo.
     * @return SunatResponse
     *
     * @throws \RashArt\SunatSender\Exceptions\ProviderException
     */
    public function getStatusCdr(string $ruc, string $documentType, string $series, int $correlative): SunatResponse;

    /**
     * Nombre legible del proveedor (para logs y mensajes de error).
     */
    public function getName(): string;
}