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
        Schema::create('brand_integrations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('brand_profile_id')->constrained('brand_profiles')->cascadeOnDelete();
            $table->string('name', 128);
            $table->string('provider', 64)->default('CUSTOM')->index();
            $table->string('api_key', 80)->unique();
            $table->text('api_secret'); // Encrypted at rest
            $table->string('status', 32)->default('ACTIVE')->index();
            $table->string('webhook_url', 500)->nullable();
            $table->json('allowed_ips')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['brand_profile_id', 'status'], 'brand_integ_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brand_integrations');
    }
};
