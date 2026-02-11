<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Mbvb1223\Pinecone\Control\ControlPlane;
use Mbvb1223\Pinecone\Errors\PineconeApiException;
use Mbvb1223\Pinecone\Errors\PineconeException;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class ControlPlaneTest extends TestCase
{
    private ControlPlane $controlPlane;
    private MockInterface $httpClientMock;

    protected function setUp(): void
    {
        $this->httpClientMock = Mockery::mock(Client::class);
        $this->controlPlane = new ControlPlane($this->httpClientMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    // ===== Index control plane success tests =====

    public function testListIndexesSuccess(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"indexes":[{"name":"idx1"},{"name":"idx2"}]}');
        $this->httpClientMock->shouldReceive('get')
            ->once()
            ->with('/indexes')
            ->andReturn($response);

        $result = $this->controlPlane->listIndexes();
        $this->assertCount(2, $result);
        $this->assertEquals('idx1', $result[0]['name']);
    }

    public function testCreateIndexSuccess(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(201);
        $response->shouldReceive('getBody->getContents')->andReturn('{"name":"test-index","dimension":1536,"metric":"cosine"}');
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/indexes', Mockery::on(function ($arg) {
                $json = $arg['json'];

                return $json['name'] === 'test-index'
                    && $json['dimension'] === 1536
                    && $json['metric'] === 'cosine';
            }))
            ->andReturn($response);

        $result = $this->controlPlane->createIndex('test-index', [
            'dimension' => 1536,
            'spec' => ['serverless' => ['cloud' => 'aws', 'region' => 'us-east-1']],
        ]);
        $this->assertEquals('test-index', $result['name']);
    }

    public function testCreateIndexPreservesOptionalFields(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(201);
        $response->shouldReceive('getBody->getContents')->andReturn('{"name":"test-index","deletion_protection":"enabled"}');
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/indexes', Mockery::on(function ($arg) {
                $json = $arg['json'];

                return $json['name'] === 'test-index'
                    && $json['deletion_protection'] === 'enabled'
                    && $json['vector_type'] === 'float';
            }))
            ->andReturn($response);

        $result = $this->controlPlane->createIndex('test-index', [
            'dimension' => 1536,
            'spec' => ['serverless' => ['cloud' => 'aws', 'region' => 'us-east-1']],
            'deletion_protection' => 'enabled',
            'vector_type' => 'float',
        ]);
        $this->assertEquals('enabled', $result['deletion_protection']);
    }

    public function testDescribeIndexSuccess(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"name":"test-index","host":"test-host.pinecone.io"}');
        $this->httpClientMock->shouldReceive('get')
            ->once()
            ->with('/indexes/test-index')
            ->andReturn($response);

        $result = $this->controlPlane->describeIndex('test-index');
        $this->assertEquals('test-index', $result['name']);
        $this->assertEquals('test-host.pinecone.io', $result['host']);
    }

    public function testDeleteIndexSuccess(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(202);
        $response->shouldReceive('getBody->getContents')->andReturn('');
        $this->httpClientMock->shouldReceive('delete')
            ->once()
            ->with('/indexes/test-index')
            ->andReturn($response);

        $this->controlPlane->deleteIndex('test-index');
        $this->assertTrue(true);
    }

    public function testConfigureIndexSuccess(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"name":"test-index","deletion_protection":"enabled"}');
        $this->httpClientMock->shouldReceive('patch')
            ->once()
            ->with('/indexes/test-index', ['json' => ['deletion_protection' => 'enabled']])
            ->andReturn($response);

        $result = $this->controlPlane->configureIndex('test-index', ['deletion_protection' => 'enabled']);
        $this->assertEquals('enabled', $result['deletion_protection']);
    }

    // ===== Index control plane error tests =====

    public function testListIndexesThrowsException(): void
    {
        $this->httpClientMock->shouldReceive('get')
            ->once()
            ->with('/indexes')
            ->andThrow(new RequestException('Network error', new Request('GET', '/indexes')));

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Failed to list indexes: Network error');

        $this->controlPlane->listIndexes();
    }

    public function testCreateIndexThrowsException(): void
    {
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/indexes', Mockery::any())
            ->andThrow(new RequestException('Creation failed', new Request('POST', '/indexes')));

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Failed to create index: test-index. Creation failed');

        $this->controlPlane->createIndex('test-index', [
            'dimension' => 1536,
            'spec' => ['serverless' => ['cloud' => 'aws', 'region' => 'us-east-1']],
        ]);
    }

    public function testCreateForModelThrowsException(): void
    {
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/indexes/create-for-model', Mockery::any())
            ->andThrow(new RequestException('Model creation failed', new Request('POST', '/indexes/create-for-model')));

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Failed to create index for model: test-index. Model creation failed');

        $this->controlPlane->createForModel('test-index', [
            'cloud' => 'aws',
            'region' => 'us-east-1',
            'embed' => ['model' => 'text-embedding-ada-002'],
        ]);
    }

    public function testDescribeIndexThrowsException(): void
    {
        $this->httpClientMock->shouldReceive('get')
            ->once()
            ->with('/indexes/test-index')
            ->andThrow(new RequestException('Index not found', new Request('GET', '/indexes/test-index')));

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Failed to describe index: test-index. Index not found');

        $this->controlPlane->describeIndex('test-index');
    }

    public function testDeleteIndexThrowsException(): void
    {
        $this->httpClientMock->shouldReceive('delete')
            ->once()
            ->with('/indexes/test-index')
            ->andThrow(new RequestException('Delete failed', new Request('DELETE', '/indexes/test-index')));

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Failed to delete index: test-index. Delete failed');

        $this->controlPlane->deleteIndex('test-index');
    }

    public function testConfigureIndexThrowsException(): void
    {
        $this->httpClientMock->shouldReceive('patch')
            ->once()
            ->with('/indexes/test-index', ['json' => ['replicas' => 2]])
            ->andThrow(new RequestException('Configure failed', new Request('PATCH', '/indexes/test-index')));

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Failed to configure index: test-index. Configure failed');

        $this->controlPlane->configureIndex('test-index', ['replicas' => 2]);
    }

    // ===== Collection control plane method tests =====

    public function testCreateCollectionThrowsException(): void
    {
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/collections', ['json' => ['name' => 'test-collection']])
            ->andThrow(new RequestException('Collection creation failed', new Request('POST', '/collections')));

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Failed to create collection: Collection creation failed');

        $this->controlPlane->createCollection(['name' => 'test-collection']);
    }

    public function testListCollectionsThrowsException(): void
    {
        $this->httpClientMock->shouldReceive('get')
            ->once()
            ->with('/collections')
            ->andThrow(new RequestException('List failed', new Request('GET', '/collections')));

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Failed to list collections: List failed');

        $this->controlPlane->listCollections();
    }

    public function testDescribeCollectionThrowsException(): void
    {
        $this->httpClientMock->shouldReceive('get')
            ->once()
            ->with('/collections/test-collection')
            ->andThrow(new RequestException('Collection not found', new Request('GET', '/collections/test-collection')));

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Failed to describe collection: test-collection. Collection not found');

        $this->controlPlane->describeCollection('test-collection');
    }

    public function testDeleteCollectionThrowsException(): void
    {
        $this->httpClientMock->shouldReceive('delete')
            ->once()
            ->with('/collections/test-collection')
            ->andThrow(new RequestException('Delete failed', new Request('DELETE', '/collections/test-collection')));

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Failed to delete collection: test-collection. Delete failed');

        $this->controlPlane->deleteCollection('test-collection');
    }

    // ===== Backup control plane method tests =====

    public function testCreateBackupThrowsException(): void
    {
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/backups', ['json' => ['source' => 'test-index']])
            ->andThrow(new RequestException('Backup creation failed', new Request('POST', '/backups')));

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Failed to create backup: Backup creation failed');

        $this->controlPlane->createBackup(['source' => 'test-index']);
    }

    public function testListBackupsThrowsException(): void
    {
        $this->httpClientMock->shouldReceive('get')
            ->once()
            ->with('/backups')
            ->andThrow(new RequestException('List failed', new Request('GET', '/backups')));

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Failed to list backups: List failed');

        $this->controlPlane->listBackups();
    }

    public function testDescribeBackupThrowsException(): void
    {
        $this->httpClientMock->shouldReceive('get')
            ->once()
            ->with('/backups/backup-123')
            ->andThrow(new RequestException('Backup not found', new Request('GET', '/backups/backup-123')));

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Failed to describe backup: backup-123. Backup not found');

        $this->controlPlane->describeBackup('backup-123');
    }

    public function testDeleteBackupThrowsException(): void
    {
        $this->httpClientMock->shouldReceive('delete')
            ->once()
            ->with('/backups/backup-123')
            ->andThrow(new RequestException('Delete failed', new Request('DELETE', '/backups/backup-123')));

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Failed to delete backup: backup-123. Delete failed');

        $this->controlPlane->deleteBackup('backup-123');
    }

    // ===== Restore control plane method tests =====

    public function testListRestoreJobsThrowsException(): void
    {
        $this->httpClientMock->shouldReceive('get')
            ->once()
            ->with('/restore?status=active')
            ->andThrow(new RequestException('List failed', new Request('GET', '/restore')));

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Failed to list restore jobs: List failed');

        $this->controlPlane->listRestoreJobs(['status' => 'active']);
    }

    public function testListRestoreJobsWithoutParamsThrowsException(): void
    {
        $this->httpClientMock->shouldReceive('get')
            ->once()
            ->with('/restore')
            ->andThrow(new RequestException('List failed', new Request('GET', '/restore')));

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Failed to list restore jobs: List failed');

        $this->controlPlane->listRestoreJobs();
    }

    public function testDescribeRestoreJobThrowsException(): void
    {
        $this->httpClientMock->shouldReceive('get')
            ->once()
            ->with('/restore/job-123')
            ->andThrow(new RequestException('Job not found', new Request('GET', '/restore/job-123')));

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Failed to describe restore job: job-123. Job not found');

        $this->controlPlane->describeRestoreJob('job-123');
    }

    // ===== Assistant control plane method tests =====

    public function testCreateAssistantThrowsException(): void
    {
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/assistants', ['json' => ['name' => 'test-assistant']])
            ->andThrow(new RequestException('Assistant creation failed', new Request('POST', '/assistants')));

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Failed to create assistant: Assistant creation failed');

        $this->controlPlane->createAssistant(['name' => 'test-assistant']);
    }

    public function testListAssistantsThrowsException(): void
    {
        $this->httpClientMock->shouldReceive('get')
            ->once()
            ->with('/assistants')
            ->andThrow(new RequestException('List failed', new Request('GET', '/assistants')));

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Failed to list assistants: List failed');

        $this->controlPlane->listAssistants();
    }

    public function testDescribeAssistantThrowsException(): void
    {
        $this->httpClientMock->shouldReceive('get')
            ->once()
            ->with('/assistants/test-assistant')
            ->andThrow(new RequestException('Assistant not found', new Request('GET', '/assistants/test-assistant')));

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Failed to describe assistant: test-assistant. Assistant not found');

        $this->controlPlane->describeAssistant('test-assistant');
    }

    public function testUpdateAssistantThrowsException(): void
    {
        $this->httpClientMock->shouldReceive('patch')
            ->once()
            ->with('/assistants/test-assistant', ['json' => ['description' => 'Updated']])
            ->andThrow(new RequestException('Update failed', new Request('PATCH', '/assistants/test-assistant')));

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Failed to update assistant: test-assistant. Update failed');

        $this->controlPlane->updateAssistant('test-assistant', ['description' => 'Updated']);
    }

    public function testDeleteAssistantThrowsException(): void
    {
        $this->httpClientMock->shouldReceive('delete')
            ->once()
            ->with('/assistants/test-assistant')
            ->andThrow(new RequestException('Delete failed', new Request('DELETE', '/assistants/test-assistant')));

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Failed to delete assistant: test-assistant. Delete failed');

        $this->controlPlane->deleteAssistant('test-assistant');
    }

    public function testResponseIs500(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(500);
        $response->shouldReceive('getBody->getContents')->andReturn('{"message":"Internal Server Error"}');
        $this->httpClientMock->shouldReceive('get')
            ->once()
            ->with('/indexes')
            ->andReturn($response);

        $this->expectException(PineconeApiException::class);
        $this->expectExceptionCode(500);

        $this->controlPlane->listIndexes();
    }

    public function testResponseDataError(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(500);
        $response->shouldReceive('getBody->getContents')->andReturn('{"message":"Internal Server Error"}');
        $this->httpClientMock->shouldReceive('get')
            ->once()
            ->with('/indexes')
            ->andReturn($response);

        try {
            $this->controlPlane->listIndexes();
        } catch (PineconeApiException $e) {
            $this->assertEquals(500, $e->getCode());
            $this->assertEquals(['message' => 'Internal Server Error'], $e->getResponseData());
        }
    }

    public function testResponseIsEmpty(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{}');
        $this->httpClientMock->shouldReceive('get')
            ->once()
            ->with('/indexes')
            ->andReturn($response);

        $this->assertEmpty($this->controlPlane->listIndexes());
    }

    public function testResponseIsNotJson(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('xxx');
        $this->httpClientMock->shouldReceive('get')
            ->once()
            ->with('/indexes')
            ->andReturn($response);

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessageMatches('/^Failed to decode JSON response:/');

        $this->controlPlane->listIndexes();
    }
}
