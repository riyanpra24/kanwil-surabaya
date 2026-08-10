<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Kelola User — Jamkrindo Kanwil Surabaya</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= base_url('assets/css/dashboard.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/dashboard-menu.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/assets-crud.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/shared-sidebar-pages.css') ?>">
<style>
.user-table { width:100%; border-collapse:collapse; }
.user-table th { text-align:left; padding:.6rem 1rem; font-size:.72rem; text-transform:uppercase; letter-spacing:.06em; color:#64748b; border-bottom:1px solid #e2e8f0; }
.user-table td { padding:.75rem 1rem; border-bottom:1px solid #f1f5f9; font-size:.875rem; vertical-align:middle; }
.user-table tr:last-child td { border-bottom:none; }
.badge { display:inline-block; padding:.18em .55em; border-radius:.3em; font-size:.72rem; font-weight:700; letter-spacing:.04em; }
.badge-admin-utama { background:#fee2e2; color:#991b1b; }
.badge-admin { background:#fef3c7; color:#92400e; }
.badge-user { background:#e2e8f0; color:#475569; }
.badge-saya { background:#dbeafe; color:#1e40af; }
.form-card { background:#fff; border:1px solid #e2e8f0; border-radius:.75rem; padding:1.5rem; margin-bottom:1.5rem; }
.form-card h2 { font-size:1rem; font-weight:700; margin:0 0 1.25rem; }
.field-row { display:flex; gap:1rem; flex-wrap:wrap; align-items:flex-end; }
.field-group { display:flex; flex-direction:column; gap:.35rem; }
.field-group label { font-size:.8rem; font-weight:600; color:#475569; }
.field-group input[type=text], .field-group input[type=password] { border:1px solid #cbd5e1; border-radius:.45rem; padding:.5rem .75rem; font-size:.875rem; font-family:inherit; min-width:200px; }
.field-group input:focus { outline:2px solid #3b82f6; outline-offset:1px; border-color:transparent; }
.check-group { display:flex; align-items:center; gap:.5rem; padding-bottom:.55rem; }
.check-group input { width:1rem; height:1rem; }
.check-group label { font-size:.875rem; font-weight:600; }
.btn-primary-sm { background:#2563eb; color:#fff; border:none; border-radius:.45rem; padding:.5rem 1.25rem; font-size:.875rem; font-weight:600; cursor:pointer; white-space:nowrap; }
.btn-primary-sm:hover { background:#1d4ed8; }
.btn-outline-sm { background:transparent; border:1px solid #cbd5e1; border-radius:.45rem; padding:.4rem .9rem; font-size:.8rem; font-weight:600; cursor:pointer; color:#475569; }
.btn-outline-sm:hover { background:#f8fafc; }
.btn-danger-sm { background:transparent; border:1px solid #fca5a5; border-radius:.45rem; padding:.4rem .9rem; font-size:.8rem; font-weight:600; cursor:pointer; color:#dc2626; }
.btn-danger-sm:hover { background:#fef2f2; }
.flash { padding:.85rem 1.1rem; border-radius:.5rem; margin-bottom:1rem; font-size:.875rem; }
.flash.success { background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; }
.flash.error { background:#fef2f2; border:1px solid #fecaca; color:#991b1b; }
.modal-backdrop { display:none; position:fixed; inset:0; background:rgba(0,0,0,.4); z-index:1000; align-items:center; justify-content:center; }
.modal-backdrop.open { display:flex; }
.modal-box { background:#fff; border-radius:.75rem; padding:1.75rem; width:100%; max-width:380px; box-shadow:0 20px 60px rgba(0,0,0,.2); }
.modal-box h3 { font-size:1rem; font-weight:700; margin:0 0 1rem; }
.modal-box input[type=password] { width:100%; box-sizing:border-box; border:1px solid #cbd5e1; border-radius:.45rem; padding:.5rem .75rem; font-size:.875rem; font-family:inherit; margin:.5rem 0 1rem; }
.modal-box input:focus { outline:2px solid #3b82f6; outline-offset:1px; border-color:transparent; }
.modal-actions { display:flex; gap:.75rem; justify-content:flex-end; }
.hint { font-size:.75rem; color:#94a3b8; margin:.2rem 0 0; }
</style>
</head>
<body class="has-shared-sidebar">
<?= view('partials/sidebar', ['sidebarActive' => 'users']) ?>
<div class="crud-shell">
    <header class="crud-topbar">
        <button class="crud-menu-toggle" type="button">☰</button>
        <div><small>MANAJEMEN SISTEM</small><strong>Kelola User</strong></div>
        <a href="<?= site_url('logout') ?>">Keluar ↗</a>
    </header>
    <main>
        <section class="crud-heading">
            <div>
                <p>PENGELOLAAN AKSES</p>
                <h1>Kelola User</h1>
                <span>Tambah, hapus, dan atur password pengguna sistem.</span>
            </div>
        </section>

        <?php if (session('success')): ?>
            <div class="flash success"><?= esc(session('success')) ?></div>
        <?php endif ?>
        <?php if (session('error')): ?>
            <div class="flash error"><?= esc(session('error')) ?></div>
        <?php endif ?>
        <?php if (session('errors')): ?>
            <div class="flash error">
                <?php foreach ((array) session('errors') as $e): ?>
                    <div><?= esc($e) ?></div>
                <?php endforeach ?>
            </div>
        <?php endif ?>

        <!-- Form Tambah User -->
        <div class="form-card">
            <h2>Tambah User Baru</h2>
            <form method="POST" action="<?= site_url('kelola-user') ?>" autocomplete="off">
                <?= csrf_field() ?>
                <div class="field-row">
                    <div class="field-group">
                        <label for="f_username">Username</label>
                        <input type="text" id="f_username" name="username" value="<?= esc(old('username')) ?>" required minlength="3" maxlength="50">
                        <span class="hint">Huruf, angka, underscore, tanda hubung.</span>
                    </div>
                    <div class="field-group">
                        <label for="f_password">Password</label>
                        <input type="password" id="f_password" name="password" required minlength="8" maxlength="255">
                        <span class="hint">Minimal 8 karakter.</span>
                    </div>
                    <div class="check-group">
                        <input type="checkbox" id="f_is_admin" name="is_admin" value="1">
                        <label for="f_is_admin">Jadikan Admin</label>
                    </div>
                    <div class="field-group">
                        <label style="visibility:hidden">Tambah</label>
                        <button type="submit" class="btn-primary-sm">+ Tambah User</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Tabel Daftar User -->
        <section class="crud-card">
            <div style="padding:.75rem 1rem .5rem; display:flex; justify-content:space-between; align-items:center;">
                <strong style="font-size:.9rem;">Daftar Pengguna Aktif</strong>
                <span style="font-size:.8rem; color:#64748b;"><?= count($users) + 1 ?> user terdaftar</span>
            </div>
            <div class="crud-table-wrap" style="overflow-x:auto;">
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Dibuat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Admin utama (env var) -->
                        <tr>
                            <td style="color:#94a3b8;">—</td>
                            <td>
                                <strong><?= esc($envAdminName) ?></strong>
                                <?php if ($envAdminName === session('username')): ?>
                                    <span class="badge badge-saya" style="margin-left:.4rem;">Anda</span>
                                <?php endif ?>
                            </td>
                            <td><span class="badge badge-admin-utama">Admin Utama</span></td>
                            <td style="color:#94a3b8; font-size:.8rem;"><em>Sistem</em></td>
                            <td style="color:#94a3b8; font-size:.8rem;"><em>Dikelola via konfigurasi server</em></td>
                        </tr>

                        <?php if (empty($users)): ?>
                            <tr><td colspan="5" style="text-align:center; padding:2rem; color:#94a3b8;">Belum ada user tambahan.</td></tr>
                        <?php else: ?>
                            <?php foreach ($users as $i => $u): ?>
                                <tr>
                                    <td style="color:#94a3b8;"><?= $i + 1 ?></td>
                                    <td>
                                        <?= esc($u['username']) ?>
                                        <?php if ($u['username'] === session('username')): ?>
                                            <span class="badge badge-saya" style="margin-left:.4rem;">Anda</span>
                                        <?php endif ?>
                                    </td>
                                    <td>
                                        <?php if ($u['is_admin']): ?>
                                            <span class="badge badge-admin">Admin</span>
                                        <?php else: ?>
                                            <span class="badge badge-user">User</span>
                                        <?php endif ?>
                                    </td>
                                    <td style="font-size:.8rem; color:#64748b;"><?= esc(date('d M Y', strtotime($u['created_at']))) ?></td>
                                    <td style="display:flex; gap:.5rem; align-items:center;">
                                        <button class="btn-outline-sm"
                                                onclick="openPassModal(<?= $u['id'] ?>, '<?= esc($u['username']) ?>')">
                                            Ganti Password
                                        </button>
                                        <?php if ($u['username'] !== session('username')): ?>
                                            <form method="POST" action="<?= site_url('kelola-user/' . $u['id'] . '/hapus') ?>"
                                                  onsubmit="return confirm('Yakin hapus user <?= esc($u['username']) ?>?')">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn-danger-sm">Hapus</button>
                                            </form>
                                        <?php endif ?>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        <?php endif ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<!-- Modal Ganti Password -->
<div class="modal-backdrop" id="passModal" onclick="if(event.target===this) closePassModal()">
    <div class="modal-box">
        <h3 id="passModalTitle">Ganti Password</h3>
        <form method="POST" id="passModalForm">
            <?= csrf_field() ?>
            <label style="font-size:.8rem; font-weight:600; color:#475569;">Password Baru</label>
            <input type="password" name="new_password" id="passInput" required minlength="8" maxlength="255" placeholder="Minimal 8 karakter">
            <div class="modal-actions">
                <button type="button" class="btn-outline-sm" onclick="closePassModal()">Batal</button>
                <button type="submit" class="btn-primary-sm">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openPassModal(id, username) {
    document.getElementById('passModalTitle').textContent = 'Ganti Password — ' + username;
    document.getElementById('passModalForm').action = '<?= site_url('kelola-user/') ?>' + id + '/ganti-password';
    document.getElementById('passInput').value = '';
    document.getElementById('passModal').classList.add('open');
    setTimeout(function(){ document.getElementById('passInput').focus(); }, 100);
}
function closePassModal() {
    document.getElementById('passModal').classList.remove('open');
}
</script>
<script src="<?= base_url('assets/js/shared-sidebar.js') ?>"></script>
</body></html>
