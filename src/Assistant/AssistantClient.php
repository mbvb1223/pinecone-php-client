<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Assistant;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Mbvb1223\Pinecone\Utils\Configuration;
use Mbvb1223\Pinecone\Errors\PineconeException;
use Mbvb1223\Pinecone\Utils\HandlesApiResponse;

class AssistantClient
{
    use HandlesApiResponse;

    private Client $httpClient;

    public function __construct(Configuration $config, array $assistantInfo = [])
    {
        $host = $assistantInfo['host'] ?? 'api.pinecone.io';
        $this->httpClient = new Client([
            'base_uri' => "https://{$host}",
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
}
