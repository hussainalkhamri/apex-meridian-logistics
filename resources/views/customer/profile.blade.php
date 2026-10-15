@extends('layouts.customer')

@section('title', __('الملف الشخصي'))

@section('content')
@php
    $avatarUrl = $customer->avatar_path ? asset('storage/' . $customer->avatar_path) : null;
    $customerInitial = mb_substr($customer->company ?: 'ع', 0, 1);
@endphp

<div class="space-y-6">
    <section class="portal-intro relative overflow-hidden rounded-xl bg-hero text-white shadow-card">
        <div class="absolute inset-0 bg-[linear-gradient(90deg,rgba(255,255,255,0.12),transparent)]"></div>
        <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-bold backdrop-blur">
                    <i data-lucide="user-round-cog" class="h-3.5 w-3.5 text-accent"></i>
                    {{ __('إدارة حساب العميل') }}
                </span>
                <h1 class="mt-3 text-2xl font-bold">{{ __('portal.profile') }}</h1>
                <p class="mt-3 max-w-2xl text-sm leading-8 text-white/80">
                    {{ __('عدل بيانات الشركة وبيانات التواصل، وغير كلمة السر، وارفع صورة تظهر داخل بوابة العميل.') }}
                </p>
            </div>

            <div class="flex items-center gap-4 rounded-xl border border-white/20 bg-white/10 p-4 backdrop-blur">
                @if($avatarUrl)
                    <img id="profile-avatar-preview" src="{{ $avatarUrl }}" alt="{{ __('صورة العميل') }}" class="h-16 w-16 shrink-0 rounded-full border border-white/30 object-cover shadow-sm">
                @else
                    <div id="profile-avatar-placeholder" class="grid h-16 w-16 shrink-0 place-items-center rounded-full border border-white/20 bg-white/15 text-xl font-black">
                        {{ $customerInitial }}
                    </div>
                    <img id="profile-avatar-preview" src="" alt="{{ __('معاينة الصورة') }}" class="hidden h-16 w-16 shrink-0 rounded-full border border-white/30 object-cover shadow-sm">
                @endif
                <div class="min-w-0">
                    <div class="truncate text-base font-black">{{ $customer->company }}</div>
                    <div class="mt-1 truncate text-xs text-white/70">{{ $customer->email }}</div>
                    <div class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-white/10 px-2.5 py-1 text-[10px] font-bold text-white/80">
                        <i data-lucide="clock-3" class="h-3 w-3"></i>
                        {{ __('آخر دخول:') }} {{ $customer->last_login_at?->format('Y-m-d H:i') ?: __('غير متوفر') }}
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[1.25fr_0.75fr]">
        <section class="rounded-xl border border-border bg-card p-5 shadow-card md:p-6">
            <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-base font-black">{{ __('بيانات الحساب') }}</h2>
                    <p class="mt-1 text-xs leading-6 text-muted-foreground">{{ __('هذه البيانات تستخدم في الطلبات والفواتير والتواصل مع فريق التشغيل.') }}</p>
                </div>
                @if($avatarUrl)
                    <form action="{{ route('customer.profile.avatar.delete') }}" method="POST" data-confirm="{{ __('هل تريد حذف الصورة الشخصية؟') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex h-9 w-fit items-center justify-center gap-2 rounded-lg border border-destructive/30 px-3 text-xs font-black text-destructive hover:bg-destructive/10">
                            <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                            {{ __('حذف الصورة') }}
                        </button>
                    </form>
                @endif
            </div>

            <form data-dirty-check action="{{ route('customer.profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="grid gap-4 md:grid-cols-2">
                    <h3 class="border-b border-border pb-2 text-sm font-semibold md:col-span-2">{{ __('portal.company') }}</h3>
                    <div class="space-y-2">
                        <label for="company" class="text-xs font-black">{{ __('اسم الشركة *') }}</label>
                        <input id="company" name="company" type="text" value="{{ old('company', $customer->company) }}" required class="h-11 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-accent">
                        @error('company')
                            <p class="text-xs font-bold text-destructive">{{ $message }}</p>
                        @enderror
                    </div>

                    <h3 class="border-b border-border pb-2 pt-2 text-sm font-semibold md:col-span-2">{{ __('portal.contact') }}</h3>
                    <div class="space-y-2">
                        <label for="contact_name" class="text-xs font-black">{{ __('اسم المسؤول') }}</label>
                        <input id="contact_name" name="contact_name" type="text" value="{{ old('contact_name', $customer->contact_name) }}" class="h-11 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-accent">
                        @error('contact_name')
                            <p class="text-xs font-bold text-destructive">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="email" class="text-xs font-black">{{ __('البريد الإلكتروني *') }}</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $customer->email) }}" required dir="ltr" autocomplete="email" class="h-11 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-accent">
                        @error('email')
                            <p class="text-xs font-bold text-destructive">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="phone" class="text-xs font-black">{{ __('رقم الهاتف') }}</label>
                        <input id="phone" name="phone" type="text" value="{{ old('phone', $customer->phone) }}" dir="ltr" autocomplete="tel" class="h-11 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-accent">
                        @error('phone')
                            <p class="text-xs font-bold text-destructive">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="rounded-xl border border-dashed border-border bg-secondary/25 p-4">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <label for="avatar" class="text-xs font-black">{{ __('الصورة الشخصية') }}</label>
                            <p class="mt-1 text-xs leading-6 text-muted-foreground">{{ __('JPG أو PNG أو WEBP، الحجم الأقصى 2MB.') }}</p>
                        </div>
                        <input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp" class="w-full max-w-sm rounded-lg border border-border bg-background px-3 py-2 text-xs file:me-3 file:rounded-md file:border-0 file:bg-accent file:px-3 file:py-1.5 file:text-xs file:font-black file:text-accent-foreground">
                    </div>
                    @error('avatar')
                        <p class="mt-2 text-xs font-bold text-destructive">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-gradient-accent px-5 text-xs font-black text-accent-foreground shadow-sm transition hover:opacity-90">
                        <i data-lucide="save" class="h-4 w-4"></i>
                        {{ __('حفظ التعديلات') }}
                    </button>
                </div>
            </form>
        </section>

        <section class="rounded-xl border border-border bg-card p-5 shadow-card md:p-6">
            <div class="mb-5">
                <h2 class="text-base font-black">{{ __('تغيير كلمة السر') }}</h2>
                <p class="mt-1 text-xs leading-6 text-muted-foreground">{{ __('استخدم كلمة سر قوية للحفاظ على حسابك وطلباتك.') }}</p>
            </div>

            <form data-dirty-check action="{{ route('customer.profile.password') }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <div class="space-y-2">
                    <label for="current_password" class="text-xs font-black">{{ __('كلمة السر الحالية *') }}</label>
                    <input id="current_password" name="current_password" type="password" required autocomplete="current-password" class="h-11 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-accent">
                    @error('current_password')
                        <p class="text-xs font-bold text-destructive">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label for="password" class="text-xs font-black">{{ __('كلمة السر الجديدة *') }}</label>
                    <input id="password" name="password" type="password" required autocomplete="new-password" class="h-11 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-accent">
                    @error('password')
                        <p class="text-xs font-bold text-destructive">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label for="password_confirmation" class="text-xs font-black">{{ __('تأكيد كلمة السر *') }}</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="h-11 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-accent">
                </div>

                <button type="submit" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-primary px-5 text-xs font-black text-primary-foreground transition hover:bg-primary/90">
                    <i data-lucide="key-round" class="h-4 w-4"></i>
                    {{ __('تحديث كلمة السر') }}
                </button>
            </form>
        </section>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const avatarInput = document.getElementById('avatar');
        const avatarPreview = document.getElementById('profile-avatar-preview');
        const avatarPlaceholder = document.getElementById('profile-avatar-placeholder');

        if (!avatarInput || !avatarPreview) {
            return;
        }

        avatarInput.addEventListener('change', () => {
            const file = avatarInput.files?.[0];

            if (!file) {
                return;
            }

            const reader = new FileReader();

            reader.onload = (event) => {
                avatarPreview.src = event.target.result;
                avatarPreview.classList.remove('hidden');
                avatarPlaceholder?.classList.add('hidden');
            };

            reader.readAsDataURL(file);
        });
    });
</script>
@endsection
