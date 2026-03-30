<?php

namespace RashArt\SunatSender\Services;

use RashArt\SunatSender\Contracts\ProviderInterface;
use RashArt\SunatSender\Contracts\SunatSenderInterface;
use RashArt\SunatSender\DTOs\SendableDocument;
use RashArt\SunatSender\DTOs\SunatResponse;

class SunatSenderService implements SunatSenderInterface
{
    public function __construct(
        protected ProviderInterface $provider,
        protected array $config = [],
    ) {}

    public function send(SendableDocument $document): SunatResponse
    {
        return $this->provider->send($document);
    }

    public function sendBatch(array $documents): SunatResponse
    {
        return $this->provider->sendBatch($documents);
    }

    public function getStatus(string $ticketNumber): SunatResponse
    {
        return $this->provider->getStatus($ticketNumber);
    }

    public function getProviderName(): string
    {
        return $this->provider->getName();
    }

    public function withProvider(ProviderInterface $provider): static
    {
        $clone = clone $this;
        $clone->provider = $provider;
        return $clone;
    }
}
