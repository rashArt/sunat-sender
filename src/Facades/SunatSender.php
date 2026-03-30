<?php

declare(strict_types=1);

namespace RashArt\SunatSender\Facades;

use Illuminate\Support\Facades\Facade;
use RashArt\SunatSender\Services\SunatSenderService;

/**
 * @method static \RashArt\SunatSender\DTOs\SunatResponse send(\RashArt\SunatSender\DTOs\DocumentData $document)
 * @method static \RashArt\SunatSender\DTOs\SunatResponse getStatusCdr(string $ruc, string $documentType, string $series, int $correlative)
 * @method static \RashArt\SunatSender\DTOs\SunatResponse getTicketStatus(string $ticket, string $ruc = '')
 * @method static string getProviderName()
 * @method static static withProvider(\RashArt\SunatSender\Contracts\ProviderInterface $provider)
 *
 * @see \RashArt\SunatSender\Services\SunatSenderService
 */
class SunatSender extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SunatSenderService::class;
    }
}