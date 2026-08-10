<?php
$sdmTotal = max(1, (int) $employeeSummary['total']);
$sdmUnitMax = max(1, ...array_column($employeeSummary['unitCounts'], 'total'));
$sdmStatusColors = ['#0875df', '#16a777', '#f59e0b', '#8b5cf6', '#ef6c4d'];
$sdmDonutParts = [];
$sdmAngle = 0;
foreach (array_values($employeeSummary['statusGroups']) as $index => $count) {
    $nextAngle = $sdmAngle + ($count / $sdmTotal * 360);
    $sdmDonutParts[] = ($sdmStatusColors[$index] ?? '#94a3b8') . ' ' . $sdmAngle . 'deg ' . $nextAngle . 'deg';
    $sdmAngle = $nextAngle;
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Monitoring SDM Jatim — Jamkrindo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/dashboard-menu.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/assets-crud.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/shared-sidebar-pages.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/sdm-dashboard.css') ?>">
</head>
<body class="has-shared-sidebar">
<?= view('partials/sidebar', ['sidebarActive' => 'employees', 'employeeUnit' => 'monitoring']) ?>
<div class="crud-shell sdm-monitoring-shell">
    <header class="crud-topbar">
        <button class="crud-menu-toggle" type="button">☰</button>
        <div><small>DATABASE SDM</small><strong>Monitoring SDM Jatim</strong></div>
        <a href="<?= site_url('logout') ?>">Keluar ↗</a>
    </header>
    <main>
        <section class="sdm-dashboard" id="rekap-sdm">
            <div class="sdm-section-heading">
                <div><p>REKAPITULASI SDM UKER</p><h2>Komposisi SDM Jawa Timur</h2><span>Data terhubung langsung dengan CRUD Data SDM Jatim.</span></div>
                <form method="get" action="<?= site_url('data-karyawan/monitoring') ?>">
                    <label for="sdmUnitFilter">Unit kerja</label>
                    <select id="sdmUnitFilter" name="unit" onchange="this.form.submit()">
                        <option value="">Seluruh Jawa Timur</option>
                        <?php foreach ($employeeUnits as $slug => $name): ?><option value="<?= esc($slug) ?>" <?= $selectedEmployeeUnit === $slug ? 'selected' : '' ?>><?= esc($name) ?></option><?php endforeach ?>
                    </select>
                </form>
            </div>

            <div class="sdm-kpi-grid">
                <article><span class="sdm-kpi-icon blue">SDM</span><div><small>Total SDM</small><strong><?= number_format($employeeSummary['total'], 0, ',', '.') ?></strong><em><?= $selectedEmployeeUnit ? esc($employeeUnits[$selectedEmployeeUnit]) : '9 unit kerja' ?></em></div></article>
                <article><span class="sdm-kpi-icon green">KT</span><div><small>Karyawan Tetap</small><strong><?= number_format($employeeSummary['permanent'], 0, ',', '.') ?></strong><em><?= number_format($employeeSummary['permanent'] / $sdmTotal * 100, 1, ',', '.') ?>% dari total SDM</em></div></article>
                <article><span class="sdm-kpi-icon orange">PK</span><div><small>Kontrak &amp; Magang</small><strong><?= number_format($employeeSummary['contract'], 0, ',', '.') ?></strong><em>Calon, PKWT, dan magang</em></div></article>
                <article><span class="sdm-kpi-icon purple">OS</span><div><small>Outsourcing</small><strong><?= number_format($employeeSummary['outsourcing'], 0, ',', '.') ?></strong><em>Admin, driver, OB/CS, security</em></div></article>
            </div>

            <div class="sdm-overview-grid">
                <article class="panel sdm-unit-panel">
                    <header><div><p>SEBARAN SDM</p><h3>Jumlah per unit kerja</h3></div><span><?= array_sum(array_column($employeeSummary['unitCounts'], 'total')) ?> SDM</span></header>
                    <div class="sdm-unit-bars">
                        <?php foreach ($employeeUnits as $slug => $name): $count = $employeeSummary['unitCounts'][$slug]['total'] ?? 0; ?>
                            <div class="<?= $selectedEmployeeUnit === $slug ? 'selected' : '' ?>"><div><strong><?= esc($name) ?></strong><span><?= $count ?></span></div><i><b style="width:<?= $count / $sdmUnitMax * 100 ?>%"></b></i></div>
                        <?php endforeach ?>
                    </div>
                </article>
                <article class="panel sdm-status-panel">
                    <header><div><p>STATUS KARYAWAN</p><h3>Komposisi tenaga kerja</h3></div></header>
                    <div class="sdm-donut-layout">
                        <div class="sdm-donut" style="background:conic-gradient(<?= implode(',', $sdmDonutParts) ?>)"><span><strong><?= $employeeSummary['total'] ?></strong><small>TOTAL SDM</small></span></div>
                        <div class="sdm-status-legend">
                            <?php $statusIndex = 0; foreach ($employeeSummary['statusGroups'] as $label => $count): ?><div><i style="background:<?= $sdmStatusColors[$statusIndex] ?>"></i><span><?= esc($label) ?></span><strong><?= $count ?></strong><small><?= number_format($count / $sdmTotal * 100, 1, ',', '.') ?>%</small></div><?php $statusIndex++; endforeach ?>
                        </div>
                    </div>
                </article>
            </div>

            <article class="panel sdm-matrix-panel">
                <header><div><p>REKAP PER POSISI</p><h3><?= $selectedEmployeeUnit ? esc($employeeUnits[$selectedEmployeeUnit]) : 'Seluruh Jawa Timur' ?></h3></div><a href="<?= site_url('data-karyawan/data-sdm-jatim') ?>">Kelola Data SDM →</a></header>
                <div class="sdm-table-wrap"><table><thead><tr><th rowspan="2">Posisi</th><th colspan="5">Status Karyawan</th><th colspan="4">Outsourcing</th><th rowspan="2">Jumlah</th></tr><tr><th>Karyawan Tetap</th><th>Calon Karyawan</th><th>PKWT Umum</th><th>PKWT ELH</th><th>Magang</th><th>Admin</th><th>Driver</th><th>OB/CS</th><th>Security</th></tr></thead><tbody>
                    <?php foreach ($employeeSummary['positions'] as $position): ?><tr><td><?= esc($position['label']) ?></td><?php foreach ($employeeSummary['categories'] as $category): ?><td class="<?= $position[$category] > 0 ? 'has-value' : '' ?>"><?= $position[$category] ?></td><?php endforeach ?><td class="row-total"><?= $position['total'] ?></td></tr><?php endforeach ?>
                    <tr class="grand-total"><td>JUMLAH SDM</td><?php foreach ($employeeSummary['categories'] as $category): ?><td><?= $employeeSummary['totals'][$category] ?></td><?php endforeach ?><td><?= $employeeSummary['total'] ?></td></tr>
                </tbody></table></div>
            </article>
        </section>
    </main>
</div>
<script src="<?= base_url('assets/js/shared-sidebar.js') ?>"></script>
</body>
</html>
