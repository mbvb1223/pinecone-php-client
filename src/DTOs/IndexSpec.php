<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\DTOs;

use Mbvb1223\Pinecone\DTOs\Traits\Mappable;

class IndexSpec
{
    use Mappable;

    public function __construct(
        public readonly ?ServerlessSpec $serverless = null,
        public readonly ?PodSpec $pod = null,
        public readonly ?ByocSpec $byoc = null
    ) {
    }
}
