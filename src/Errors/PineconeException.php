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
