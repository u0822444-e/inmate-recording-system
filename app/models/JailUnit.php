<?php
declare(strict_types=1);

namespace App\Models;

use mysqli;

class JailUnit
{
    public function __construct(private mysqli $db) {}

    public function all(bool $activeOnly = true): array
    {
        $sql = 'SELECT id, name, type, dormitory, municipality, province, warden, is_active
                FROM jail_units';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY type ASC, name ASC';

        try {
            $result = $this->db->query($sql);
            $rows   = $result->fetch_all(MYSQLI_ASSOC);
            foreach ($rows as &$r) {
                $r['id']        = (int) $r['id'];
                $r['is_active'] = (bool) $r['is_active'];
            }
            return $rows;
        } catch (\Throwable $e) {
            error_log('JailUnit::all: ' . $e->getMessage());
            return [];
        }
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, type, dormitory, municipality, province, warden, is_active
             FROM jail_units WHERE id = ? LIMIT 1'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO jail_units (name, type, dormitory, municipality, province, warden, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $isActive = !empty($data['is_active']) ? 1 : 0;
        $stmt->bind_param(
            'ssssssi',
            $data['name'],
            $data['type'],
            $data['dormitory'],
            $data['municipality'],
            $data['province'],
            $data['warden'],
            $isActive
        );
        $stmt->execute();
        $id = (int) $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE jail_units SET
                name = ?, type = ?, dormitory = ?, municipality = ?,
                province = ?, warden = ?, is_active = ?
             WHERE id = ?'
        );
        $isActive = !empty($data['is_active']) ? 1 : 0;
        $stmt->bind_param(
            'ssssssii',
            $data['name'],
            $data['type'],
            $data['dormitory'],
            $data['municipality'],
            $data['province'],
            $data['warden'],
            $isActive,
            $id
        );
        $stmt->execute();
        $stmt->close();
    }

    public function nameExists(string $name, int $excludeId = 0): bool
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM jail_units WHERE name = ? AND id <> ? LIMIT 1'
        );
        $stmt->bind_param('si', $name, $excludeId);
        $stmt->execute();
        $exists = (bool) $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $exists;
    }

    public function toggle(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE jail_units SET is_active = 1 - is_active WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    }
}