<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\DTOs;

use Mbvb1223\Pinecone\DTOs\Traits\Mappable;

class Index
{
    use Mappable;

    public function __construct(
        public readonly string $name,
        public readonly int $dimension,
        public readonly string $metric,
        public readonly IndexStatus $status,
        public readonly string $host,
        public readonly IndexSpec $spec,
        public readonly ?string $vectorType = null,
        public readonly ?string $deletionProtection = null,
        public readonly ?array $tags = null
    ) {
    }
}
