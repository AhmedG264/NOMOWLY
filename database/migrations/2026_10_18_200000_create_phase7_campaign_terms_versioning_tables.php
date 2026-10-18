<?php

use App\Enums\CampaignTermsVersionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create campaign_terms_versions table
        Schema::create('campaign_terms_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('status', 32)->default(CampaignTermsVersionStatus::ACTIVE->value)->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('brief')->nullable();
            $table->string('commission_type', 32);
            $table->string('commission_value', 100);
            $table->string('currency', 10)->default('SAR');
            $table->json('currencies')->nullable();
            $table->unsignedInteger('cookie_duration_days')->default(30);
            $table->text('conversion_qualification_rules')->nullable();
            $table->text('approval_rules')->nullable();
            $table->text('cancellation_refund_rules')->nullable();
            $table->timestamp('effective_from')->useCurrent();
            $table->timestamp('effective_until')->nullable();
            $table->text('change_summary')->nullable();
            $table->foreignUuid('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['campaign_id', 'version_number'], 'camp_version_unique');
            $table->index(['campaign_id', 'status'], 'camp_version_status_idx');
        });

        // 2. Create campaign_terms_change_requests table
        Schema::create('campaign_terms_change_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->foreignUuid('current_version_id')->nullable()->constrained('campaign_terms_versions')->nullOnDelete();
            $table->string('status', 32)->default('PENDING')->index();
            $table->string('scope', 32)->default('NEW_MARKETERS_ONLY');
            $table->text('reason');
            $table->timestamp('proposed_effective_from')->nullable();
            $table->json('proposed_terms');
            $table->json('previous_terms_snapshot');
            $table->json('diff_summary')->nullable();
            $table->foreignUuid('requested_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reviewer_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignUuid('applied_terms_version_id')->nullable()->constrained('campaign_terms_versions')->nullOnDelete();
            $table->timestamps();

            $table->index(['campaign_id', 'status'], 'camp_cr_status_idx');
            $table->index(['status', 'created_at'], 'cr_status_time_idx');
        });

        // 3. Add terms locking and current version to campaigns table
        Schema::table('campaigns', function (Blueprint $table) {
            $table->foreignUuid('current_terms_version_id')->nullable()->after('brand_profile_id')->constrained('campaign_terms_versions')->nullOnDelete();
            $table->boolean('is_terms_locked')->default(false)->after('status')->index();
            $table->timestamp('terms_locked_at')->nullable()->after('is_terms_locked');
            $table->string('terms_lock_reason', 255)->nullable()->after('terms_locked_at');
        });

        // 4. Add terms_version_id to campaign_applications
        Schema::table('campaign_applications', function (Blueprint $table) {
            $table->foreignUuid('terms_version_id')->nullable()->after('campaign_id')->constrained('campaign_terms_versions')->nullOnDelete();
            $table->timestamp('accepted_terms_at')->nullable()->after('terms_version_id');
        });

        // 5. Add terms_version_id to attribution_clicks
        Schema::table('attribution_clicks', function (Blueprint $table) {
            $table->foreignUuid('terms_version_id')->nullable()->after('campaign_application_id')->constrained('campaign_terms_versions')->nullOnDelete();
        });

        // 6. Add terms_version_id and snapshot to conversions
        Schema::table('conversions', function (Blueprint $table) {
            $table->foreignUuid('terms_version_id')->nullable()->after('campaign_application_id')->constrained('campaign_terms_versions')->nullOnDelete();
            $table->json('commission_terms_snapshot')->nullable()->after('metadata');
        });

        // 7. Backfill existing campaigns with initial v1 terms version
        $campaigns = DB::table('campaigns')->get();
        foreach ($campaigns as $camp) {
            $versionId = (string) Str::uuid();
            $brandUser = DB::table('brand_profiles')->where('id', $camp->brand_profile_id)->value('user_id');

            $currencies = is_string($camp->currencies) ? json_decode($camp->currencies, true) : (array) $camp->currencies;
            $mainCurrency = $currencies[0] ?? 'SAR';

            DB::table('campaign_terms_versions')->insert([
                'id' => $versionId,
                'campaign_id' => $camp->id,
                'version_number' => 1,
                'status' => CampaignTermsVersionStatus::ACTIVE->value,
                'title' => $camp->title,
                'description' => $camp->description,
                'brief' => $camp->brief,
                'commission_type' => $camp->commission_type,
                'commission_value' => $camp->commission_value,
                'currency' => $mainCurrency,
                'currencies' => is_string($camp->currencies) ? $camp->currencies : json_encode($camp->currencies),
                'cookie_duration_days' => $camp->cookie_duration_days ?? 30,
                'conversion_qualification_rules' => 'احتساب مباشر للمبيعات المكتملة عبر الرابط التتبعي أو كود الخصم ضمن فترة الكوكيز.',
                'approval_rules' => 'مراجعة المبيعات خلال 14 يوماً من تاريخ الطلب واستبعاد الطلبات المرتجعة أو الملغاة.',
                'cancellation_refund_rules' => 'يتم عكس العمولة في حال استرجاع العميل للطلب بالكامل قبل انقضاء فترة المرتجعات.',
                'effective_from' => $camp->starts_at ?? $camp->created_at ?? now(),
                'change_summary' => 'الإصدار الأولي التعاقدي للحملة التسويقية (v1)',
                'created_by_user_id' => $brandUser,
                'created_at' => $camp->created_at ?? now(),
                'updated_at' => $camp->updated_at ?? now(),
            ]);

            // Determine if campaign should be locked
            $hasApprovedApps = DB::table('campaign_applications')
                ->where('campaign_id', $camp->id)
                ->where('status', 'APPROVED')
                ->exists();

            $hasClicks = DB::table('attribution_clicks')
                ->where('campaign_id', $camp->id)
                ->exists();

            $hasConversions = DB::table('conversions')
                ->where('campaign_id', $camp->id)
                ->exists();

            $isLocked = $hasApprovedApps || $hasClicks || $hasConversions;

            DB::table('campaigns')->where('id', $camp->id)->update([
                'current_terms_version_id' => $versionId,
                'is_terms_locked' => $isLocked,
                'terms_locked_at' => $isLocked ? now() : null,
                'terms_lock_reason' => $isLocked ? 'تم تثبيت الشروط تلقائياً لوجود نشاط تسويقي أو مسوقين معتمدين مسبقاً.' : null,
            ]);

            // Link existing applications to this v1
            DB::table('campaign_applications')
                ->where('campaign_id', $camp->id)
                ->whereNull('terms_version_id')
                ->update([
                    'terms_version_id' => $versionId,
                    'accepted_terms_at' => now(),
                ]);

            // Link existing clicks to this v1
            DB::table('attribution_clicks')
                ->where('campaign_id', $camp->id)
                ->whereNull('terms_version_id')
                ->update([
                    'terms_version_id' => $versionId,
                ]);

            // Link existing conversions to this v1 and save snapshot
            $convs = DB::table('conversions')->where('campaign_id', $camp->id)->get();
            foreach ($convs as $conv) {
                $snapshot = [
                    'terms_version_id' => $versionId,
                    'version_number' => 1,
                    'commission_type' => $camp->commission_type,
                    'commission_value' => $camp->commission_value,
                    'currency' => $conv->currency,
                    'cookie_duration_days' => $camp->cookie_duration_days ?? 30,
                    'order_value' => (string) $conv->order_value,
                    'calculated_commission' => (string) $conv->commission_value,
                    'calculated_at' => (string) ($conv->created_at ?? now()),
                ];

                DB::table('conversions')->where('id', $conv->id)->update([
                    'terms_version_id' => $versionId,
                    'commission_terms_snapshot' => json_encode($snapshot),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversions', function (Blueprint $table) {
            $table->dropForeign(['terms_version_id']);
            $table->dropColumn(['terms_version_id', 'commission_terms_snapshot']);
        });

        Schema::table('attribution_clicks', function (Blueprint $table) {
            $table->dropForeign(['terms_version_id']);
            $table->dropColumn('terms_version_id');
        });

        Schema::table('campaign_applications', function (Blueprint $table) {
            $table->dropForeign(['terms_version_id']);
            $table->dropColumn(['terms_version_id', 'accepted_terms_at']);
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropForeign(['current_terms_version_id']);
            $table->dropColumn(['current_terms_version_id', 'is_terms_locked', 'terms_locked_at', 'terms_lock_reason']);
        });

        Schema::dropIfExists('campaign_terms_change_requests');
        Schema::dropIfExists('campaign_terms_versions');
    }
};
