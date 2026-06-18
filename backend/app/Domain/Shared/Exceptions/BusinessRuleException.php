<?php

namespace App\Domain\Shared\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class BusinessRuleException extends Exception
{
    public function __construct(
        string $message,
        protected ?string $rule = null,
        protected array $context = [],
        int $code = 422,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getRule(): ?string
    {
        return $this->rule;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'rule' => $this->getRule(),
            'context' => $this->getContext(),
        ], $this->getCode());
    }
}
