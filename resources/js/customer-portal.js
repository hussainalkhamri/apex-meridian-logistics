document.addEventListener('DOMContentLoaded', () => {
    if (!document.body.classList.contains('customer-portal')) return;

    const strings = JSON.parse(document.getElementById('portal-strings')?.textContent || '{}');
    const t = (key) => strings[key] || key;
    const toast = document.createElement('div');
    toast.className = 'portal-toast';
    toast.hidden = true;
    toast.setAttribute('role', 'status');
    toast.setAttribute('aria-live', 'polite');
    document.body.append(toast);
    let toastTimeout;
    const announce = (message, tone = 'success') => {
        clearTimeout(toastTimeout);
        toast.textContent = message;
        toast.dataset.tone = tone;
        toast.hidden = false;
        toastTimeout = setTimeout(() => { toast.hidden = true; }, 5000);
    };

    const sidebar = document.getElementById('customer-sidebar');
    const backdrop = document.getElementById('customer-sidebar-backdrop');
    const menuButton = document.getElementById('customer-menu-toggle');
    const desktop = matchMedia('(min-width: 1024px)');
    const shell = document.getElementById('customer-shell');
    let drawerTrigger;
    const closeDrawer = (restore = true) => {
        if (!sidebar) return;
        sidebar.dataset.open = 'false';
        sidebar.inert = !desktop.matches;
        sidebar.removeAttribute('role');
        sidebar.removeAttribute('aria-modal');
        backdrop.hidden = true;
        shell.inert = false;
        document.body.style.overflow = '';
        menuButton?.setAttribute('aria-expanded', 'false');
        if (restore && drawerTrigger?.isConnected) drawerTrigger.focus();
    };
    window.toggleCustomerSidebar = () => {
        if (!sidebar || desktop.matches) return;
        if (sidebar.dataset.open === 'true') return closeDrawer();
        drawerTrigger = document.activeElement;
        sidebar.inert = false;
        sidebar.dataset.open = 'true';
        sidebar.setAttribute('role', 'dialog');
        sidebar.setAttribute('aria-modal', 'true');
        backdrop.hidden = false;
        shell.inert = true;
        document.body.style.overflow = 'hidden';
        menuButton?.setAttribute('aria-expanded', 'true');
        sidebar.querySelector('button')?.focus();
    };
    if (sidebar) {
        closeDrawer(false);
        desktop.addEventListener('change', () => closeDrawer(false));
    }

    const bell = document.getElementById('customer-notification-bell-btn');
    const dropdown = document.getElementById('customer-notification-dropdown');
    const closeNotifications = () => {
        dropdown?.classList.add('hidden');
        bell?.setAttribute('aria-expanded', 'false');
    };
    bell?.addEventListener('click', () => {
        const open = dropdown.classList.toggle('hidden') === false;
        bell.setAttribute('aria-expanded', String(open));
    });
    document.addEventListener('click', (event) => {
        if (!bell?.contains(event.target) && !dropdown?.contains(event.target)) closeNotifications();
        const copy = event.target.closest('[data-copy-reference]');
        if (copy) {
            navigator.clipboard?.writeText(copy.dataset.copyReference)
                .then(() => announce(t('copied')))
                .catch(() => announce(t('copy_failed'), 'error'));
            if (!navigator.clipboard) announce(t('copy_failed'), 'error');
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            if (sidebar?.dataset.open === 'true') closeDrawer();
            if (dropdown && !dropdown.classList.contains('hidden')) { closeNotifications(); bell.focus(); }
        }
        if (event.key === 'Tab' && sidebar?.dataset.open === 'true') {
            const controls = [...sidebar.querySelectorAll('a, button')].filter(el => el.getClientRects().length && !el.disabled);
            const first = controls[0];
            const last = controls.at(-1);
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        }
    });

    const markAll = document.getElementById('customer-mark-all-read-btn');
    markAll?.addEventListener('click', async () => {
        if (markAll.disabled) return;
        markAll.disabled = true;
        markAll.setAttribute('aria-busy', 'true');
        try {
            const response = await fetch(markAll.dataset.url, {
                method: 'POST',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({}),
            });
            if (!response.ok || !(await response.json()).success) throw new Error('Notification update failed');
            // Refresh server-rendered counts and the notification center together.
            location.reload();
        } catch (error) {
            console.error(error);
            announce(t('error'), 'error');
            markAll.disabled = false;
            markAll.removeAttribute('aria-busy');
        }
    });

    // Enable stacked phone rows using the table's actual column headings.
    document.querySelectorAll('[data-responsive-table]').forEach(table => {
        const headings = [...table.querySelectorAll('thead th')].map(th => th.textContent.trim());
        table.querySelectorAll('tbody tr').forEach(row => {
            [...row.cells].forEach((cell, index) => { cell.dataset.label = headings[index] || t('actions'); });
        });
        table.classList.add('portal-table-ready');
    });

    document.querySelectorAll('[data-list-search]').forEach(input => {
        const table = document.getElementById(input.dataset.listSearch);
        const empty = document.querySelector(`[data-list-empty="${table.id}"]`);
        const unpaid = document.querySelector(`[data-unpaid-filter="${table.id}"]`);
        const filter = () => {
            let visible = 0;
            const query = input.value.trim().toLocaleLowerCase();
            table.querySelectorAll('tbody tr').forEach(row => {
                row.hidden = !row.textContent.toLocaleLowerCase().includes(query) || (unpaid?.checked && row.dataset.outstanding !== 'true');
                if (!row.hidden) visible++;
            });
            if (empty) empty.hidden = visible !== 0;
        };
        input.addEventListener('input', filter);
        unpaid?.addEventListener('change', filter);
    });

    // Apply a chip removal to the existing GET form, preserving all other filters.
    document.querySelectorAll('[data-filter-form]').forEach(form => {
        const chips = document.createElement('div');
        chips.className = 'portal-filter-chips';
        [...form.elements].filter(el => el.name && el.value && el.value !== 'all' && !['submit', 'hidden'].includes(el.type)).forEach(field => {
            const chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'portal-filter-chip';
            const label = field.labels?.[0]?.textContent.trim() || field.getAttribute('aria-label') || '';
            const value = field.tagName === 'SELECT' ? field.selectedOptions[0].textContent : field.value;
            chip.textContent = `${label ? `${label}: ` : ''}${value} ×`;
            chip.addEventListener('click', () => { field.value = [...(field.options || [])].some(o => o.value === 'all') ? 'all' : ''; form.requestSubmit(); });
            chips.append(chip);
        });
        if (chips.childElementCount) form.after(chips);
    });

    // Connect nearby labels and server errors without changing submitted field names.
    document.querySelectorAll('input[type="password"]').forEach(field => {
        if (field.parentElement.querySelector('[data-password-toggle]')) return;
        const wrapper = document.createElement('div');
        wrapper.className = 'relative';
        field.before(wrapper);
        wrapper.append(field);
        field.style.paddingInlineEnd = '3rem';
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'portal-icon-button absolute end-1 top-0';
        button.setAttribute('aria-label', t('show_password'));
        button.setAttribute('aria-pressed', 'false');
        button.innerHTML = '<i data-lucide="eye" class="h-4 w-4"></i>';
        wrapper.append(button);
        button.addEventListener('click', () => {
            const show = field.type === 'password';
            field.type = show ? 'text' : 'password';
            button.setAttribute('aria-pressed', String(show));
            button.setAttribute('aria-label', t(show ? 'hide_password' : 'show_password'));
            field.focus();
        });
    });
    document.querySelectorAll('input:not([type="hidden"]), select, textarea').forEach((field, index) => {
        if (!field.id) field.id = `portal-field-${index}`;
        const group = field.closest('.space-y-2, .space-y-1\\.5');
        const label = group?.querySelector('label:not([for])');
        if (label && !label.querySelector('input,select,textarea')) label.htmlFor = field.id;
        const error = group?.querySelector('p.text-destructive');
        if (error) {
            error.id = `${field.id}-error`;
            field.setAttribute('aria-invalid', 'true');
            field.setAttribute('aria-describedby', error.id);
        }
        if (!field.labels?.length && !field.hasAttribute('aria-label') && field.placeholder) field.setAttribute('aria-label', field.placeholder);
        field.addEventListener('input', () => field.removeAttribute('aria-invalid'));
    });
    document.querySelectorAll('[data-error-field]').forEach(link => {
        const name = link.dataset.errorField.replace(/\.([^.]*)/g, '[$1]');
        const field = [...document.querySelectorAll('[name]')].find(el => el.name === name && el.type !== 'hidden' && !el.disabled);
        if (!field) return;
        field.setAttribute('aria-invalid', 'true');
        link.href = `#${field.id}`;
        link.addEventListener('click', () => field.focus());
    });

    document.querySelectorAll('input[type="file"]').forEach(input => {
        const files = document.createElement('ul');
        files.className = 'portal-file-list';
        files.setAttribute('aria-live', 'polite');
        input.after(files);
        input.addEventListener('change', () => {
            files.replaceChildren();
            [...input.files].forEach(file => {
                const item = document.createElement('li');
                item.textContent = `${file.name} · ${new Intl.NumberFormat(document.documentElement.lang, { maximumFractionDigits: 1 }).format(file.size / 1024)} KB`;
                files.append(item);
            });
        });
    });

    const dirtyForms = [...document.querySelectorAll('[data-dirty-check]')];
    const signature = form => JSON.stringify([...new FormData(form)].filter(([name]) => !['_token', '_method'].includes(name)).map(([name, value]) => [name, value instanceof File ? (value.name ? [value.name, value.size, value.lastModified] : null) : value]));
    const original = new Map(dirtyForms.map(form => [form, signature(form)]));
    let navigating = false;
    addEventListener('beforeunload', event => {
        if (!navigating && dirtyForms.some(form => signature(form) !== original.get(form))) {
            event.preventDefault();
            event.returnValue = '';
        }
    });

    const review = document.getElementById('portal-request-review');
    let reviewedForm;
    let reviewedSubmitter;
    let confirmed = false;
    document.querySelectorAll('[data-review-request]').forEach(form => {
        form.addEventListener('submit', event => {
            if (event.defaultPrevented) return;
            if (confirmed || !review) return;
            event.preventDefault();
            if (!form.reportValidity()) return;
            reviewedForm = form;
            reviewedSubmitter = event.submitter;
            const details = review.querySelector('dl');
            details.replaceChildren();
            // Show every enabled visible input, including each cargo/container row.
            const summaryLabels = { pol: t('origin'), pod: t('destination'), origin_country: t('origin'), destination_country: t('destination'), shipmentType: t('shipment_type'), route_mode: t('shipping_terms'), shipping_category: t('method') };
            [...form.elements].filter(field => field.name && !field.disabled && !['submit', 'button'].includes(field.type) && (summaryLabels[field.name] || (field.type !== 'hidden' && field.getClientRects().length))).forEach(field => {
                if (['checkbox', 'radio'].includes(field.type) && !field.checked) return;
                if (field.name === 'shipmentType' && field.value.toLowerCase() === form.elements.namedItem('shipping_category')?.value.toLowerCase()) return;
                let value = field.type === 'file' ? [...field.files].map(file => file.name).join('\n') : field.tagName === 'SELECT' ? field.selectedOptions[0]?.textContent : field.value;
                if (['checkbox', 'radio'].includes(field.type)) value = t('yes');
                if (field.name === 'shipping_category') {
                    const tab = field.value === 'door_to_door' ? 'contracting' : field.value;
                    value = [...document.querySelectorAll('[data-customer-tab]')].find(button => button.dataset.customerTab === tab)?.textContent.trim() || value;
                }
                if (!value?.trim()) return;
                const row = document.createElement('div');
                const label = document.createElement('dt');
                const content = document.createElement('dd');
                const column = field.closest('td');
                const heading = column ? field.closest('table')?.querySelectorAll('thead th')[column.cellIndex]?.textContent : null;
                label.textContent = (field.labels?.[0]?.textContent || summaryLabels[field.name] || heading || field.getAttribute('aria-label') || field.placeholder || t('files')).replace('*', '').trim();
                content.textContent = value;
                row.append(label, content);
                details.append(row);
            });
            review.showModal();
        });
    });
    review?.querySelectorAll('[data-close-review]').forEach(button => button.addEventListener('click', () => review.close()));
    review?.querySelector('[data-confirm-review]')?.addEventListener('click', () => {
        if (!reviewedForm || reviewedForm.dataset.submitting === 'true') return;
        confirmed = true;
        review.close();
        reviewedForm.requestSubmit(reviewedSubmitter);
        confirmed = false;
    });
    review?.addEventListener('close', () => reviewedSubmitter?.focus());

    // Wait for form-specific validation/review before marking a native POST busy.
    document.querySelectorAll('form[data-confirm]').forEach(form => {
        form.addEventListener('submit', event => {
            if (!event.defaultPrevented && !window.confirm(form.dataset.confirm)) event.preventDefault();
        });
    });
    document.addEventListener('submit', event => {
        if (event.target.dataset.submitting === 'true') event.preventDefault();
    }, true);
    document.addEventListener('submit', event => {
        const form = event.target;
        if (event.defaultPrevented || form.method.toLowerCase() === 'get') return;
        form.dataset.submitting = 'true';
        form.setAttribute('aria-busy', 'true');
        navigating = true;
        const button = event.submitter;
        if (button) { button.classList.add('portal-busy'); button.setAttribute('aria-disabled', 'true'); }
    });
    addEventListener('pageshow', () => {
        navigating = false;
        document.querySelectorAll('form[data-submitting]').forEach(form => { delete form.dataset.submitting; form.removeAttribute('aria-busy'); });
        document.querySelectorAll('.portal-busy').forEach(button => { button.classList.remove('portal-busy'); button.removeAttribute('aria-disabled'); });
    });
});
