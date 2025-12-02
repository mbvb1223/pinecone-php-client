<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Tests\Integration\Base;

use Mbvb1223\Pinecone\Pinecone;
use PHPUnit\Framework\TestCase;

class BaseIntegrationTestCase extends TestCase
{
    const INDEX_NAMES = [
        'test-index-1',
        'test-index-2',
        'test-index-3',
        'test-index-4',
    ];
    protected Pinecone $pinecone;

    protected function setUp(): void
    {
        parent::setUp();

        $apiKey = getenv('PINECONE_API_KEY') ?: '';
        if (!$apiKey) {
            $this->markTestSkipped('PINECONE_API_KEY environment variable not set');
        }

        $this->pinecone = new Pinecone($apiKey);
    }
}
