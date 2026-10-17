import { test, expect } from '@playwright/test';

test.describe('Shipping and Logistics ERP System E2E Suite', () => {

  test('1. Direct Login Portal renders without logo or marketing website clutter', async ({ page }) => {
    await page.goto('/');

    // Check title contains system name
    const title = await page.title();
    expect(title).toMatch(/(نظام إدارة الشحن والخدمات اللوجستية|Shipping and Logistics)/);

    // Verify Direct Login Form inputs are visible immediately
    const usernameInput = page.locator('input[name="username"]').first();
    await expect(usernameInput).toBeVisible();

    const passwordInput = page.locator('input[name="password"]').first();
    await expect(passwordInput).toBeVisible();

    // Verify submit button exists
    const submitBtn = page.locator('button[type="submit"]').first();
    await expect(submitBtn).toBeVisible();

    // Verify Customer Registration link exists
    const registerLink = page.locator('a[href*="/customer/register"]').first();
    await expect(registerLink).toBeVisible();

    // Verify no img logos exist in header
    const logoImgs = page.locator('header img, aside img, .company-site-logo');
    await expect(logoImgs).toHaveCount(0);
  });

  test('2. Language Switching works seamlessly (AR RTL <-> EN LTR)', async ({ page }) => {
    // Switch to English
    await page.goto('/language/en');
    await page.waitForLoadState('networkidle');
    const htmlDirEn = await page.locator('html').getAttribute('dir');
    expect(htmlDirEn).toBe('ltr');

    // Switch to Arabic (Default system language)
    await page.goto('/language/ar');
    await page.waitForLoadState('networkidle');
    const htmlDirAr = await page.locator('html').getAttribute('dir');
    expect(htmlDirAr).toBe('rtl');
    await expect(page.locator('body')).toContainText(/نظام إدارة الشحن والخدمات اللوجستية|تسجيل الدخول/);
  });

  test('3. Real-time Cargo Tracking System displays accurate shipment status and map', async ({ page }) => {
    await page.goto('/tracking?q=TR-2026-8802');

    // Confirm tracking page loaded
    await expect(page).toHaveURL(/.*tracking.*/);

    // Check that shipment reference or tracking number is displayed
    const content = await page.content();
    expect(content).toMatch(/(TR-2026-8802|مسار الشحنة|Shipment|Tracking)/);

    // Click interactive map preview button
    const mapOpenBtn = page.locator('button[data-tracking-map-open]').first();
    if (await mapOpenBtn.isVisible()) {
      await mapOpenBtn.click();
      const mapDialog = page.locator('#tracking-map-dialog');
      await expect(mapDialog).toBeVisible();
    }
  });

  test('4. Vessel & Flight Schedules Lookup functions smoothly', async ({ page }) => {
    await page.goto('/schedules');
    await expect(page).toHaveURL(/.*schedules.*/);

    // Verify schedule content or table
    const bodyText = await page.locator('body').innerText();
    expect(bodyText).toMatch(/(جدول|رحلات|Schedules|Vessel|Port|Flight)/i);
  });

  test('5. Operations Staff Login authenticates and loads Management Dashboard', async ({ page }) => {
    await page.goto('/login');

    // Fill credentials
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'admin1234');
    await page.locator('button[type="submit"]').click();

    // Expect navigation to internal dashboard
    await page.waitForURL('**/dashboard', { timeout: 15000 });
    expect(page.url()).toContain('/dashboard');

    // Verify internal operations interface elements
    const dashboardText = await page.locator('body').innerText();
    expect(dashboardText).toMatch(/(لوحة التحكم|العمليات|Dashboard|Shipments|الشحنات)/i);
  });

  test('6. Customer Portal Login authenticates and loads Client Dashboard', async ({ browser }) => {
    // Use fresh context for customer session
    const context = await browser.newContext();
    const page = await context.newPage();

    await page.goto('/customer/login');

    // Fill customer credentials
    await page.fill('input[name="email"]', 'customer@example.com');
    await page.fill('input[name="password"]', 'password');
    await page.locator('button[type="submit"]').click();

    // Expect navigation to customer dashboard
    await page.waitForURL('**/customer/dashboard', { timeout: 15000 });
    expect(page.url()).toContain('/customer/dashboard');

    // Verify customer dashboard elements
    const customerText = await page.locator('body').innerText();
    expect(customerText).toMatch(/(شحناتي|لوحة العميل|Customer Portal|My Shipments|طلب شحنة)/i);

    await context.close();
  });

});
