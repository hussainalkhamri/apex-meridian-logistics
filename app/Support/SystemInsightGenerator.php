<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Notification;
use App\Models\NotificationRead;
use App\Models\ShipmentTrack;
use App\Models\SystemInsight;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class SystemInsightGenerator
{
    public function generate(): array
    {
        $candidates = $this->analyzeAndGenerate($this->buildContext());
        $sourceKeys = collect($candidates)->pluck('source_key')->all();

        return DB::transaction(function () use ($candidates, $sourceKeys) {
            $summary = [
                'created' => 0,
                'updated' => 0,
                'unchanged' => 0,
                'notifications_created' => 0,
                'notifications_updated' => 0,
                'notifications_cleared' => 0,
                'total' => count($candidates),
            ];

            $this->removeLegacyInsights();
            $this->removeResolvedInsights($sourceKeys);

            if (! in_array('system_normal', $sourceKeys, true)) {
                $this->removeNormalInsight();
            }

            foreach ($candidates as $data) {
                [$insight, $state] = $this->upsertInsight($data);
                $summary[$state]++;

                foreach ($this->syncCriticalNotification($insight) as $key => $value) {
                    $summary[$key] += $value;
                }
            }

            return $summary;
        });
    }

    protected function buildContext(): array
    {
        $today = now()->startOfDay();

        try {
            $invoiceStats = $this->overdueInvoiceStats($today);
            $activeShipmentQuery = ShipmentTrack::query()
                ->where(function ($query) {
                    $query->whereNull('progress')->orWhere('progress', '<', 100);
                });

            $clearanceQuery = Booking::query()
                ->where(function ($query) {
                    $query->whereIn('status', ['approved', 'booked'])
                        ->orWhereNotNull('customs_reference')
                        ->orWhere('customs_status', '!=', 'pending');
                });

            return [
                'totalBookings' => Booking::count(),
                'activeBookings' => Booking::whereIn('status', ['new', 'priced', 'sent', 'needs_pricing'])->count(),
                'approvedBookings' => Booking::where('status', 'approved')->count(),
                'rejectedBookings' => Booking::where('status', 'rejected')->count(),
                'pendingPricing' => Booking::whereIn('status', ['new', 'needs_pricing'])->count(),
                'pendingSales' => Booking::where('status', 'priced')->count(),
                'activeShipments' => (clone $activeShipmentQuery)->count(),
                'deliveredCount' => ShipmentTrack::where('progress', '>=', 100)->count(),
                'overdueShipments' => (clone $activeShipmentQuery)->whereDate('eta', '<', $today)->count(),
                'recentLogs' => AuditLog::where('created_at', '>=', now()->subDay())->count(),
                'overdueInvoices' => $invoiceStats['count'],
                'overdueInvoiceAmount' => $invoiceStats['amount'],
                'customsOnHold' => (clone $clearanceQuery)->where('customs_status', 'on_hold')->count(),
                'customsPending' => (clone $clearanceQuery)->whereIn('customs_status', ['pending', 'documents_review', 'inspection', 'duties'])->count(),
            ];
        } catch (\Throwable) {
            return [];
        }
    }

    protected function overdueInvoiceStats($today): array
    {
        $overdueInvoices = Invoice::with('vouchers')
            ->whereNotNull('due_at')
            ->whereDate('due_at', '<', $today)
            ->get()
            ->filter(fn (Invoice $invoice) => $invoice->remaining_amount > 0);

        return [
            'count' => $overdueInvoices->count(),
            'amount' => (float) $overdueInvoices->sum(fn (Invoice $invoice) => $invoice->remaining_amount),
        ];
    }

    protected function analyzeAndGenerate(array $ctx): array
    {
        $insights = [];

        if (! empty($ctx)) {
            if (($ctx['pendingPricing'] ?? 0) > 5) {
                $insights[] = $this->insight(
                    sourceKey: 'pending_pricing',
                    title: __('messages.insight_pending_pricing_title'),
                    content: __('messages.insight_pending_pricing_content', ['count' => $ctx['pendingPricing']]),
                    level: $ctx['pendingPricing'] > 15 ? 'CRITICAL' : 'WARNING',
                    target: 'pricing',
                    routeName: 'pricing.index',
                    actionLabel: 'messages.open_pricing'
                );
            }

            if (($ctx['pendingSales'] ?? 0) > 3) {
                $insights[] = $this->insight(
                    sourceKey: 'pending_sales',
                    title: __('messages.insight_pending_sales_title'),
                    content: __('messages.insight_pending_sales_content', ['count' => $ctx['pendingSales']]),
                    level: $ctx['pendingSales'] > 10 ? 'CRITICAL' : 'WARNING',
                    target: 'sales',
                    routeName: 'sales.index',
                    actionLabel: 'messages.open_sales'
                );
            }

            if (($ctx['activeShipments'] ?? 0) > 0) {
                $insights[] = $this->insight(
                    sourceKey: 'active_shipments',
                    title: __('messages.insight_active_shipments_title'),
                    content: __('messages.insight_active_shipments_content', [
                        'active' => $ctx['activeShipments'],
                        'delivered' => $ctx['deliveredCount'],
                    ]),
                    level: 'INFO',
                    target: 'tracking',
                    routeName: 'bookings.index',
                    actionLabel: 'messages.open_tracking_queue'
                );
            }

            if (($ctx['overdueShipments'] ?? 0) > 0) {
                $insights[] = $this->insight(
                    sourceKey: 'overdue_shipments',
                    title: __('messages.insight_overdue_shipments_title'),
                    content: __('messages.insight_overdue_shipments_content', ['count' => $ctx['overdueShipments']]),
                    level: $ctx['overdueShipments'] > 3 ? 'CRITICAL' : 'WARNING',
                    target: 'tracking',
                    routeName: 'bookings.index',
                    actionLabel: 'messages.open_tracking_queue'
                );
            }

            if (($ctx['rejectedBookings'] ?? 0) > 0) {
                $insights[] = $this->insight(
                    sourceKey: 'rejected_bookings',
                    title: __('messages.insight_rejected_bookings_title'),
                    content: __('messages.insight_rejected_bookings_content', ['count' => $ctx['rejectedBookings']]),
                    level: $ctx['rejectedBookings'] > 5 ? 'CRITICAL' : 'WARNING',
                    target: 'sales',
                    routeName: 'sales.index',
                    actionLabel: 'messages.open_sales'
                );
            }

            if (($ctx['overdueInvoices'] ?? 0) > 0) {
                $insights[] = $this->insight(
                    sourceKey: 'overdue_invoices',
                    title: __('messages.insight_overdue_invoices_title'),
                    content: __('messages.insight_overdue_invoices_content', [
                        'count' => $ctx['overdueInvoices'],
                        'amount' => number_format((float) $ctx['overdueInvoiceAmount'], 2),
                    ]),
                    level: $ctx['overdueInvoices'] > 3 ? 'CRITICAL' : 'WARNING',
                    target: 'accounts',
                    routeName: 'accounts.index',
                    actionLabel: 'messages.open_accounts'
                );
            }

            if (($ctx['customsOnHold'] ?? 0) > 0) {
                $insights[] = $this->insight(
                    sourceKey: 'customs_on_hold',
                    title: __('messages.insight_customs_on_hold_title'),
                    content: __('messages.insight_customs_on_hold_content', ['count' => $ctx['customsOnHold']]),
                    level: $ctx['customsOnHold'] > 5 ? 'CRITICAL' : 'WARNING',
                    target: 'customs',
                    routeName: 'customs.index',
                    actionLabel: 'messages.open_customs'
                );
            }

            if (($ctx['recentLogs'] ?? 0) > 50) {
                $insights[] = $this->insight(
                    sourceKey: 'high_activity',
                    title: __('messages.insight_high_activity_title'),
                    content: __('messages.insight_high_activity_content', ['count' => $ctx['recentLogs']]),
                    level: 'INFO',
                    target: 'audit',
                    routeName: 'audit-logs.index',
                    actionLabel: 'messages.open_audit_logs'
                );
            }
        }

        if (empty($insights)) {
            $insights[] = $this->insight(
                sourceKey: 'system_normal',
                title: __('messages.insight_system_normal_title'),
                content: __('messages.insight_system_normal_content'),
                level: 'INFO',
                target: 'general',
                routeName: 'dashboard',
                actionLabel: 'messages.open_dashboard'
            );
        }

        return $insights;
    }

    protected function insight(
        string $sourceKey,
        string $title,
        string $content,
        string $level,
        string $target,
        string $routeName,
        string $actionLabel
    ): array {
        return [
            'source_key' => $sourceKey,
            'title' => $title,
            'content' => $content,
            'level' => $level,
            'target' => $target,
            'action_url' => $this->routePath($routeName),
            'action_label' => $actionLabel,
            'last_seen_at' => now(),
        ];
    }

    protected function upsertInsight(array $data): array
    {
        $insight = SystemInsight::where('source_key', $data['source_key'])->first();

        if (! $insight) {
            return [
                SystemInsight::create(array_merge($data, ['is_read' => false])),
                'created',
            ];
        }

        $trackedFields = ['title', 'content', 'level', 'target', 'action_url', 'action_label'];
        $hasChanged = collect($trackedFields)->contains(
            fn (string $field) => (string) $insight->{$field} !== (string) ($data[$field] ?? '')
        );

        $insight->fill($data);

        if ($hasChanged) {
            $insight->is_read = false;
        }

        $insight->save();

        return [$insight->fresh(), $hasChanged ? 'updated' : 'unchanged'];
    }

    protected function syncCriticalNotification(SystemInsight $insight): array
    {
        $summary = [
            'notifications_created' => 0,
            'notifications_updated' => 0,
            'notifications_cleared' => 0,
        ];

        if ($insight->level !== 'CRITICAL') {
            if ($insight->notification_id) {
                Notification::whereKey($insight->notification_id)->delete();
                $insight->forceFill(['notification_id' => null])->save();
                $summary['notifications_cleared']++;
            }

            return $summary;
        }

        $message = __('messages.insight_critical_notification', ['title' => $insight->title]);
        $notification = $insight->notification_id
            ? Notification::find($insight->notification_id)
            : null;

        if (! $notification) {
            $notification = Notification::create([
                'id' => SystemId::make(Notification::class, 'N'),
                'message' => $message,
                'booking_id' => null,
                'read' => false,
                'target' => $insight->target ?: 'general',
            ]);

            $insight->forceFill(['notification_id' => $notification->id])->save();
            $summary['notifications_created']++;

            return $summary;
        }

        $changed = $notification->message !== $message
            || $notification->target !== ($insight->target ?: 'general')
            || $notification->read;

        if ($changed) {
            $notification->update([
                'message' => $message,
                'booking_id' => null,
                'read' => false,
                'target' => $insight->target ?: 'general',
            ]);

            NotificationRead::where('notification_id', $notification->id)->delete();
            $summary['notifications_updated']++;
        }

        return $summary;
    }

    protected function routePath(string $routeName): ?string
    {
        if (! Route::has($routeName)) {
            return null;
        }

        return route($routeName, [], false);
    }

    protected function removeNormalInsight(): void
    {
        $normalInsight = SystemInsight::where('source_key', 'system_normal')->first();

        if (! $normalInsight) {
            return;
        }

        if ($normalInsight->notification_id) {
            Notification::whereKey($normalInsight->notification_id)->delete();
        }

        $normalInsight->delete();
    }

    protected function removeLegacyInsights(): void
    {
        SystemInsight::whereNull('source_key')
            ->get()
            ->each(function (SystemInsight $insight): void {
                if ($insight->notification_id) {
                    Notification::whereKey($insight->notification_id)->delete();
                }

                $insight->delete();
            });
    }

    protected function removeResolvedInsights(array $activeSourceKeys): void
    {
        SystemInsight::whereNotNull('source_key')
            ->whereNotIn('source_key', $activeSourceKeys)
            ->get()
            ->each(function (SystemInsight $insight): void {
                if ($insight->notification_id) {
                    Notification::whereKey($insight->notification_id)->delete();
                }

                $insight->delete();
            });
    }
}
