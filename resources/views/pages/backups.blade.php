@extends('layouts.app')

@section('title', 'النسخ الاحتياطي للبيانات — {{ config('company.name.ar') }}')

@section('content')
<div>
    <section class="bg-hero py-14 text-white">
        <div class="container mx-auto px-4">
            <span class="inline-flex items-center rounded-full border border-white/20 bg-white/15 px-3 py-1 text-xs">حماية البيانات</span>
            <h1 class="mt-3 text-3xl font-extrabold md:text-4xl">النسخ الاحتياطي والاستعادة</h1>
            <p class="mt-2 max-w-2xl text-white/80">
                صدّر نسخة كاملة من بيانات النظام واستوردها عند الحاجة لاستعادة الطلبات، العملاء، الفواتير، السندات، التتبع، والصلاحيات.
            </p>
        </div>
    </section>

    <section class="container mx-auto px-4 py-10">
        @php
            $backupPreview = session('backup_preview');
            $backupPreviewActionLabels = [
                'merge' => 'دمج وتحديث',
                'replace' => 'استبدال',
                'preserved' => 'محمي ولن يتغير',
                'missing' => 'غير موجود في الملف',
            ];
        @endphp

        @if($errors->any())
            <div class="mb-6 rounded-xl border border-destructive/30 bg-destructive/10 p-4 text-sm">
                <div class="mb-2 flex items-center gap-2 font-black text-destructive">
                    <i data-lucide="x-circle" class="h-5 w-5"></i>
                    <span>تعذر تنفيذ العملية</span>
                </div>
                <ul class="list-disc pr-5 text-xs leading-6">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($backupPreview)
            <div class="mb-6 overflow-hidden rounded-xl border border-accent/25 bg-card shadow-card">
                <div class="flex flex-col gap-2 border-b border-border bg-accent/5 p-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-sm font-black text-primary">نتيجة فحص النسخة قبل الاستيراد</h2>
                        <p class="mt-1 text-xs text-muted-foreground">
                            الوضع: {{ $backupPreview['mode'] === 'replace' ? 'استبدال كامل' : 'دمج آمن' }}
                            @if(!empty($backupPreview['exported_at']))
                                · تاريخ التصدير: {{ $backupPreview['exported_at'] }}
                            @endif
                        </p>
                    </div>
                    @if(!empty($backupPreview['preserve_foundation_data']))
                        <span class="inline-flex items-center gap-1 rounded-full bg-success/10 px-3 py-1 text-[11px] font-black text-success">
                            <i data-lucide="shield-check" class="h-3.5 w-3.5"></i>
                            الموظفون والموانئ والمطارات محمية
                        </span>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[720px] text-right text-xs">
                        <thead class="bg-secondary/40 text-muted-foreground">
                            <tr>
                                <th class="p-3">الجدول</th>
                                <th class="p-3">الموجود حاليًا</th>
                                <th class="p-3">داخل الملف</th>
                                <th class="p-3">ما سيحدث</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @foreach($backupPreview['tables'] as $row)
                                <tr>
                                    <td class="p-3 font-mono font-bold text-foreground">{{ $row['table'] }}</td>
                                    <td class="p-3">{{ number_format($row['current']) }}</td>
                                    <td class="p-3">{{ number_format($row['incoming']) }}</td>
                                    <td class="p-3">
                                        <span class="inline-flex rounded-full bg-secondary px-2 py-0.5 font-black text-foreground">
                                            {{ $backupPreviewActionLabels[$row['action']] ?? $row['action'] }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-xl border border-border bg-card p-5 shadow-card">
                <span class="grid h-11 w-11 place-items-center rounded-lg bg-accent/10 text-accent">
                    <i data-lucide="database" class="h-5 w-5"></i>
                </span>
                <div class="mt-3 text-2xl font-black">{{ $tableStats->sum('count') }}</div>
                <div class="text-xs font-bold text-muted-foreground">إجمالي السجلات المحمية</div>
            </div>
            <div class="rounded-xl border border-border bg-card p-5 shadow-card">
                <span class="grid h-11 w-11 place-items-center rounded-lg bg-primary/10 text-primary">
                    <i data-lucide="table-properties" class="h-5 w-5"></i>
                </span>
                <div class="mt-3 text-2xl font-black">{{ $tableStats->count() }}</div>
                <div class="text-xs font-bold text-muted-foreground">جداول ضمن النسخة</div>
            </div>
            <div class="rounded-xl border border-border bg-card p-5 shadow-card">
                <span class="grid h-11 w-11 place-items-center rounded-lg bg-warning/15 text-warning-foreground">
                    <i data-lucide="shield-alert" class="h-5 w-5"></i>
                </span>
                <div class="mt-3 text-sm font-black">الاستبدال الكامل يحتاج تأكيد</div>
                <div class="mt-1 text-xs text-muted-foreground">لمنع حذف البيانات بالخطأ.</div>
            </div>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <div class="rounded-xl border border-border bg-card p-6 shadow-card">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="flex items-center gap-2 text-lg font-black text-primary">
                            <i data-lucide="download-cloud" class="h-5 w-5 text-accent"></i>
                            <span>تصدير نسخة احتياطية</span>
                        </h2>
                        <p class="mt-2 text-xs leading-6 text-muted-foreground">
                            نزّل ملف JSON يحتوي كل بيانات النظام الأساسية. احفظه خارج الجهاز أو في قرص خارجي/سحابة آمنة.
                        </p>
                    </div>
                </div>

                <div class="mt-5 rounded-lg border border-dashed border-border bg-secondary/30 p-4 text-xs leading-6">
                    النسخة تشمل: المستخدمين، العملاء، الطلبات، التسعير، الفواتير، السندات، التتبع، الجداول، الوكلاء، الموانئ، والتنبيهات.
                </div>

                <a href="{{ route('backups.export') }}" class="mt-5 inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-gradient-accent px-5 text-xs font-black text-accent-foreground shadow hover:opacity-95">
                    <i data-lucide="file-down" class="h-4 w-4"></i>
                    <span>تنزيل نسخة احتياطية الآن</span>
                </a>
            </div>

            <div class="rounded-xl border border-border bg-card p-6 shadow-card">
                <h2 class="flex items-center gap-2 text-lg font-black text-primary">
                    <i data-lucide="upload-cloud" class="h-5 w-5 text-accent"></i>
                    <span>استيراد نسخة احتياطية</span>
                </h2>
                <p class="mt-2 text-xs leading-6 text-muted-foreground">
                    استخدم ملف نسخة صادرة من نفس النظام. وضع الدمج يضيف ويحدث البيانات بدون حذف الموجود، أما الاستبدال الكامل فيمسح الجداول ثم يعيد تعبئتها من الملف.
                </p>

                <form action="{{ route('backups.import') }}" method="POST" enctype="multipart/form-data" class="mt-5 space-y-4 text-xs">
                    @csrf

                    <div class="space-y-1">
                        <label class="font-black text-foreground">ملف النسخة الاحتياطية *</label>
                        <div class="flex flex-col gap-2 rounded-lg border border-border bg-background p-2 sm:flex-row sm:items-center">
                            <input id="backup-file-input" type="file" name="backup_file" required accept=".json,application/json" class="sr-only">
                            <label for="backup-file-input" class="inline-flex h-9 cursor-pointer items-center justify-center rounded-md bg-secondary px-3 text-xs font-bold text-foreground hover:bg-secondary/80">
                                اختيار الملف
                            </label>
                            <span id="backup-file-name" data-empty-label="لم يتم اختيار أي ملف" class="min-w-0 flex-1 truncate px-1 text-xs text-muted-foreground">
                                لم يتم اختيار أي ملف
                            </span>
                        </div>
                        <p class="text-[11px] text-muted-foreground">الحد الأقصى داخل النظام: {{ $backupUploadSettings['app_limit_mb'] ?? 100 }}MB.</p>
                        @isset($backupUploadSettings)
                            <div class="rounded-lg border border-border/70 bg-secondary/20 p-3 text-[11px] leading-6 text-muted-foreground">
                                <div class="font-black text-foreground">إعدادات الرفع في السيرفر الحالي</div>
                                <div dir="ltr">upload_max_filesize={{ $backupUploadSettings['upload_max_filesize'] }} · post_max_size={{ $backupUploadSettings['post_max_size'] }}</div>
                                <div>
                                    مجلد الرفع المؤقت:
                                    <span dir="ltr">{{ $backupUploadSettings['upload_tmp_dir_label'] }}</span>
                                    <span class="{{ $backupUploadSettings['upload_tmp_ok'] ? 'text-success' : 'text-destructive' }}">({{ $backupUploadSettings['upload_tmp_status'] }})</span>
                                </div>
                            </div>
                        @endisset
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-border bg-background p-3 hover:bg-secondary/30">
                            <input type="radio" name="import_mode" value="merge" checked class="mt-0.5 h-4 w-4 accent-accent">
                            <span>
                                <span class="block font-black">دمج آمن</span>
                                <span class="mt-1 block text-[11px] leading-5 text-muted-foreground">يحدث السجلات الموجودة ويضيف المفقود فقط.</span>
                            </span>
                        </label>
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-destructive/25 bg-destructive/5 p-3 hover:bg-destructive/10">
                            <input type="radio" name="import_mode" value="replace" class="mt-0.5 h-4 w-4 accent-destructive">
                            <span>
                                <span class="block font-black text-destructive">استبدال كامل</span>
                                <span class="mt-1 block text-[11px] leading-5 text-muted-foreground">يمسح البيانات الحالية ويستعيد الملف.</span>
                            </span>
                        </label>
                    </div>

                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-accent/25 bg-accent/5 p-3">
                        <input type="checkbox" name="preserve_foundation_data" value="1" checked class="mt-0.5 h-4 w-4 accent-accent">
                        <span>
                            <span class="block font-black">
                                {{ app()->getLocale() === 'en' ? 'Keep staff, ports, and airports during replacement' : 'حماية الموظفين والموانئ والمطارات عند الاستبدال' }}
                            </span>
                            <span class="mt-1 block text-[11px] leading-5 text-muted-foreground">
                                {{ app()->getLocale() === 'en' ? 'When full replacement is selected, operational data is restored while roles, users, ports, and airports remain unchanged.' : 'عند اختيار الاستبدال الكامل سيتم استرجاع بيانات التشغيل مع إبقاء الأدوار والموظفين والموانئ والمطارات كما هي.' }}
                            </span>
                        </span>
                    </label>

                    <div class="space-y-1">
                        <label class="font-black text-foreground">تأكيد الاستبدال الكامل</label>
                        <input type="text" name="replace_confirmation" placeholder="اكتب: استبدال البيانات فقط عند اختيار الاستبدال الكامل" class="h-10 w-full rounded-lg border border-border bg-background px-3 focus:outline-none focus:ring-1 focus:ring-accent">
                        <p class="text-[11px] text-muted-foreground">اترك الحقل فارغاً عند استخدام الدمج الآمن.</p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button type="submit" formaction="{{ route('backups.preview') }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-accent/30 bg-accent/10 px-5 text-xs font-black text-accent hover:bg-accent/15">
                            <i data-lucide="scan-search" class="h-4 w-4"></i>
                            <span>فحص قبل الاستيراد</span>
                        </button>
                        <button type="submit" onclick="return confirm('هل أنت متأكد من استيراد هذه النسخة الاحتياطية؟')" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-primary px-5 text-xs font-black text-primary-foreground shadow hover:opacity-90">
                            <i data-lucide="refresh-cw" class="h-4 w-4"></i>
                            <span>بدء الاستيراد</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            <div class="rounded-xl border border-border bg-card shadow-card lg:col-span-2">
                <div class="border-b border-border p-4">
                    <h2 class="text-sm font-black text-primary">الجداول داخل النسخة الاحتياطية</h2>
                </div>
                <div class="grid gap-2 p-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($tableStats as $stat)
                        <div class="flex items-center justify-between gap-3 rounded-lg border border-border bg-background px-3 py-2 text-xs">
                            <span class="font-mono font-bold text-muted-foreground">{{ $stat['table'] }}</span>
                            <span class="rounded-full bg-secondary px-2 py-0.5 font-black">{{ $stat['count'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-xl border border-border bg-card shadow-card">
                <div class="border-b border-border p-4">
                    <h2 class="text-sm font-black text-primary">آخر عمليات النسخ</h2>
                </div>
                <div class="divide-y divide-border/60">
                    @forelse($latestBackupActivity as $activity)
                        <div class="p-4 text-xs">
                            <div class="font-black">{{ $activity->action_type === 'DATA_BACKUP_EXPORT' ? 'تصدير نسخة' : 'استيراد نسخة' }}</div>
                            <div class="mt-1 text-muted-foreground">{{ $activity->created_at->format('Y-m-d H:i') }}</div>
                        </div>
                    @empty
                        <div class="p-6 text-center text-xs text-muted-foreground">لا توجد عمليات بعد.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const backupFileInput = document.getElementById('backup-file-input');
        const backupFileName = document.getElementById('backup-file-name');

        if (!backupFileInput || !backupFileName) {
            return;
        }

        const emptyLabel = backupFileName.dataset.emptyLabel || backupFileName.textContent.trim();

        backupFileInput.addEventListener('change', () => {
            backupFileName.textContent = backupFileInput.files?.[0]?.name || emptyLabel;
        });
    });
</script>
@endsection
