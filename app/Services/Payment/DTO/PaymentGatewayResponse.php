<?php

namespace App\Services\Payment\DTO;

class PaymentGatewayResponse
{
    public function __construct(
        protected bool $success,
        protected ?string $redirectUrl = null,
        protected ?string $transactionId = null,
        protected ?string $message = null,
        protected array $raw = []
    ) {}

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function redirectUrl(): ?string
    {
        return $this->redirectUrl;
    }

    public function transactionId(): ?string
    {
        return $this->transactionId;
    }

    public function message(): ?string
    {
        return $this->message;
    }

    public function raw(): array
    {
        return $this->raw;
    }
}
