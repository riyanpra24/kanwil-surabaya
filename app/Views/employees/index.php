<?php
$queryUrl = static function (array $changes = []) use ($unitSlug, $selectedUnit, $status, $search, $perPage): string {
    $params = array_filter(array_merge(['unit' => $selectedUnit, 'status' => $status, 'q' => $search, 'per_page' => $perPage], $changes), static fn ($value) => $value !== '' && $value !== null);
    return site_url('data-karyawan/' . $unitSlug) . ($params ? '?' . http_build_query($params) : '');
};
$statusClass = static function (?string $value): string {
    $upper = strtoupper(trim((string) $value));
    if (str_contains($upper, 'TETAP') || $upper === 'PKWTT') return 'permanent';
    if (str_contains($upper, 'MAGANG')) return 'intern';
    if (str_contains($upper, 'OUTSOUR') || str_contains($upper, 'ALIH DAYA') || $upper === 'OS') return 'outsourcing';
    return 'contract';
};
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Karyawan <?= esc($unitName) ?> — Jamkrindo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/dashboard-menu.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/assets-crud.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/shared-sidebar-pages.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/asset-modal.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/employee-crud.css') ?>">
</head>
<body class="has-shared-sidebar">
<?= view('partials/sidebar', ['sidebarActive' => 'employees', 'employeeUnit' => $unitSlug]) ?>
<div class="crud-shell employee-shell">
    <header class="crud-topbar"><button class="crud-menu-toggle" type="button">☰</button><div><small>DATABASE SDM</small><strong><?= esc($unitName) ?></strong></div><a href="<?= site_url('logout') ?>">Keluar ↗</a></header>
    <main>
        <section class="crud-heading"><div><p>DATA KARYAWAN</p><h1><?= esc($unitName) ?></h1><span>Kelola identitas, unit kerja, jabatan, status, dan kontak karyawan.</span></div><button class="add-button employee-add" data-employee-create type="button">+ Tambah Karyawan</button></section>
        <?php if (session('success')): ?><div class="flash success"><?= esc(session('success')) ?></div><?php endif ?>
        <section class="crud-card">
            <form class="filters employee-filters" method="get" action="<?= site_url('data-karyawan/' . $unitSlug) ?>">
                <input type="hidden" name="per_page" value="<?= $perPage ?>">
                <label><span>⌕</span><input type="search" name="q" value="<?= esc($search) ?>" placeholder="Cari nama, NPP, bagian, jabatan, atau kontak..."></label>
                <?php if ($isCombined): ?><select name="unit"><option value="">Semua unit</option><?php foreach ($unitOptions as $slug => $label): ?><option value="<?= esc($slug) ?>" <?= $selectedUnit === $slug ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach ?></select><?php endif ?>
                <select name="status"><option value="">Semua status</option><?php foreach ($statuses as $item): ?><option value="<?= esc($item) ?>" <?= $status === $item ? 'selected' : '' ?>><?= esc($item) ?></option><?php endforeach ?></select>
                <button type="submit">Terapkan</button><?php if ($search || $status || $selectedUnit): ?><a href="<?= site_url('data-karyawan/' . $unitSlug) ?>">Reset</a><?php endif ?>
            </form>
            <div class="result-summary"><strong><?= number_format($total, 0, ',', '.') ?></strong> karyawan ditemukan<?= $selectedUnit ? ' • ' . esc($unitOptions[$selectedUnit]) : ' • Seluruh Jawa Timur' ?></div>
            <div class="crud-table-wrap employee-table"><table><thead><tr><th>Karyawan / NPP</th><th>Unit Kerja</th><th>Bagian</th><th>Posisi</th><th>Status</th><th>Kontak</th><th>Aksi</th></tr></thead><tbody>
            <?php if (! $employees): ?><tr><td colspan="7" class="empty">Tidak ada data karyawan yang sesuai.</td></tr><?php endif ?>
            <?php foreach ($employees as $employee): ?><tr>
                <td><strong><?= esc($employee['full_name']) ?></strong><small>NPP <?= esc($employee['employee_number'] ?: '-') ?><?= $employee['gender'] ? ' • ' . esc($employee['gender']) : '' ?></small></td>
                <td><strong><?= esc($employee['unit_name']) ?></strong></td>
                <td><?= esc($employee['division'] ?: '-') ?></td>
                <td><?= esc($employee['position'] ?: '-') ?></td>
                <td><span class="employee-status <?= $statusClass($employee['employment_status']) ?>"><?= esc($employee['employment_status'] ?: '-') ?></span></td>
                <td><strong><?= esc($employee['phone'] ?: '-') ?></strong><small><?= esc($employee['corporate_email'] ?: 'Email belum diisi') ?></small></td>
                <td><div class="actions"><a data-employee-view="<?= $employee['id'] ?>" href="#">View</a><a data-employee-edit="<?= $employee['id'] ?>" href="#">Edit</a><form method="post" action="<?= site_url('data-karyawan/' . $employee['id'] . '/delete') ?>" onsubmit="return confirm('Hapus data karyawan ini secara permanen?')"><?= csrf_field() ?><button type="submit">Hapus</button></form></div></td>
            </tr><?php endforeach ?></tbody></table></div>
            <div class="crud-pagination"><div class="pagination-left"><span>Halaman <?= $page ?> dari <?= $pages ?></span><form method="get" action="<?= site_url('data-karyawan/' . $unitSlug) ?>"><?php if ($selectedUnit): ?><input type="hidden" name="unit" value="<?= esc($selectedUnit) ?>"><?php endif ?><?php if ($status): ?><input type="hidden" name="status" value="<?= esc($status) ?>"><?php endif ?><?php if ($search): ?><input type="hidden" name="q" value="<?= esc($search) ?>"><?php endif ?><label>Tampilkan <select name="per_page" onchange="this.form.submit()"><?php foreach ([15,50,100] as $size): ?><option value="<?= $size ?>" <?= $perPage === $size ? 'selected' : '' ?>><?= $size ?> item</option><?php endforeach ?></select></label></form></div><div><?php if ($page > 1): ?><a href="<?= $queryUrl(['page' => $page - 1]) ?>">← Sebelumnya</a><?php endif ?><?php if ($page < $pages): ?><a href="<?= $queryUrl(['page' => $page + 1]) ?>">Berikutnya →</a><?php endif ?></div></div>
        </section>
    </main>
