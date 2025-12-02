<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Data;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Mbvb1223\Pinecone\Utils\Configuration;
use Mbvb1223\Pinecone\Errors\PineconeApiException;
use Mbvb1223\Pinecone\Errors\PineconeException;
use Psr\Http\Message\ResponseInterface;

class Index
{
    private Client $httpClient;
    private Configuration $config;
    private array $indexInfo;
    private DataPlane $dataPlane;

    public function __construct(Configuration $config, array $indexInfo)
    {
        $this->config = $config;
        $this->indexInfo = $indexInfo;

        $host = $this->indexInfo['host'] ?? $this->buildIndexHost($this->indexInfo['name']);

        $this->httpClient = new Client([
            'base_uri' => "https://{$host}",
            'timeout' => $config->getTimeout(),
            'headers' => $config->getDefaultHeaders(),
        ]);

        $this->dataPlane = new DataPlane($config, $indexInfo);
    }

    public function describeIndexStats(?array $filter = null): array
    {
        try {
            $payload = [];
            if ($filter) {
                $payload['filter'] = $filter;
            }

            $response = $this->httpClient->post('/describe_index_stats', ['json' => (object) $payload]);

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to describe index stats: ' . $e->getMessage(), 0, $e);
        }
    }

    // Import operations
    public function startImport(array $requestData): array
    {
        try {
            $response = $this->httpClient->post('/bulk/imports', ['json' => $requestData]);

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to start import: ' . $e->getMessage(), 0, $e);
        }
    }

    public function listImports(): array
    {
        try {
            $response = $this->httpClient->get('/bulk/imports');

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to list imports: ' . $e->getMessage(), 0, $e);
        }
    }

    public function describeImport(string $importId): array
    {
        try {
            $response = $this->httpClient->get("/bulk/imports/{$importId}");

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to describe import: ' . $e->getMessage(), 0, $e);
        }
    }

    public function cancelImport(string $importId): void
    {
        try {
            $this->httpClient->delete("/bulk/imports/{$importId}");
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to cancel import: ' . $e->getMessage(), 0, $e);
        }
    }

    // Namespace operations
    public function listNamespaces(): array
    {
        try {
            $response = $this->httpClient->get('/describe_index_stats');
            $data = $this->handleResponse($response);

            return array_keys($data['namespaces'] ?? []);
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to list namespaces: ' . $e->getMessage(), 0, $e);
        }
    }

    public function describeNamespace(string $namespace): array
    {
        try {
            $response = $this->httpClient->post('/describe_index_stats', [
                'json' => (object) ['filter' => ['namespace' => $namespace]]
            ]);
            $data = $this->handleResponse($response);

            return $data['namespaces'][$namespace] ?? [];
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to describe namespace: ' . $e->getMessage(), 0, $e);
        }
    }

    public function deleteNamespace(string $namespace): void
    {
        try {
            $this->httpClient->post('/vectors/delete', [
                'json' => ['deleteAll' => true, 'namespace' => $namespace]
            ]);
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to delete namespace: ' . $e->getMessage(), 0, $e);
        }
    }

    public function namespace(string $namespace): IndexNamespace
    {
        return new IndexNamespace($this->dataPlane, $namespace);
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
