<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Tests\Integration\Base;

use Mbvb1223\Pinecone\Pinecone;
use PHPUnit\Framework\TestCase;

class BaseIntegrationTestCase extends TestCase
{
    public const INDEX_NAMES = [
        1 => 'test-index-1',
        2 => 'test-index-2',
        3 => 'test-index-3',
        4 => 'test-index-4',
    ];
    protected Pinecone $pinecone;

    protected function setUp(): void
    {
        parent::setUp();

        $apiKey = getenv('PINECONE_API_KEY') ?: ($_ENV['PINECONE_API_KEY'] ?? '');
        if (!$apiKey) {
            $this->markTestSkipped('PINECONE_API_KEY environment variable not set');
        }

        $this->pinecone = new Pinecone($apiKey);
    }

    protected function waitForIndexReady(string $indexName, int $timeoutSeconds = 120): void
    {
        $start = time();
        while (time() - $start < $timeoutSeconds) {
            $info = $this->pinecone->describeIndex($indexName);
            if (($info['status']['ready'] ?? false) === true) {
                return;
            }
            sleep(5);
        }
        $this->fail("Index '$indexName' did not become ready within {$timeoutSeconds} seconds.");
    }
}
