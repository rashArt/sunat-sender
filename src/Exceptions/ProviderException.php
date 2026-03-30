<?php

declare(strict_types=1);

namespace RashArt\SunatSender\Exceptions;

/**
 * Excepción lanzada cuando un proveedor (OSE, PSE, Directo)
 * retorna un error de comunicación o respuesta inesperada.
 */
class ProviderException extends SunatSenderException
{
    public static function communicationError(string $provider, string $message, ?\Throwable $previous = null): self
    {
        return new self(
            "Error de comunicación con el proveedor [{$provider}]: {$message}",
            0,
            $previous
        );
    }

    public static function unexpectedResponse(string $provider, string $detail): self
    {
        return new self(
            "Respuesta inesperada del proveedor [{$provider}]: {$detail}"
        );
    }
}