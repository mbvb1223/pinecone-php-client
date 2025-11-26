<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Tests\Integration;

use Mbvb1223\Pinecone\DTOs\Index;
use Mbvb1223\Pinecone\DTOs\IndexStatus;
use Mbvb1223\Pinecone\DTOs\IndexSpec;
use Mbvb1223\Pinecone\Pinecone;
use PHPUnit\Framework\TestCase;

class ListIndexesTest extends TestCase
{
    private Pinecone $pinecone;

    protected function setUp(): void
    {
        parent::setUp();

        // Skip integration tests if no API key is provided
        $apiKey = $_ENV['PINECONE_API_KEY'] ?? '';
        die($apiKey);
        if (!$apiKey) {
            $this->markTestSkipped('PINECONE_API_KEY environment variable not set');
        }

        $this->pinecone = new Pinecone($apiKey);
    }

    public function testListIndexesReturnsArrayOfIndexObjects(): void
    {
        $indexes = $this->pinecone->listIndexes();

        $this->assertIsArray($indexes);

        if (count($indexes) > 0) {
            // Verify each index is an Index object with required properties
            foreach ($indexes as $index) {
                $this->assertInstanceOf(Index::class, $index);
                $this->assertIsString($index->name);
                $this->assertIsString($index->metric);
                $this->assertIsString($index->host);
                $this->assertIsString($index->vectorType);
                $this->assertIsString($index->deletionProtection);
                $this->assertInstanceOf(IndexStatus::class, $index->status);
                $this->assertInstanceOf(IndexSpec::class, $index->spec);

                // Verify status has required fields
                $this->assertIsBool($index->status->ready);
                $this->assertIsString($index->status->state);

                // Verify at least one spec type is set
                $hasSpec = $index->spec->serverless !== null ||
                          $index->spec->pod !== null ||
                          $index->spec->byoc !== null;
                $this->assertTrue($hasSpec, 'Index must have one of serverless, pod, or byoc spec');

                // Test dimension is present for dense vectors
                if ($index->vectorType === 'dense') {
                    $this->assertNotNull($index->dimension, 'Dense vectors must have dimension specified');
                    $this->assertIsInt($index->dimension);
                    $this->assertGreaterThan(0, $index->dimension);
                }

                // Test metric values
                $this->assertContains($index->metric, ['cosine', 'euclidean', 'dotproduct']);

                // Test vector type values
                $this->assertContains($index->vectorType, ['dense', 'sparse']);

                // Test deletion protection values
                $this->assertContains($index->deletionProtection, ['enabled', 'disabled']);
            }
        } else {
            // If no indexes exist, we can still verify the method returns an empty array
            $this->assertSame([], $indexes);
        }
    }

    public function testListIndexesWithServerlessIndex(): void
    {
        $indexes = $this->pinecone->listIndexes();

        // Find a serverless index if any exist
        $serverlessIndex = null;
        foreach ($indexes as $index) {
            if ($index->spec->serverless !== null) {
                $serverlessIndex = $index;
                break;
            }
        }

        if ($serverlessIndex) {
            $this->assertNotNull($serverlessIndex->spec->serverless);
            $this->assertIsString($serverlessIndex->spec->serverless->cloud);
            $this->assertIsString($serverlessIndex->spec->serverless->region);
            $this->assertNull($serverlessIndex->spec->pod);
            $this->assertNull($serverlessIndex->spec->byoc);
        } else {
            $this->markTestSkipped('No serverless indexes found for testing');
        }
    }

    public function testListIndexesWithPodIndex(): void
    {
        $indexes = $this->pinecone->listIndexes();

        // Find a pod index if any exist
        $podIndex = null;
        foreach ($indexes as $index) {
            if ($index->spec->pod !== null) {
                $podIndex = $index;
                break;
            }
        }

        if ($podIndex) {
            $this->assertNotNull($podIndex->spec->pod);
            $this->assertIsString($podIndex->spec->pod->environment);
            $this->assertNull($podIndex->spec->serverless);
            $this->assertNull($podIndex->spec->byoc);
        } else {
            $this->markTestSkipped('No pod-based indexes found for testing');
        }
    }

    public function testListIndexesWithByocIndex(): void
    {
        $indexes = $this->pinecone->listIndexes();

        // Find a BYOC index if any exist
        $byocIndex = null;
        foreach ($indexes as $index) {
            if ($index->spec->byoc !== null) {
                $byocIndex = $index;
                break;
            }
        }

        if ($byocIndex) {
            $this->assertNotNull($byocIndex->spec->byoc);
            $this->assertIsString($byocIndex->spec->byoc->environment);
            $this->assertNull($byocIndex->spec->serverless);
            $this->assertNull($byocIndex->spec->pod);
        } else {
            $this->markTestSkipped('No BYOC indexes found for testing');
        }
    }

    public function testListIndexesWithEmbedField(): void
    {
        $indexes = $this->pinecone->listIndexes();

        // Find an index with embed configuration if any exist
        $embedIndex = null;
        foreach ($indexes as $index) {
            if ($index->embed !== null) {
                $embedIndex = $index;
                break;
            }
        }

        if ($embedIndex) {
            $this->assertNotNull($embedIndex->embed);
            $this->assertIsString($embedIndex->embed->model);
            $this->assertIsString($embedIndex->embed->vectorType);
        } else {
            $this->markTestSkipped('No indexes with embed configuration found for testing');
        }
    }
}
