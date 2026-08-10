<?php
$percentage = static fn (float $value): string => number_format($value * 100, 1, ',', '.') . '%';
$topOperatingSystems = array_slice($summary['operatingSystems'], 0, 6, true);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Monitoring Endpoint — Jamkrindo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/dashboard-menu.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/assets-crud.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/color-theme.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/shared-sidebar-pages.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/endpoint-monitoring.css') ?>">
</head>
<body class="has-shared-sidebar">
<?= view('partials/sidebar', ['sidebarActive' => 'operational-ti', 'operationalUnit' => 'monitoring']) ?>
<div class="crud-shell monitoring-shell">
    <header class="crud-topbar">
        <button class="crud-menu-toggle" type="button">☰</button>
        <div><small>DIVISI OPERASIONAL TI</small><strong>Monitoring Endpoint</strong></div>
        <a href="<?= site_url('logout') ?>">Keluar ↗</a>
    </header>
    <main>
        <section class="monitoring-heading">
            <div><p>MONITORING TERINTEGRASI</p><h1>Kesiapan Endpoint Seluruh Unit</h1><span>Rekap otomatis status domain dan kelengkapan data endpoint dari database terbaru.</span></div>
            <a class="export-excel-button" href="<?= site_url('operasional-ti/monitoring/export') ?>"><span>⇩</span> Export to Excel</a>
        </section>

        <section class="monitoring-summary-layout">
            <div class="monitoring-summary-kpis">
                <article class="summary-kpi total"><span class="monitoring-kpi-icon">▦</span><div><small>Total Endpoint</small><strong><?= number_format($summary['total'], 0, ',', '.') ?></strong><em>Tersebar di <?= count($summary['units']) ?> unit kerja</em></div></article>
                <article class="summary-kpi attention"><span class="monitoring-kpi-icon">!</span><div><small>Perlu Tindak Lanjut</small><strong><?= number_format(max($summary['notJoined'], $summary['incomplete']), 0, ',', '.') ?></strong><em><?= number_format($summary['notJoined'], 0, ',', '.') ?> belum join · <?= number_format($summary['incomplete'], 0, ',', '.') ?> belum lengkap</em></div></article>
            </div>

            <article class="monitoring-donut-card join-donut-card">
                <div class="monitoring-donut" style="--donut-value:<?= $summary['joinRate'] * 100 ?>;--donut-color:#168c68"><div><strong><?= $percentage($summary['joinRate']) ?></strong><span>JOIN DOMAIN</span></div></div>
                <div class="donut-copy"><p>STATUS DOMAIN</p><h2>Kesiapan Join Domain</h2><span><?= number_format($summary['joined'], 0, ',', '.') ?> dari <?= number_format($summary['total'], 0, ',', '.') ?> endpoint sudah terhubung.</span><div class="donut-legend"><i class="done"></i><b>Sudah Join</b><strong><?= $summary['joined'] ?></strong><i class="pending"></i><b>Belum Join</b><strong><?= $summary['notJoined'] ?></strong></div></div>
            </article>

            <article class="monitoring-donut-card complete-donut-card">
                <div class="monitoring-donut" style="--donut-value:<?= $summary['completionRate'] * 100 ?>;--donut-color:#0875df"><div><strong><?= $percentage($summary['completionRate']) ?></strong><span>DATA LENGKAP</span></div></div>
                <div class="donut-copy"><p>KELENGKAPAN DATA</p><h2>Pengisian Seluruh Field</h2><span><?= number_format($summary['complete'], 0, ',', '.') ?> endpoint sudah terisi lengkap.</span><div class="donut-legend"><i class="complete"></i><b>Lengkap</b><strong><?= $summary['complete'] ?></strong><i class="pending"></i><b>Belum Lengkap</b><strong><?= $summary['incomplete'] ?></strong></div></div>
            </article>
        </section>

        <section class="monitoring-grid">
            <article class="monitoring-panel unit-readiness-panel">
                <header><div><p>STATUS PER UNIT</p><h2>Kesiapan endpoint unit kerja</h2></div><span><?= count($summary['units']) ?> unit</span></header>
                <div class="unit-readiness-list">
                    <?php foreach ($summary['units'] as $index => $unit): ?>
                        <?php $joinRatio = $unit['total'] ? $unit['joined'] / $unit['total'] : 0; $completeRatio = $unit['total'] ? $unit['complete'] / $unit['total'] : 0; ?>
                        <a href="<?= site_url('operasional-ti/endpoint-kanwil') . '?branch=' . urlencode($unit['branch']) ?>">
                            <span class="unit-rank"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                            <div class="unit-readiness-content">
                                <div class="unit-readiness-title"><strong><?= esc($unit['name']) ?></strong><em><?= $unit['total'] ?> endpoint</em></div>
                                <div class="unit-metric"><span>Join domain</span><i><b style="width:<?= $joinRatio * 100 ?>%"></b></i><strong><?= $percentage($joinRatio) ?></strong></div>
                                <div class="unit-metric complete"><span>Data lengkap</span><i><b style="width:<?= $completeRatio * 100 ?>%"></b></i><strong><?= $percentage($completeRatio) ?></strong></div>
                            </div>
                            <span class="unit-arrow">→</span>
                        </a>
                    <?php endforeach ?>
                </div>
            </article>

            <article class="monitoring-panel compact-panel">
                <header><div><p>TIPE PERANGKAT</p><h2>Komposisi endpoint</h2></div></header>
                <div class="compact-stats">
                    <?php foreach ($summary['endpointTypes'] as $type => $count): ?><div><span><?= esc(ucwords(strtolower($type))) ?></span><strong><?= $count ?></strong><em><?= $percentage($summary['total'] ? $count / $summary['total'] : 0) ?></em></div><?php endforeach ?>
                </div>
            </article>

            <article class="monitoring-panel compact-panel">
                <header><div><p>SISTEM OPERASI</p><h2>Distribusi OS endpoint</h2></div></header>
                <div class="compact-stats os-stats">
                    <?php foreach ($topOperatingSystems as $os => $count): ?><div><span><?= esc($os) ?></span><strong><?= $count ?></strong><em><?= $percentage($summary['total'] ? $count / $summary['total'] : 0) ?></em></div><?php endforeach ?>
                </div>
            </article>
        </section>
    </main>
</div>
<script src="<?= base_url('assets/js/shared-sidebar.js') ?>"></script>
</body>
</html>
