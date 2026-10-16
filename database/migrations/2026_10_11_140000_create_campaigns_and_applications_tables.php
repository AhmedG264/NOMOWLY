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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('brand_profile_id')->constrained('brand_profiles')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category', 100);
            $table->string('category_slug', 50)->index();
            $table->text('description');
            $table->text('brief');
            $table->text('requirements')->nullable();
            $table->json('target_markets'); // array of target countries
            $table->json('currencies');     // array of currency codes
            $table->string('commission_type', 32)->default('PERCENTAGE_CPO');
            $table->string('commission_value', 100);
            $table->string('avg_basket', 100)->nullable();
            $table->string('conversion_rate', 50)->nullable();
            $table->unsignedInteger('cookie_duration_days')->default(30);
            $table->json('tracking_methods'); // array of tracking mechanisms
            $table->string('status', 32)->default('ACTIVE')->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->string('badge', 100)->nullable();
            $table->text('brand_guidelines')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'is_featured'], 'campaigns_status_featured_idx');
        });

        Schema::create('campaign_applications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->foreignUuid('marketer_profile_id')->constrained('marketer_profiles')->cascadeOnDelete();
            $table->string('status', 32)->default('PENDING')->index();
            $table->text('pitch_note')->nullable();
            $table->json('channel_urls')->nullable();
            $table->string('tracking_code', 64)->nullable()->index();
            $table->string('tracking_link', 255)->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamp('applied_at')->useCurrent();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'marketer_profile_id'], 'campaign_marketer_unique');
            $table->index(['campaign_id', 'status'], 'campaign_app_status_idx');
            $table->index(['marketer_profile_id', 'status'], 'marketer_app_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_applications');
        Schema::dropIfExists('campaigns');
    }
};
