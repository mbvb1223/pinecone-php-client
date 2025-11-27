<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone;

use Mbvb1223\Pinecone\Control\ControlPlane;
use Mbvb1223\Pinecone\Data\DataPlane;
use Mbvb1223\Pinecone\DTOs\Index;
use Mbvb1223\Pinecone\Inference\InferenceClient;
use Mbvb1223\Pinecone\Assistant\AssistantClient;
use Mbvb1223\Pinecone\Utils\Configuration;

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

    public function createIndex(string $name, array $requestData): array
    {
        return $this->controlPlane->createIndex($name, $requestData);
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

    public function addHeader(string $name, string $value): self
    {
        $this->config->addHeader($name, $value);

        return $this;
    }

    public function removeHeader(string $name): self
    {
        $this->config->removeHeader($name);

        return $this;
    }

    public function setHeaders(array $headers): self
    {
        $this->config->setHeaders($headers);

        return $this;
    }
}
