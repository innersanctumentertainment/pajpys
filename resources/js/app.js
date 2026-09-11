// Mobile navigation toggle
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('mobile-nav-toggle');
    const menu = document.getElementById('mobile-nav-menu');

    if (toggle && menu) {
        toggle.addEventListener('click', () => {
            const expanded = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', String(!expanded));
            menu.classList.toggle('hidden');
        });
    }

    // FAQ accordion — close others when one opens
    document.querySelectorAll('.faq-item').forEach((item) => {
        item.addEventListener('toggle', () => {
            if (item.open) {
                document.querySelectorAll('.faq-item').forEach((other) => {
                    if (other !== item) other.open = false;
                });
            }
        });
    });
});

// Modal open/close helpers
document.addEventListener('click', (e) => {
    const openBtn = e.target.closest('[data-modal-open]');
    if (openBtn) {
        const name = openBtn.dataset.modalOpen;
        const modal = document.getElementById(`modal-${name}`);
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }

    const closeEl = e.target.closest('[data-modal-close]');
    if (closeEl) {
        const name = closeEl.dataset.modalClose;
        const modal = document.getElementById(`modal-${name}`);
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.querySelectorAll('[id^="modal-"]').forEach((modal) => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        });
    }
});

// Service worker registration
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker
            .register('/sw.js')
            .catch(() => {
                // SW registration failed silently in dev
            });
    });
}
