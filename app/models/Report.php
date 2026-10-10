<?php
declare(strict_types=1);

namespace App\Models;

use mysqli;

class Report
{
    public function __construct(private mysqli $db) {}

    /* ============================================================
       POPULATION SNAPSHOT
       Classifies current in-custody PDLs by classification + sex.
       ============================================================ */

    public function populationSnapshot(): array
    {
        try {
            $sql = "
                SELECT
                    COALESCE(classification, 'Unclassified') AS classification,
                    SUM(CASE WHEN sex = 'Male'   THEN 1 ELSE 0 END) AS male,
                    SUM(CASE WHEN sex = 'Female' THEN 1 ELSE 0 END) AS female,
                    COUNT(*) AS total
                FROM inmates
                WHERE custody_status = 'In Custody'
                GROUP BY COALESCE(classification, 'Unclassified')
                ORDER BY FIELD(COALESCE(classification, 'Unclassified'),
                               'Detainee', 'Sentenced', 'Awaiting Trial', 'Unclassified')
            ";
            $result = $this->db->query($sql);
            $rows   = $result->fetch_all(MYSQLI_ASSOC);

            foreach ($rows as &$r) {
                $r['male']   = (int) $r['male'];
                $r['female'] = (int) $r['female'];
                $r['total']  = (int) $r['total'];
            }
            return $rows;
        } catch (\Throwable $e) {
            error_log('populationSnapshot: ' . $e->getMessage());
            return [];
        }
    }

    /* ============================================================
       CLASSIFICATION PERCENTAGES (for the footer)
       ============================================================ */

    public function classificationPercentages(): array
    {
        try {
            $result = $this->db->query("
                SELECT
                    COALESCE(classification, 'Unclassified') AS classification,
                    COUNT(*) AS c
                FROM inmates
                WHERE custody_status = 'In Custody'
                GROUP BY COALESCE(classification, 'Unclassified')
                ORDER BY FIELD(COALESCE(classification, 'Unclassified'),
                               'Detainee', 'Sentenced', 'Awaiting Trial', 'Unclassified')
            ");
            $rows  = $result->fetch_all(MYSQLI_ASSOC);
            $total = 0;
            foreach ($rows as $r) { $total += (int) $r['c']; }

            $out = [];
            foreach ($rows as $r) {
                $out[] = [
                    'classification' => $r['classification'],
                    'count'          => (int) $r['c'],
                    'percentage'     => $total > 0 ? round(((int) $r['c'] / $total) * 100, 2) : 0.0,
                ];
            }
            return ['total' => $total, 'items' => $out];
        } catch (\Throwable $e) {
            error_log('classificationPercentages: ' . $e->getMessage());
            return ['total' => 0, 'items' => []];
        }
    }

    /* ============================================================
       FLOW — Committed vs. Released
       For a given month, returns per-day rows for the two events.
       ============================================================ */

    public function flow(string $monthYyyyMm): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    d.day,
                    COALESCE(c.male, 0)     AS committed_male,
                    COALESCE(c.female, 0)   AS committed_female,
                    COALESCE(c.total, 0)    AS committed_total,
                    COALESCE(r.male, 0)     AS released_male,
                    COALESCE(r.female, 0)   AS released_female,
                    COALESCE(r.total, 0)    AS released_total
                FROM (
                    SELECT DATE_FORMAT(DATE_ADD(?, INTERVAL n DAY), '%Y-%m-%d') AS day
                    FROM (
                        SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3
                        UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7
                        UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10 UNION ALL SELECT 11
                        UNION ALL SELECT 12 UNION ALL SELECT 13 UNION ALL SELECT 14 UNION ALL SELECT 15
                        UNION ALL SELECT 16 UNION ALL SELECT 17 UNION ALL SELECT 18 UNION ALL SELECT 19
                        UNION ALL SELECT 20 UNION ALL SELECT 21 UNION ALL SELECT 22 UNION ALL SELECT 23
                        UNION ALL SELECT 24 UNION ALL SELECT 25 UNION ALL SELECT 26 UNION ALL SELECT 27
                        UNION ALL SELECT 28 UNION ALL SELECT 29 UNION ALL SELECT 30
                    ) days
                    WHERE DATE_ADD(?, INTERVAL n DAY) < DATE_ADD(?, INTERVAL 1 MONTH)
                ) d
                LEFT JOIN (
                    SELECT DATE(committed_at) AS day,
                           SUM(sex = 'Male')   AS male,
                           SUM(sex = 'Female') AS female,
                           COUNT(*)            AS total
                    FROM inmates
                    WHERE committed_at >= ? AND committed_at < DATE_ADD(?, INTERVAL 1 MONTH)
                    GROUP BY DATE(committed_at)
                ) c ON c.day = d.day
                LEFT JOIN (
                    SELECT DATE(updated_at) AS day,
                           SUM(sex = 'Male')   AS male,
                           SUM(sex = 'Female') AS female,
                           COUNT(*)            AS total
                    FROM inmates
                    WHERE custody_status = 'Released'
                      AND updated_at >= ? AND updated_at < DATE_ADD(?, INTERVAL 1 MONTH)
                    GROUP BY DATE(updated_at)
                ) r ON r.day = d.day
                HAVING committed_total > 0 OR released_total > 0
                ORDER BY d.day ASC
            ");
            $monthStart = $monthYyyyMm . '-01';
            $stmt->bind_param(
                'ssssssss',
                $monthStart,
                $monthStart,
                $monthStart,
                $monthStart,
                $monthStart,
                $monthStart,
                $monthStart,
                $monthStart
            );
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            foreach ($rows as &$r) {
                $r['committed_male']   = (int) $r['committed_male'];
                $r['committed_female'] = (int) $r['committed_female'];
                $r['committed_total']  = (int) $r['committed_total'];
                $r['released_male']    = (int) $r['released_male'];
                $r['released_female']  = (int) $r['released_female'];
                $r['released_total']   = (int) $r['released_total'];
            }
            return $rows;
        } catch (\Throwable $e) {
            error_log('flow: ' . $e->getMessage());
            return [];
        }
    }

    /* ============================================================
       DRUG CASES
       In-custody PDLs flagged as drug-related, by classification + sex.
       ============================================================ */

    public function drugCases(): array
    {
        try {
            $sql = "
                SELECT
                    COALESCE(classification, 'Unclassified') AS classification,
                    SUM(CASE WHEN sex = 'Male'   THEN 1 ELSE 0 END) AS male,
                    SUM(CASE WHEN sex = 'Female' THEN 1 ELSE 0 END) AS female,
                    COUNT(*) AS total
                FROM inmates
                WHERE custody_status = 'In Custody'
                  AND is_drug_case = 1
                GROUP BY COALESCE(classification, 'Unclassified')
                ORDER BY FIELD(COALESCE(classification, 'Unclassified'),
                               'Detainee', 'Sentenced', 'Awaiting Trial', 'Unclassified')
            ";
            $result = $this->db->query($sql);
            $rows   = $result->fetch_all(MYSQLI_ASSOC);

            foreach ($rows as &$r) {
                $r['male']   = (int) $r['male'];
                $r['female'] = (int) $r['female'];
                $r['total']  = (int) $r['total'];
            }
            return $rows;
        } catch (\Throwable $e) {
            error_log('drugCases: ' . $e->getMessage());
            return [];
        }
    }
}