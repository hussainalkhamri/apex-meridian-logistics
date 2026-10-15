function setupCarrierVesselPicker() {
    const picker = document.querySelector('[data-carrier-vessel-picker]');
    if (!picker || picker.dataset.ready === 'true') return;

    picker.dataset.ready = 'true';

    const input = picker.querySelector('[data-carrier-vessel-input]');
    const menu = picker.querySelector('[data-carrier-vessel-menu]');
    const toggle = picker.querySelector('[data-carrier-vessel-toggle]');
    const options = Array.from(picker.querySelectorAll('[data-carrier-vessel-option]'));
    const empty = picker.querySelector('[data-carrier-vessel-empty]');

    const normalize = value => (value || '').toString().trim().toLocaleLowerCase();

    const filterOptions = () => {
        const term = normalize(input.value);
        let visibleCount = 0;

        options.forEach(option => {
            const name = normalize(option.dataset.carrierVesselName);
            const isVisible = !term || name.includes(term);
            option.classList.toggle('hidden', !isVisible);
            if (isVisible) visibleCount += 1;
        });

        if (empty) {
            empty.classList.toggle('hidden', visibleCount > 0);
        }
    };

    const openMenu = () => {
        filterOptions();
        menu.classList.remove('hidden');
        if (toggle) toggle.setAttribute('aria-expanded', 'true');
    };

    const closeMenu = () => {
        menu.classList.add('hidden');
        if (toggle) toggle.setAttribute('aria-expanded', 'false');
    };

    const chooseOption = option => {
        input.value = option.dataset.carrierVesselName || option.textContent.trim();
        closeMenu();
        input.focus();
    };

    input.addEventListener('focus', openMenu);
    input.addEventListener('input', openMenu);
    input.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            closeMenu();
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            const firstVisible = options.find(option => !option.classList.contains('hidden'));
            if (firstVisible) firstVisible.focus();
        }
    });

    if (toggle) {
        toggle.addEventListener('click', () => {
            if (menu.classList.contains('hidden')) {
                input.focus();
                openMenu();
            } else {
                closeMenu();
            }
        });
    }

    options.forEach(option => {
        option.addEventListener('mousedown', event => {
            event.preventDefault();
            chooseOption(option);
        });

        option.addEventListener('keydown', event => {
            if (event.key === 'Enter') {
                event.preventDefault();
                chooseOption(option);
            }

            if (event.key === 'Escape') {
                closeMenu();
                input.focus();
            }

            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                const visibleOptions = options.filter(item => !item.classList.contains('hidden'));
                const currentIndex = visibleOptions.indexOf(option);
                const nextOption = event.key === 'ArrowDown'
                    ? visibleOptions[currentIndex + 1]
                    : visibleOptions[currentIndex - 1];

                if (nextOption) {
                    nextOption.focus();
                } else {
                    input.focus();
                }
            }
        });
    });

    document.addEventListener('click', event => {
        if (!picker.contains(event.target)) {
            closeMenu();
        }
    });

    window.closeCarrierVesselPickerMenu = closeMenu;
    window.refreshCarrierVesselPicker = () => {
        filterOptions();
        closeMenu();
    };
}

setupCarrierVesselPicker();
