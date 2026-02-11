<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Data;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Mbvb1223\Pinecone\Errors\PineconeException;
use Mbvb1223\Pinecone\Utils\HandlesApiResponse;

class Index
{
    use HandlesApiResponse;

    private DataPlane $dataPlane;

    public function __construct(private readonly Client $httpClient)
    {
        $this->dataPlane = new DataPlane($httpClient);
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
            $response = $this->httpClient->post('/describe_index_stats', ['json' => (object) []]);
            $data = $this->handleResponse($response);

            return array_keys($data['namespaces'] ?? []);
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to list namespaces: ' . $e->getMessage(), 0, $e);
        }
    }

    public function describeNamespace(string $namespace): array
    {
        try {
            $response = $this->httpClient->post('/describe_index_stats', ['json' => (object) []]);
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
                'json' => ['deleteAll' => true, 'namespace' => $namespace],
            ]);
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to delete namespace: ' . $e->getMessage(), 0, $e);
        }
    }

    public function namespace(string $namespace): IndexNamespace
    {
        return new IndexNamespace($this->dataPlane, $namespace);
    }

    // Data plane proxy methods
    public function upsert(array $vectors, ?string $namespace = null): array
    {
        return $this->dataPlane->upsert($vectors, $namespace);
    }

    public function query(
        array $vector = [],
        ?string $id = null,
        int $topK = 10,
        ?array $filter = null,
        ?string $namespace = null,
        bool $includeValues = false,
        bool $includeMetadata = true,
        ?array $sparseVector = null
    ): array {
        return $this->dataPlane->query($vector, $id, $topK, $filter, $namespace, $includeValues, $includeMetadata, $sparseVector);
    }

    public function fetch(array $ids, ?string $namespace = null): array
    {
        return $this->dataPlane->fetch($ids, $namespace);
    }

    public function delete(array $ids = [], ?array $filter = null, ?string $namespace = null, bool $deleteAll = false): array
    {
        return $this->dataPlane->delete($ids, $filter, $namespace, $deleteAll);
    }

    public function update(string $id, array $values = [], ?array $setMetadata = null, ?string $namespace = null): array
    {
        return $this->dataPlane->update($id, $values, $setMetadata, $namespace);
    }

    public function listVectorIds(?string $prefix = null, ?int $limit = null, ?string $paginationToken = null, ?string $namespace = null): array
    {
        return $this->dataPlane->listVectorIds($prefix, $limit, $paginationToken, $namespace);
    }
}
