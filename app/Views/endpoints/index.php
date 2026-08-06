<?php
$queryUrl = static function (array $changes = []) use ($scope, $branch, $type, $search, $perPage): string {
    $params = array_filter(array_merge([
        'branch' => $branch === '' ? 'ALL' : $branch, 'type' => $type, 'q' => $search, 'per_page' => $perPage,
    ], $changes), static fn ($value) => $value !== '' && $value !== null);
    return site_url('operasional-ti/' . $scope) . ($params ? '?' . http_build_query($params) : '');
};
$isCombined = $unit === '';
$branchOptions = $isCombined ? array_merge(['Kanwil'], $branches) : [$branch];
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Endpoint <?= esc($title) ?> — Jamkrindo</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= base_url('assets/css/dashboard.css') ?>"><link rel="stylesheet" href="<?= base_url('assets/css/dashboard-menu.css') ?>"><link rel="stylesheet" href="<?= base_url('assets/css/assets-crud.css') ?>"><link rel="stylesheet" href="<?= base_url('assets/css/shared-sidebar-pages.css') ?>"><link rel="stylesheet" href="<?= base_url('assets/css/asset-modal.css') ?>"><link rel="stylesheet" href="<?= base_url('assets/css/endpoint-crud.css') ?>"></head><body class="has-shared-sidebar">
<?= view('partials/sidebar', ['sidebarActive' => 'operational-ti', 'operationalUnit' => $scope]) ?>
<div class="crud-shell endpoint-shell">
    <header class="crud-topbar"><button class="crud-menu-toggle" type="button">☰</button><div><small>DIVISI OPERASIONAL TI</small><strong><?= esc($title) ?></strong></div><a href="<?= site_url('logout') ?>">Keluar ↗</a></header>
    <main>
        <section class="crud-heading"><div><p>DATABASE ENDPOINT</p><h1><?= esc($title) ?></h1><span>Kelola perangkat, pengguna, domain, dan status endpoint operasional.</span></div><button class="add-button endpoint-add" data-endpoint-create type="button">+ Tambah Endpoint</button></section>
        <?php if (session('success')): ?><div class="flash success"><?= esc(session('success')) ?></div><?php endif ?>
        <section class="crud-card">
            <form class="filters endpoint-filters" method="get" action="<?= site_url('operasional-ti/' . $scope) ?>">
                <input type="hidden" name="per_page" value="<?= $perPage ?>">
                <label><span>⌕</span><input type="search" name="q" value="<?= esc($search) ?>" placeholder="Cari hostname, IP, serial, aset, pengguna..."></label>
                <?php if ($isCombined): ?><select name="branch"><option value="ALL">Semua unit</option><?php foreach ($branchOptions as $item): ?><option value="<?= esc($item) ?>" <?= $branch === $item ? 'selected' : '' ?>><?= esc($item === 'Kanwil' ? 'Kanwil Surabaya' : (str_starts_with(strtoupper($item), 'KUP ') ? $item : 'Cabang ' . $item)) ?></option><?php endforeach ?></select><?php endif ?>
                <select name="type"><option value="">Semua perangkat</option><option value="PC" <?= $type === 'PC' ? 'selected' : '' ?>>PC</option><option value="LAPTOP" <?= $type === 'LAPTOP' ? 'selected' : '' ?>>Laptop</option></select>
                <button type="submit">Terapkan</button><?php if ($search || $type || ($isCombined && $branch)): ?><a href="<?= site_url('operasional-ti/' . $scope) ?>">Reset</a><?php endif ?>
            </form>
            <div class="result-summary"><strong><?= number_format($total, 0, ',', '.') ?></strong> endpoint ditemukan<?= $branch ? ' • ' . esc($branch) : '' ?></div>
            <div class="crud-table-wrap endpoint-table"><table><thead><tr><th>Hostname / IP</th><th>Perangkat</th><th>Brand / Serial</th><th>Pengguna</th><th>Sistem Operasi</th><th>Domain</th><th>Aksi</th></tr></thead><tbody>
            <?php if (! $endpoints): ?><tr><td colspan="7" class="empty">Tidak ada endpoint yang sesuai.</td></tr><?php endif ?>
            <?php foreach ($endpoints as $endpoint): ?><tr>
                <td><strong><?= esc($endpoint['hostname']) ?></strong><small><?= esc($endpoint['ip_address'] ?: 'IP belum diisi') ?><?= ($isCombined || $unit === 'CABANG') ? ' • ' . esc($endpoint['organization_unit'] === 'KANWIL' ? 'Kanwil Surabaya' : (str_starts_with(strtoupper($endpoint['branch_name']), 'KUP ') ? $endpoint['branch_name'] : 'Cabang ' . $endpoint['branch_name'])) : '' ?></small></td>
                <td><span class="endpoint-chip"><?= esc(ucfirst(strtolower($endpoint['endpoint_type']))) ?></span><small><?= esc($endpoint['procurement_year'] ?: '-') ?></small></td>
                <td><strong><?= esc($endpoint['brand'] ?: '-') ?></strong><small><?= esc($endpoint['serial_number'] ?: 'Tanpa serial') ?></small></td>
                <td><strong><?= esc($endpoint['user_name'] ?: 'Tidak ada pengguna') ?></strong><small><?= esc($endpoint['employee_status'] ?: '-') ?></small></td>
                <td><?= esc($endpoint['operating_system'] ?: '-') ?></td>
                <td><span class="domain-status <?= strcasecmp((string) $endpoint['join_domain'], 'Done') === 0 ? 'done' : '' ?>"><?= esc($endpoint['join_domain'] ?: 'Belum') ?></span></td>
                <td><div class="actions"><a data-endpoint-view="<?= $endpoint['id'] ?>" href="#">View</a><a data-endpoint-edit="<?= $endpoint['id'] ?>" href="#">Edit</a><form method="post" action="<?= site_url('operasional-ti/endpoints/' . $endpoint['id'] . '/delete') ?>" onsubmit="return confirm('Hapus endpoint ini secara permanen?')"><?= csrf_field() ?><button type="submit">Hapus</button></form></div></td>
            </tr><?php endforeach ?></tbody></table></div>
            <div class="crud-pagination"><div class="pagination-left"><span>Halaman <?= $page ?> dari <?= $pages ?></span><form method="get" action="<?= site_url('operasional-ti/' . $scope) ?>"><?php if ($isCombined): ?><input type="hidden" name="branch" value="<?= esc($branch ?: 'ALL') ?>"><?php endif ?><?php if ($type): ?><input type="hidden" name="type" value="<?= esc($type) ?>"><?php endif ?><?php if ($search): ?><input type="hidden" name="q" value="<?= esc($search) ?>"><?php endif ?><label>Tampilkan <select name="per_page" onchange="this.form.submit()"><?php foreach ([15,50,100] as $size): ?><option value="<?= $size ?>" <?= $perPage === $size ? 'selected' : '' ?>><?= $size ?> item</option><?php endforeach ?></select></label></form></div><div><?php if ($page > 1): ?><a href="<?= $queryUrl(['page' => $page - 1]) ?>">← Sebelumnya</a><?php endif ?><?php if ($page < $pages): ?><a href="<?= $queryUrl(['page' => $page + 1]) ?>">Berikutnya →</a><?php endif ?></div></div>
        </section>
    </main>
