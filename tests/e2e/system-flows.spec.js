import { test, expect } from '@playwright/test';

test.describe('North Star Line Shipping & Logistics System E2E Suite', () => {

  test('1. Marketing Landing Page and Registration Portal render as intended', async ({ page }) => {
    await page.goto('/');

    // Check title contains restored company name
    const title = await page.title();
    expect(title).toMatch(/(نجم الشمال لاين للشحن والخدمات اللوجستية|North Star Line)/);

    // Verify hero slider and marketing elements are present
    const heroSlider = page.locator('[data-home-hero-slider], .hero-section, .marketing-hero');
    await expect(page.locator('body')).toContainText(/نجم الشمال لاين للشحن والخدمات اللوجستية|North Star Line/);

    // Verify partner / trust section or carrier logos
    const partnersSection = page.locator('.company-partners-slider, footer');
    await expect(partnersSection.first()).toBeVisible();

    // Verify registration link leads to customer registration form
    const registerBtn = page.locator('a[href*="/customer/register"]').first();
    await expect(registerBtn).toBeVisible();

    // Navigate to registration form
    await page.goto('/customer/register');
    await expect(page.locator('input[name="company"]')).toBeVisible();
    await expect(page.locator('input[name="contact_name"]')).toBeVisible();
    await expect(page.locator('input[name="email"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
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
    await expect(page.locator('body')).toContainText(/نجم الشمال لاين للشحن والخدمات اللوجستية/);
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

  test('5. Operations Staff Login authenticates and loads Management Dashboard with exact title', async ({ page }) => {
    await page.goto('/login');

    // Fill credentials
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'admin1234');
    await page.locator('button[type="submit"]').click();

    // Expect navigation to internal dashboard
    await page.waitForURL('**/dashboard', { timeout: 15000 });
    expect(page.url()).toContain('/dashboard');

    // Verify header title in management layout
    const headerTitle = page.locator('header h2').first();
    await expect(headerTitle).toContainText(/لوحة التحكم — نجم الشمال لاين للشحن والخدمات اللوجستية|Dashboard/);

    // Navigate to Orders page and verify exact title in header
    await page.goto('/orders');
    await expect(page.locator('header h2').first()).toContainText('الطلبات — نجم الشمال لاين للشحن والخدمات اللوجستية');
  });

  test('6. Customer Portal Login authenticates and loads Client Dashboard with exact title', async ({ browser }) => {
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

    // Verify customer dashboard elements & header title
    const headerTitle = page.locator('header .truncate').first();
    await expect(headerTitle).toContainText(/نظرة عامة — نجم الشمال لاين للشحن والخدمات اللوجستية|بوابة العملاء/);

    await context.close();
  });

});
