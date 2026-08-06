<?php
$isEdit = $asset !== null;
$value = static fn (string $field, $default = '') => old($field, $asset[$field] ?? $default);
$action = $isEdit ? site_url('data-aset/' . $asset['id']) : site_url('data-aset');
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= esc($title) ?> — Jamkrindo</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="<?= base_url('assets/css/dashboard.css') ?>"><link rel="stylesheet" href="<?= base_url('assets/css/dashboard-menu.css') ?>"><link rel="stylesheet" href="<?= base_url('assets/css/assets-crud.css') ?>"><link rel="stylesheet" href="<?= base_url('assets/css/shared-sidebar-pages.css') ?>"></head><body class="form-page has-shared-sidebar">
<?= view('partials/sidebar', ['sidebarActive' => 'assets', 'assetGroup' => $selectedGroup]) ?>
<div class="shared-page-shell">
<header class="form-topbar"><button class="shared-sidebar-toggle" type="button">☰</button><a href="<?= site_url('data-aset') . ($selectedGroup ? '?type=' . urlencode($selectedGroup) : '') ?>">← Kembali ke Data Aset</a></header>
<main class="form-main"><section class="form-heading"><p>FORMULIR ASET</p><h1><?= esc($title) ?></h1><span><?= $isEdit ? 'Perbarui informasi inventaris yang dipilih.' : 'Masukkan informasi aset baru secara lengkap.' ?></span></section>
<?php if (session('success')): ?><div class="flash success"><?= esc(session('success')) ?></div><?php endif ?>
<?php if (session('errors')): ?><div class="flash error"><strong>Periksa kembali formulir:</strong><ul><?php foreach (session('errors') as $error): ?><li><?= esc($error) ?></li><?php endforeach ?></ul></div><?php endif ?>
<form class="asset-form" method="post" action="<?= $action ?>"><?= csrf_field() ?>
    <section><header><span>01</span><div><h2>Identitas Aset</h2><p>Jenis, nama, kode, dan nomor inventaris.</p></div></header><div class="form-grid">
        <label>Jenis aset*<select name="asset_group" required><option value="">Pilih jenis</option><?php foreach ($groups as $group): ?><option value="<?= $group ?>" <?= strtoupper((string) $value('asset_group', $selectedGroup)) === $group ? 'selected' : '' ?>><?= esc(ucwords(strtolower($group))) ?></option><?php endforeach ?></select></label>
        <label>Kategori akuntansi*<select name="category" required><?php foreach (['FURNITURE','TI','PERALATAN','MESIN'] as $category): ?><option value="<?= $category ?>" <?= strtoupper((string) $value('category', 'PERALATAN')) === $category ? 'selected' : '' ?>><?= esc(ucwords(strtolower($category))) ?></option><?php endforeach ?></select></label>
        <label class="wide">Nama / tipe aset*<input name="name" value="<?= esc($value('name')) ?>" required maxlength="255"></label>
        <label>Kode aset SIM AT<input name="asset_code_simat" value="<?= esc($value('asset_code_simat')) ?>"></label><label>Kode aset JSTREAM<input name="asset_code_jstream" value="<?= esc($value('asset_code_jstream')) ?>"></label><label>Nomor aset<input name="asset_number" value="<?= esc($value('asset_number')) ?>"></label>
    </div></section>
    <section><header><span>02</span><div><h2>Penempatan & Kondisi</h2><p>Lokasi fisik dan keadaan aset saat ini.</p></div></header><div class="form-grid">
        <label class="wide">Lokasi aset*<input name="location" value="<?= esc($value('location')) ?>" required></label><label>Kondisi*<select name="condition" required><?php foreach (['SEDANG DIGUNAKAN','RUSAK','HILANG','TIDAK DIKETAHUI'] as $condition): ?><option value="<?= $condition ?>" <?= $value('condition', 'SEDANG DIGUNAKAN') === $condition ? 'selected' : '' ?>><?= esc(ucwords(strtolower($condition))) ?></option><?php endforeach ?></select></label>
        <label>Tanggal perolehan<input type="date" name="acquired" value="<?= esc($value('acquired')) ?>"></label><label>Tanggal habis manfaat<input type="date" name="benefit_end" value="<?= esc($value('benefit_end')) ?>"></label><label>Umur ekonomis (bulan)<input type="number" min="0" name="useful_life_months" value="<?= esc($value('useful_life_months', 0)) ?>"></label>
    </div></section>
    <section><header><span>03</span><div><h2>Nilai & Penyusutan</h2><p>Informasi finansial aset dalam Rupiah.</p></div></header><div class="form-grid">
        <label>Harga perolehan*<input type="number" step="0.01" min="0" name="acquisition_value" value="<?= esc($value('acquisition_value', 0)) ?>" required></label><label>Nilai residu (%)<input type="number" step="0.0001" min="0" name="residual_percent" value="<?= esc($value('residual_percent', 0)) ?>"></label><label>Nilai residu nominal<input type="number" step="0.01" min="0" name="residual_value" value="<?= esc($value('residual_value', 0)) ?>"></label><label>Dasar penyusutan<input type="number" step="0.01" min="0" name="depreciation_base" value="<?= esc($value('depreciation_base', 0)) ?>"></label><label>Penyusutan per bulan<input type="number" step="0.01" min="0" name="monthly_depreciation" value="<?= esc($value('monthly_depreciation', 0)) ?>"></label>
    </div></section>
    <div class="form-actions"><a href="<?= site_url('data-aset') ?>">Batal</a><button type="submit"><?= $isEdit ? 'Simpan Perubahan' : 'Tambah Aset' ?></button></div>
</form></main></div><script src="<?= base_url('assets/js/shared-sidebar.js') ?>"></script></body></html>
