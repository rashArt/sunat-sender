<?php

declare(strict_types=1);

namespace RashArt\SunatSender\Exceptions;

/**
 * Excepción lanzada cuando ocurre un error en la generación,
 * firma o parseo del XML del documento electrónico.
 */
class XmlException extends SunatSenderException
{
    public static function generationFailed(string $documentType, string $detail, ?\Throwable $previous = null): self
    {
        return new self(
            "Error al generar XML para [{$documentType}]: {$detail}",
            0,
            $previous
        );
    }

    public static function signingFailed(string $detail, ?\Throwable $previous = null): self
    {
        return new self(
            "Error al firmar el XML: {$detail}",
            0,
            $previous
        );
    }

    public static function parseFailed(string $detail, ?\Throwable $previous = null): self
    {
        return new self(
            "Error al parsear respuesta XML de SUNAT: {$detail}",
            0,
            $previous
        );
    }
}