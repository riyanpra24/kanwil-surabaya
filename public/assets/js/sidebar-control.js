(() => {
    const sidebar = document.querySelector('.sidebar');
    const controls = [...document.querySelectorAll('.sidebar-toggle, .crud-menu-toggle, .shared-sidebar-toggle')];
    if (!sidebar || !controls.length) return;

    const mobileQuery = window.matchMedia('(max-width: 800px)');
    const backdrop = document.createElement('button');
    backdrop.className = 'sidebar-backdrop';
    backdrop.type = 'button';
    backdrop.setAttribute('aria-label', 'Tutup sidebar');
    document.body.appendChild(backdrop);

    controls.forEach((control) => {
        control.textContent = '';
        control.setAttribute('aria-label', 'Tutup sidebar');
        control.setAttribute('aria-controls', 'mainSidebar');
        control.setAttribute('aria-expanded', 'true');
        for (let index = 0; index < 3; index += 1) {
            const line = document.createElement('span');
            line.className = 'burger-line';
            line.setAttribute('aria-hidden', 'true');
            control.appendChild(line);
        }
    });
    sidebar.id = 'mainSidebar';

    const syncControls = (open) => {
        controls.forEach((control) => {
            control.setAttribute('aria-expanded', String(open));
            control.setAttribute('aria-label', open ? 'Tutup sidebar' : 'Buka sidebar');
        });
    };

    const closeMobile = () => {
        sidebar.classList.remove('open');
        document.body.classList.remove('sidebar-mobile-open');
        syncControls(false);
    };

    const toggleSidebar = () => {
        if (mobileQuery.matches) {
            const willOpen = !sidebar.classList.contains('open');
            sidebar.classList.toggle('open', willOpen);
            document.body.classList.toggle('sidebar-mobile-open', willOpen);
            syncControls(willOpen);
            return;
        }

        const willCollapse = !document.body.classList.contains('sidebar-collapsed');
        document.body.classList.toggle('sidebar-collapsed', willCollapse);
        syncControls(!willCollapse);
    };

    controls.forEach((control) => control.addEventListener('click', toggleSidebar));
    backdrop.addEventListener('click', closeMobile);

    mobileQuery.addEventListener('change', (event) => {
        sidebar.classList.remove('open');
        document.body.classList.remove('sidebar-mobile-open', 'sidebar-collapsed');
        syncControls(!event.matches);
    });

    syncControls(!mobileQuery.matches);
})();
