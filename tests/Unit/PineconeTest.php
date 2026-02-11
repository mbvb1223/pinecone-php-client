<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Tests\Unit;

use Mbvb1223\Pinecone\Errors\PineconeException;
use Mbvb1223\Pinecone\Errors\PineconeValidationException;
use Mbvb1223\Pinecone\Inference\InferenceClient;
use Mbvb1223\Pinecone\Pinecone;
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
}
