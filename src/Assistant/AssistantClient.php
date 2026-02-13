<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Assistant;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Mbvb1223\Pinecone\Utils\Configuration;
use Mbvb1223\Pinecone\Errors\PineconeException;
use Mbvb1223\Pinecone\Errors\PineconeValidationException;
use Mbvb1223\Pinecone\Utils\HandlesApiResponse;

class AssistantClient
{
    use HandlesApiResponse;

    private Client $httpClient;
    private string $assistantName;

    public function __construct(Configuration $config, string $assistantName, array $assistantInfo = [])
    {
        $host = $assistantInfo['host'] ?? null;
        $baseUri = $host ? "https://{$host}" : $config->getControllerHost();

        $this->assistantName = $assistantName;
        $this->httpClient = new Client([
            'base_uri' => $baseUri,
            'timeout' => $config->getTimeout(),
            'headers' => $config->getDefaultHeaders(),
        ]);
    }

    /**
     * Chat with the assistant.
     *
     * @param array $messages Array of message objects (e.g., [['role' => 'user', 'content' => 'Hello']]).
     * @param array $options Additional options for the chat request.
     * @return array The chat response.
     */
    public function chat(array $messages, array $options = []): array
    {
        if (empty($messages)) {
            throw new PineconeValidationException('At least one message is required for chat.');
        }

        try {
            $payload = array_merge([
                'messages' => $messages,
            ], $options);

            $encodedName = urlencode($this->assistantName);
            $response = $this->httpClient->post("/assistant/chat/{$encodedName}/completions", [
                'json' => $payload,
            ]);

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to chat with assistant: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Upload a file to the assistant's knowledge base.
     *
     * @param string $filePath Path to the file to upload.
     * @return array The upload response.
     */
    public function uploadFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new PineconeValidationException("File not found: {$filePath}");
        }

        $fileHandle = @fopen($filePath, 'r');
        if ($fileHandle === false) {
            throw new PineconeValidationException("Cannot open file: {$filePath}");
        }

        try {
            $encodedName = urlencode($this->assistantName);
            $response = $this->httpClient->post("/assistant/files/{$encodedName}", [
                'multipart' => [
                    [
                        'name' => 'file',
                        'contents' => $fileHandle,
                        'filename' => basename($filePath),
                    ],
                ],
            ]);

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to upload file to assistant: ' . $e->getMessage(), 0, $e);
        } finally {
            if (is_resource($fileHandle)) {
                fclose($fileHandle);
            }
        }
    }

    /**
     * List files uploaded to the assistant.
     *
     * @return array The list of files.
     */
    public function listFiles(): array
    {
        try {
            $encodedName = urlencode($this->assistantName);
            $response = $this->httpClient->get("/assistant/files/{$encodedName}");

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to list assistant files: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Describe a specific file uploaded to the assistant.
     *
     * @param string $fileId The file ID.
     * @return array The file details.
     */
    public function describeFile(string $fileId): array
    {
        try {
            $encodedName = urlencode($this->assistantName);
            $encodedFileId = urlencode($fileId);
            $response = $this->httpClient->get("/assistant/files/{$encodedName}/{$encodedFileId}");

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to describe assistant file: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Delete a file from the assistant.
     *
     * @param string $fileId The file ID.
     */
    public function deleteFile(string $fileId): void
    {
        try {
            $encodedName = urlencode($this->assistantName);
            $encodedFileId = urlencode($fileId);
            $response = $this->httpClient->delete("/assistant/files/{$encodedName}/{$encodedFileId}");
            $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new PineconeException('Failed to delete assistant file: ' . $e->getMessage(), 0, $e);
        }
    }
}
