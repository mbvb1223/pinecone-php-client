<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\DTOs;

use CuyZ\Valinor\Mapper\Source\Source;
use CuyZ\Valinor\MapperBuilder;
use Mbvb1223\Pinecone\DTOs\Traits\Mappable;

class ServerlessSpec
{
    use Mappable;

    public function __construct(
        public readonly string $cloud,
        public readonly string $region
    ) {
    }
}
