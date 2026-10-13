<?php
declare(strict_types=1);

namespace App\Models;

use mysqli;

class Incident
{
    public function __construct(private mysqli $db) {}

    /* ============================================================
       LIST
       ============================================================ */

    public function all(
        string $q = '',
        int $jailUnitId = 0,
        string $type = '',
        string $severity = '',
        string $status = '',
        string $date = '',
        int $municipalityId = 0,
        int $barangayId = 0,
        int $limit = 20,
        int $offset = 0
    ): array {
        $sql = 'SELECT inc.id, inc.jail_unit_id,
                       inc.incident_date, inc.incident_time, inc.incident_type,
                       inc.province, inc.municipality_id, inc.barangay_id, inc.spot,
                       inc.severity, inc.status,
                       inc.description, inc.action_taken, inc.persons_involved, inc.witnesses,
                       inc.reported_by, inc.reviewed_by, inc.resolved_at,
                       inc.created_at, inc.updated_at,
                       j.name AS jail_unit_name,
                       m.name AS municipality_name,
                       b.name AS barangay_name,
                       ru.full_name AS reported_by_name,
                       vu.full_name AS reviewed_by_name
                FROM incidents inc
                LEFT JOIN jail_units    j  ON j.id  = inc.jail_unit_id
                LEFT JOIN municipalities m ON m.id  = inc.municipality_id
                LEFT JOIN barangays     b  ON b.id  = inc.barangay_id
                LEFT JOIN users        ru  ON ru.id = inc.reported_by
                LEFT JOIN users        vu  ON vu.id = inc.reviewed_by
                WHERE 1 = 1';

        $params = [];
        $types  = '';

        if ($q !== '') {
            $sql .= ' AND (inc.description LIKE ?
                        OR inc.spot LIKE ?
                        OR inc.persons_involved LIKE ?)';
            $like = '%' . $q . '%';
            for ($i = 0; $i < 3; $i++) { $params[] = $like; $types .= 's'; }
        }

        if ($jailUnitId > 0) {
            $sql .= ' AND inc.jail_unit_id = ?';
            $params[] = $jailUnitId;
            $types .= 'i';
        }

        if ($type !== '') {
            $sql .= ' AND inc.incident_type = ?';
            $params[] = $type;
            $types .= 's';
        }

        if ($severity !== '') {
            $sql .= ' AND inc.severity = ?';
            $params[] = $severity;
            $types .= 's';
        }

        if ($status !== '') {
            $sql .= ' AND inc.status = ?';
            $params[] = $status;
            $types .= 's';
        }

        if ($date !== '') {
            $sql .= ' AND inc.incident_date = ?';
            $params[] = $date;
            $types .= 's';
        }

        if ($municipalityId > 0) {
            $sql .= ' AND inc.municipality_id = ?';
            $params[] = $municipalityId;
            $types .= 'i';
        }

        if ($barangayId > 0) {
            $sql .= ' AND inc.barangay_id = ?';
            $params[] = $barangayId;
            $types .= 'i';
        }

        // Count
        $countSql = 'SELECT COUNT(*) AS c FROM (' . $sql . ') AS t';
        $stmt = $this->db->prepare($countSql);
        if ($params) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $total = (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0);
        $stmt->close();

        // Paginate
        $sql .= ' ORDER BY inc.incident_date DESC, inc.incident_time DESC, inc.id DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';

        $stmt = $this->db->prepare($sql);
        if ($params) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($rows as &$r) {
            $r['id']              = (int) $r['id'];
            $r['jail_unit_id']    = $r['jail_unit_id']    !== null ? (int) $r['jail_unit_id']    : null;
            $r['municipality_id'] = $r['municipality_id'] !== null ? (int) $r['municipality_id'] : null;
            $r['barangay_id']     = $r['barangay_id']     !== null ? (int) $r['barangay_id']     : null;
            $r['reported_by']     = (int) $r['reported_by'];
            $r['reviewed_by']     = $r['reviewed_by'] !== null ? (int) $r['reviewed_by'] : null;
        }

