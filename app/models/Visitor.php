<?php
declare(strict_types=1);

namespace App\Models;

use mysqli;

class Visitor
{
    public function __construct(private mysqli $db) {}

    /* ============================================================
       LIST — with filters
       ============================================================ */

    public function all(
        string $q = '',
        int $jailUnitId = 0,
        string $status = '',
        string $date = '',
        int $limit = 20,
        int $offset = 0
    ): array {
        // ✅ Pointing to 'visitors' table and using correct columns
        $sql = 'SELECT v.id, v.inmate_id, v.jail_unit_id,
                       v.full_name,
                       v.relationship,
                       v.contact_no,
                       v.address,
                       v.id_type,
                       v.id_number,
                       v.purpose,
                       v.time_in,
                       v.time_out,
                       v.recorded_by, v.created_at,
                       CONCAT_WS(" ", i.first_name, i.middle_name, i.last_name) AS inmate_name,
                       i.inmate_number AS inmate_number,
                       j.name AS jail_unit_name,
                       u.full_name AS recorded_by_name
                FROM visitors v
                LEFT JOIN inmates    i ON i.id = v.inmate_id
                LEFT JOIN jail_units j ON j.id = v.jail_unit_id
                LEFT JOIN users      u ON u.id = v.recorded_by
                WHERE 1 = 1';

        $params = [];
        $types  = '';

        if ($q !== '') {
            $sql .= ' AND (v.full_name LIKE ?
                        OR v.id_number LIKE ?
                        OR i.inmate_number LIKE ?
                        OR CONCAT_WS(" ", i.first_name, i.middle_name, i.last_name) LIKE ?)';
            $like = '%' . $q . '%';
            for ($i = 0; $i < 4; $i++) { $params[] = $like; $types .= 's'; }
        }

        if ($jailUnitId > 0) {
            $sql .= ' AND v.jail_unit_id = ?';
            $params[] = $jailUnitId;
            $types .= 'i';
        }

        if ($status === 'in') {
            $sql .= ' AND v.time_out IS NULL';
        } elseif ($status === 'out') {
            $sql .= ' AND v.time_out IS NOT NULL';
        }

        if ($date !== '') {
            $sql .= ' AND DATE(v.time_in) = ?';
            $params[] = $date;
            $types .= 's';
        }

        // Count
        $countSql = 'SELECT COUNT(*) AS c FROM (' . $sql . ') AS t';
        $stmt = $this->db->prepare($countSql);
        if ($params) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $total = (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0);
        $stmt->close();

        // Paginate
        $sql .= ' ORDER BY v.time_in DESC, v.id DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';

        $stmt = $this->db->prepare($sql);
        if ($params) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($rows as &$r) {
            $r['id']           = (int) $r['id'];
            $r['inmate_id']    = $r['inmate_id']    !== null ? (int) $r['inmate_id']    : null;
            $r['jail_unit_id'] = $r['jail_unit_id'] !== null ? (int) $r['jail_unit_id'] : null;
            $r['recorded_by']  = (int) $r['recorded_by'];
            $r['is_inside']    = ($r['time_out'] === null);
        }

        return ['rows' => $rows, 'total' => $total];
    }

    /* ============================================================
       FIND
       ============================================================ */

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT v.*,
                    CONCAT_WS(" ", i.first_name, i.middle_name, i.last_name) AS inmate_name,
                    i.inmate_number AS inmate_number,
                    j.name AS jail_unit_name,
                    u.full_name AS recorded_by_name
             FROM visitors v
             LEFT JOIN inmates    i ON i.id = v.inmate_id
             LEFT JOIN jail_units j ON j.id = v.jail_unit_id
             LEFT JOIN users      u ON u.id = v.recorded_by
             WHERE v.id = ? LIMIT 1'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) return null;

        $row['id']           = (int) $row['id'];
        $row['inmate_id']    = $row['inmate_id']    !== null ? (int) $row['inmate_id']    : null;
        $row['jail_unit_id'] = $row['jail_unit_id'] !== null ? (int) $row['jail_unit_id'] : null;
        $row['recorded_by']  = (int) $row['recorded_by'];
        return $row;
    }

    /* ============================================================
       CREATE
       ============================================================ */

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO visitors
             (jail_unit_id, inmate_id, full_name, relationship, contact_no, address,
              id_type, id_number, time_in, time_out, purpose, notes, recorded_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $stmt->bind_param(
            'iissssssssssi',
            $data['jail_unit_id'],
            $data['inmate_id'],
            $data['full_name'],
            $data['relationship'],
            $data['contact_no'],
            $data['address'],
            $data['id_type'],
            $data['id_number'],
            $data['time_in'],
            $data['time_out'],
            $data['purpose'],
            $data['notes'],
            $data['recorded_by']
        );

        $stmt->execute();
        $id = (int) $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    /* ============================================================
       UPDATE
       ============================================================ */

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE visitors SET
                inmate_id    = ?,
                full_name    = ?,
                relationship = ?,
                contact_no   = ?,
                address      = ?,
                id_type      = ?,
                id_number    = ?,
                purpose      = ?,
                time_in      = ?,
                time_out     = ?
             WHERE id = ?'
        );

        $stmt->bind_param(
            'isssssssssi',
            $data['inmate_id'],
            $data['full_name'],
            $data['relationship'],
            $data['contact_no'],
            $data['address'],
            $data['id_type'],
            $data['id_number'],
            $data['purpose'],
            $data['time_in'],
            $data['time_out'],
            $id
        );

        $stmt->execute();
        $stmt->close();
    }

    /* ============================================================
       CHECKOUT
       ============================================================ */

    public function checkout(int $id): void
    {
        $stmt = $this->db->prepare(
            'UPDATE visitors
             SET time_out = CURRENT_TIMESTAMP
             WHERE id = ? AND time_out IS NULL'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    }

    /* ============================================================
       DELETE
       ============================================================ */

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM visitors WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    }

    /* ============================================================
       STATS
       ============================================================ */

    public function currentlyInside(int $jailUnitId = 0): int
    {
        $sql = 'SELECT COUNT(*) AS c
                FROM visitors v
                WHERE v.time_out IS NULL';
        if ($jailUnitId > 0) {
            $sql .= ' AND v.jail_unit_id = ' . $jailUnitId;
        }
        $result = $this->db->query($sql);
        return (int) ($result->fetch_assoc()['c'] ?? 0);
    }
}