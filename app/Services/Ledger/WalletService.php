<?php

namespace App\Services\Ledger;

use App\Enums\AuditAction;
use App\Enums\Currency;
use App\Enums\WalletStatus;
use App\Enums\WalletType;
use App\Models\User;
use App\Models\Wallet;
use App\Services\AuditService;
use App\Services\Math\MoneyMath;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class WalletService
{
    /**
     * Get or create a wallet for a user in a specific currency.
     * Enforces strict uniqueness (one wallet per user and currency).
     */
    public function getOrCreateWallet(
        User $user,
        string|Currency $currency,
        ?WalletType $type = null
    ): Wallet {
        $currencyEnum = $currency instanceof Currency ? $currency : Currency::from(strtoupper(trim($currency)));
        $currencyCode = $currencyEnum->value;

        // Determine wallet type automatically based on user role if not provided
        if (!$type) {
            $type = match (true) {
                $user->isBrand() => WalletType::BRAND,
                $user->isMarketer() => WalletType::MARKETER,
                default => WalletType::BRAND,
            };
        }

        return DB::transaction(function () use ($user, $currencyCode, $type) {
            // Check for existing wallet with lock to avoid race conditions
            $wallet = Wallet::where('user_id', $user->id)
                ->where('currency', $currencyCode)
                ->lockForUpdate()
                ->first();

            if ($wallet) {
                return $wallet;
            }

            // Create new wallet
            $wallet = Wallet::create([
                'user_id' => $user->id,
                'wallet_type' => $type,
                'currency' => $currencyCode,
                'available_balance' => '0.0000',
                'reserved_balance' => '0.0000',
                'status' => WalletStatus::ACTIVE,
            ]);

            // Record in audit log
            AuditService::log(
                action: AuditAction::WALLET_CREATED,
                userId: $user->id,
                entityType: Wallet::class,
                entityId: $wallet->id,
                payloadDiff: [
                    'new' => [
                        'wallet_id' => $wallet->id,
                        'user_id' => $user->id,
                        'currency' => $currencyCode,
                        'type' => $type->value,
                    ],
                ],
                status: 'SUCCESS',
                reason: "إنشاء محفظة مالية للمستخدم بالعملة {$currencyCode}"
            );

            return $wallet;
        });
    }

    /**
     * Get all wallets belonging to a user.
     *
     * @return Collection<int, Wallet>
     */
    public function getWalletsForUser(User $user): Collection
    {
        return Wallet::where('user_id', $user->id)
            ->orderBy('currency', 'asc')
            ->get();
    }

    /**
     * Freeze wallet (prevents debits and withdrawals).
     */
    public function freezeWallet(Wallet $wallet, User $performer, string $reason): void
    {
        if ($wallet->status === WalletStatus::FROZEN) {
            return;
        }

        $oldStatus = $wallet->status->value;

        DB::transaction(function () use ($wallet, $performer, $reason, $oldStatus) {
            $wallet->update(['status' => WalletStatus::FROZEN]);

            AuditService::log(
                action: AuditAction::WALLET_STATUS_CHANGED,
                userId: $performer->id,
                entityType: Wallet::class,
                entityId: $wallet->id,
                payloadDiff: [
                    'old' => ['status' => $oldStatus],
                    'new' => ['status' => WalletStatus::FROZEN->value],
                ],
                status: 'SUCCESS',
                reason: $reason
            );
        });
    }

    /**
     * Unfreeze / Activate a wallet.
     */
    public function activateWallet(Wallet $wallet, User $performer, string $reason): void
    {
        if ($wallet->status === WalletStatus::ACTIVE) {
            return;
        }

        $oldStatus = $wallet->status->value;

        DB::transaction(function () use ($wallet, $performer, $reason, $oldStatus) {
            $wallet->update(['status' => WalletStatus::ACTIVE]);

            AuditService::log(
                action: AuditAction::WALLET_STATUS_CHANGED,
                userId: $performer->id,
                entityType: Wallet::class,
                entityId: $wallet->id,
                payloadDiff: [
                    'old' => ['status' => $oldStatus],
                    'new' => ['status' => WalletStatus::ACTIVE->value],
                ],
                status: 'SUCCESS',
                reason: $reason
            );
        });
    }

    /**
     * Reconcile wallet balance against ledger statement entries.
     */
    public function reconcileWallet(Wallet $wallet): array
    {
        $lastTx = $wallet->transactions()->latest('created_at')->first();

        $cachedAvailable = MoneyMath::format($wallet->available_balance);
        $cachedReserved = MoneyMath::format($wallet->reserved_balance);
        $cachedTotal = MoneyMath::add($cachedAvailable, $cachedReserved);

        $statementAvailable = $lastTx ? MoneyMath::format($lastTx->balance_after) : '0.0000';
        $statementReserved = $lastTx ? MoneyMath::format($lastTx->reserved_after) : '0.0000';
        $statementTotal = MoneyMath::add($statementAvailable, $statementReserved);

        $isAvailableMatched = MoneyMath::comp($cachedAvailable, $statementAvailable) === 0;
        $isReservedMatched = MoneyMath::comp($cachedReserved, $statementReserved) === 0;

        return [
            'wallet_id' => $wallet->id,
            'currency' => $wallet->currency->value,
            'cached_available' => $cachedAvailable,
            'cached_reserved' => $cachedReserved,
            'cached_total' => $cachedTotal,
            'statement_available' => $statementAvailable,
            'statement_reserved' => $statementReserved,
            'statement_total' => $statementTotal,
            'is_matched' => $isAvailableMatched && $isReservedMatched,
        ];
    }
}
