<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Mbvb1223\Pinecone\Data\DataPlane;
use Mbvb1223\Pinecone\Errors\PineconeException;
use Mbvb1223\Pinecone\Errors\PineconeValidationException;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class DataPlaneTest extends TestCase
{
    private DataPlane $dataPlane;
    private MockInterface $httpClientMock;

    protected function setUp(): void
    {
        $this->httpClientMock = Mockery::mock(Client::class);
        $this->dataPlane = new DataPlane($this->httpClientMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    // ===== upsert =====

    public function testUpsertSuccess(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"upsertedCount":2}');
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/vectors/upsert', Mockery::on(function ($arg) {
                return count($arg['json']['vectors']) === 2
                    && !isset($arg['json']['namespace']);
            }))
            ->andReturn($response);

        $result = $this->dataPlane->upsert([
            ['id' => 'v1', 'values' => [0.1, 0.2]],
            ['id' => 'v2', 'values' => [0.3, 0.4]],
        ]);
        $this->assertEquals(2, $result['upsertedCount']);
    }

    public function testUpsertWithNamespace(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"upsertedCount":1}');
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/vectors/upsert', Mockery::on(function ($arg) {
                return $arg['json']['namespace'] === 'test-ns';
            }))
            ->andReturn($response);

        $result = $this->dataPlane->upsert([['id' => 'v1', 'values' => [0.1]]], 'test-ns');
        $this->assertEquals(1, $result['upsertedCount']);
    }

    public function testUpsertThrowsException(): void
    {
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->andThrow(new RequestException('Network error', new Request('POST', '/vectors/upsert')));

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Failed to upsert vectors: Network error');

        $this->dataPlane->upsert([['id' => 'v1', 'values' => [0.1]]]);
    }

    // ===== query =====

    public function testQueryByVector(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"matches":[{"id":"v1","score":0.95}]}');
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/query', Mockery::on(function ($arg) {
                return $arg['json']['vector'] === [0.1, 0.2]
                    && $arg['json']['topK'] === 5;
            }))
            ->andReturn($response);

        $result = $this->dataPlane->query(vector: [0.1, 0.2], topK: 5);
        $this->assertEquals(0.95, $result['matches'][0]['score']);
    }

    public function testQueryById(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"matches":[{"id":"v2","score":0.8}]}');
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/query', Mockery::on(function ($arg) {
                return $arg['json']['id'] === 'v1'
                    && !isset($arg['json']['vector']);
            }))
            ->andReturn($response);

        $result = $this->dataPlane->query(id: 'v1', topK: 3);
        $this->assertCount(1, $result['matches']);
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
                return $arg['json']['sparseVector'] === $sparseVector;
            }))
            ->andReturn($response);

        $result = $this->dataPlane->query(vector: [0.1, 0.2], sparseVector: $sparseVector);
        $this->assertEmpty($result['matches']);
    }

    public function testQueryThrowsException(): void
    {
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->andThrow(new RequestException('Network error', new Request('POST', '/query')));

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Failed to query vectors: Network error');

        $this->dataPlane->query(vector: [0.1]);
    }

    // ===== fetch =====

    public function testFetchSuccess(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"vectors":{"v1":{"id":"v1","values":[0.1]},"v2":{"id":"v2","values":[0.2]}}}');
        $this->httpClientMock->shouldReceive('get')
            ->once()
            ->with(Mockery::pattern('/\/vectors\/fetch\?ids=v1&ids=v2/'))
            ->andReturn($response);

        $result = $this->dataPlane->fetch(['v1', 'v2']);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('v1', $result);
    }

    public function testFetchThrowsException(): void
    {
        $this->httpClientMock->shouldReceive('get')
            ->once()
            ->andThrow(new RequestException('Network error', new Request('GET', '/vectors/fetch')));

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Failed to fetch vectors: Network error');

        $this->dataPlane->fetch(['v1']);
    }

    // ===== delete =====

    public function testDeleteByIds(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{}');
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/vectors/delete', Mockery::on(function ($arg) {
                return $arg['json']['ids'] === ['v1', 'v2'];
            }))
            ->andReturn($response);

        $result = $this->dataPlane->delete(ids: ['v1', 'v2']);
        $this->assertIsArray($result);
    }

    public function testDeleteAll(): void
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

        $result = $this->dataPlane->delete(namespace: 'test-ns', deleteAll: true);
        $this->assertIsArray($result);
    }

    public function testDeleteThrowsException(): void
    {
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->andThrow(new RequestException('Network error', new Request('POST', '/vectors/delete')));

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Failed to delete vectors: Network error');

        $this->dataPlane->delete(ids: ['v1']);
    }

    // ===== update =====

    public function testUpdateSuccess(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{}');
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/vectors/update', Mockery::on(function ($arg) {
                return $arg['json']['id'] === 'v1'
                    && $arg['json']['values'] === [0.5, 0.6];
            }))
            ->andReturn($response);

        $result = $this->dataPlane->update('v1', [0.5, 0.6]);
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
                    && $arg['json']['setMetadata'] === ['genre' => 'comedy'];
            }))
            ->andReturn($response);

        $result = $this->dataPlane->update('v1', setMetadata: ['genre' => 'comedy']);
        $this->assertIsArray($result);
    }

    public function testUpdateWithSparseValues(): void
    {
        $sparseValues = ['indices' => [0, 3, 7], 'values' => [0.1, 0.5, 0.9]];
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{}');
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/vectors/update', Mockery::on(function ($arg) use ($sparseValues) {
                return $arg['json']['id'] === 'v1'
                    && $arg['json']['sparseValues'] === $sparseValues;
            }))
            ->andReturn($response);

        $result = $this->dataPlane->update('v1', sparseValues: $sparseValues);
        $this->assertIsArray($result);
    }

    public function testUpdateWithAllParams(): void
    {
        $sparseValues = ['indices' => [1], 'values' => [0.5]];
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{}');
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/vectors/update', Mockery::on(function ($arg) use ($sparseValues) {
                $json = $arg['json'];

                return $json['id'] === 'v1'
                    && $json['values'] === [0.5, 0.6]
                    && $json['setMetadata'] === ['genre' => 'drama']
                    && $json['namespace'] === 'ns1'
                    && $json['sparseValues'] === $sparseValues;
            }))
            ->andReturn($response);

        $result = $this->dataPlane->update(
            'v1',
            [0.5, 0.6],
            ['genre' => 'drama'],
            'ns1',
            $sparseValues
        );
        $this->assertIsArray($result);
    }

    public function testUpdateWithNullSparseValuesOmitsKey(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{}');
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/vectors/update', Mockery::on(function ($arg) {
                return !isset($arg['json']['sparseValues']);
            }))
            ->andReturn($response);

        $result = $this->dataPlane->update('v1', [0.1]);
        $this->assertIsArray($result);
    }

    public function testUpdateThrowsException(): void
    {
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->andThrow(new RequestException('Network error', new Request('POST', '/vectors/update')));

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Failed to update vector: Network error');

        $this->dataPlane->update('v1', [0.1]);
    }

    // ===== listVectorIds =====

    public function testListVectorIdsSuccess(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"vectors":[{"id":"v1"},{"id":"v2"}],"pagination":{"next":"token123"}}');
        $this->httpClientMock->shouldReceive('get')
            ->once()
            ->with('/vectors/list?prefix=doc1%23&limit=10&namespace=test-ns')
            ->andReturn($response);

        $result = $this->dataPlane->listVectorIds(prefix: 'doc1#', limit: 10, namespace: 'test-ns');
        $this->assertCount(2, $result['vectors']);
        $this->assertEquals('token123', $result['pagination']['next']);
    }

    public function testListVectorIdsNoParams(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"vectors":[]}');
        $this->httpClientMock->shouldReceive('get')
            ->once()
            ->with('/vectors/list')
            ->andReturn($response);

        $result = $this->dataPlane->listVectorIds();
        $this->assertEmpty($result['vectors']);
    }

    public function testListVectorIdsWithPaginationToken(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"vectors":[{"id":"v3"}]}');
        $this->httpClientMock->shouldReceive('get')
            ->once()
            ->with('/vectors/list?paginationToken=token123')
            ->andReturn($response);

        $result = $this->dataPlane->listVectorIds(paginationToken: 'token123');
        $this->assertCount(1, $result['vectors']);
    }

    public function testListVectorIdsThrowsException(): void
    {
        $this->httpClientMock->shouldReceive('get')
            ->once()
            ->andThrow(new RequestException('Network error', new Request('GET', '/vectors/list')));

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Failed to list vector IDs: Network error');

        $this->dataPlane->listVectorIds();
    }

    // ===== query with filter =====

    public function testQueryWithFilter(): void
    {
        $filter = ['genre' => ['$eq' => 'comedy']];
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"matches":[{"id":"v1","score":0.9}]}');
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/query', Mockery::on(function ($arg) use ($filter) {
                return $arg['json']['filter'] === $filter
                    && $arg['json']['vector'] === [0.1, 0.2]
                    && $arg['json']['topK'] === 5;
            }))
            ->andReturn($response);

        $result = $this->dataPlane->query(vector: [0.1, 0.2], topK: 5, filter: $filter);
        $this->assertCount(1, $result['matches']);
    }

    public function testQueryWithIncludeValuesTrue(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"matches":[{"id":"v1","score":0.9,"values":[0.1,0.2]}]}');
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/query', Mockery::on(function ($arg) {
                return $arg['json']['includeValues'] === true;
            }))
            ->andReturn($response);

        $result = $this->dataPlane->query(vector: [0.1, 0.2], includeValues: true);
        $this->assertArrayHasKey('values', $result['matches'][0]);
    }

    public function testQueryWithIncludeMetadataFalse(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"matches":[{"id":"v1","score":0.9}]}');
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/query', Mockery::on(function ($arg) {
                return $arg['json']['includeMetadata'] === false;
            }))
            ->andReturn($response);

        $result = $this->dataPlane->query(vector: [0.1, 0.2], includeMetadata: false);
        $this->assertCount(1, $result['matches']);
    }

    // ===== delete with filter =====

    public function testDeleteWithFilter(): void
    {
        $filter = ['genre' => ['$eq' => 'comedy']];
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{}');
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/vectors/delete', Mockery::on(function ($arg) use ($filter) {
                return $arg['json']['filter'] === $filter
                    && !isset($arg['json']['ids'])
                    && !isset($arg['json']['deleteAll']);
            }))
            ->andReturn($response);

        $result = $this->dataPlane->delete(filter: $filter);
        $this->assertIsArray($result);
    }

    // ===== Bug fix: fetch with empty ids =====

    public function testFetchEmptyIdsThrowsValidationException(): void
    {
        $this->expectException(PineconeValidationException::class);
        $this->expectExceptionMessage('At least one vector ID is required for fetch.');

        $this->dataPlane->fetch([]);
    }

    // ===== Bug fix: delete with both ids and filter =====

    public function testDeleteWithIdsAndFilterSendsBoth(): void
    {
        $ids = ['v1', 'v2'];
        $filter = ['genre' => ['$eq' => 'comedy']];
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{}');
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/vectors/delete', Mockery::on(function ($arg) use ($ids, $filter) {
                return $arg['json']['ids'] === $ids
                    && $arg['json']['filter'] === $filter
                    && !isset($arg['json']['deleteAll']);
            }))
            ->andReturn($response);

        $result = $this->dataPlane->delete(ids: $ids, filter: $filter);
        $this->assertIsArray($result);
    }

    public function testDeleteAllIgnoresIdsAndFilter(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{}');
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/vectors/delete', Mockery::on(function ($arg) {
                return $arg['json']['deleteAll'] === true
                    && !isset($arg['json']['ids'])
                    && !isset($arg['json']['filter']);
            }))
            ->andReturn($response);

        $result = $this->dataPlane->delete(ids: ['v1'], filter: ['genre' => ['$eq' => 'comedy']], deleteAll: true);
        $this->assertIsArray($result);
    }
}
