<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Data;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Mbvb1223\Pinecone\Utils\Configuration;
use Mbvb1223\Pinecone\Errors\PineconeApiException;
use Mbvb1223\Pinecone\Errors\PineconeException;
use Psr\Http\Message\ResponseInterface;

class DataPlane
{
    private Client $httpClient;
    private Configuration $config;
    private array $indexInfo;

    public function __construct(Configuration $config, array $indexInfo)
    {
        $this->config = $config;
        $this->indexInfo = $indexInfo;
        
        $host = $indexInfo['host'] ?? $this->buildIndexHost($indexInfo['name']);
        
        $this->httpClient = new Client([
            'base_uri' => "https://{$host}",
            'timeout' => $config->getTimeout(),
            'headers' => $config->getDefaultHeaders(),
        ]);
    }

    public function upsert(array $vectors, ?string $namespace = null): array
    {
        try {
            $payload = ['vectors' => $vectors];
            if ($namespace) {
                $payload['namespace'] = $namespace;
            }

            $response = $this->httpClient->post('/vectors/upsert', [
                'json' => $payload,
            ]);

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to upsert vectors: ' . $e->getMessage(), 0, $e);
        }
    }

    public function query(
        array $vector = [],
        ?string $id = null,
        int $topK = 10,
        ?array $filter = null,
        ?string $namespace = null,
        bool $includeValues = false,
        bool $includeMetadata = true
    ): array {
        try {
            $payload = [
                'topK' => $topK,
                'includeValues' => $includeValues,
                'includeMetadata' => $includeMetadata,
            ];

            if (!empty($vector)) {
                $payload['vector'] = $vector;
            }

            if ($id) {
                $payload['id'] = $id;
            }

            if ($filter) {
                $payload['filter'] = $filter;
            }

            if ($namespace) {
                $payload['namespace'] = $namespace;
            }

            $response = $this->httpClient->post('/query', [
                'json' => $payload,
            ]);

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to query vectors: ' . $e->getMessage(), 0, $e);
        }
    }

    public function fetch(array $ids, ?string $namespace = null): array
    {
        try {
            $params = ['ids' => implode(',', $ids)];
            if ($namespace) {
                $params['namespace'] = $namespace;
            }

            $response = $this->httpClient->get('/vectors/fetch?' . http_build_query($params));
            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to fetch vectors: ' . $e->getMessage(), 0, $e);
        }
    }

    public function delete(array $ids = [], ?array $filter = null, ?string $namespace = null, bool $deleteAll = false): array
    {
        try {
            $payload = [];

            if ($deleteAll) {
                $payload['deleteAll'] = true;
            } elseif (!empty($ids)) {
                $payload['ids'] = $ids;
            } elseif ($filter) {
                $payload['filter'] = $filter;
            }

            if ($namespace) {
                $payload['namespace'] = $namespace;
            }

            $response = $this->httpClient->post('/vectors/delete', [
                'json' => $payload,
            ]);

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to delete vectors: ' . $e->getMessage(), 0, $e);
        }
    }

    public function update(string $id, array $values = [], ?array $setMetadata = null, ?string $namespace = null): array
    {
        try {
            $payload = ['id' => $id];

            if (!empty($values)) {
                $payload['values'] = $values;
            }

            if ($setMetadata) {
                $payload['setMetadata'] = $setMetadata;
            }

            if ($namespace) {
                $payload['namespace'] = $namespace;
            }

            $response = $this->httpClient->post('/vectors/update', [
                'json' => $payload,
            ]);

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to update vector: ' . $e->getMessage(), 0, $e);
        }
    }

    public function describeIndexStats(?array $filter = null): array
    {
        try {
            $payload = [];
            if ($filter) {
                $payload['filter'] = $filter;
            }

            $response = $this->httpClient->post('/describe_index_stats', [
                'json' => $payload,
            ]);

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to describe index stats: ' . $e->getMessage(), 0, $e);
        }
    }

    private function buildIndexHost(string $indexName): string
    {
        return "{$indexName}-{$this->config->getEnvironment()}.svc.{$this->config->getEnvironment()}.pinecone.io";
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
