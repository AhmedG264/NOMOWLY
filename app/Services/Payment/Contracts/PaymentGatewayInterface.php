<?php

namespace App\Services\Payment\Contracts;

use App\Enums\Currency;
use App\Enums\PaymentProvider;
use App\Models\PaymentDeposit;
use App\Models\PaymentRefund;
use App\Services\Payment\DTO\PaymentGatewayResponse;
use App\Services\Payment\DTO\PaymentGatewayResult;
use App\Services\Payment\DTO\WebhookPayload;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    /**
     * Get the payment provider enum identifier.
     */
    public function getProvider(): PaymentProvider;

    /**
     * Check if the gateway credentials and configuration are properly set.
     */
    public function isConfigured(): bool;

    /**
     * Check if this gateway supports a given currency.
     */
    public function supportsCurrency(Currency|string $currency): bool;

    /**
     * Check if this gateway supports a given country code (ISO 3166-1 alpha-2, e.g. SA, EG, AE).
     */
    public function supportsCountry(string $country): bool;

    /**
     * List supported payment methods (e.g. ['mada', 'creditcard', 'applepay']).
     *
     * @return list<string>
     */
    public function supportedMethods(): array;

    /**
     * Create / initialize a payment with the provider.
     *
     * @param array<string, mixed> $options
     */
    public function createPayment(PaymentDeposit $deposit, array $options = []): PaymentGatewayResponse;

    /**
     * Verify payment status directly with the provider (e.g. after customer redirect return).
     *
     * @param array<string, mixed> $payload
     */
    public function verifyPayment(PaymentDeposit $deposit, array $payload = []): PaymentGatewayResult;

    /**
     * Check current status of transaction on the provider API.
     */
    public function checkStatus(PaymentDeposit $deposit): PaymentGatewayResult;

    /**
     * Verify the webhook request cryptographic signature / authentication token.
     */
    public function verifyWebhookSignature(Request $request): bool;

    /**
     * Parse the webhook request into a normalized WebhookPayload DTO.
     */
    public function parseWebhook(Request $request): WebhookPayload;

    /**
     * Request a full or partial refund from the provider.
     */
    public function refund(PaymentRefund $refund): PaymentGatewayResult;
}
