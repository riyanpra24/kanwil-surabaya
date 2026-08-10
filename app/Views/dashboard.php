<?php
$rupiah = static function (float $value, bool $compact = false): string {
    if ($compact && $value >= 1_000_000_000) {
        return 'Rp ' . number_format($value / 1_000_000_000, 2, ',', '.') . ' M';
    }
    if ($compact && $value >= 1_000_000) {
        return 'Rp ' . number_format($value / 1_000_000, 1, ',', '.') . ' Jt';
    }
    return 'Rp ' . number_format($value, 0, ',', '.');
};
$conditionMap = array_column($summary['conditionCounts'], 1, 0);
$usedAssets = $conditionMap['SEDANG DIGUNAKAN'] ?? 0;
$problemAssets = ($conditionMap['RUSAK'] ?? 0) + ($conditionMap['HILANG'] ?? 0) + ($conditionMap['#N/A'] ?? 0) + ($conditionMap['TIDAK DIKETAHUI'] ?? 0);
$categoryMax = max(array_column($summary['categoryValues'], 1));
$topLocations = array_slice($summary['locationCounts'], 0, 7);
$yearSeries = $summary['yearCounts'];
usort($yearSeries, static fn($a, $b) => (int) $a[0] <=> (int) $b[0]);
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Aset — Jamkrindo Kanwil Surabaya</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/dashboard-menu.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/account-menu.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/dashboard-hero.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/color-theme.css') ?>">
</head>

