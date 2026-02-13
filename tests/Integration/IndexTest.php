<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Tests\Integration;

use Mbvb1223\Pinecone\Tests\Integration\Base\BaseIntegrationTestCase;

class IndexTest extends BaseIntegrationTestCase
{
    public function testIndexControlPlane(): void
    {
        $indexes = $this->pinecone->listIndexes();

        $this->assertIsArray($indexes);

        foreach ($indexes as $index) {
            $this->assertIsArray($index);
            $this->assertArrayHasKey('name', $index);
            $this->assertArrayHasKey('metric', $index);
            $this->assertArrayHasKey('host', $index);
            $this->assertArrayHasKey('vector_type', $index);
            $this->assertArrayHasKey('deletion_protection', $index);
            $this->assertArrayHasKey('status', $index);
            $this->assertArrayHasKey('spec', $index);
        }

        $indexName = BaseIntegrationTestCase::INDEX_NAMES[2];
        $index = $this->createIndexName($indexName);

        $this->assertIsArray($index);
        $this->assertSame($indexName, $index['name']);

        $index = $this->pinecone->describeIndex($indexName);
        $this->assertIsArray($index);
        $this->assertSame($indexName, $index['name']);

        $tag = 'test_' . bin2hex(random_bytes(5));
        $index = $this->pinecone->configureIndex($indexName, [
            'tags' => [
                'environment' => $tag
            ]
        ]);
        $this->assertIsArray($index);
        $this->assertSame($tag, $index['tags']['environment']);

        $this->pinecone->deleteIndex($indexName);

        $indexModelName = BaseIntegrationTestCase::INDEX_NAMES[3];
        $index = $this->pinecone->createForModel($indexModelName, [
            'cloud' => 'aws',
            'region' => 'us-east-1',
            'embed' => [
                'model' => 'multilingual-e5-large',
                'metric' => 'cosine',
                'field_map' => [
                    'text' => 'content'
                ]
            ],
            'deletion_protection' => 'disabled',
            'tags' => [
                'environment' => $tag
            ]
        ]);
        $this->assertIsArray($index);
        $this->assertSame($tag, $index['tags']['environment']);

        $this->pinecone->deleteIndex($indexModelName);
    }

    public function testIndexDataPlane(): void
    {
        $indexName = BaseIntegrationTestCase::INDEX_NAMES[4];
        $this->createIndexName($indexName);
        $this->waitForIndexReady($indexName);
        $index = $this->pinecone->index($indexName);
        $result = $index->describeIndexStats();
        $this->assertIsArray($result);
        try {
            $index->describeIndexStats(['category' => 'non-existent']);
        } catch (\Exception $exception) {
            $this->assertTrue(str_contains($exception->getMessage(), 'Serverless and Starter indexes do not support describing index stats with metadata filterin'));
        }

        $result = $index->listImports();
        $this->assertIsArray($result);

        try {
            $index->describeImport('123');
        } catch (\Exception $exception) {
            $this->assertTrue(str_contains($exception->getMessage(), 'Operation not found'));
        }

        $result = $index->startImport([
            'id' => '1',
            'uri' => 's3://non-existent-bucket/path',
        ]);
        $this->assertIsArray($result);

        $index->cancelImport('1');
        $this->assertIsArray($result);

        $this->pinecone->deleteIndex($indexName);
    }

    protected function createIndexName(string $indexName): array
    {
        return $this->pinecone->createIndex($indexName, [
            'dimension' => 1024,
            'metric' => 'cosine',
            'spec' => [
                'serverless' => [
                    'cloud' => 'aws',
                    'region' => 'us-east-1'
                ]
            ]
        ]);
    }
}
