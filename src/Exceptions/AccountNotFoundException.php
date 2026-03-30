<?php

declare(strict_types=1);

namespace RashArt\SunatSender\Exceptions;

/**
 * Excepción lanzada cuando no se encuentran las credenciales
 * (RUC, clave SOL, certificado) necesarias para el envío.
 */
class AccountNotFoundException extends SunatSenderException
{
    public static function forRuc(string $ruc): self
    {
        return new self("No se encontraron credenciales SUNAT para el RUC [{$ruc}].");
    }

    public static function missingCertificate(string $ruc): self
    {
        return new self("No se encontró el certificado digital para el RUC [{$ruc}].");
    }
}