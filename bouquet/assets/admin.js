(() => {
    'use strict';
    const body = document.body;
    const toggleMenu = () => body.classList.toggle('admin-menu-open');
    document.querySelectorAll('[data-admin-menu]').forEach((button) => button.addEventListener('click', toggleMenu));
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') body.classList.remove('admin-menu-open'); });
    document.querySelectorAll('[data-confirm-form]').forEach((form) => form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirmForm || 'Lanjutkan tindakan ini?')) event.preventDefault();
    }));
    document.querySelectorAll('[data-confirm]').forEach((button) => button.addEventListener('click', (event) => {
        if (!window.confirm(button.dataset.confirm || 'Lanjutkan tindakan ini?')) event.preventDefault();
    }));
})();
