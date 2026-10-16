<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductVisionAndMarketingPagesTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Test that homepage presents genuine Arab performance marketing and creator economy content.
     */
    public function test_homepage_reflects_nomowly_performance_marketing_product_vision(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('dir="rtl"', false);
        $response->assertSee('lang="ar"', false);
        $response->assertSee('منصة نمولي');
        $response->assertSee('Nomowly');
        $response->assertSee('منصة عربية للتسويق بالأداء واقتصاد صناع المحتوى');
        $response->assertSee('حوّل التأثير إلى');
        $response->assertSee('نمو حقيقي');
        $response->assertSee('أطلق حملتك');
        $response->assertSee('انضم كصانع محتوى');
        $response->assertSee('اقتصاد جديد لصناع المحتوى');
        $response->assertSee('من العلامات الناشئة إلى الشركات الكبرى');
        $response->assertSee('حدد هدفك');
        $response->assertSee('وسع ما ينجح');

        // Verify technical noise is removed from public home
        $response->assertDontSee('Laravel 12+ (Eloquent & Blade)');
        $response->assertDontSee('MySQL 8+ (InnoDB Engines)');
        $response->assertDontSee('Laravel Database Queue');
        $response->assertDontSee('Hostinger Cron Scheduler');
        $response->assertDontSee('مخطط مراحل الهجرة المعمارية');
    }

    /**
     * Test that all public product marketing pages return 200 OK with correct layouts and new copy.
     */
    public function test_public_product_pages_render_successfully(): void
    {
        $routes = [
            '/' => 'حوّل التأثير إلى',
            '/marketplace' => 'اكتشف فرص الحملات التسويقية في المنطقة',
            '/for-brands' => 'نمِّ مبيعاتك وأعمالك من خلال',
            '/for-marketers' => 'حوّل التأثير والخبرة التسويقية إلى',
            '/how-it-works' => 'كيف تعمل منصة نمولي',
            '/pricing' => 'لا اشتراكات شهرية، ادفع فقط عند تحقيق مبيعات فعلية',
        ];

        foreach ($routes as $route => $expectedPhrase) {
            $response = $this->get($route);
            $response->assertStatus(200);
            $response->assertSee($expectedPhrase);
            $response->assertSee('/build/assets/app-', false);
        }
    }

    /**
     * Test that marketplace search and category filtering work properly.
     */
    public function test_marketplace_filtering_and_search(): void
    {
        // 1. Filter by category
        $responseCategory = $this->get('/marketplace?category=perfumes');
        $responseCategory->assertStatus(200);
        $responseCategory->assertSee('Lavender Perfumes');

        // 2. Search query
        $responseSearch = $this->get('/marketplace?q=' . urlencode('سارة'));
        $responseSearch->assertStatus(200);
        $responseSearch->assertSee('Sara Couture');
    }

    /**
     * Test navigation and footer links.
     */
    public function test_header_and_footer_contain_nomowly_product_navigation(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee(route('home'));
        $response->assertSee(route('marketplace'));
        $response->assertSee(route('for-brands'));
        $response->assertSee(route('for-marketers'));
        $response->assertSee(route('how-it-works'));
        $response->assertSee(route('pricing'));
        $response->assertSee('hello@nomowly.com');
    }
}
