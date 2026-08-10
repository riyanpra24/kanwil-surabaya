<?php

namespace App\Libraries;

use PDO;

class UserRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = new PDO('sqlite:' . WRITEPATH . 'assets.sqlite');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->initialize();
    }

    private function initialize(): void
    {
        $this->db->exec('CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            is_admin INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )');
    }

    public function all(): array
    {
        return $this->db->query('SELECT id, username, is_admin, created_at FROM users ORDER BY id')->fetchAll();
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = :username');
        $stmt->execute(['username' => $username]);
        return $stmt->fetch() ?: null;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, username, is_admin, created_at FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function usernameExists(string $username, int $excludeId = 0): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE username = :username AND id != :id');
        $stmt->execute(['username' => $username, 'id' => $excludeId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function insert(string $username, string $password, bool $isAdmin = false): bool
    {
        $now  = date('Y-m-d H:i:s');
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare(
            'INSERT INTO users (username, password_hash, is_admin, created_at, updated_at) VALUES (:u, :h, :a, :c, :up)'
        );
        return $stmt->execute(['u' => $username, 'h' => $hash, 'a' => (int) $isAdmin, 'c' => $now, 'up' => $now]);
    }

    public function changePassword(int $id, string $newPassword): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET password_hash = :h, updated_at = :u WHERE id = :id');
        return $stmt->execute(['h' => password_hash($newPassword, PASSWORD_BCRYPT), 'u' => date('Y-m-d H:i:s'), 'id' => $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM users WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function count(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }
}
