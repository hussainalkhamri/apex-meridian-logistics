<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-door-volume-form]').forEach(form => {
            const unitInput = form.querySelector('[name="package_dimension_unit"]');
            const fields = {
                volume: { input: form.querySelector('[name="volume_cbm"], [name="volumeCbm"]'), precision: 4 },
                weight: { input: form.querySelector('[name="gross_weight_kg"], [name="weightKg"]'), precision: 2 },
                pieces: { input: form.querySelector('[name="packages_count"]'), precision: 0 },
            };
            Object.values(fields).forEach(field => {
                field.manual = field.input?.value || '';
                field.calculated = false;
            });
            const rounded = (value, precision) => Math.round((value + Number.EPSILON) * 10 ** precision) / 10 ** precision;

            function syncMetrics() {
                const dimensionFactor = unitInput?.value === 'm' ? 100 : 1;
                const totals = { volume: 0, weight: 0, pieces: 0 };
                form.querySelectorAll('[data-package-row], [data-customer-package-row]').forEach(row => {
                    const value = key => Math.max(0, Number(row.querySelector(`[name$="[${key}]"]`)?.value || 0));
                    const quantity = Math.max(1, Math.trunc(value('quantity')));
                    const length = rounded(value('length_cm') * dimensionFactor, 2);
                    const width = rounded(value('width_cm') * dimensionFactor, 2);
                    const height = rounded(value('height_cm') * dimensionFactor, 2);
                    const unitWeight = rounded(value('weight_kg'), 2);
                    if (length > 0 && width > 0 && height > 0) {
                        totals.volume += quantity * length * width * height / 1000000;
                    }
                    totals.weight += quantity * unitWeight;
                    if (value('quantity') > 0 || length > 0 || width > 0 || height > 0 || unitWeight > 0) {
                        totals.pieces += quantity;
                    }
                    ['length_cm', 'width_cm', 'height_cm'].forEach(key => {
                        const input = row.querySelector(`[name$="[${key}]"]`);
                        if (input) input.step = dimensionFactor === 100 ? '0.0001' : '0.01';
                    });
                });

                Object.entries(fields).forEach(([key, field]) => {
                    if (!field.input) return;
                    const hasValue = totals[key] > 0;
                    if (hasValue) {
                        if (!field.calculated) field.manual = field.input.value;
                        field.input.value = String(rounded(totals[key], field.precision));
                    } else if (field.calculated) {
                        field.input.value = field.manual;
                    }
                    field.calculated = hasValue;
                    field.input.readOnly = hasValue;
                    field.input.classList.toggle('bg-secondary/30', hasValue);
                });

                const option = unitInput?.selectedOptions[0];
                ['length', 'width', 'height'].forEach(key => {
                    const header = form.querySelector(`[data-package-${key}-label]`);
                    if (header && option?.dataset[`${key}Label`]) {
                        header.textContent = option.dataset[`${key}Label`];
                    }
                });
            }

            form.addEventListener('input', event => {
                Object.values(fields).forEach(field => {
                    if (event.target === field.input && !field.calculated) field.manual = field.input.value;
                });
                if (event.target.closest('[data-package-row], [data-customer-package-row]')) {
                    syncMetrics();
                }
            });
            form.addEventListener('change', event => {
                if (event.target === unitInput || event.target.closest('[data-package-row], [data-customer-package-row]')) {
                    syncMetrics();
                }
            });
            form.addEventListener('submit', syncMetrics, true);
            syncMetrics();
        });
    });
</script>
