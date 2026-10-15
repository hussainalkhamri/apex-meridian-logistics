<script>
    (() => {
        document.querySelectorAll('[data-booking-filter-form]').forEach((form) => {
            const searchInput = form.querySelector('[data-booking-search-input]');
            const transportFilter = form.querySelector('[data-booking-transport-filter]');
            const seaWrapper = form.querySelector('[data-booking-sea-type-wrapper]');
            const seaFilter = form.querySelector('[data-booking-sea-type-filter]');
            const statusFilter = form.querySelector('[data-booking-status-filter]');

            const submit = () => form.submit();

            const syncSeaFilter = (submitAfterSync = false) => {
                const isSea = transportFilter?.value === 'sea';

                seaWrapper?.classList.toggle('hidden', !isSea);
                if (seaFilter) {
                    seaFilter.disabled = !isSea;
                    if (!isSea) {
                        seaFilter.value = 'all';
                    }
                }

                if (submitAfterSync) {
                    submit();
                }
            };

            searchInput?.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                }
            });

            transportFilter?.addEventListener('change', () => syncSeaFilter(true));
            seaFilter?.addEventListener('change', submit);
            statusFilter?.addEventListener('change', submit);
            syncSeaFilter();
        });
    })();
</script>
