<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Utils;

use Mbvb1223\Pinecone\Errors\PineconeValidationException;

class Configuration
{
    private readonly string $apiKey;
    private readonly string $controllerHost;
    private readonly array $additionalHeaders;
    private readonly int $timeout;

    public function __construct(?string $apiKey = null, ?array $config = null)
    {
        $this->apiKey = $apiKey ?? $this->getApiKeyFromEnvironment();
        $controllerHost = $config['controllerHost'] ?? 'https://api.pinecone.io';
        $this->controllerHost = rtrim($controllerHost, '/');
        $this->additionalHeaders = $config['additionalHeaders'] ?? [];

        $timeout = isset($config['timeout']) ? (int) $config['timeout'] : 30;
        if ($timeout <= 0) {
            throw new PineconeValidationException('Timeout must be a positive integer.');
        }
        $this->timeout = $timeout;

        if (empty($this->apiKey)) {
            throw new PineconeValidationException('API key is required. Set PINECONE_API_KEY environment variable or pass it in configuration.');
        }

        $scheme = parse_url($this->controllerHost, PHP_URL_SCHEME);
        if ($scheme !== 'https' && $scheme !== 'http') {
            throw new PineconeValidationException('Controller host must be a valid URL with http or https scheme.');
        }
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
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
            'X-Pinecone-Api-Version' => '2025-10',
        ], $this->additionalHeaders);
    }

    private function getApiKeyFromEnvironment(): string
    {
        $envKey = $_ENV['PINECONE_API_KEY'] ?? null;
        if ($envKey !== null) {
            return $envKey;
        }

        $getenvKey = getenv('PINECONE_API_KEY');

        return $getenvKey !== false ? $getenvKey : '';
    }
}
