<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Control;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Mbvb1223\Pinecone\Errors\PineconeException;
use Mbvb1223\Pinecone\Utils\HandlesApiResponse;

class ControlPlane
{
    use HandlesApiResponse;

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
            $payload = array_merge(['metric' => 'cosine'], $requestData, ['name' => $name]);

            $response = $this->httpClient->post('/indexes', ['json' => $payload]);

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException("Failed to create index: $name. {$e->getMessage()}", 0, $e);
        }
    }

    public function createForModel(string $name, array $requestData): array
    {
        try {
            $payload = array_merge(['name' => $name], $requestData);

            $response = $this->httpClient->post('/indexes/create-for-model', ['json' => $payload]);

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException("Failed to create index for model: $name. {$e->getMessage()}", 0, $e);
        }
    }

    public function describeIndex(string $name): array
    {
        try {
            $encodedName = urlencode($name);
            $response = $this->httpClient->get("/indexes/{$encodedName}");

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException("Failed to describe index: $name. {$e->getMessage()}", 0, $e);
        }
    }

    public function deleteIndex(string $name): void
    {
        try {
            $encodedName = urlencode($name);
            $response = $this->httpClient->delete("/indexes/{$encodedName}");
            $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException("Failed to delete index: $name. {$e->getMessage()}", 0, $e);
        }
    }

    public function configureIndex(string $name, array $requestData): array
    {
        try {
            $encodedName = urlencode($name);
            $response = $this->httpClient->patch("/indexes/{$encodedName}", ['json' => $requestData]);

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
            $encodedName = urlencode($name);
            $response = $this->httpClient->get("/collections/{$encodedName}");

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException("Failed to describe collection: $name. {$e->getMessage()}", 0, $e);
        }
    }

    public function deleteCollection(string $name): void
    {
        try {
            $encodedName = urlencode($name);
            $response = $this->httpClient->delete("/collections/{$encodedName}");
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

            return $data['data'] ?? [];
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to list backups: ' . $e->getMessage(), 0, $e);
        }
    }

    public function describeBackup(string $id): array
    {
        try {
            $encodedId = urlencode($id);
            $response = $this->httpClient->get("/backups/{$encodedId}");

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException("Failed to describe backup: $id. {$e->getMessage()}", 0, $e);
        }
    }

    public function deleteBackup(string $id): void
    {
        try {
            $encodedId = urlencode($id);
            $response = $this->httpClient->delete("/backups/{$encodedId}");
            $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException("Failed to delete backup: $id. {$e->getMessage()}", 0, $e);
        }
    }

    // Restore methods
    public function createIndexFromBackup(string $backupId, array $config): array
    {
        try {
            $encodedId = urlencode($backupId);
            $response = $this->httpClient->post("/backups/{$encodedId}/create-index", ['json' => $config]);

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException("Failed to create index from backup: $backupId. {$e->getMessage()}", 0, $e);
        }
    }

    public function listRestoreJobs(array $params = []): array
    {
        try {
            $options = !empty($params) ? ['query' => $params] : [];
            $response = $this->httpClient->get('/restore-jobs', $options);
            $data = $this->handleResponse($response);

            return $data['data'] ?? [];
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to list restore jobs: ' . $e->getMessage(), 0, $e);
        }
    }

    public function describeRestoreJob(string $id): array
    {
        try {
            $encodedId = urlencode($id);
            $response = $this->httpClient->get("/restore-jobs/{$encodedId}");

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
            $encodedName = urlencode($name);
            $response = $this->httpClient->get("/assistants/{$encodedName}");

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException("Failed to describe assistant: $name. {$e->getMessage()}", 0, $e);
        }
    }

    public function updateAssistant(string $name, array $config): array
    {
        try {
            $encodedName = urlencode($name);
            $response = $this->httpClient->patch("/assistants/{$encodedName}", ['json' => $config]);

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException("Failed to update assistant: $name. {$e->getMessage()}", 0, $e);
        }
    }

    public function deleteAssistant(string $name): void
    {
        try {
            $encodedName = urlencode($name);
            $response = $this->httpClient->delete("/assistants/{$encodedName}");
            $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException("Failed to delete assistant: $name. {$e->getMessage()}", 0, $e);
        }
    }
}
