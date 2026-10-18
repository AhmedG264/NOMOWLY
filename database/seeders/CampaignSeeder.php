<?php

namespace Database\Seeders;

use App\Enums\CampaignApplicationStatus;
use App\Enums\CampaignCommissionType;
use App\Enums\CampaignStatus;
use App\Enums\MarketerSpecializationType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\BrandProfile;
use App\Models\Campaign;
use App\Models\CampaignApplication;
use App\Models\MarketerProfile;
use App\Models\MarketerSpecialization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CampaignSeeder extends Seeder
{
    /**
     * Seed campaigns, demo accounts, and authentic applications.
     *
     * PRODUCTION SAFETY GUARD:
     * This seeder contains local development / staging demo fixtures.
     * It is strictly prevented from executing in production environments.
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->error('CRITICAL: CampaignSeeder contains development demo data and cannot be run in production.');
            return;
        }

        // Development/Testing demo credentials only (Never used in production)
        $demoPassword = env('DEMO_ACCOUNTS_PASSWORD', 'Password123!');

        // 1. Seed or find demo Brand user: Sara Couture
        $brandUser = User::firstOrCreate(
            ['email' => 'brand@nomowly.com'],
            [
                'name' => 'سارة كوتور للأزياء',
                'password' => Hash::make($demoPassword),
                'role' => UserRole::BRAND,
                'status' => UserStatus::ACTIVE,
                'must_change_password' => false,
                'email_verified_at' => now(),
            ]
        );

        $brandProfile = BrandProfile::firstOrCreate(
            ['user_id' => $brandUser->id],
            [
                'company_name' => 'Sara Couture',
                'legal_name' => 'شركة سارة للأزياء والتصميم ذ.م.م',
                'commercial_reg_no' => '1010987654',
                'website_url' => 'https://saracouture.com',
                'currency' => 'SAR',
            ]
        );

        // 2. Seed or find second Brand user: TechPlus Egypt
        $techBrandUser = User::firstOrCreate(
            ['email' => 'tech@nomowly.com'],
            [
                'name' => 'تيك بلس مصر',
                'password' => Hash::make($demoPassword),
                'role' => UserRole::BRAND,
                'status' => UserStatus::ACTIVE,
                'must_change_password' => false,
                'email_verified_at' => now(),
            ]
        );

        $techBrandProfile = BrandProfile::firstOrCreate(
            ['user_id' => $techBrandUser->id],
            [
                'company_name' => 'TechPlus Egypt',
                'legal_name' => 'شركة تيك بلس لتكنولوجيا الإلكترونيات ش.م.م',
                'commercial_reg_no' => '4020192837',
                'website_url' => 'https://techplusegypt.com',
                'currency' => 'EGP',
            ]
        );

        // 3. Seed or find demo Marketer user: Ahmed Kamal
        $marketerUser = User::firstOrCreate(
            ['email' => 'marketer@nomowly.com'],
            [
                'name' => 'أحمد كمال (Ahmed Kamal)',
                'password' => Hash::make($demoPassword),
                'role' => UserRole::MARKETER,
                'status' => UserStatus::ACTIVE,
                'must_change_password' => false,
                'email_verified_at' => now(),
            ]
        );

        $marketerProfile = MarketerProfile::firstOrCreate(
            ['user_id' => $marketerUser->id],
            [
                'full_name' => 'أحمد كمال',
                'phone' => '+966501234567',
                'country' => 'السعودية',
            ]
        );

        MarketerSpecialization::firstOrCreate(
            ['marketer_profile_id' => $marketerProfile->id, 'specialization' => MarketerSpecializationType::CREATOR],
            ['is_primary' => true]
        );

        MarketerSpecialization::firstOrCreate(
            ['marketer_profile_id' => $marketerProfile->id, 'specialization' => MarketerSpecializationType::AFFILIATE],
            ['is_primary' => false]
        );

        // 4. Seed Campaigns
        $campaignsData = [
            [
                'brand_profile_id' => $brandProfile->id,
                'title' => 'متجر سارة للأزياء والعبايات الشرقية الفاخرة',
                'slug' => 'sara-couture-fashion',
                'destination_url' => 'https://saracouture.com',
                'category' => 'أزياء وموضة',
                'category_slug' => 'fashion',
                'description' => 'كوليكشن عبايات وأزياء راقية في السعودية والخليج. معدل تحويل عالي مع سلة تسوق مرتفعة وتصميمات حصرية.',
                'brief' => "تقدم سارة كوتور أحدث تشكيلة من العبايات والفساتين الخليجية الراقية المصنوعة من أفخر أنواع الأقمشة الكورية واليابانية. \n\nنستهدف السيدات والفتيات في الفئة العمرية (20-45) المهتمات بالأناقة والظهور المتميز في المناسبات والمواسم. \n\nالمنتجات تتميز بجودة خياطة فائقة وتغليف فاخر يليق بالإهداء، مع توصيل سريع وضمان استبدال واسترجاع ميسر.",
                'requirements' => "• فيديو ريلز (Reel) أو مقطع تيك توك يوضح تفاصيل القماش والتصميم.\n• تصوير فتح الصندوق (Unboxing Experience) والإشادة بفخامة التغليف.\n• تضمين كود الخصم الحصري في الفيديو وفي وصف المنشور (Caption).",
                'target_markets' => ['السعودية', 'الإمارات', 'الكويت'],
                'currencies' => ['SAR', 'AED', 'KWD'],
                'commission_type' => CampaignCommissionType::PERCENTAGE_CPO,
                'commission_value' => '15',
                'avg_basket' => '480 ر.س',
                'conversion_rate' => '4.8%',
                'cookie_duration_days' => 30,
                'tracking_methods' => ['كود خصم حصري', 'رابط تتبع ذكي'],
                'status' => CampaignStatus::ACTIVE,
                'is_featured' => true,
                'badge' => 'الأكثر طلباً',
                'brand_guidelines' => "يمنع الإعلان المضلل أو الادعاء بوجود تخفيضات غير حقيقية خارج العرض. يمنع المزايدة على الكلمات المفتاحية للعلامة في Google Ads.",
            ],
            [
                'brand_profile_id' => $brandProfile->id,
                'title' => 'لافندر برفيومز — العطور الفرنسية والشرقية الفاخرة',
                'slug' => 'lavender-perfumes-arabia',
                'category' => 'عطور وتجميل',
                'category_slug' => 'perfumes',
                'description' => 'عطور أصلية فاخرة وخلطات مسك وبخور مميزة مع تغليف هدايا مجاني وشحن سريع لجميع مدن الخليج.',
                'brief' => "لافندر برفيومز دار عطور خليجية تجمع بين الزيوت العطرية الفرنسية الفاخرة ونفحات العود والمسك الشرقي الأصيل. \n\nالتركيز العالي (Eau de Parfum) والثبات الممتد لأكثر من 24 ساعة يجعلان المنتجات سريعة التحويل وعالية الطلب في مواسم الهدايا والمناسبات.",
                'requirements' => "• مراجعة عطرية صادقة لشخصيات العطور المختلفة.\n• إبراز علبة الإهداء المجانية والمظهر الفخم للزجاجة.\n• رابط مباشر في البايو أو كود خصم في الفيديو.",
                'target_markets' => ['السعودية', 'الإمارات', 'قطر', 'عمان'],
                'currencies' => ['SAR', 'AED', 'QAR', 'OMR'],
                'commission_type' => CampaignCommissionType::FIXED_PER_ORDER,
                'commission_value' => '65 ر.س / د.إ',
                'avg_basket' => '320 ر.س',
                'conversion_rate' => '5.2%',
                'cookie_duration_days' => 45,
                'tracking_methods' => ['كود خصم مخصص', 'رابط تتبع ذكي'],
                'status' => CampaignStatus::ACTIVE,
                'is_featured' => true,
                'badge' => 'عمولة فورية',
                'brand_guidelines' => "يجب ذكر أن العطور حاصلة على شهادات مطابقة من هيئة الغذاء والدواء.",
            ],
            [
                'brand_profile_id' => $techBrandProfile->id,
                'title' => 'تيك بلس مصر — ملحقات الهواتف الذكية والأجهزة المحمولة',
                'slug' => 'techplus-electronics-egypt',
                'category' => 'إلكترونيات',
                'category_slug' => 'electronics',
                'description' => 'شواحن سريعة، سماعات لاسلكية وإكسسوارات هواتف معتمدة مع ضمان سنة وشحن لجميع محافظات مصر والخليج.',
                'brief' => "تيك بلس هي العلامة الرائدة في مصر لملحقات الهواتف الذكية الأصلية والبدائل المعتمدة ذات الجودة العالية. \n\nنوفر شواحن تدعم GaN، بنوك طاقة فائقة السعة، وسماعات عازلة للضوضاء بأسعار منافسة مع خيارات دفع عند الاستلام وتقسيط بدون فوائد.",
                'requirements' => "• تجربة سرعة الشحن أو جودة الصوت عملياً في فيديو قصير.\n• توضيح مدة الضمان (12 شهر استبدال فوري).\n• توجيه المتابعين للشراء عبر كود الخصم الحصري.",
                'target_markets' => ['مصر', 'السعودية'],
                'currencies' => ['EGP', 'SAR'],
                'commission_type' => CampaignCommissionType::PERCENTAGE_CPO,
                'commission_value' => '12%',
                'avg_basket' => '1,450 ج.م',
                'conversion_rate' => '3.9%',
                'cookie_duration_days' => 30,
                'tracking_methods' => ['كود خصم حصري', 'رابط تتبع ذكي'],
                'status' => CampaignStatus::ACTIVE,
                'is_featured' => true,
                'badge' => 'طلب عالي في مصر',
                'brand_guidelines' => "التأكيد على أن الضمان محلي معتمد مع إمكانية المعاينة قبل الاستلام.",
            ],
            [
                'brand_profile_id' => $techBrandProfile->id,
                'title' => 'محفظة كاشي الرقمية — تطبيق المدفوعات والتحويل الفوري',
                'slug' => 'cashy-pay-fintech-app',
                'category' => 'خدمات وتطبيقات',
                'category_slug' => 'fintech',
                'description' => 'تطبيق مالي رائد يوفر تحويل أموال فوري بدون رسوم وبطاقة دفع رقمية مع كاش باك على المشتريات اليومية.',
                'brief' => "تطبيق كاشي هو الحل المالي الذكي للشباب وأصحاب الأعمال الحرة لإرسال واستقبال الأموال محلياً ودولياً بضغطة زر. \n\nالحملة موجهة لمشتري الإعلانات وصناع المحتوى التقني والمالي لجلب مستخدمين مسجلين ومفعلين لحساباتهم.",
                'requirements' => "• شرح خطوات التسجيل وتفعيل الحساب في أقل من دقيقة.\n• إبراز ميزة الكاش باك الفوري والتحويل المجاني.\n• وضع رابط التحميل الذكي المشفر في البايو أو الإعلانات الممولة.",
                'target_markets' => ['مصر', 'السعودية', 'الإمارات'],
                'currencies' => ['EGP', 'SAR', 'AED'],
                'commission_type' => CampaignCommissionType::CPA_ACTION,
                'commission_value' => '45 ر.س / 250 ج.م لكل تفعيل',
                'avg_basket' => 'تطبيق مجاني',
                'conversion_rate' => '8.5%',
                'cookie_duration_days' => 14,
                'tracking_methods' => ['رابط تتبع ذكي', 'إحالة مباشرة'],
                'status' => CampaignStatus::ACTIVE,
                'is_featured' => true,
                'badge' => 'تطبيق مالي رائد',
                'brand_guidelines' => "يمنع تقديم وعود استثمارية أو أرباح مالية. التركيز على مزايا السهولة والأمان والسرعة.",
            ],
            [
                'brand_profile_id' => $brandProfile->id,
                'title' => 'نيوتري فيتا — المكملات الغذائية والمنتجات الرياضية المعتمدة',
                'slug' => 'nutrivita-sports-nutrition',
                'category' => 'صحة ورشاقة',
                'category_slug' => 'health',
                'description' => 'بروتينات، فيتامينات ومعززات طاقة طبيعية معتمدة من هيئات الغذاء والدواء مع استشارات تغذية مجانية.',
                'brief' => "نيوتري فيتا توفر أفضل منتجات التغذية الرياضية ومكملات العناية بالصحة العامة المعتمدة عالمياً. \n\nنستهدف المهتمين بنمط الحياة الصحي، الرياضيين، ومدربي اللياقة البدنية في الخليج ومصر بأسعار تنافسية وخيارات شحن سريعة.",
                'requirements' => "• محتوى يتناول الروتين اليومي للتمرين أو التغذية مع إبراز المنتج.\n• توضيح المكونات الطبيعية وخلو المنتج من المواد المحظورة.\n• كود الخصم يوفر 10% إضافية للمتابع.",
                'target_markets' => ['السعودية', 'الإمارات', 'الكويت'],
                'currencies' => ['SAR', 'AED', 'KWD'],
                'commission_type' => CampaignCommissionType::PERCENTAGE_CPO,
                'commission_value' => '18%',
                'avg_basket' => '390 ر.س',
                'conversion_rate' => '4.2%',
                'cookie_duration_days' => 30,
                'tracking_methods' => ['كود خصم حصري', 'رابط تتبع ذكي'],
                'status' => CampaignStatus::ACTIVE,
                'is_featured' => false,
                'badge' => 'سلة شراء متكررة',
                'brand_guidelines' => "يجب التأكيد على أن المكملات ليست بديلاً عن النظام الغذائي المتوازن واستشارة الطبيب المختص.",
            ],
            [
                'brand_profile_id' => $brandProfile->id,
                'title' => 'دار الأصالة — الأثاث المنزلي والمفروشات الفندقية الفاخرة',
                'slug' => 'dar-alasalah-home-furniture',
                'category' => 'مستلزمات منزل',
                'category_slug' => 'home',
                'description' => 'مفروشات فندقية فاخرة وديكورات داخلية عصرية للمنازل والفلل بأسعار منافسة مع خدمة التوصيل والتركيب.',
                'brief' => "دار الأصالة متخصصة في توفير أطقم اللحاف والمفارش القطنية الفندقية (100% قطن مصري ناعم) مع إكسسوارات الديكور الفاخرة. \n\nسلة الشراء المرتفعة ومعدل الإرجاع شبه المنعدم يجعلان هذه الحملة من أكثر الفرص ربحية للمسوقين وصناع محتوى الديكور والمنزل.",
                'requirements' => "• مقطع جولة في غرفة النوم أو المنزل يبرز فخامة المفرش وتناسق الألوان.\n• تجربة ملمس القطن والراحة الفندقية.\n• كود خصم مع رابط شراء مباشر.",
                'target_markets' => ['السعودية', 'الإمارات', 'مصر'],
                'currencies' => ['SAR', 'AED', 'EGP'],
                'commission_type' => CampaignCommissionType::FIXED_PER_ORDER,
                'commission_value' => '90 ر.س / 450 ج.م لكل طلب',
                'avg_basket' => '750 ر.س',
                'conversion_rate' => '3.9%',
                'cookie_duration_days' => 30,
                'tracking_methods' => ['كود خصم حصري'],
                'status' => CampaignStatus::ACTIVE,
                'is_featured' => false,
                'badge' => 'سلة تسوق مرتفعة',
                'brand_guidelines' => "التأكيد على أن القطن طبيعي 100% مع ضمان الجودة ضد الانكماش أو تغير اللون.",
            ],
        ];

        foreach ($campaignsData as $data) {
            $camp = Campaign::updateOrCreate(['slug' => $data['slug']], $data);
            app(\App\Services\Campaign\CampaignTermsService::class)->createInitialVersion($camp);
        }

        // 5. Seed Applications for Ahmed Kamal
        $saraCampaign = Campaign::where('slug', 'sara-couture-fashion')->first();
        if ($saraCampaign) {
            $approvedApp = CampaignApplication::updateOrCreate(
                [
                    'campaign_id' => $saraCampaign->id,
                    'marketer_profile_id' => $marketerProfile->id,
                ],
                [
                    'terms_version_id' => $saraCampaign->current_terms_version_id,
                    'status' => CampaignApplicationStatus::APPROVED,
                    'pitch_note' => 'أخطط لنشر سلسة فيديوهات ريلز وتيك توك تستعرض تجربة أزياء المناسبات والعبايات مع كود الخصم الحصري لجمهوري في الرياض وجدة.',
                    'channel_urls' => ['https://instagram.com/ahmed_reviews', 'https://tiktok.com/@ahmed_fashion'],
                    'tracking_code' => 'SARA-KAMAL-15',
                    'tracking_link' => url('/go/sara-kamal-15'),
                    'review_notes' => 'مرحباً أحمد، سعداء بانضمامك كشريك تسويقي رسمي. كودك مفعل الآن بنسبة خصم وعمولة 15%.',
                    'applied_at' => now()->subDays(3),
                    'accepted_terms_at' => now()->subDays(3),
                    'reviewed_at' => now()->subDays(2),
                ]
            );

            $saraCampaign->lockTerms('تم تثبيت الشروط التعاقدية لاعتماد أول مسوق رسمي في الحملة.');
        }

        $lavenderCampaign = Campaign::where('slug', 'lavender-perfumes-arabia')->first();
        if ($lavenderCampaign) {
            CampaignApplication::updateOrCreate(
                [
                    'campaign_id' => $lavenderCampaign->id,
                    'marketer_profile_id' => $marketerProfile->id,
                ],
                [
                    'terms_version_id' => $lavenderCampaign->current_terms_version_id,
                    'status' => CampaignApplicationStatus::PENDING,
                    'pitch_note' => 'لدي جمهور مهتم بالعطور الفاخرة والهدايا في السعودية والإمارات وأرغب بالحصول على كود خصم مخصص للحملة.',
                    'channel_urls' => ['https://tiktok.com/@ahmed_perfumes'],
                    'applied_at' => now()->subHours(6),
                    'accepted_terms_at' => now()->subHours(6),
                ]
            );
        }
    }
}
