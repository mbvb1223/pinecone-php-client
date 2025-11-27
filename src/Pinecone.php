<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone;

use Mbvb1223\Pinecone\Control\ControlPlane;
use Mbvb1223\Pinecone\Data\Index;
use Mbvb1223\Pinecone\Inference\InferenceClient;
use Mbvb1223\Pinecone\Assistant\AssistantClient;
use Mbvb1223\Pinecone\Utils\Configuration;

class Pinecone
{
    private Configuration $config;
    private ControlPlane $controlPlane;

    public function __construct(?string $apiKey = null, ?array $config = null)
    {
        $this->config = new Configuration($apiKey, $config);
        $this->controlPlane = new ControlPlane($this->config);
    }

    // Factory methods to get sub-components
    public function index(string $name): Index
    {
        $indexInfo = $this->describeIndex($name);
        return new Index($this->config, $indexInfo);
    }

    public function inference(): InferenceClient
    {
        return new InferenceClient($this->config);
    }

    public function assistant(string $name): AssistantClient
    {
        $assistantInfo = $this->describeAssistant($name);
        return new AssistantClient($this->config, $assistantInfo);
    }

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

    // Collection control plane methods
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

    // Backup control plane methods
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

    // Restore operations
    public function listRestoreJobs(array $params = []): array
    {
        return $this->controlPlane->listRestoreJobs($params);
    }

    public function describeRestoreJob(string $id): array
    {
        return $this->controlPlane->describeRestoreJob($id);
    }

    // Assistant control plane methods
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
