@extends('layouts.app')

@section('title', __('messages.agents_page_title'))

@section('content')
<div>
    <section class="bg-hero py-16 text-white">
        <div class="container mx-auto px-4">
            <span class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-xs text-white border border-white/10">{{ __('messages.agents_badge') }}</span>
            <h1 class="mt-4 text-3xl font-extrabold md:text-4xl">{{ __('messages.agents_heading') }}</h1>
            <p class="mt-3 max-w-2xl text-white/80">{{ __('messages.agents_intro') }}</p>
        </div>
    </section>

    <section class="container mx-auto px-4 py-12">
        <div class="grid gap-8 lg:grid-cols-2">
            <div class="rounded-3xl border border-border bg-card p-8 shadow-elegant">
                <div class="flex items-center gap-3 text-primary">
                    <i data-lucide="home" class="h-6 w-6"></i>
                    <h2 class="text-xl font-bold">{{ __('messages.local_agents_heading') }}</h2>
                </div>
                <p class="mt-3 text-sm text-muted-foreground">{{ __('messages.local_agents_intro') }}</p>

                <div class="mt-8 space-y-5">
                    @php
                        $localAgents = [
                            ['name' => config('company.name.' . app()->getLocale()), 'phone' => config('company.phones.0.display'), 'email' => config('company.email'), 'region' => config('company.address.' . app()->getLocale())],
                            ['name' => __('messages.agent_company_yemeni'), 'phone' => '+967 730 567 890', 'email' => 'local2@yamenshipping.com', 'region' => __('messages.agent_region_aden')],
                            ['name' => __('messages.agent_company_ports_branch'), 'phone' => '+967 770 123 456', 'email' => 'local3@ports-yd.com', 'region' => __('messages.agent_region_hodeidah')],
                        ];
                    @endphp

                    @foreach($localAgents as $agent)
                        <div class="rounded-3xl border border-border/80 bg-background p-5 shadow-sm transition hover:border-accent hover:shadow-elegant">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <h3 class="text-lg font-bold text-foreground">{{ $agent['name'] }}</h3>
                                    <p class="text-xs uppercase tracking-wider text-muted-foreground">{{ __('messages.agent_region_label') }} {{ $agent['region'] }}</p>
                                </div>
                                <span class="rounded-full bg-accent/10 px-3 py-1 text-xs font-semibold text-accent">{{ __('messages.agent_local_type') }}</span>
                            </div>
                            <div class="mt-4 space-y-2 text-sm text-foreground/80">
                                <p><span class="font-semibold text-foreground">{{ __('messages.agent_phone_label') }}</span> {{ $agent['phone'] }}</p>
                                <p><span class="font-semibold text-foreground">{{ __('messages.agent_email_label') }}</span> {{ $agent['email'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-3xl border border-border bg-card p-8 shadow-elegant">
                <div class="flex items-center gap-3 text-primary">
                    <i data-lucide="globe" class="h-6 w-6"></i>
                    <h2 class="text-xl font-bold">{{ __('messages.external_agents_heading') }}</h2>
                </div>
                <p class="mt-3 text-sm text-muted-foreground">{{ __('messages.external_agents_intro') }}</p>

                <div class="mt-8 space-y-5">
                    @php
                        $externalAgents = [
                            ['name' => __('messages.agent_company_shanghai'), 'phone' => '+86 21 1234 5678', 'email' => 'shanghai@global-logistics.com', 'country' => __('messages.agent_country_china')],
                            ['name' => __('messages.agent_company_jebel_ali'), 'phone' => '+971 4 876 5432', 'email' => 'jaf@jaforwarders.ae', 'country' => __('messages.agent_country_uae')],
                            ['name' => __('messages.agent_company_rotterdam'), 'phone' => '+31 10 987 6543', 'email' => 'rotterdam@oceanpartners.nl', 'country' => __('messages.agent_country_netherlands')],
                        ];
                    @endphp

                    @foreach($externalAgents as $agent)
                        <div class="rounded-3xl border border-border/80 bg-background p-5 shadow-sm transition hover:border-accent hover:shadow-elegant">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <h3 class="text-lg font-bold text-foreground">{{ $agent['name'] }}</h3>
                                    <p class="text-xs uppercase tracking-wider text-muted-foreground">{{ __('messages.agent_country_label') }} {{ $agent['country'] }}</p>
                                </div>
                                <span class="rounded-full bg-accent/10 px-3 py-1 text-xs font-semibold text-accent">{{ __('messages.agent_external_type') }}</span>
                            </div>
                            <div class="mt-4 space-y-2 text-sm text-foreground/80">
                                <p><span class="font-semibold text-foreground">{{ __('messages.agent_phone_label') }}</span> {{ $agent['phone'] }}</p>
                                <p><span class="font-semibold text-foreground">{{ __('messages.agent_email_label') }}</span> {{ $agent['email'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="mt-12 rounded-3xl border border-border bg-card p-8 shadow-elegant">
            <h2 class="text-xl font-bold text-foreground">{{ __('messages.need_agent_heading') }}</h2>
            <p class="mt-3 text-sm text-muted-foreground">{{ __('messages.need_agent_desc') }}</p>
            <div class="mt-6 grid gap-4 md:grid-cols-3">
                <div class="rounded-2xl bg-secondary/10 p-5">
                    <div class="text-sm font-bold text-foreground">{{ __('messages.agent_support_general') }}</div>
                    <p class="mt-2 text-sm text-muted-foreground">{{ config('company.email') }}</p>
                </div>
                <div class="rounded-2xl bg-secondary/10 p-5">
                    <div class="text-sm font-bold text-foreground">{{ __('messages.agent_support_phone') }}</div>
                    <p class="mt-2 text-sm text-muted-foreground">{{ config('company.phones.0.display') }}</p>
                </div>
                <div class="rounded-2xl bg-secondary/10 p-5">
                    <div class="text-sm font-bold text-foreground">{{ __('messages.agent_support_platform') }}</div>
                    <p class="mt-2 text-sm text-muted-foreground">{{ config('company.website') }}</p>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

