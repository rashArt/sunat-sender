<?php

namespace RashArt\SunatSender\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use RashArt\SunatSender\Contracts\ProviderInterface;
use RashArt\SunatSender\DTOs\SendableDocument;
use RashArt\SunatSender\DTOs\SunatResponse;
use RashArt\SunatSender\Exceptions\ProviderException;
use RashArt\SunatSender\Exceptions\SunatSenderException;

abstract class AbstractSunatProvider implements ProviderInterface
{
    protected Client $httpClient;

    public function __construct(protected array $config)
    {
        $this->validateConfig();
        $this->httpClient = $this->buildHttpClient();
    }

    protected function validateConfig(): void
    {
        foreach ($this->requiredConfigKeys() as $key) {
            if (empty($this->config[$key])) {
                throw SunatSenderException::configurationMissing($key);
            }
        }
    }

    protected function buildHttpClient(): Client
    {
        return new Client([
            'timeout'         => $this->config['timeout'] ?? 30,
            'connect_timeout' => $this->config['connect_timeout'] ?? 10,
            'verify'          => $this->config['ssl_verify'] ?? true,
            'headers'         => [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ],
        ]);
    }

    public function healthCheck(): bool
    {
        try {
            $this->httpClient->get($this->getBaseUrl() . '/health');
            return true;
        } catch (GuzzleException) {
            return false;
        }
    }

    abstract protected function getBaseUrl(): string;

    abstract protected function requiredConfigKeys(): array;

    protected function buildZipContent(SendableDocument $document): string
    {
        $zipPath = sys_get_temp_dir() . '/' . $document->getFileName() . '.zip';

        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString($document->getFileName(), $document->xmlContent);
        $zip->close();

        $content = base64_encode(file_get_contents($zipPath));
        unlink($zipPath);

        return $content;
    }
}
