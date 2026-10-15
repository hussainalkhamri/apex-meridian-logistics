<?php

namespace App\Support;

class BookingStatus
{
    public const GROUPS = [
        'waiting' => ['new', 'needs_pricing', 'priced'],
        'offers' => ['priced', 'sent'],
        'active' => ['customer_approved', 'approved', 'booked'],
        'closed' => ['customer_rejected', 'rejected'],
        'customs' => ['pending', 'documents_review', 'inspection', 'duties', 'cleared', 'on_hold'],
    ];

    public static function labels(): array
    {
        return [
            'new' => ['label' => 'بانتظار التسعير', 'class' => 'bg-warning/20 text-warning-foreground border-warning/30'],
            'needs_pricing' => ['label' => 'إعادة تسعير مطلوبة', 'class' => 'bg-warning/20 text-warning-foreground border-warning/30'],
            'priced' => ['label' => 'بانتظار تأكيدك', 'class' => 'bg-accent/15 text-accent-foreground border-accent/20'],
            'sent' => ['label' => 'بانتظار قرارك', 'class' => 'bg-accent/15 text-accent-foreground border-accent/20'],
            'customer_approved' => ['label' => 'وافقت على العرض', 'class' => 'bg-success/15 text-success border-success/25'],
            'customer_rejected' => ['label' => 'رفضت العرض', 'class' => 'bg-destructive/15 text-destructive border-destructive/20'],
            'approved' => ['label' => 'معتمد', 'class' => 'bg-success/15 text-success border-success/25'],
            'booked' => ['label' => 'تم حجز الناقل', 'class' => 'bg-success/15 text-success border-success/25'],
            'rejected' => ['label' => 'مرفوض', 'class' => 'bg-destructive/15 text-destructive border-destructive/20'],
            'cleared' => ['label' => 'تم التخليص', 'class' => 'bg-success/15 text-success border-success/25'],
        ];
    }

    public static function customsLabels(): array
    {
        return [
            'pending' => 'بانتظار فتح الملف',
            'documents_review' => 'مراجعة المستندات',
            'inspection' => 'الفحص والمعاينة',
            'duties' => 'الرسوم والبيان',
            'cleared' => 'تم التخليص',
            'on_hold' => 'متوقف مؤقتا',
        ];
    }

    public static function optionGroups(): array
    {
        return [
            'all' => 'كل الشحنات',
            'waiting' => 'قيد التسعير',
            'offers' => 'عروض تنتظر قرارك',
            'active' => 'قيد التنفيذ',
            'customs' => 'ملفات تخليص',
            'closed' => 'مغلقة أو مرفوضة',
        ];
    }

    public static function resolve(?string $status): array
    {
        $status = (string) $status;

        return self::labels()[$status] ?? [
            'label' => $status !== '' ? $status : 'غير محدد',
            'class' => 'bg-secondary text-foreground border-border',
        ];
    }
}
