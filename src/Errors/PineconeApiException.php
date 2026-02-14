<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Errors;

class PineconeApiException extends PineconeException
{
    /** @var array<string, mixed> */
    private readonly array $responseData;

    /** @param array<string, mixed> $responseData */
    public function __construct(string $message, int $statusCode, array $responseData = [], ?\Throwable $previous = null)
    {
        $this->responseData = $responseData;

        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->getCode();
    }

    /** @return array<string, mixed> */
    public function getResponseData(): array
    {
        return $this->responseData;
    }
}
