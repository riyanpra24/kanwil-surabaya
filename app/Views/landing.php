<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Portal Sistem Informasi Jamkrindo Kantor Wilayah Surabaya">
    <title>Sistem Jamkrindo Kanwil Surabaya</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/landing.css') ?>">
</head>

<body>
    <main class="hero" id="beranda">
        <div class="orb orb-one"></div>
        <div class="orb orb-two"></div>

        <header class="navbar">
            <a class="brand" href="#beranda" aria-label="Jamkrindo">
                <img class="brand-full-logo" src="<?= base_url('assets/img/logo-jamkrindo-kanwil-v2.png') ?>" alt="Jamkrindo Kanwil Surabaya">
            </a>
        </header>

        <section class="hero-content">
            <div class="intro">
                <p class="eyebrow">PORTAL INTERNAL TERINTEGRASI</p>
                <h1>Kerja lebih cepat.<br><span>Kolaborasi lebih mudah.</span></h1>
                <p class="lead">Satu pintu untuk mengakses informasi, layanan, dan aktivitas operasional Jamkrindo Kantor Wilayah Surabaya secara aman dan efisien.</p>
                <div class="intro-actions">
                    <a class="button-primary" href="#login" aria-controls="login" aria-expanded="false">Masuk ke sistem <span>→</span></a>
                    <a class="text-link" href="#tentang">Pelajari lebih lanjut</a>
                </div>
            </div>

            <aside class="login-card" id="login">
                <div class="card-glow"></div>
                <button class="login-close" type="button" aria-label="Kembali ke halaman awal"><span aria-hidden="true">←</span></button>
                <div class="login-inner">
                    <div class="login-heading">
                        <span class="login-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <path d="M20 21a8 8 0 0 0-16 0M12 13a5 5 0 1 0 0-10 5 5 0 0 0 0 10Z" />
                            </svg>
                        </span>
                        <div>
                            <p>SELAMAT DATANG</p>
                            <h2>Login Sistem</h2>
                        </div>
                    </div>

                    <?php if (session('success')): ?>
                        <div class="alert success"><?= esc(session('success')) ?></div>
                    <?php endif ?>
                    <?php if (session('error')): ?>
                        <div class="alert error"><?= esc(session('error')) ?></div>
                    <?php endif ?>

                    <form action="<?= site_url('login') ?>" method="post" autocomplete="on">
                        <?= csrf_field() ?>
                        <label for="username">Username</label>
                        <div class="input-wrap <?= session('errors.username') ? 'invalid' : '' ?>">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M20 21a8 8 0 0 0-16 0M12 13a5 5 0 1 0 0-10 5 5 0 0 0 0 10Z" />
                            </svg>
                            <input id="username" name="username" type="text" value="<?= esc(old('username')) ?>" placeholder="Masukkan username" required autofocus>
                        </div>
                        <?php if (session('errors.username')): ?><small class="field-error"><?= esc(session('errors.username')) ?></small><?php endif ?>

                        <label for="password">Password</label>
                        <div class="input-wrap <?= session('errors.password') ? 'invalid' : '' ?>">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M7 10V7a5 5 0 0 1 10 0v3M5 10h14v11H5V10Z" />
                            </svg>
                            <input id="password" name="password" type="password" placeholder="Masukkan password" required>
                            <button class="toggle-password" type="button" aria-label="Tampilkan password">◉</button>
                        </div>
                        <?php if (session('errors.password')): ?><small class="field-error"><?= esc(session('errors.password')) ?></small><?php endif ?>

                        <div class="form-meta">
                            <label class="remember"><input type="checkbox" name="remember"> <span>Ingat saya</span></label>
                            <span>Butuh bantuan? Hubungi IT Kanwil</span>
                        </div>
                        <button class="login-button" type="submit">LOGIN <span>→</span></button>
                    </form>
                    <p class="login-note">Akses Khusus Manajemen dan Staff Operasional.</p>
                </div>
            </aside>
        </section>

    </main>
    <div class="login-loader" role="status" aria-live="polite" aria-hidden="true">
        <div class="loader-mark"><span></span></div>
        <p>MEMVERIFIKASI AKSES</p>
        <strong>LOGIN TO OPERASIONAL</strong>
        <div class="loader-dots" aria-hidden="true"><i></i><i></i><i></i></div>
    </div>
    <script>
        const loginCard = document.querySelector('.login-card');
        const loginTrigger = document.querySelector('.button-primary');
        const loginBack = document.querySelector('.login-close');
        const hero = document.querySelector('.hero');
        const introPanel = document.querySelector('.intro');
        const loginForm = loginCard.querySelector('form');
        const loginLoader = document.querySelector('.login-loader');
        const loginSubmit = loginForm.querySelector('.login-button');
        let heroScrollPosition = 0;
        let loginIsSubmitting = false;

        function openLogin() {
            if (loginCard.classList.contains('is-fullscreen')) return;

            heroScrollPosition = hero.scrollTop;
            const startRect = loginCard.getBoundingClientRect();
            const loginInner = loginCard.querySelector('.login-inner');
            const innerRect = loginInner.getBoundingClientRect();
            const introRect = introPanel.getBoundingClientRect();
            introPanel.style.setProperty('--intro-left', `${introRect.left}px`);
            introPanel.style.setProperty('--intro-top', `${introRect.top}px`);
            introPanel.style.setProperty('--intro-width', `${introRect.width}px`);
            introPanel.style.setProperty('--intro-height', `${introRect.height}px`);
            introPanel.classList.add('is-transition-locked');
            loginCard.style.setProperty('--login-left', `${startRect.left}px`);
            loginCard.style.setProperty('--login-top', `${startRect.top}px`);
            loginCard.style.setProperty('--login-width', `${startRect.width}px`);
            loginCard.style.setProperty('--login-height', `${startRect.height}px`);
            const mobile = window.innerWidth <= 560;
            const horizontalPadding = mobile ? 44 : 56;
            const paddingTop = mobile ? 76 : Math.max(70, Math.min(window.innerHeight * .08, 100));
            const paddingBottom = mobile ? 25 : 34;
            const targetWidth = Math.min(520, window.innerWidth - horizontalPadding);
            const availableHeight = window.innerHeight - paddingTop - paddingBottom;
            const targetLeft = (window.innerWidth - targetWidth) / 2;
            const targetTop = paddingTop + Math.max(0, (availableHeight - innerRect.height) / 2);
            Object.assign(loginInner.style, {
                position: 'fixed',
                zIndex: '2',
                left: `${innerRect.left}px`,
                top: `${innerRect.top}px`,
                width: `${innerRect.width}px`,
                height: `${innerRect.height}px`,
                transition: 'none',
                willChange: 'left, top, width',
            });
            loginInner.getBoundingClientRect();
            loginCard.classList.add('is-transitioning', 'is-opening');
            document.body.classList.add('login-expanded');

            let openFinished = false;
            let openFallbackTimer;
            const finishOpen = event => {
                if (event && (event.target !== loginCard || event.propertyName !== 'width')) return;
                if (openFinished) return;
                openFinished = true;
                window.clearTimeout(openFallbackTimer);
                loginCard.removeEventListener('transitionend', finishOpen);
                loginCard.classList.remove('is-opening');
                loginInner.removeAttribute('style');
                document.querySelector('#username').focus({ preventScroll: true });
            };

            loginCard.addEventListener('transitionend', finishOpen);
            loginCard.getBoundingClientRect();

            requestAnimationFrame(() => {
                loginCard.classList.add('is-fullscreen');
                loginInner.style.transition = 'left .58s cubic-bezier(.65,0,.35,1), top .58s cubic-bezier(.65,0,.35,1), width .58s cubic-bezier(.65,0,.35,1)';
                loginInner.style.left = `${targetLeft}px`;
                loginInner.style.top = `${targetTop}px`;
                loginInner.style.width = `${targetWidth}px`;
                loginTrigger.setAttribute('aria-expanded', 'true');
                openFallbackTimer = window.setTimeout(() => finishOpen(), 680);
            });
        }

        function closeLogin() {
            if (!loginCard.classList.contains('is-fullscreen') || loginCard.classList.contains('is-closing') || loginCard.classList.contains('is-opening')) return;

            if (loginCard.contains(document.activeElement)) document.activeElement.blur();
            loginCard.classList.add('is-closing');
            loginCard.getBoundingClientRect();
            let closeFinished = false;
            let closeFallbackTimer;

            const finishClose = event => {
                if (event && (event.target !== loginCard || event.propertyName !== 'width')) return;
                if (closeFinished) return;
                closeFinished = true;
                window.clearTimeout(closeFallbackTimer);

                loginCard.classList.remove('is-transitioning', 'is-closing');
                loginCard.removeAttribute('style');
                introPanel.classList.remove('is-transition-locked');
                introPanel.removeAttribute('style');
                hero.scrollTop = heroScrollPosition;
                document.body.classList.remove('login-expanded');
                loginCard.removeEventListener('transitionend', finishClose);

                requestAnimationFrame(() => {
                    hero.scrollTop = heroScrollPosition;
                });
            };

            loginCard.addEventListener('transitionend', finishClose);
            requestAnimationFrame(() => requestAnimationFrame(() => {
                loginCard.classList.remove('is-fullscreen');
                loginTrigger.setAttribute('aria-expanded', 'false');
                closeFallbackTimer = window.setTimeout(() => finishClose(), 750);
            }));
        }

        loginTrigger.addEventListener('click', event => {
            event.preventDefault();
            openLogin();
        });
        loginBack.addEventListener('click', closeLogin);

        loginForm.addEventListener('submit', event => {
            event.preventDefault();
            if (loginIsSubmitting) return;

            loginIsSubmitting = true;
            loginCard.classList.remove('is-opening');
            loginCard.querySelector('.login-inner').removeAttribute('style');
            loginSubmit.disabled = true;
            loginSubmit.classList.add('is-loading');
            loginSubmit.innerHTML = 'MEMPROSES <span>···</span>';
            loginLoader.setAttribute('aria-hidden', 'false');
            document.body.classList.add('is-auth-loading');

            window.setTimeout(() => {
                HTMLFormElement.prototype.submit.call(loginForm);
            }, 500);
        });

        document.querySelector('.toggle-password').addEventListener('click', function() {
            const input = document.querySelector('#password');
            input.type = input.type === 'password' ? 'text' : 'password';
            this.setAttribute('aria-label', input.type === 'password' ? 'Tampilkan password' : 'Sembunyikan password');
        });
    </script>
</body>

</html>
