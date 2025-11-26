<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\DTOs;

use Mbvb1223\Pinecone\DTOs\Traits\Mappable;

class IndexEmbed
{
    use Mappable;

    public function __construct(
        public readonly string $model,
        public readonly string $vectorType = 'dense',
        public readonly ?string $metric = null,
        public readonly ?int $dimension = null,
        public readonly ?array $fieldMap = null,
        public readonly ?array $readParameters = null,
        public readonly ?array $writeParameters = null
    ) {
    }
}
