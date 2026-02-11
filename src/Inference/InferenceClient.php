<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Inference;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Mbvb1223\Pinecone\Utils\Configuration;
use Mbvb1223\Pinecone\Errors\PineconeException;
use Mbvb1223\Pinecone\Utils\HandlesApiResponse;

class InferenceClient
{
    use HandlesApiResponse;

    private Client $httpClient;

    public function __construct(Configuration $config)
    {
        $this->httpClient = new Client([
            'base_uri' => 'https://api.pinecone.io',
            'timeout' => $config->getTimeout(),
            'headers' => $config->getDefaultHeaders(),
        ]);
    }

    public function embed(string $model, array $inputs, array $parameters = []): array
    {
        try {
            $payload = [
                'model' => $model,
                'inputs' => $inputs,
            ];

            if (!empty($parameters)) {
                $payload['parameters'] = (object) $parameters;
            }

            $response = $this->httpClient->post('/embed', [
                'json' => $payload,
            ]);

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to generate embeddings: ' . $e->getMessage(), 0, $e);
        }
    }

    public function rerank(
        string $model,
        string $query,
        array $documents,
        int $topN = 0,
        bool $returnDocuments = true,
        array $rankFields = [],
        array $parameters = []
    ): array {
        try {
            $payload = [
                'model' => $model,
                'query' => $query,
                'documents' => $documents,
                'return_documents' => $returnDocuments,
            ];

            if ($topN > 0) {
                $payload['top_n'] = $topN;
            }

            if (!empty($rankFields)) {
                $payload['rank_fields'] = $rankFields;
            }

            if (!empty($parameters)) {
                $payload['parameters'] = (object) $parameters;
            }

            $response = $this->httpClient->post('/rerank', [
                'json' => $payload,
            ]);

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to rerank documents: ' . $e->getMessage(), 0, $e);
        }
    }

    public function listModels(): array
    {
        try {
            $response = $this->httpClient->get('/models');

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to list models: ' . $e->getMessage(), 0, $e);
        }
    }
}
