<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Tests\Unit;

use Mbvb1223\Pinecone\Assistant\AssistantClient;
use Mbvb1223\Pinecone\Control\ControlPlane;
use Mbvb1223\Pinecone\Data\Index;
use Mbvb1223\Pinecone\Errors\PineconeException;
use Mbvb1223\Pinecone\Errors\PineconeValidationException;
use Mbvb1223\Pinecone\Inference\InferenceClient;
use Mbvb1223\Pinecone\Pinecone;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class PineconeTest extends TestCase
{
    // ===== Constructor validation =====

    public function testConstructorWithApiKey(): void
    {
        $pinecone = new Pinecone('my-key');
        $this->assertInstanceOf(Pinecone::class, $pinecone);
    }

    public function testConstructorWithConfig(): void
    {
        $pinecone = new Pinecone('my-key', ['timeout' => 60]);
        $this->assertInstanceOf(Pinecone::class, $pinecone);
    }

    public function testConstructorEmptyApiKeyThrows(): void
    {
        $this->expectException(PineconeValidationException::class);
        $this->expectExceptionMessage('API key is required.');

        new Pinecone('');
    }

    public function testConstructorInvalidTimeoutThrows(): void
    {
        $this->expectException(PineconeValidationException::class);
        $this->expectExceptionMessage('Timeout must be a positive integer.');

        new Pinecone('test-key', ['timeout' => 0]);
    }

    public function testConstructorInvalidHostThrows(): void
    {
        $this->expectException(PineconeValidationException::class);
        $this->expectExceptionMessage('Controller host must be a valid URL');

        new Pinecone('test-key', ['controllerHost' => 'not-a-url']);
    }

    // ===== index() input validation =====

    public function testIndexEmptyNameThrows(): void
    {
        $pinecone = new Pinecone('test-api-key');

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Index name must not be empty.');

        $pinecone->index('');
    }

    public function testIndexWhitespaceOnlyNameThrows(): void
    {
        $pinecone = new Pinecone('test-api-key');

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Index name must not be empty.');

        $pinecone->index('   ');
    }

    public function testIndexTabNameThrows(): void
    {
        $pinecone = new Pinecone('test-api-key');

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Index name must not be empty.');

        $pinecone->index("\t");
    }

    // ===== inference() lazy initialization =====

    public function testInferenceReturnsClient(): void
    {
        $pinecone = new Pinecone('test-api-key');
        $inference = $pinecone->inference();
        $this->assertInstanceOf(InferenceClient::class, $inference);
    }

    public function testInferenceLazyInitialization(): void
    {
        $pinecone = new Pinecone('test-api-key');
        $inference1 = $pinecone->inference();
        $inference2 = $pinecone->inference();
        $this->assertSame($inference1, $inference2);
    }

    // ===== assistant() input validation =====

    public function testAssistantEmptyNameThrows(): void
    {
        $pinecone = new Pinecone('test-api-key');

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Assistant name must not be empty.');

        $pinecone->assistant('');
    }

    public function testAssistantWhitespaceNameThrows(): void
    {
        $pinecone = new Pinecone('test-api-key');

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Assistant name must not be empty.');

        $pinecone->assistant('   ');
    }

    public function testAssistantTabNameThrows(): void
    {
        $pinecone = new Pinecone('test-api-key');

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Assistant name must not be empty.');

        $pinecone->assistant("\t\n");
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    // ===== index() success path =====

    public function testIndexReturnsIndexWhenHostPresent(): void
    {
        $pinecone = Mockery::mock(Pinecone::class, ['test-api-key'])->makePartial();
        $pinecone->shouldReceive('describeIndex')
            ->once()
            ->with('my-index')
            ->andReturn(['name' => 'my-index', 'host' => 'my-index-abc123.svc.pinecone.io']);

        $index = $pinecone->index('my-index');

        $this->assertInstanceOf(Index::class, $index);
    }

    // ===== index() caching =====

    public function testIndexCachesResultOnSubsequentCalls(): void
    {
        $pinecone = Mockery::mock(Pinecone::class, ['test-api-key'])->makePartial();
        $pinecone->shouldReceive('describeIndex')
            ->once()
            ->with('cached-index')
            ->andReturn(['name' => 'cached-index', 'host' => 'cached-index-host.svc.pinecone.io']);

        $index1 = $pinecone->index('cached-index');
        $index2 = $pinecone->index('cached-index');

        $this->assertSame($index1, $index2);
    }

    // ===== index() host missing error =====

    public function testIndexThrowsWhenHostMissing(): void
    {
        $pinecone = Mockery::mock(Pinecone::class, ['test-api-key'])->makePartial();
        $pinecone->shouldReceive('describeIndex')
            ->once()
            ->with('no-host-index')
            ->andReturn(['name' => 'no-host-index']);

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage("Index 'no-host-index' does not have a host URL.");

        $pinecone->index('no-host-index');
    }

    public function testIndexThrowsWhenHostIsNull(): void
    {
        $pinecone = Mockery::mock(Pinecone::class, ['test-api-key'])->makePartial();
        $pinecone->shouldReceive('describeIndex')
            ->once()
            ->with('null-host-index')
            ->andReturn(['name' => 'null-host-index', 'host' => null]);

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage("Index 'null-host-index' does not have a host URL.");

        $pinecone->index('null-host-index');
    }

    public function testIndexThrowsWhenHostIsEmptyString(): void
    {
        $pinecone = Mockery::mock(Pinecone::class, ['test-api-key'])->makePartial();
        $pinecone->shouldReceive('describeIndex')
            ->once()
            ->with('empty-host-index')
            ->andReturn(['name' => 'empty-host-index', 'host' => '']);

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage("Index 'empty-host-index' does not have a host URL.");

        $pinecone->index('empty-host-index');
    }

    // ===== assistant() success path =====

    public function testAssistantReturnsAssistantClient(): void
    {
        $pinecone = Mockery::mock(Pinecone::class, ['test-api-key'])->makePartial();
        $pinecone->shouldReceive('describeAssistant')
            ->once()
            ->with('my-assistant')
            ->andReturn(['name' => 'my-assistant', 'status' => 'Ready', 'host' => 'assistant-host.pinecone.io']);

        $assistant = $pinecone->assistant('my-assistant');

        $this->assertInstanceOf(AssistantClient::class, $assistant);
    }

    public function testAssistantReturnsNewInstanceEachCall(): void
    {
        $pinecone = Mockery::mock(Pinecone::class, ['test-api-key'])->makePartial();
        $pinecone->shouldReceive('describeAssistant')
            ->twice()
            ->with('my-assistant')
            ->andReturn(['name' => 'my-assistant', 'status' => 'Ready']);

        $assistant1 = $pinecone->assistant('my-assistant');
        $assistant2 = $pinecone->assistant('my-assistant');

        $this->assertInstanceOf(AssistantClient::class, $assistant1);
        $this->assertInstanceOf(AssistantClient::class, $assistant2);
        $this->assertNotSame($assistant1, $assistant2);
    }

    // ===== hasIndex() =====

    public function testHasIndexReturnsTrueWhenIndexExists(): void
    {
        $pinecone = Mockery::mock(Pinecone::class, ['test-api-key'])->makePartial();
        $pinecone->shouldReceive('describeIndex')
            ->once()
            ->with('existing-index')
            ->andReturn(['name' => 'existing-index', 'host' => 'existing-index.svc.pinecone.io']);

        $this->assertTrue($pinecone->hasIndex('existing-index'));
    }

    public function testHasIndexReturnsFalseWhenIndexDoesNotExist(): void
    {
        $pinecone = Mockery::mock(Pinecone::class, ['test-api-key'])->makePartial();
        $pinecone->shouldReceive('describeIndex')
            ->once()
            ->with('nonexistent-index')
            ->andThrow(new PineconeException('Failed to describe index: nonexistent-index. Index not found'));

        $this->assertFalse($pinecone->hasIndex('nonexistent-index'));
    }

    // ===== Helper: inject mocked ControlPlane via reflection =====

    private function createPineconeWithMockedControlPlane(MockInterface $controlPlaneMock): Pinecone
    {
        $reflection = new \ReflectionClass(Pinecone::class);
        $pinecone = $reflection->newInstanceWithoutConstructor();
        $property = $reflection->getProperty('controlPlane');
        $property->setValue($pinecone, $controlPlaneMock);

        return $pinecone;
    }

    // ===== ControlPlane delegation: Index methods =====

    public function testListIndexesDelegatesToControlPlane(): void
    {
        $expected = [['name' => 'index-1'], ['name' => 'index-2']];
        $mock = Mockery::mock(ControlPlane::class);
        $mock->shouldReceive('listIndexes')->once()->andReturn($expected);

        $pinecone = $this->createPineconeWithMockedControlPlane($mock);

        $this->assertSame($expected, $pinecone->listIndexes());
    }

    public function testCreateIndexDelegatesToControlPlane(): void
    {
        $requestData = ['dimension' => 128, 'metric' => 'cosine'];
        $expected = ['name' => 'new-index', 'dimension' => 128];
        $mock = Mockery::mock(ControlPlane::class);
        $mock->shouldReceive('createIndex')->once()->with('new-index', $requestData)->andReturn($expected);

        $pinecone = $this->createPineconeWithMockedControlPlane($mock);

        $this->assertSame($expected, $pinecone->createIndex('new-index', $requestData));
    }

    public function testCreateForModelDelegatesToControlPlane(): void
    {
        $requestData = ['cloud' => 'aws', 'region' => 'us-east-1'];
        $expected = ['name' => 'model-index', 'status' => 'Initializing'];
        $mock = Mockery::mock(ControlPlane::class);
        $mock->shouldReceive('createForModel')->once()->with('model-index', $requestData)->andReturn($expected);

        $pinecone = $this->createPineconeWithMockedControlPlane($mock);

        $this->assertSame($expected, $pinecone->createForModel('model-index', $requestData));
    }

    public function testDescribeIndexDelegatesToControlPlane(): void
    {
        $expected = ['name' => 'my-index', 'dimension' => 256, 'host' => 'my-index.svc.pinecone.io'];
        $mock = Mockery::mock(ControlPlane::class);
        $mock->shouldReceive('describeIndex')->once()->with('my-index')->andReturn($expected);

        $pinecone = $this->createPineconeWithMockedControlPlane($mock);

        $this->assertSame($expected, $pinecone->describeIndex('my-index'));
    }

    public function testDeleteIndexDelegatesToControlPlane(): void
    {
        $mock = Mockery::mock(ControlPlane::class);
        $mock->shouldReceive('deleteIndex')->once()->with('old-index');

        $pinecone = $this->createPineconeWithMockedControlPlane($mock);

        $pinecone->deleteIndex('old-index');

        // Mockery verifies the expectation in tearDown
        $this->assertTrue(true);
    }

    public function testConfigureIndexDelegatesToControlPlane(): void
    {
        $requestData = ['replicas' => 2];
        $expected = ['name' => 'my-index', 'replicas' => 2];
        $mock = Mockery::mock(ControlPlane::class);
        $mock->shouldReceive('configureIndex')->once()->with('my-index', $requestData)->andReturn($expected);

        $pinecone = $this->createPineconeWithMockedControlPlane($mock);

        $this->assertSame($expected, $pinecone->configureIndex('my-index', $requestData));
    }

    // ===== ControlPlane delegation: Collection methods =====

    public function testCreateCollectionDelegatesToControlPlane(): void
    {
        $config = ['name' => 'my-collection', 'source' => 'my-index'];
        $expected = ['name' => 'my-collection', 'status' => 'Initializing'];
        $mock = Mockery::mock(ControlPlane::class);
        $mock->shouldReceive('createCollection')->once()->with($config)->andReturn($expected);

        $pinecone = $this->createPineconeWithMockedControlPlane($mock);

        $this->assertSame($expected, $pinecone->createCollection($config));
    }

    public function testListCollectionsDelegatesToControlPlane(): void
    {
        $expected = [['name' => 'col-1'], ['name' => 'col-2']];
        $mock = Mockery::mock(ControlPlane::class);
        $mock->shouldReceive('listCollections')->once()->andReturn($expected);

        $pinecone = $this->createPineconeWithMockedControlPlane($mock);

        $this->assertSame($expected, $pinecone->listCollections());
    }

    public function testDescribeCollectionDelegatesToControlPlane(): void
    {
        $expected = ['name' => 'my-collection', 'size' => 1024];
        $mock = Mockery::mock(ControlPlane::class);
        $mock->shouldReceive('describeCollection')->once()->with('my-collection')->andReturn($expected);

        $pinecone = $this->createPineconeWithMockedControlPlane($mock);

        $this->assertSame($expected, $pinecone->describeCollection('my-collection'));
    }

    public function testDeleteCollectionDelegatesToControlPlane(): void
    {
        $mock = Mockery::mock(ControlPlane::class);
        $mock->shouldReceive('deleteCollection')->once()->with('old-collection');

        $pinecone = $this->createPineconeWithMockedControlPlane($mock);

        $pinecone->deleteCollection('old-collection');

        $this->assertTrue(true);
    }

    // ===== ControlPlane delegation: Backup methods =====

    public function testCreateBackupDelegatesToControlPlane(): void
    {
        $config = ['source_index' => 'my-index', 'name' => 'my-backup'];
        $expected = ['backup_id' => 'bkp-123', 'status' => 'Initializing'];
        $mock = Mockery::mock(ControlPlane::class);
        $mock->shouldReceive('createBackup')->once()->with($config)->andReturn($expected);

        $pinecone = $this->createPineconeWithMockedControlPlane($mock);

        $this->assertSame($expected, $pinecone->createBackup($config));
    }

    public function testListBackupsDelegatesToControlPlane(): void
    {
        $expected = [['backup_id' => 'bkp-1'], ['backup_id' => 'bkp-2']];
        $mock = Mockery::mock(ControlPlane::class);
        $mock->shouldReceive('listBackups')->once()->andReturn($expected);

        $pinecone = $this->createPineconeWithMockedControlPlane($mock);

        $this->assertSame($expected, $pinecone->listBackups());
    }

    public function testDescribeBackupDelegatesToControlPlane(): void
    {
        $expected = ['backup_id' => 'bkp-123', 'status' => 'Ready'];
        $mock = Mockery::mock(ControlPlane::class);
        $mock->shouldReceive('describeBackup')->once()->with('bkp-123')->andReturn($expected);

        $pinecone = $this->createPineconeWithMockedControlPlane($mock);

        $this->assertSame($expected, $pinecone->describeBackup('bkp-123'));
    }

    public function testDeleteBackupDelegatesToControlPlane(): void
    {
        $mock = Mockery::mock(ControlPlane::class);
        $mock->shouldReceive('deleteBackup')->once()->with('bkp-123');

        $pinecone = $this->createPineconeWithMockedControlPlane($mock);

        $pinecone->deleteBackup('bkp-123');

        $this->assertTrue(true);
    }

    // ===== ControlPlane delegation: Restore methods =====

    public function testCreateIndexFromBackupDelegatesToControlPlane(): void
    {
        $config = ['name' => 'restored-index'];
        $expected = ['restore_job_id' => 'rj-456', 'status' => 'Pending'];
        $mock = Mockery::mock(ControlPlane::class);
        $mock->shouldReceive('createIndexFromBackup')->once()->with('bkp-123', $config)->andReturn($expected);

        $pinecone = $this->createPineconeWithMockedControlPlane($mock);

        $this->assertSame($expected, $pinecone->createIndexFromBackup('bkp-123', $config));
    }

    public function testListRestoreJobsDelegatesToControlPlane(): void
    {
        $expected = [['restore_job_id' => 'rj-1'], ['restore_job_id' => 'rj-2']];
        $mock = Mockery::mock(ControlPlane::class);
        $mock->shouldReceive('listRestoreJobs')->once()->with([])->andReturn($expected);

        $pinecone = $this->createPineconeWithMockedControlPlane($mock);

        $this->assertSame($expected, $pinecone->listRestoreJobs());
    }

    public function testListRestoreJobsWithParamsDelegatesToControlPlane(): void
    {
        $params = ['limit' => 10];
        $expected = [['restore_job_id' => 'rj-1']];
        $mock = Mockery::mock(ControlPlane::class);
        $mock->shouldReceive('listRestoreJobs')->once()->with($params)->andReturn($expected);

        $pinecone = $this->createPineconeWithMockedControlPlane($mock);

        $this->assertSame($expected, $pinecone->listRestoreJobs($params));
    }

    public function testDescribeRestoreJobDelegatesToControlPlane(): void
    {
        $expected = ['restore_job_id' => 'rj-456', 'status' => 'Completed'];
        $mock = Mockery::mock(ControlPlane::class);
        $mock->shouldReceive('describeRestoreJob')->once()->with('rj-456')->andReturn($expected);

        $pinecone = $this->createPineconeWithMockedControlPlane($mock);

        $this->assertSame($expected, $pinecone->describeRestoreJob('rj-456'));
    }

    // ===== ControlPlane delegation: Assistant methods =====

    public function testCreateAssistantDelegatesToControlPlane(): void
    {
        $config = ['name' => 'my-assistant', 'instructions' => 'Be helpful'];
        $expected = ['name' => 'my-assistant', 'status' => 'Initializing'];
        $mock = Mockery::mock(ControlPlane::class);
        $mock->shouldReceive('createAssistant')->once()->with($config)->andReturn($expected);

        $pinecone = $this->createPineconeWithMockedControlPlane($mock);

        $this->assertSame($expected, $pinecone->createAssistant($config));
    }

    public function testListAssistantsDelegatesToControlPlane(): void
    {
        $expected = [['name' => 'assistant-1'], ['name' => 'assistant-2']];
        $mock = Mockery::mock(ControlPlane::class);
        $mock->shouldReceive('listAssistants')->once()->andReturn($expected);

        $pinecone = $this->createPineconeWithMockedControlPlane($mock);

        $this->assertSame($expected, $pinecone->listAssistants());
    }

    public function testDescribeAssistantDelegatesToControlPlane(): void
    {
        $expected = ['name' => 'my-assistant', 'status' => 'Ready'];
        $mock = Mockery::mock(ControlPlane::class);
        $mock->shouldReceive('describeAssistant')->once()->with('my-assistant')->andReturn($expected);

        $pinecone = $this->createPineconeWithMockedControlPlane($mock);

        $this->assertSame($expected, $pinecone->describeAssistant('my-assistant'));
    }

    public function testUpdateAssistantDelegatesToControlPlane(): void
    {
        $config = ['instructions' => 'Updated instructions'];
        $expected = ['name' => 'my-assistant', 'instructions' => 'Updated instructions'];
        $mock = Mockery::mock(ControlPlane::class);
        $mock->shouldReceive('updateAssistant')->once()->with('my-assistant', $config)->andReturn($expected);

        $pinecone = $this->createPineconeWithMockedControlPlane($mock);

        $this->assertSame($expected, $pinecone->updateAssistant('my-assistant', $config));
    }

    public function testDeleteAssistantDelegatesToControlPlane(): void
    {
        $mock = Mockery::mock(ControlPlane::class);
        $mock->shouldReceive('deleteAssistant')->once()->with('my-assistant');

        $pinecone = $this->createPineconeWithMockedControlPlane($mock);

        $pinecone->deleteAssistant('my-assistant');

        $this->assertTrue(true);
    }
}
