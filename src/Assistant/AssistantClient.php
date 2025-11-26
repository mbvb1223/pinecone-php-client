<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Assistant;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Mbvb1223\Pinecone\Utils\Configuration;
use Mbvb1223\Pinecone\Errors\PineconeApiException;
use Mbvb1223\Pinecone\Errors\PineconeException;
use Psr\Http\Message\ResponseInterface;

class AssistantClient
{
    private Client $httpClient;
    private Configuration $config;

    public function __construct(Configuration $config)
    {
        $this->config = $config;
        $this->httpClient = new Client([
            'base_uri' => 'https://api.pinecone.io',
            'timeout' => $config->getTimeout(),
            'headers' => $config->getDefaultHeaders(),
        ]);
    }

    public function createAssistant(string $name, array $instructions = []): array
    {
        try {
            $payload = [
                'name' => $name,
                'instructions' => $instructions,
            ];

            $response = $this->httpClient->post('/assistants', [
                'json' => $payload,
            ]);

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to create assistant: ' . $e->getMessage(), 0, $e);
        }
    }

    public function listAssistants(): array
    {
        try {
            $response = $this->httpClient->get('/assistants');
            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to list assistants: ' . $e->getMessage(), 0, $e);
        }
    }

    public function describeAssistant(string $assistantName): array
    {
        try {
            $response = $this->httpClient->get("/assistants/{$assistantName}");
            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to describe assistant: ' . $e->getMessage(), 0, $e);
        }
    }

    public function deleteAssistant(string $assistantName): void
    {
        try {
            $response = $this->httpClient->delete("/assistants/{$assistantName}");
            $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to delete assistant: ' . $e->getMessage(), 0, $e);
        }
    }

    public function chat(string $assistantName, array $messages, array $options = []): array
    {
        try {
            $payload = array_merge([
                'messages' => $messages,
            ], $options);

            $response = $this->httpClient->post("/assistants/{$assistantName}/chat", [
                'json' => $payload,
            ]);

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to chat with assistant: ' . $e->getMessage(), 0, $e);
        }
    }

    private function handleResponse(ResponseInterface $response): array
    {
        $statusCode = $response->getStatusCode();
        $body = $response->getBody()->getContents();

        if ($statusCode >= 400) {
            $data = json_decode($body, true) ?? [];
            $message = $data['message'] ?? 'API request failed';
            throw new PineconeApiException($message, $statusCode, $data);
        }

        if (empty($body)) {
            return [];
        }

        $decoded = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new PineconeException('Failed to decode JSON response: ' . json_last_error_msg());
        }

        return $decoded;
    }
}
