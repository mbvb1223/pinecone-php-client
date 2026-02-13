<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Mbvb1223\Pinecone\Data\Index;
use Mbvb1223\Pinecone\Errors\PineconeException;
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

    // ===== describeIndexStats =====

    public function testDescribeIndexStatsSuccess(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"namespaces":{"ns1":{"vectorCount":10}},"dimension":1536,"totalVectorCount":10}');
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/describe_index_stats', Mockery::any())
            ->andReturn($response);

        $result = $this->index->describeIndexStats();
        $this->assertEquals(1536, $result['dimension']);
        $this->assertEquals(10, $result['totalVectorCount']);
    }

    public function testDescribeIndexStatsThrowsException(): void
    {
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->andThrow(new RequestException('Network error', new Request('GET', '/indexes')));

        $this->expectException(PineconeException::class);

        $this->index->describeIndexStats();
    }

    // ===== listNamespaces =====

    public function testListNamespacesSuccess(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"namespaces":{"ns1":{"vectorCount":5},"ns2":{"vectorCount":3}}}');
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/describe_index_stats', Mockery::on(function ($arg) {
                return isset($arg['json']) && $arg['json'] instanceof \stdClass;
            }))
            ->andReturn($response);

        $result = $this->index->listNamespaces();
        $this->assertCount(2, $result);
        $this->assertContains('ns1', $result);
        $this->assertContains('ns2', $result);
    }

    public function testListNamespacesUsesPost(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"namespaces":{}}');
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/describe_index_stats', Mockery::any())
            ->andReturn($response);

        $result = $this->index->listNamespaces();
        $this->assertEmpty($result);
    }

    public function testListNamespacesThrowsException(): void
    {
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->andThrow(new RequestException('Network error', new Request('POST', '/describe_index_stats')));

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Failed to list namespaces: Network error');

        $this->index->listNamespaces();
    }

    // ===== describeNamespace =====

    public function testDescribeNamespaceSuccess(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"namespaces":{"ns1":{"vectorCount":42}}}');
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/describe_index_stats', Mockery::on(function ($arg) {
                return isset($arg['json']) && $arg['json'] instanceof \stdClass;
            }))
            ->andReturn($response);

        $result = $this->index->describeNamespace('ns1');
        $this->assertEquals(['vectorCount' => 42], $result);
    }

    public function testDescribeNamespaceNotFound(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"namespaces":{"ns1":{"vectorCount":42}}}');
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/describe_index_stats', Mockery::any())
            ->andReturn($response);

        $result = $this->index->describeNamespace('nonexistent');
        $this->assertEmpty($result);
    }

    // ===== deleteNamespace =====

    public function testDeleteNamespaceSuccess(): void
    {
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/vectors/delete', ['json' => ['deleteAll' => true, 'namespace' => 'ns1']])
            ->andReturn(Mockery::mock(ResponseInterface::class));

        $this->index->deleteNamespace('ns1');
        $this->assertTrue(true);
    }

    // ===== proxy methods =====

    public function testUpsertProxy(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"upsertedCount":1}');
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/vectors/upsert', Mockery::any())
            ->andReturn($response);

        $result = $this->index->upsert([['id' => 'v1', 'values' => [0.1, 0.2]]]);
        $this->assertEquals(1, $result['upsertedCount']);
    }

    public function testQueryProxy(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"matches":[{"id":"v1","score":0.9}]}');
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/query', Mockery::any())
            ->andReturn($response);

        $result = $this->index->query(vector: [0.1, 0.2], topK: 5);
        $this->assertCount(1, $result['matches']);
    }

    public function testFetchProxy(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"vectors":{"v1":{"id":"v1","values":[0.1]}}}');
        $this->httpClientMock->shouldReceive('get')
            ->once()
            ->with(Mockery::pattern('/\/vectors\/fetch\?ids=v1/'))
            ->andReturn($response);

        $result = $this->index->fetch(['v1']);
        $this->assertArrayHasKey('v1', $result);
    }

    public function testListVectorIdsProxy(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"vectors":[{"id":"v1"},{"id":"v2"}]}');
        $this->httpClientMock->shouldReceive('get')
            ->once()
            ->with(Mockery::pattern('/\/vectors\/list/'))
            ->andReturn($response);

        $result = $this->index->listVectorIds(prefix: 'v');
        $this->assertCount(2, $result['vectors']);
    }

    // ===== Import operations =====

    public function testStartImportSuccess(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"id":"import-123"}');
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/bulk/imports', ['json' => ['uri' => 's3://bucket/data.parquet']])
            ->andReturn($response);

        $result = $this->index->startImport(['uri' => 's3://bucket/data.parquet']);
        $this->assertEquals('import-123', $result['id']);
    }

    public function testStartImportThrowsException(): void
    {
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->andThrow(new RequestException('Network error', new Request('POST', '/bulk/imports')));

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Failed to start import: Network error');

        $this->index->startImport(['uri' => 's3://bucket/data.parquet']);
    }

    public function testListImportsNoParams(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"data":[{"id":"imp1"}]}');
        $this->httpClientMock->shouldReceive('get')
            ->once()
            ->with('/bulk/imports')
            ->andReturn($response);

        $result = $this->index->listImports();
        $this->assertArrayHasKey('data', $result);
    }

    public function testListImportsWithLimit(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"data":[]}');
        $this->httpClientMock->shouldReceive('get')
            ->once()
            ->with('/bulk/imports?limit=5')
            ->andReturn($response);

        $result = $this->index->listImports(limit: 5);
        $this->assertEmpty($result['data']);
    }

    public function testListImportsWithPaginationToken(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"data":[]}');
        $this->httpClientMock->shouldReceive('get')
            ->once()
            ->with('/bulk/imports?paginationToken=token123')
            ->andReturn($response);

        $result = $this->index->listImports(paginationToken: 'token123');
        $this->assertEmpty($result['data']);
    }

    public function testListImportsWithAllParams(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"data":[],"pagination":{"next":"token456"}}');
        $this->httpClientMock->shouldReceive('get')
            ->once()
            ->with('/bulk/imports?limit=10&paginationToken=token123')
            ->andReturn($response);

        $result = $this->index->listImports(limit: 10, paginationToken: 'token123');
        $this->assertArrayHasKey('pagination', $result);
    }

    public function testListImportsThrowsException(): void
    {
        $this->httpClientMock->shouldReceive('get')
            ->once()
            ->andThrow(new RequestException('Error', new Request('GET', '/bulk/imports')));

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Failed to list imports: Error');

        $this->index->listImports();
    }

    public function testDescribeImportSuccess(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"id":"imp-123","status":"Completed"}');
        $this->httpClientMock->shouldReceive('get')
            ->once()
            ->with('/bulk/imports/imp-123')
            ->andReturn($response);

        $result = $this->index->describeImport('imp-123');
        $this->assertEquals('Completed', $result['status']);
    }

    public function testDescribeImportUrlEncodes(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"id":"imp 123"}');
        $this->httpClientMock->shouldReceive('get')
            ->once()
            ->with('/bulk/imports/imp+123')
            ->andReturn($response);

        $result = $this->index->describeImport('imp 123');
        $this->assertEquals('imp 123', $result['id']);
    }

    public function testDescribeImportThrowsException(): void
    {
        $this->httpClientMock->shouldReceive('get')
            ->once()
            ->andThrow(new RequestException('Not found', new Request('GET', '/bulk/imports/imp-123')));

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Failed to describe import: Not found');

        $this->index->describeImport('imp-123');
    }

    public function testCancelImportSuccess(): void
    {
        $this->httpClientMock->shouldReceive('delete')
            ->once()
            ->with('/bulk/imports/imp-123')
            ->andReturn(Mockery::mock(ResponseInterface::class));

        $this->index->cancelImport('imp-123');
        $this->assertTrue(true);
    }

    public function testCancelImportUrlEncodes(): void
    {
        $this->httpClientMock->shouldReceive('delete')
            ->once()
            ->with('/bulk/imports/imp+123')
            ->andReturn(Mockery::mock(ResponseInterface::class));

        $this->index->cancelImport('imp 123');
        $this->assertTrue(true);
    }

    public function testCancelImportThrowsException(): void
    {
        $this->httpClientMock->shouldReceive('delete')
            ->once()
            ->andThrow(new RequestException('Error', new Request('DELETE', '/bulk/imports/imp-123')));

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Failed to cancel import: Error');

        $this->index->cancelImport('imp-123');
    }

    // ===== Update proxy with sparseValues =====

    public function testUpdateProxyWithSparseValues(): void
    {
        $sparseValues = ['indices' => [0, 5], 'values' => [0.1, 0.9]];
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

        $result = $this->index->update('v1', sparseValues: $sparseValues);
        $this->assertIsArray($result);
    }

    public function testDeleteProxy(): void
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

        $result = $this->index->delete(ids: ['v1', 'v2']);
        $this->assertIsArray($result);
    }

    // ===== namespace proxy =====

    public function testNamespaceReturnsIndexNamespace(): void
    {
        $ns = $this->index->namespace('test-ns');
        $this->assertInstanceOf(\Mbvb1223\Pinecone\Data\IndexNamespace::class, $ns);
    }

    // ===== describeIndexStats with filter =====

    public function testDescribeIndexStatsWithFilter(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"dimension":1536,"totalVectorCount":5}');
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/describe_index_stats', Mockery::on(function ($arg) {
                return isset($arg['json']->filter);
            }))
            ->andReturn($response);

        $result = $this->index->describeIndexStats(['genre' => 'comedy']);
        $this->assertEquals(5, $result['totalVectorCount']);
    }
}
