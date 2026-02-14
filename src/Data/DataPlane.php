<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Data;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Mbvb1223\Pinecone\Errors\PineconeException;
use Mbvb1223\Pinecone\Errors\PineconeValidationException;
use Mbvb1223\Pinecone\Utils\HandlesApiResponse;

class DataPlane
{
    use HandlesApiResponse;

    public function __construct(private readonly Client $httpClient)
    {
    }

    /**
     * @param array<int, array{id: string, values: array<float>, sparseValues?: array{indices: array<int>, values: array<float>}, metadata?: array<string, mixed>}> $vectors
     * @return array<string, mixed>
     */
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

    /**
     * @param array<float> $vector
     * @param array<string, mixed>|null $filter Metadata filter expression
     * @param array{indices: array<int>, values: array<float>}|null $sparseVector
     * @return array<string, mixed>
     */
    public function query(
        array $vector = [],
        ?string $id = null,
        int $topK = 10,
        ?array $filter = null,
        ?string $namespace = null,
        bool $includeValues = false,
        bool $includeMetadata = true,
        ?array $sparseVector = null,
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

    /**
     * @param array<int, string> $ids
     * @return array<string, array<string, mixed>>
     */
    public function fetch(array $ids, ?string $namespace = null): array
    {
        if (empty($ids)) {
            throw new PineconeValidationException('At least one vector ID is required for fetch.');
        }

        try {
            // Pinecone expects repeated query params (ids=vec1&ids=vec2),
            // not PHP-style array params (ids[0]=vec1&ids[1]=vec2),
            // so we build the query string manually.
            $query = implode('&', array_map(fn ($id) => 'ids=' . urlencode((string) $id), $ids));
            if ($namespace !== null) {
                $query .= '&namespace=' . urlencode($namespace);
            }

            $response = $this->httpClient->get('/vectors/fetch?' . $query);

            return $this->handleResponse($response)['vectors'] ?? [];
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to fetch vectors: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @param array<int, string> $ids
     * @param array<string, mixed>|null $filter Metadata filter expression
     * @return array<string, mixed>
     */
    public function delete(array $ids = [], ?array $filter = null, ?string $namespace = null, bool $deleteAll = false): array
    {
        try {
            $payload = [];

            if ($deleteAll) {
                $payload['deleteAll'] = true;
            } else {
                if (!empty($ids)) {
                    $payload['ids'] = $ids;
                }
                if ($filter !== null) {
                    $payload['filter'] = $filter;
                }
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

    /**
     * @param array<float> $values
     * @param array<string, mixed>|null $setMetadata
     * @param array{indices: array<int>, values: array<float>}|null $sparseValues
     * @return array<string, mixed>
     */
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

    /** @return array<string, mixed> */
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

            $options = !empty($params) ? ['query' => $params] : [];
            $response = $this->httpClient->get('/vectors/list', $options);

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to list vector IDs: ' . $e->getMessage(), 0, $e);
        }
    }
}
