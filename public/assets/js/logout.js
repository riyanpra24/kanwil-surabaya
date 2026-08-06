(() => {
    let logoutStarted = false;

    document.addEventListener('DOMContentLoaded', () => {
        const loader = document.querySelector('.logout-loader');
        if (!loader) return;

        document.querySelectorAll('a[href$="/logout"]').forEach((link) => {
            link.addEventListener('click', (event) => {
                if (event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;

                event.preventDefault();
                if (logoutStarted) return;
                logoutStarted = true;

                loader.setAttribute('aria-hidden', 'false');
                document.body.classList.add('is-logout-loading');

                window.setTimeout(() => {
                    window.location.assign(link.href);
                }, 700);
            });
        });
    });
})();
