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
                "SELECT COUNT(*) AS c FROM inmates
                 WHERE status IN ('Detained', 'Convicted')"
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
            error_log('visitorsToday: ' . $e->getMessage());
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
            error_log('openIncidents: ' . $e->getMessage());
            return 0;
        }
    }

    public function activeUsers(): int
    {
        try {
            $result = $this->db->query(
                "SELECT COUNT(*) AS c FROM users
                 WHERE is_active = 1 AND archived_at IS NULL"
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
            $stmt = $this->db->prepare("
                SELECT COUNT(*) AS c FROM inmates
                WHERE created_by = ? AND DATE(created_at) = CURDATE()
            ");
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
            $stmt = $this->db->prepare("
                SELECT COUNT(*) AS c FROM headcounts
                WHERE officer_id = ? AND DATE(recorded_at) = CURDATE()
            ");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $count = (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0);
            $stmt->close();
            return $count;
        } catch (\Throwable $e) {
            error_log('myPendingHeadcounts: ' . $e->getMessage());
            return 0;
        }
    }

    public function myIncidentsThisWeek(int $userId): int
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) AS c FROM incidents
                WHERE created_by = ?
                  AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            ");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $count = (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0);
            $stmt->close();
            return $count;
        } catch (\Throwable $e) {
            error_log('myIncidentsThisWeek: ' . $e->getMessage());
            return 0;
        }
    }

    /* ============================================================
       ACTIVITY FEEDS
       ============================================================ */

    public function recentActivity(int $limit = 5): array
    {
        try {
            $sql = "
                (SELECT 'inmate' AS kind, id,
                        CONCAT('Inmate record ',
                            CASE WHEN created_at = updated_at THEN 'created' ELSE 'updated' END) AS title,
                        CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) AS subtitle,
                        updated_at AS happened_at
                 FROM inmates ORDER BY updated_at DESC LIMIT ?)
                UNION ALL
                (SELECT 'visitor' AS kind, id, 'Visitor logged' AS title,
                        COALESCE(name,'') AS subtitle, created_at AS happened_at
                 FROM visitors ORDER BY created_at DESC LIMIT ?)
                UNION ALL
                (SELECT 'incident' AS kind, id, 'Incident reported' AS title,
                        COALESCE(type,'') AS subtitle, created_at AS happened_at
                 FROM incidents ORDER BY created_at DESC LIMIT ?)
                UNION ALL
                (SELECT 'user' AS kind, id, 'User account created' AS title,
                        COALESCE(full_name, username) AS subtitle, created_at AS happened_at
                 FROM users ORDER BY created_at DESC LIMIT ?)
                ORDER BY happened_at DESC LIMIT ?
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('iiiii', $limit, $limit, $limit, $limit, $limit);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            foreach ($rows as &$r) {
                $r['id']          = (int) $r['id'];
                $r['happened_at'] = (string) $r['happened_at'];
            }
            return $rows;
        } catch (\Throwable $e) {
            error_log('recentActivity: ' . $e->getMessage());
            return [];
        }
    }

    public function myRecentActivity(int $userId, int $limit = 5): array
    {
        try {
            $sql = "
                (SELECT 'inmate' AS kind, id,
                        CONCAT('Inmate record ',
                            CASE WHEN created_at = updated_at THEN 'created' ELSE 'updated' END) AS title,
                        CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) AS subtitle,
                        updated_at AS happened_at
                 FROM inmates
                 WHERE created_by = ?
                 ORDER BY updated_at DESC LIMIT ?)
                UNION ALL
                (SELECT 'visitor' AS kind, id, 'Visitor logged' AS title,
                        COALESCE(name,'') AS subtitle, created_at AS happened_at
                 FROM visitors
                 WHERE created_by = ?
                 ORDER BY created_at DESC LIMIT ?)
                UNION ALL
                (SELECT 'headcount' AS kind, id, 'Headcount submitted' AS title,
                        CONCAT('Shift ', COALESCE(shift,''), ' · ', COALESCE(count,0), ' PDL') AS subtitle,
                        recorded_at AS happened_at
                 FROM headcounts
                 WHERE officer_id = ?
                 ORDER BY recorded_at DESC LIMIT ?)
                ORDER BY happened_at DESC
                LIMIT ?
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param(
                'iiiiiii',
                $userId, $limit,
                $userId, $limit,
                $userId, $limit,
                $limit
            );
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            foreach ($rows as &$r) {
                $r['id']          = (int) $r['id'];
                $r['happened_at'] = (string) $r['happened_at'];
            }
            return $rows;
        } catch (\Throwable $e) {
            error_log('myRecentActivity: ' . $e->getMessage());
            return [];
        }
    }

    /* ============================================================
       UPCOMING
       ============================================================ */

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
       TREND
       ============================================================ */

    public function populationTrend(int $days = 7): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT DATE(recorded_at) AS d, MAX(count) AS c
                FROM headcounts
                WHERE recorded_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY DATE(recorded_at)
                ORDER BY d ASC
            ");
            $stmt->bind_param('i', $days);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            return array_map(function ($r) {
                return [
                    'date'  => (string) $r['d'],
                    'count' => (int) $r['c'],
                ];
            }, $rows);
        } catch (\Throwable $e) {
            error_log('populationTrend: ' . $e->getMessage());
            return [];
        }
    }
}