<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone;

use GuzzleHttp\Client;
use Mbvb1223\Pinecone\Assistant\AssistantClient;
use Mbvb1223\Pinecone\Control\ControlPlane;
use Mbvb1223\Pinecone\Data\Index;
use Mbvb1223\Pinecone\Errors\PineconeException;
use Mbvb1223\Pinecone\Inference\InferenceClient;
use Mbvb1223\Pinecone\Utils\Configuration;

class Pinecone
{
    private readonly Configuration $config;
    private readonly ControlPlane $controlPlane;
    private ?InferenceClient $inferenceClient = null;

    /** @var array<string, Index> */
    private array $indexCache = [];

    public function __construct(?string $apiKey = null, ?array $config = null)
    {
        $this->config = new Configuration($apiKey, $config);
        $client = new Client([
            'base_uri' => $this->config->getControllerHost(),
            'timeout' => $this->config->getTimeout(),
            'headers' => $this->config->getDefaultHeaders(),
        ]);
        $this->controlPlane = new ControlPlane($client);
    }

    // ===== Factory methods to get sub-components =====
    public function index(string $name): Index
    {
        if (trim($name) === '') {
            throw new PineconeException('Index name must not be empty.');
        }

        if (isset($this->indexCache[$name])) {
            return $this->indexCache[$name];
        }

        $indexInfo = $this->describeIndex($name);
        $host = $indexInfo['host'] ?? null;
        if (!$host) {
            throw new PineconeException("Index '$name' does not have a host URL.");
        }
        $client = new Client([
            'base_uri' => "https://{$host}",
            'timeout' => $this->config->getTimeout(),
            'headers' => $this->config->getDefaultHeaders(),
        ]);

        $index = new Index($client);
        $this->indexCache[$name] = $index;

        return $index;
    }

    public function inference(): InferenceClient
    {
        if ($this->inferenceClient === null) {
            $this->inferenceClient = new InferenceClient($this->config);
        }

        return $this->inferenceClient;
    }

    public function assistant(string $name): AssistantClient
    {
        if (trim($name) === '') {
            throw new PineconeException('Assistant name must not be empty.');
        }

        $assistantInfo = $this->describeAssistant($name);

        return new AssistantClient($this->config, $name, $assistantInfo);
    }

    // ===== Index control plane methods =====
    public function listIndexes(): array
    {
        return $this->controlPlane->listIndexes();
    }

    public function createIndex(string $name, array $requestData): array
    {
        return $this->controlPlane->createIndex($name, $requestData);
    }

    public function createForModel(string $name, array $requestData): array
    {
        return $this->controlPlane->createForModel($name, $requestData);
    }

    public function describeIndex(string $name): array
    {
        return $this->controlPlane->describeIndex($name);
    }

    public function deleteIndex(string $name): void
    {
        $this->controlPlane->deleteIndex($name);
    }

    public function configureIndex(string $name, array $requestData): array
    {
        return $this->controlPlane->configureIndex($name, $requestData);
    }

    public function hasIndex(string $name): bool
    {
        try {
            $this->describeIndex($name);

            return true;
        } catch (PineconeException) {
            return false;
        }
    }

    // ===== Collection control plane methods =====
    public function createCollection(array $config): array
    {
        return $this->controlPlane->createCollection($config);
    }

    public function listCollections(): array
    {
        return $this->controlPlane->listCollections();
    }

    public function describeCollection(string $name): array
    {
        return $this->controlPlane->describeCollection($name);
    }

    public function deleteCollection(string $name): void
    {
        $this->controlPlane->deleteCollection($name);
    }

    // ===== Backup control plane methods =====
    public function createBackup(array $config): array
    {
        return $this->controlPlane->createBackup($config);
    }

    public function listBackups(): array
    {
        return $this->controlPlane->listBackups();
    }

    public function describeBackup(string $id): array
    {
        return $this->controlPlane->describeBackup($id);
    }

    public function deleteBackup(string $id): void
    {
        $this->controlPlane->deleteBackup($id);
    }

    // ===== Restore control plane methods =====
    public function createIndexFromBackup(string $backupId, array $config): array
    {
        return $this->controlPlane->createIndexFromBackup($backupId, $config);
    }

    public function listRestoreJobs(array $params = []): array
    {
        return $this->controlPlane->listRestoreJobs($params);
    }

    public function describeRestoreJob(string $id): array
    {
        return $this->controlPlane->describeRestoreJob($id);
    }

    // ===== Assistant control plane methods =====
    public function createAssistant(array $config): array
    {
        return $this->controlPlane->createAssistant($config);
    }

    public function listAssistants(): array
    {
        return $this->controlPlane->listAssistants();
    }

    public function describeAssistant(string $name): array
    {
        return $this->controlPlane->describeAssistant($name);
    }

    public function updateAssistant(string $name, array $config): array
    {
        return $this->controlPlane->updateAssistant($name, $config);
    }

    public function deleteAssistant(string $name): void
    {
        $this->controlPlane->deleteAssistant($name);
    }
}
