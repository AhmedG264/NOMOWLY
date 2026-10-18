<?php

namespace App\Services\Payment;

use App\Enums\Currency;
use App\Enums\PaymentProvider;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\Gateways\FakePaymentGateway;
use App\Services\Payment\Gateways\MoyasarGateway;
use App\Services\Payment\Gateways\TapGateway;
use InvalidArgumentException;

class PaymentGatewayManager
{
    /**
     * @var array<string, PaymentGatewayInterface>
     */
    protected array $gateways = [];

    public function __construct()
    {
        $this->register(new MoyasarGateway());
        $this->register(new TapGateway());
        $this->register(new FakePaymentGateway());
    }

    public function register(PaymentGatewayInterface $gateway): void
    {
        $this->gateways[$gateway->getProvider()->value] = $gateway;
    }

    public function getGateway(PaymentProvider|string $provider): PaymentGatewayInterface
    {
        $key = $provider instanceof PaymentProvider ? $provider->value : strtolower(trim($provider));

        if (!isset($this->gateways[$key])) {
            throw new InvalidArgumentException("مزود الدفع المحدد غير مسجل في النظام: [{$key}]");
        }

        return $this->gateways[$key];
    }

    /**
     * Get all configured, live gateways that support the given currency and country.
     * In non-production testing, may include Fake gateway if enabled.
     *
     * @return array<string, PaymentGatewayInterface>
     */
    public function getAvailableGateways(Currency|string $currency, ?string $country = null): array
    {
        $available = [];

        foreach ($this->gateways as $providerKey => $gateway) {
            // Check if gateway is configured
            if (!$gateway->isConfigured()) {
                continue;
            }

            // In production, strictly reject the Fake gateway
            if (app()->environment('production') && $gateway->getProvider() === PaymentProvider::FAKE) {
                continue;
            }

            // Check currency support
            if (!$gateway->supportsCurrency($currency)) {
                continue;
            }

            // Check country support if specified
            if ($country && !$gateway->supportsCountry($country)) {
                continue;
            }

            $available[$providerKey] = $gateway;
        }

        return $available;
    }

    /**
     * Check if any live, real-world payment gateway is currently configured.
     */
    public function isAnyLiveGatewayConfigured(): bool
    {
        foreach ($this->gateways as $gateway) {
            if ($gateway->getProvider() === PaymentProvider::FAKE) {
                continue;
            }

            if ($gateway->isConfigured()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find best default gateway for a currency and country.
     */
    public function getDefaultGatewayFor(Currency|string $currency, ?string $country = null): ?PaymentGatewayInterface
    {
        $available = $this->getAvailableGateways($currency, $country);

        // Preference: Moyasar for SA/SAR, Tap for others
        $currCode = $currency instanceof Currency ? $currency->value : strtoupper(trim($currency));

        if (($currCode === 'SAR' || strtoupper((string) $country) === 'SA') && isset($available[PaymentProvider::MOYASAR->value])) {
            return $available[PaymentProvider::MOYASAR->value];
        }

        if (isset($available[PaymentProvider::TAP->value])) {
            return $available[PaymentProvider::TAP->value];
        }

        // Return first available (or fake in testing)
        return !empty($available) ? reset($available) : null;
    }
}
