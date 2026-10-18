<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Commission Settlements Table (Audit link between Conversion and Double-Entry Ledger)
        Schema::create('commission_settlements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('conversion_id')->unique()->constrained('conversions')->cascadeOnDelete();
            $table->foreignUuid('ledger_transaction_id')->constrained('ledger_transactions')->cascadeOnDelete();
            $table->foreignUuid('brand_wallet_id')->constrained('wallets');
            $table->foreignUuid('marketer_wallet_id')->constrained('wallets');
            $table->string('currency', 3);
            $table->decimal('commission_amount', 18, 4);
            $table->decimal('platform_fee_amount', 18, 4)->default(0);
            $table->foreignUuid('settled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('settled_at')->useCurrent();
            $table->foreignUuid('reversal_ledger_transaction_id')->nullable()->constrained('ledger_transactions')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->timestamps();

            $table->index(['currency', 'settled_at']);
        });

        // 2. Payout Requests Table (Marketer Payout Workflow)
        Schema::create('payout_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('request_number', 64)->unique();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('wallet_id')->constrained('wallets')->cascadeOnDelete();
            $table->string('currency', 3);
            $table->decimal('amount', 18, 4);
            $table->string('status', 32)->default('REQUESTED'); // REQUESTED, APPROVED, PROCESSING, PAID, REJECTED, CANCELLED
            $table->string('bank_name')->nullable();
            $table->string('account_holder_name')->nullable();
            $table->string('account_reference')->nullable(); // Masked bank/IBAN reference (e.g. SA**...1234)
            $table->text('admin_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->string('payment_reference')->nullable(); // External bank transfer reference
            $table->string('idempotency_key', 128)->unique();
            $table->foreignUuid('hold_ledger_transaction_id')->nullable()->constrained('ledger_transactions')->nullOnDelete();
            $table->foreignUuid('payout_ledger_transaction_id')->nullable()->constrained('ledger_transactions')->nullOnDelete();
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignUuid('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['currency', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payout_requests');
        Schema::dropIfExists('commission_settlements');
    }
};
