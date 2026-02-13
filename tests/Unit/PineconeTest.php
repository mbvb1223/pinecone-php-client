<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Tests\Unit;

use Mbvb1223\Pinecone\Assistant\AssistantClient;
use Mbvb1223\Pinecone\Data\Index;
use Mbvb1223\Pinecone\Errors\PineconeException;
use Mbvb1223\Pinecone\Errors\PineconeValidationException;
use Mbvb1223\Pinecone\Inference\InferenceClient;
use Mbvb1223\Pinecone\Pinecone;
use Mockery;
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
}
