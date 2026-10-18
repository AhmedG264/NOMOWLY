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
use Tests\TestCase;

class Phase6_2PaymentSettlementAccountingTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $brandUser;
    protected BrandProfile $brandProfile;

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

        $superAdminRole = Role::firstOrCreate(['name' => UserRole::SUPER_ADMIN->value], ['label' => 'المدير العام', 'is_system' => true]);
        $brandRole = Role::firstOrCreate(['name' => UserRole::BRAND->value], ['label' => 'علامة تجارية', 'is_system' => true]);

        $superAdminRole->permissions()->sync(Permission::pluck('id'));

        $this->superAdmin = User::factory()->create([
            'email' => 'finance-auditor@nomowly.com',
            'role' => UserRole::SUPER_ADMIN,
        ]);
        $this->superAdmin->roles()->syncWithoutDetaching([$superAdminRole->id]);

        $this->brandUser = User::factory()->create([
            'email' => 'merchant-brand@nomowly.com',
            'role' => UserRole::BRAND,
        ]);
        $this->brandUser->roles()->syncWithoutDetaching([$brandRole->id]);
        $this->brandProfile = BrandProfile::create([
            'user_id' => $this->brandUser->id,
            'company_name' => 'متجر النخبة للتجارة',
            'website' => 'https://nokhba.example.com',
            'currency' => 'SAR',
        ]);
    }

    /**
     * Test 1: Hypothetical Example from Prompt:
     * - Paid amount: 1,000.00 SAR
     * - Gateway fee: 20.00 SAR
     * - Net bank settlement: 980.00 SAR
     *
     * Invariants verified:
     * 1. Brand wallet credited full 1,000.00 SAR (platform absorbs fee).
     * 2. Cash account 1010 receives 980.00 SAR (no fictitious cash: exactly net settlement).
     * 3. Fee expense account 5030 receives 20.00 SAR.
     * 4. Balanced double-entry: 980.00 + 20.00 = 1,000.00.
     */
    public function test_hypothetical_payment_settlement_exact_flow_and_ledger_breakdown(): void
    {
        $depositResult = $this->depositService->initiateDeposit([
            'user' => $this->brandUser,
            'currency' => Currency::SAR,
            'amount' => '1000.0000',
            'provider' => 'fake',
            'payment_method' => 'card',
        ]);

        /** @var PaymentDeposit $deposit */
        $deposit = $depositResult['deposit'];
        $this->assertEquals('1000.0000', MoneyMath::format($deposit->amount));

        // Confirm deposit with 20.00 SAR gateway fee
        $confirmed = $this->depositService->confirmDeposit(
            deposit: $deposit,
            providerTransactionId: 'tx_settle_hypothetical_1000',
            confirmedFee: '20.0000'
        );

        $this->assertEquals(PaymentDepositStatus::SUCCEEDED, $confirmed->status);
        $this->assertEquals('20.0000', MoneyMath::format($confirmed->fee_amount));

        // 1. Brand wallet check: full 1,000.00 SAR available
        $wallet = Wallet::find($deposit->wallet_id);
        $this->assertEquals('1000.0000', MoneyMath::format($wallet->available_balance));
        $this->assertEquals('0.0000', MoneyMath::format($wallet->reserved_balance));

        // 2. Ledger Transaction Entries check
        $ledgerTx = $confirmed->ledgerTransaction;
        $this->assertNotNull($ledgerTx);
        $this->assertTrue($ledgerTx->isBalanced());

        $entries = $ledgerTx->entries()->with('account')->get();
        $this->assertCount(3, $entries);

        // Account 1010: Net Cash Settled = 980.00 SAR
        $cashEntry = $entries->where('account.code', '1010_PLATFORM_CASH')->first();
        $this->assertNotNull($cashEntry);
        $this->assertEquals(LedgerEntryType::DEBIT, $cashEntry->entry_type);
        $this->assertEquals('980.0000', MoneyMath::format($cashEntry->amount));

        // Account 5030: Gateway Fee Expense = 20.00 SAR
        $feeEntry = $entries->where('account.code', '5030_PAYMENT_GATEWAY_FEE_EXPENSE')->first();
        $this->assertNotNull($feeEntry);
        $this->assertEquals(LedgerEntryType::DEBIT, $feeEntry->entry_type);
        $this->assertEquals('20.0000', MoneyMath::format($feeEntry->amount));

        // Account 2010: Brand Wallet Liability = 1,000.00 SAR
        $liabilityEntry = $entries->where('account.code', '2010_BRAND_WALLET_LIABILITY')->first();
        $this->assertNotNull($liabilityEntry);
        $this->assertEquals(LedgerEntryType::CREDIT, $liabilityEntry->entry_type);
        $this->assertEquals('1000.0000', MoneyMath::format($liabilityEntry->amount));
        $this->assertEquals($wallet->id, $liabilityEntry->wallet_id);

        // Mathematical equality: Debits == Credits
        $totalDebits = MoneyMath::add($cashEntry->amount, $feeEntry->amount);
        $this->assertEquals('1000.0000', $totalDebits);
        $this->assertEquals($totalDebits, MoneyMath::format($liabilityEntry->amount));
    }

    /**
     * Test 2: Fees are not recorded twice on replay or duplicate webhook events.
     */
    public function test_fees_not_recorded_twice_on_duplicate_webhook_or_reconciliation(): void
    {
        $deposit = $this->depositService->initiateDeposit([
            'user' => $this->brandUser,
            'currency' => Currency::SAR,
            'amount' => '1000.0000',
            'provider' => 'fake',
        ])['deposit'];

        $secret = config('services.fake_gateway.secret', 'sandbox_secret_nomowly_key');
        $payload = [
            'event_id' => 'evt_settle_replay_check',
            'event_type' => 'payment.captured',
            'deposit_number' => $deposit->deposit_number,
            'status' => 'SUCCEEDED',
            'amount' => '1000.0000',
            'currency' => 'SAR',
            'fee' => '20.0000',
        ];

        // 1st delivery
        $this->postJson(route('api.webhooks.payments', ['provider' => 'fake']), $payload, [
            'X-Fake-Signature' => $secret,
        ])->assertStatus(200);

        // 2nd delivery (replayed)
        $this->postJson(route('api.webhooks.payments', ['provider' => 'fake']), $payload, [
            'X-Fake-Signature' => $secret,
        ])->assertStatus(200);

        // Ensure exactly ONE fee entry exists in the system for this deposit
        $feeEntriesCount = \App\Models\LedgerEntry::whereHas('account', function ($q) {
            $q->where('code', '5030_PAYMENT_GATEWAY_FEE_EXPENSE');
        })->whereHas('transaction', function ($q) use ($deposit) {
            $q->where('source_type', PaymentDeposit::class)->where('source_id', $deposit->id);
        })->count();

        $this->assertEquals(1, $feeEntriesCount);

        // Wallet balance remains 1,000.00 SAR
        $wallet = Wallet::find($deposit->wallet_id);
        $this->assertEquals('1000.0000', MoneyMath::format($wallet->available_balance));
    }

    /**
     * Test 3: No fictitious cash in clearing account: 1010 reflects exact net cash arriving.
     */
    public function test_no_fictitious_cash_in_bank_clearing_account(): void
    {
        // Deposit 1: 1,000 SAR with 20 SAR fee -> Net 980 SAR
        $d1 = $this->depositService->initiateDeposit([
            'user' => $this->brandUser,
            'currency' => Currency::SAR,
            'amount' => '1000.0000',
            'provider' => 'fake',
        ])['deposit'];
        $this->depositService->confirmDeposit($d1, 'tx_d1', '20.0000');

        // Deposit 2: 2,000 SAR with 50 SAR fee -> Net 1,950 SAR
        $d2 = $this->depositService->initiateDeposit([
            'user' => $this->brandUser,
            'currency' => Currency::SAR,
            'amount' => '2000.0000',
            'provider' => 'fake',
        ])['deposit'];
        $this->depositService->confirmDeposit($d2, 'tx_d2', '50.0000');

        // Total Net Cash deposited in 1010: 980 + 1950 = 2,930.0000
        $totalCashIn1010 = \App\Models\LedgerEntry::whereHas('account', function ($q) {
            $q->where('code', '1010_PLATFORM_CASH');
        })->where('entry_type', LedgerEntryType::DEBIT)->sum('amount');

        $this->assertEquals('2930.0000', MoneyMath::format($totalCashIn1010));

        // Total Fee Expense in 5030: 20 + 50 = 70.0000
        $totalFeeExpense = \App\Models\LedgerEntry::whereHas('account', function ($q) {
            $q->where('code', '5030_PAYMENT_GATEWAY_FEE_EXPENSE');
        })->sum('amount');

        $this->assertEquals('70.0000', MoneyMath::format($totalFeeExpense));

        // Total Brand Wallet liability in 2010: 1,000 + 2,000 = 3,000.0000
        $totalLiability = \App\Models\LedgerEntry::whereHas('account', function ($q) {
            $q->where('code', '2010_BRAND_WALLET_LIABILITY');
        })->where('entry_type', LedgerEntryType::CREDIT)->sum('amount');

        $this->assertEquals('3000.0000', MoneyMath::format($totalLiability));

        // Reconciliation Invariant: Cash (2,930) + Fees (70) == Liabilities (3,000)
        $this->assertEquals(
            MoneyMath::format($totalLiability),
            MoneyMath::add($totalCashIn1010, $totalFeeExpense)
        );
    }

    /**
     * Test 4: Zero-fee transactions produce exactly 2 entries (Dr 1010, Cr 2010).
     */
    public function test_zero_fee_deposit_produces_two_balanced_entries(): void
    {
        $deposit = $this->depositService->initiateDeposit([
            'user' => $this->brandUser,
            'currency' => Currency::SAR,
            'amount' => '500.0000',
            'provider' => 'fake',
        ])['deposit'];

        $confirmed = $this->depositService->confirmDeposit($deposit, 'tx_zero_fee', '0.0000');
        $entries = $confirmed->ledgerTransaction->entries;

        $this->assertCount(2, $entries);
        $this->assertEquals('500.0000', MoneyMath::format($entries->first()->amount));
        $this->assertEquals('500.0000', MoneyMath::format($entries->last()->amount));
    }

    /**
     * Test 5: Refund preserves the gateway fee expense; gateway does not return processing fees.
     */
    public function test_refund_preserves_gateway_fee_expense_and_does_not_mutate_expense_account(): void
    {
        // 1. Initial Deposit: 1,000 SAR, Fee: 20 SAR, Net Cash: 980 SAR
        $deposit = $this->depositService->initiateDeposit([
            'user' => $this->brandUser,
            'currency' => Currency::SAR,
            'amount' => '1000.0000',
            'provider' => 'fake',
        ])['deposit'];
        $this->depositService->confirmDeposit($deposit, 'tx_refund_basis', '20.0000');

        // 2. Process partial refund of 300.00 SAR
        $refund = $this->depositService->processRefund([
            'deposit' => $deposit,
            'amount' => '300.0000',
            'reason' => 'استرداد جزئي تجريبي للتحقق من الرسوم',
            'performer' => $this->superAdmin,
        ]);

        $this->assertEquals(PaymentRefundStatus::SUCCEEDED, $refund->status);

        // Wallet available balance is reduced to 700.00 SAR
        $wallet = Wallet::find($deposit->wallet_id);
        $this->assertEquals('700.0000', MoneyMath::format($wallet->available_balance));

        // Expense account 5030 remains exactly 20.0000 SAR (gateway keeps transaction fee)
        $feeExpense = \App\Models\LedgerEntry::whereHas('account', function ($q) {
            $q->where('code', '5030_PAYMENT_GATEWAY_FEE_EXPENSE');
        })->sum('amount');
        $this->assertEquals('20.0000', MoneyMath::format($feeExpense));

        // Refund transaction reverses liability (Dr 2010 300) and reduces cash (Cr 1010 300)
        $refundTx = $refund->ledgerTransaction;
        $this->assertEquals(LedgerTransactionType::REVERSAL, $refundTx->transaction_type);
        $this->assertTrue($refundTx->isBalanced());
    }

    /**
     * Test 6: Multi-currency isolation: SAR and USD transactions and fees never mix.
     */
    public function test_no_cross_currency_mixing_in_settlement_or_fees(): void
    {
        // 1. SAR Deposit: 1,000 SAR, 20 SAR fee
        $dSAR = $this->depositService->initiateDeposit([
            'user' => $this->brandUser,
            'currency' => Currency::SAR,
            'amount' => '1000.0000',
            'provider' => 'fake',
        ])['deposit'];
        $this->depositService->confirmDeposit($dSAR, 'tx_sar_mix_1', '20.0000');

        // 2. USD Deposit: 500 USD, 15 USD fee
        $dUSD = $this->depositService->initiateDeposit([
            'user' => $this->brandUser,
            'currency' => Currency::USD,
            'amount' => '500.0000',
            'provider' => 'fake',
        ])['deposit'];
        $this->depositService->confirmDeposit($dUSD, 'tx_usd_mix_1', '15.0000');

        $walletSAR = Wallet::where('user_id', $this->brandUser->id)->where('currency', 'SAR')->first();
        $walletUSD = Wallet::where('user_id', $this->brandUser->id)->where('currency', 'USD')->first();

        $this->assertEquals('1000.0000', MoneyMath::format($walletSAR->available_balance));
        $this->assertEquals('500.0000', MoneyMath::format($walletUSD->available_balance));

        // Ledger entries for SAR vs USD remain strictly segregated
        $dSAR->refresh();
        $dUSD->refresh();

        $sarTx = $dSAR->ledgerTransaction;
        $usdTx = $dUSD->ledgerTransaction;

        $this->assertEquals(Currency::SAR, $sarTx->currency);
        $this->assertEquals(Currency::USD, $usdTx->currency);
        $this->assertNotEquals($sarTx->currency, $usdTx->currency);
    }
}
