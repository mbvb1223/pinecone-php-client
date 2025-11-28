<?php

declare(strict_types=1);

namespace Integration;

use Mbvb1223\Pinecone\Tests\Integration\Base\BaseIntegrationTestCase;

class IndexNamespaceTest extends BaseIntegrationTestCase
{
    public function testIndexNamespaceOperations(): void
    {
        $indexName = 'test-integration';

        $this->pinecone->createIndex($indexName, [
            'dimension' => 1024,
            'metric' => 'cosine',
            'spec' => [
                'serverless' => [
                    'cloud' => 'aws',
                    'region' => 'us-east-1'
                ]
            ]
        ]);

        $index = $this->pinecone->index($indexName);
        $namespace = $index->namespace('khien');
        $namespace->upsert([
            [
                'id' => 'vec1',
                'values' => array_fill(0, 1024, 0.5),
                'metadata' => ['category' => 'test']
            ],
            [
                'id' => 'vec2',
                'values' => array_fill(0, 1024, 0.8),
                'metadata' => ['category' => 'test']
            ]
        ]);
        sleep(5);
        $vectors = $namespace->fetch(['vec1', 'vec2']);
        $this->assertCount(2, $vectors);

        $namespace->delete(['vec1']);

        $vectors = $namespace->fetch(['vec1', 'vec2']);
        $this->assertCount(1, $vectors);

        $this->pinecone->deleteIndex($indexName);
    }
}
