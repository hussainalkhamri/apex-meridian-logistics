@extends('layouts.app')

@section('title', __('messages.public_contact') . ' - ' . __('messages.site_name'))

@section('content')
@php
    $isArabic = app()->getLocale() === 'ar';
    $copy = [
        'badge' => $isArabic ? 'تواصل مباشر' : 'Direct Contact',
        'title' => $isArabic ? 'اتصل بنا' : 'Contact Us',
        'subtitle' => $isArabic
            ? 'أرسل تفاصيل الشحنة أو التخليص، وسنوجهها للفريق المناسب داخل النظام.'
            : 'Send shipment or clearance details and we will route them to the right team.',
        'form_title' => $isArabic ? 'طلب تواصل سريع' : 'Quick contact request',
        'company' => $isArabic ? 'اسم الشركة' : 'Company name',
        'name' => $isArabic ? 'اسم المسؤول' : 'Contact name',
        'phone' => $isArabic ? 'رقم الهاتف' : 'Phone',
        'service' => $isArabic ? 'نوع الخدمة' : 'Service',
        'message' => $isArabic ? 'تفاصيل الطلب' : 'Request details',
        'send_whatsapp' => $isArabic ? 'إرسال عبر واتساب' : 'Send via WhatsApp',
        'shipping' => $isArabic ? 'شحن دولي' : 'International shipping',
        'customs' => $isArabic ? 'تخليص جمركي' : 'Customs clearance',
        'door' => $isArabic ? 'Door to Door' : 'Door to Door',
        'placeholder' => $isArabic ? 'اكتب من أين إلى أين، نوع البضاعة، الوزن، وعدد الطرود...' : 'Write origin, destination, cargo type, weight, and packages...',
        'visit' => $isArabic ? 'زيارة الصفحات المهمة' : 'Important pages',
        'ports' => $isArabic ? 'موانئنا' : 'Our ports',
        'agents' => $isArabic ? 'وكلاؤنا' : 'Our agents',
        'schedules' => $isArabic ? 'جداول الرحلات' : 'Schedules',
    ];
@endphp

