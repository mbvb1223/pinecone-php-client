<?php

declare(strict_types=1);

namespace Pinecone\Utils;

use Pinecone\Errors\PineconeException;

class Configuration
{
    private string $apiKey;
    private string $environment;
    private string $controllerHost;
    private array $additionalHeaders;
    private int $timeout;

    public function __construct(?string $apiKey = null, ?array $config = null)
    {
        $this->apiKey = $apiKey ?? $this->getApiKeyFromEnvironment();
        $this->environment = $config['environment'] ?? 'us-east1-aws';
        $this->controllerHost = $config['controllerHost'] ?? 'https://api.pinecone.io';
        $this->additionalHeaders = $config['additionalHeaders'] ?? [];
        $this->timeout = $config['timeout'] ?? 30;

        if (empty($this->apiKey)) {
            throw new PineconeException('API key is required. Set PINECONE_API_KEY environment variable or pass it in configuration.');
        }
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getEnvironment(): string
    {
        return $this->environment;
    }

    public function getControllerHost(): string
    {
        return $this->controllerHost;
    }

    public function getAdditionalHeaders(): array
    {
        return $this->additionalHeaders;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function getDefaultHeaders(): array
    {
        return array_merge([
            'Api-Key' => $this->apiKey,
            'User-Agent' => 'pinecone-php-client/1.0.0',
            'Content-Type' => 'application/json',
        ], $this->additionalHeaders);
    }

    private function getApiKeyFromEnvironment(): string
    {
        return $_ENV['PINECONE_API_KEY'] ?? getenv('PINECONE_API_KEY') ?: '';
    }
}