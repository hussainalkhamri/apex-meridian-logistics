<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RolesAndUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_locale_switch_updates_translated_content(): void
    {
        $response = $this->withSession(['locale' => 'ar'])->get('/language/en');

        $response->assertRedirect();
        $this->assertSame('en', session('locale'));

        $homeResponse = $this->withSession(['locale' => 'en'])->get('/');

        $homeResponse->assertStatus(200);
        $homeResponse->assertSeeText('Home');
        $homeResponse->assertDontSeeText('الرئيسية');
    }

    public function test_locale_switch_removes_stale_locale_query_from_redirect(): void
    {
        $response = $this
            ->withSession(['locale' => 'ar'])
            ->get('/language/en?redirect_to=' . urlencode('/?locale=ar&foo=1'));

        $response->assertRedirect();
        $this->assertSame('en', session('locale'));
        $this->assertStringNotContainsString('locale=ar', $response->headers->get('Location', ''));
        $this->assertStringContainsString('foo=1', $response->headers->get('Location', ''));
    }

    public function test_staff_login_respects_arabic_locale(): void
    {
        $response = $this->withSession(['locale' => 'ar'])->get('/login');

        $response->assertStatus(200);
        $response->assertSee('dir="rtl"', false);
        $response->assertSee('id="password-toggle"', false);
        $response->assertSee('data-lucide="eye"', false);
        $response->assertSee('data-lucide="eye-off"', false);
        $response->assertSeeText('تسجيل الدخول');
        $response->assertDontSeeText('Sign In');
    }

    public function test_staff_login_respects_english_locale(): void
    {
        $response = $this->withSession(['locale' => 'en'])->get('/login');

        $response->assertStatus(200);
        $response->assertSee('dir="ltr"', false);
        $response->assertSee('id="password-toggle"', false);
        $response->assertSee('data-lucide="eye"', false);
        $response->assertSee('data-lucide="eye-off"', false);
        $response->assertSee('Show password', false);
        $response->assertSeeText('Staff Login');
        $response->assertDontSeeText('تسجيل الدخول');
    }

    public function test_hardcoded_public_copy_is_translated_in_english_locale(): void
    {
        $response = $this->withSession(['locale' => 'en'])->get('/');

        $response->assertStatus(200);
        $response->assertSeeText('International shipping, customs clearance, and full operations from one platform');
        $response->assertDontSeeText('شحن دولي، تخليص جمركي، وإدارة عمليات من منصة واحدة');
    }

    public function test_hardcoded_admin_copy_is_translated_in_english_locale(): void
    {
        $this->seed(RolesAndUsersSeeder::class);

        $admin = User::where('username', 'admin')->firstOrFail();

        $response = $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get('/customers');

        $response->assertStatus(200);
        $response->assertSeeText('Customer Management');
        $response->assertDontSeeText('إدارة العملاء');
    }

    public function test_core_admin_pages_show_english_headings_when_english_locale_is_active(): void
    {
        $this->seed(RolesAndUsersSeeder::class);

        $admin = User::where('username', 'admin')->firstOrFail();

        $pages = [
            '/orders' => 'Manage Orders',
            '/pricing' => 'Pricing and Quotations',
            '/sales' => 'Deals & Quotes Management',
            '/bookings' => 'Carrier booking requests',
            '/accounts' => 'Invoices & payments for shipping operations',
            '/ports' => 'Global Port Management',
            '/users' => 'User Management',
            '/roles' => 'Roles & Permissions',
            '/backups' => 'Backup & Restore',
        ];

        foreach ($pages as $uri => $heading) {
            $response = $this->actingAs($admin)
                ->withSession(['locale' => 'en'])
                ->get($uri);

            $response->assertStatus(200);
            $response->assertSeeText($heading);
        }

        $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get('/pricing')
            ->assertStatus(200)
            ->assertSeeText('Agent')
            ->assertDontSeeText('messages.pricing_agent_label');
    }

    public function test_roles_super_admin_notice_is_translated_in_english_locale(): void
    {
        $this->seed(RolesAndUsersSeeder::class);

        $admin = User::where('username', 'admin')->firstOrFail();

        $response = $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get('/roles');

        $response->assertStatus(200);
        $response->assertSeeText('The system administrator automatically has all permissions and cannot be restricted.');
        $response->assertSeeText('Manage roles');
        $response->assertDontSeeText('مدير النظام يملك جميع الصلاحيات تلقائياً ولا يمكن تقييده.');
        $response->assertDontSeeText('إدارة الأدوار');
    }

    public function test_dashboard_audit_logout_label_is_translated_in_english_locale(): void
    {
        $this->seed(RolesAndUsersSeeder::class);

        $admin = User::where('username', 'admin')->firstOrFail();

        AuditLog::create([
            'user_id' => $admin->id,
            'action_type' => 'LOGOUT',
            'table_name' => 'users',
            'record_id' => (string) $admin->id,
            'ip_address' => '127.0.0.1',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSeeText('Logout: 1');
        $response->assertDontSeeText('تسجيل خروج');
    }

    public function test_dashboard_default_alert_copy_is_translated_in_english_locale(): void
    {
        $this->seed(RolesAndUsersSeeder::class);

        $admin = User::where('username', 'admin')->firstOrFail();

        $response = $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSeeText('All operations are running according to the planned schedule.');
        $response->assertDontSeeText('جميع العمليات تسير وفق الجدول المحدد.');
    }

    public function test_accounts_agent_empty_state_is_translated_in_english_locale(): void
    {
        $this->seed(RolesAndUsersSeeder::class);

        $admin = User::where('username', 'admin')->firstOrFail();

        $response = $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get('/accounts');

        $response->assertStatus(200);
        $response->assertSeeText('0 agent-linked requests');
        $response->assertSeeText('No agent transactions yet. Priced requests with an agent purchase price will appear here.');
        $response->assertDontSeeText('طلب مرتبط بوكيل');
        $response->assertDontSeeText('لا توجد تعاملات وكلاء بعد');
    }

    public function test_static_extra_copy_is_translated_on_english_admin_pages(): void
    {
        $this->seed(RolesAndUsersSeeder::class);

        $admin = User::where('username', 'admin')->firstOrFail();

        $pages = [
            '/sales' => ['Deals & Quotes Management', 'قسم المبيعات'],
            '/customs-clearance' => ['Track clearance files, prepare declarations, review documents, and calculate estimated duties from the same operations screen.', 'متابعة ملفات التخليص، تجهيز الإقرارات، مراجعة المستندات، وحساب الرسوم التقديرية من نفس شاشة العمليات.'],
            '/backups' => ['Safe merge', 'دمج آمن'],
        ];

        foreach ($pages as $uri => [$englishText, $arabicText]) {
            $response = $this->actingAs($admin)
                ->withSession(['locale' => 'en'])
                ->get($uri);

            $response->assertStatus(200);
            $response->assertSeeText($englishText);
            $response->assertDontSeeText($arabicText);
        }
    }

    public function test_customs_workflow_copy_is_translated_in_english_locale(): void
    {
        $this->seed(RolesAndUsersSeeder::class);

        $admin = User::where('username', 'admin')->firstOrFail();

        $response = $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get('/customs-clearance');

        $response->assertStatus(200);

        foreach ([
            'Track clearance files, prepare declarations, review documents, and calculate estimated duties from the same operations screen.',
            'File Received',
            'Match the invoice, packing list, bill of lading, and authorization.',
            'Inspection and review',
            'Track the port, bayan, and customs declaration number.',
            'Calculate Duties',
            'Estimate duties based on value and HS codes.',
            'Release',
            'Update the status and release date after procedures are complete.',
        ] as $englishText) {
            $response->assertSeeText($englishText);
        }

        foreach ([
            'متابعة ملفات التخليص، تجهيز الإقرارات، مراجعة المستندات، وحساب الرسوم التقديرية من نفس شاشة العمليات.',
            'مطابقة الفاتورة، التعبئة، البوليصة والتفويض.',
            'متابعة المنفذ، البيان، ورقم الإقرار الجمركي.',
            'تقدير الرسوم حسب القيمة والبنود التعريفية.',
            'تحديث الحالة وتاريخ الإفراج بعد اكتمال الإجراءات.',
        ] as $arabicText) {
            $response->assertDontSeeText($arabicText);
        }
    }

    public function test_backup_page_copy_and_file_picker_are_translated_in_english_locale(): void
    {
        $this->seed(RolesAndUsersSeeder::class);

        $admin = User::where('username', 'admin')->firstOrFail();

        $response = $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get('/backups');

        $response->assertStatus(200);

        foreach ([
            'Export backup',
            'Download a JSON file containing all core system data. Store it off this device or on an external drive or secure cloud storage.',
            'The backup includes users, customers, orders, pricing, invoices, vouchers, tracking, schedules, agents, ports, and notifications.',
            'Download backup now',
            'Import Backup',
            'Use a backup file exported from this system. Merge mode adds and updates rows without deleting existing data; full replacement clears tables and imports the file.',
            'Backup file *',
            'Choose File',
            'No file chosen',
            'Safe merge',
            'Updates existing records and only adds missing ones.',
            'Full Replacement',
            'Deletes the current data and restores the file.',
        ] as $englishText) {
            $response->assertSeeText($englishText);
        }

        foreach ([
            'نزّل ملف JSON يحتوي كل بيانات النظام الأساسية. احفظه خارج الجهاز أو في قرص خارجي/سحابة آمنة.',
            'النسخة تشمل: المستخدمين، العملاء، الطلبات، التسعير، الفواتير، السندات، التتبع، الجداول، الوكلاء، الموانئ، والتنبيهات.',
            'لم يتم اختيار أي ملف',
            'يحدث السجلات الموجودة ويضيف المفقود فقط.',
            'يمسح البيانات الحالية ويستعيد الملف.',
        ] as $arabicText) {
            $response->assertDontSeeText($arabicText);
        }

        $response->assertDontSeeText('notifications..');
        $response->assertDontSeeText('the file..');
    }
}