</div>

<div class="asset-modal endpoint-modal" id="endpointModal" aria-hidden="true">
    <button class="asset-modal-backdrop" type="button" data-modal-close aria-label="Tutup popup"></button>
    <section class="asset-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="endpointModalTitle">
        <header class="asset-modal-header"><div><p id="endpointModalEyebrow">DETAIL ENDPOINT</p><h2 id="endpointModalTitle">Informasi Endpoint</h2></div><button class="asset-modal-close" type="button" data-modal-close aria-label="Tutup popup">×</button></header>
        <div class="asset-modal-body endpoint-view-pane"><dl class="asset-detail-list endpoint-detail-list"><?php foreach (['hostname'=>'Hostname','ip_address'=>'IP Address','branch_name'=>'Unit / Cabang','employee_status'=>'Status Karyawan','endpoint_type'=>'Tipe Endpoint','serial_number'=>'Serial Number','brand'=>'Brand','procurement_year'=>'Tahun Pengadaan / Sewa','asset_number'=>'Nomor Aset Oracle','user_name'=>'User Pengguna','notes'=>'Keterangan','operating_system'=>'Operating System','domain_user'=>'User Domain','join_domain'=>'Join Domain','login_domain'=>'Login Domain'] as $field => $label): ?><div class="<?= in_array($field, ['asset_number','user_name','notes'], true) ? 'wide' : '' ?>"><dt><?= esc($label) ?></dt><dd data-detail="<?= $field ?>"></dd></div><?php endforeach ?></dl><div class="asset-modal-actions"><button type="button" data-modal-close>Tutup</button><button class="modal-edit-switch" type="button">Edit Endpoint</button></div></div>
        <form class="asset-modal-body endpoint-edit-pane" id="endpointModalForm" method="post"><?= csrf_field() ?><div class="modal-form-errors" hidden></div>
            <?php if ($isCombined): ?><div class="modal-form-grid endpoint-organization-grid"><label>Organisasi*<select name="organization_unit" required><option value="KANWIL">Kanwil</option><option value="CABANG">Cabang / KUP</option></select></label></div><?php else: ?><input type="hidden" name="organization_unit" value="<?= esc($unit) ?>"><?php endif ?>
            <div class="modal-form-grid endpoint-form-grid">
                <label>Unit / Cabang*<select name="branch_name" required><?php foreach ($branchOptions as $item): ?><option value="<?= esc($item) ?>"><?= esc($item) ?></option><?php endforeach ?></select></label>
                <label>Hostname*<input name="hostname" required></label><label>IP Address<input name="ip_address" placeholder="192.168.x.x"></label><label>Tipe endpoint*<select name="endpoint_type" required><option value="PC">PC</option><option value="LAPTOP">Laptop</option><option value="TIDAK DIKETAHUI">Tidak Diketahui</option></select></label>
                <label>Status karyawan<input name="employee_status"></label><label>Brand<input name="brand"></label><label>Serial number<input name="serial_number"></label><label>Tahun pengadaan / sewa<input type="number" min="2000" max="2100" name="procurement_year"></label>
                <label class="wide">Nomor aset Oracle<input name="asset_number"></label><label class="wide">User pengguna<input name="user_name"></label><label>Operating system<input name="operating_system"></label><label>User domain<input name="domain_user"></label>
                <label>Join domain<input name="join_domain"></label><label>Login domain<input name="login_domain"></label><label class="wide">Keterangan<input name="notes"></label>
            </div><div class="asset-modal-actions"><button type="button" data-modal-close>Batal</button><button class="modal-save-button" type="submit">Simpan Perubahan</button></div>
        </form>
    </section>
</div>
<script>window.endpointCrudModal = <?= json_encode(['endpoints'=>$endpoints,'storeUrl'=>site_url('operasional-ti/endpoints'),'updateBase'=>site_url('operasional-ti/endpoints'),'unit'=>$unit ?: 'KANWIL','defaultBranch'=>$branch ?: ($unit === 'CABANG' ? 'Surabaya' : 'Kanwil')], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;</script>
<script src="<?= base_url('assets/js/shared-sidebar.js') ?>"></script><script src="<?= base_url('assets/js/endpoint-modal.js') ?>"></script></body></html>
