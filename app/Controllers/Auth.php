<?php

namespace App\Controllers;

class Auth extends BaseController
{
    private function getAdminUsername(): string
    {
        $username = getenv('ADMIN_USERNAME');
        if ($username === false || $username === '') {
            log_message('error', 'ADMIN_USERNAME environment variable is not set.');
            return '';
        }
        return $username;
    }

    private function getAdminPasswordHash(): string
    {
        $hash = getenv('ADMIN_PASSWORD_HASH');
        if ($hash === false || $hash === '') {
            log_message('error', 'ADMIN_PASSWORD_HASH environment variable is not set.');
            return '';
        }
        return $hash;
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

        $username     = trim((string) $this->request->getPost('username'));
        $password     = (string) $this->request->getPost('password');
        $adminUser    = $this->getAdminUsername();
        $adminHash    = $this->getAdminPasswordHash();

        if ($adminUser === '' || $adminHash === '' || ! hash_equals($adminUser, $username) || ! password_verify($password, $adminHash)) {
            return redirect()->to('/#login')->withInput()->with('error', 'Username atau password tidak sesuai.');
        }

        session()->regenerate(true);
        session()->set([
            'isLoggedIn' => true,
            'username'   => $adminUser,
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
