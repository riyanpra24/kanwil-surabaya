<?php

namespace App\Controllers;

use App\Libraries\UserRepository;

class Users extends BaseController
{
    private UserRepository $users;

    public function __construct()
    {
        $this->users = new UserRepository();
    }

    private function requireAdmin(): ?object
    {
        if (! session('isAdmin')) {
            return redirect()->to('/dashboard')->with('error', 'Halaman ini hanya dapat diakses oleh admin.');
        }
        return null;
    }

    public function index(): string|object
    {
        if ($redirect = $this->requireAdmin()) return $redirect;

        return view('users/index', [
            'sidebarActive' => 'users',
            'users'         => $this->users->all(),
            'envAdminName'  => (string) (getenv('ADMIN_USERNAME') ?: ''),
        ]);
    }

    public function store(): object
    {
        if ($redirect = $this->requireAdmin()) return $redirect;

        $rules = [
            'username' => 'required|min_length[3]|max_length[50]|alpha_dash',
            'password' => 'required|min_length[8]|max_length[255]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->to('/kelola-user')->withInput()->with('errors', $this->validator->getErrors());
        }

        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('password');
        $isAdmin  = (bool) $this->request->getPost('is_admin');

        // Cegah duplikat dengan env admin
        $envAdmin = (string) (getenv('ADMIN_USERNAME') ?: '');
        if ($envAdmin !== '' && strtolower($username) === strtolower($envAdmin)) {
            return redirect()->to('/kelola-user')->withInput()->with('error', 'Username sudah digunakan oleh akun admin utama.');
        }

        if ($this->users->usernameExists($username)) {
            return redirect()->to('/kelola-user')->withInput()->with('error', 'Username sudah digunakan, pilih username lain.');
        }

        $this->users->insert($username, $password, $isAdmin);
        return redirect()->to('/kelola-user')->with('success', "User \"$username\" berhasil ditambahkan.");
    }

    public function changePassword(int $id): object
    {
        if ($redirect = $this->requireAdmin()) return $redirect;

        $user = $this->users->find($id);
        if (! $user) {
            return redirect()->to('/kelola-user')->with('error', 'User tidak ditemukan.');
        }

        $rules = ['new_password' => 'required|min_length[8]|max_length[255]'];
        if (! $this->validate($rules)) {
            return redirect()->to('/kelola-user')->with('errors', $this->validator->getErrors());
        }

        $this->users->changePassword($id, (string) $this->request->getPost('new_password'));
        return redirect()->to('/kelola-user')->with('success', "Password user \"{$user['username']}\" berhasil diubah.");
    }

    public function delete(int $id): object
    {
        if ($redirect = $this->requireAdmin()) return $redirect;

        $user = $this->users->find($id);
        if (! $user) {
            return redirect()->to('/kelola-user')->with('error', 'User tidak ditemukan.');
        }

        // Jangan hapus diri sendiri
        if ($user['username'] === session('username')) {
            return redirect()->to('/kelola-user')->with('error', 'Tidak bisa menghapus akun yang sedang aktif.');
        }

        $this->users->delete($id);
        return redirect()->to('/kelola-user')->with('success', "User \"{$user['username']}\" berhasil dihapus.");
    }
}
