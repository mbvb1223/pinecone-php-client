<?php

declare(strict_types=1);

namespace Pinecone;

use Pinecone\Control\ControlPlane;
use Pinecone\Data\DataPlane;
use Pinecone\Inference\InferenceClient;
use Pinecone\Assistant\AssistantClient;
use Pinecone\Utils\Configuration;
use Pinecone\Errors\PineconeException;

class Pinecone
{
    private Configuration $config;
    private ControlPlane $controlPlane;
    private array $dataClients = [];

    public function __construct(?string $apiKey = null, ?array $config = null)
    {
        $this->config = new Configuration($apiKey, $config);
        $this->controlPlane = new ControlPlane($this->config);
    }

    public function listIndexes(): array
    {
        return $this->controlPlane->listIndexes();
    }

    public function createIndex(string $name, array $spec): void
    {
        $this->controlPlane->createIndex($name, $spec);
    }

    public function describeIndex(string $name): array
    {
        return $this->controlPlane->describeIndex($name);
    }

    public function deleteIndex(string $name): void
    {
        $this->controlPlane->deleteIndex($name);
    }

    public function index(string $name): DataPlane
    {
        if (!isset($this->dataClients[$name])) {
            $indexInfo = $this->describeIndex($name);
            $this->dataClients[$name] = new DataPlane($this->config, $indexInfo);
        }

        return $this->dataClients[$name];
    }

    public function inference(): InferenceClient
    {
        return new InferenceClient($this->config);
    }

    public function assistant(): AssistantClient
    {
        return new AssistantClient($this->config);
    }
}