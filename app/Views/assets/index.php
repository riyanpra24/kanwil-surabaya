<?php
$labels = ['FURNITURE' => 'Furniture', 'TI' => 'TI', 'PERALATAN' => 'Peralatan', 'MESIN' => 'Mesin', 'RUMAH DINAS' => 'Rumah Dinas', 'KENDARAAN' => 'Kendaraan', 'TANAH' => 'Tanah', 'GEDUNG' => 'Gedung'];
$queryUrl = static function (array $changes = []) use ($group, $search, $condition, $perPage): string {
    $params = array_filter(array_merge(['type' => $group, 'q' => $search, 'condition' => $condition, 'per_page' => $perPage], $changes), static fn ($value) => $value !== '' && $value !== null);
    return site_url('data-aset') . ($params ? '?' . http_build_query($params) : '');
};
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Kelola Data Aset — Jamkrindo</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= base_url('assets/css/dashboard.css') ?>"><link rel="stylesheet" href="<?= base_url('assets/css/dashboard-menu.css') ?>"><link rel="stylesheet" href="<?= base_url('assets/css/assets-crud.css') ?>"><link rel="stylesheet" href="<?= base_url('assets/css/assets-detail.css') ?>"><link rel="stylesheet" href="<?= base_url('assets/css/shared-sidebar-pages.css') ?>"><link rel="stylesheet" href="<?= base_url('assets/css/asset-modal.css') ?>"><link rel="stylesheet" href="<?= base_url('assets/css/assets-table-compact.css') ?>"></head><body class="has-shared-sidebar">
<?= view('partials/sidebar', ['sidebarActive' => 'assets', 'assetGroup' => $group]) ?>
<div class="crud-shell">
    <header class="crud-topbar"><button class="crud-menu-toggle" type="button">☰</button><div><small>DATABASE ASET</small><strong><?= esc($group ? ($labels[$group] ?? $group) : 'Semua Jenis Aset') ?></strong></div><a href="<?= site_url('logout') ?>">Keluar ↗</a></header>
    <main>
        <section class="crud-heading"><div><p>PENGELOLAAN DATA</p><h1><?= esc($group ? 'Aset ' . ($labels[$group] ?? $group) : 'Seluruh Data Aset') ?></h1><span>Tambah, perbarui, cari, dan hapus data inventaris.</span></div><a class="add-button" data-asset-create href="<?= site_url('data-aset/new') . ($group ? '?type=' . urlencode($group) : '') ?>">+ Tambah Aset</a></section>
        <?php if (session('success')): ?><div class="flash success"><?= esc(session('success')) ?></div><?php endif ?>
        <section class="crud-card">
            <form class="filters" method="get" action="<?= site_url('data-aset') ?>">
                <?php if ($group): ?><input type="hidden" name="type" value="<?= esc($group) ?>"><?php endif ?>
                <input type="hidden" name="per_page" value="<?= $perPage ?>">
                <label><span>⌕</span><input type="search" name="q" value="<?= esc($search) ?>" placeholder="Cari nama, kode, nomor, atau lokasi..."></label>
                <select name="condition"><option value="">Semua kondisi</option><?php foreach (['SEDANG DIGUNAKAN','RUSAK','HILANG','TIDAK DIKETAHUI'] as $item): ?><option value="<?= $item ?>" <?= $condition === $item ? 'selected' : '' ?>><?= esc(ucwords(strtolower($item))) ?></option><?php endforeach ?></select>
                <button type="submit">Terapkan</button><?php if ($search || $condition): ?><a href="<?= $queryUrl(['q' => '', 'condition' => '']) ?>">Reset</a><?php endif ?>
            </form>
            <div class="result-summary"><strong><?= number_format($total, 0, ',', '.') ?></strong> aset ditemukan</div>
            <div class="crud-table-wrap"><table><thead><tr><th>Aset</th><th>Jenis</th><th>Lokasi</th><th>Kondisi</th><th>Tahun</th><th>Nilai Perolehan</th><th>Aksi</th></tr></thead><tbody>
            <?php if (! $assets): ?><tr><td colspan="7" class="empty">Tidak ada data aset yang sesuai.</td></tr><?php endif ?>
            <?php foreach ($assets as $asset): ?><tr>
                <td><strong><?= esc($asset['name']) ?></strong><small><?= esc($asset['asset_code_simat'] ?: ($asset['asset_number'] ?: 'Tanpa kode aset')) ?></small></td>
                <td><span class="group-chip"><?= esc(ucwords(strtolower($asset['asset_group']))) ?></span></td><td><?= esc(ucwords(strtolower($asset['location']))) ?></td>
                <td><span class="status <?= $asset['condition'] === 'SEDANG DIGUNAKAN' ? 'used' : ($asset['condition'] === 'RUSAK' ? 'damaged' : 'lost') ?>"><?= esc(ucwords(strtolower($asset['condition']))) ?></span></td>
                <td><?= esc($asset['year'] ?: '-') ?></td><td>Rp <?= number_format((float) $asset['acquisition_value'], 0, ',', '.') ?></td>
                <td><div class="actions"><a class="view-action" data-asset-view="<?= $asset['id'] ?>" href="<?= site_url('data-aset/' . $asset['id']) ?>">View</a><a class="edit-action" data-asset-edit="<?= $asset['id'] ?>" href="<?= site_url('data-aset/' . $asset['id'] . '/edit') ?>">Edit</a><form method="post" action="<?= site_url('data-aset/' . $asset['id'] . '/delete') ?>" onsubmit="return confirm('Hapus aset ini secara permanen?')"><?= csrf_field() ?><button type="submit">Hapus</button></form></div></td>
            </tr><?php endforeach ?></tbody></table></div>
            <div class="crud-pagination">
                <div class="pagination-left"><span>Halaman <?= $page ?> dari <?= $pages ?></span><form method="get" action="<?= site_url('data-aset') ?>"><?php if ($group): ?><input type="hidden" name="type" value="<?= esc($group) ?>"><?php endif ?><?php if ($search): ?><input type="hidden" name="q" value="<?= esc($search) ?>"><?php endif ?><?php if ($condition): ?><input type="hidden" name="condition" value="<?= esc($condition) ?>"><?php endif ?><label>Tampilkan <select name="per_page" onchange="this.form.submit()"><?php foreach ([15, 50, 100] as $size): ?><option value="<?= $size ?>" <?= $perPage === $size ? 'selected' : '' ?>><?= $size ?> item</option><?php endforeach ?></select></label></form></div>
                <div><?php if ($page > 1): ?><a href="<?= $queryUrl(['page' => $page - 1]) ?>">← Sebelumnya</a><?php endif ?><?php if ($page < $pages): ?><a href="<?= $queryUrl(['page' => $page + 1]) ?>">Berikutnya →</a><?php endif ?></div>
            </div>
        </section>
    </main>
