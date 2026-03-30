<?php

namespace RashArt\SunatSender\Exceptions;

use RuntimeException;

class SunatSenderException extends RuntimeException
{
    public static function providerNotFound(string $providerName): self
    {
        return new self("Proveedor SUNAT no encontrado: [{$providerName}].");
    }

    public static function configurationMissing(string $key): self
    {
        return new self("Configuración requerida no encontrada: [{$key}].");
    }

    public static function invalidDocument(string $reason): self
    {
        return new self("Documento inválido: {$reason}");
    }
}
