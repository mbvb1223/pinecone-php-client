<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Mbvb1223\Pinecone\Control\ControlPlane;
use Mbvb1223\Pinecone\Data\Index;
use Mbvb1223\Pinecone\Errors\PineconeApiException;
use Mbvb1223\Pinecone\Errors\PineconeException;
use Mbvb1223\Pinecone\Utils\Configuration;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class IndexTest extends TestCase
{
    private Index $index;
    private MockInterface $httpClientMock;

    protected function setUp(): void
    {
        $this->httpClientMock = Mockery::mock(Client::class);
        $this->index = new Index($this->httpClientMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testDescribeIndexStatsThrowsException(): void
    {
        $this->httpClientMock->shouldReceive('get')
            ->once()
            ->with('//describe_index_stats')
            ->andThrow(new RequestException('Network error', new Request('GET', '/indexes')));

        $this->expectException(PineconeException::class);

        $this->index->describeIndexStats();
    }
}
