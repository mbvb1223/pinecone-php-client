<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Inference;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Mbvb1223\Pinecone\Errors\PineconeException;
use Mbvb1223\Pinecone\Errors\PineconeValidationException;
use Mbvb1223\Pinecone\Utils\Configuration;
use Mbvb1223\Pinecone\Utils\HandlesApiResponse;

class InferenceClient
{
    use HandlesApiResponse;

    private Client $httpClient;

    public function __construct(Configuration $config)
    {
        $this->httpClient = new Client([
            'base_uri' => $config->getControllerHost(),
            'timeout' => $config->getTimeout(),
            'headers' => $config->getDefaultHeaders(),
        ]);
    }

    /**
     * Generate embeddings for the given inputs.
     *
     * @param string $model The embedding model to use.
     * @param array $inputs Array of input objects (e.g., [['text' => 'hello'], ['text' => 'world']]).
     * @param array $parameters Optional model-specific parameters.
     * @return array The embedding response.
     */
    public function embed(string $model, array $inputs, array $parameters = []): array
    {
        if (empty($model)) {
            throw new PineconeValidationException('Model name is required for embedding.');
        }

        if (empty($inputs)) {
            throw new PineconeValidationException('At least one input is required for embedding.');
        }

        try {
            // Normalize inputs: accept strings or objects with 'text' key
            $normalizedInputs = array_map(function ($input) {
                if (is_string($input)) {
                    return ['text' => $input];
                }

                return $input;
            }, $inputs);

            $payload = [
                'model' => $model,
                'inputs' => $normalizedInputs,
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

    /**
     * Rerank documents by relevance to a query.
     *
     * @param string $model The reranking model to use.
     * @param string $query The query to rank against.
     * @param array $documents The documents to rerank.
     * @param int $topN Number of top results to return (0 means return all).
     * @param bool $returnDocuments Whether to include documents in the response.
     * @param array $rankFields Fields to use for ranking.
     * @param array $parameters Optional model-specific parameters.
     * @return array The reranking response.
     */
    public function rerank(
        string $model,
        string $query,
        array $documents,
        int $topN = 0,
        bool $returnDocuments = true,
        array $rankFields = [],
        array $parameters = [],
    ): array {
        if (empty($model)) {
            throw new PineconeValidationException('Model name is required for reranking.');
        }

        if (empty($query)) {
            throw new PineconeValidationException('Query is required for reranking.');
        }

        if (empty($documents)) {
            throw new PineconeValidationException('At least one document is required for reranking.');
        }

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
