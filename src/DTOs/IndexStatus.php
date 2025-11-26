<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\DTOs;

use Mbvb1223\Pinecone\DTOs\Traits\Mappable;

class IndexStatus
{
    use Mappable;

    public function __construct(
        public readonly bool $ready,
        public readonly string $state
    ) {
    }
}
