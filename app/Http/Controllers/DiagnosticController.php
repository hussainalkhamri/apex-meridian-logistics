<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DiagnosticController extends Controller
{
    public function index(Request $request)
    {
        $isAuthenticated = $this->checkAuth($request);

        if (!$isAuthenticated) {
            return view('pages.diagnose-login', ['error' => $request->has('pin') ? 'رمز الدخول غير صحيح' : null]);
        }

        $checks = [];

        // 1. PHP Version
        $checks['php_version'] = [
            'label' => 'إصدار PHP / PHP Version',
            'status' => version_compare(PHP_VERSION, '8.3.0', '>=') ? 'success' : 'danger',
            'value' => PHP_VERSION,
            'message' => version_compare(PHP_VERSION, '8.3.0', '>=') ? 'مطابق لمتطلبات النظام (>= 8.3)' : 'مطلوب ترقية إصدار PHP إلى 8.3 أو أحدث'
        ];

        // 2. Storage Writable
        $storagePath = storage_path();
        $isStorageWritable = is_writable($storagePath);
        $checks['storage_writable'] = [
            'label' => 'صلاحية الكتابة لمجلد التخزين / Storage Writable',
            'status' => $isStorageWritable ? 'success' : 'danger',
            'value' => $isStorageWritable ? 'Writable (775/755)' : 'Not Writable',
            'message' => $isStorageWritable ? 'مجلد storage متاح للكتابة بشكل صحيح' : 'يرجى تغيير صلاحيات مجلد storage والملفات داخله إلى 775 أو 755'
        ];

        // 3. Cache Writable
        $cachePath = base_path('bootstrap/cache');
        $isCacheWritable = is_writable($cachePath);
        $checks['cache_writable'] = [
            'label' => 'صلاحية الكتابة لمجلد الكاش / Bootstrap Cache Writable',
            'status' => $isCacheWritable ? 'success' : 'danger',
            'value' => $isCacheWritable ? 'Writable (775/755)' : 'Not Writable',
            'message' => $isCacheWritable ? 'مجلد bootstrap/cache متاح للكتابة بشكل صحيح' : 'يرجى تغيير صلاحيات مجلد bootstrap/cache إلى 775 أو 755'
        ];

        // 4. Database Connection
        $dbConnected = false;
        $dbError = null;
        try {
            DB::connection()->getPdo();
            $dbConnected = true;
        } catch (\Exception $e) {
            $dbError = $e->getMessage();
        }

        $checks['db_connection'] = [
            'label' => 'الاتصال بقاعدة البيانات / Database Connection',
            'status' => $dbConnected ? 'success' : 'danger',
            'value' => $dbConnected ? 'Connected' : 'Failed',
            'message' => $dbConnected ? 'تم الاتصال بقاعدة البيانات بنجاح' : 'فشل الاتصال: ' . $dbError
        ];

        // 5. Check migrations / Tables status
        $rolesTableExists = false;
        $usersTableExists = false;
        $rolesCount = 0;
        $usersCount = 0;

        if ($dbConnected) {
            $rolesTableExists = Schema::hasTable('roles');
            $usersTableExists = Schema::hasTable('users');
            if ($rolesTableExists) $rolesCount = Role::count();
            if ($usersTableExists) $usersCount = User::count();
        }

        $checks['roles_table'] = [
            'label' => 'جدول الأدوار / Roles Table',
            'status' => ($dbConnected && $rolesTableExists) ? ($rolesCount > 0 ? 'success' : 'warning') : 'danger',
            'value' => ($dbConnected && $rolesTableExists) ? "Exists (Count: $rolesCount)" : 'Not Found',
            'message' => ($dbConnected && $rolesTableExists) ? ($rolesCount > 0 ? 'الجدول موجود والبيانات الأساسية مغذاة' : 'الجدول موجود ولكنه فارغ! يجب تشغيل الـ Seeder لتغذية الأدوار') : 'الجدول غير موجود، يرجى تشغيل المهاجرة (migrate)'
        ];

        $checks['users_table'] = [
            'label' => 'جدول المستخدمين / Users Table',
            'status' => ($dbConnected && $usersTableExists) ? ($usersCount > 0 ? 'success' : 'warning') : 'danger',
            'value' => ($dbConnected && $usersTableExists) ? "Exists (Count: $usersCount)" : 'Not Found',
            'message' => ($dbConnected && $usersTableExists) ? ($usersCount > 0 ? 'الجدول موجود ويحتوي على حسابات' : 'الجدول موجود ولكنه فارغ! يرجى تشغيل الـ Seeder لإضافة حساب الإدمن') : 'الجدول غير موجود، يرجى تشغيل المهاجرة (migrate)'
        ];

        // 6. Test User Creation E2E
        $userCreationTest = 'Not Attempted';
        $userCreationStatus = 'neutral';
        $userCreationError = null;

        if ($dbConnected && $rolesTableExists && $usersTableExists) {
            try {
                // Find a role to associate
                $role = Role::first();
                
                // Create a temporary test user
                $testUser = User::create([
                    'full_name' => 'فحص النظام التجريبي',
                    'username' => 'test_diag_' . Str::lower(Str::random(8)),
                    'phone' => '+96600000000',
                    'password' => 'diag_pass_123',
                    'role_id' => $role ? $role->id : null,
                    'is_active' => true
                ]);

                // Delete immediately
                $testUser->delete();

                $userCreationTest = 'Passed';
                $userCreationStatus = 'success';
            } catch (\Exception $e) {
                $userCreationTest = 'Failed';
                $userCreationStatus = 'danger';
                $userCreationError = $e->getMessage();
            }
        }

        $checks['user_creation'] = [
            'label' => 'فحص إضافة مستخدم / User Creation E2E Test',
            'status' => $userCreationStatus,
            'value' => $userCreationTest,
            'message' => $userCreationStatus === 'success' ? 'تم تجربة إضافة مستخدم وحذفه بنجاح (قاعدة البيانات تدعم الحفظ وبنية الـ Model سليمة)' : ($userCreationStatus === 'danger' ? 'فشلت الإضافة: ' . $userCreationError : 'لم يتم محاولة الفحص لعدم توفر الاتصال أو الجداول')
        ];

        // 7. Configuration Cache status
        // Check if cached .env values match actual config values
        $envDatabase = env('DB_DATABASE');
        $configDatabase = config('database.connections.mysql.database');
        $isConfigOutdated = ($envDatabase && $configDatabase && $envDatabase !== $configDatabase);

        $checks['config_cache'] = [
            'label' => 'حالة كاش الإعدادات / Configuration Cache Status',
            'status' => !$isConfigOutdated ? 'success' : 'danger',
            'value' => $isConfigOutdated ? 'Outdated / Mismatched' : 'Synced',
            'message' => !$isConfigOutdated ? 'كاش الإعدادات متطابق مع ملف البيئة الحالية' : 'ملف البيئة تم تعديله ولكن الكاش يقرأ إعدادات قديمة! يرجى مسح الكاش فوراً'
        ];

        return view('pages.diagnose', compact('checks', 'isAuthenticated'));
    }

    public function fix(Request $request)
    {
        if (!$this->checkAuth($request)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $action = $request->input('action');
        $output = '';

        try {
            switch ($action) {
                case 'clear_cache':
                    Artisan::call('config:clear');
                    Artisan::call('cache:clear');
                    Artisan::call('view:clear');
                    Artisan::call('route:clear');
                    $output = "تم مسح كاش الإعدادات، الجداول، والتوجيهات بنجاح.\n" . Artisan::output();
                    break;

                case 'run_migrations':
                    Artisan::call('migrate', ['--force' => true]);
                    $output = "تم تشغيل هجرات قاعدة البيانات بنجاح.\n" . Artisan::output();
                    break;

                case 'run_seeders':
                    Artisan::call('db:seed', ['--class' => 'RolesAndUsersSeeder', '--force' => true]);
                    $output = "تم تشغيل Seeder الأدوار والمستخدمين بنجاح.\n" . Artisan::output();
                    break;

                default:
                    return response()->json(['error' => 'Invalid Action'], 422);
            }

            return response()->json(['success' => true, 'output' => $output]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function checkAuth(Request $request)
    {
        $user = auth('web')->user();
        if ($user?->isSuperAdmin()) {
            return true;
        }

        if (! config('diagnostic.allow_pin_access')) {
            return false;
        }

        // Check if session has pin verified
        if ($request->session()->get('diagnose_authenticated') === true) {
            return true;
        }

        $diagnosticPin = config('diagnostic.pin');
        if (! is_string($diagnosticPin) || $diagnosticPin === '') {
            return false;
        }

        // Check pin from request
        $pin = $request->input('pin');
        if (hash_equals($diagnosticPin, (string) $pin)) {
            $request->session()->put('diagnose_authenticated', true);
            return true;
        }

        return false;
    }
}
