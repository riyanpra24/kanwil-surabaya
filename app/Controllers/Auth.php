<?php

namespace App\Controllers;

class Auth extends BaseController
{
    private function getUsername(): string
    {
        return (string) (getenv('ADMIN_USERNAME') ?: 'admin');
    }

    private function getPasswordHash(): string
    {
        return (string) (getenv('ADMIN_PASSWORD_HASH') ?: '$2y$10$54m2Zhff1tj1crKWjMOr6ekfOrGVQlkVhzhBZd3ymixBDw0D1ocRO');
    }

    public function login()
    {
        $rules = [
            'username' => 'required|min_length[3]|max_length[50]',
            'password' => 'required|min_length[8]|max_length[255]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->to('/#login')->withInput()->with('errors', $this->validator->getErrors());
        }

        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('password');

        if (! hash_equals($this->getUsername(), $username) || ! password_verify($password, $this->getPasswordHash())) {
            return redirect()->to('/#login')->withInput()->with('error', 'Username atau password tidak sesuai.');
        }

        session()->regenerate(true);
        session()->set([
            'isLoggedIn' => true,
            'username'   => $username,
            'loginAt'    => date('d M Y, H:i'),
        ]);

        return redirect()->to('/dashboard')->with('success', 'Selamat datang di Sistem Jamkrindo.');
    }

    public function logout()
    {
        session()->destroy();

        return redirect()->to('/')->with('success', 'Anda telah keluar dari sistem.');
    }
}
