<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\DTOs\Traits;

use CuyZ\Valinor\Mapper\Source\Source;
use CuyZ\Valinor\MapperBuilder;

trait Mappable
{
    /**
     * @template T of self
     *
     * @return T
     */
    public static function fromArray(iterable $data): self
    {
        return (new MapperBuilder())
            ->allowSuperfluousKeys()
            ->allowPermissiveTypes()
            ->mapper()
            ->map(self::class, Source::iterable($data)->camelCaseKeys());
    }

    /**
     * @template T of self
     *
     * @return T[]
     */
    public static function listMap(iterable $data): array
    {
        return (new MapperBuilder())
            ->allowSuperfluousKeys()
            ->allowPermissiveTypes()
            ->mapper()
            ->map(self::class . '[]', Source::iterable($data)->camelCaseKeys());
    }
}
