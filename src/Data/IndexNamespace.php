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

    public function upsert(array $vectors): array
    {
        return $this->dataPlane->upsert($vectors, $this->namespace);
    }

    public function query(
        array $vector = [],
        ?string $id = null,
        int $topK = 10,
        ?array $filter = null,
        bool $includeValues = false,
        bool $includeMetadata = true
    ): array {
        return $this->dataPlane->query($vector, $id, $topK, $filter, $this->namespace, $includeValues, $includeMetadata);
    }

    public function fetch(array $ids): array
    {
        return $this->dataPlane->fetch($ids, $this->namespace);
    }

    public function delete(array $ids = [], ?array $filter = null, bool $deleteAll = false): array
    {
        return $this->dataPlane->delete($ids, $filter, $this->namespace, $deleteAll);
    }

    public function update(string $id, array $values = [], ?array $setMetadata = null): array
    {
        return $this->dataPlane->update($id, $values, $setMetadata, $this->namespace);
    }
}
