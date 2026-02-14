<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Tests\Unit;

use Mbvb1223\Pinecone\Errors\PineconeApiException;
use Mbvb1223\Pinecone\Errors\PineconeAuthException;
use Mbvb1223\Pinecone\Errors\PineconeException;
use Mbvb1223\Pinecone\Errors\PineconeRateLimitException;
use Mbvb1223\Pinecone\Errors\PineconeTimeoutException;
use Mbvb1223\Pinecone\Utils\HandlesApiResponse;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class HandlesApiResponseTest extends TestCase
{
    private object $handler;

    protected function setUp(): void
    {
        $this->handler = new class () {
            use HandlesApiResponse;

            public function handle(ResponseInterface $response): array
            {
                return $this->handleResponse($response);
            }
        };
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    private function mockResponse(int $statusCode, string $body): ResponseInterface
    {
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')->andReturn($body);

        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn($statusCode);
        $response->shouldReceive('getBody')->andReturn($stream);

        return $response;
    }

    // ===== Success cases =====

    public function testSuccessfulJsonResponse(): void
    {
        $response = $this->mockResponse(200, '{"key":"value"}');
        $result = $this->handler->handle($response);
        $this->assertEquals(['key' => 'value'], $result);
    }

    public function testEmptyBodyReturnsEmptyArray(): void
    {
        $response = $this->mockResponse(200, '');
        $result = $this->handler->handle($response);
        $this->assertEquals([], $result);
    }

    public function testNullJsonReturnsEmptyArray(): void
    {
        $response = $this->mockResponse(200, 'null');
        $result = $this->handler->handle($response);
        $this->assertEquals([], $result);
    }

    public function testInvalidJsonThrowsPineconeException(): void
    {
        $response = $this->mockResponse(200, 'not json');

        $this->expectException(PineconeException::class);
        $this->expectExceptionMessageMatches('/Failed to decode JSON response/');

        $this->handler->handle($response);
    }

    // ===== Auth exceptions (401/403) =====

    public function testStatus401ThrowsAuthException(): void
    {
        $response = $this->mockResponse(401, '{"message":"Unauthorized"}');

        $this->expectException(PineconeAuthException::class);
        $this->expectExceptionMessage('Unauthorized');
        $this->expectExceptionCode(401);

        $this->handler->handle($response);
    }

    public function testStatus403ThrowsAuthException(): void
    {
        $response = $this->mockResponse(403, '{"message":"Forbidden"}');

        $this->expectException(PineconeAuthException::class);
        $this->expectExceptionMessage('Forbidden');
        $this->expectExceptionCode(403);

        $this->handler->handle($response);
    }

    // ===== Timeout exceptions (408/504) =====

    public function testStatus408ThrowsTimeoutException(): void
    {
        $response = $this->mockResponse(408, '{"message":"Request Timeout"}');

        $this->expectException(PineconeTimeoutException::class);
        $this->expectExceptionMessage('Request Timeout');
        $this->expectExceptionCode(408);

        $this->handler->handle($response);
    }

    public function testStatus504ThrowsTimeoutException(): void
    {
        $response = $this->mockResponse(504, '{"message":"Gateway Timeout"}');

        $this->expectException(PineconeTimeoutException::class);
        $this->expectExceptionMessage('Gateway Timeout');
        $this->expectExceptionCode(504);

        $this->handler->handle($response);
    }

    // ===== General API exceptions =====

    public function testStatus500ThrowsApiException(): void
    {
        $response = $this->mockResponse(500, '{"message":"Internal Server Error"}');

        try {
            $this->handler->handle($response);
            $this->fail('Expected PineconeApiException');
        } catch (PineconeApiException $e) {
            $this->assertEquals('Internal Server Error', $e->getMessage());
            $this->assertEquals(500, $e->getStatusCode());
            $this->assertEquals(500, $e->getCode());
            $this->assertEquals(['message' => 'Internal Server Error'], $e->getResponseData());
        }
    }

    public function testStatus404ThrowsApiException(): void
    {
        $response = $this->mockResponse(404, '{"message":"Not Found"}');

        $this->expectException(PineconeApiException::class);
        $this->expectExceptionMessage('Not Found');
        $this->expectExceptionCode(404);

        $this->handler->handle($response);
    }

    public function testStatus429ThrowsRateLimitException(): void
    {
        $response = $this->mockResponse(429, '{"message":"Rate limited"}');

        $this->expectException(PineconeRateLimitException::class);
        $this->expectExceptionMessage('Rate limited');
        $this->expectExceptionCode(429);

        $this->handler->handle($response);
    }

    public function testRateLimitExceptionExtendsPineconeException(): void
    {
        $response = $this->mockResponse(429, '{"message":"Rate limited"}');

        $this->expectException(PineconeException::class);

        $this->handler->handle($response);
    }

    // ===== Error message extraction =====

    public function testErrorMessageFromNestedError(): void
    {
        $response = $this->mockResponse(400, '{"error":{"message":"Bad request detail"}}');

        $this->expectException(PineconeApiException::class);
        $this->expectExceptionMessage('Bad request detail');

        $this->handler->handle($response);
    }

    public function testErrorMessageFromErrorString(): void
    {
        $response = $this->mockResponse(400, '{"error":"Something went wrong"}');

        $this->expectException(PineconeApiException::class);
        $this->expectExceptionMessage('Something went wrong');

        $this->handler->handle($response);
    }

    public function testFallbackErrorMessage(): void
    {
        $response = $this->mockResponse(400, '{"status":"error"}');

        $this->expectException(PineconeApiException::class);
        $this->expectExceptionMessage('API request failed');

        $this->handler->handle($response);
    }

    public function testArrayErrorMessageIsJsonEncoded(): void
    {
        $response = $this->mockResponse(400, '{"message":["error1","error2"]}');

        $this->expectException(PineconeApiException::class);
        $this->expectExceptionMessage('["error1","error2"]');

        $this->handler->handle($response);
    }

    public function testNonJsonErrorBodyUsesDefault(): void
    {
        $response = $this->mockResponse(500, 'plain text error');

        $this->expectException(PineconeApiException::class);
        $this->expectExceptionMessage('API request failed');

        $this->handler->handle($response);
    }

    // ===== Exception hierarchy =====

    public function testAuthExceptionExtendsPineconeException(): void
    {
        $response = $this->mockResponse(401, '{"message":"Unauthorized"}');

        $this->expectException(PineconeException::class);

        $this->handler->handle($response);
    }

    public function testTimeoutExceptionExtendsPineconeException(): void
    {
        $response = $this->mockResponse(408, '{"message":"Timeout"}');

        $this->expectException(PineconeException::class);

        $this->handler->handle($response);
    }

    public function testApiExceptionExtendsPineconeException(): void
    {
        $response = $this->mockResponse(500, '{"message":"Error"}');

        $this->expectException(PineconeException::class);

        $this->handler->handle($response);
    }

    // ===== Edge case: status 399 is success =====

    public function testStatus399IsSuccess(): void
    {
        $response = $this->mockResponse(399, '{"result":"ok"}');
        $result = $this->handler->handle($response);
        $this->assertEquals(['result' => 'ok'], $result);
    }

    // ===== Edge case: status 400 is first error =====

    public function testStatus400ThrowsApiException(): void
    {
        $response = $this->mockResponse(400, '{"message":"Bad Request"}');

        $this->expectException(PineconeApiException::class);
        $this->expectExceptionMessage('Bad Request');
        $this->expectExceptionCode(400);

        $this->handler->handle($response);
    }

    // ===== Edge case: error as string from $data['error'] =====

    public function testErrorStringFromErrorKey(): void
    {
        $response = $this->mockResponse(422, '{"error":"Unprocessable entity"}');

        try {
            $this->handler->handle($response);
            $this->fail('Expected PineconeApiException');
        } catch (PineconeApiException $e) {
            $this->assertEquals('Unprocessable entity', $e->getMessage());
            $this->assertEquals(422, $e->getStatusCode());
            $this->assertEquals(['error' => 'Unprocessable entity'], $e->getResponseData());
        }
    }

    // ===== Edge case: null/empty body on error =====

    public function testEmptyBodyOnErrorUsesFallbackMessage(): void
    {
        $response = $this->mockResponse(500, '');

        $this->expectException(PineconeApiException::class);
        $this->expectExceptionMessage('API request failed');
        $this->expectExceptionCode(500);

        $this->handler->handle($response);
    }
}
