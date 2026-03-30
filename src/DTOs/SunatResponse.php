<?php

declare(strict_types=1);

namespace RashArt\SunatSender\DTOs;

/**
 * Respuesta normalizada de SUNAT o del proveedor (OSE/PSE).
 *
 * Cubre tres escenarios:
 *   - Envío síncrono aceptado   → success=true,  code=0,   cdrZip poblado
 *   - Envío async (ticket)      → success=true,  code=0,   ticket poblado, cdrZip=null
 *   - Rechazo / error           → success=false, code≠0,   message con descripción
 */
final class SunatResponse
{
    public function __construct(
        public readonly bool    $success,
        public readonly int     $code,
        public readonly string  $message,

        /**
         * Ticket retornado por SUNAT en envíos asíncronos (RC, RA).
         * Null en envíos síncronos.
         */
        public readonly ?string $ticket  = null,

        /**
         * CDR en base64 (ZIP con el archivo XML de respuesta de SUNAT).
         * Null si el proceso es async y aún no hay respuesta.
         */
        public readonly ?string $cdrZip  = null,

        /**
         * Respuesta cruda del proveedor (para debug/logs).
         */
        public readonly array   $raw     = [],
    ) {}

    // ------------------------------------------------------------------ //
    //  Factories
    // ------------------------------------------------------------------ //

    public static function accepted(int $code, string $message, string $cdrZip, array $raw = []): self
    {
        return new self(success: true, code: $code, message: $message, cdrZip: $cdrZip, raw: $raw);
    }

    public static function pending(string $ticket, array $raw = []): self
    {
        return new self(success: true, code: 0, message: 'Pendiente', ticket: $ticket, raw: $raw);
    }

    public static function rejected(int $code, string $message, array $raw = []): self
    {
        return new self(success: false, code: $code, message: $message, raw: $raw);
    }

    // ------------------------------------------------------------------ //
    //  Helpers
    // ------------------------------------------------------------------ //

    /** El comprobante fue aceptado y tiene CDR. */
    public function isAccepted(): bool
    {
        return $this->success && $this->cdrZip !== null;
    }

    /** SUNAT recibió el documento pero aún procesa (modo async). */
    public function isPending(): bool
    {
        return $this->success && $this->ticket !== null && $this->cdrZip === null;
    }

    /** Hubo un error de negocio o comunicación. */
    public function isRejected(): bool
    {
        return ! $this->success;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'code'    => $this->code,
            'message' => $this->message,
            'ticket'  => $this->ticket,
            'cdr_zip' => $this->cdrZip,
            'raw'     => $this->raw,
        ];
    }
}