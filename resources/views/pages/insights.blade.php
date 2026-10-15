@extends('layouts.app')

@section('title', 'الرؤى الذكية — {{ config('company.name.ar') }}')

@section('content')
<div>
    <section class="bg-hero py-14 text-white">
        <div class="container mx-auto px-4">
            <span class="inline-flex items-center rounded-full border border-white/20 bg-white/15 px-3 py-1 text-xs">تحليلات ذكية</span>
            <h1 class="mt-3 text-3xl font-extrabold md:text-4xl">الرؤى الذكية</h1>
            <p class="mt-2 max-w-2xl text-white/80">تحليل آلي لبيانات النظام لاكتشاف التنبيهات والفرص وتحسين الأداء.</p>
            <p class="mt-2 max-w-2xl text-xs text-white/70">{{ __('messages.insights_auto_schedule_note') }}</p>
            <div class="mt-6 flex flex-wrap gap-3">
                <form action="{{ route('insights.generate') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-gradient-accent px-5 text-sm font-bold text-accent-foreground hover:opacity-95">
                        <i data-lucide="cpu" class="h-4 w-4"></i> توليد رؤى جديدة
                    </button>
                </form>
                <form action="{{ route('insights.mark-all-read') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-white/30 bg-white/10 px-5 text-sm font-bold text-white hover:bg-white/20">
                        <i data-lucide="check-circle" class="h-4 w-4"></i> تحديد الكل كمقروء
                    </button>
                </form>
            </div>
        </div>
    </section>

    <section class="container mx-auto px-4 py-8">
        {{-- Stats --}}
        <div class="grid gap-4 md:grid-cols-4 mb-6">
            @foreach([
                ['label' => 'إجمالي الرؤى', 'value' => $insights->count(), 'color' => 'text-accent bg-accent/10', 'icon' => 'brain'],
                ['label' => 'غير مقروءة', 'value' => $insights->where('is_read', false)->count(), 'color' => 'text-primary bg-primary/10', 'icon' => 'bell'],
                ['label' => 'تحذيرات', 'value' => $insights->where('level', 'WARNING')->count(), 'color' => 'text-warning bg-warning/10', 'icon' => 'alert-circle'],
                ['label' => 'حرجة', 'value' => $insights->where('level', 'CRITICAL')->count(), 'color' => 'text-destructive bg-destructive/10', 'icon' => 'alert-triangle'],
            ] as $stat)
                <div class="rounded-xl border border-border/60 bg-card p-5 shadow-card flex items-center gap-4">
                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-lg {{ $stat['color'] }}">
                        <i data-lucide="{{ $stat['icon'] }}" class="h-6 w-6"></i>
                    </span>
                    <div>
                        <div class="text-2xl font-extrabold">{{ $stat['value'] }}</div>
                        <div class="text-sm text-muted-foreground">{{ $stat['label'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($insights->isEmpty())
            <div class="rounded-2xl border border-dashed border-border/60 p-16 text-center">
                <i data-lucide="brain" class="h-16 w-16 mx-auto mb-4 text-muted-foreground/40"></i>
                <h2 class="text-xl font-bold mb-2">لا توجد رؤى بعد</h2>
                <p class="text-muted-foreground text-sm">اضغط على "توليد رؤى جديدة" لتحليل بيانات النظام وإنشاء تقرير ذكي.</p>
            </div>
        @else
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach($insights->sortByDesc('created_at') as $insight)
                    <div class="rounded-xl border bg-card shadow-card overflow-hidden {{ !$insight->is_read ? 'ring-1 ring-accent/30' : '' }} transition-all hover:-translate-y-0.5">
                        {{-- Level Banner --}}
                        <div class="h-1.5 w-full {{ match($insight->level) { 'CRITICAL' => 'bg-destructive', 'WARNING' => 'bg-warning', default => 'bg-accent' } }}"></div>
                        <div class="p-5">
                            <div class="flex items-start justify-between gap-3 mb-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-xs font-bold {{ $insight->level_color }}">
                                        <i data-lucide="{{ $insight->level_icon }}" class="h-3 w-3"></i>
                                        {{ $insight->level_label }}
                                    </span>
                                    <span class="inline-flex items-center gap-1 rounded-full border border-border bg-secondary/50 px-2 py-0.5 text-xs font-bold text-muted-foreground">
                                        <i data-lucide="folder-open" class="h-3 w-3"></i>
                                        {{ __('messages.insight_target') }}: {{ $insight->target_label }}
                                    </span>
                                    @if(!$insight->is_read)
                                        <span class="h-2 w-2 rounded-full bg-accent animate-pulse"></span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-1">
                                    @if(!$insight->is_read)
                                        <form action="{{ route('insights.mark-read', $insight->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="h-7 w-7 flex items-center justify-center rounded-lg hover:bg-secondary transition-all" title="تحديد كمقروء">
                                                <i data-lucide="check" class="h-3.5 w-3.5 text-muted-foreground"></i>
                                            </button>
                                        </form>
                                    @endif
                                    <form action="{{ route('insights.destroy', $insight->id) }}" method="POST" class="inline" onsubmit="return confirm('حذف هذه الرؤية؟')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="h-7 w-7 flex items-center justify-center rounded-lg hover:bg-destructive/10 transition-all" title="حذف">
                                            <i data-lucide="trash-2" class="h-3.5 w-3.5 text-muted-foreground hover:text-destructive"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <h3 class="font-bold text-base mb-2">{{ $insight->title }}</h3>
                            <p class="text-sm text-muted-foreground leading-relaxed">{{ $insight->content }}</p>
                            @if($insight->action_url)
                                <a href="{{ $insight->action_url }}" class="mt-4 inline-flex h-8 items-center justify-center gap-2 rounded-lg border border-border bg-secondary px-3 text-xs font-bold text-foreground transition hover:bg-secondary/70">
                                    <i data-lucide="arrow-up-right" class="h-3.5 w-3.5"></i>
                                    {{ $insight->action_label_text }}
                                </a>
                            @endif
                            @php
                                $lastSeen = $insight->last_seen_at ?: $insight->updated_at;
                            @endphp
                            <div class="mt-3 text-[10px] text-muted-foreground">
                                {{ __('messages.insight_last_seen') }}: {{ $lastSeen?->diffForHumans() }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection

