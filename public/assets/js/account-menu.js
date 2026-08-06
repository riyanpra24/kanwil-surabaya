(() => {
    const menu = document.querySelector('.account-menu');
    const toggle = document.querySelector('.account-menu-toggle');
    if (!menu || !toggle) return;

    const setOpen = (open) => {
        menu.classList.toggle('open', open);
        toggle.setAttribute('aria-expanded', String(open));
    };

    toggle.addEventListener('click', () => setOpen(!menu.classList.contains('open')));
    document.addEventListener('click', (event) => {
        if (!menu.contains(event.target)) setOpen(false);
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setOpen(false);
            toggle.focus();
        }
    });
})();
