<?php

namespace RashArt\SunatSender\Exceptions;

use RuntimeException;
use Throwable;

class ProviderException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $sunatCode = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function connectionFailed(string $provider, ?Throwable $previous = null): self
    {
        return new self(
            "No se pudo conectar con el proveedor [{$provider}].",
            0,
            $previous
        );
    }

    public static function sendFailed(string $provider, int $code, string $detail): self
    {
        return new self(
            "Error al enviar documento al proveedor [{$provider}]: ({$code}) {$detail}",
            $code
        );
    }

    public static function statusQueryFailed(string $ticketNumber, ?Throwable $previous = null): self
    {
        return new self(
            "Error al consultar el estado del ticket [{$ticketNumber}].",
            0,
            $previous
        );
    }
}