</div>

<div class="asset-modal" id="assetModal" aria-hidden="true">
    <button class="asset-modal-backdrop" type="button" data-modal-close aria-label="Tutup popup"></button>
    <section class="asset-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="assetModalTitle">
        <header class="asset-modal-header">
            <div><p id="assetModalEyebrow">DETAIL ASET</p><h2 id="assetModalTitle">Informasi Aset</h2></div>
            <button class="asset-modal-close" type="button" data-modal-close aria-label="Tutup popup">×</button>
        </header>

        <div class="asset-modal-body asset-view-pane">
            <div class="asset-modal-status" data-detail="condition"></div>
            <dl class="asset-detail-list">
                <div class="wide"><dt>Nama / tipe aset</dt><dd data-detail="name"></dd></div>
                <div><dt>Jenis aset</dt><dd data-detail="asset_group"></dd></div>
                <div><dt>Kategori</dt><dd data-detail="category"></dd></div>
                <div class="wide"><dt>Lokasi</dt><dd data-detail="location"></dd></div>
                <div><dt>Kode SIM AT</dt><dd data-detail="asset_code_simat"></dd></div>
                <div><dt>Kode JSTREAM</dt><dd data-detail="asset_code_jstream"></dd></div>
                <div><dt>Nomor aset</dt><dd data-detail="asset_number"></dd></div>
                <div><dt>Tanggal perolehan</dt><dd data-detail="acquired"></dd></div>
                <div><dt>Habis masa manfaat</dt><dd data-detail="benefit_end"></dd></div>
                <div><dt>Umur ekonomis</dt><dd data-detail="useful_life_months"></dd></div>
                <div><dt>Harga perolehan</dt><dd data-detail="acquisition_value"></dd></div>
                <div><dt>Penyusutan / bulan</dt><dd data-detail="monthly_depreciation"></dd></div>
            </dl>
            <div class="asset-modal-actions"><button type="button" data-modal-close>Tutup</button><button class="modal-edit-switch" type="button">Edit Aset</button></div>
        </div>

        <form class="asset-modal-body asset-edit-pane" id="assetModalForm" method="post">
            <?= csrf_field() ?>
            <div class="modal-form-errors" hidden></div>
            <div class="modal-form-grid">
                <label>Jenis aset*<select name="asset_group" required><?php foreach ($groups as $item): ?><option value="<?= esc($item) ?>"><?= esc($labels[$item] ?? $item) ?></option><?php endforeach ?></select></label>
                <label>Kategori*<select name="category" required><?php foreach (['FURNITURE','TI','PERALATAN','MESIN'] as $item): ?><option value="<?= $item ?>"><?= esc(ucwords(strtolower($item))) ?></option><?php endforeach ?></select></label>
                <label class="wide">Nama / tipe aset*<input name="name" required maxlength="255"></label>
                <label class="wide">Lokasi aset*<input name="location" required></label>
                <label>Kondisi*<select name="condition" required><?php foreach (['SEDANG DIGUNAKAN','RUSAK','HILANG','TIDAK DIKETAHUI'] as $item): ?><option value="<?= $item ?>"><?= esc(ucwords(strtolower($item))) ?></option><?php endforeach ?></select></label>
                <label>Tanggal perolehan<input type="date" name="acquired"></label>
                <label>Kode aset SIM AT<input name="asset_code_simat"></label>
                <label>Kode aset JSTREAM<input name="asset_code_jstream"></label>
                <label>Nomor aset<input name="asset_number"></label>
                <label>Tanggal habis manfaat<input type="date" name="benefit_end"></label>
                <label>Umur ekonomis (bulan)<input type="number" min="0" name="useful_life_months"></label>
                <label>Harga perolehan*<input type="number" step="0.01" min="0" name="acquisition_value" required></label>
                <label>Nilai residu (%)<input type="number" step="0.0001" min="0" name="residual_percent"></label>
                <label>Nilai residu nominal<input type="number" step="0.01" min="0" name="residual_value"></label>
                <label>Dasar penyusutan<input type="number" step="0.01" min="0" name="depreciation_base"></label>
                <label>Penyusutan per bulan<input type="number" step="0.01" min="0" name="monthly_depreciation"></label>
            </div>
            <div class="asset-modal-actions"><button type="button" data-modal-close>Batal</button><button class="modal-save-button" type="submit">Simpan Perubahan</button></div>
        </form>
    </section>
</div>

<script>
window.assetCrudModal = <?= json_encode([
    'assets' => $assets,
    'updateBase' => site_url('data-aset'),
    'storeUrl' => site_url('data-aset'),
    'selectedGroup' => $group,
    'labels' => $labels,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<script src="<?= base_url('assets/js/shared-sidebar.js') ?>"></script><script src="<?= base_url('assets/js/asset-modal.js') ?>"></script></body></html>
