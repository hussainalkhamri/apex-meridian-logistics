@extends('layouts.customer')

@section('title', __('messages.documents'))

@section('content')
@php
    $summaryCards = [
        ['label' => __('ملفات فيها مستندات'), 'value' => $stats['files'], 'icon' => 'folder-open'],
        ['label' => __('مرفقات الشحن'), 'value' => $stats['attachments'], 'icon' => 'paperclip'],
        ['label' => __('مستندات التخليص'), 'value' => $stats['customs_documents'], 'icon' => 'clipboard-check'],
        ['label' => __('ملاحظات مستندية'), 'value' => $stats['with_notes'], 'icon' => 'file-text'],
    ];
@endphp

<div class="space-y-6">
    <section class="rounded-xl border border-border bg-card p-5 shadow-card">
        <h1 class="text-2xl font-black">{{ __('messages.documents') }}</h1>
        <p class="mt-2 max-w-2xl text-sm leading-7 text-muted-foreground">{{ __('messages.documents_intro') }}</p>
    </section>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach($summaryCards as $card)
            <article class="rounded-xl border border-border bg-card p-5 shadow-card">
                <div class="flex items-center justify-between gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-secondary text-primary">
                        <i data-lucide="{{ $card['icon'] }}" class="h-5 w-5"></i>
                    </span>
                    <div class="text-3xl font-black">{{ $card['value'] }}</div>
                </div>
                <div class="mt-3 text-sm font-black">{{ $card['label'] }}</div>
            </article>
        @endforeach
    </section>

    <section class="rounded-xl border border-border bg-card shadow-card">
        <div class="border-b border-border p-5">
            <h2 class="text-lg font-black">{{ __('سجل المستندات') }}</h2>
            <p class="mt-1 text-xs text-muted-foreground">{{ __('كل مستند مرتبط برقم طلب محدد حتى يسهل الرجوع إليه من فريق التشغيل.') }}</p>
        </div>

        @if($documentRows->isEmpty())
            <div class="p-12 text-center text-sm text-muted-foreground">{{ __('messages.no_documents_yet') }}</div>
        @else
            @include('customer.partials.list-search', ['tableId' => 'portal-documents'])
            <div class="overflow-x-auto">
                <table id="portal-documents" data-responsive-table class="w-full min-w-[980px] text-right text-sm">
                    <thead class="bg-secondary/40 text-xs text-muted-foreground">
                        <tr>
                            <th class="px-5 py-3">{{ __('الطلب') }}</th>
                            <th class="px-5 py-3">{{ __('المسار') }}</th>
                            <th class="px-5 py-3">{{ __('وصف المستندات') }}</th>
                            <th class="px-5 py-3">{{ __('مرفقات الشحن') }}</th>
                            <th class="px-5 py-3">{{ __('مستندات التخليص') }}</th>
                            <th class="px-5 py-3">{{ __('الحالة') }}</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($documentRows as $row)
                            @php
                                $booking = $row['booking'];
                                $status = $statusLabels[$booking->status] ?? ['label' => $booking->status, 'class' => 'bg-secondary text-foreground border-border'];
                            @endphp
                            <tr class="border-t border-border/50 align-top hover:bg-secondary/20">
                                <td class="px-5 py-4">
                                    <div class="font-mono text-xs font-black" dir="ltr">{{ $booking->id }}</div>
                                    <div class="mt-1 text-[11px] text-muted-foreground">{{ optional($booking->created_at)->format('Y-m-d') }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-bold">{{ $booking->pol }}</div>
                                    <div class="text-xs text-muted-foreground">إلى {{ $booking->pod }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="max-w-[260px] text-xs leading-6 text-muted-foreground">{{ $booking->documents ?: __('لا يوجد وصف مكتوب، راجع المرفقات.') }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="space-y-1">
                                        @forelse($row['attachments'] as $attachment)
                                            <a href="{{ route('bookings.attachments.show', ['booking' => $booking, 'path' => ltrim(str_replace('\\', '/', $attachment), '/')]) }}" target="_blank" class="block rounded-md bg-secondary/40 px-2 py-1 text-xs font-bold text-primary hover:bg-secondary">
                                                {{ basename($attachment) }}
                                            </a>
                                        @empty
                                            <span class="text-xs text-muted-foreground">—</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="space-y-1">
                                        @forelse($row['customs_documents'] as $document)
                                            @php
                                                $documentPath = is_array($document) ? ($document['path'] ?? $document['file'] ?? null) : $document;
                                                $documentName = is_array($document) ? ($document['name'] ?? basename((string) $documentPath ?: __('مستند'))) : basename((string) $document);
                                            @endphp
                                            @if($documentPath)
                                                <a href="{{ route('bookings.attachments.show', ['booking' => $booking, 'path' => ltrim(str_replace('\\', '/', $documentPath), '/')]) }}" target="_blank" class="block rounded-md bg-secondary/40 px-2 py-1 text-xs font-bold text-primary hover:bg-secondary">
                                                    {{ $documentName }}
                                                </a>
                                            @else
                                                <span class="block rounded-md bg-secondary/40 px-2 py-1 text-xs font-bold">{{ $documentName }}</span>
                                            @endif
                                        @empty
                                            <span class="text-xs text-muted-foreground">—</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full border px-2.5 py-1 text-[11px] font-black {{ $status['class'] }}">{{ __($status['label']) }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <a href="{{ route('customer.shipments.show', $booking) }}" class="inline-flex h-9 items-center justify-center gap-2 rounded-lg border border-border px-3 text-xs font-black hover:bg-secondary">
                                        {{ __('الملف') }}
                                        <i data-lucide="arrow-left" class="h-3.5 w-3.5"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
@endsection
