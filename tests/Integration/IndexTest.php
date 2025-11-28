<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Tests\Integration;

use Mbvb1223\Pinecone\Tests\Integration\Base\BaseIntegrationTestCase;

class IndexTest extends BaseIntegrationTestCase
{
    public function testListIndexes(): void
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
    }

    public function testIndexOperations(): void
    {
        $indexName = 'test-integration';

        sleep(5);
        $index = $this->pinecone->createIndex($indexName, [
            'dimension' => 1024,
            'metric' => 'cosine',
            'spec' => [
                'serverless' => [
                    'cloud' => 'aws',
                    'region' => 'us-east-1'
                ]
            ]
        ]);

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

        $indexModelName = 'test-integration-model-' . bin2hex(random_bytes(5));
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
}
