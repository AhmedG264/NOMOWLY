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
        Schema::create('brand_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('company_name');
            $table->string('legal_name')->nullable();
            $table->string('commercial_reg_no')->nullable();
            $table->string('website_url')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('currency', 10)->default('EGP');
            $table->timestamps();
        });

        Schema::create('marketer_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('full_name');
            $table->string('phone')->nullable();
            $table->string('country', 10)->nullable();
            $table->timestamps();
        });

        Schema::create('marketer_specializations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('marketer_profile_id')->constrained('marketer_profiles')->cascadeOnDelete();
            $table->string('specialization', 32);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['marketer_profile_id', 'specialization'], 'marketer_spec_unique');
            $table->index(['marketer_profile_id', 'is_primary'], 'marketer_spec_primary_idx');
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 64)->index();
            $table->string('entity_type', 64)->nullable()->index();
            $table->string('entity_id', 64)->nullable()->index();
            $table->json('payload_diff')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('marketer_specializations');
        Schema::dropIfExists('marketer_profiles');
        Schema::dropIfExists('brand_profiles');
    }
};