<body>
    <?= view('partials/sidebar', ['sidebarActive' => 'dashboard']) ?>

    <div class="app-shell">
        <header class="topbar">
            <button class="sidebar-toggle" type="button" aria-label="Buka menu">☰</button>
            <div>
                <p>PORTAL INTERNAL</p>
                <h1>Dashboard Operasional Kanwil Surabaya</h1>
            </div>
            <div class="account-menu">
                <button class="topbar-user account-menu-toggle" type="button" aria-expanded="false" aria-controls="accountDropdown">
                    <span class="avatar"><?= strtoupper(substr((string) session('username'), 0, 1)) ?></span>
                    <span class="account-copy"><strong><?= esc(session('username')) ?></strong><small>Administrator</small></span>
                    <svg class="account-chevron" viewBox="0 0 20 20" aria-hidden="true">
                        <path d="m5.5 7.5 4.5 4.5 4.5-4.5" />
                    </svg>
                </button>
                <div class="account-dropdown" id="accountDropdown">
                    <small>AKUN</small>
                    <a href="<?= site_url('logout') ?>"><span>↗</span>
                        <div><strong>Logout</strong><small>Keluar dari sistem</small></div>
                    </a>
                </div>
            </div>
        </header>

        <main class="dashboard-content" id="ringkasan">
            <section class="dashboard-hero">
                <div class="dashboard-hero-content">
                    <section class="page-intro">
                        <div>
                            <p class="hero-eyebrow">DASHBOARD TERINTEGRASI</p>
                            <h2>Monitoring Operasional Kantor Wilayah Surabaya</h2>
                            <p>Monitoring inventaris aset dan kesiapan perangkat TI dalam satu dashboard terpadu.</p>
                        </div>
                        <div class="update-pill"><span></span>
                            <div><small>Data diperbarui</small><strong>04 Agustus 2026</strong></div>
                        </div>
                    </section>

                    <section class="kpi-grid">
                        <article class="kpi-card blue">
                            <div class="kpi-icon">▦</div>
                            <div><span>Total Aset</span><strong><?= number_format($summary['totalAssets'], 0, ',', '.') ?></strong><small>4 kategori aset</small></div>
                        </article>
                        <article class="kpi-card navy">
                            <div class="kpi-icon">Rp</div>
                            <div><span>Nilai Perolehan</span><strong><?= $rupiah($summary['totalAcquisitionValue'], true) ?></strong><small>Rata-rata <?= $rupiah($summary['averageAssetValue'], true) ?></small></div>
                        </article>
                        <article class="kpi-card green">
                            <div class="kpi-icon">✓</div>
                            <div><span>Sedang Digunakan</span><strong><?= number_format($usedAssets, 0, ',', '.') ?></strong><small><?= number_format($usedAssets / $summary['totalAssets'] * 100, 1, ',', '.') ?>% dari seluruh aset</small></div>
                        </article>
                        <article class="kpi-card orange">
                            <div class="kpi-icon">!</div>
                            <div><span>Perlu Perhatian</span><strong><?= number_format($problemAssets, 0, ',', '.') ?></strong><small>Rusak, hilang, atau data tidak valid</small></div>
                        </article>
                    </section>
                </div>
            </section>

            <section class="analytics-grid" id="analitik">
                <article class="panel category-panel">
                    <header>
                        <div>
                            <p>DISTRIBUSI NILAI</p>
                            <h3>Nilai aset per kategori</h3>
                        </div><span>Total <?= $rupiah($summary['totalAcquisitionValue'], true) ?></span>
                    </header>
                    <div class="category-bars">
                        <?php foreach ($summary['categoryValues'] as [$category, $value]): ?>
                            <?php $count = array_column($summary['categoryCounts'], 1, 0)[$category] ?? 0 ?>
                            <div class="bar-row">
                                <div class="bar-meta"><strong><?= esc($category) ?></strong><span><?= $count ?> aset · <?= $rupiah($value, true) ?></span></div>
                                <div class="bar-track"><i style="width:<?= max(3, $value / $categoryMax * 100) ?>%"></i></div>
                            </div>
                        <?php endforeach ?>
                    </div>
                </article>

                <article class="panel condition-panel">
                    <header>
                        <div>
                            <p>KONDISI ASET</p>
                            <h3>Status penggunaan</h3>
                        </div>
                    </header>
                    <div class="donut-layout">
                        <canvas id="conditionChart" width="190" height="190" aria-label="Grafik kondisi aset"></canvas>
                        <div class="legend-list">
                            <?php $legendColors = ['#0875df', '#f59e0b', '#ef4444', '#94a3b8'] ?>
                            <?php foreach ($summary['conditionCounts'] as $index => [$label, $count]): ?>
                                <div><i style="background:<?= $legendColors[$index] ?? '#94a3b8' ?>"></i><span><?= esc(ucwords(strtolower($label))) ?></span><strong><?= $count ?></strong></div>
                            <?php endforeach ?>
                        </div>
                    </div>
                </article>

                <article class="panel lifecycle-panel">
                    <header>
                        <div>
                            <p>MASA MANFAAT</p>
                            <h3>Status umur ekonomis</h3>
                        </div>
                    </header>
                    <div class="lifecycle-list">
                        <div class="lifecycle expired"><span class="status-dot"></span>
                            <div><strong>Telah lewat masa manfaat</strong><small>Perlu evaluasi penggantian atau penghapusan</small></div><b><?= $summary['lifecycle']['expired'] ?></b>
                        </div>
                        <div class="lifecycle warning"><span class="status-dot"></span>
                            <div><strong>Berakhir ≤ 12 bulan</strong><small>Masuk prioritas pemantauan</small></div><b><?= $summary['lifecycle']['expiringWithinYear'] ?></b>
                        </div>
                        <div class="lifecycle healthy"><span class="status-dot"></span>
                            <div><strong>Aktif lebih dari 1 tahun</strong><small>Masa manfaat masih memadai</small></div><b><?= $summary['lifecycle']['activeOverYear'] ?></b>
                        </div>
                    </div>
                    <div class="depreciation-note"><span>∿</span>
                        <div><small>Total penyusutan per bulan</small><strong><?= $rupiah($summary['totalMonthlyDepreciation'], true) ?></strong></div>
                    </div>
                </article>

                <article class="panel location-panel">
                    <header>
                        <div>
                            <p>SEBARAN LOKASI</p>
                            <h3>Lokasi dengan aset terbanyak</h3>
                        </div>
                    </header>
                    <div class="location-list">
                        <?php $maxLocation = $topLocations[0][1] ?>
                        <?php foreach ($topLocations as $index => [$location, $count]): ?>
                            <div><span class="rank"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                                <div><strong><?= esc(ucwords(strtolower($location))) ?></strong><i><b style="width:<?= $count / $maxLocation * 100 ?>%"></b></i></div><em><?= $count ?></em>
                            </div>
                        <?php endforeach ?>
                    </div>
                </article>
            </section>

        </main>
    </div>

    <script>
        window.assetDashboard = <?= json_encode([
                                    'assets' => $assets,
                                    'conditions' => $summary['conditionCounts'],
                                    'years' => $yearSeries,
                                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>
    <script src="<?= base_url('assets/js/dashboard.js') ?>"></script>
    <script src="<?= base_url('assets/js/account-menu.js') ?>"></script>
</body>

</html>
