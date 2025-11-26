<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\DTOs;

use Mbvb1223\Pinecone\DTOs\Traits\Mappable;

class PodSpec
{
    use Mappable;

    public function __construct(
        public readonly string $environment,
        public readonly ?string $podType = null,
        public readonly ?int $pods = null,
        public readonly ?int $replicas = null,
        public readonly ?int $shards = null,
        public readonly ?array $metadataConfig = null
    ) {
    }
}