</div>

<div class="asset-modal employee-modal" id="employeeModal" aria-hidden="true">
    <button class="asset-modal-backdrop" type="button" data-modal-close aria-label="Tutup popup"></button>
    <section class="asset-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="employeeModalTitle">
        <header class="asset-modal-header"><div><p id="employeeModalEyebrow">DETAIL KARYAWAN</p><h2 id="employeeModalTitle">Informasi Karyawan</h2></div><button class="asset-modal-close" type="button" data-modal-close aria-label="Tutup popup">×</button></header>
        <div class="asset-modal-body employee-view-pane"><dl class="asset-detail-list employee-detail-list"><?php foreach (['full_name'=>'Nama Lengkap','employee_number'=>'NPP','unit_name'=>'Unit Kerja','gender'=>'Gender','division'=>'Bagian','position'=>'Posisi','employment_status'=>'Status Karyawan','phone'=>'Nomor HP / WA','corporate_email'=>'Email Korporat'] as $field => $label): ?><div class="<?= in_array($field, ['full_name','corporate_email'], true) ? 'wide' : '' ?>"><dt><?= esc($label) ?></dt><dd data-detail="<?= $field ?>"></dd></div><?php endforeach ?></dl><div class="asset-modal-actions"><button type="button" data-modal-close>Tutup</button><button class="modal-edit-switch" type="button">Edit Karyawan</button></div></div>
        <form class="asset-modal-body employee-edit-pane" id="employeeModalForm" method="post"><?= csrf_field() ?><div class="modal-form-errors" hidden></div>
            <div class="modal-form-grid employee-form-grid">
                <label class="wide">Nama lengkap*<input name="full_name" required></label>
                <label>Unit kerja*<select name="unit_slug" required><?php foreach ($unitOptions as $slug => $label): ?><option value="<?= esc($slug) ?>"><?= esc($label) ?></option><?php endforeach ?></select></label>
                <label>NPP<input name="employee_number"></label>
                <label>Gender<select name="gender"><option value="">Tidak diketahui</option><option value="L">Laki-laki</option><option value="P">Perempuan</option></select></label>
                <label>Bagian<input name="division"></label>
                <label>Posisi<input name="position"></label>
                <label>Status karyawan<input name="employment_status"></label>
                <label>Nomor HP / WA<input name="phone"></label>
                <label class="wide">Email korporat<input type="email" name="corporate_email"></label>
            </div><div class="asset-modal-actions"><button type="button" data-modal-close>Batal</button><button class="modal-save-button" type="submit">Simpan Perubahan</button></div>
        </form>
    </section>
</div>
<script>window.employeeCrudModal = <?= json_encode(['employees'=>$employees,'storeUrl'=>site_url('data-karyawan'),'updateBase'=>site_url('data-karyawan'),'defaultUnit'=>$selectedUnit ?: 'kanwil-surabaya'], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;</script>
<script src="<?= base_url('assets/js/shared-sidebar.js') ?>"></script><script src="<?= base_url('assets/js/employee-modal.js') ?>"></script>
</body>
</html>
