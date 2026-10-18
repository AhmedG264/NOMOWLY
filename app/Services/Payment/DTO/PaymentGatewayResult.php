<?php

namespace App\Services\Payment\DTO;

use App\Enums\PaymentDepositStatus;

class PaymentGatewayResult
{
    public function __construct(
        protected PaymentDepositStatus $status,
        protected ?string $transactionId = null,
        protected ?string $amount = null,
        protected ?string $currency = null,
        protected ?string $fee = '0.0000',
        protected ?string $message = null,
        protected array $raw = []
    ) {}

    public function status(): PaymentDepositStatus
    {
        return $this->status;
    }

    public function isSuccessful(): bool
    {
        return $this->status === PaymentDepositStatus::SUCCEEDED;
    }

    public function isPending(): bool
    {
        return $this->status === PaymentDepositStatus::PENDING || $this->status === PaymentDepositStatus::PROCESSING;
    }

    public function isFailed(): bool
    {
        return $this->status === PaymentDepositStatus::FAILED || $this->status === PaymentDepositStatus::CANCELLED;
    }

    public function transactionId(): ?string
    {
        return $this->transactionId;
    }

    public function amount(): ?string
    {
        return $this->amount;
    }

    public function currency(): ?string
    {
        return $this->currency;
    }

    public function fee(): ?string
    {
        return $this->fee;
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
