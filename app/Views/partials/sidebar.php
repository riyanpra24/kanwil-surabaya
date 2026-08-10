<?php
$sidebarActive = $sidebarActive ?? 'dashboard';
$assetGroup = $assetGroup ?? '';
$operationalUnit = strtolower(trim((string) ($operationalUnit ?? '')));
$operationalOpen = $sidebarActive === 'operational-ti';
$employeeUnit = strtolower(trim((string) ($employeeUnit ?? '')));
$employeeOpen = $sidebarActive === 'employees';
$assetLabels = ['FURNITURE' => 'Furniture', 'TI' => 'TI', 'PERALATAN' => 'Peralatan', 'MESIN' => 'Mesin', 'RUMAH DINAS' => 'Rumah Dinas', 'KENDARAAN' => 'Kendaraan', 'TANAH' => 'Tanah', 'GEDUNG' => 'Gedung'];
$operationalLabels = [
    'kanwil-surabaya' => 'Kanwil Surabaya',
    'cabang-surabaya' => 'Cabang Surabaya',
    'cabang-malang' => 'Cabang Malang',
    'cabang-kediri' => 'Cabang Kediri',
    'cabang-madiun' => 'Cabang Madiun',
    'cabang-banyuwangi' => 'Cabang Banyuwangi',
    'kup-jember' => 'KUP Jember',
    'kup-bojonegoro' => 'KUP Bojonegoro',
    'kup-pamekasan' => 'KUP Pamekasan',
];
$employeeLabels = $operationalLabels;
?>
<aside class="sidebar">
    <a class="sidebar-brand" href="<?= site_url('dashboard') ?>">
        <img src="<?= base_url('assets/img/logo-jamkrindo-kanwil-v2.png') ?>" alt="Jamkrindo Kanwil Surabaya">
    </a>
    <nav>
        <a class="<?= $sidebarActive === 'dashboard' && ! $operationalOpen && ! $employeeOpen ? 'active' : '' ?>" href="<?= site_url('dashboard') ?>#ringkasan" data-sidebar-label="Dashboard"><span class="nav-menu-symbol">⌂</span><span class="sidebar-label">Dashboard</span></a>
        <div class="nav-dropdown <?= $sidebarActive === 'assets' ? 'open' : '' ?>">
            <button class="nav-dropdown-toggle" id="assetMenuToggle" type="button" aria-expanded="<?= $sidebarActive === 'assets' ? 'true' : 'false' ?>" data-sidebar-label="Data Aset"><span class="nav-menu-symbol">▦</span><span class="nav-dropdown-label">Data Aset</span><svg class="nav-dropdown-chevron" viewBox="0 0 20 20" aria-hidden="true"><path d="m5.5 7.5 4.5 4.5 4.5-4.5"/></svg></button>
            <div class="nav-submenu" id="assetSubmenu">
                <?php foreach ($assetLabels as $value => $label): ?>
                    <a class="<?= $assetGroup === $value ? 'selected' : '' ?>" href="<?= site_url('data-aset') . '?type=' . urlencode($value) ?>"><?= esc($label) ?></a>
                <?php endforeach ?>
            </div>
        </div>
        <div class="nav-dropdown <?= $operationalOpen ? 'open' : '' ?>">
            <button class="nav-dropdown-toggle operational-menu-toggle" id="operationalMenuToggle" type="button" aria-expanded="<?= $operationalOpen ? 'true' : 'false' ?>" data-sidebar-label="Divisi Operasional TI">
                <span class="nav-menu-symbol" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 5h16v11H4zM8 20h8M12 16v4"/></svg></span>
                <span class="nav-dropdown-label">Divisi Operasional TI</span>
                <svg class="nav-dropdown-chevron" viewBox="0 0 20 20" aria-hidden="true"><path d="m5.5 7.5 4.5 4.5 4.5-4.5"/></svg>
            </button>
            <div class="nav-submenu" id="operationalSubmenu">
                <a class="<?= $operationalUnit === 'monitoring' ? 'selected' : '' ?>" href="<?= site_url('operasional-ti/monitoring') ?>">Monitoring</a>
                <a class="<?= $operationalUnit === 'endpoint-kanwil' ? 'selected' : '' ?>" href="<?= site_url('operasional-ti/endpoint-kanwil') ?>">Endpoint Kanwil</a>
            </div>
        </div>
        <div class="nav-dropdown <?= $employeeOpen ? 'open' : '' ?>">
            <button class="nav-dropdown-toggle operational-menu-toggle" id="employeeMenuToggle" type="button" aria-expanded="<?= $employeeOpen ? 'true' : 'false' ?>" data-sidebar-label="Data Karyawan">
                <span class="nav-menu-symbol" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3.5"/><path d="M5.5 20c.5-4 2.7-6 6.5-6s6 2 6.5 6"/></svg></span>
                <span class="nav-dropdown-label">Data Karyawan</span>
                <svg class="nav-dropdown-chevron" viewBox="0 0 20 20" aria-hidden="true"><path d="m5.5 7.5 4.5 4.5 4.5-4.5"/></svg>
            </button>
            <div class="nav-submenu" id="employeeSubmenu">
                <a class="<?= $employeeUnit === 'monitoring' ? 'selected' : '' ?>" href="<?= site_url('data-karyawan/monitoring') ?>">Monitoring</a>
                <a class="<?= $employeeUnit === 'data-sdm-jatim' ? 'selected' : '' ?>" href="<?= site_url('data-karyawan/data-sdm-jatim') ?>">Data SDM Jatim</a>
            </div>
        </div>
    </nav>
</aside>
<div class="logout-loader" role="status" aria-live="polite" aria-hidden="true">
    <div class="logout-loader-icon"><span>↗</span></div>
    <p>MENUTUP SESI</p>
    <strong>KELUAR DARI SISTEM</strong>
    <div class="logout-progress" aria-hidden="true"><i></i></div>
</div>
<link rel="stylesheet" href="<?= base_url('assets/css/logout.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/sidebar-control.css') ?>">
<script src="<?= base_url('assets/js/logout.js') ?>" defer></script>
<script src="<?= base_url('assets/js/sidebar-control.js') ?>" defer></script>
