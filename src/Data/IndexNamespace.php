<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Data;

class IndexNamespace
{
    private DataPlane $dataPlane;
    private string $namespace;

    public function __construct(DataPlane $dataPlane, string $namespace)
    {
        $this->dataPlane = $dataPlane;
        $this->namespace = $namespace;
    }

    /**
     * @param array<int, array{id: string, values: array<float>, sparseValues?: array{indices: array<int>, values: array<float>}, metadata?: array<string, mixed>}> $vectors
     * @return array<string, mixed>
     */
    public function upsert(array $vectors): array
    {
        return $this->dataPlane->upsert($vectors, $this->namespace);
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
        bool $includeValues = false,
        bool $includeMetadata = true,
        ?array $sparseVector = null,
    ): array {
        return $this->dataPlane->query($vector, $id, $topK, $filter, $this->namespace, $includeValues, $includeMetadata, $sparseVector);
    }

    /**
     * @param array<int, string> $ids
     * @return array<string, array<string, mixed>>
     */
    public function fetch(array $ids): array
    {
        return $this->dataPlane->fetch($ids, $this->namespace);
    }

    /**
     * @param array<int, string> $ids
     * @param array<string, mixed>|null $filter Metadata filter expression
     * @return array<string, mixed>
     */
    public function delete(array $ids = [], ?array $filter = null, bool $deleteAll = false): array
    {
        return $this->dataPlane->delete($ids, $filter, $this->namespace, $deleteAll);
    }

    /**
     * @param array<float> $values
     * @param array<string, mixed>|null $setMetadata
     * @param array{indices: array<int>, values: array<float>}|null $sparseValues
     * @return array<string, mixed>
     */
    public function update(string $id, array $values = [], ?array $setMetadata = null, ?array $sparseValues = null): array
    {
        return $this->dataPlane->update($id, $values, $setMetadata, $this->namespace, $sparseValues);
    }

    /** @return array<string, mixed> */
    public function listVectorIds(?string $prefix = null, ?int $limit = null, ?string $paginationToken = null): array
    {
        return $this->dataPlane->listVectorIds($prefix, $limit, $paginationToken, $this->namespace);
    }
}
