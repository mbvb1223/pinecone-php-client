<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Inference;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Mbvb1223\Pinecone\Utils\Configuration;
use Mbvb1223\Pinecone\Errors\PineconeApiException;
use Mbvb1223\Pinecone\Errors\PineconeException;
use Psr\Http\Message\ResponseInterface;

class InferenceClient
{
    private Client $httpClient;
    private Configuration $config;

    public function __construct(Configuration $config)
    {
        $this->config = $config;
        $this->httpClient = new Client([
            'base_uri' => 'https://api.pinecone.io',
            'timeout' => $config->getTimeout(),
            'headers' => $config->getDefaultHeaders(),
        ]);
    }

    public function embed(string $model, array $inputs, array $parameters = []): array
    {
        try {
            $payload = [
                'model' => $model,
                'inputs' => $inputs,
                'parameters' => $parameters,
            ];

            $response = $this->httpClient->post('/embed', [
                'json' => $payload,
            ]);

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to generate embeddings: ' . $e->getMessage(), 0, $e);
        }
    }

    public function rerank(string $model, string $query, array $documents, array $parameters = []): array
    {
        try {
            $payload = [
                'model' => $model,
                'query' => $query,
                'documents' => $documents,
                'parameters' => $parameters,
            ];

            $response = $this->httpClient->post('/rerank', [
                'json' => $payload,
            ]);

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to rerank documents: ' . $e->getMessage(), 0, $e);
        }
    }

    public function listModels(): array
    {
        try {
            $response = $this->httpClient->get('/models');
            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to list models: ' . $e->getMessage(), 0, $e);
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
