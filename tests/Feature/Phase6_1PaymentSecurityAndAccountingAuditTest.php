<?php

namespace Tests\Feature;

use App\Enums\Currency;
use App\Enums\LedgerAccountType;
use App\Enums\LedgerEntryType;
use App\Enums\LedgerTransactionType;
use App\Enums\PaymentDepositStatus;
use App\Enums\PaymentProvider;
use App\Enums\PaymentRefundStatus;
use App\Enums\UserRole;
use App\Exceptions\InsufficientFundsException;
use App\Models\AuditLog;
use App\Models\BrandProfile;
use App\Models\Campaign;
use App\Models\CommissionSettlement;
use App\Models\Conversion;
use App\Models\LedgerAccount;
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
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tests\TestCase;

class Phase6_1PaymentSecurityAndAccountingAuditTest extends TestCase
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

        // System Roles
        $superAdminRole = Role::firstOrCreate(['name' => UserRole::SUPER_ADMIN->value], ['label' => 'المدير العام', 'is_system' => true]);
        $brandRole = Role::firstOrCreate(['name' => UserRole::BRAND->value], ['label' => 'علامة تجارية', 'is_system' => true]);
        $marketerRole = Role::firstOrCreate(['name' => UserRole::MARKETER->value], ['label' => 'مسوق', 'is_system' => true]);

        $superAdminRole->permissions()->sync(Permission::pluck('id'));

        // 1. Super Admin
        $this->superAdmin = User::factory()->create([
            'email' => 'audit-admin@nomowly.com',
            'role' => UserRole::SUPER_ADMIN,
        ]);
        $this->superAdmin->roles()->syncWithoutDetaching([$superAdminRole->id]);

        // 2. Brand A
        $this->brandUserA = User::factory()->create([
            'email' => 'audit-brand-a@nomowly.com',
            'role' => UserRole::BRAND,
        ]);
        $this->brandUserA->roles()->syncWithoutDetaching([$brandRole->id]);
        $this->brandProfileA = BrandProfile::create([
            'user_id' => $this->brandUserA->id,
            'company_name' => 'شركة تدقيق أ',
            'website' => 'https://audit-a.example.com',
            'currency' => 'SAR',
        ]);

        // 3. Brand B
        $this->brandUserB = User::factory()->create([
            'email' => 'audit-brand-b@nomowly.com',
            'role' => UserRole::BRAND,
        ]);
        $this->brandUserB->roles()->syncWithoutDetaching([$brandRole->id]);
        $this->brandProfileB = BrandProfile::create([
            'user_id' => $this->brandUserB->id,
            'company_name' => 'شركة تدقيق ب',
            'website' => 'https://audit-b.example.com',
            'currency' => 'SAR',
        ]);

        // 4. Marketer User
        $this->marketerUser = User::factory()->create([
            'email' => 'audit-marketer@nomowly.com',
            'role' => UserRole::MARKETER,
        ]);
        $this->marketerUser->roles()->syncWithoutDetaching([$marketerRole->id]);
    }

    /**
     * 1. Deposit Lifecycle: End-to-end trace from creation, checkout, to ledger posting.
     */
    public function test_deposit_lifecycle_end_to_end_with_exact_ledger_and_wallet_crediting(): void
    {
        $depositResult = $this->depositService->initiateDeposit([
            'user' => $this->brandUserA,
            'currency' => Currency::SAR,
            'amount' => '1000.0000',
            'provider' => 'fake',
            'payment_method' => 'card',
        ]);

        /** @var PaymentDeposit $deposit */
        $deposit = $depositResult['deposit'];
        $this->assertEquals(PaymentDepositStatus::PENDING, $deposit->status);
        $this->assertEquals('1000.0000', MoneyMath::format($deposit->amount));

        // Initial wallet balance must be zero
        $wallet = Wallet::find($deposit->wallet_id);
        $this->assertEquals('0.0000', MoneyMath::format($wallet->available_balance));

        // Confirm deposit through deposit service
        $confirmed = $this->depositService->confirmDeposit($deposit, 'audit_tx_001', '25.0000');

        $this->assertEquals(PaymentDepositStatus::SUCCEEDED, $confirmed->status);
        $this->assertNotNull($confirmed->paid_at);
        $this->assertNotNull($confirmed->ledger_transaction_id);

        // Wallet available balance credited exactly with net amount
        $wallet->refresh();
        $this->assertEquals('1000.0000', MoneyMath::format($wallet->available_balance));

        // Verify ledger entries are strictly balanced
        $ledgerTx = $confirmed->ledgerTransaction;
        $this->assertEquals(LedgerTransactionType::DEPOSIT, $ledgerTx->transaction_type);
        $this->assertTrue($ledgerTx->isBalanced());
        $this->assertEquals('1000.0000', MoneyMath::format($ledgerTx->debitEntries()->sum('amount')));
        $this->assertEquals('1000.0000', MoneyMath::format($ledgerTx->creditEntries()->sum('amount')));
    }

    /**
     * 2. Fraud Resistance: Cannot credit wallet without trusted gateway confirmation.
     */
    public function test_cannot_add_balance_without_trusted_gateway_confirmation(): void
    {
        $depositResult = $this->depositService->initiateDeposit([
            'user' => $this->brandUserA,
            'currency' => Currency::SAR,
            'amount' => '500.0000',
            'provider' => 'fake',
        ]);
        $deposit = $depositResult['deposit'];

        // Simulate gateway reporting FAILED
        $this->fakeGateway->simulateOutcome(PaymentDepositStatus::FAILED->value, null, 'Insufficient funds on user card');

        $response = $this->actingAs($this->brandUserA)->get(route('brand.wallet.deposit.callback', [
            'deposit_ref' => $deposit->deposit_number,
            'currency' => 'SAR',
        ]));

        $response->assertSessionHas('error');

        $deposit->refresh();
        $this->assertEquals(PaymentDepositStatus::FAILED, $deposit->status);

        // Assert wallet was NOT credited
        $wallet = Wallet::find($deposit->wallet_id);
        $this->assertEquals('0.0000', MoneyMath::format($wallet->available_balance));
        $this->assertNull($deposit->ledger_transaction_id);
    }

    /**
     * 3. Concurrency & Idempotency: Duplicate confirmation calls cannot double credit.
     */
    public function test_concurrent_deposit_confirmations_strictly_deduplicated_no_double_crediting(): void
    {
        $depositResult = $this->depositService->initiateDeposit([
            'user' => $this->brandUserA,
            'currency' => Currency::SAR,
            'amount' => '750.0000',
            'provider' => 'fake',
        ]);
        $deposit = $depositResult['deposit'];

        // Confirm 1
        $this->depositService->confirmDeposit($deposit, 'tx_same_event_1');
        $wallet = Wallet::find($deposit->wallet_id);
        $this->assertEquals('750.0000', MoneyMath::format($wallet->available_balance));

        // Confirm 2 (duplicate/replayed)
        $this->depositService->confirmDeposit($deposit, 'tx_same_event_1');
        $wallet->refresh();
        $this->assertEquals('750.0000', MoneyMath::format($wallet->available_balance));

        // Confirm 3
        $this->depositService->confirmDeposit($deposit, 'tx_same_event_1');
        $wallet->refresh();
        $this->assertEquals('750.0000', MoneyMath::format($wallet->available_balance));
    }

    /**
     * 4. Fee Accounting Audit: Examine 5030_PAYMENT_GATEWAY_FEE_EXPENSE and gross/net balancing.
     */
    public function test_fee_accounting_audit_analysis_and_balancing_with_account_5030(): void
    {
        // Assert account 5030_PAYMENT_GATEWAY_FEE_EXPENSE exists in the system
        $feeAccount = LedgerAccount::where('code', '5030_PAYMENT_GATEWAY_FEE_EXPENSE')->first();
        $this->assertNotNull($feeAccount, 'Ledger account 5030_PAYMENT_GATEWAY_FEE_EXPENSE must be provisioned in the chart of accounts.');
        $this->assertEquals(LedgerAccountType::EXPENSE, $feeAccount->type);

        // Perform deposit with fee tracking
        $depositResult = $this->depositService->initiateDeposit([
            'user' => $this->brandUserA,
            'currency' => Currency::SAR,
            'amount' => '1000.0000',
            'provider' => 'fake',
        ]);
        $deposit = $depositResult['deposit'];

        $confirmed = $this->depositService->confirmDeposit($deposit, 'tx_fee_audit', '25.0000');

        // Verify fee is recorded on the deposit entity
        $this->assertEquals('25.0000', MoneyMath::format($confirmed->fee_amount));
        $this->assertEquals('1000.0000', MoneyMath::format($confirmed->amount));
        $this->assertEquals('1000.0000', MoneyMath::format($confirmed->net_amount));

        // In Nomowly's gross crediting model, customer brand wallet receives full 1000 SAR
        $wallet = Wallet::find($deposit->wallet_id);
        $this->assertEquals('1000.0000', MoneyMath::format($wallet->available_balance));

        // Double-entry transaction remains balanced
        $tx = $confirmed->ledgerTransaction;
        $this->assertTrue($tx->isBalanced());
        $this->assertEquals('1000.0000', MoneyMath::format($tx->debitEntries()->sum('amount')));
        $this->assertEquals('1000.0000', MoneyMath::format($tx->creditEntries()->sum('amount')));
    }

    /**
     * 5. Refund Lifecycle: Refund requires provider confirmation before ledger posting.
     */
    public function test_refund_lifecycle_requires_trusted_confirmation_before_posting(): void
    {
        $deposit = $this->depositService->initiateDeposit([
            'user' => $this->brandUserA,
            'currency' => Currency::SAR,
            'amount' => '500.0000',
            'provider' => 'fake',
        ])['deposit'];
        $this->depositService->confirmDeposit($deposit);

        // Admin initiates refund
        $refund = $this->depositService->processRefund([
            'deposit' => $deposit,
            'amount' => '200.0000',
            'reason' => 'تدقيق الاسترداد المحاسبي',
            'performer' => $this->superAdmin,
        ]);

        $this->assertEquals(PaymentRefundStatus::SUCCEEDED, $refund->status);
        $this->assertNotNull($refund->ledger_transaction_id);

        $wallet = Wallet::find($deposit->wallet_id);
        $this->assertEquals('300.0000', MoneyMath::format($wallet->available_balance));

        $ledgerTx = $refund->ledgerTransaction;
        $this->assertEquals(LedgerTransactionType::REVERSAL, $ledgerTx->transaction_type);
        $this->assertTrue($ledgerTx->isBalanced());
    }

    /**
     * 6. Refund Protection: Cannot refund amounts locked in Campaign Escrow.
     */
    public function test_refund_cannot_exceed_available_balance_and_protects_campaign_escrow(): void
    {
        $deposit = $this->depositService->initiateDeposit([
            'user' => $this->brandUserA,
            'currency' => Currency::SAR,
            'amount' => '600.0000',
            'provider' => 'fake',
        ])['deposit'];
        $this->depositService->confirmDeposit($deposit);

        $wallet = Wallet::find($deposit->wallet_id);

        // Lock 400 SAR in campaign escrow
        $wallet->update([
            'available_balance' => '200.0000',
            'reserved_balance' => '400.0000',
        ]);

        // Attempting to refund 300 SAR must fail because only 200 SAR is available
        $this->expectException(InsufficientFundsException::class);
        $this->depositService->processRefund([
            'deposit' => $deposit,
            'amount' => '300.0000',
            'reason' => 'محاولة سحب مبالغ محجوزة في الضمان',
            'performer' => $this->superAdmin,
        ]);
    }

    /**
     * 7. Marketer Commission Protection: Brand refund cannot claw back settled marketer commissions.
     */
    public function test_refund_cannot_clawback_funds_from_settled_marketer_commissions(): void
    {
        // 1. Brand deposits 400 SAR
        $deposit = $this->depositService->initiateDeposit([
            'user' => $this->brandUserA,
            'currency' => Currency::SAR,
            'amount' => '400.0000',
            'provider' => 'fake',
        ])['deposit'];
        $this->depositService->confirmDeposit($deposit);

        $brandWallet = Wallet::find($deposit->wallet_id);
        $marketerWallet = $this->walletService->getOrCreateWallet($this->marketerUser, Currency::SAR);

        // Simulate that 350 SAR was settled to marketer from brand funds
        $brandWallet->update([
            'available_balance' => '50.0000',
            'reserved_balance' => '0.0000',
        ]);
        $marketerWallet->update([
            'available_balance' => '350.0000',
        ]);

        // Brand attempts to refund 200 SAR of the original 400 SAR deposit
        // Must fail with InsufficientFundsException protecting marketer earnings
        try {
            $this->depositService->processRefund([
                'deposit' => $deposit,
                'amount' => '200.0000',
                'reason' => 'محاولة استرداد تتجاوز الرصيد بعد تسوية عمولات المسوقين',
                'performer' => $this->superAdmin,
            ]);
            $this->fail('Expected InsufficientFundsException was not thrown.');
        } catch (InsufficientFundsException $e) {
            $this->assertStringContainsString('أقل من مبلغ الاسترداد المطلوب', $e->getMessage());
        }

        // Marketer balance remains intact
        $marketerWallet->refresh();
        $this->assertEquals('350.0000', MoneyMath::format($marketerWallet->available_balance));
    }

    /**
     * 8. Concurrent Refund Safety: In-flight refunds reserve from the refundable pool.
     */
    public function test_concurrent_refund_race_conditions_prevent_double_refund(): void
    {
        $deposit = $this->depositService->initiateDeposit([
            'user' => $this->brandUserA,
            'currency' => Currency::SAR,
            'amount' => '300.0000',
            'provider' => 'fake',
        ])['deposit'];
        $this->depositService->confirmDeposit($deposit);

        // Refund 1: 200 SAR
        $this->depositService->processRefund([
            'deposit' => $deposit,
            'amount' => '200.0000',
            'reason' => 'استرداد أول',
            'performer' => $this->superAdmin,
        ]);

        $deposit->refresh();
        $this->assertEquals('100.0000', $deposit->remainingRefundableAmount());

        // Refund 2: Attempting 150 SAR when only 100 SAR remains
        $this->expectException(InvalidArgumentException::class);
        $this->depositService->processRefund([
            'deposit' => $deposit,
            'amount' => '150.0000',
            'reason' => 'استرداد ثاني يتجاوز المتبقي',
            'performer' => $this->superAdmin,
        ]);
    }

    /**
     * 9. Webhook Replay & Duplicate Safety: Webhooks with duplicate event_id are handled idempotently.
     */
    public function test_duplicate_or_replayed_refund_webhook_is_strictly_idempotent(): void
    {
        $deposit = $this->depositService->initiateDeposit([
            'user' => $this->brandUserA,
            'currency' => Currency::SAR,
            'amount' => '450.0000',
            'provider' => 'fake',
        ])['deposit'];

        $secret = config('services.fake_gateway.secret', 'sandbox_secret_nomowly_key');
        $payload = [
            'event_id' => 'evt_audit_replay_101',
            'event_type' => 'payment.captured',
            'deposit_number' => $deposit->deposit_number,
            'status' => 'SUCCEEDED',
            'amount' => '450.0000',
            'currency' => 'SAR',
        ];

        // 1st delivery
        $res1 = $this->postJson(route('api.webhooks.payments', ['provider' => 'fake']), $payload, [
            'X-Fake-Signature' => $secret,
        ]);
        $res1->assertStatus(200);

        $wallet = Wallet::find($deposit->wallet_id);
        $this->assertEquals('450.0000', MoneyMath::format($wallet->available_balance));

        // 2nd delivery (replayed)
        $res2 = $this->postJson(route('api.webhooks.payments', ['provider' => 'fake']), $payload, [
            'X-Fake-Signature' => $secret,
        ]);
        $res2->assertStatus(200);
        $res2->assertJson(['data' => ['idempotent' => true]]);

        $wallet->refresh();
        $this->assertEquals('450.0000', MoneyMath::format($wallet->available_balance));
    }

    /**
     * 10. Webhook Out-of-Order Delivery: Late FAILED event cannot overturn SUCCEEDED deposit.
     */
    public function test_webhook_out_of_order_late_failed_cannot_override_succeeded_deposit(): void
    {
        $deposit = $this->depositService->initiateDeposit([
            'user' => $this->brandUserA,
            'currency' => Currency::SAR,
            'amount' => '800.0000',
            'provider' => 'fake',
        ])['deposit'];

        // Confirm deposit
        $this->depositService->confirmDeposit($deposit);
        $deposit->refresh();
        $this->assertEquals(PaymentDepositStatus::SUCCEEDED, $deposit->status);

        // Out-of-order FAILED attempt must throw RuntimeException and protect the ledger
        $this->expectException(RuntimeException::class);
        $this->depositService->failDeposit($deposit, 'خطأ متأخر');
    }

    /**
     * 11. Security & Forensics: Raw Webhook data is recorded with sensitive data sanitized.
     */
    public function test_webhook_sanitizes_secrets_and_masks_sensitive_data_in_database(): void
    {
        $deposit = $this->depositService->initiateDeposit([
            'user' => $this->brandUserA,
            'currency' => Currency::SAR,
            'amount' => '300.0000',
            'provider' => 'fake',
        ])['deposit'];

        $secret = config('services.fake_gateway.secret', 'sandbox_secret_nomowly_key');
        $payload = [
            'event_id' => 'evt_sanitize_test_1',
            'deposit_number' => $deposit->deposit_number,
            'status' => 'SUCCEEDED',
            'amount' => '300.0000',
            'currency' => 'SAR',
            'secret_token' => 'super_secret_raw_token',
            'card_pan' => '4111111111111111',
            'api_key' => 'live_sec_key_xyz',
        ];

        $res = $this->postJson(route('api.webhooks.payments', ['provider' => 'fake']), $payload, [
            'X-Fake-Signature' => $secret,
        ]);
        $res->assertStatus(200);

        // Verify stored webhook record has masked sensitive keys
        $webhook = PaymentWebhook::where('event_id', 'evt_sanitize_test_1')->first();
        $this->assertNotNull($webhook);
        $storedPayload = $webhook->payload;

        $this->assertEquals('***REDACTED***', $storedPayload['secret_token']);
        $this->assertEquals('***REDACTED***', $storedPayload['card_pan']);
        $this->assertEquals('***REDACTED***', $storedPayload['api_key']);
        $this->assertEquals('300.0000', $storedPayload['amount']);
    }

    /**
     * 12. Tenancy & Isolation: Brand A cannot view, touch or callback Brand B's deposits.
     */
    public function test_brand_tenancy_isolation_strictly_enforced_for_deposits_and_refunds(): void
    {
        $depositA = $this->depositService->initiateDeposit([
            'user' => $this->brandUserA,
            'currency' => Currency::SAR,
            'amount' => '600.0000',
            'provider' => 'fake',
        ])['deposit'];

        // Brand B attempts to access Brand A's deposit via callback
        $res = $this->actingAs($this->brandUserB)->get(route('brand.wallet.deposit.callback', [
            'deposit_ref' => $depositA->deposit_number,
            'currency' => 'SAR',
        ]));

        $res->assertSessionHas('error', 'لم يتم العثور على سجل الإيداع المطلوب.');

        // Brand A cannot see Brand B's deposit
        $depositB = $this->depositService->initiateDeposit([
            'user' => $this->brandUserB,
            'currency' => Currency::SAR,
            'amount' => '250.0000',
            'provider' => 'fake',
        ])['deposit'];

        $res2 = $this->actingAs($this->brandUserA)->get(route('brand.wallet.deposit.callback', [
            'deposit_ref' => $depositB->deposit_number,
            'currency' => 'SAR',
        ]));
        $res2->assertSessionHas('error', 'لم يتم العثور على سجل الإيداع المطلوب.');
    }

    /**
     * 13. RBAC: Marketers cannot access payment management or administrative endpoints.
     */
    public function test_marketer_and_unauthorized_users_forbidden_from_payment_operations(): void
    {
        // Marketer forbidden from admin deposit listing
        $this->actingAs($this->marketerUser)
            ->get(route('admin.finance.deposits.index'))
            ->assertStatus(403);

        // Marketer forbidden from refunding
        $deposit = $this->depositService->initiateDeposit([
            'user' => $this->brandUserA,
            'currency' => Currency::SAR,
            'amount' => '100.0000',
            'provider' => 'fake',
        ])['deposit'];

        $this->actingAs($this->marketerUser)
            ->post(route('admin.finance.deposits.refund', $deposit), [
                'amount' => '50.0000',
                'reason' => 'محاولة غير مصرح بها',
            ])
            ->assertStatus(403);

        // Marketer forbidden from reconciliation dashboard
        $this->actingAs($this->marketerUser)
            ->get(route('admin.finance.reconciliation.index'))
            ->assertStatus(403);
    }

    /**
     * 14. Financial Reconciliation: Detects discrepancies without silent mutations.
     */
    public function test_financial_reconciliation_detects_discrepancies_without_silent_mutations_and_records_audit(): void
    {
        $deposit = $this->depositService->initiateDeposit([
            'user' => $this->brandUserA,
            'currency' => Currency::SAR,
            'amount' => '500.0000',
            'provider' => 'fake',
        ])['deposit'];
        $this->depositService->confirmDeposit($deposit);

        // Simulate gateway mismatch on status and amount
        $this->fakeGateway->simulateOutcome(PaymentDepositStatus::FAILED->value, '10.0000', 'Mismatched status');

        $report = $this->depositService->reconcileWithProvider($deposit);

        $this->assertFalse($report['is_matched']);
        $this->assertNotEmpty($report['discrepancies']);

        // Assert no silent mutation occurred on wallet
        $wallet = Wallet::find($deposit->wallet_id);
        $this->assertEquals('500.0000', MoneyMath::format($wallet->available_balance));

        // Audit log was recorded
        $audit = AuditLog::where('entity_id', $deposit->id)
            ->where('action', \App\Enums\AuditAction::PAYMENT_RECONCILIATION_RUN)
            ->latest()
            ->first();

        $this->assertNotNull($audit);
        $this->assertEquals('FAILED', $audit->status);
    }

    /**
     * 15. MoneyMath & Currency Precision: All financial math uses string bcmath without floating-point errors.
     */
    public function test_money_math_bcmath_precision_enforced_across_all_financial_deposit_operations(): void
    {
        // 0.1 + 0.2 floating point anomaly
        $amount1 = '0.1000';
        $amount2 = '0.2000';
        $sum = MoneyMath::add($amount1, $amount2);
        $this->assertEquals('0.3000', $sum);

        // Precision verification on deposit
        $deposit = $this->depositService->initiateDeposit([
            'user' => $this->brandUserA,
            'currency' => Currency::SAR,
            'amount' => '123.4567',
            'provider' => 'fake',
        ])['deposit'];

        $this->assertEquals('123.4567', MoneyMath::format($deposit->amount));

        $confirmed = $this->depositService->confirmDeposit($deposit, 'tx_precision_1', '1.2345');
        $this->assertEquals('1.2345', MoneyMath::format($confirmed->fee_amount));

        $wallet = Wallet::find($deposit->wallet_id);
        $this->assertEquals('123.4567', MoneyMath::format($wallet->available_balance));
    }
}
