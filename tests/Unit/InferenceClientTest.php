<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Mbvb1223\Pinecone\Errors\PineconeException;
use Mbvb1223\Pinecone\Errors\PineconeValidationException;
use Mbvb1223\Pinecone\Inference\InferenceClient;
use Mbvb1223\Pinecone\Utils\Configuration;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class InferenceClientTest extends TestCase
{
    private InferenceClient $client;
    private MockInterface $httpClientMock;

    protected function setUp(): void
    {
        $this->httpClientMock = Mockery::mock(Client::class);

        // Create InferenceClient with a real Configuration, then replace the httpClient via reflection
        $config = new Configuration('test-api-key');
        $this->client = new InferenceClient($config);

        $reflection = new \ReflectionClass($this->client);
        $prop = $reflection->getProperty('httpClient');
        $prop->setValue($this->client, $this->httpClientMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    // ===== embed =====

    public function testEmbedSuccess(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"data":[{"values":[0.1,0.2,0.3]}],"model":"multilingual-e5-large"}');

        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/embed', Mockery::on(function ($arg) {
                $json = $arg['json'];

                return $json['model'] === 'multilingual-e5-large'
                    && $json['inputs'] === [['text' => 'hello']];
            }))
            ->andReturn($response);

        $result = $this->client->embed('multilingual-e5-large', [['text' => 'hello']]);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('multilingual-e5-large', $result['model']);
    }

    public function testEmbedNormalizesStringInputs(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"data":[]}');

        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/embed', Mockery::on(function ($arg) {
                $inputs = $arg['json']['inputs'];

                return $inputs === [['text' => 'hello'], ['text' => 'world']];
            }))
            ->andReturn($response);

        $result = $this->client->embed('model', ['hello', 'world']);
        $this->assertArrayHasKey('data', $result);
    }

    public function testEmbedWithParameters(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"data":[]}');

        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/embed', Mockery::on(function ($arg) {
                return isset($arg['json']['parameters'])
                    && $arg['json']['parameters'] instanceof \stdClass;
            }))
            ->andReturn($response);

        $result = $this->client->embed('model', [['text' => 'hello']], ['input_type' => 'query']);
        $this->assertArrayHasKey('data', $result);
    }

    public function testEmbedWithoutParametersOmitsKey(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"data":[]}');

        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/embed', Mockery::on(function ($arg) {
                return !isset($arg['json']['parameters']);
            }))
            ->andReturn($response);

        $result = $this->client->embed('model', [['text' => 'hello']]);
        $this->assertArrayHasKey('data', $result);
    }

    public function testEmbedEmptyModelThrows(): void
    {
        $this->expectException(PineconeValidationException::class);
        $this->expectExceptionMessage('Model name is required for embedding.');

        $this->client->embed('', [['text' => 'hello']]);
    }

    public function testEmbedEmptyInputsThrows(): void
    {
        $this->expectException(PineconeValidationException::class);
        $this->expectExceptionMessage('At least one input is required for embedding.');

        $this->client->embed('model', []);
    }

    public function testEmbedNetworkErrorThrows(): void
    {
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->andThrow(new RequestException('Connection refused', new Request('POST', '/embed')));

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Failed to generate embeddings: Connection refused');

        $this->client->embed('model', [['text' => 'hello']]);
    }

    // ===== rerank =====

    public function testRerankSuccess(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"data":[{"index":0,"score":0.95}]}');

        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/rerank', Mockery::on(function ($arg) {
                $json = $arg['json'];

                return $json['model'] === 'bge-reranker-v2-m3'
                    && $json['query'] === 'What is AI?'
                    && count($json['documents']) === 2
                    && $json['return_documents'] === true;
            }))
            ->andReturn($response);

        $result = $this->client->rerank(
            'bge-reranker-v2-m3',
            'What is AI?',
            [['text' => 'AI is cool'], ['text' => 'Something else']],
        );
        $this->assertArrayHasKey('data', $result);
    }

    public function testRerankWithTopN(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"data":[]}');

        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/rerank', Mockery::on(function ($arg) {
                return $arg['json']['top_n'] === 3;
            }))
            ->andReturn($response);

        $result = $this->client->rerank('model', 'query', [['text' => 'doc']], topN: 3);
        $this->assertArrayHasKey('data', $result);
    }

    public function testRerankWithRankFields(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"data":[]}');

        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/rerank', Mockery::on(function ($arg) {
                return $arg['json']['rank_fields'] === ['title', 'body'];
            }))
            ->andReturn($response);

        $result = $this->client->rerank('model', 'query', [['text' => 'doc']], rankFields: ['title', 'body']);
        $this->assertArrayHasKey('data', $result);
    }

    public function testRerankWithParameters(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"data":[]}');

        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/rerank', Mockery::on(function ($arg) {
                return isset($arg['json']['parameters'])
                    && $arg['json']['parameters'] instanceof \stdClass;
            }))
            ->andReturn($response);

        $result = $this->client->rerank('model', 'query', [['text' => 'doc']], parameters: ['truncate' => 'END']);
        $this->assertArrayHasKey('data', $result);
    }

    public function testRerankEmptyModelThrows(): void
    {
        $this->expectException(PineconeValidationException::class);
        $this->expectExceptionMessage('Model name is required for reranking.');

        $this->client->rerank('', 'query', [['text' => 'doc']]);
    }

    public function testRerankEmptyQueryThrows(): void
    {
        $this->expectException(PineconeValidationException::class);
        $this->expectExceptionMessage('Query is required for reranking.');

        $this->client->rerank('model', '', [['text' => 'doc']]);
    }

    public function testRerankEmptyDocumentsThrows(): void
    {
        $this->expectException(PineconeValidationException::class);
        $this->expectExceptionMessage('At least one document is required for reranking.');

        $this->client->rerank('model', 'query', []);
    }

    public function testRerankNetworkErrorThrows(): void
    {
        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->andThrow(new RequestException('Timeout', new Request('POST', '/rerank')));

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Failed to rerank documents: Timeout');

        $this->client->rerank('model', 'query', [['text' => 'doc']]);
    }

    // ===== listModels =====

    public function testListModelsSuccess(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"models":[{"name":"multilingual-e5-large"}]}');

        $this->httpClientMock->shouldReceive('get')
            ->once()
            ->with('/models')
            ->andReturn($response);

        $result = $this->client->listModels();
        $this->assertArrayHasKey('models', $result);
    }

    public function testListModelsNetworkErrorThrows(): void
    {
        $this->httpClientMock->shouldReceive('get')
            ->once()
            ->andThrow(new RequestException('Error', new Request('GET', '/models')));

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessage('Failed to list models: Error');

        $this->client->listModels();
    }

    // ===== rerank edge cases =====

    public function testRerankWithReturnDocumentsFalse(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"data":[]}');

        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/rerank', Mockery::on(function ($arg) {
                return $arg['json']['return_documents'] === false;
            }))
            ->andReturn($response);

        $result = $this->client->rerank('model', 'query', [['text' => 'doc']], returnDocuments: false);
        $this->assertArrayHasKey('data', $result);
    }

    public function testRerankTopNZeroOmitsKey(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"data":[]}');

        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/rerank', Mockery::on(function ($arg) {
                return !isset($arg['json']['top_n']);
            }))
            ->andReturn($response);

        $result = $this->client->rerank('model', 'query', [['text' => 'doc']], topN: 0);
        $this->assertArrayHasKey('data', $result);
    }

    // ===== embed mixed inputs =====

    public function testEmbedMixedInputs(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $response->shouldReceive('getBody->getContents')->andReturn('{"data":[]}');

        $this->httpClientMock->shouldReceive('post')
            ->once()
            ->with('/embed', Mockery::on(function ($arg) {
                $inputs = $arg['json']['inputs'];

                return $inputs === [['text' => 'hello'], ['text' => 'world', 'extra' => 'data']];
            }))
            ->andReturn($response);

        $result = $this->client->embed('model', ['hello', ['text' => 'world', 'extra' => 'data']]);
        $this->assertArrayHasKey('data', $result);
    }
}
