<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Tests\Integration;

use Mbvb1223\Pinecone\Tests\Integration\Base\BaseIntegrationTestCase;

class ListIndexesTest extends BaseIntegrationTestCase
{
    public function testListIndexesReturnsArrayOfIndexArrays(): void
    {
        $indexes = $this->pinecone->listIndexes();

        $this->assertIsArray($indexes);

        if (count($indexes) > 0) {
            // Verify each index is an array with required properties
            foreach ($indexes as $index) {
                $this->assertIsArray($index);
                $this->assertArrayHasKey('name', $index);
                $this->assertArrayHasKey('metric', $index);
                $this->assertArrayHasKey('host', $index);
                $this->assertArrayHasKey('vector_type', $index);
                $this->assertArrayHasKey('deletion_protection', $index);
                $this->assertArrayHasKey('status', $index);
                $this->assertArrayHasKey('spec', $index);

                // Verify types
                $this->assertIsString($index['name']);
                $this->assertIsString($index['metric']);
                $this->assertIsString($index['host']);
                $this->assertIsString($index['vector_type']);
                $this->assertIsString($index['deletion_protection']);
                $this->assertIsArray($index['status']);
                $this->assertIsArray($index['spec']);

                // Verify status has required fields
                $this->assertArrayHasKey('ready', $index['status']);
                $this->assertArrayHasKey('state', $index['status']);
                $this->assertIsBool($index['status']['ready']);
                $this->assertIsString($index['status']['state']);

                // Verify at least one spec type is set
                $hasSpec = isset($index['spec']['serverless']) ||
                          isset($index['spec']['pod']) ||
                          isset($index['spec']['byoc']);
                $this->assertTrue($hasSpec, 'Index must have one of serverless, pod, or byoc spec');

                // Test dimension is present for dense vectors
                if ($index['vector_type'] === 'dense') {
                    $this->assertArrayHasKey('dimension', $index);
                    $this->assertIsInt($index['dimension']);
                    $this->assertGreaterThan(0, $index['dimension']);
                }

                // Test metric values
                $this->assertContains($index['metric'], ['cosine', 'euclidean', 'dotproduct']);

                // Test vector type values
                $this->assertContains($index['vector_type'], ['dense', 'sparse']);

                // Test deletion protection values
                $this->assertContains($index['deletion_protection'], ['enabled', 'disabled']);
            }
        } else {
            $this->assertSame([], $indexes);
        }
    }

    public function testListIndexesWithServerlessIndex(): void
    {
        $indexes = $this->pinecone->listIndexes();

        // Find a serverless index if any exist
        $serverlessIndex = null;
        foreach ($indexes as $index) {
            if (isset($index['spec']['serverless'])) {
                $serverlessIndex = $index;
                break;
            }
        }

        if ($serverlessIndex) {
            $this->assertArrayHasKey('serverless', $serverlessIndex['spec']);
            $this->assertIsArray($serverlessIndex['spec']['serverless']);
            $this->assertArrayHasKey('cloud', $serverlessIndex['spec']['serverless']);
            $this->assertArrayHasKey('region', $serverlessIndex['spec']['serverless']);
            $this->assertIsString($serverlessIndex['spec']['serverless']['cloud']);
            $this->assertIsString($serverlessIndex['spec']['serverless']['region']);
            $this->assertArrayNotHasKey('pod', $serverlessIndex['spec']);
            $this->assertArrayNotHasKey('byoc', $serverlessIndex['spec']);
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
            if (isset($index['spec']['pod'])) {
                $podIndex = $index;
                break;
            }
        }

        if ($podIndex) {
            $this->assertArrayHasKey('pod', $podIndex['spec']);
            $this->assertIsArray($podIndex['spec']['pod']);
            $this->assertArrayHasKey('environment', $podIndex['spec']['pod']);
            $this->assertIsString($podIndex['spec']['pod']['environment']);
            $this->assertArrayNotHasKey('serverless', $podIndex['spec']);
            $this->assertArrayNotHasKey('byoc', $podIndex['spec']);
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
            if (isset($index['spec']['byoc'])) {
                $byocIndex = $index;
                break;
            }
        }

        if ($byocIndex) {
            $this->assertArrayHasKey('byoc', $byocIndex['spec']);
            $this->assertIsArray($byocIndex['spec']['byoc']);
            $this->assertArrayHasKey('environment', $byocIndex['spec']['byoc']);
            $this->assertIsString($byocIndex['spec']['byoc']['environment']);
            $this->assertArrayNotHasKey('serverless', $byocIndex['spec']);
            $this->assertArrayNotHasKey('pod', $byocIndex['spec']);
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
            if (isset($index['embed'])) {
                $embedIndex = $index;
                break;
            }
        }

        if ($embedIndex) {
            $this->assertArrayHasKey('embed', $embedIndex);
            $this->assertIsArray($embedIndex['embed']);
            $this->assertArrayHasKey('model', $embedIndex['embed']);
            $this->assertIsString($embedIndex['embed']['model']);
            if (isset($embedIndex['embed']['vector_type'])) {
                $this->assertIsString($embedIndex['embed']['vector_type']);
            }
        } else {
            $this->markTestSkipped('No indexes with embed configuration found for testing');
        }
    }
}
