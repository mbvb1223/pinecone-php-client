<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Tests\Unit;

use Mbvb1223\Pinecone\Errors\PineconeValidationException;
use Mbvb1223\Pinecone\Utils\Configuration;
use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
{
    public function testConstructorWithApiKey(): void
    {
        $config = new Configuration('test-api-key');
        $this->assertEquals('test-api-key', $config->getApiKey());
    }

    public function testDefaultControllerHost(): void
    {
        $config = new Configuration('test-api-key');
        $this->assertEquals('https://api.pinecone.io', $config->getControllerHost());
    }

    public function testCustomControllerHost(): void
    {
        $config = new Configuration('test-api-key', ['controllerHost' => 'https://custom-host.example.com']);
        $this->assertEquals('https://custom-host.example.com', $config->getControllerHost());
    }

    public function testTrailingSlashNormalization(): void
    {
        $config = new Configuration('test-api-key', ['controllerHost' => 'https://custom-host.example.com/']);
        $this->assertEquals('https://custom-host.example.com', $config->getControllerHost());
    }

    public function testMultipleTrailingSlashesNormalized(): void
    {
        $config = new Configuration('test-api-key', ['controllerHost' => 'https://custom-host.example.com///']);
        $this->assertEquals('https://custom-host.example.com', $config->getControllerHost());
    }

    public function testDefaultTimeout(): void
    {
        $config = new Configuration('test-api-key');
        $this->assertEquals(30, $config->getTimeout());
    }

    public function testCustomTimeout(): void
    {
        $config = new Configuration('test-api-key', ['timeout' => 60]);
        $this->assertEquals(60, $config->getTimeout());
    }

    public function testZeroTimeoutThrows(): void
    {
        $this->expectException(PineconeValidationException::class);
        $this->expectExceptionMessage('Timeout must be a positive integer.');

        new Configuration('test-api-key', ['timeout' => 0]);
    }

    public function testNegativeTimeoutThrows(): void
    {
        $this->expectException(PineconeValidationException::class);
        $this->expectExceptionMessage('Timeout must be a positive integer.');

        new Configuration('test-api-key', ['timeout' => -5]);
    }

    public function testEmptyApiKeyThrows(): void
    {
        $this->expectException(PineconeValidationException::class);
        $this->expectExceptionMessage('API key is required.');

        new Configuration('');
    }

    public function testNullApiKeyWithoutEnvThrows(): void
    {
        // Ensure env variable is not set
        $original = getenv('PINECONE_API_KEY');
        putenv('PINECONE_API_KEY');
        unset($_ENV['PINECONE_API_KEY']);

        try {
            $this->expectException(PineconeValidationException::class);
            $this->expectExceptionMessage('API key is required.');
            new Configuration(null);
        } finally {
            if ($original !== false) {
                putenv("PINECONE_API_KEY={$original}");
            }
        }
    }

    public function testInvalidControllerHostSchemeThrows(): void
    {
        $this->expectException(PineconeValidationException::class);
        $this->expectExceptionMessage('Controller host must be a valid URL with http or https scheme.');

        new Configuration('test-api-key', ['controllerHost' => 'ftp://example.com']);
    }

    public function testNonUrlControllerHostThrows(): void
    {
        $this->expectException(PineconeValidationException::class);
        $this->expectExceptionMessage('Controller host must be a valid URL with http or https scheme.');

        new Configuration('test-api-key', ['controllerHost' => 'not-a-url']);
    }

    public function testHttpSchemeAllowed(): void
    {
        $config = new Configuration('test-api-key', ['controllerHost' => 'http://localhost:8080']);
        $this->assertEquals('http://localhost:8080', $config->getControllerHost());
    }

    public function testDefaultHeaders(): void
    {
        $config = new Configuration('test-api-key');
        $headers = $config->getDefaultHeaders();

        $this->assertEquals('test-api-key', $headers['Api-Key']);
        $this->assertEquals('application/json', $headers['Content-Type']);
        $this->assertArrayHasKey('User-Agent', $headers);
        $this->assertArrayHasKey('X-Pinecone-Api-Version', $headers);
    }

    public function testAdditionalHeaders(): void
    {
        $config = new Configuration('test-api-key', [
            'additionalHeaders' => ['X-Custom' => 'value'],
        ]);

        $this->assertEquals(['X-Custom' => 'value'], $config->getAdditionalHeaders());
    }

    public function testAdditionalHeadersMergedIntoDefault(): void
    {
        $config = new Configuration('test-api-key', [
            'additionalHeaders' => ['X-Custom' => 'custom-value'],
        ]);

        $headers = $config->getDefaultHeaders();
        $this->assertEquals('custom-value', $headers['X-Custom']);
        $this->assertEquals('test-api-key', $headers['Api-Key']);
    }

    public function testAdditionalHeadersCanOverrideDefaults(): void
    {
        $config = new Configuration('test-api-key', [
            'additionalHeaders' => ['Content-Type' => 'text/plain'],
        ]);

        $headers = $config->getDefaultHeaders();
        $this->assertEquals('text/plain', $headers['Content-Type']);
    }

    public function testTimeoutCastToInt(): void
    {
        // The source casts timeout with (int), so string '45' becomes 45
        $config = new Configuration('test-api-key', ['timeout' => '45']);
        $this->assertEquals(45, $config->getTimeout());
    }

    public function testApiKeyFromEnvironment(): void
    {
        $original = getenv('PINECONE_API_KEY');
        $originalEnv = $_ENV['PINECONE_API_KEY'] ?? null;

        // Clear $_ENV so getenv() path is tested
        unset($_ENV['PINECONE_API_KEY']);
        putenv('PINECONE_API_KEY=env-api-key');

        try {
            $config = new Configuration(null);
            $this->assertEquals('env-api-key', $config->getApiKey());
        } finally {
            if ($original !== false) {
                putenv("PINECONE_API_KEY={$original}");
            } else {
                putenv('PINECONE_API_KEY');
            }
            if ($originalEnv !== null) {
                $_ENV['PINECONE_API_KEY'] = $originalEnv;
            }
        }
    }

    public function testApiKeyFromEnvSuperglobal(): void
    {
        $originalGetenv = getenv('PINECONE_API_KEY');
        $originalEnv = $_ENV['PINECONE_API_KEY'] ?? null;

        // Clear getenv and set $_ENV directly
        putenv('PINECONE_API_KEY');
        $_ENV['PINECONE_API_KEY'] = 'env-superglobal-key';

        try {
            $config = new Configuration(null);
            $this->assertEquals('env-superglobal-key', $config->getApiKey());
        } finally {
            if ($originalGetenv !== false) {
                putenv("PINECONE_API_KEY={$originalGetenv}");
            } else {
                putenv('PINECONE_API_KEY');
            }
            if ($originalEnv !== null) {
                $_ENV['PINECONE_API_KEY'] = $originalEnv;
            } else {
                unset($_ENV['PINECONE_API_KEY']);
            }
        }
    }

    public function testConstructorWithNullConfig(): void
    {
        $config = new Configuration('test-api-key', null);
        $this->assertEquals('test-api-key', $config->getApiKey());
        $this->assertEquals('https://api.pinecone.io', $config->getControllerHost());
        $this->assertEquals(30, $config->getTimeout());
    }

    public function testConstructorWithEmptyConfigArray(): void
    {
        $config = new Configuration('test-api-key', []);
        $this->assertEquals('test-api-key', $config->getApiKey());
        $this->assertEquals('https://api.pinecone.io', $config->getControllerHost());
        $this->assertEquals(30, $config->getTimeout());
    }

    public function testDefaultAdditionalHeadersEmpty(): void
    {
        $config = new Configuration('test-api-key');
        $this->assertEquals([], $config->getAdditionalHeaders());
    }

    public function testUserAgentHeaderContainsVersion(): void
    {
        $config = new Configuration('test-api-key');
        $headers = $config->getDefaultHeaders();
        $this->assertStringContainsString('pinecone-php-client', $headers['User-Agent']);
    }

    public function testPineconeApiVersionHeader(): void
    {
        $config = new Configuration('test-api-key');
        $headers = $config->getDefaultHeaders();
        $this->assertEquals('2025-10', $headers['X-Pinecone-Api-Version']);
    }
}
