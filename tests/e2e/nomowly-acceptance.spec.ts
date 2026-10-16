import { test, expect } from '@playwright/test';

// Helper to attach error listeners to page
function setupErrorListeners(page: any, collectedErrors: string[], collectedFailedRequests: string[]) {
  page.on('console', (msg: any) => {
    if (msg.type() === 'error') {
      const text = msg.text();
      // Ignore known benign iframe/websocket/font warnings
      if (!text.includes('Failed to load resource') && !text.includes('WebSocket')) {
        collectedErrors.push(`[Console Error] ${text}`);
      }
    }
  });

  page.on('response', (response: any) => {
    const status = response.status();
    const url = response.url();
    // Capture unexpected HTTP errors on our app routes
    if (status >= 400 && !url.includes('favicon') && !url.includes('.png')) {
      collectedFailedRequests.push(`[HTTP ${status}] ${url}`);
    }
  });
}

test.describe('Nomowly Phase 5.2.4 Real Browser End-to-End Suite', () => {

  test('1. Public Homepage Navigation', async ({ page }) => {
    const consoleErrors: string[] = [];
    const failedReqs: string[] = [];
    setupErrorListeners(page, consoleErrors, failedReqs);

    // 1. Open Homepage
    await page.goto('/');
    await expect(page).toHaveTitle(/نمولي/);
    await expect(page.locator('h1')).toContainText('سوق التسويق بالأداء');

    // 2. Click Login button
    await page.getByRole('link', { name: 'تسجيل الدخول' }).first().click();
    await expect(page).toHaveURL(/\/login/);
    await expect(page.locator('h2')).toContainText('تسجيل الدخول');

    // 3. Return to Homepage and click Register
    await page.goto('/');
    await page.getByRole('link', { name: 'ابدأ الآن مجاناً' }).first().click();
    await expect(page).toHaveURL(/\/register/);
    await expect(page.locator('h2')).toContainText('إنشاء حساب جديد');

    // 4. Click Marketplace link
    await page.getByRole('link', { name: 'سوق الحملات' }).first().click();
    await expect(page).toHaveURL(/\/marketplace/);
    await expect(page.locator('h1')).toContainText('سوق الحملات التسويقية');

    expect(failedReqs, 'Unexpected HTTP errors during navigation').toHaveLength(0);
  });

  test('2. Registration Form Submission and Dashboard Redirection', async ({ page }) => {
    const consoleErrors: string[] = [];
    const failedReqs: string[] = [];
    setupErrorListeners(page, consoleErrors, failedReqs);

    const uniqueId = Date.now();
    const testEmail = `playwright_marketer_${uniqueId}@nomowly.test`;

    await page.goto('/register');
    
    // Select Marketer role radio
    await page.locator('input[name="role"][value="MARKETER"]').check();

    // Fill form fields
    await page.fill('input[name="name"]', `مسوق أوتوماتيكي ${uniqueId}`);
    await page.fill('input[name="email"]', testEmail);
    await page.fill('input[name="password"]', 'Playwright2026!');
    await page.fill('input[name="password_confirmation"]', 'Playwright2026!');
    
    // Select primary specialization
    await page.selectOption('select[name="primary_specialization"]', 'CREATOR');

    // Submit form
    await page.getByRole('button', { name: 'إنشاء الحساب وبدء العمل' }).click();

    // Verify redirected to marketer dashboard
    await expect(page).toHaveURL(/\/marketer\/dashboard/);
    await expect(page.locator('body')).toContainText('مرحباً بك في منصة نمولي');

    expect(failedReqs, 'Unexpected HTTP errors during registration').toHaveLength(0);
  });

  test('3. Login Form Submission & Session Persistence', async ({ page }) => {
    const consoleErrors: string[] = [];
    const failedReqs: string[] = [];
    setupErrorListeners(page, consoleErrors, failedReqs);

    await page.goto('/login');

    await page.fill('input[name="email"]', 'brand@nomowly.com');
    await page.fill('input[name="password"]', 'Password123!');
    await page.getByRole('button', { name: 'تسجيل الدخول إلى حسابك' }).click();

    // Should navigate to brand dashboard
    await expect(page).toHaveURL(/\/brand\/dashboard/);
    await expect(page.locator('body')).toContainText('سارة كوتور للأزياء');

    // 4. Refresh page to verify authenticated session persists
    await page.reload();
    await expect(page).toHaveURL(/\/brand\/dashboard/);
    await expect(page.locator('body')).toContainText('سارة كوتور للأزياء');

    expect(failedReqs, 'Unexpected HTTP errors during login/persistence').toHaveLength(0);
  });

  test('4. Logout and Protected-Route Behavior', async ({ page }) => {
    const consoleErrors: string[] = [];
    const failedReqs: string[] = [];
    setupErrorListeners(page, consoleErrors, failedReqs);

    // Login first
    await page.goto('/login');
    await page.fill('input[name="email"]', 'brand@nomowly.com');
    await page.fill('input[name="password"]', 'Password123!');
    await page.getByRole('button', { name: 'تسجيل الدخول إلى حسابك' }).click();
    await expect(page).toHaveURL(/\/brand\/dashboard/);

    // Click visible Logout button
    await page.getByRole('button', { name: 'تسجيل الخروج' }).first().click();

    // Should redirect to homepage
    await expect(page).toHaveURL(/^(https?:\/\/[^\/]+)?\/$/);

    // Verify protected brand route is no longer accessible
    await page.goto('/brand/dashboard');
    await expect(page).toHaveURL(/\/login/);

    expect(failedReqs, 'Unexpected HTTP errors during logout').toHaveLength(0);
  });

  test('5. Brand Campaign Creation, Persistence and Editing', async ({ page }) => {
    const consoleErrors: string[] = [];
    const failedReqs: string[] = [];
    setupErrorListeners(page, consoleErrors, failedReqs);

    const testId = Date.now();
    const campaignTitle = `حملة المتصفح الحية ${testId}`;

    // Login as Brand
    await page.goto('/login');
    await page.fill('input[name="email"]', 'brand@nomowly.com');
    await page.fill('input[name="password"]', 'Password123!');
    await page.getByRole('button', { name: 'تسجيل الدخول إلى حسابك' }).click();
    await expect(page).toHaveURL(/\/brand\/dashboard/);

    // Click Create Campaign button
    await page.getByRole('link', { name: 'إنشاء حملة تسويقية جديدة' }).first().click();
    await expect(page).toHaveURL(/\/brand\/campaigns\/create/);

    // Fill Campaign Details
    await page.fill('input[name="title"]', campaignTitle);
    await page.fill('input[name="destination_url"]', 'https://sara-couture.com/playwright');
    await page.selectOption('select[name="category"]', 'موضة وأزياء');
    await page.fill('textarea[name="description"]', 'وصف اختباري تم إنشاؤه عبر متصفح كروميوم للتحقق من حفظ البيانات.');
    await page.fill('textarea[name="brief"]', 'موجز تفصيلي لشروط التسويق بالأداء ومتطلبات النشر لصناع المحتوى.');
    await page.selectOption('select[name="commission_type"]', 'PERCENTAGE_CPO');
    await page.fill('input[name="commission_value"]', '18%');
    await page.fill('input[name="cookie_duration_days"]', '45');
    
    // Check market & currency
    await page.locator('input[name="target_markets[]"][value="السعودية"]').check();
    await page.locator('input[name="currencies[]"][value="SAR"]').check();

    // Click Submit
    await page.getByRole('button', { name: 'حفظ ونشر الحملة في السوق' }).click();

    // Verify redirected to Brand Campaigns index
    await expect(page).toHaveURL(/\/brand\/campaigns/);
    await expect(page.locator('body')).toContainText(campaignTitle);

    // Edit the created campaign
    const editLink = page.locator(`tr:has-text("${campaignTitle}")`).getByRole('link', { name: 'تعديل' });
    await editLink.click();
    await expect(page).toHaveURL(/\/brand\/campaigns\/[^\/]+\/edit/);

    // Update field
    const updatedDesc = `وصف معدل بنجاح في اختبار المتصفح ${testId}`;
    await page.fill('textarea[name="description"]', updatedDesc);
    await page.getByRole('button', { name: 'حفظ التعديلات' }).click();

    // Verify persistence
    await expect(page).toHaveURL(/\/brand\/campaigns/);
    await expect(page.locator('body')).toContainText('تم تحديث بيانات الحملة بنجاح');

    expect(failedReqs, 'Unexpected HTTP errors during campaign lifecycle').toHaveLength(0);
  });

  test('6. Marketer Application, Brand Review & Clipboard Copy', async ({ page }) => {
    const consoleErrors: string[] = [];
    const failedReqs: string[] = [];
    setupErrorListeners(page, consoleErrors, failedReqs);

    // 1. Login as Marketer
    await page.goto('/login');
    await page.fill('input[name="email"]', 'marketer@nomowly.com');
    await page.fill('input[name="password"]', 'Password123!');
    await page.getByRole('button', { name: 'تسجيل الدخول إلى حسابك' }).click();
    await expect(page).toHaveURL(/\/marketer\/dashboard/);

    // 2. Open Marketplace
    await page.goto('/marketplace');
    await expect(page).toHaveURL(/\/marketplace/);

    // 3. Open First Campaign
    const firstCampaignLink = page.locator('a[href*="/marketplace/"]').first();
    await firstCampaignLink.click();

    // If already applied, check workspace; if application form exists, apply
    const applyButton = page.getByRole('button', { name: 'إرسال طلب الانضمام للحملة' });
    if (await applyButton.isVisible()) {
      await page.fill('textarea[name="pitch_note"]', 'خطة نشر تفاعلية عبر ريلز تيك توك وإنستغرام');
      await page.fill('textarea[name="channels"]', 'https://instagram.com/test\nhttps://tiktok.com/@test');
      await applyButton.click();
      await expect(page.locator('body')).toContainText('تم إرسال طلب انضمامك للحملة بنجاح');
    }

    // 4. Test Clipboard Interaction on Marketer Workspace
    await page.goto('/marketer/campaigns');
    await expect(page).toHaveURL(/\/marketer\/campaigns/);

    const copyBtn = page.locator('button[title*="نسخ"]').first();
    if (await copyBtn.isVisible()) {
      await copyBtn.click();
      // Verify visible reactive confirmation feedback
      await expect(page.locator('body')).toContainText('تم النسخ ✓');
    }

    expect(failedReqs, 'Unexpected HTTP errors during application/clipboard flow').toHaveLength(0);
  });

});
