<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\DTOs;

use Mbvb1223\Pinecone\DTOs\Traits\Mappable;

class Index
{
    use Mappable;

    public function __construct(
        public readonly string $name,
        public readonly IndexStatus $status,
        public readonly string $host,
        public readonly IndexSpec $spec,
        public readonly string $metric = 'cosine',
        public readonly string $vectorType = 'dense',
        public readonly string $deletionProtection = 'disabled',
        public readonly ?int $dimension = null,
        public readonly ?array $tags = null,
        public readonly ?IndexEmbed $embed = null
    ) {
    }
}
