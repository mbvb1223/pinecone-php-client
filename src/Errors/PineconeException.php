<?php

declare(strict_types=1);

namespace Mbvb1223\Pinecone\Errors;

use Exception;

class PineconeException extends Exception
{
    public function __construct(string $message = '', int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

class PineconeApiException extends PineconeException
{
    private array $responseData;
    private int $statusCode;

    public function __construct(string $message, int $statusCode, array $responseData = [], ?Exception $previous = null)
    {
        $this->statusCode = $statusCode;
        $this->responseData = $responseData;
        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getResponseData(): array
    {
        return $this->responseData;
    }
}

class PineconeAuthException extends PineconeException
{
}

class PineconeValidationException extends PineconeException
{
}

class PineconeTimeoutException extends PineconeException
{
}