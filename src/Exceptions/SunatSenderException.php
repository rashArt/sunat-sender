<?php

declare(strict_types=1);

namespace RashArt\SunatSender\Exceptions;

/**
 * Excepción base del paquete sunat-sender.
 * Todas las demás excepciones del paquete heredan de esta clase,
 * lo que permite al consumidor capturar cualquier error del paquete
 * con un solo catch (SunatSenderException).
 */
class SunatSenderException extends \RuntimeException
{
}