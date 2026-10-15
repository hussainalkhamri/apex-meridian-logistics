<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use JsonException;

class DataBackupController extends Controller
{
    private const BACKUP_TYPE = 'shipmate-data-backup';
    private const BACKUP_MAX_KILOBYTES = 102400;

    private const TABLES = [
        'roles',
        'users',
        'customers',
        'agents',
        'ports',
        'port_countries',
        'airports',
        'shipping_rates',
        'vessels',
        'schedules',
        'bookings',
        'invoices',
        'shipment_tracks',
        'shipment_events',
        'vouchers',
        'notifications',
        'notification_reads',
        'customer_notification_reads',
        'audit_logs',
        'system_insights',
    ];

    private const PRESERVED_REPLACE_TABLES = [
        'roles',
        'users',
        'ports',
        'port_countries',
        'airports',
    ];

    public function index()
    {
        $tableStats = collect(self::TABLES)
            ->filter(fn (string $table) => Schema::hasTable($table))
            ->map(fn (string $table) => [
                'table' => $table,
                'count' => DB::table($table)->count(),
            ])
            ->values();

        $latestBackupActivity = AuditLog::whereIn('action_type', ['DATA_BACKUP_EXPORT', 'DATA_BACKUP_IMPORT'])
            ->latest()
            ->take(8)
            ->get();
        $backupUploadSettings = $this->backupUploadSettings();

        return view('pages.backups', compact('tableStats', 'latestBackupActivity', 'backupUploadSettings'));
    }

    public function export()
    {
        $payload = [
            'meta' => [
                'type' => self::BACKUP_TYPE,
                'app' => config('app.name', 'ShipMate'),
                'exported_at' => now()->toISOString(),
                'format_version' => 1,
                'tables' => self::TABLES,
            ],
            'tables' => [],
        ];

        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $payload['tables'][$table] = DB::table($table)
                ->get()
                ->map(fn ($row) => (array) $row)
                ->values()
                ->all();
        }

        AuditLog::logAction('DATA_BACKUP_EXPORT', 'system', 'backup', [], [
            'tables' => array_keys($payload['tables']),
            'counts' => collect($payload['tables'])->map(fn (array $rows) => count($rows))->all(),
        ]);

