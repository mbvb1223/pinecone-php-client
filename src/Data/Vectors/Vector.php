<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Data\Vectors;

class Vector
{
    /**
     * @param array<float> $values
     * @param array<string, mixed>|null $metadata
     * @param array{indices: array<int>, values: array<float>}|null $sparseValues
     */
    public function __construct(
        public readonly string $id,
        public readonly array $values,
        public readonly ?array $metadata = null,
        public readonly ?array $sparseValues = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $data = ['id' => $this->id, 'values' => $this->values];
        if ($this->metadata !== null) {
            $data['metadata'] = $this->metadata;
        }
        if ($this->sparseValues !== null) {
            $data['sparseValues'] = $this->sparseValues;
        }

        return $data;
    }
}
