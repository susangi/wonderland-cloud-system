<?php

namespace App\Exceptions;

use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;

// Base class for business errors (not found, slot full, payment down ...).
// Services throw these and Laravel calls render() to build the JSON error.
// Controllers do not need to catch them.
class ApiException extends Exception
{
    use ApiResponse;

    public function __construct(
        private string $errorCode,
        string $message,
        private int $httpStatus,
    ) {
        parent::__construct($message);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function httpStatus(): int
    {
        return $this->httpStatus;
    }

    // Laravel calls this automatically when the exception is not caught.
    public function render(): JsonResponse
    {
        return $this->errorResponse($this->errorCode, $this->getMessage(), $this->httpStatus);
    }
}
