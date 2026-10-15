<header class="company-letterhead">
    <img src="{{ asset(config('company.letterhead_logo')) }}" alt="{{ config('company.name.' . app()->getLocale()) }}" class="company-letterhead-logo">
    <div class="company-letterhead-details">
        <strong>{{ config('company.name.' . app()->getLocale()) }}</strong>
        <p>{{ config('company.address.' . app()->getLocale()) }}</p>
        <p dir="ltr">{{ config('company.landline.display') }} · {{ config('company.phones.0.display') }}</p>
        <p dir="ltr">{{ config('company.email') }}</p>
        <p dir="ltr">{{ parse_url(config('company.website'), PHP_URL_HOST) ?? config('company.website') }}</p>
        @if(config('company.tax_number'))
            <p>{{ app()->getLocale() === 'ar' ? 'الرقم الضريبي' : 'Tax number' }}: {{ config('company.tax_number') }}</p>
        @endif
    </div>
</header>
