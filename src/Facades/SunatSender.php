<?php

namespace RashArt\SunatSender\Facades;

use Illuminate\Support\Facades\Facade;
use RashArt\SunatSender\Contracts\SunatSenderInterface;

/**
 * @method static \RashArt\SunatSender\DTOs\SunatResponse send(\RashArt\SunatSender\DTOs\SendableDocument $document)
 * @method static \RashArt\SunatSender\DTOs\SunatResponse sendBatch(array $documents)
 * @method static \RashArt\SunatSender\DTOs\SunatResponse getStatus(string $ticketNumber)
 * @method static string getProviderName()
 *
 * @see \RashArt\SunatSender\Services\SunatSenderService
 */
class SunatSender extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SunatSenderInterface::class;
    }
}
