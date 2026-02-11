<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Data;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Mbvb1223\Pinecone\Errors\PineconeException;
use Mbvb1223\Pinecone\Utils\HandlesApiResponse;

class DataPlane
{
    use HandlesApiResponse;

    public function __construct(private readonly Client $httpClient)
    {
    }

    public function upsert(array $vectors, ?string $namespace = null): array
    {
        try {
            $payload = ['vectors' => $vectors];
            if ($namespace !== null) {
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
        bool $includeMetadata = true,
        ?array $sparseVector = null
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

            if ($id !== null) {
                $payload['id'] = $id;
            }

            if ($filter !== null) {
                $payload['filter'] = $filter;
            }

            if ($namespace !== null) {
                $payload['namespace'] = $namespace;
            }

            if ($sparseVector !== null) {
                $payload['sparseVector'] = $sparseVector;
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
            $idQueries = implode('&', array_map(fn ($id) => 'ids=' . urlencode($id), $ids));

            $namespaceQuery = $namespace !== null ? '&namespace=' . urlencode($namespace) : '';

            $response = $this->httpClient->get('/vectors/fetch?' . $idQueries . $namespaceQuery);

            return $this->handleResponse($response)['vectors'] ?? [];
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
            } elseif ($filter !== null) {
                $payload['filter'] = $filter;
            }

            if ($namespace !== null) {
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

    public function update(string $id, array $values = [], ?array $setMetadata = null, ?string $namespace = null, ?array $sparseValues = null): array
    {
        try {
            $payload = ['id' => $id];

            if (!empty($values)) {
                $payload['values'] = $values;
            }

            if ($setMetadata !== null) {
                $payload['setMetadata'] = $setMetadata;
            }

            if ($namespace !== null) {
                $payload['namespace'] = $namespace;
            }

            if ($sparseValues !== null) {
                $payload['sparseValues'] = $sparseValues;
            }

            $response = $this->httpClient->post('/vectors/update', [
                'json' => $payload,
            ]);

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to update vector: ' . $e->getMessage(), 0, $e);
        }
    }

    public function listVectorIds(?string $prefix = null, ?int $limit = null, ?string $paginationToken = null, ?string $namespace = null): array
    {
        try {
            $params = [];
            if ($prefix !== null) {
                $params['prefix'] = $prefix;
            }
            if ($limit !== null) {
                $params['limit'] = $limit;
            }
            if ($paginationToken !== null) {
                $params['paginationToken'] = $paginationToken;
            }
            if ($namespace !== null) {
                $params['namespace'] = $namespace;
            }

            $query = !empty($params) ? '?' . http_build_query($params) : '';
            $response = $this->httpClient->get("/vectors/list{$query}");

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to list vector IDs: ' . $e->getMessage(), 0, $e);
        }
    }
}