        $filename = 'shipmate-backup-' . now()->format('Y-m-d-His') . '.json';
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return response($json, 200, [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function preview(Request $request)
    {
        $this->prepareBackupUploadRequest($request);

        $validated = $request->validate($this->backupValidationRules(), $this->backupValidationMessages());

        try {
            $payload = json_decode($request->file('backup_file')->get(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return back()->withErrors([
                'backup_file' => 'ملف النسخة الاحتياطية غير صالح أو ليس بصيغة JSON صحيحة.',
            ]);
        }

        if (($payload['meta']['type'] ?? null) !== self::BACKUP_TYPE || ! is_array($payload['tables'] ?? null)) {
            return back()->withErrors([
                'backup_file' => 'هذا الملف ليس نسخة احتياطية صادرة من النظام.',
            ]);
        }

        $preserveFoundationData = $validated['import_mode'] === 'replace'
            && $request->boolean('preserve_foundation_data');

        return redirect()->route('backups.index')->with('backup_preview', [
            'mode' => $validated['import_mode'],
            'preserve_foundation_data' => $preserveFoundationData,
            'exported_at' => $payload['meta']['exported_at'] ?? null,
            'format_version' => $payload['meta']['format_version'] ?? null,
            'tables' => $this->previewPayload($payload['tables'], $validated['import_mode'], $preserveFoundationData),
        ]);
    }

    public function import(Request $request)
    {
        $this->prepareBackupUploadRequest($request);

        $validated = $request->validate($this->backupValidationRules([
            'replace_confirmation' => ['nullable', 'string'],
        ]), $this->backupValidationMessages());

        if ($validated['import_mode'] === 'replace' && $request->input('replace_confirmation') !== 'استبدال البيانات') {
            return back()->withErrors([
                'replace_confirmation' => 'لاستخدام الاستبدال الكامل اكتب: استبدال البيانات',
            ]);
        }

        try {
            $payload = json_decode($request->file('backup_file')->get(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return back()->withErrors([
                'backup_file' => 'ملف النسخة الاحتياطية غير صالح أو ليس بصيغة JSON صحيحة.',
            ]);
        }

        if (($payload['meta']['type'] ?? null) !== self::BACKUP_TYPE || ! is_array($payload['tables'] ?? null)) {
            return back()->withErrors([
                'backup_file' => 'هذا الملف ليس نسخة احتياطية صادرة من النظام.',
            ]);
        }

        $preserveFoundationData = $validated['import_mode'] === 'replace'
            && $request->boolean('preserve_foundation_data');
        $summary = $this->restorePayload($payload['tables'], $validated['import_mode'], $preserveFoundationData);

        AuditLog::logAction('DATA_BACKUP_IMPORT', 'system', 'backup', [], [
            'mode' => $validated['import_mode'],
            'preserve_foundation_data' => $preserveFoundationData,
            'tables' => $summary,
        ]);

        $message = $validated['import_mode'] === 'replace'
            ? 'تم استبدال بيانات النظام من النسخة الاحتياطية بنجاح.'
            : 'تم دمج بيانات النسخة الاحتياطية مع النظام بنجاح.';

        return redirect()->route('backups.index')->with('success', $message);
    }

    private function backupValidationRules(array $extraRules = []): array
    {
        return $extraRules + [
            'backup_file' => ['bail', 'required', 'file', 'mimes:json,txt', 'max:' . self::BACKUP_MAX_KILOBYTES],
            'import_mode' => ['required', 'in:merge,replace'],
            'preserve_foundation_data' => ['nullable', 'boolean'],
        ];
    }

    private function backupValidationMessages(): array
    {
        $maxMegabytes = (int) (self::BACKUP_MAX_KILOBYTES / 1024);

        return [
            'backup_file.required' => 'يرجى اختيار ملف النسخة الاحتياطية أولاً.',
            'backup_file.uploaded' => $this->backupUploadErrorMessage(),
            'backup_file.file' => 'ملف النسخة الاحتياطية غير صالح للرفع.',
            'backup_file.mimes' => 'يجب أن يكون ملف النسخة الاحتياطية بصيغة JSON.',
            'backup_file.max' => "حجم ملف النسخة الاحتياطية أكبر من الحد المسموح ({$maxMegabytes}MB).",
            'import_mode.required' => 'يرجى اختيار طريقة الاستيراد.',
            'import_mode.in' => 'طريقة الاستيراد غير صحيحة.',
        ];
    }

    private function raiseBackupRuntimeLimits(): void
    {
        @ini_set('memory_limit', '512M');
        @ini_set('max_execution_time', '300');
        @ini_set('max_input_time', '300');
    }

    private function prepareBackupUploadRequest(Request $request): void
    {
        $this->raiseBackupRuntimeLimits();
        $this->rejectOversizedBackupPost($request);
        $this->rejectInvalidUploadedBackup($request);
    }

    private function rejectOversizedBackupPost(Request $request): void
    {
        $contentLength = (int) $request->server('CONTENT_LENGTH', 0);
        $postMaxBytes = $this->phpSizeToBytes((string) ini_get('post_max_size'));

        if ($contentLength > 0 && $postMaxBytes > 0 && $contentLength > $postMaxBytes) {
            throw ValidationException::withMessages([
                'backup_file' => 'تعذر رفع ملف النسخة الاحتياطية لأن حجم الطلب يتجاوز حد post_max_size في PHP على السيرفر الداخلي. الحد الحالي: '
                    . $this->formatBytes($postMaxBytes) . '، وحجم الطلب: ' . $this->formatBytes($contentLength) . '.',
            ]);
        }
    }

    private function rejectInvalidUploadedBackup(Request $request): void
    {
        $file = $request->file('backup_file');

        if ($file instanceof UploadedFile && ! $file->isValid()) {
            throw ValidationException::withMessages([
                'backup_file' => $this->backupUploadErrorMessage($file->getError()),
            ]);
        }
    }

    private function backupUploadErrorMessage(?int $errorCode = null): string
    {
        $settings = $this->backupUploadSettings();
        $serverDetails = "إعدادات السيرفر الحالية: upload_max_filesize={$settings['upload_max_filesize']}, post_max_size={$settings['post_max_size']}, upload_tmp_dir={$settings['upload_tmp_dir_label']} ({$settings['upload_tmp_status']}).";

        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'تعذر رفع الملف لأن حد الرفع في PHP على السيرفر الداخلي أقل من حجم الطلب. ' . $serverDetails,
            UPLOAD_ERR_PARTIAL => 'لم يكتمل رفع ملف النسخة الاحتياطية. أعد المحاولة من متصفح آخر أو تأكد من ثبات الشبكة. ' . $serverDetails,
            UPLOAD_ERR_NO_FILE => 'لم يصل أي ملف نسخة احتياطية إلى السيرفر. اختر الملف مرة أخرى ثم أعد المحاولة.',
            UPLOAD_ERR_NO_TMP_DIR => 'مجلد الرفع المؤقت في PHP غير موجود على السيرفر الداخلي. اضبط upload_tmp_dir أو أنشئ مجلد temp قابل للكتابة.',
            UPLOAD_ERR_CANT_WRITE => 'السيرفر الداخلي لا يستطيع الكتابة في مجلد الرفع المؤقت. امنح صلاحية كتابة لمجلد: ' . $settings['upload_tmp_dir_label'] . '.',
            UPLOAD_ERR_EXTENSION => 'إضافة PHP على السيرفر الداخلي أوقفت عملية الرفع. راجع إعدادات PHP extensions أو سجل الأخطاء.',
            default => 'تعذر رفع ملف النسخة الاحتياطية. ' . $serverDetails,
        };
    }

    private function backupUploadSettings(): array
    {
        $uploadTmpDir = ini_get('upload_tmp_dir') ?: sys_get_temp_dir();
        $uploadTmpDir = $uploadTmpDir ?: 'غير محدد';
        $tmpExists = is_string($uploadTmpDir) && $uploadTmpDir !== 'غير محدد' && is_dir($uploadTmpDir);
        $tmpWritable = $tmpExists && is_writable($uploadTmpDir);

        return [
            'app_limit_mb' => (int) (self::BACKUP_MAX_KILOBYTES / 1024),
            'upload_max_filesize' => (string) ini_get('upload_max_filesize'),
            'post_max_size' => (string) ini_get('post_max_size'),
            'upload_tmp_dir_label' => $uploadTmpDir,
            'upload_tmp_status' => $tmpWritable ? 'قابل للكتابة' : ($tmpExists ? 'غير قابل للكتابة' : 'غير موجود'),
            'upload_tmp_ok' => $tmpWritable,
        ];
    }

    private function phpSizeToBytes(string $size): int
    {
        $size = trim($size);
        if ($size === '') {
            return 0;
        }

        $unit = strtolower(substr($size, -1));
        $number = (float) $size;

        return (int) match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return round($bytes / 1024 / 1024, 2) . 'MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . 'KB';
        }

        return $bytes . 'B';
    }

    private function previewPayload(array $tables, string $mode, bool $preserveFoundationData): array
    {
        $preservedTables = $preserveFoundationData ? self::PRESERVED_REPLACE_TABLES : [];

        return collect(self::TABLES)
            ->map(function (string $table) use ($tables, $mode, $preservedTables) {
                $incomingRows = is_array($tables[$table] ?? null)
                    ? collect($tables[$table])->filter(fn ($row) => is_array($row))->count()
                    : 0;

                $action = 'missing';
                if (Schema::hasTable($table) && array_key_exists($table, $tables)) {
                    $action = $mode === 'replace' ? 'replace' : 'merge';
                    if ($mode === 'replace' && in_array($table, $preservedTables, true)) {
                        $action = 'preserved';
                    }
                }

                return [
                    'table' => $table,
                    'current' => Schema::hasTable($table) ? DB::table($table)->count() : 0,
                    'incoming' => $incomingRows,
                    'action' => $action,
                ];
            })
            ->values()
            ->all();
    }

    private function restorePayload(array $tables, string $mode, bool $preserveFoundationData = false): array
    {
        $summary = [];
        $preservedTables = $preserveFoundationData ? self::PRESERVED_REPLACE_TABLES : [];

        $this->withoutForeignKeyChecks(function () use ($tables, $mode, $preservedTables, &$summary) {
            DB::transaction(function () use ($tables, $mode, $preservedTables, &$summary) {
                if ($mode === 'replace') {
                    foreach (array_reverse(self::TABLES) as $table) {
                        if (in_array($table, $preservedTables, true)) {
                            continue;
                        }

                        if (Schema::hasTable($table)) {
                            DB::table($table)->delete();
                        }
                    }
                }

                foreach (self::TABLES as $table) {
                    if (! Schema::hasTable($table) || ! array_key_exists($table, $tables)) {
                        continue;
                    }

                    if ($mode === 'replace' && in_array($table, $preservedTables, true)) {
                        $summary[$table] = 'preserved';
                        continue;
                    }

                    $columns = Schema::getColumnListing($table);
                    $rows = collect($tables[$table])
                        ->filter(fn ($row) => is_array($row))
                        ->map(fn (array $row) => $this->normalizeRow($row, $columns))
                        ->filter()
                        ->values()
                        ->all();

                    if ($mode === 'merge') {
                        $this->mergeRows($table, $rows, $columns);
                    } else {
                        foreach (array_chunk($rows, 300) as $chunk) {
                            DB::table($table)->insert($chunk);
                        }
                    }

                    $summary[$table] = count($rows);
                }
            }, 3);
        });

        return $summary;
    }

    private function mergeRows(string $table, array $rows, array $columns): void
    {
        if ($table === 'system_insights' && in_array('source_key', $columns, true)) {
            $sourceKeyRows = collect($rows)
                ->filter(fn (array $row) => filled($row['source_key'] ?? null))
                ->keyBy(fn (array $row) => (string) $row['source_key'])
                ->values()
                ->all();

            $this->upsertRows($table, $sourceKeyRows, ['source_key'], $this->columnsExcept($columns, ['id', 'source_key']));

            $idRows = collect($rows)
                ->reject(fn (array $row) => filled($row['source_key'] ?? null))
                ->filter(fn (array $row) => filled($row['id'] ?? null))
                ->keyBy(fn (array $row) => (string) $row['id'])
                ->values()
                ->all();

            $this->upsertRows($table, $idRows, ['id'], $this->columnsExcept($columns, ['id']));

            return;
        }

        if ($table === 'airports' && in_array('iata_code', $columns, true)) {
            $iataRows = collect($rows)
                ->filter(fn (array $row) => filled($row['iata_code'] ?? null))
                ->keyBy(fn (array $row) => strtoupper((string) $row['iata_code']))
                ->values()
                ->all();

            $this->upsertRows($table, $iataRows, ['iata_code'], $this->columnsExcept($columns, ['id', 'iata_code']));

            $idRows = collect($rows)
                ->reject(fn (array $row) => filled($row['iata_code'] ?? null))
                ->filter(fn (array $row) => filled($row['id'] ?? null))
                ->keyBy(fn (array $row) => (string) $row['id'])
                ->values()
                ->all();

            $this->upsertRows($table, $idRows, ['id'], $this->columnsExcept($columns, ['id']));

            return;
        }

        if ($table === 'port_countries') {
            $codeRows = collect($rows)
                ->filter(fn (array $row) => filled($row['code'] ?? null))
                ->keyBy(fn (array $row) => strtoupper((string) $row['code']))
                ->values()
                ->all();

            $this->upsertRows($table, $codeRows, ['code'], $this->columnsExcept($columns, ['id', 'code']));

            $nameRows = collect($rows)
                ->reject(fn (array $row) => filled($row['code'] ?? null))
                ->filter(fn (array $row) => filled($row['name'] ?? null))
                ->keyBy(fn (array $row) => mb_strtolower((string) $row['name']))
                ->values()
                ->all();

            $this->upsertRows($table, $nameRows, ['name'], $this->columnsExcept($columns, ['id', 'name']));

            $idRows = collect($rows)
                ->reject(fn (array $row) => filled($row['code'] ?? null) || filled($row['name'] ?? null))
                ->filter(fn (array $row) => filled($row['id'] ?? null))
                ->keyBy(fn (array $row) => (string) $row['id'])
                ->values()
                ->all();

            $this->upsertRows($table, $idRows, ['id'], $this->columnsExcept($columns, ['id']));

            return;
        }

        $this->upsertRows($table, $rows, ['id'], $columns);
    }

    private function upsertRows(string $table, array $rows, array $uniqueBy, array $updateColumns): void
    {
        foreach (array_chunk($rows, 300) as $chunk) {
            if ($chunk === []) {
                continue;
            }

            $chunkColumns = collect($chunk)
                ->flatMap(fn (array $row) => array_keys($row))
                ->unique()
                ->values()
                ->all();

            DB::table($table)->upsert($chunk, $uniqueBy, array_values(array_intersect($updateColumns, $chunkColumns)));
        }
    }

    private function columnsExcept(array $columns, array $excluded): array
    {
        return array_values(array_diff($columns, $excluded));
    }

    private function normalizeRow(array $row, array $columns): array
    {
        $normalized = [];

        foreach ($columns as $column) {
            if (! array_key_exists($column, $row)) {
                continue;
            }

            $value = $row[$column];
            $normalized[$column] = is_array($value) || is_object($value)
                ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : $value;
        }

        return $normalized;
    }

    private function withoutForeignKeyChecks(callable $callback): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        }

        try {
            $callback();
        } finally {
            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            } elseif ($driver === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = ON');
            }
        }
    }
}
