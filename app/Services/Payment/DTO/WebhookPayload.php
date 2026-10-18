<?php

namespace App\Services\Payment\DTO;

use App\Enums\PaymentDepositStatus;

class WebhookPayload
{
    public function __construct(
        protected string $eventId,
        protected string $eventType,
        protected ?string $depositNumber = null,
        protected ?string $transactionId = null,
        protected PaymentDepositStatus $status = PaymentDepositStatus::PENDING,
        protected ?string $amount = null,
        protected ?string $currency = null,
        protected ?string $fee = '0.0000',
        protected ?string $failureReason = null,
        protected array $raw = []
    ) {}

    public function eventId(): string
    {
        return $this->eventId;
    }

    public function eventType(): string
    {
        return $this->eventType;
    }

    public function depositNumber(): ?string
    {
        return $this->depositNumber;
    }

    public function transactionId(): ?string
    {
        return $this->transactionId;
    }

    public function status(): PaymentDepositStatus
    {
        return $this->status;
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

    public function failureReason(): ?string
    {
        return $this->failureReason;
    }

    public function raw(): array
    {
        return $this->raw;
    }
}
