<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Data\Vectors;

class SparseVector
{
    /**
     * @param array<int> $indices
     * @param array<float> $values
     */
    public function __construct(
        public readonly array $indices,
        public readonly array $values,
    ) {
    }

    /** @return array{indices: array<int>, values: array<float>} */
    public function toArray(): array
    {
        return ['indices' => $this->indices, 'values' => $this->values];
    }
}
