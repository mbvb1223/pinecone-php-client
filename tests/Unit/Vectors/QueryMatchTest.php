<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Tests\Unit\Vectors;

use Mbvb1223\Pinecone\Data\Vectors\QueryMatch;
use PHPUnit\Framework\TestCase;

class QueryMatchTest extends TestCase
{
    public function testConstructorWithRequiredFields(): void
    {
        $match = new QueryMatch('vec-1', 0.95);

        $this->assertEquals('vec-1', $match->id);
        $this->assertEquals(0.95, $match->score);
        $this->assertNull($match->values);
        $this->assertNull($match->metadata);
        $this->assertNull($match->sparseValues);
    }

    public function testConstructorWithAllFields(): void
    {
        $values = [0.1, 0.2, 0.3];
        $metadata = ['genre' => 'action'];
        $sparseValues = ['indices' => [0], 'values' => [1.0]];

        $match = new QueryMatch('vec-2', 0.87, $values, $metadata, $sparseValues);

        $this->assertEquals('vec-2', $match->id);
        $this->assertEquals(0.87, $match->score);
        $this->assertEquals($values, $match->values);
        $this->assertEquals($metadata, $match->metadata);
        $this->assertEquals($sparseValues, $match->sparseValues);
    }

    public function testFromArrayWithRequiredFields(): void
    {
        $data = ['id' => 'vec-1', 'score' => 0.95];

        $match = QueryMatch::fromArray($data);

        $this->assertEquals('vec-1', $match->id);
        $this->assertEquals(0.95, $match->score);
        $this->assertNull($match->values);
        $this->assertNull($match->metadata);
        $this->assertNull($match->sparseValues);
    }

    public function testFromArrayWithAllFields(): void
    {
        $data = [
            'id' => 'vec-2',
            'score' => 0.87,
            'values' => [0.1, 0.2, 0.3],
            'metadata' => ['genre' => 'action'],
            'sparseValues' => ['indices' => [0], 'values' => [1.0]],
        ];

        $match = QueryMatch::fromArray($data);

        $this->assertEquals('vec-2', $match->id);
        $this->assertEquals(0.87, $match->score);
        $this->assertEquals([0.1, 0.2, 0.3], $match->values);
        $this->assertEquals(['genre' => 'action'], $match->metadata);
        $this->assertEquals(['indices' => [0], 'values' => [1.0]], $match->sparseValues);
    }

    public function testFromArrayCastsScoreToFloat(): void
    {
        $data = ['id' => 'vec-1', 'score' => 1];

        $match = QueryMatch::fromArray($data);

        $this->assertIsFloat($match->score);
        $this->assertEquals(1.0, $match->score);
    }

    public function testFromArrayWithPartialOptionalFields(): void
    {
        $data = [
            'id' => 'vec-3',
            'score' => 0.5,
            'metadata' => ['color' => 'red'],
        ];

        $match = QueryMatch::fromArray($data);

        $this->assertEquals('vec-3', $match->id);
        $this->assertEquals(0.5, $match->score);
        $this->assertNull($match->values);
        $this->assertEquals(['color' => 'red'], $match->metadata);
        $this->assertNull($match->sparseValues);
    }

    public function testReadonlyProperties(): void
    {
        $match = new QueryMatch('vec-1', 0.9);

        $reflection = new \ReflectionClass($match);

        $this->assertTrue($reflection->getProperty('id')->isReadOnly());
        $this->assertTrue($reflection->getProperty('score')->isReadOnly());
        $this->assertTrue($reflection->getProperty('values')->isReadOnly());
        $this->assertTrue($reflection->getProperty('metadata')->isReadOnly());
        $this->assertTrue($reflection->getProperty('sparseValues')->isReadOnly());
    }

    public function testFromArrayWithZeroScore(): void
    {
        $data = ['id' => 'vec-1', 'score' => 0.0];

        $match = QueryMatch::fromArray($data);

        $this->assertEquals(0.0, $match->score);
    }
}
