<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Errors;

class PineconeApiException extends PineconeException
{
    private readonly array $responseData;

    public function __construct(string $message, int $statusCode, array $responseData = [], ?\Throwable $previous = null)
    {
        $this->responseData = $responseData;

        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->getCode();
    }

    public function getResponseData(): array
    {
        return $this->responseData;
    }
}
