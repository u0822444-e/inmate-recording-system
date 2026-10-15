<?php
declare(strict_types=1);

namespace App\Models;

use mysqli;

class Dashboard
{
    public function __construct(private mysqli $db) {}

    /* ============================================================
       ADMIN KPIs
       ============================================================ */

    public function totalPdl(): int
    {
        try {
            $result = $this->db->query(
                "SELECT COUNT(*) AS c FROM inmates WHERE custody_status = 'In Custody'"
            );
            return (int) ($result->fetch_assoc()['c'] ?? 0);
        } catch (\Throwable $e) {
            error_log('totalPdl: ' . $e->getMessage());
            return 0;
        }
    }

    public function visitorsToday(): int
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) AS c FROM visitors WHERE DATE(time_in) = CURDATE()"
            );
            $stmt->execute();
            $count = (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0);
            $stmt->close();
            return $count;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function openIncidents(): int
    {
        try {
            $result = $this->db->query(
                "SELECT COUNT(*) AS c FROM incidents
                 WHERE status IN ('Open', 'Pending', 'Under Review')"
            );
            return (int) ($result->fetch_assoc()['c'] ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function activeUsers(): int
    {
        try {
            $result = $this->db->query(
                "SELECT COUNT(*) AS c FROM users WHERE is_active = 1 AND archived_at IS NULL"
            );
            return (int) ($result->fetch_assoc()['c'] ?? 0);
        } catch (\Throwable $e) {
            error_log('activeUsers: ' . $e->getMessage());
            return 0;
        }
    }

    /* ============================================================
       STAFF KPIs
       ============================================================ */

    public function myEntriesToday(int $userId): int
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) AS c FROM inmates
                 WHERE created_by = ? AND DATE(created_at) = CURDATE()"
            );
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $count = (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0);
            $stmt->close();
            return $count;
        } catch (\Throwable $e) {
            error_log('myEntriesToday: ' . $e->getMessage());
            return 0;
        }
    }

    public function myPendingHeadcounts(int $userId): int
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) AS c FROM headcounts
                 WHERE recorded_by = ? AND recorded_on = CURDATE()"
            );
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $count = (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0);
            $stmt->close();
            return $count;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    // FIX: incidents table uses reported_by, not created_by
    public function myIncidentsThisWeek(int $userId): int
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) AS c FROM incidents
                 WHERE reported_by = ?
                   AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"
            );
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $count = (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0);
            $stmt->close();
            return $count;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /* ============================================================
       ACTIVITY FEEDS
       ============================================================ */

    public function recentActivity(int $limit = 5): array
    {
        $items = [];

        try {
            $stmt = $this->db->prepare(
                "SELECT 'inmate' AS kind, id,
                        CONCAT('Inmate record ',
                            CASE WHEN created_at = updated_at THEN 'created' ELSE 'updated' END
                        ) AS title,
                        TRIM(CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,''))) AS subtitle,
                        COALESCE(updated_at, created_at) AS happened_at
                 FROM inmates
                 ORDER BY COALESCE(updated_at, created_at) DESC
                 LIMIT ?"
            );
            $stmt->bind_param('i', $limit);
            $stmt->execute();
            $items = array_merge($items, $stmt->get_result()->fetch_all(MYSQLI_ASSOC));
            $stmt->close();
        } catch (\Throwable $e) {}

        try {
            $stmt = $this->db->prepare(
                "SELECT 'user' AS kind, id,
                        'User account created' AS title,
                        COALESCE(full_name, username) AS subtitle,
                        created_at AS happened_at
                 FROM users ORDER BY created_at DESC LIMIT ?"
            );
            $stmt->bind_param('i', $limit);
            $stmt->execute();
            $items = array_merge($items, $stmt->get_result()->fetch_all(MYSQLI_ASSOC));
            $stmt->close();
        } catch (\Throwable $e) {}

        // FIX: visitors table uses full_name, not name
        try {
            $stmt = $this->db->prepare(
                "SELECT 'visitor' AS kind, id,
                        'Visitor logged' AS title,
                        COALESCE(full_name,'') AS subtitle,
                        created_at AS happened_at
                 FROM visitors ORDER BY created_at DESC LIMIT ?"
            );
            $stmt->bind_param('i', $limit);
            $stmt->execute();
            $items = array_merge($items, $stmt->get_result()->fetch_all(MYSQLI_ASSOC));
            $stmt->close();
        } catch (\Throwable $e) {}

        try {
            $stmt = $this->db->prepare(
                "SELECT 'incident' AS kind, id,
                        CONCAT('Incident: ', incident_type) AS title,
                        COALESCE(spot,'') AS subtitle,
                        created_at AS happened_at
                 FROM incidents ORDER BY created_at DESC LIMIT ?"
            );
            $stmt->bind_param('i', $limit);
            $stmt->execute();
            $items = array_merge($items, $stmt->get_result()->fetch_all(MYSQLI_ASSOC));
            $stmt->close();
        } catch (\Throwable $e) {}

        usort($items, fn($a, $b) => strcmp((string) $b['happened_at'], (string) $a['happened_at']));
        $items = array_slice($items, 0, $limit);

        foreach ($items as &$r) {
            $r['id'] = (int) $r['id'];
            $r['happened_at'] = (string) $r['happened_at'];
        }
        return $items;
    }

    public function myRecentActivity(int $userId, int $limit = 5): array
    {
        $items = [];

        try {
            $stmt = $this->db->prepare(
                "SELECT 'inmate' AS kind, id,
                        CONCAT('Inmate record ',
                            CASE WHEN created_at = updated_at THEN 'created' ELSE 'updated' END
                        ) AS title,
                        TRIM(CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,''))) AS subtitle,
                        COALESCE(updated_at, created_at) AS happened_at
                 FROM inmates WHERE created_by = ?
                 ORDER BY COALESCE(updated_at, created_at) DESC LIMIT ?"
            );
            $stmt->bind_param('ii', $userId, $limit);
            $stmt->execute();
            $items = array_merge($items, $stmt->get_result()->fetch_all(MYSQLI_ASSOC));
            $stmt->close();
        } catch (\Throwable $e) {}

        try {
            $stmt = $this->db->prepare(
                "SELECT 'headcount' AS kind, id,
                        'Headcount recorded' AS title,
                        CONCAT(actual_count, ' PDL on ', recorded_on) AS subtitle,
                        recorded_at AS happened_at
                 FROM headcounts WHERE recorded_by = ?
                 ORDER BY recorded_at DESC LIMIT ?"
            );
            $stmt->bind_param('ii', $userId, $limit);
            $stmt->execute();
            $items = array_merge($items, $stmt->get_result()->fetch_all(MYSQLI_ASSOC));
            $stmt->close();
        } catch (\Throwable $e) {}

        usort($items, fn($a, $b) => strcmp((string) $b['happened_at'], (string) $a['happened_at']));
        $items = array_slice($items, 0, $limit);

        foreach ($items as &$r) {
            $r['id'] = (int) $r['id'];
            $r['happened_at'] = (string) $r['happened_at'];
        }
        return $items;
    }

    public function upcomingEvents(int $limit = 3): array
    {
        $demo = [
            ['date' => '2026-10-14', 'time' => '09:00', 'title' => 'Court Hearing',  'meta' => 'RTC Ipil · 3 PDL'],
            ['date' => '2026-10-15', 'time' => '13:00', 'title' => 'Visitation Day',  'meta' => 'Block B · 13:00–17:00'],
            ['date' => '2026-10-18', 'time' => '08:00', 'title' => 'Headcount Audit', 'meta' => 'Region IX · 08:00'],
        ];
        return array_slice($demo, 0, $limit);
    }

    /* ============================================================
       ANALYTICS — NEW
       ============================================================ */

    public function populationTrend(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT recorded_on AS d, MAX(actual_count) AS c
                 FROM headcounts
                 WHERE recorded_on >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                 GROUP BY recorded_on
                 ORDER BY d ASC"
            );
            $stmt->bind_param('i', $days);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return array_map(fn($r) => [
                'date'  => (string) $r['d'],
                'count' => (int) $r['c'],
            ], $rows);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Admission counts per day for the last N days. */
    public function admissionsTrend(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT DATE(admission_date) AS d, COUNT(*) AS c
                 FROM inmates
                 WHERE admission_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                 GROUP BY DATE(admission_date)
                 ORDER BY d ASC"
            );
            $stmt->bind_param('i', $days);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return array_map(fn($r) => [
                'date'  => (string) $r['d'],
                'count' => (int) $r['c'],
            ], $rows);
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function classificationBreakdown(): array
    {
        try {
            $result = $this->db->query(
                "SELECT COALESCE(classification, 'Unclassified') AS label,
                        COUNT(*) AS count
                 FROM inmates
                 WHERE custody_status = 'In Custody'
                 GROUP BY COALESCE(classification, 'Unclassified')
                 ORDER BY count DESC"
            );
            return array_map(fn($r) => [
                'label' => (string) $r['label'],
                'count' => (int) $r['count'],
            ], $result->fetch_all(MYSQLI_ASSOC));
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function custodyBreakdown(): array
    {
        try {
            $result = $this->db->query(
                "SELECT custody_status AS label, COUNT(*) AS count
                 FROM inmates GROUP BY custody_status ORDER BY count DESC"
            );
            return array_map(fn($r) => [
                'label' => (string) $r['label'],
                'count' => (int) $r['count'],
            ], $result->fetch_all(MYSQLI_ASSOC));
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function topOffenses(int $limit = 8): array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT o.name AS label, COUNT(i.id) AS count
                 FROM inmates i
                 INNER JOIN offenses o ON o.id = i.offense_id
                 WHERE i.custody_status = 'In Custody'
                 GROUP BY o.id, o.name
                 ORDER BY count DESC
                 LIMIT ?"
            );
            $stmt->bind_param('i', $limit);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return array_map(fn($r) => [
                'label' => (string) $r['label'],
                'count' => (int) $r['count'],
            ], $rows);
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function visitorsPerDay(int $days = 14): array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT DATE(time_in) AS d, COUNT(*) AS c
                 FROM visitors
                 WHERE time_in >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                 GROUP BY DATE(time_in)
                 ORDER BY d ASC"
            );
            $stmt->bind_param('i', $days);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return array_map(fn($r) => [
                'date'  => (string) $r['d'],
                'count' => (int) $r['c'],
            ], $rows);
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function incidentsByType(): array
    {
        try {
            $result = $this->db->query(
                "SELECT incident_type AS label, COUNT(*) AS count
                 FROM incidents GROUP BY incident_type ORDER BY count DESC"
            );
            return array_map(fn($r) => [
                'label' => (string) $r['label'],
                'count' => (int) $r['count'],
            ], $result->fetch_all(MYSQLI_ASSOC));
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function incidentsBySeverity(): array
    {
        try {
            $result = $this->db->query(
                "SELECT severity AS label,
                        SUM(CASE WHEN status IN ('Open','Under Review') THEN 1 ELSE 0 END) AS open,
                        SUM(CASE WHEN status IN ('Resolved','Closed')     THEN 1 ELSE 0 END) AS resolved
                 FROM incidents
                 GROUP BY severity
                 ORDER BY FIELD(severity, 'Low', 'Medium', 'High', 'Critical')"
            );
            return array_map(fn($r) => [
                'label'    => (string) $r['label'],
                'open'     => (int) $r['open'],
                'resolved' => (int) $r['resolved'],
            ], $result->fetch_all(MYSQLI_ASSOC));
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function ageDistribution(): array
    {
        try {
            $result = $this->db->query(
                "SELECT
                    CASE
                        WHEN age < 18 THEN 'Under 18'
                        WHEN age < 30 THEN '18–29'
                        WHEN age < 40 THEN '30–39'
                        WHEN age < 50 THEN '40–49'
                        WHEN age < 60 THEN '50–59'
                        ELSE '60+'
                    END AS label,
                    COUNT(*) AS count
                 FROM (
                     SELECT TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) AS age
                     FROM inmates
                     WHERE custody_status = 'In Custody' AND date_of_birth IS NOT NULL
                 ) AS a
                 GROUP BY label
                 ORDER BY FIELD(label, 'Under 18','18–29','30–39','40–49','50–59','60+')"
            );
            return array_map(fn($r) => [
                'label' => (string) $r['label'],
                'count' => (int) $r['count'],
            ], $result->fetch_all(MYSQLI_ASSOC));
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function inmatesByUnit(): array
    {
        try {
            $result = $this->db->query(
                "SELECT j.name AS label,
                        SUM(CASE WHEN i.sex = 'Male'   THEN 1 ELSE 0 END) AS male,
                        SUM(CASE WHEN i.sex = 'Female' THEN 1 ELSE 0 END) AS female
                 FROM jail_units j
                 LEFT JOIN inmates i ON i.jail_unit_id = j.id AND i.custody_status = 'In Custody'
                 WHERE j.is_active = 1
                 GROUP BY j.id, j.name
                 ORDER BY j.name"
            );
            return array_map(fn($r) => [
                'label'  => (string) $r['label'],
                'male'   => (int) $r['male'],
                'female' => (int) $r['female'],
            ], $result->fetch_all(MYSQLI_ASSOC));
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function releasesThisMonth(): array
    {
        try {
            $result = $this->db->query(
                "SELECT DATE(updated_at) AS d, COUNT(*) AS c
                 FROM inmates
                 WHERE custody_status = 'Released'
                   AND MONTH(updated_at) = MONTH(CURDATE())
                   AND YEAR(updated_at)  = YEAR(CURDATE())
                 GROUP BY DATE(updated_at)
                 ORDER BY d ASC"
            );
            return array_map(fn($r) => [
                'date'  => (string) $r['d'],
                'count' => (int) $r['count'],
            ], $result->fetch_all(MYSQLI_ASSOC));
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function drugCaseBreakdown(): array
    {
        try {
            $result = $this->db->query(
                "SELECT COALESCE(drug_case_law, 'Not Specified') AS label,
                        COUNT(*) AS count
                 FROM inmates
                 WHERE is_drug_case = 1 AND custody_status = 'In Custody'
                 GROUP BY COALESCE(drug_case_law, 'Not Specified')
                 ORDER BY count DESC"
            );
            return array_map(fn($r) => [
                'label' => (string) $r['label'],
                'count' => (int) $r['count'],
            ], $result->fetch_all(MYSQLI_ASSOC));
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function headcountComplianceToday(): array
    {
        try {
            $result = $this->db->query(
                "SELECT j.name AS unit,
                        SUM(CASE WHEN h.id IS NOT NULL THEN 1 ELSE 0 END) AS submitted,
                        3 AS required
                 FROM jail_units j
                 LEFT JOIN headcounts h
                     ON h.jail_unit_id = j.id AND h.recorded_on = CURDATE()
                 WHERE j.is_active = 1
                 GROUP BY j.id, j.name
                 ORDER BY j.name"
            );
            return array_map(fn($r) => [
                'unit'      => (string) $r['unit'],
                'submitted' => (int) $r['submitted'],
                'required'  => (int) $r['required'],
            ], $result->fetch_all(MYSQLI_ASSOC));
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function admissionsByMonth(int $months = 6): array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT DATE_FORMAT(admission_date, '%Y-%m') AS ym, COUNT(*) AS c
                 FROM inmates
                 WHERE admission_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
                 GROUP BY ym
                 ORDER BY ym ASC"
            );
            $stmt->bind_param('i', $months);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return array_map(fn($r) => [
                'month' => (string) $r['ym'],
                'count' => (int) $r['c'],
            ], $rows);
        } catch (\Throwable $e) {
            return [];
        }
    }
}