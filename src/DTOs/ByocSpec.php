<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\DTOs;

use Mbvb1223\Pinecone\DTOs\Traits\Mappable;

class ByocSpec
{
    use Mappable;

    public function __construct(
        public readonly string $environment,
        public readonly ?array $schema = null
    ) {
    }
}