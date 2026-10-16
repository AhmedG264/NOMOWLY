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
        // 1. Add destination_url to campaigns if not already present
        if (!Schema::hasColumn('campaigns', 'destination_url')) {
            Schema::table('campaigns', function (Blueprint $table) {
                $table->string('destination_url', 500)->nullable()->after('slug');
            });
        }

        // 2. Attribution Clicks
        Schema::create('attribution_clicks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->foreignUuid('campaign_application_id')->constrained('campaign_applications')->cascadeOnDelete();
            $table->string('tracking_code', 64)->index();
            $table->string('tracking_link', 255)->nullable();
            $table->string('visitor_id', 64)->index();
            $table->string('session_id', 64)->index();
            $table->string('ip_hash', 64)->nullable()->index();
            $table->string('user_agent_hash', 64)->nullable();
            $table->string('referrer', 500)->nullable();
            $table->string('landing_url', 500)->nullable();
            $table->string('country', 10)->nullable();
            $table->string('device_type', 32)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['campaign_id', 'created_at'], 'clicks_camp_time_idx');
            $table->index(['campaign_id', 'visitor_id'], 'clicks_camp_visitor_idx');
            $table->index(['campaign_application_id', 'created_at'], 'clicks_app_time_idx');
        });

        // 3. Conversions
        Schema::create('conversions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->foreignUuid('campaign_application_id')->nullable()->constrained('campaign_applications')->nullOnDelete();
            $table->foreignUuid('attribution_click_id')->nullable()->constrained('attribution_clicks')->nullOnDelete();
            $table->string('visitor_id', 64)->nullable()->index();
            $table->string('external_order_id', 128)->index();
            $table->string('conversion_type', 32)->default('ORDER');
            $table->string('status', 32)->default('PENDING')->index();
            $table->decimal('order_value', 12, 2);
            $table->string('currency', 10);
            $table->decimal('commission_value', 12, 2)->default(0.00);
            $table->string('rejection_reason', 255)->nullable();
            $table->timestamp('occurred_at')->useCurrent()->index();
            $table->timestamp('verified_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Critical Idempotency & Deduplication: Same external order for a campaign cannot be duplicated
            $table->unique(['campaign_id', 'external_order_id'], 'campaign_order_unique');
            $table->index(['campaign_id', 'status'], 'conv_camp_status_idx');
            $table->index(['campaign_application_id', 'status'], 'conv_app_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversions');
        Schema::dropIfExists('attribution_clicks');

        if (Schema::hasColumn('campaigns', 'destination_url')) {
            Schema::table('campaigns', function (Blueprint $table) {
                $table->dropColumn('destination_url');
            });
        }
    }
};
