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

    public function __construct(Configuration $config)
    {
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
            $data = $this->handleResponse($response);

            return $data['indexes'] ?? [];
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to list indexes: ' . $e->getMessage(), 0, $e);
        }
    }

    public function createIndex(string $name, array $requestData): array
    {
        try {
            $payload = [
                'name' => $name,
                'dimension' => $requestData['dimension'],
                'metric' => $requestData['metric'] ?? 'cosine',
                'spec' => $requestData['spec'],
            ];

            $response = $this->httpClient->post('/indexes', ['json' => $payload]);

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException("Failed to create index: $name. {$e->getMessage()}", 0, $e);
        }
    }

    public function createForModel(string $name, array $requestData): array
    {
        try {
            $payload = [
                'name' => $name,
                'cloud' => $requestData['cloud'],
                'region' => $requestData['region'],
                'embed' => $requestData['embed'],
            ];

            // Add optional fields if provided
            if (isset($requestData['deletion_protection'])) {
                $payload['deletion_protection'] = $requestData['deletion_protection'];
            }

            if (isset($requestData['tags'])) {
                $payload['tags'] = $requestData['tags'];
            }

            if (isset($requestData['schema'])) {
                $payload['schema'] = $requestData['schema'];
            }

            if (isset($requestData['read_capacity'])) {
                $payload['read_capacity'] = $requestData['read_capacity'];
            }

            $response = $this->httpClient->post('/indexes/create-for-model', ['json' => $payload]);

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException("Failed to create index for model: $name. {$e->getMessage()}", 0, $e);
        }
    }

    public function describeIndex(string $name): array
    {
        try {
            $response = $this->httpClient->get("/indexes/{$name}");

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException("Failed to describe index: $name. {$e->getMessage()}", 0, $e);
        }
    }

    public function deleteIndex(string $name): void
    {
        try {
            $response = $this->httpClient->delete("/indexes/{$name}");
            $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException("Failed to delete index: $name. {$e->getMessage()}", 0, $e);
        }
    }

    public function configureIndex(string $name, array $requestData): array
    {
        try {
            $response = $this->httpClient->patch("/indexes/{$name}", ['json' => $requestData]);

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException("Failed to configure index: $name. {$e->getMessage()}", 0, $e);
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
