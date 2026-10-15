<div class="company-document-footer">
    <div class="company-document-partners" aria-label="{{ app()->getLocale() === 'ar' ? 'شعارات التعاون' : 'Cooperation logos' }}">
        <span>{{ app()->getLocale() === 'ar' ? 'شعارات التعاون' : 'Cooperation' }}</span>
        @foreach(config('company.partners') as $partner)
            <img src="{{ ($pdfAssets ?? false) ? public_path($partner['logo']) : asset($partner['logo']) }}" alt="{{ $partner['name'] }}">
        @endforeach
    </div>
    <div class="company-document-contacts">
        <div dir="ltr">
            @foreach(config('company.phones') as $phone)
                <a href="tel:{{ $phone['tel'] }}">{{ $phone['display'] }}</a>
            @endforeach
            <a href="tel:{{ config('company.landline.tel') }}">{{ config('company.landline.display') }}</a>
        </div>
        <div dir="ltr">
            @foreach(config('company.emails') as $email)
                <a href="mailto:{{ $email }}">{{ $email }}</a>
            @endforeach
        </div>
        <p>{{ config('company.address.' . app()->getLocale()) }}</p>
        <a href="{{ config('company.website') }}" dir="ltr">{{ parse_url(config('company.website'), PHP_URL_HOST) ?? config('company.website') }}</a>
    </div>
</div>
