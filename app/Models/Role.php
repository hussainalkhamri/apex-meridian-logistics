<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    public static array $labels = [
        'super_admin' => 'مدير النظام',
        'ops_manager' => 'مدير العمليات',
        'pricing' => 'موظف تسعير',
        'sales' => 'موظف مبيعات',
        'accounts' => 'محاسب',
        'viewer' => 'مشاهد فقط',
    ];

    public static array $allPermissions = [
        'manage_users' => 'إدارة المستخدمين',
        'manage_roles' => 'إدارة الأدوار',
        'manage_bookings' => 'إدارة الطلبات',
        'manage_customers' => 'إدارة العملاء',
        'manage_ports' => 'إدارة الموانئ',
        'manage_agents' => 'إدارة الوكلاء',
        'manage_customs' => 'التخليص الجمركي',
        'manage_pricing' => 'التسعير',
        'manage_shipping_rates' => 'إدارة أسعار الشحن',
        'view_shipping_rates_operations' => 'أسعار الشحن',
        'manage_sales' => 'المبيعات',
        'manage_carriers' => 'حجوزات الناقلين',
        'manage_accounts' => 'الحسابات والفواتير',
        'view_dashboard' => 'لوحة التحكم',
        'view_targets' => 'مشاهدة هدف الاعتمادات',
        'view_reports' => 'التقارير والإحصاء',
        'view_audit_logs' => 'سجلات التدقيق',
        'manage_insights' => 'الرؤى الذكية',
        'manage_backups' => 'النسخ الاحتياطي للبيانات',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? [], true);
    }

    public static function validPermissionKeys(): array
    {
        return array_keys(self::$allPermissions);
    }

    public static function normalizePermissions(?array $permissions): array
    {
        if (! $permissions) {
            return [];
        }

        $validPermissions = array_flip(self::validPermissionKeys());

        return array_values(array_unique(array_filter(
            $permissions,
            fn ($permission) => is_string($permission) && isset($validPermissions[$permission])
        )));
    }

    public function isSuperAdmin(): bool
    {
        return $this->name === 'super_admin';
    }

    public function getDisplayName(): string
    {
        return self::displayNameFor($this->name);
    }

    public static function displayNameFor(string $roleName): string
    {
        $key = "messages.role_label_{$roleName}";
        $translated = __($key);

        return $translated !== $key ? $translated : (self::$labels[$roleName] ?? $roleName);
    }

    public static function permissionLabelFor(string $permission): string
    {
        $key = "messages.permission_label_{$permission}";
        $translated = __($key);

        return $translated !== $key ? $translated : (self::$allPermissions[$permission] ?? $permission);
    }
}
