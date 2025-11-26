<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Control;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Mbvb1223\Pinecone\Utils\Configuration;
use Mbvb1223\Pinecone\Errors\PineconeApiException;
use Mbvb1223\Pinecone\Errors\PineconeException;
use Psr\Http\Message\ResponseInterface;

class ControlPlane
{
    private Client $httpClient;
    private Configuration $config;

    public function __construct(Configuration $config)
    {
        $this->config = $config;
        $this->httpClient = new Client([
            'base_uri' => $config->getControllerHost(),
            'timeout' => $config->getTimeout(),
            'headers' => $config->getDefaultHeaders(),
        ]);
    }

    public function listIndexes(): array
    {
        try {
            $response = $this->httpClient->get('/indexes');
            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to list indexes: ' . $e->getMessage(), 0, $e);
        }
    }

    public function createIndex(string $name, array $spec): void
    {
        try {
            $payload = [
                'name' => $name,
                'dimension' => $spec['dimension'],
                'metric' => $spec['metric'] ?? 'cosine',
                'spec' => $spec,
            ];

            $response = $this->httpClient->post('/indexes', [
                'json' => $payload,
            ]);

            $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to create index: ' . $e->getMessage(), 0, $e);
        }
    }

    public function describeIndex(string $name): array
    {
        try {
            $response = $this->httpClient->get("/indexes/{$name}");
            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to describe index: ' . $e->getMessage(), 0, $e);
        }
    }

    public function deleteIndex(string $name): void
    {
        try {
            $response = $this->httpClient->delete("/indexes/{$name}");
            $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to delete index: ' . $e->getMessage(), 0, $e);
        }
    }

    public function configureIndex(string $name, array $spec): void
    {
        try {
            $response = $this->httpClient->patch("/indexes/{$name}", [
                'json' => ['spec' => $spec],
            ]);
            $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to configure index: ' . $e->getMessage(), 0, $e);
        }
    }

    private function handleResponse(ResponseInterface $response): array
    {
        $statusCode = $response->getStatusCode();
        $body = $response->getBody()->getContents();
        
        if ($statusCode >= 400) {
            $data = json_decode($body, true) ?? [];
            $message = $data['message'] ?? 'API request failed';
            throw new PineconeApiException($message, $statusCode, $data);
        }

        if (empty($body)) {
            return [];
        }

        $decoded = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new PineconeException('Failed to decode JSON response: ' . json_last_error_msg());
        }

        return $decoded;
    }
}