<div class="motion-page">
    <section class="relative overflow-hidden bg-primary py-16 text-primary-foreground">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_15%,hsl(var(--accent)/0.28),transparent_34%),linear-gradient(135deg,hsl(var(--primary)),hsl(var(--ocean-deep)))]"></div>
        <div class="container relative mx-auto px-4">
            <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/12 px-3 py-1 text-xs font-bold">
                <i data-lucide="headphones" class="h-4 w-4 text-accent"></i>
                {{ $copy['badge'] }}
            </span>
            <h1 class="mt-5 max-w-3xl text-3xl font-black leading-tight md:text-5xl">{{ $copy['title'] }}</h1>
            <p class="mt-4 max-w-2xl text-sm leading-8 text-primary-foreground/78 md:text-base">{{ $copy['subtitle'] }}</p>
        </div>
    </section>

    <section class="container mx-auto grid gap-6 px-4 py-10 lg:grid-cols-[minmax(0,1fr)_24rem]">
        <div class="rounded-lg border border-border bg-card p-5 shadow-card md:p-6">
            <h2 class="text-xl font-black text-foreground">{{ $copy['form_title'] }}</h2>
            <form id="public-contact-form" class="mt-5 grid gap-4 md:grid-cols-2">
                <div class="space-y-2">
                    <label for="contact-company" class="text-xs font-bold text-foreground">{{ $copy['company'] }}</label>
                    <input id="contact-company" type="text" class="h-11 w-full rounded-lg border border-border bg-background px-3 text-sm outline-none focus:border-accent">
                </div>
                <div class="space-y-2">
                    <label for="contact-name" class="text-xs font-bold text-foreground">{{ $copy['name'] }}</label>
                    <input id="contact-name" type="text" class="h-11 w-full rounded-lg border border-border bg-background px-3 text-sm outline-none focus:border-accent">
                </div>
                <div class="space-y-2">
                    <label for="contact-phone" class="text-xs font-bold text-foreground">{{ $copy['phone'] }}</label>
                    <input id="contact-phone" type="text" dir="ltr" class="h-11 w-full rounded-lg border border-border bg-background px-3 text-right text-sm outline-none focus:border-accent">
                </div>
                <div class="space-y-2">
                    <label for="contact-service" class="text-xs font-bold text-foreground">{{ $copy['service'] }}</label>
                    <select id="contact-service" class="h-11 w-full rounded-lg border border-border bg-background px-3 text-sm outline-none focus:border-accent">
                        <option>{{ $copy['shipping'] }}</option>
                        <option>{{ $copy['customs'] }}</option>
                        <option>{{ $copy['door'] }}</option>
                    </select>
                </div>
                <div class="space-y-2 md:col-span-2">
                    <label for="contact-message" class="text-xs font-bold text-foreground">{{ $copy['message'] }}</label>
                    <textarea id="contact-message" rows="5" placeholder="{{ $copy['placeholder'] }}" class="w-full rounded-lg border border-border bg-background p-3 text-sm outline-none focus:border-accent"></textarea>
                </div>
                <div class="md:col-span-2">
                    <button type="submit" class="motion-button inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-gradient-accent px-5 text-sm font-black text-accent-foreground shadow-glow">
                        <i data-lucide="send" class="h-4 w-4"></i>
                        {{ $copy['send_whatsapp'] }}
                    </button>
                </div>
            </form>
        </div>

        <aside class="space-y-4">
            @foreach($contactCards as $card)
                <div class="motion-card rounded-lg border border-border bg-card p-5 shadow-card">
                    <div class="flex items-start gap-3">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-accent/10 text-accent">
                            <i data-lucide="{{ $card['icon'] }}" class="h-5 w-5"></i>
                        </span>
                        <div class="min-w-0">
                            <h3 class="text-sm font-black text-foreground">{{ $card['label'] }}</h3>
                            <div class="mt-1 break-words font-bold text-primary dark:text-accent" dir="{{ $card['dir'] }}">
                                @if(isset($card['href']))
                                    <a href="{{ $card['href'] }}">{{ $card['value'] }}</a>
                                @else
                                    {{ $card['value'] }}
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="rounded-lg border border-accent/20 bg-accent/5 p-5">
                <h3 class="text-sm font-black text-foreground">{{ $copy['visit'] }}</h3>
                <div class="mt-3 grid gap-2">
                    <a href="{{ route('public.ports') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-border bg-card px-3 text-xs font-bold hover:bg-secondary">
                        <i data-lucide="anchor" class="h-4 w-4 text-accent"></i>
                        {{ $copy['ports'] }}
                    </a>
                    <a href="{{ route('schedules') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-border bg-card px-3 text-xs font-bold hover:bg-secondary">
                        <i data-lucide="calendar-days" class="h-4 w-4 text-accent"></i>
                        {{ $copy['schedules'] }}
                    </a>
                </div>
            </div>
        </aside>
    </section>
</div>
@endsection

@section('scripts')
<script>
    document.getElementById('public-contact-form')?.addEventListener('submit', function (event) {
        event.preventDefault();

        const messageParts = [
            'طلب تواصل من الموقع',
            'الشركة: ' + (document.getElementById('contact-company')?.value || '-'),
            'المسؤول: ' + (document.getElementById('contact-name')?.value || '-'),
            'الهاتف: ' + (document.getElementById('contact-phone')?.value || '-'),
            'الخدمة: ' + (document.getElementById('contact-service')?.value || '-'),
            'التفاصيل: ' + (document.getElementById('contact-message')?.value || '-'),
        ];

        window.open('https://wa.me/' + @json(config('company.whatsapp')) + '?text=' + encodeURIComponent(messageParts.join('\n')), '_blank', 'noopener');
    });
</script>
@endsection
