<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Data\Vectors;

class QueryMatch
{
    /**
     * @param array<float>|null $values
     * @param array<string, mixed>|null $metadata
     * @param array{indices: array<int>, values: array<float>}|null $sparseValues
     */
    public function __construct(
        public readonly string $id,
        public readonly float $score,
        public readonly ?array $values = null,
        public readonly ?array $metadata = null,
        public readonly ?array $sparseValues = null,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            score: (float) $data['score'],
            values: $data['values'] ?? null,
            metadata: $data['metadata'] ?? null,
            sparseValues: $data['sparseValues'] ?? null,
        );
    }
}
