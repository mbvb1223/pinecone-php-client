<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Control;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Mbvb1223\Pinecone\Errors\PineconeApiException;
use Mbvb1223\Pinecone\Errors\PineconeException;
use Psr\Http\Message\ResponseInterface;

class ControlPlane
{
    public function __construct(private readonly Client $httpClient)
    {
    }

    public function listIndexes(): array
    {
        try {
            $response = $this->httpClient->get('/indexes');
            $data = $this->handleResponse($response);

            return $data['indexes'] ?? [];
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to list indexes: ' . $e->getMessage(), 0, $e);
        }
    }

    public function createIndex(string $name, array $requestData): array
    {
        try {
            $payload = [
                'name' => $name,
                'dimension' => $requestData['dimension'],
                'metric' => $requestData['metric'] ?? 'cosine',
                'spec' => $requestData['spec'],
            ];

            $response = $this->httpClient->post('/indexes', ['json' => $payload]);

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException("Failed to create index: $name. {$e->getMessage()}", 0, $e);
        }
    }

    public function createForModel(string $name, array $requestData): array
    {
        try {
            $payload = [
                'name' => $name,
                'cloud' => $requestData['cloud'],
                'region' => $requestData['region'],
                'embed' => $requestData['embed'],
            ];

            // Add optional fields if provided
            if (isset($requestData['deletion_protection'])) {
                $payload['deletion_protection'] = $requestData['deletion_protection'];
            }

            if (isset($requestData['tags'])) {
                $payload['tags'] = $requestData['tags'];
            }

            if (isset($requestData['schema'])) {
                $payload['schema'] = $requestData['schema'];
            }

            if (isset($requestData['read_capacity'])) {
                $payload['read_capacity'] = $requestData['read_capacity'];
            }

            $response = $this->httpClient->post('/indexes/create-for-model', ['json' => $payload]);

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException("Failed to create index for model: $name. {$e->getMessage()}", 0, $e);
        }
    }

    public function describeIndex(string $name): array
    {
        try {
            $response = $this->httpClient->get("/indexes/{$name}");

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException("Failed to describe index: $name. {$e->getMessage()}", 0, $e);
        }
    }

    public function deleteIndex(string $name): void
    {
        try {
            $response = $this->httpClient->delete("/indexes/{$name}");
            $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException("Failed to delete index: $name. {$e->getMessage()}", 0, $e);
        }
    }

    public function configureIndex(string $name, array $requestData): array
    {
        try {
            $response = $this->httpClient->patch("/indexes/{$name}", ['json' => $requestData]);

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException("Failed to configure index: $name. {$e->getMessage()}", 0, $e);
        }
    }

    // Collection methods
    public function createCollection(array $config): array
    {
        try {
            $response = $this->httpClient->post('/collections', ['json' => $config]);

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to create collection: ' . $e->getMessage(), 0, $e);
        }
    }

    public function listCollections(): array
    {
        try {
            $response = $this->httpClient->get('/collections');
            $data = $this->handleResponse($response);

            return $data['collections'] ?? [];
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to list collections: ' . $e->getMessage(), 0, $e);
        }
    }

    public function describeCollection(string $name): array
    {
        try {
            $response = $this->httpClient->get("/collections/{$name}");

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException("Failed to describe collection: $name. {$e->getMessage()}", 0, $e);
        }
    }

    public function deleteCollection(string $name): void
    {
        try {
            $response = $this->httpClient->delete("/collections/{$name}");
            $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException("Failed to delete collection: $name. {$e->getMessage()}", 0, $e);
        }
    }

    // Backup methods
    public function createBackup(array $config): array
    {
        try {
            $response = $this->httpClient->post('/backups', ['json' => $config]);

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to create backup: ' . $e->getMessage(), 0, $e);
        }
    }

    public function listBackups(): array
    {
        try {
            $response = $this->httpClient->get('/backups');
            $data = $this->handleResponse($response);

            return $data['backups'] ?? [];
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to list backups: ' . $e->getMessage(), 0, $e);
        }
    }

    public function describeBackup(string $id): array
    {
        try {
            $response = $this->httpClient->get("/backups/{$id}");

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException("Failed to describe backup: $id. {$e->getMessage()}", 0, $e);
        }
    }

    public function deleteBackup(string $id): void
    {
        try {
            $response = $this->httpClient->delete("/backups/{$id}");
            $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException("Failed to delete backup: $id. {$e->getMessage()}", 0, $e);
        }
    }

    // Restore methods
    public function listRestoreJobs(array $params = []): array
    {
        try {
            $query = !empty($params) ? '?' . http_build_query($params) : '';
            $response = $this->httpClient->get("/restore{$query}");
            $data = $this->handleResponse($response);

            return $data['jobs'] ?? [];
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to list restore jobs: ' . $e->getMessage(), 0, $e);
        }
    }

    public function describeRestoreJob(string $id): array
    {
        try {
            $response = $this->httpClient->get("/restore/{$id}");

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException("Failed to describe restore job: $id. {$e->getMessage()}", 0, $e);
        }
    }

    // Assistant methods
    public function createAssistant(array $config): array
    {
        try {
            $response = $this->httpClient->post('/assistants', ['json' => $config]);

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to create assistant: ' . $e->getMessage(), 0, $e);
        }
    }

    public function listAssistants(): array
    {
        try {
            $response = $this->httpClient->get('/assistants');
            $data = $this->handleResponse($response);

            return $data['assistants'] ?? [];
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to list assistants: ' . $e->getMessage(), 0, $e);
        }
    }

    public function describeAssistant(string $name): array
    {
        try {
            $response = $this->httpClient->get("/assistants/{$name}");

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException("Failed to describe assistant: $name. {$e->getMessage()}", 0, $e);
        }
    }

    public function updateAssistant(string $name, array $config): array
    {
        try {
            $response = $this->httpClient->patch("/assistants/{$name}", ['json' => $config]);

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException("Failed to update assistant: $name. {$e->getMessage()}", 0, $e);
        }
    }

    public function deleteAssistant(string $name): void
    {
        try {
            $response = $this->httpClient->delete("/assistants/{$name}");
            $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException("Failed to delete assistant: $name. {$e->getMessage()}", 0, $e);
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
