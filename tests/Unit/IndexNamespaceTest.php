<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Tests\Unit;

use GuzzleHttp\Client;
use Mbvb1223\Pinecone\Data\DataPlane;
use Mbvb1223\Pinecone\Data\IndexNamespace;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class IndexNamespaceTest extends TestCase
{
    private IndexNamespace $indexNamespace;
    private MockInterface $httpClientMock;

    protected function setUp(): void
    {
        $this->httpClientMock = Mockery::mock(Client::class);
        $dataPlane = new DataPlane($this->httpClientMock);
        $this->indexNamespace = new IndexNamespace($dataPlane, 'test-ns');
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testUpsertPassesNamespace(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"upsertedCount":1}');
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/vectors/upsert', Mockery::on(function ($arg) {
                return $arg['json']['namespace'] === 'test-ns'
                    && count($arg['json']['vectors']) === 1;
            }))
            ->andReturn($response);

        $result = $this->indexNamespace->upsert([['id' => 'v1', 'values' => [0.1]]]);
        $this->assertEquals(1, $result['upsertedCount']);
    }

    public function testQueryPassesNamespace(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"matches":[]}');
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/query', Mockery::on(function ($arg) {
                return $arg['json']['namespace'] === 'test-ns';
            }))
            ->andReturn($response);

        $result = $this->indexNamespace->query(vector: [0.1, 0.2]);
        $this->assertEmpty($result['matches']);
    }

    public function testQueryWithSparseVector(): void
    {
        $sparseVector = ['indices' => [0, 3], 'values' => [0.5, 0.8]];
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"matches":[]}');
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/query', Mockery::on(function ($arg) use ($sparseVector) {
                return $arg['json']['sparseVector'] === $sparseVector
                    && $arg['json']['namespace'] === 'test-ns';
            }))
            ->andReturn($response);

        $result = $this->indexNamespace->query(vector: [0.1], sparseVector: $sparseVector);
        $this->assertEmpty($result['matches']);
    }

    public function testFetchPassesNamespace(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"vectors":{"v1":{"id":"v1"}}}');
        $this->httpClientMock->shouldReceive('get')
            ->once()
            ->with(Mockery::pattern('/ids=v1&namespace=test-ns/'))
            ->andReturn($response);

        $result = $this->indexNamespace->fetch(['v1']);
        $this->assertArrayHasKey('v1', $result);
    }

    public function testDeletePassesNamespace(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{}');
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/vectors/delete', Mockery::on(function ($arg) {
                return $arg['json']['ids'] === ['v1']
                    && $arg['json']['namespace'] === 'test-ns';
            }))
            ->andReturn($response);

        $result = $this->indexNamespace->delete(ids: ['v1']);
        $this->assertIsArray($result);
    }

    public function testDeleteAllPassesNamespace(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{}');
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/vectors/delete', Mockery::on(function ($arg) {
                return $arg['json']['deleteAll'] === true
                    && $arg['json']['namespace'] === 'test-ns';
            }))
            ->andReturn($response);

        $result = $this->indexNamespace->delete(deleteAll: true);
        $this->assertIsArray($result);
    }

    public function testUpdatePassesNamespaceAndSparseValues(): void
    {
        $sparseValues = ['indices' => [0, 5], 'values' => [0.1, 0.9]];
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{}');
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/vectors/update', Mockery::on(function ($arg) use ($sparseValues) {
                return $arg['json']['id'] === 'v1'
                    && $arg['json']['namespace'] === 'test-ns'
                    && $arg['json']['sparseValues'] === $sparseValues;
            }))
            ->andReturn($response);

        $result = $this->indexNamespace->update('v1', sparseValues: $sparseValues);
        $this->assertIsArray($result);
    }

    public function testUpdateWithMetadata(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{}');
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/vectors/update', Mockery::on(function ($arg) {
                return $arg['json']['id'] === 'v1'
                    && $arg['json']['setMetadata'] === ['key' => 'val']
                    && $arg['json']['namespace'] === 'test-ns';
            }))
            ->andReturn($response);

        $result = $this->indexNamespace->update('v1', setMetadata: ['key' => 'val']);
        $this->assertIsArray($result);
    }

    public function testListVectorIdsPassesNamespace(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"vectors":[{"id":"v1"}]}');
        $this->httpClientMock->shouldReceive('get')
            ->once()
            ->with('/vectors/list?namespace=test-ns')
            ->andReturn($response);

        $result = $this->indexNamespace->listVectorIds();
        $this->assertCount(1, $result['vectors']);
    }

    public function testListVectorIdsWithPrefixAndLimit(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"vectors":[]}');
        $this->httpClientMock->shouldReceive('get')
            ->once()
            ->with('/vectors/list?prefix=doc&limit=5&namespace=test-ns')
            ->andReturn($response);

        $result = $this->indexNamespace->listVectorIds(prefix: 'doc', limit: 5);
        $this->assertEmpty($result['vectors']);
    }
}
