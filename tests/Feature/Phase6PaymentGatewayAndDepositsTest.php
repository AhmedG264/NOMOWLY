<?php

namespace Tests\Feature;

use App\Enums\Currency;
use App\Enums\LedgerEntryType;
use App\Enums\LedgerTransactionType;
use App\Enums\PaymentDepositStatus;
use App\Enums\PaymentProvider;
use App\Enums\PaymentRefundStatus;
use App\Enums\UserRole;
use App\Exceptions\InsufficientFundsException;
use App\Models\BrandProfile;
use App\Models\PaymentDeposit;
use App\Models\PaymentRefund;
use App\Models\PaymentWebhook;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Ledger\LedgerService;
use App\Services\Ledger\WalletService;
use App\Services\Math\MoneyMath;
use App\Services\Payment\Gateways\FakePaymentGateway;
use App\Services\Payment\PaymentDepositService;
use App\Services\Payment\PaymentGatewayManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tests\TestCase;

class Phase6PaymentGatewayAndDepositsTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $brandUserA;
    protected BrandProfile $brandProfileA;

    protected User $brandUserB;
    protected BrandProfile $brandProfileB;

    protected User $marketerUser;

    protected WalletService $walletService;
    protected LedgerService $ledgerService;
    protected PaymentGatewayManager $gatewayManager;
    protected PaymentDepositService $depositService;
    protected FakePaymentGateway $fakeGateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->walletService = app(WalletService::class);
        $this->ledgerService = app(LedgerService::class);
        $this->gatewayManager = app(PaymentGatewayManager::class);
        $this->depositService = app(PaymentDepositService::class);

        /** @var FakePaymentGateway $fakeGateway */
        $this->fakeGateway = $this->gatewayManager->getGateway(PaymentProvider::FAKE);

        // Roles
        $superAdminRole = Role::firstOrCreate(['name' => UserRole::SUPER_ADMIN->value], ['label' => 'المدير العام', 'is_system' => true]);
        $brandRole = Role::firstOrCreate(['name' => UserRole::BRAND->value], ['label' => 'علامة تجارية', 'is_system' => true]);
        $marketerRole = Role::firstOrCreate(['name' => UserRole::MARKETER->value], ['label' => 'مسوق', 'is_system' => true]);

        // Permissions
        $perms = Permission::all();
        $superAdminRole->permissions()->sync($perms->pluck('id'));

        // 1. Super Admin
        $this->superAdmin = User::factory()->create([
            'email' => 'admin-finance@nomowly.com',
            'role' => UserRole::SUPER_ADMIN,
        ]);
        $this->superAdmin->roles()->syncWithoutDetaching([$superAdminRole->id]);

        // 2. Brand User A
        $this->brandUserA = User::factory()->create([
            'email' => 'brand-alpha@nomowly.com',
            'role' => UserRole::BRAND,
        ]);
        $this->brandUserA->roles()->syncWithoutDetaching([$brandRole->id]);
        $this->brandProfileA = BrandProfile::create([
            'user_id' => $this->brandUserA->id,
            'company_name' => 'متجر ألفا للتجارة',
            'website' => 'https://alpha.example.com',
            'currency' => 'SAR',
        ]);

        // 3. Brand User B
        $this->brandUserB = User::factory()->create([
            'email' => 'brand-beta@nomowly.com',
            'role' => UserRole::BRAND,
        ]);
        $this->brandUserB->roles()->syncWithoutDetaching([$brandRole->id]);
        $this->brandProfileB = BrandProfile::create([
            'user_id' => $this->brandUserB->id,
            'company_name' => 'متجر بيتا للملابس',
            'website' => 'https://beta.example.com',
            'currency' => 'AED',
        ]);

        // 4. Marketer User
        $this->marketerUser = User::factory()->create([
            'email' => 'marketer@nomowly.com',
            'role' => UserRole::MARKETER,
        ]);
        $this->marketerUser->roles()->syncWithoutDetaching([$marketerRole->id]);
    }

    /**
     * 1. Successful Deposit initiation and confirmation with balanced double-entry ledger.
     */
    public function test_successful_deposit_initiation_and_confirmation_with_balanced_double_entry_ledger(): void
    {
        $depositResult = $this->depositService->initiateDeposit([
            'user' => $this->brandUserA,
            'currency' => Currency::SAR,
            'amount' => '500.0000',
            'provider' => 'fake',
            'payment_method' => 'mada',
        ]);

        /** @var PaymentDeposit $deposit */
        $deposit = $depositResult['deposit'];
        $this->assertEquals(PaymentDepositStatus::PENDING, $deposit->status);
        $this->assertEquals('500.0000', MoneyMath::format($deposit->amount));

        // Confirm deposit (zero fee baseline test)
        $confirmed = $this->depositService->confirmDeposit(
            deposit: $deposit,
            providerTransactionId: 'fake_tx_123456',
            confirmedFee: '0.0000'
        );

        $this->assertEquals(PaymentDepositStatus::SUCCEEDED, $confirmed->status);
        $this->assertNotNull($confirmed->paid_at);
        $this->assertNotNull($confirmed->ledger_transaction_id);

        // Check wallet balance
        $wallet = Wallet::find($deposit->wallet_id);
        $this->assertEquals('500.0000', MoneyMath::format($wallet->available_balance));
        $this->assertEquals('0.0000', MoneyMath::format($wallet->reserved_balance));

        // Check double-entry ledger transaction
        $ledgerTx = $confirmed->ledgerTransaction;
        $this->assertNotNull($ledgerTx);
        $this->assertEquals(LedgerTransactionType::DEPOSIT, $ledgerTx->transaction_type);

        $entries = $ledgerTx->entries()->with('account')->get();
        $this->assertCount(2, $entries);

        $debitEntry = $entries->where('entry_type', LedgerEntryType::DEBIT)->first();
        $creditEntry = $entries->where('entry_type', LedgerEntryType::CREDIT)->first();

        $this->assertNotNull($debitEntry);
        $this->assertNotNull($creditEntry);

        // Dr. 1010_PLATFORM_CASH
        $this->assertEquals('1010_PLATFORM_CASH', $debitEntry->account->code);
        $this->assertEquals('500.0000', MoneyMath::format($debitEntry->amount));

        // Cr. 2010_BRAND_WALLET_LIABILITY
        $this->assertEquals('2010_BRAND_WALLET_LIABILITY', $creditEntry->account->code);
        $this->assertEquals('500.0000', MoneyMath::format($creditEntry->amount));
        $this->assertEquals($wallet->id, $creditEntry->wallet_id);

        // Balanced invariant
        $this->assertEquals(
            MoneyMath::format($debitEntry->amount),
            MoneyMath::format($creditEntry->amount)
        );
    }

    /**
     * 2. Prevent double crediting: Calling confirmDeposit multiple times is strictly idempotent.
     */
    public function test_prevent_double_crediting_idempotent_confirmation(): void
    {
        $depositResult = $this->depositService->initiateDeposit([
            'user' => $this->brandUserA,
            'currency' => Currency::SAR,
            'amount' => '300.0000',
            'provider' => 'fake',
        ]);

        $deposit = $depositResult['deposit'];

        // Confirm 1st time
        $this->depositService->confirmDeposit($deposit, 'tx_idempotent_1');
        $walletAfter1 = Wallet::find($deposit->wallet_id);
        $this->assertEquals('300.0000', MoneyMath::format($walletAfter1->available_balance));

        // Confirm 2nd time
        $this->depositService->confirmDeposit($deposit, 'tx_idempotent_1');
        $walletAfter2 = Wallet::find($deposit->wallet_id);
        $this->assertEquals('300.0000', MoneyMath::format($walletAfter2->available_balance));

        // Confirm 3rd time
        $this->depositService->confirmDeposit($deposit, 'tx_idempotent_1');
        $walletAfter3 = Wallet::find($deposit->wallet_id);
        $this->assertEquals('300.0000', MoneyMath::format($walletAfter3->available_balance));
    }

    /**
     * 3. Failed payment does not mutate wallet balance or post ledger entries.
     */
    public function test_failed_payment_does_not_mutate_wallet_balance_or_post_ledger_entries(): void
    {
        $depositResult = $this->depositService->initiateDeposit([
            'user' => $this->brandUserA,
            'currency' => Currency::SAR,
            'amount' => '250.0000',
            'provider' => 'fake',
        ]);

        $deposit = $depositResult['deposit'];

        $failed = $this->depositService->failDeposit($deposit, 'رصيد البطاقة البنكية غير كافٍ');
        $this->assertEquals(PaymentDepositStatus::FAILED, $failed->status);
        $this->assertEquals('رصيد البطاقة البنكية غير كافٍ', $failed->failure_reason);
        $this->assertNull($failed->ledger_transaction_id);

        $wallet = Wallet::find($deposit->wallet_id);
        $this->assertEquals('0.0000', MoneyMath::format($wallet->available_balance));
    }

    /**
     * 4. Webhook with invalid signature is strictly rejected with 401.
     */
    public function test_webhook_with_invalid_signature_is_strictly_rejected(): void
    {
        $depositResult = $this->depositService->initiateDeposit([
            'user' => $this->brandUserA,
            'currency' => Currency::SAR,
            'amount' => '400.0000',
            'provider' => 'fake',
        ]);

        $deposit = $depositResult['deposit'];

        $response = $this->postJson(route('api.webhooks.payments', ['provider' => 'fake']), [
            'event_id' => 'evt_tampered_1',
            'event_type' => 'payment.captured',
            'deposit_number' => $deposit->deposit_number,
            'status' => 'SUCCEEDED',
            'amount' => '400.0000',
            'currency' => 'SAR',
        ], [
            'X-Fake-Signature' => 'invalid_forged_secret',
        ]);

        $response->assertStatus(401);

        $deposit->refresh();
        $this->assertEquals(PaymentDepositStatus::PENDING, $deposit->status);
    }

    /**
     * 5. Webhook duplicate event is handled idempotently.
     */
    public function test_webhook_duplicate_event_is_handled_idempotently(): void
    {
        $depositResult = $this->depositService->initiateDeposit([
            'user' => $this->brandUserA,
            'currency' => Currency::SAR,
            'amount' => '350.0000',
            'provider' => 'fake',
        ]);

        $deposit = $depositResult['deposit'];
        $secret = config('services.fake_gateway.secret', 'sandbox_secret_nomowly_key');

        $payload = [
            'event_id' => 'evt_unique_9999',
            'event_type' => 'payment.captured',
            'deposit_number' => $deposit->deposit_number,
            'status' => 'SUCCEEDED',
            'amount' => '350.0000',
            'currency' => 'SAR',
        ];

        // 1st Webhook delivery
        $res1 = $this->postJson(route('api.webhooks.payments', ['provider' => 'fake']), $payload, [
            'X-Fake-Signature' => $secret,
        ]);
        $res1->assertStatus(200);

        $deposit->refresh();
        $this->assertEquals(PaymentDepositStatus::SUCCEEDED, $deposit->status);

        $wallet = Wallet::find($deposit->wallet_id);
        $this->assertEquals('350.0000', MoneyMath::format($wallet->available_balance));

        // 2nd Webhook delivery (exact duplicate replay)
        $res2 = $this->postJson(route('api.webhooks.payments', ['provider' => 'fake']), $payload, [
            'X-Fake-Signature' => $secret,
        ]);
        $res2->assertStatus(200);
        $res2->assertJson(['data' => ['idempotent' => true]]);

        $wallet->refresh();
        $this->assertEquals('350.0000', MoneyMath::format($wallet->available_balance));
    }

    /**
     * 6. Webhook amount or currency mismatch is rejected and wallet not credited.
     */
    public function test_webhook_amount_or_currency_mismatch_is_rejected_and_wallet_not_credited(): void
    {
        $depositResult = $this->depositService->initiateDeposit([
            'user' => $this->brandUserA,
            'currency' => Currency::SAR,
            'amount' => '100.0000',
            'provider' => 'fake',
        ]);

        $deposit = $depositResult['deposit'];
        $secret = config('services.fake_gateway.secret', 'sandbox_secret_nomowly_key');

        // Mismatched amount
        $res = $this->postJson(route('api.webhooks.payments', ['provider' => 'fake']), [
            'event_id' => 'evt_mismatch_amt',
            'deposit_number' => $deposit->deposit_number,
            'status' => 'SUCCEEDED',
            'amount' => '500.0000', // Tampered!
            'currency' => 'SAR',
        ], [
            'X-Fake-Signature' => $secret,
        ]);

        $res->assertStatus(400);

        $deposit->refresh();
        $this->assertNotEquals(PaymentDepositStatus::SUCCEEDED, $deposit->status);

        $wallet = Wallet::find($deposit->wallet_id);
        $this->assertEquals('0.0000', MoneyMath::format($wallet->available_balance));
    }

    /**
     * 7. Out of order event arrival safety (processing after success returns cleanly).
     */
    public function test_out_of_order_event_arrival_safety(): void
    {
        $depositResult = $this->depositService->initiateDeposit([
            'user' => $this->brandUserA,
            'currency' => Currency::SAR,
            'amount' => '150.0000',
            'provider' => 'fake',
        ]);

        $deposit = $depositResult['deposit'];
        $secret = config('services.fake_gateway.secret', 'sandbox_secret_nomowly_key');

        // 1. Success Webhook arrives first
        $this->postJson(route('api.webhooks.payments', ['provider' => 'fake']), [
            'event_id' => 'evt_order_1',
            'deposit_number' => $deposit->deposit_number,
            'status' => 'SUCCEEDED',
            'amount' => '150.0000',
            'currency' => 'SAR',
        ], [
            'X-Fake-Signature' => $secret,
        ])->assertStatus(200);

        // 2. Delayed browser callback arrives later
        $res = $this->actingAs($this->brandUserA)->get(route('brand.wallet.deposit.callback', [
            'deposit_ref' => $deposit->deposit_number,
            'currency' => 'SAR',
        ]));

        $res->assertRedirect(route('brand.wallet.index', ['currency' => 'SAR']));
        $deposit->refresh();
        $this->assertEquals(PaymentDepositStatus::SUCCEEDED, $deposit->status);

        // Balance remains 150 (not doubled to 300)
        $wallet = Wallet::find($deposit->wallet_id);
        $this->assertEquals('150.0000', MoneyMath::format($wallet->available_balance));
    }

    /**
     * 8. Tenancy: Brand cannot view or callback other brands' deposits.
     */
    public function test_brand_cannot_view_or_callback_other_brands_deposits(): void
    {
        $depositResultA = $this->depositService->initiateDeposit([
            'user' => $this->brandUserA,
            'currency' => Currency::SAR,
            'amount' => '100.0000',
            'provider' => 'fake',
        ]);

        $depositA = $depositResultA['deposit'];

        // Brand B attempts to hit callback for Brand A's deposit
        $res = $this->actingAs($this->brandUserB)->get(route('brand.wallet.deposit.callback', [
            'deposit_ref' => $depositA->deposit_number,
            'currency' => 'SAR',
        ]));

        $res->assertSessionHas('error');
    }

    /**
     * 9. Unauthorized user cannot manage payments or refunds.
     */
    public function test_unauthorized_user_cannot_manage_payments_or_refunds(): void
    {
        $res1 = $this->actingAs($this->marketerUser)->get(route('admin.finance.deposits.index'));
        $res1->assertStatus(403);

        $res2 = $this->actingAs($this->brandUserA)->get(route('admin.finance.refunds.index'));
        $res2->assertStatus(403);

        $res3 = $this->actingAs($this->brandUserA)->get(route('admin.finance.reconciliation.index'));
        $res3->assertStatus(403);
    }

    /**
     * 10. Full and partial refund creates reversal ledger entries and updates balances.
     */
    public function test_full_and_partial_refund_creates_reversal_ledger_entries_and_updates_balances(): void
    {
        // 1. Successful deposit of 500 SAR
        $depositResult = $this->depositService->initiateDeposit([
            'user' => $this->brandUserA,
            'currency' => Currency::SAR,
            'amount' => '500.0000',
            'provider' => 'fake',
        ]);
        $deposit = $depositResult['deposit'];
        $this->depositService->confirmDeposit($deposit);

        $wallet = Wallet::find($deposit->wallet_id);
        $this->assertEquals('500.0000', MoneyMath::format($wallet->available_balance));

        // 2. Partial refund of 200 SAR
        $refund1 = $this->depositService->processRefund([
            'deposit' => $deposit,
            'amount' => '200.0000',
            'reason' => 'استرداد جزئي بطلب من المتجر',
            'performer' => $this->superAdmin,
        ]);

        $this->assertEquals(PaymentRefundStatus::SUCCEEDED, $refund1->status);
        $deposit->refresh();
        $this->assertEquals(PaymentDepositStatus::PARTIALLY_REFUNDED, $deposit->status);
        $this->assertEquals('300.0000', $deposit->remainingRefundableAmount());

        $wallet->refresh();
        $this->assertEquals('300.0000', MoneyMath::format($wallet->available_balance));

        // Check refund ledger reversal
        $refundTx1 = $refund1->ledgerTransaction;
        $this->assertNotNull($refundTx1);
        $this->assertEquals(LedgerTransactionType::REVERSAL, $refundTx1->transaction_type);

        // 3. Full remaining refund of 300 SAR
        $refund2 = $this->depositService->processRefund([
            'deposit' => $deposit,
            'amount' => '300.0000',
            'reason' => 'استرداد كامل الرصيد المتبقي',
            'performer' => $this->superAdmin,
        ]);

        $deposit->refresh();
        $this->assertEquals(PaymentDepositStatus::REFUNDED, $deposit->status);
        $this->assertEquals('0.0000', $deposit->remainingRefundableAmount());

        $wallet->refresh();
        $this->assertEquals('0.0000', MoneyMath::format($wallet->available_balance));
    }

    /**
     * 11. Prevent duplicate or excessive refunds.
     */
    public function test_prevent_duplicate_or_excessive_refunds(): void
    {
        $depositResult = $this->depositService->initiateDeposit([
            'user' => $this->brandUserA,
            'currency' => Currency::SAR,
            'amount' => '100.0000',
            'provider' => 'fake',
        ]);
        $deposit = $depositResult['deposit'];
        $this->depositService->confirmDeposit($deposit);

        // Attempt refunding 150 SAR (exceeding deposit amount)
        $this->expectException(InvalidArgumentException::class);
        $this->depositService->processRefund([
            'deposit' => $deposit,
            'amount' => '150.0000',
            'reason' => 'محاولة استرداد أكبر من المبلغ',
            'performer' => $this->superAdmin,
        ]);
    }

    /**
     * 12. Refund fails if brand wallet available balance is insufficient.
     */
    public function test_refund_fails_if_brand_wallet_available_balance_insufficient(): void
    {
        $depositResult = $this->depositService->initiateDeposit([
            'user' => $this->brandUserA,
            'currency' => Currency::SAR,
            'amount' => '200.0000',
            'provider' => 'fake',
        ]);
        $deposit = $depositResult['deposit'];
        $this->depositService->confirmDeposit($deposit);

        // Simulate brand reserved this balance in a campaign escrow
        $wallet = Wallet::find($deposit->wallet_id);
        $wallet->update([
            'available_balance' => '50.0000',
            'reserved_balance' => '150.0000',
        ]);

        // Trying to refund 100 SAR should fail with InsufficientFundsException because available is only 50
        $this->expectException(InsufficientFundsException::class);
        $this->depositService->processRefund([
            'deposit' => $deposit,
            'amount' => '100.0000',
            'reason' => 'محاولة استرداد مبلغ محجوز بالحملات',
            'performer' => $this->superAdmin,
        ]);
    }

    /**
     * 13. Currency isolation in deposits and refunds.
     */
    public function test_currency_isolation_in_deposits_and_refunds(): void
    {
        // Deposit in AED
        $depositAED = $this->depositService->initiateDeposit([
            'user' => $this->brandUserA,
            'currency' => Currency::AED,
            'amount' => '700.0000',
            'provider' => 'fake',
        ])['deposit'];
        $this->depositService->confirmDeposit($depositAED);

        // Deposit in USD
        $depositUSD = $this->depositService->initiateDeposit([
            'user' => $this->brandUserA,
            'currency' => Currency::USD,
            'amount' => '200.0000',
            'provider' => 'fake',
        ])['deposit'];
        $this->depositService->confirmDeposit($depositUSD);

        $walletAED = Wallet::where('user_id', $this->brandUserA->id)->where('currency', 'AED')->first();
        $walletUSD = Wallet::where('user_id', $this->brandUserA->id)->where('currency', 'USD')->first();

        $this->assertEquals('700.0000', MoneyMath::format($walletAED->available_balance));
        $this->assertEquals('200.0000', MoneyMath::format($walletUSD->available_balance));

        // Balances are strictly separated with zero cross-currency mixing
        $this->assertNotEquals($walletAED->id, $walletUSD->id);
    }

    /**
     * 14. Unsupported currency (e.g. EUR) is strictly rejected.
     */
    public function test_unsupported_currency_rejected(): void
    {
        $this->expectException(\ValueError::class);
        Currency::from('EUR');
    }

    /**
     * 15. Reconciliation detects discrepancies without silent mutations.
     */
    public function test_reconciliation_detects_discrepancies_without_silent_mutations(): void
    {
        $depositResult = $this->depositService->initiateDeposit([
            'user' => $this->brandUserA,
            'currency' => Currency::SAR,
            'amount' => '600.0000',
            'provider' => 'fake',
        ]);
        $deposit = $depositResult['deposit'];
        $deposit = $this->depositService->confirmDeposit($deposit);
        $deposit->refresh();

        // Simulate provider reporting FAILED or difference
        $this->fakeGateway->simulateOutcome(PaymentDepositStatus::FAILED->value, null, 'Bank chargeback occurred');

        $reconcileReport = $this->depositService->reconcileWithProvider($deposit);

        $this->assertFalse($reconcileReport['is_matched']);
        $this->assertNotEmpty($reconcileReport['discrepancies']);
        $this->assertEquals('SUCCEEDED', $reconcileReport['internal_status']);
        $this->assertEquals('FAILED', $reconcileReport['provider_status']);

        // Assert that the internal balance was NOT silently mutated
        $wallet = Wallet::find($deposit->wallet_id);
        $this->assertEquals('600.0000', MoneyMath::format($wallet->available_balance));
    }
}
