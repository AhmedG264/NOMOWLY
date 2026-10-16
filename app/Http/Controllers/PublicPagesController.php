<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicPagesController extends Controller
{
    /**
     * Curated authentic performance marketing campaigns for Nomowly marketplace showcase.
     */
    public static function getShowcaseCampaigns(): array
    {
        return [
            [
                'id' => 'cmp-101',
                'title' => 'متجر سارة للأزياء والعبايات الشرقية',
                'brand_name' => 'Sara Couture',
                'category' => 'أزياء وموضة',
                'category_slug' => 'fashion',
                'description' => 'كوليكشن عبايات وأزياء راقية في السعودية والخليج. معدل تحويل عالي مع سلة تسوق مرتفعة.',
                'commission' => '15%',
                'commission_type' => 'نسبة من المبيعة (CPO)',
                'avg_basket' => '480 ر.س',
                'cookie_days' => 30,
                'tracking_methods' => ['كود خصم حصري', 'رابط تتبع ذكي'],
                'target_markets' => ['السعودية', 'الإمارات', 'الكويت'],
                'currencies' => ['SAR', 'AED', 'KWD'],
                'conversion_rate' => '4.8%',
                'badge' => 'الأكثر طلباً',
            ],
            [
                'id' => 'cmp-102',
                'title' => 'لافندر برفيومز — العطور الفرنسية والشرقية',
                'brand_name' => 'Lavender Perfumes',
                'category' => 'عطور وتجميل',
                'category_slug' => 'perfumes',
                'description' => 'عطور أصلية فاخرة وخلطات مميزة مع تغليف هدايا مجاني وشحن سريع في الخليج.',
                'commission' => '65 ر.س / د.إ',
                'commission_type' => 'مبلغ ثابت لكل طلب',
                'avg_basket' => '320 ر.س',
                'cookie_days' => 45,
                'tracking_methods' => ['كود خصم مخصص'],
                'target_markets' => ['السعودية', 'الإمارات', 'قطر', 'عمان'],
                'currencies' => ['SAR', 'AED', 'QAR', 'OMR'],
                'conversion_rate' => '5.2%',
                'badge' => 'عمولة فورية',
            ],
            [
                'id' => 'cmp-103',
                'title' => 'تيك بلس — ملحقات الهواتف والأجهزة الذكية',
                'brand_name' => 'TechPlus Egypt',
                'category' => 'إلكترونيات',
                'category_slug' => 'electronics',
                'description' => 'شواحن سريعة، سماعات لاسلكية وإكسسوارات هواتف معتمدة مع ضمان سنة وشحن لجميع محافظات مصر.',
                'commission' => '12%',
                'commission_type' => 'نسبة من المبيعة (CPO)',
                'avg_basket' => '1,450 ج.م',
                'cookie_days' => 30,
                'tracking_methods' => ['رابط تتبع مباشر', 'كود خصم'],
                'target_markets' => ['مصر'],
                'currencies' => ['EGP'],
                'conversion_rate' => '6.1%',
                'badge' => 'سريع الشحن',
            ],
            [
                'id' => 'cmp-104',
                'title' => 'ناتشورال كير — منتجات العناية العضوية والمكملات',
                'brand_name' => 'Natural Care Arabia',
                'category' => 'صحة ورشاقة',
                'category_slug' => 'health',
                'description' => 'منتجات عناية بالبشرة والشعر ومكملات غذائية مرخصة رسمياً وخالية من المواد الكيميائية الضارة.',
                'commission' => '18%',
                'commission_type' => 'نسبة من المبيعة',
                'avg_basket' => '290 ر.س',
                'cookie_days' => 60,
                'tracking_methods' => ['كود خصم حصري', 'رابط تتبع'],
                'target_markets' => ['السعودية', 'مصر', 'الإمارات'],
                'currencies' => ['SAR', 'EGP', 'AED'],
                'conversion_rate' => '4.4%',
                'badge' => 'عائد تحويل عالي',
            ],
            [
                'id' => 'cmp-105',
                'title' => 'روعة للمفروشات والديكور المنزلي المعاصر',
                'brand_name' => 'Rawaa Home',
                'category' => 'مستلزمات منزل',
                'category_slug' => 'home',
                'description' => 'مفروشات فندقية فاخرة وديكورات داخلية عصرية للمنازل والفلل بأسعار منافسة.',
                'commission' => '90 ر.س / 450 ج.م',
                'commission_type' => 'مبلغ ثابت لكل طلب مستلم',
                'avg_basket' => '750 ر.س',
                'cookie_days' => 30,
                'tracking_methods' => ['كود خصم حصري'],
                'target_markets' => ['السعودية', 'الإمارات', 'مصر'],
                'currencies' => ['SAR', 'AED', 'EGP'],
                'conversion_rate' => '3.9%',
                'badge' => 'سلة شراء مرتفعة',
            ],
            [
                'id' => 'cmp-106',
                'title' => 'كيدز زون — ألعاب تعليمية ومستلزمات الأطفال',
                'brand_name' => 'KidsZone Arabia',
                'category' => 'أطفال وعائلة',
                'category_slug' => 'kids',
                'description' => 'أفضل الألعاب التنموية ومنتجات الرعاية بالرضع والأمهات مع توصيل سريع لجميع مدن الخليج ومصر.',
                'commission' => '14%',
                'commission_type' => 'نسبة من المبيعة',
                'avg_basket' => '340 ر.س',
                'cookie_days' => 30,
                'tracking_methods' => ['كود خصم', 'رابط تتبع ذكي'],
                'target_markets' => ['السعودية', 'مصر', 'الكويت'],
                'currencies' => ['SAR', 'EGP', 'KWD'],
                'conversion_rate' => '4.7%',
                'badge' => 'طلب متكرر',
            ],
        ];
    }

    /**
     * Categories list for filter navigation.
     */
    public static function getCategories(): array
    {
        return [
            ['slug' => 'all', 'name' => 'جميع التصنيفات'],
            ['slug' => 'fashion', 'name' => 'أزياء وموضة'],
            ['slug' => 'perfumes', 'name' => 'عطور وتجميل'],
            ['slug' => 'electronics', 'name' => 'إلكترونيات'],
            ['slug' => 'health', 'name' => 'صحة ورشاقة'],
            ['slug' => 'home', 'name' => 'مستلزمات منزل'],
            ['slug' => 'kids', 'name' => 'أطفال وعائلة'],
        ];
    }

    /**
     * Public Homepage: The genuine Nomowly performance marketplace experience.
     */
    public function home(): View
    {
        try {
            $dbCampaigns = \App\Models\Campaign::with('brandProfile')->active()->latest()->take(6)->get();
        } catch (\Throwable $e) {
            $dbCampaigns = collect();
        }

        if ($dbCampaigns->isNotEmpty()) {
            $campaigns = $dbCampaigns;
        } else {
            $campaigns = array_slice(self::getShowcaseCampaigns(), 0, 6);
        }

        $categories = self::getCategories();

        return view('welcome', compact('campaigns', 'categories'));
    }

    /**
     * Campaign Marketplace directory page.
     */
    public function marketplace(Request $request): View
    {
        $categories = self::getCategories();
        $selectedCategory = $request->query('category', 'all');
        $selectedCountry = $request->query('country', 'all');
        $selectedCommissionType = $request->query('commission_type', 'all');
        $search = trim($request->query('q', ''));

        try {
            $campaignCount = \App\Models\Campaign::count();
            if ($campaignCount > 0) {
                $query = \App\Models\Campaign::with(['brandProfile', 'applications'])->active()->latest();

                if ($selectedCategory !== 'all') {
                    $query->where(function ($q) use ($selectedCategory) {
                        $q->where('category_slug', $selectedCategory)
                          ->orWhere('category', $selectedCategory);
                    });
                }

                if ($selectedCountry !== 'all') {
                    $query->where(function ($q) use ($selectedCountry) {
                        $q->whereJsonContains('target_markets', $selectedCountry)
                          ->orWhere('target_markets', 'like', "%{$selectedCountry}%");
                    });
                }

                if ($selectedCommissionType !== 'all') {
                    $query->where('commission_type', $selectedCommissionType);
                }

                if (!empty($search)) {
                    $query->where(function ($q) use ($search) {
                        $q->where('title', 'like', "%{$search}%")
                          ->orWhere('description', 'like', "%{$search}%")
                          ->orWhere('category', 'like', "%{$search}%")
                          ->orWhereHas('brandProfile', function ($bq) use ($search) {
                              $bq->where('company_name', 'like', "%{$search}%");
                          });
                    });
                }

                $campaigns = $query->paginate(12)->withQueryString();
            } else {
                throw new \Exception("Empty campaigns in DB");
            }
        } catch (\Throwable $e) {
            $allCampaigns = self::getShowcaseCampaigns();
            $campaigns = array_filter($allCampaigns, function ($campaign) use ($selectedCategory, $selectedCountry, $search) {
                $matchesCategory = ($selectedCategory === 'all' || $campaign['category_slug'] === $selectedCategory);
                $matchesCountry = ($selectedCountry === 'all' || in_array($selectedCountry, (array) ($campaign['target_markets'] ?? []), true));
                $matchesSearch = empty($search) ||
                    str_contains(mb_strtolower($campaign['title']), mb_strtolower($search)) ||
                    str_contains(mb_strtolower($campaign['brand_name']), mb_strtolower($search)) ||
                    str_contains(mb_strtolower($campaign['description']), mb_strtolower($search));

                return $matchesCategory && $matchesCountry && $matchesSearch;
            });
        }

        return view('pages.marketplace', compact(
            'campaigns',
            'categories',
            'selectedCategory',
            'selectedCountry',
            'selectedCommissionType',
            'search'
        ));
    }

    /**
     * Display single campaign details page with application flow.
     */
    public function campaignShow(string $slug): View
    {
        $campaign = \App\Models\Campaign::with(['brandProfile', 'approvedApplications'])
            ->where('slug', $slug)
            ->orWhere('id', $slug)
            ->firstOrFail();

        \Illuminate\Support\Facades\Gate::authorize('view', $campaign);

        $user = auth()->user();
        $marketerProfile = $user?->marketerProfile;
        $myApplication = $marketerProfile ? $campaign->getApplicationFor($marketerProfile) : null;

        return view('pages.campaign-detail', compact('campaign', 'myApplication'));
    }

    /**
     * Dedicated page for Brands & E-commerce Stores.
     */
    public function forBrands(): View
    {
        return view('pages.for-brands');
    }

    /**
     * Dedicated page for Creators, Influencers & Media Buyers.
     */
    public function forMarketers(): View
    {
        return view('pages.for-marketers');
    }

    /**
     * Walkthrough page: How Nomowly works for both parties.
     */
    public function howItWorks(): View
    {
        return view('pages.how-it-works');
    }

    /**
     * Pricing and zero-risk performance model page.
     */
    public function pricing(): View
    {
        return view('pages.pricing');
    }
}