        return ['rows' => $rows, 'total' => $total];
    }

    /* ============================================================
       FIND
       ============================================================ */

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT inc.*,
                    j.name AS jail_unit_name,
                    m.name AS municipality_name,
                    b.name AS barangay_name,
                    ru.full_name AS reported_by_name,
                    vu.full_name AS reviewed_by_name
             FROM incidents inc
             LEFT JOIN jail_units    j  ON j.id  = inc.jail_unit_id
             LEFT JOIN municipalities m ON m.id  = inc.municipality_id
             LEFT JOIN barangays     b  ON b.id  = inc.barangay_id
             LEFT JOIN users        ru  ON ru.id = inc.reported_by
             LEFT JOIN users        vu  ON vu.id = inc.reviewed_by
             WHERE inc.id = ? LIMIT 1'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) return null;

        $row['id']              = (int) $row['id'];
        $row['jail_unit_id']    = $row['jail_unit_id']    !== null ? (int) $row['jail_unit_id']    : null;
        $row['municipality_id'] = $row['municipality_id'] !== null ? (int) $row['municipality_id'] : null;
        $row['barangay_id']     = $row['barangay_id']     !== null ? (int) $row['barangay_id']     : null;
        $row['reported_by']     = (int) $row['reported_by'];
        $row['reviewed_by']     = $row['reviewed_by'] !== null ? (int) $row['reviewed_by'] : null;
        return $row;
    }

    /* ============================================================
       CREATE
       ============================================================ */

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO incidents
             (jail_unit_id, incident_date, incident_time, incident_type,
              province, municipality_id, barangay_id, spot,
              severity, status,
              description, action_taken, persons_involved, witnesses,
              reported_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $stmt->bind_param(
            'issssiiissssssi',
            $data['jail_unit_id'],
            $data['incident_date'],
            $data['incident_time'],
            $data['incident_type'],
            $data['province'],
            $data['municipality_id'],
            $data['barangay_id'],
            $data['spot'],
            $data['severity'],
            $data['status'],
            $data['description'],
            $data['action_taken'],
            $data['persons_involved'],
            $data['witnesses'],
            $data['reported_by']
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
        $resolvedAt = ($data['status'] === 'Resolved' || $data['status'] === 'Closed')
            ? ($data['resolved_at'] ?: date('Y-m-d H:i:s'))
            : null;

        $reviewedBy = $data['reviewed_by'] ?? null;

        $stmt = $this->db->prepare(
            'UPDATE incidents SET
                jail_unit_id     = ?,
                incident_date    = ?,
                incident_time    = ?,
                incident_type    = ?,
                province         = ?,
                municipality_id  = ?,
                barangay_id      = ?,
                spot             = ?,
                severity         = ?,
                status           = ?,
                description      = ?,
                action_taken     = ?,
                persons_involved = ?,
                witnesses        = ?,
                reviewed_by      = ?,
                resolved_at      = ?
             WHERE id = ?'
        );

        $stmt->bind_param(
            'issssiiissssssisi',
            $data['jail_unit_id'],
            $data['incident_date'],
            $data['incident_time'],
            $data['incident_type'],
            $data['province'],
            $data['municipality_id'],
            $data['barangay_id'],
            $data['spot'],
            $data['severity'],
            $data['status'],
            $data['description'],
            $data['action_taken'],
            $data['persons_involved'],
            $data['witnesses'],
            $reviewedBy,
            $resolvedAt,
            $id
        );

        $stmt->execute();
        $stmt->close();
    }

    /* ============================================================
       DELETE
       ============================================================ */

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM incidents WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    }

    /* ============================================================
       STATS
       ============================================================ */

    public function openCount(int $jailUnitId = 0): int
    {
        $sql = "SELECT COUNT(*) AS c FROM incidents WHERE status IN ('Open', 'Under Review')";
        if ($jailUnitId > 0) {
            $sql .= ' AND jail_unit_id = ' . $jailUnitId;
        }
        $result = $this->db->query($sql);
        return (int) ($result->fetch_assoc()['c'] ?? 0);
    }
}