(() => {
    document.querySelectorAll('.nav-dropdown-toggle').forEach((menuToggle) => {
        menuToggle.addEventListener('click', () => {
            const menu = menuToggle.closest('.nav-dropdown');
            const isOpen = !menu?.classList.contains('open');

            document.querySelectorAll('.nav-dropdown').forEach((item) => {
                item.classList.remove('open');
                item.querySelector('.nav-dropdown-toggle')?.setAttribute('aria-expanded', 'false');
            });

            if (isOpen) {
                menu?.classList.add('open');
                menuToggle.setAttribute('aria-expanded', 'true');
            }
        });
    });
})();
