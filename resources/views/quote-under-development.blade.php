@extends('layouts.app')

@section('title', 'الصفحة قيد التطوير — ' . __('messages.site_name'))

@section('content')
<section class="container mx-auto flex min-h-[60vh] items-center justify-center px-4 py-16">
    <div class="w-full max-w-xl rounded-2xl border border-border bg-card p-8 text-center shadow-card">
        <span class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-secondary text-primary">
            <i data-lucide="construction" class="h-8 w-8"></i>
        </span>
        <h1 class="mt-5 text-2xl font-black text-foreground">الصفحة قيد التطوير</h1>
        <p class="mt-3 text-sm leading-relaxed text-muted-foreground">
            عذرًا، هذه الصفحة غير متاحة حاليًا ويتم العمل عليها. يرجى المحاولة لاحقًا.
        </p>
        <a href="{{ route('home') }}" class="mt-6 inline-flex h-10 items-center justify-center rounded-lg border border-border bg-card px-5 text-xs font-bold hover:bg-secondary/40">
            العودة للرئيسية
        </a>
    </div>
</section>
@endsection
