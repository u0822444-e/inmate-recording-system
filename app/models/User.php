<?php
declare(strict_types=1);

namespace App\Models;

use mysqli;

class User
{
    public function __construct(private mysqli $db) {}

    public function findByCredentials(string $username, string $role): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, username, password, role, full_name
             FROM users
             WHERE username = ? AND role = ? AND is_active = 1
               AND archived_at IS NULL
             LIMIT 1'
        );
        $stmt->bind_param('ss', $username, $role);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $user ?: null;
    }

    public function all(string $q = '', string $role = '', string $status = '', string $scope = 'active'): array
    {
        $sql = 'SELECT id, username, full_name, role, is_active, created_at, archived_at
                FROM users WHERE 1 = 1';
        $params = [];
        $types = '';

        if ($scope === 'archived') {
            $sql .= ' AND archived_at IS NOT NULL';
        } else {
            $sql .= ' AND archived_at IS NULL';
        }

        if ($q !== '') {
            $sql .= ' AND (username LIKE ? OR full_name LIKE ?)';
            $like = '%' . $q . '%';
            $params[] = $like;
            $params[] = $like;
            $types .= 'ss';
        }

        if ($role !== '') {
            $sql .= ' AND role = ?';
            $params[] = $role;
            $types .= 's';
        }

        if ($status === 'active') {
            $sql .= ' AND is_active = 1';
        } elseif ($status === 'inactive') {
            $sql .= ' AND is_active = 0';
        }

        $sql .= ' ORDER BY role ASC, full_name ASC';

        $stmt = $this->db->prepare($sql);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($rows as &$r) {
            $r['id'] = (int) $r['id'];
            $r['is_active'] = (bool) $r['is_active'];
        }
        return $rows;
    }

    public function create(string $username, string $fullName, string $role, string $password): int
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare(
            'INSERT INTO users (username, full_name, role, password, is_active, archived_at)
             VALUES (?, ?, ?, ?, 1, NULL)'
        );
        $stmt->bind_param('ssss', $username, $fullName, $role, $hash);
        $stmt->execute();
        $id = (int) $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    public function update(int $id, string $username, string $fullName, string $role, ?string $password): void
    {
        if ($password !== null && $password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare(
                'UPDATE users SET username = ?, full_name = ?, role = ?, password = ? WHERE id = ?'
            );
            $stmt->bind_param('ssssi', $username, $fullName, $role, $hash, $id);
        } else {
            $stmt = $this->db->prepare(
                'UPDATE users SET username = ?, full_name = ?, role = ? WHERE id = ?'
            );
            $stmt->bind_param('sssi', $username, $fullName, $role, $id);
        }
        $stmt->execute();
        $stmt->close();
    }

    public function toggle(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE users SET is_active = 1 - is_active WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    }

    public function archive(int $id): void
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET archived_at = CURRENT_TIMESTAMP, is_active = 0 WHERE id = ?'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    }

    public function restore(int $id): void
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET archived_at = NULL, is_active = 1 WHERE id = ?'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM users WHERE id = ? AND archived_at IS NOT NULL');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    }

    public function usernameExists(string $username, int $excludeId = 0): bool
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1'
        );
        $stmt->bind_param('si', $username, $excludeId);
        $stmt->execute();
        $exists = (bool) $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $exists;
    }
}