<?php

namespace App\Controllers;

class Auth extends BaseController
{
    /**
     * Maximum number of failed login attempts allowed within the counting window.
     */
    private const MAX_ATTEMPTS = 5;

    /**
     * Duration of the rolling counting window in seconds (10 minutes).
     * A lockout is triggered only when MAX_ATTEMPTS failures occur within this window.
     */
    private const ATTEMPT_WINDOW = 600;

    /**
     * How long the IP stays locked out after reaching MAX_ATTEMPTS (10 minutes).
     */
    private const LOCKOUT_SECONDS = 600;

    // -------------------------------------------------------------------------
    // Environment helpers
    // -------------------------------------------------------------------------

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

    // -------------------------------------------------------------------------
    // Rate-limit storage helpers
    // -------------------------------------------------------------------------

    /**
     * Returns the path to the per-IP flock file.
     * The file serialises concurrent login requests from the same IP so that
     * every read-modify-write on the attempt counter is an atomic transaction.
     */
    private function lockFilePath(): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9_]/', '_', $this->request->getIPAddress());
        return sys_get_temp_dir() . '/ci4_login_lock_' . $safe . '.lock';
    }

    /** Returns the CI4 cache key for the current IP's attempt data. */
    private function rateLimitKey(): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9_]/', '_', $this->request->getIPAddress());
        return 'login_attempts_' . $safe;
    }

    /**
     * Read stored attempt data, returning safe defaults when nothing is stored.
     *
     * Schema:
     *   window_start  — unix timestamp when the current counting window began
     *   attempts      — number of failures recorded in this window
     *   locked_until  — unix timestamp when the lockout expires (0 = not locked)
     */
    private function readAttemptData(): array
    {
        $data = cache()->get($this->rateLimitKey());
        if (! is_array($data)) {
            return ['window_start' => 0, 'attempts' => 0, 'locked_until' => 0];
        }
        return $data;
    }

    /** Persist attempt data; keep for a bit longer than the lockout so expiry is clean. */
    private function writeAttemptData(array $data): void
    {
        cache()->save($this->rateLimitKey(), $data, self::LOCKOUT_SECONDS + 120);
    }

    // -------------------------------------------------------------------------
    // Core login action
    // -------------------------------------------------------------------------

    public function login()
    {
        // Acquire the per-IP exclusive lock before touching any rate-limit state.
        // This makes the entire check→credential-verify→update cycle atomic and
        // prevents concurrent requests from racing past the counter or clearing
        // a lockout that was just set.
        $lockFile = $this->lockFilePath();
        $fh       = fopen($lockFile, 'c');

        if ($fh === false) {
            log_message('error', 'Could not open login rate-limit lock file: {file}', ['file' => $lockFile]);
            // Degrade gracefully: continue without the lock (weaker but not broken)
        } else {
            flock($fh, LOCK_EX);
        }

        try {
            return $this->processLogin();
        } finally {
            if ($fh !== false) {
                flock($fh, LOCK_UN);
                fclose($fh);
            }
        }
    }

    /**
     * All login logic — called inside the per-IP lock so every state
     * transition (read → check → verify → update) is serialised.
     */
    private function processLogin()
    {
        // --- 1. Rolling-window lockout check ------------------------------------
        $data = $this->readAttemptData();
        $now  = time();

        if ($data['locked_until'] > 0 && $now < $data['locked_until']) {
            $remaining = $data['locked_until'] - $now;
            $minutes   = (int) ceil($remaining / 60);
            return redirect()->to('/#login')
                ->with('error', "Terlalu banyak percobaan login. Silakan coba lagi dalam {$minutes} menit.");
        }

        // --- 2. Input validation ------------------------------------------------
        $rules = [
            'username' => 'required|min_length[3]|max_length[50]',
            'password' => 'required|min_length[8]|max_length[255]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->to('/#login')->withInput()->with('errors', $this->validator->getErrors());
        }

        // --- 3. Credential check ------------------------------------------------
        $username  = trim((string) $this->request->getPost('username'));
        $password  = (string) $this->request->getPost('password');
        $adminUser = $this->getAdminUsername();
        $adminHash = $this->getAdminPasswordHash();

        $credentialsValid = $adminUser !== ''
            && $adminHash !== ''
            && hash_equals($adminUser, $username)
            && password_verify($password, $adminHash);

        // --- 4. Update rate-limit state -----------------------------------------
        if (! $credentialsValid) {
            // Reset the window when the current window has expired
            if ($now - $data['window_start'] > self::ATTEMPT_WINDOW) {
                $data['window_start'] = $now;
                $data['attempts']     = 0;
                $data['locked_until'] = 0;
            }

            $data['attempts']++;

            if ($data['attempts'] >= self::MAX_ATTEMPTS) {
                $data['locked_until'] = $now + self::LOCKOUT_SECONDS;
                $this->writeAttemptData($data);

                log_message('notice', 'Login locked for IP {ip} after {n} failed attempts', [
                    'ip' => $this->request->getIPAddress(),
                    'n'  => $data['attempts'],
                ]);

                $minutes = (int) ceil(self::LOCKOUT_SECONDS / 60);
                return redirect()->to('/#login')
                    ->with('error', "Terlalu banyak percobaan login. Akun diblokir sementara selama {$minutes} menit.");
            }

            $this->writeAttemptData($data);

            log_message('notice', 'Failed login attempt {n}/{max} from IP {ip}', [
                'n'   => $data['attempts'],
                'max' => self::MAX_ATTEMPTS,
                'ip'  => $this->request->getIPAddress(),
            ]);

            return redirect()->to('/#login')->withInput()->with('error', 'Username atau password tidak sesuai.');
        }

        // --- 5. Successful login — clear rate-limit state and start session ------
        cache()->delete($this->rateLimitKey());

        session()->regenerate(true);
        session()->set([
            'isLoggedIn' => true,
            'username'   => $adminUser,
            'loginAt'    => date('d M Y, H:i'),
        ]);

        return redirect()->to('/dashboard')->with('success', 'Selamat datang di Sistem Jamkrindo.');
    }

    // -------------------------------------------------------------------------

    public function logout()
    {
        session()->destroy();

        return redirect()->to('/')->with('success', 'Anda telah keluar dari sistem.');
    }
}
