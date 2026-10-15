import { createIcons, icons } from 'lucide';
import './customer-portal.js';
import './notifications.js';
import './orders-export.js';

window.lucide = {
    createIcons: (options) => createIcons(options || { icons }),
    icons
};

document.addEventListener('DOMContentLoaded', () => {
    window.lucide.createIcons();

    document.querySelectorAll('[data-tracking-map-open]').forEach((button) => {
        button.addEventListener('click', async () => {
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            try {
                const { openTrackingMap } = await import('./tracking-map.js');
                await openTrackingMap(document.querySelector('[data-tracking-map]'));
            } catch (error) {
                window.showGlobalToast?.(document.documentElement.lang === 'ar' ? 'تعذر تحميل الخريطة. أعد المحاولة.' : 'Unable to load the map. Please try again.', 'error');
            } finally {
                button.disabled = false;
                button.removeAttribute('aria-busy');
            }
        });
    });

    function applyMotionEnhancements(root = document) {
        if (document.body.classList.contains('customer-portal')) return;
        const scope = root.nodeType === 1 ? root : document;

        scope.querySelectorAll('main').forEach((element) => {
            element.classList.add('motion-page');
        });

        scope.querySelectorAll('button, a.inline-flex, a.rounded-lg, [role="button"]').forEach((element) => {
            if (!element.dataset.motionReady) {
                element.classList.add('motion-button');
                element.dataset.motionReady = '1';
            }
        });

        scope.querySelectorAll('input, select, textarea').forEach((element) => {
            element.classList.add('motion-input');
        });

        scope.querySelectorAll('.shadow-card, .shadow-elegant').forEach((element) => {
            if (!element.closest('[id*="modal"]')) {
                element.classList.add('motion-card');
            }
        });

        scope.querySelectorAll('table tbody tr').forEach((element, index) => {
            if (!element.dataset.motionReady) {
                element.classList.add('motion-table-row');
                element.style.setProperty('--motion-index', String(Math.min(index, 18)));
                element.dataset.motionReady = '1';
            }
        });

        scope.querySelectorAll('[id$="-modal"], [id*="modal"]').forEach((element) => {
            if (element.classList.contains('fixed') && element.classList.contains('inset-0')) {
                element.classList.add('motion-modal');
                const panel = Array.from(element.children).find((child) => child.nodeType === 1);
                if (panel) {
                    panel.classList.add('motion-modal-panel');
                }
            }
        });

        scope.querySelectorAll('[id$="dropdown"], [id*="dropdown"]').forEach((element) => {
            element.classList.add('motion-dropdown');
        });

        scope.querySelectorAll('[class*="bg-success/10"], [class*="bg-destructive/10"], [class*="bg-warning/10"]').forEach((element) => {
            if (!element.dataset.noticeReady) {
                element.classList.add('motion-notice');
                element.dataset.noticeReady = '1';
            }
        });
    }

    applyMotionEnhancements();

    document.querySelectorAll('form').forEach((form) => {
        if (document.body.classList.contains('customer-portal')) return;
        if (form.dataset.submitMotionReady === '1') return;
        form.dataset.submitMotionReady = '1';

        form.addEventListener('submit', (event) => {
            if (event.defaultPrevented) return;

            const method = String(form.getAttribute('method') || 'GET').toUpperCase();
            if (method === 'GET') return;

            const button = event.submitter || form.querySelector('button[type="submit"], input[type="submit"]');
            if (!button || button.dataset.keepEnabled === '1') return;

            button.classList.add('is-loading');
            window.setTimeout(() => {
                button.setAttribute('disabled', 'disabled');
            }, 0);
        });
    });
    
    // Theme toggle handling
    const toggleButtons = document.querySelectorAll('.theme-toggle-btn');
    toggleButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            if (window.lucide) window.lucide.createIcons();
        });
    });

    // Automatically capture dynamically added elements (like modals, tables)
    const observer = new MutationObserver((mutations) => {
        let hasNewIcons = false;
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === 1) { // Element node
                    const isSvg = node.tagName.toLowerCase() === 'svg';
                    const hasUnconverted = (node.hasAttribute('data-lucide') && !isSvg) || 
                                           node.querySelector('[data-lucide]:not(svg)');
                    if (hasUnconverted) {
                        hasNewIcons = true;
                    }
                }
            });
        });
        if (hasNewIcons) {
            window.lucide.createIcons();
        }
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === 1) {
                    applyMotionEnhancements(node);
                }
            });
        });
    });
    observer.observe(document.body, { childList: true, subtree: true });
});
