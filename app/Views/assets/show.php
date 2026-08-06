<?php
$rupiah = static fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
$date = static fn ($value) => $value ? date('d M Y', strtotime($value)) : '-';
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Detail Aset — Jamkrindo</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="<?= base_url('assets/css/dashboard.css') ?>"><link rel="stylesheet" href="<?= base_url('assets/css/dashboard-menu.css') ?>"><link rel="stylesheet" href="<?= base_url('assets/css/assets-crud.css') ?>"><link rel="stylesheet" href="<?= base_url('assets/css/assets-detail.css') ?>"><link rel="stylesheet" href="<?= base_url('assets/css/shared-sidebar-pages.css') ?>"></head><body class="detail-page has-shared-sidebar">
<?= view('partials/sidebar', ['sidebarActive' => 'assets', 'assetGroup' => $asset['asset_group']]) ?>
<div class="shared-page-shell">
<header class="form-topbar"><button class="shared-sidebar-toggle" type="button">☰</button><a href="<?= site_url('data-aset') . '?type=' . urlencode($asset['asset_group']) ?>">← Kembali ke Data Aset</a></header>
<main class="detail-main">
    <section class="detail-heading"><div><p>DETAIL INVENTARIS</p><h1><?= esc($asset['name']) ?></h1><span>Informasi lengkap aset nomor #<?= esc($asset['id']) ?></span></div><div><span class="detail-status <?= $asset['condition'] === 'SEDANG DIGUNAKAN' ? 'used' : ($asset['condition'] === 'RUSAK' ? 'damaged' : 'lost') ?>"><?= esc(ucwords(strtolower($asset['condition']))) ?></span><a href="<?= site_url('data-aset/' . $asset['id'] . '/edit') ?>">Edit Aset</a></div></section>
    <section class="detail-grid">
        <article><header><span>01</span><div><h2>Identitas Aset</h2><p>Informasi pengenal dan klasifikasi.</p></div></header><dl>
            <div><dt>Jenis aset</dt><dd><?= esc(ucwords(strtolower($asset['asset_group']))) ?></dd></div><div><dt>Kategori akuntansi</dt><dd><?= esc($asset['category']) ?></dd></div><div><dt>Area</dt><dd><?= esc(ucwords(strtolower($asset['area']))) ?></dd></div><div><dt>Nomor aset</dt><dd><?= esc($asset['asset_number'] ?: '-') ?></dd></div><div class="wide"><dt>Kode SIM AT</dt><dd><?= esc($asset['asset_code_simat'] ?: '-') ?></dd></div><div class="wide"><dt>Kode JSTREAM</dt><dd><?= esc($asset['asset_code_jstream'] ?: '-') ?></dd></div>
        </dl></article>
        <article><header><span>02</span><div><h2>Lokasi & Masa Manfaat</h2><p>Penempatan dan umur ekonomis.</p></div></header><dl>
            <div class="wide"><dt>Lokasi</dt><dd><?= esc(ucwords(strtolower($asset['location']))) ?></dd></div><div><dt>Tanggal perolehan</dt><dd><?= $date($asset['acquired']) ?></dd></div><div><dt>Tahun</dt><dd><?= esc($asset['year'] ?: '-') ?></dd></div><div><dt>Tanggal habis manfaat</dt><dd><?= $date($asset['benefit_end']) ?></dd></div><div><dt>Umur ekonomis</dt><dd><?= number_format((int) $asset['useful_life_months'], 0, ',', '.') ?> bulan</dd></div>
        </dl></article>
        <article class="finance-detail"><header><span>03</span><div><h2>Nilai & Penyusutan</h2><p>Ringkasan nilai finansial aset.</p></div></header><dl>
            <div><dt>Harga perolehan</dt><dd><?= $rupiah($asset['acquisition_value']) ?></dd></div><div><dt>Dasar penyusutan</dt><dd><?= $rupiah($asset['depreciation_base']) ?></dd></div><div><dt>Nilai residu</dt><dd><?= $rupiah($asset['residual_value']) ?></dd></div><div><dt>Persentase residu</dt><dd><?= number_format((float) $asset['residual_percent'], 2, ',', '.') ?>%</dd></div><div><dt>Penyusutan per bulan</dt><dd><?= $rupiah($asset['monthly_depreciation']) ?></dd></div><div><dt>Terakhir diperbarui</dt><dd><?= $date($asset['updated_at']) ?></dd></div>
        </dl></article>
    </section>
</main></div><script src="<?= base_url('assets/js/shared-sidebar.js') ?>"></script></body></html>
