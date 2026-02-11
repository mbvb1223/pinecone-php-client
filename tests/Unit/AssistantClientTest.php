<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Mbvb1223\Pinecone\Assistant\AssistantClient;
use Mbvb1223\Pinecone\Errors\PineconeException;
use Mbvb1223\Pinecone\Errors\PineconeValidationException;
use Mbvb1223\Pinecone\Utils\Configuration;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class AssistantClientTest extends TestCase
{
    private AssistantClient $assistant;
    private MockInterface $httpClientMock;

    protected function setUp(): void
    {
        $this->httpClientMock = Mockery::mock(Client::class);
        $config = new Configuration('test-api-key');
        $this->assistant = new AssistantClient($config, 'test-assistant');

        $reflection = new \ReflectionClass($this->assistant);
        $prop = $reflection->getProperty('httpClient');
        $prop->setValue($this->assistant, $this->httpClientMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    // ===== Constructor =====

    public function testConstructorWithHostFromAssistantInfo(): void
    {
        $config = new Configuration('test-api-key');
        $assistant = new AssistantClient($config, 'my-assistant', ['host' => 'custom-host.pinecone.io']);

        // Verify it was constructed without error; the host is internal to Guzzle client
        $this->assertInstanceOf(AssistantClient::class, $assistant);
    }

    public function testConstructorWithoutHostFallsBackToConfig(): void
    {
        $config = new Configuration('test-api-key');
        $assistant = new AssistantClient($config, 'my-assistant', []);
        $this->assertInstanceOf(AssistantClient::class, $assistant);
    }

    // ===== chat =====

    public function testChatSuccess(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"message":{"role":"assistant","content":"Hello!"}}');

        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/assistant/chat/test-assistant/completions', Mockery::on(function ($arg) {
                return $arg['json']['messages'][0]['role'] === 'user'
                    && $arg['json']['messages'][0]['content'] === 'Hi';
            }))
            ->andReturn($response);

        $result = $this->assistant->chat([['role' => 'user', 'content' => 'Hi']]);
        $this->assertEquals('Hello!', $result['message']['content']);
    }

    public function testChatWithOptions(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"message":{"content":"ok"}}');

        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/assistant/chat/test-assistant/completions', Mockery::on(function ($arg) {
                return $arg['json']['messages'][0]['content'] === 'Hi'
                    && $arg['json']['model'] === 'gpt-4';
            }))
            ->andReturn($response);

        $result = $this->assistant->chat(
            [['role' => 'user', 'content' => 'Hi']],
            ['model' => 'gpt-4']
        );
        $this->assertEquals('ok', $result['message']['content']);
    }

    public function testChatEmptyMessagesThrows(): void
    {
        $this->expectException(PineconeValidationException::class);
        $this->expectExceptionMessage('At least one message is required for chat.');

        $this->assistant->chat([]);
    }

    public function testChatNetworkErrorThrows(): void
    {
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->andThrow(new RequestException('Timeout', new Request('POST', '/chat')));

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Failed to chat with assistant: Timeout');

        $this->assistant->chat([['role' => 'user', 'content' => 'Hi']]);
    }

    // ===== uploadFile =====

    public function testUploadFileNotFoundThrows(): void
    {
        $this->expectException(PineconeValidationException::class);
        $this->expectExceptionMessage('File not found: /nonexistent/file.txt');

        $this->assistant->uploadFile('/nonexistent/file.txt');
    }

    public function testUploadFileSuccess(): void
    {
        // Create a temp file for the test
        $tmpFile = tempnam(sys_get_temp_dir(), 'pinecone_test_');
        file_put_contents($tmpFile, 'test content');

        try {
            $response = Mockery::mock(ResponseInterface::class);
            $response->shouldReceive('getStatusCode')->andReturn(200);
            $response->shouldReceive('getBody->getContents')->andReturn('{"id":"file-123","name":"' . basename($tmpFile) . '"}');

            $this->httpClientMock->shouldReceive('post')
                ->once()
                ->with('/assistant/files/test-assistant', Mockery::on(function ($arg) use ($tmpFile) {
                    return isset($arg['multipart'])
                        && $arg['multipart'][0]['name'] === 'file'
                        && $arg['multipart'][0]['filename'] === basename($tmpFile);
                }))
                ->andReturn($response);

            $result = $this->assistant->uploadFile($tmpFile);
            $this->assertEquals('file-123', $result['id']);
        } finally {
            unlink($tmpFile);
        }
    }

    public function testUploadFileNetworkErrorThrows(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'pinecone_test_');
        file_put_contents($tmpFile, 'test');

        try {
            $this->httpClientMock->shouldReceive('post')
                ->once()
                ->andThrow(new RequestException('Upload failed', new Request('POST', '/files')));

            $this->expectException(PineconeException::class);
            $this->expectExceptionMessage('Failed to upload file to assistant: Upload failed');

            $this->assistant->uploadFile($tmpFile);
        } finally {
            unlink($tmpFile);
        }
    }

    // ===== listFiles =====

    public function testListFilesSuccess(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"files":[{"id":"f1"},{"id":"f2"}]}');

        $this->httpClientMock->shouldReceive('get')
            ->once()
            ->with('/assistant/files/test-assistant')
            ->andReturn($response);

        $result = $this->assistant->listFiles();
        $this->assertCount(2, $result['files']);
    }

    public function testListFilesNetworkErrorThrows(): void
    {
        $this->httpClientMock->shouldReceive('get')
            ->once()
            ->andThrow(new RequestException('Error', new Request('GET', '/files')));

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Failed to list assistant files: Error');

        $this->assistant->listFiles();
    }

    // ===== describeFile =====

    public function testDescribeFileSuccess(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"id":"file-123","name":"doc.pdf","status":"ready"}');

        $this->httpClientMock->shouldReceive('get')
            ->once()
            ->with('/assistant/files/test-assistant/file-123')
            ->andReturn($response);

        $result = $this->assistant->describeFile('file-123');
        $this->assertEquals('file-123', $result['id']);
        $this->assertEquals('ready', $result['status']);
    }

    public function testDescribeFileNetworkErrorThrows(): void
    {
        $this->httpClientMock->shouldReceive('get')
            ->once()
            ->andThrow(new RequestException('Not found', new Request('GET', '/files')));

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Failed to describe assistant file: Not found');

        $this->assistant->describeFile('file-123');
    }

    // ===== deleteFile =====

    public function testDeleteFileSuccess(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('');

        $this->httpClientMock->shouldReceive('delete')
            ->once()
            ->with('/assistant/files/test-assistant/file-123')
            ->andReturn($response);

        $this->assistant->deleteFile('file-123');
        $this->assertTrue(true); // No exception means success
    }

    public function testDeleteFileNetworkErrorThrows(): void
    {
        $this->httpClientMock->shouldReceive('delete')
            ->once()
            ->andThrow(new RequestException('Delete failed', new Request('DELETE', '/files')));

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Failed to delete assistant file: Delete failed');

        $this->assistant->deleteFile('file-123');
    }

    // ===== URL encoding =====

    public function testAssistantNameIsUrlEncoded(): void
    {
        $config = new Configuration('test-api-key');
        $assistant = new AssistantClient($config, 'my assistant');

        // httpClient is private but not readonly on AssistantClient, so reflection works
        $reflection = new \ReflectionClass($assistant);
        $prop = $reflection->getProperty('httpClient');
        $prop->setValue($assistant, $this->httpClientMock);

        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"files":[]}');

        $this->httpClientMock->shouldReceive('get')
            ->once()
            ->with('/assistant/files/my+assistant')
            ->andReturn($response);

        $result = $assistant->listFiles();
        $this->assertEmpty($result['files']);
    }
}