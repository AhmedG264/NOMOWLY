<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for Phase 6 Payment Foundation, Deposits, Webhooks & Refunds.
     */
    public function up(): void
    {
        // 1. Payment Deposits Table
        Schema::create('payment_deposits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('deposit_number', 64)->unique();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('wallet_id')->constrained('wallets')->cascadeOnDelete();
            $table->string('currency', 3);
            $table->decimal('amount', 18, 4);
            $table->decimal('fee_amount', 18, 4)->default(0);
            $table->decimal('net_amount', 18, 4);
            $table->string('provider', 32); // fake, moyasar, tap
            $table->string('provider_transaction_id', 128)->nullable()->index();
            $table->string('payment_method', 64)->nullable(); // mada, visa, mastercard, applepay
            $table->string('status', 32)->default('PENDING'); // PENDING, PROCESSING, SUCCEEDED, FAILED, CANCELLED, PARTIALLY_REFUNDED, REFUNDED
            $table->string('idempotency_key', 128)->unique();
            $table->json('metadata')->nullable();
            $table->text('failure_reason')->nullable();
            $table->foreignUuid('ledger_transaction_id')->nullable()->constrained('ledger_transactions')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['currency', 'status', 'created_at']);
        });

        // 2. Payment Webhooks Table (Strict idempotency and audit)
        Schema::create('payment_webhooks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('provider', 32);
            $table->string('event_id', 128);
            $table->string('event_type', 64);
            $table->json('payload');
            $table->text('signature')->nullable();
            $table->string('status', 32)->default('PROCESSED'); // PROCESSED, FAILED, IGNORED
            $table->foreignUuid('deposit_id')->nullable()->constrained('payment_deposits')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            // Strict deduplication: same provider event cannot be processed twice
            $table->unique(['provider', 'event_id']);
            $table->index(['provider', 'status', 'created_at']);
        });

        // 3. Payment Refunds Table
        Schema::create('payment_refunds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('refund_number', 64)->unique();
            $table->foreignUuid('deposit_id')->constrained('payment_deposits')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 18, 4);
            $table->string('currency', 3);
            $table->text('reason');
            $table->string('status', 32)->default('REQUESTED'); // REQUESTED, PROCESSING, SUCCEEDED, FAILED, CANCELLED
            $table->string('provider_refund_id', 128)->nullable()->index();
            $table->string('idempotency_key', 128)->unique();
            $table->json('metadata')->nullable();
            $table->foreignUuid('ledger_transaction_id')->nullable()->constrained('ledger_transactions')->nullOnDelete();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['deposit_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_refunds');
        Schema::dropIfExists('payment_webhooks');
        Schema::dropIfExists('payment_deposits');
    }
};
