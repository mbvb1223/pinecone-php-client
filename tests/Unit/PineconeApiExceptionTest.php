<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Tests\Unit;

use Mbvb1223\Pinecone\Errors\PineconeApiException;
use Mbvb1223\Pinecone\Errors\PineconeAuthException;
use Mbvb1223\Pinecone\Errors\PineconeException;
use Mbvb1223\Pinecone\Errors\PineconeTimeoutException;
use Mbvb1223\Pinecone\Errors\PineconeValidationException;
use PHPUnit\Framework\TestCase;

class PineconeApiExceptionTest extends TestCase
{
    public function testGetStatusCode(): void
    {
        $exception = new PineconeApiException('Error', 422, ['detail' => 'invalid']);
        $this->assertEquals(422, $exception->getStatusCode());
    }

    public function testGetStatusCodeMatchesGetCode(): void
    {
        $exception = new PineconeApiException('Error', 500);
        $this->assertEquals($exception->getCode(), $exception->getStatusCode());
    }

    public function testGetResponseData(): void
    {
        $data = ['message' => 'Not Found', 'code' => 5];
        $exception = new PineconeApiException('Not Found', 404, $data);
        $this->assertEquals($data, $exception->getResponseData());
    }

    public function testGetResponseDataDefaultsToEmptyArray(): void
    {
        $exception = new PineconeApiException('Error', 500);
        $this->assertEquals([], $exception->getResponseData());
    }

    public function testExtendsException(): void
    {
        $exception = new PineconeApiException('Error', 500);
        $this->assertInstanceOf(PineconeException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function testPreviousThrowable(): void
    {
        $previous = new \RuntimeException('Original error');
        $exception = new PineconeApiException('Wrapped', 500, [], $previous);
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testMessage(): void
    {
        $exception = new PineconeApiException('Custom message', 400);
        $this->assertEquals('Custom message', $exception->getMessage());
    }

    // ===== PineconeException tests =====

    public function testPineconeExceptionDefaults(): void
    {
        $exception = new PineconeException();
        $this->assertEquals('', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testPineconeExceptionWithPrevious(): void
    {
        $previous = new \RuntimeException('cause');
        $exception = new PineconeException('msg', 1, $previous);
        $this->assertSame($previous, $exception->getPrevious());
    }

    // ===== Auth/Timeout/Validation exception hierarchy =====

    public function testAuthExceptionExtendsBase(): void
    {
        $exception = new PineconeAuthException('Unauthorized', 401);
        $this->assertInstanceOf(PineconeException::class, $exception);
        $this->assertEquals(401, $exception->getCode());
    }

    public function testTimeoutExceptionExtendsBase(): void
    {
        $exception = new PineconeTimeoutException('Timeout', 408);
        $this->assertInstanceOf(PineconeException::class, $exception);
        $this->assertEquals(408, $exception->getCode());
    }

    public function testValidationExceptionExtendsBase(): void
    {
        $exception = new PineconeValidationException('Invalid input');
        $this->assertInstanceOf(PineconeException::class, $exception);
        $this->assertEquals('Invalid input', $exception->getMessage());
    }

    // ===== PineconeValidationException detailed tests =====

    public function testValidationExceptionConstructor(): void
    {
        $exception = new PineconeValidationException('Field is required', 100);
        $this->assertEquals('Field is required', $exception->getMessage());
        $this->assertEquals(100, $exception->getCode());
    }

    public function testValidationExceptionHierarchy(): void
    {
        $exception = new PineconeValidationException('Bad input');
        $this->assertInstanceOf(PineconeException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    // ===== PineconeAuthException detailed tests =====

    public function testAuthExceptionConstructor(): void
    {
        $exception = new PineconeAuthException('Forbidden', 403);
        $this->assertEquals('Forbidden', $exception->getMessage());
        $this->assertEquals(403, $exception->getCode());
    }

    public function testAuthExceptionHierarchy(): void
    {
        $exception = new PineconeAuthException('Unauthorized', 401);
        $this->assertInstanceOf(PineconeException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function testAuthExceptionWithPreviousThrowable(): void
    {
        $previous = new \RuntimeException('Original auth error');
        $exception = new PineconeAuthException('Auth failed', 401, $previous);
        $this->assertSame($previous, $exception->getPrevious());
        $this->assertEquals('Auth failed', $exception->getMessage());
        $this->assertEquals(401, $exception->getCode());
    }

    // ===== PineconeTimeoutException detailed tests =====

    public function testTimeoutExceptionConstructor(): void
    {
        $exception = new PineconeTimeoutException('Gateway Timeout', 504);
        $this->assertEquals('Gateway Timeout', $exception->getMessage());
        $this->assertEquals(504, $exception->getCode());
    }

    public function testTimeoutExceptionHierarchy(): void
    {
        $exception = new PineconeTimeoutException('Timeout', 408);
        $this->assertInstanceOf(PineconeException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function testTimeoutExceptionWithPreviousThrowable(): void
    {
        $previous = new \RuntimeException('Connection timed out');
        $exception = new PineconeTimeoutException('Request timed out', 408, $previous);
        $this->assertSame($previous, $exception->getPrevious());
        $this->assertEquals('Request timed out', $exception->getMessage());
        $this->assertEquals(408, $exception->getCode());
    }

    // ===== All exceptions catchable as PineconeException =====

    public function testAllExceptionsCaughtAsPineconeException(): void
    {
        $exceptions = [
            new PineconeApiException('API error', 500),
            new PineconeAuthException('Auth error', 401),
            new PineconeTimeoutException('Timeout', 408),
            new PineconeValidationException('Validation error'),
        ];

        foreach ($exceptions as $exception) {
            try {
                throw $exception;
            } catch (PineconeException $caught) {
                $this->assertSame($exception, $caught);
            }
        }
    }
}
