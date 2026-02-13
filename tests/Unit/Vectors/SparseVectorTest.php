<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Tests\Unit\Vectors;

use Mbvb1223\Pinecone\Data\Vectors\SparseVector;
use PHPUnit\Framework\TestCase;

class SparseVectorTest extends TestCase
{
    public function testConstructor(): void
    {
        $sparseVector = new SparseVector([0, 3, 7], [0.5, 0.8, 1.2]);

        $this->assertEquals([0, 3, 7], $sparseVector->indices);
        $this->assertEquals([0.5, 0.8, 1.2], $sparseVector->values);
    }

    public function testToArray(): void
    {
        $sparseVector = new SparseVector([0, 3, 7], [0.5, 0.8, 1.2]);

        $expected = [
            'indices' => [0, 3, 7],
            'values' => [0.5, 0.8, 1.2],
        ];

        $this->assertEquals($expected, $sparseVector->toArray());
    }

    public function testEmptyIndicesAndValues(): void
    {
        $sparseVector = new SparseVector([], []);

        $this->assertEquals([], $sparseVector->indices);
        $this->assertEquals([], $sparseVector->values);
        $this->assertEquals(['indices' => [], 'values' => []], $sparseVector->toArray());
    }

    public function testReadonlyProperties(): void
    {
        $sparseVector = new SparseVector([1], [0.5]);

        $reflection = new \ReflectionClass($sparseVector);

        $this->assertTrue($reflection->getProperty('indices')->isReadOnly());
        $this->assertTrue($reflection->getProperty('values')->isReadOnly());
    }

    public function testSingleElement(): void
    {
        $sparseVector = new SparseVector([42], [0.99]);

        $expected = [
            'indices' => [42],
            'values' => [0.99],
        ];

        $this->assertEquals($expected, $sparseVector->toArray());
    }
}
