<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Utils;

use Mbvb1223\Pinecone\Errors\PineconeApiException;
use Mbvb1223\Pinecone\Errors\PineconeAuthException;
use Mbvb1223\Pinecone\Errors\PineconeException;
use Mbvb1223\Pinecone\Errors\PineconeTimeoutException;
use Psr\Http\Message\ResponseInterface;

trait HandlesApiResponse
{
    private function handleResponse(ResponseInterface $response): array
    {
        $statusCode = $response->getStatusCode();
        $body = $response->getBody()->getContents();

        if ($statusCode >= 400) {
            $data = json_decode($body, true) ?? [];
            $message = $data['message'] ?? $data['error']['message'] ?? $data['error'] ?? 'API request failed';
            if (is_array($message)) {
                $message = json_encode($message);
            }

            if ($statusCode === 401 || $statusCode === 403) {
                throw new PineconeAuthException($message, $statusCode);
            }

            if ($statusCode === 408 || $statusCode === 504) {
                throw new PineconeTimeoutException($message, $statusCode);
            }

            throw new PineconeApiException($message, $statusCode, $data);
        }

        if (empty($body)) {
            return [];
        }

        $decoded = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new PineconeException('Failed to decode JSON response: ' . json_last_error_msg());
        }

        return $decoded ?? [];
    }
}
