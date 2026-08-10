<?php

namespace App\Controllers;

use App\Libraries\UserRepository;

class Auth extends BaseController
{
    private function getEnvAdmin(): array
    {
        return [
            'username' => (string) (getenv('ADMIN_USERNAME') ?: ''),
            'hash'     => (string) (getenv('ADMIN_PASSWORD_HASH') ?: ''),
        ];
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

        // Cek tabel users di database terlebih dahulu
        $users   = new UserRepository();
        $dbUser  = $users->findByUsername($username);

        if ($dbUser && password_verify($password, $dbUser['password_hash'])) {
            session()->regenerate(true);
            session()->set([
                'isLoggedIn' => true,
                'username'   => $dbUser['username'],
                'isAdmin'    => (bool) $dbUser['is_admin'],
                'loginAt'    => date('d M Y, H:i'),
            ]);
            return redirect()->to('/dashboard')->with('success', 'Selamat datang di Sistem Jamkrindo.');
        }

        // Fallback: cek env var admin
        $env = $this->getEnvAdmin();
        if ($env['username'] !== '' && $env['hash'] !== ''
            && hash_equals($env['username'], $username)
            && password_verify($password, $env['hash'])
        ) {
            session()->regenerate(true);
            session()->set([
                'isLoggedIn' => true,
                'username'   => $env['username'],
                'isAdmin'    => true,
                'loginAt'    => date('d M Y, H:i'),
            ]);
            return redirect()->to('/dashboard')->with('success', 'Selamat datang di Sistem Jamkrindo.');
        }

        return redirect()->to('/#login')->withInput()->with('error', 'Username atau password tidak sesuai.');
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/')->with('success', 'Anda telah keluar dari sistem.');
    }
}
