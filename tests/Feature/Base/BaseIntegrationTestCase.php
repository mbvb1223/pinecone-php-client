<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Tests\Feature\Base;

use Mbvb1223\Pinecone\Pinecone;
use PHPUnit\Framework\TestCase;

class BaseIntegrationTestCase extends TestCase
{
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
