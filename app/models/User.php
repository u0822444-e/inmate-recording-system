<?php
declare(strict_types=1);

namespace App\Models;

use mysqli;

class User
{
    public function __construct(private mysqli $db)
    {
    }

    public function findByCredentials(string $username, string $role): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, username, password, role, full_name, jail_unit_id
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

    public function all(
        string $q = '',
        string $role = '',
        string $status = '',
        string $scope = 'active',
        int $jailUnitId = 0
    ): array {
        $sql = 'SELECT u.id, u.username, u.jail_unit_id,
                   u.first_name, u.middle_name, u.last_name, u.full_name,
                   u.email, u.phone, u.birthdate, u.sex, u.civil_status,
                   u.employee_no, u.`position`, u.`rank`, u.department, u.date_hired, u.employment_status,
                   u.bjmp_rank, u.salary_grade, u.personnel_type, u.eligibility,
                   u.role, u.is_active, u.created_at, u.archived_at,
                   j.name AS jail_unit_name
            FROM users u
            LEFT JOIN jail_units j ON j.id = u.jail_unit_id
            WHERE 1 = 1';

        $params = [];
        $types = '';

        $sql .= $scope === 'archived'
            ? ' AND u.archived_at IS NOT NULL'
            : ' AND u.archived_at IS NULL';

        if ($q !== '') {
            $sql .= ' AND (u.username LIKE ? OR u.full_name LIKE ? OR u.employee_no LIKE ?)';
            $like = '%' . $q . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $types .= 'sss';
        }

        if ($role !== '') {
            $sql .= ' AND u.role = ?';
            $params[] = $role;
            $types .= 's';
        }

        if ($status === 'active') {
            $sql .= ' AND u.is_active = 1';
        } elseif ($status === 'inactive') {
            $sql .= ' AND u.is_active = 0';
        }

        if ($jailUnitId > 0) {
            $sql .= ' AND u.jail_unit_id = ?';
            $params[] = $jailUnitId;
            $types .= 'i';
        }

        $sql .= ' ORDER BY u.role ASC, u.last_name ASC, u.first_name ASC';

        $stmt = $this->db->prepare($sql);
        if ($params)
            $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($rows as &$r) {
            $r['id'] = (int) $r['id'];
            $r['jail_unit_id'] = $r['jail_unit_id'] !== null ? (int) $r['jail_unit_id'] : null;
            $r['is_active'] = (bool) $r['is_active'];
            $r['salary_grade'] = $r['salary_grade'] !== null ? (int) $r['salary_grade'] : null;
        }
        return $rows;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT u.*, j.name AS jail_unit_name
             FROM users u
             LEFT JOIN jail_units j ON j.id = u.jail_unit_id
             WHERE u.id = ? LIMIT 1'
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
             (username, jail_unit_id, first_name, middle_name, last_name, full_name,
              email, phone, birthdate, sex, civil_status,
              employee_no, `position`, `rank`, department, date_hired, employment_status,
              bjmp_rank, salary_grade, personnel_type, eligibility,
              role, password, is_active, archived_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NULL)'
        );

        $params = [
            $data['username'],
            $data['jail_unit_id'],
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
            $data['bjmp_rank'],
            $data['salary_grade'],
            $data['personnel_type'],
            $data['eligibility'],
            $data['role'],
            $hash,
        ];

        $types = '';
        foreach ($params as $p) {
            $types .= is_int($p) ? 'i' : 's';
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $id = (int) $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    public function update(int $id, array $data): void
    {
        $sql = 'UPDATE users SET
                    username = ?,
                    jail_unit_id = ?,
                    first_name = ?, middle_name = ?, last_name = ?, full_name = ?,
                    email = ?, phone = ?, birthdate = ?, sex = ?, civil_status = ?,
                    employee_no = ?, `position` = ?, `rank` = ?, department = ?, date_hired = ?, employment_status = ?,
                    bjmp_rank = ?, salary_grade = ?, personnel_type = ?, eligibility = ?,
                    role = ?';

        $params = [
            $data['username'],
            $data['jail_unit_id'],
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
            $data['bjmp_rank'],
            $data['salary_grade'],
            $data['personnel_type'],
            $data['eligibility'],
            $data['role'],
        ];

        if (!empty($data['password'])) {
            $sql .= ', password = ?';
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $sql .= ' WHERE id = ?';
        $params[] = $id;

        $types = '';
        foreach ($params as $p) {
            $types .= is_int($p) ? 'i' : 's';
        }

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

    public function generateUsername(string $firstName, string $lastName, int $excludeId = 0): string
    {
        $base = strtolower(
            preg_replace('/[^a-z0-9]/i', '', $firstName) . '.' .
            preg_replace('/[^a-z0-9]/i', '', $lastName)
        );
        $base = trim($base, '.');
        if ($base === '') {
            $base = 'user';
        }

        $candidate = $base;
        $suffix = 1;

        while ($this->usernameExists($candidate, $excludeId)) {
            $candidate = $base . $suffix;
            $suffix++;
        }

        return $candidate;
    }
}