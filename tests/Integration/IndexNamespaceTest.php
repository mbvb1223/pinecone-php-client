<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Tests\Integration;

use Mbvb1223\Pinecone\Tests\Integration\Base\BaseIntegrationTestCase;

class IndexNamespaceTest extends BaseIntegrationTestCase
{
    public function testIndexNamespaceOperations(): void
    {
        $indexName = BaseIntegrationTestCase::INDEX_NAMES[1];

        $this->pinecone->createIndex($indexName, [
            'dimension' => 1024,
            'metric' => 'cosine',
            'spec' => [
                'serverless' => [
                    'cloud' => 'aws',
                    'region' => 'us-east-1',
                ],
            ],
        ]);
        $this->waitForIndexReady($indexName);

        $index = $this->pinecone->index($indexName);
        $namespace = $index->namespace('khien');
        $namespace->upsert([
            [
                'id' => 'vec1',
                'values' => array_fill(0, 1024, 0.5),
                'metadata' => ['category' => 'test'],
            ],
            [
                'id' => 'vec2',
                'values' => array_fill(0, 1024, 0.8),
                'metadata' => ['category' => 'test'],
            ],
        ]);
        $this->waitForVectors($namespace, ['vec1', 'vec2']);
        $vectors = $namespace->fetch(['vec1', 'vec2']);
        $this->assertCount(2, $vectors);

        $namespace->delete(['vec1']);
        $namespace->update('vec2', [], ['category' => 'updated']);
        $namespace->query(
            vector: array_fill(0, 1024, 0.8),
            topK: 1,
        );

        // Wait for delete to propagate (eventual consistency)
        $start = time();
        while (time() - $start < 30) {
            $vectors = $namespace->fetch(['vec1', 'vec2']);
            if (count($vectors) === 1) {
                break;
            }
            sleep(2);
        }
        $this->assertCount(1, $vectors);

        $this->pinecone->deleteIndex($indexName);
    }
}
