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
        $sql = 'SELECT id, username, first_name, middle_name, last_name, full_name,
                       email, phone, birthdate, sex, civil_status,
                       employee_no, position, rank, department, date_hired, employment_status,
                       role, is_active, created_at, archived_at
                FROM users WHERE 1 = 1';
        $params = [];
        $types = '';

        $sql .= $scope === 'archived'
            ? ' AND archived_at IS NOT NULL'
            : ' AND archived_at IS NULL';

        if ($q !== '') {
            $sql .= ' AND (username LIKE ? OR full_name LIKE ? OR employee_no LIKE ?)';
            $like = '%' . $q . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $types .= 'sss';
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

        $sql .= ' ORDER BY role ASC, last_name ASC, first_name ASC';

        $stmt = $this->db->prepare($sql);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($rows as &$r) {
            $r['id']        = (int) $r['id'];
            $r['is_active'] = (bool) $r['is_active'];
        }
        return $rows;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM users WHERE id = ? LIMIT 1'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $user ?: null;
    }

    public function create(array $data): int
    {
        $hash = password_hash($data['password'], PASSWORD_DEFAULT);

        $stmt = $this->db->prepare(
            'INSERT INTO users
             (username, first_name, middle_name, last_name, full_name,
              email, phone, birthdate, sex, civil_status,
              employee_no, position, rank, department, date_hired, employment_status,
              role, password, is_active, archived_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NULL)'
        );

        $stmt->bind_param(
            'ssssssssssssssssss',
            $data['username'],
            $data['first_name'],
            $data['middle_name'],
            $data['last_name'],
            $data['full_name'],
            $data['email'],
            $data['phone'],
            $data['birthdate'],
            $data['sex'],
            $data['civil_status'],
            $data['employee_no'],
            $data['position'],
            $data['rank'],
            $data['department'],
            $data['date_hired'],
            $data['employment_status'],
            $data['role'],
            $hash
        );

        $stmt->execute();
        $id = (int) $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    public function update(int $id, array $data): void
    {
        $sql = 'UPDATE users SET
                    username = ?, first_name = ?, middle_name = ?, last_name = ?, full_name = ?,
                    email = ?, phone = ?, birthdate = ?, sex = ?, civil_status = ?,
                    employee_no = ?, position = ?, rank = ?, department = ?, date_hired = ?, employment_status = ?,
                    role = ?';

        $params = [
            $data['username'],
            $data['first_name'],
            $data['middle_name'],
            $data['last_name'],
            $data['full_name'],
            $data['email'],
            $data['phone'],
            $data['birthdate'],
            $data['sex'],
            $data['civil_status'],
            $data['employee_no'],
            $data['position'],
            $data['rank'],
            $data['department'],
            $data['date_hired'],
            $data['employment_status'],
            $data['role'],
        ];
        $types = 'sssssssssssssssss';

        if (!empty($data['password'])) {
            $sql .= ', password = ?';
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
            $types .= 's';
        }

        $sql .= ' WHERE id = ?';
        $params[] = $id;
        $types .= 'i';

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
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

    /**
     * Generate a unique username "{first}.{last}" lowercased.
     * Appends a numeric suffix if taken.
     */
    public function generateUsername(string $firstName, string $lastName, int $excludeId = 0): string
    {
        $base = strtolower(
            preg_replace('/[^a-z0-9]/i', '', $firstName) . '.' .
            preg_replace('/[^a-z0-9]/i', '', $lastName)
        );
        $base = trim($base, '.');

        if ($base === '') $base = 'user';

        $candidate = $base;
        $suffix = 1;

        while ($this->usernameExists($candidate, $excludeId)) {
            $candidate = $base . $suffix;
            $suffix++;
        }

        return $candidate;
    }
}