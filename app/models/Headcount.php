<?php
declare(strict_types=1);

namespace App\Models;

use mysqli;

class Headcount
{
    public function __construct(private mysqli $db) {}

    /* ============================================================
       LIST
       ============================================================ */

    public function all(
        int $jailUnitId = 0,
        string $shift = '',
        string $status = '',
        string $date = '',
        int $limit = 20,
        int $offset = 0
    ): array {
        $sql = 'SELECT h.id, h.shift, h.jail_unit_id,
                       h.recorded_on, h.recorded_at,
                       h.expected_count, h.actual_count,
                       h.status, h.remarks,
                       h.recorded_by, h.verified_by, h.verified_at,
                       h.created_at,
                       j.name AS jail_unit_name,
                       ru.full_name AS recorded_by_name,
                       vu.full_name AS verified_by_name
                FROM headcounts h
                LEFT JOIN jail_units j ON j.id = h.jail_unit_id
                LEFT JOIN users      ru ON ru.id = h.recorded_by
                LEFT JOIN users      vu ON vu.id = h.verified_by
                WHERE 1 = 1';

        $params = [];
        $types  = '';

        if ($jailUnitId > 0) {
            $sql .= ' AND h.jail_unit_id = ?';
            $params[] = $jailUnitId;
            $types .= 'i';
        }

        if ($shift !== '') {
            $sql .= ' AND h.shift = ?';
            $params[] = $shift;
            $types .= 's';
        }

        if ($status !== '') {
            $sql .= ' AND h.status = ?';
            $params[] = $status;
            $types .= 's';
        }

        if ($date !== '') {
            $sql .= ' AND h.recorded_on = ?';
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
        $sql .= ' ORDER BY h.recorded_on DESC, FIELD(h.shift, "Morning", "Afternoon", "Night"), h.id DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';

        $stmt = $this->db->prepare($sql);
        if ($params) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($rows as &$r) {
            $r['id']             = (int) $r['id'];
            $r['jail_unit_id']   = $r['jail_unit_id'] !== null ? (int) $r['jail_unit_id'] : null;
            $r['expected_count'] = (int) $r['expected_count'];
            $r['actual_count']   = (int) $r['actual_count'];
            $r['recorded_by']    = $r['recorded_by'] !== null ? (int) $r['recorded_by'] : null;
            $r['verified_by']    = $r['verified_by'] !== null ? (int) $r['verified_by'] : null;
            $r['has_discrepancy'] = $r['expected_count'] !== $r['actual_count'];
        }

        return ['rows' => $rows, 'total' => $total];
    }

    /* ============================================================
       FIND
       ============================================================ */

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT h.*,
                    j.name AS jail_unit_name,
                    ru.full_name AS recorded_by_name,
                    vu.full_name AS verified_by_name
             FROM headcounts h
             LEFT JOIN jail_units j ON j.id = h.jail_unit_id
             LEFT JOIN users      ru ON ru.id = h.recorded_by
             LEFT JOIN users      vu ON vu.id = h.verified_by
             WHERE h.id = ? LIMIT 1'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) return null;

        $row['id']             = (int) $row['id'];
        $row['jail_unit_id']   = $row['jail_unit_id'] !== null ? (int) $row['jail_unit_id'] : null;
        $row['expected_count'] = (int) $row['expected_count'];
        $row['actual_count']   = (int) $row['actual_count'];
        $row['recorded_by']    = $row['recorded_by'] !== null ? (int) $row['recorded_by'] : null;
        $row['verified_by']    = $row['verified_by'] !== null ? (int) $row['verified_by'] : null;
        return $row;
    }

    /* ============================================================
       SUBMIT (create)
       ============================================================ */

    public function submit(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO headcounts
             (shift, jail_unit_id, recorded_on, expected_count, actual_count,
              status, remarks, recorded_by)
             VALUES (?, ?, ?, ?, ?, "Submitted", ?, ?)'
        );

        $stmt->bind_param(
            'sisii si',
            $data['shift'],
            $data['jail_unit_id'],
            $data['recorded_on'],
            $data['expected_count'],
            $data['actual_count'],
            $data['remarks'],
            $data['recorded_by']
        );

        $stmt->execute();
        $id = (int) $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    /* ============================================================
       VERIFY (admin)
       ============================================================ */

    public function verify(int $id, int $verifiedBy, bool $discrepancy = false): void
    {
        $status = $discrepancy ? 'Discrepancy' : 'Verified';

        $stmt = $this->db->prepare(
            'UPDATE headcounts SET
                status      = ?,
                verified_by = ?,
                verified_at = CURRENT_TIMESTAMP
             WHERE id = ?'
        );
        $stmt->bind_param('sii', $status, $verifiedBy, $id);
        $stmt->execute();
        $stmt->close();
    }

    /* ============================================================
       DELETE (admin)
       ============================================================ */

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM headcounts WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    }

    /* ============================================================
       DUPLICATE CHECK
       ============================================================ */

    public function existsForShift(string $date, string $shift, int $jailUnitId, int $excludeId = 0): bool
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM headcounts
             WHERE recorded_on = ? AND shift = ? AND jail_unit_id = ? AND id <> ?
             LIMIT 1'
        );
        $stmt->bind_param('ssii', $date, $shift, $jailUnitId, $excludeId);
        $stmt->execute();
        $exists = (bool) $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $exists;
    }

    /* ============================================================
       STATS
       ============================================================ */

    /**
     * Expected count = number of In Custody PDLs in the given unit
     * (or all units if 0).
     */
    public function computeExpected(int $jailUnitId = 0): int
    {
        $sql = "SELECT COUNT(*) AS c FROM inmates WHERE custody_status = 'In Custody'";
        if ($jailUnitId > 0) {
            $sql .= ' AND jail_unit_id = ' . $jailUnitId;
        }
        $result = $this->db->query($sql);
        return (int) ($result->fetch_assoc()['c'] ?? 0);
    }

    /**
     * Returns the three shifts' status for a given date & unit.
     * e.g. ['Morning' => 'submitted', 'Afternoon' => 'pending', 'Night' => 'upcoming']
     */
    public function shiftStatusForToday(int $jailUnitId, string $date): array
    {
        $sql = 'SELECT shift, status FROM headcounts
                WHERE recorded_on = ?';
        if ($jailUnitId > 0) {
            $sql .= ' AND jail_unit_id = ' . $jailUnitId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $date);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $byShift = [];
        foreach ($rows as $r) {
            $byShift[$r['shift']] = $r['status'];
        }

        // Determine which shifts are pending based on current hour
        $hour = (int) date('G');
        $currentShift = 'Night';
        if ($hour >= 6 && $hour < 14)  $currentShift = 'Morning';
        elseif ($hour >= 14 && $hour < 22) $currentShift = 'Afternoon';

        $out = [];
        foreach (['Morning', 'Afternoon', 'Night'] as $s) {
            if (isset($byShift[$s])) {
                $out[$s] = strtolower($byShift[$s]);
            } else {
                // Pending if we're currently in this shift or past it
                $order = ['Morning' => 1, 'Afternoon' => 2, 'Night' => 3];
                $out[$s] = ($order[$s] <= $order[$currentShift]) ? 'pending' : 'upcoming';
            }
        }
        return $out;
    }
}