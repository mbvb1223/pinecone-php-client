<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Tests\Unit\Vectors;

use Mbvb1223\Pinecone\Data\Vectors\Vector;
use PHPUnit\Framework\TestCase;

class VectorTest extends TestCase
{
    public function testConstructorWithRequiredFields(): void
    {
        $vector = new Vector('vec-1', [0.1, 0.2, 0.3]);

        $this->assertEquals('vec-1', $vector->id);
        $this->assertEquals([0.1, 0.2, 0.3], $vector->values);
        $this->assertNull($vector->metadata);
        $this->assertNull($vector->sparseValues);
    }

    public function testConstructorWithAllFields(): void
    {
        $metadata = ['genre' => 'action', 'year' => 2023];
        $sparseValues = ['indices' => [0, 3], 'values' => [0.5, 0.8]];

        $vector = new Vector('vec-2', [1.0, 2.0], $metadata, $sparseValues);

        $this->assertEquals('vec-2', $vector->id);
        $this->assertEquals([1.0, 2.0], $vector->values);
        $this->assertEquals($metadata, $vector->metadata);
        $this->assertEquals($sparseValues, $vector->sparseValues);
    }

    public function testToArrayWithRequiredFieldsOnly(): void
    {
        $vector = new Vector('vec-1', [0.1, 0.2, 0.3]);

        $expected = [
            'id' => 'vec-1',
            'values' => [0.1, 0.2, 0.3],
        ];

        $this->assertEquals($expected, $vector->toArray());
    }

    public function testToArrayWithMetadata(): void
    {
        $metadata = ['genre' => 'action'];
        $vector = new Vector('vec-1', [0.1, 0.2], $metadata);

        $result = $vector->toArray();

        $this->assertArrayHasKey('metadata', $result);
        $this->assertEquals($metadata, $result['metadata']);
        $this->assertArrayNotHasKey('sparseValues', $result);
    }

    public function testToArrayWithSparseValues(): void
    {
        $sparseValues = ['indices' => [0, 3], 'values' => [0.5, 0.8]];
        $vector = new Vector('vec-1', [0.1, 0.2], null, $sparseValues);

        $result = $vector->toArray();

        $this->assertArrayNotHasKey('metadata', $result);
        $this->assertArrayHasKey('sparseValues', $result);
        $this->assertEquals($sparseValues, $result['sparseValues']);
    }

    public function testToArrayWithAllFields(): void
    {
        $metadata = ['genre' => 'action'];
        $sparseValues = ['indices' => [0], 'values' => [1.0]];
        $vector = new Vector('vec-1', [0.1], $metadata, $sparseValues);

        $expected = [
            'id' => 'vec-1',
            'values' => [0.1],
            'metadata' => $metadata,
            'sparseValues' => $sparseValues,
        ];

        $this->assertEquals($expected, $vector->toArray());
    }

    public function testReadonlyProperties(): void
    {
        $vector = new Vector('vec-1', [0.1, 0.2]);

        $reflection = new \ReflectionClass($vector);

        $this->assertTrue($reflection->getProperty('id')->isReadOnly());
        $this->assertTrue($reflection->getProperty('values')->isReadOnly());
        $this->assertTrue($reflection->getProperty('metadata')->isReadOnly());
        $this->assertTrue($reflection->getProperty('sparseValues')->isReadOnly());
    }

    public function testEmptyValues(): void
    {
        $vector = new Vector('vec-empty', []);

        $this->assertEquals([], $vector->values);
        $this->assertEquals(['id' => 'vec-empty', 'values' => []], $vector->toArray());
    }
}
