<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Errors;

use Exception;

class PineconeApiException extends PineconeException
{
    private array $responseData;

    public function __construct(string $message, int $statusCode, array $responseData = [], ?Exception $previous = null)
    {
        $this->responseData = $responseData;

        parent::__construct($message, $statusCode, $previous);
    }

    public function getResponseData(): array
    {
        return $this->responseData;
    }
}
