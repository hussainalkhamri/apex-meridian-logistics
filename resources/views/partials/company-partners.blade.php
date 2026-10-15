<section class="company-partners" aria-labelledby="company-partners-title">
    <div class="container mx-auto px-4">
        <h2 id="company-partners-title">{{ app()->getLocale() === 'ar' ? 'شعارات التعاون' : 'Cooperation' }}</h2>
        <div class="company-partner-logos">
            @foreach(config('company.partners') as $partner)
                <img src="{{ asset($partner['logo']) }}" alt="{{ $partner['name'] }}" loading="lazy" width="240" height="105">
            @endforeach
        </div>
    </div>
</section>
