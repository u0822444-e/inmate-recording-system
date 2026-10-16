<?php
declare(strict_types=1);

namespace App\Models;

use mysqli;

class Report
{
    public function __construct(private mysqli $db) {}

    /* ============================================================
       POPULATION SNAPSHOT — Simple (classification × sex)
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
       ACTUAL POPULATION — by barangay within a municipality
       ============================================================ */

    public function actualPopulationByBarangay(int $municipalityId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    COALESCE(b.name, 'Unspecified') AS barangay,

                    SUM(CASE WHEN i.classification = 'Detainee' AND i.sex = 'Male'   THEN 1 ELSE 0 END) AS detainee_male,
                    SUM(CASE WHEN i.classification = 'Detainee' AND i.sex = 'Female' THEN 1 ELSE 0 END) AS detainee_female,

                    SUM(CASE WHEN i.classification = 'Sentenced' AND i.prisoner_class = 'Municipal'  AND i.sex = 'Male'   THEN 1 ELSE 0 END) AS municipal_male,
                    SUM(CASE WHEN i.classification = 'Sentenced' AND i.prisoner_class = 'Municipal'  AND i.sex = 'Female' THEN 1 ELSE 0 END) AS municipal_female,
                    SUM(CASE WHEN i.classification = 'Sentenced' AND i.prisoner_class = 'City'       AND i.sex = 'Male'   THEN 1 ELSE 0 END) AS city_male,
                    SUM(CASE WHEN i.classification = 'Sentenced' AND i.prisoner_class = 'City'       AND i.sex = 'Female' THEN 1 ELSE 0 END) AS city_female,
                    SUM(CASE WHEN i.classification = 'Sentenced' AND i.prisoner_class = 'Provincial' AND i.sex = 'Male'   THEN 1 ELSE 0 END) AS provincial_male,
                    SUM(CASE WHEN i.classification = 'Sentenced' AND i.prisoner_class = 'Provincial' AND i.sex = 'Female' THEN 1 ELSE 0 END) AS provincial_female,
                    SUM(CASE WHEN i.classification = 'Sentenced' AND i.prisoner_class = 'Insular'    AND i.sex = 'Male'   THEN 1 ELSE 0 END) AS insular_male,
                    SUM(CASE WHEN i.classification = 'Sentenced' AND i.prisoner_class = 'Insular'    AND i.sex = 'Female' THEN 1 ELSE 0 END) AS insular_female,

                    SUM(CASE WHEN i.classification = 'Detainee'  THEN 1 ELSE 0 END) AS detainee_total,
                    SUM(CASE WHEN i.classification = 'Sentenced' THEN 1 ELSE 0 END) AS sentenced_total,
                    COUNT(*) AS grand_total
                FROM inmates i
                LEFT JOIN barangays b ON b.id = i.barangay_id
                WHERE i.custody_status = 'In Custody'
                  AND i.municipality_id = ?
                GROUP BY b.id, b.name
                ORDER BY b.name ASC
            ");
            $stmt->bind_param('i', $municipalityId);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            foreach ($rows as &$r) {
                foreach ($r as $k => $v) {
                    if ($k !== 'barangay') $r[$k] = (int) $v;
                }
            }
            return $rows;
        } catch (\Throwable $e) {
            error_log('actualPopulationByBarangay: ' . $e->getMessage());
            return [];
        }
    }

    /* ============================================================
       ESCORTS — by barangay within a municipality
       ============================================================ */

    public function escortedByBarangay(int $municipalityId, string $monthYyyyMm): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    COALESCE(b.name, 'Unspecified') AS barangay,

                    SUM(CASE WHEN e.purpose='Court Hearing' AND i.sex='Male'   THEN 1 ELSE 0 END) AS court_male,
                    SUM(CASE WHEN e.purpose='Court Hearing' AND i.sex='Female' THEN 1 ELSE 0 END) AS court_female,
                    SUM(CASE WHEN e.purpose='Hospital'      AND i.sex='Male'   THEN 1 ELSE 0 END) AS hospital_male,
                    SUM(CASE WHEN e.purpose='Hospital'      AND i.sex='Female' THEN 1 ELSE 0 END) AS hospital_female,
                    SUM(CASE WHEN e.purpose='Drug Exam'     AND i.sex='Male'   THEN 1 ELSE 0 END) AS drug_exam_male,
                    SUM(CASE WHEN e.purpose='Drug Exam'     AND i.sex='Female' THEN 1 ELSE 0 END) AS drug_exam_female,
                    SUM(CASE WHEN e.purpose='Rehab'         AND i.sex='Male'   THEN 1 ELSE 0 END) AS rehab_male,
                    SUM(CASE WHEN e.purpose='Rehab'         AND i.sex='Female' THEN 1 ELSE 0 END) AS rehab_female,
                    SUM(CASE WHEN e.purpose='Inquest'       AND i.sex='Male'   THEN 1 ELSE 0 END) AS inquest_male,
                    SUM(CASE WHEN e.purpose='Inquest'       AND i.sex='Female' THEN 1 ELSE 0 END) AS inquest_female,
                    SUM(CASE WHEN e.purpose='Transfer'      AND i.sex='Male'   THEN 1 ELSE 0 END) AS transfer_male,
                    SUM(CASE WHEN e.purpose='Transfer'      AND i.sex='Female' THEN 1 ELSE 0 END) AS transfer_female,
                    SUM(CASE WHEN e.purpose='Visitation'    AND i.sex='Male'   THEN 1 ELSE 0 END) AS visitation_male,
                    SUM(CASE WHEN e.purpose='Visitation'    AND i.sex='Female' THEN 1 ELSE 0 END) AS visitation_female,
                    SUM(CASE WHEN e.purpose='Other'         AND i.sex='Male'   THEN 1 ELSE 0 END) AS other_male,
                    SUM(CASE WHEN e.purpose='Other'         AND i.sex='Female' THEN 1 ELSE 0 END) AS other_female,

                    COUNT(*) AS total
                FROM escorts e
                INNER JOIN inmates i ON i.id = e.inmate_id
                LEFT JOIN barangays b ON b.id = i.barangay_id
                WHERE i.municipality_id = ?
                  AND DATE_FORMAT(e.escort_date, '%Y-%m') = ?
                GROUP BY b.id, b.name
                ORDER BY b.name ASC
            ");
            $stmt->bind_param('is', $municipalityId, $monthYyyyMm);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            foreach ($rows as &$r) {
                foreach ($r as $k => $v) {
                    if ($k !== 'barangay') $r[$k] = (int) $v;
                }
            }
            return $rows;
        } catch (\Throwable $e) {
            error_log('escortedByBarangay: ' . $e->getMessage());
            return [];
        }
    }

    /* Helper: look up a municipality by name (for the modal title) */
    public function findMunicipalityByName(string $name): ?array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT id, name, province FROM municipalities WHERE name = ? LIMIT 1"
            );
            $stmt->bind_param('s', $name);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$row) return null;
            $row['id'] = (int) $row['id'];
            return $row;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /* ============================================================
       POPULATION SNAPSHOT — Detailed (classification × prisoner class × sex)
       ============================================================ */

    public function populationSnapshotDetailed(): array
    {
        try {
            $sql = "
                SELECT
                    COALESCE(classification, 'Unclassified') AS classification,

                    -- Detainee (no prisoner class applies)
                    SUM(CASE WHEN classification = 'Detainee' AND sex = 'Male'   THEN 1 ELSE 0 END) AS detainee_male,
                    SUM(CASE WHEN classification = 'Detainee' AND sex = 'Female' THEN 1 ELSE 0 END) AS detainee_female,

                    -- Sentenced by prisoner class
                    SUM(CASE WHEN classification = 'Sentenced' AND prisoner_class = 'Municipal'  AND sex = 'Male'   THEN 1 ELSE 0 END) AS municipal_male,
                    SUM(CASE WHEN classification = 'Sentenced' AND prisoner_class = 'Municipal'  AND sex = 'Female' THEN 1 ELSE 0 END) AS municipal_female,
                    SUM(CASE WHEN classification = 'Sentenced' AND prisoner_class = 'City'       AND sex = 'Male'   THEN 1 ELSE 0 END) AS city_male,
                    SUM(CASE WHEN classification = 'Sentenced' AND prisoner_class = 'City'       AND sex = 'Female' THEN 1 ELSE 0 END) AS city_female,
                    SUM(CASE WHEN classification = 'Sentenced' AND prisoner_class = 'Provincial' AND sex = 'Male'   THEN 1 ELSE 0 END) AS provincial_male,
                    SUM(CASE WHEN classification = 'Sentenced' AND prisoner_class = 'Provincial' AND sex = 'Female' THEN 1 ELSE 0 END) AS provincial_female,
                    SUM(CASE WHEN classification = 'Sentenced' AND prisoner_class = 'Insular'    AND sex = 'Male'   THEN 1 ELSE 0 END) AS insular_male,
                    SUM(CASE WHEN classification = 'Sentenced' AND prisoner_class = 'Insular'    AND sex = 'Female' THEN 1 ELSE 0 END) AS insular_female,

                    COUNT(*) AS grand_total
                FROM inmates
                WHERE custody_status = 'In Custody'
                GROUP BY COALESCE(classification, 'Unclassified')
                ORDER BY FIELD(COALESCE(classification, 'Unclassified'),
                               'Detainee', 'Sentenced', 'Awaiting Trial', 'Unclassified')
            ";
            $result = $this->db->query($sql);
            $rows = $result->fetch_all(MYSQLI_ASSOC);

            foreach ($rows as &$r) {
                foreach ($r as $k => $v) {
                    if ($k !== 'classification') $r[$k] = (int) $v;
                }
            }
            return $rows;
        } catch (\Throwable $e) {
            error_log('populationSnapshotDetailed: ' . $e->getMessage());
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
       FLOW — Committed vs. Released (per-day, legacy)
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
            $stmt->bind_param('ssssssss',
                $monthStart, $monthStart, $monthStart, $monthStart,
                $monthStart, $monthStart, $monthStart, $monthStart);
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
       FLOW — Committed vs. Released (by jail unit)
       ============================================================ */

    public function committedReleasedByUnit(string $monthYyyyMm): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    j.id AS jail_unit_id,
                    j.name AS jail_unit_name,
                    j.dormitory,

                    SUM(CASE WHEN i.sex = 'Male'   AND DATE_FORMAT(i.committed_at, '%Y-%m') = ? THEN 1 ELSE 0 END) AS committed_male,
                    SUM(CASE WHEN i.sex = 'Female' AND DATE_FORMAT(i.committed_at, '%Y-%m') = ? THEN 1 ELSE 0 END) AS committed_female,

                    SUM(CASE WHEN i.sex = 'Male'   AND i.custody_status = 'Released' AND DATE_FORMAT(i.updated_at, '%Y-%m') = ? THEN 1 ELSE 0 END) AS released_male,
                    SUM(CASE WHEN i.sex = 'Female' AND i.custody_status = 'Released' AND DATE_FORMAT(i.updated_at, '%Y-%m') = ? THEN 1 ELSE 0 END) AS released_female

                FROM jail_units j
                LEFT JOIN inmates i ON i.jail_unit_id = j.id
                WHERE j.is_active = 1
                GROUP BY j.id, j.name, j.dormitory
                ORDER BY j.name ASC
            ");
            $stmt->bind_param('ssss', $monthYyyyMm, $monthYyyyMm, $monthYyyyMm, $monthYyyyMm);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            foreach ($rows as &$r) {
                $r['jail_unit_id']    = (int) $r['jail_unit_id'];
                $r['committed_male']  = (int) $r['committed_male'];
                $r['committed_female']= (int) $r['committed_female'];
                $r['committed_total'] = $r['committed_male'] + $r['committed_female'];
                $r['released_male']   = (int) $r['released_male'];
                $r['released_female'] = (int) $r['released_female'];
                $r['released_total']  = $r['released_male'] + $r['released_female'];
                $r['grand_total']     = $r['committed_total'] + $r['released_total'];
            }
            return $rows;
        } catch (\Throwable $e) {
            error_log('committedReleasedByUnit: ' . $e->getMessage());
            return [];
        }
    }

    /* ============================================================
       DRUG CASES — Detailed
       Split by classification × drug law × sex × Adult/CICL.
       ============================================================ */

    public function drugCasesDetailed(): array
    {
        try {
            $sql = "
                SELECT
                    COALESCE(classification, 'Unclassified') AS classification,

                    -- Sentenced, R.A. 6425
                    SUM(CASE WHEN classification='Sentenced' AND drug_case_law='R.A. 6425'
                              AND sex='Male'   AND TIMESTAMPDIFF(YEAR, date_of_birth, admission_date) >= 18
                             THEN 1 ELSE 0 END) AS r6425_adult_male,
                    SUM(CASE WHEN classification='Sentenced' AND drug_case_law='R.A. 6425'
                              AND sex='Female' AND TIMESTAMPDIFF(YEAR, date_of_birth, admission_date) >= 18
                             THEN 1 ELSE 0 END) AS r6425_adult_female,
                    SUM(CASE WHEN classification='Sentenced' AND drug_case_law='R.A. 6425'
                              AND sex='Male'   AND TIMESTAMPDIFF(YEAR, date_of_birth, admission_date) < 18
                             THEN 1 ELSE 0 END) AS r6425_cicl_male,
                    SUM(CASE WHEN classification='Sentenced' AND drug_case_law='R.A. 6425'
                              AND sex='Female' AND TIMESTAMPDIFF(YEAR, date_of_birth, admission_date) < 18
                             THEN 1 ELSE 0 END) AS r6425_cicl_female,

                    -- Sentenced, R.A. 9165
                    SUM(CASE WHEN classification='Sentenced' AND drug_case_law='R.A. 9165'
                              AND sex='Male'   AND TIMESTAMPDIFF(YEAR, date_of_birth, admission_date) >= 18
                             THEN 1 ELSE 0 END) AS r9165_adult_male,
                    SUM(CASE WHEN classification='Sentenced' AND drug_case_law='R.A. 9165'
                              AND sex='Female' AND TIMESTAMPDIFF(YEAR, date_of_birth, admission_date) >= 18
                             THEN 1 ELSE 0 END) AS r9165_adult_female,
                    SUM(CASE WHEN classification='Sentenced' AND drug_case_law='R.A. 9165'
                              AND sex='Male'   AND TIMESTAMPDIFF(YEAR, date_of_birth, admission_date) < 18
                             THEN 1 ELSE 0 END) AS r9165_cicl_male,
                    SUM(CASE WHEN classification='Sentenced' AND drug_case_law='R.A. 9165'
                              AND sex='Female' AND TIMESTAMPDIFF(YEAR, date_of_birth, admission_date) < 18
                             THEN 1 ELSE 0 END) AS r9165_cicl_female,

                    -- Detainee / Awaiting Trial, R.A. 6425
                    SUM(CASE WHEN classification IN ('Detainee','Awaiting Trial') AND drug_case_law='R.A. 6425'
                              AND sex='Male'   AND TIMESTAMPDIFF(YEAR, date_of_birth, admission_date) >= 18
                             THEN 1 ELSE 0 END) AS at_r6425_adult_male,
                    SUM(CASE WHEN classification IN ('Detainee','Awaiting Trial') AND drug_case_law='R.A. 6425'
                              AND sex='Female' AND TIMESTAMPDIFF(YEAR, date_of_birth, admission_date) >= 18
                             THEN 1 ELSE 0 END) AS at_r6425_adult_female,
                    SUM(CASE WHEN classification IN ('Detainee','Awaiting Trial') AND drug_case_law='R.A. 6425'
                              AND sex='Male'   AND TIMESTAMPDIFF(YEAR, date_of_birth, admission_date) < 18
                             THEN 1 ELSE 0 END) AS at_r6425_cicl_male,
                    SUM(CASE WHEN classification IN ('Detainee','Awaiting Trial') AND drug_case_law='R.A. 6425'
                              AND sex='Female' AND TIMESTAMPDIFF(YEAR, date_of_birth, admission_date) < 18
                             THEN 1 ELSE 0 END) AS at_r6425_cicl_female,

                    -- Detainee / Awaiting Trial, R.A. 9165
                    SUM(CASE WHEN classification IN ('Detainee','Awaiting Trial') AND drug_case_law='R.A. 9165'
                              AND sex='Male'   AND TIMESTAMPDIFF(YEAR, date_of_birth, admission_date) >= 18
                             THEN 1 ELSE 0 END) AS at_r9165_adult_male,
                    SUM(CASE WHEN classification IN ('Detainee','Awaiting Trial') AND drug_case_law='R.A. 9165'
                              AND sex='Female' AND TIMESTAMPDIFF(YEAR, date_of_birth, admission_date) >= 18
                             THEN 1 ELSE 0 END) AS at_r9165_adult_female,
                    SUM(CASE WHEN classification IN ('Detainee','Awaiting Trial') AND drug_case_law='R.A. 9165'
                              AND sex='Male'   AND TIMESTAMPDIFF(YEAR, date_of_birth, admission_date) < 18
                             THEN 1 ELSE 0 END) AS at_r9165_cicl_male,
                    SUM(CASE WHEN classification IN ('Detainee','Awaiting Trial') AND drug_case_law='R.A. 9165'
                              AND sex='Female' AND TIMESTAMPDIFF(YEAR, date_of_birth, admission_date) < 18
                             THEN 1 ELSE 0 END) AS at_r9165_cicl_female,

                    COUNT(*) AS total

                FROM inmates
                WHERE custody_status = 'In Custody'
                  AND is_drug_case = 1
                GROUP BY COALESCE(classification, 'Unclassified')
                ORDER BY FIELD(COALESCE(classification, 'Unclassified'),
                               'Sentenced', 'Detainee', 'Awaiting Trial', 'Unclassified')
            ";
            $result = $this->db->query($sql);
            $rows = $result->fetch_all(MYSQLI_ASSOC);

            foreach ($rows as &$r) {
                foreach ($r as $k => $v) {
                    if ($k !== 'classification') $r[$k] = (int) $v;
                }
            }
            return $rows;
        } catch (\Throwable $e) {
            error_log('drugCasesDetailed: ' . $e->getMessage());
            return [];
        }
    }

    /* ============================================================
       DRUG CASES — Simple (classification × sex)
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

    /* ============================================================
       ACTUAL JAIL POPULATION DATA
       ============================================================ */

    public function actualPopulationByMunicipality(): array
    {
        try {
            $sql = "
                SELECT
                    COALESCE(m.name, 'Unspecified') AS municipality,

                    SUM(CASE WHEN i.classification = 'Detainee' AND i.sex = 'Male'   THEN 1 ELSE 0 END) AS detainee_male,
                    SUM(CASE WHEN i.classification = 'Detainee' AND i.sex = 'Female' THEN 1 ELSE 0 END) AS detainee_female,

                    SUM(CASE WHEN i.classification = 'Sentenced' AND i.prisoner_class = 'Municipal'  AND i.sex = 'Male'   THEN 1 ELSE 0 END) AS municipal_male,
                    SUM(CASE WHEN i.classification = 'Sentenced' AND i.prisoner_class = 'Municipal'  AND i.sex = 'Female' THEN 1 ELSE 0 END) AS municipal_female,
                    SUM(CASE WHEN i.classification = 'Sentenced' AND i.prisoner_class = 'City'       AND i.sex = 'Male'   THEN 1 ELSE 0 END) AS city_male,
                    SUM(CASE WHEN i.classification = 'Sentenced' AND i.prisoner_class = 'City'       AND i.sex = 'Female' THEN 1 ELSE 0 END) AS city_female,
                    SUM(CASE WHEN i.classification = 'Sentenced' AND i.prisoner_class = 'Provincial' AND i.sex = 'Male'   THEN 1 ELSE 0 END) AS provincial_male,
                    SUM(CASE WHEN i.classification = 'Sentenced' AND i.prisoner_class = 'Provincial' AND i.sex = 'Female' THEN 1 ELSE 0 END) AS provincial_female,
                    SUM(CASE WHEN i.classification = 'Sentenced' AND i.prisoner_class = 'Insular'    AND i.sex = 'Male'   THEN 1 ELSE 0 END) AS insular_male,
                    SUM(CASE WHEN i.classification = 'Sentenced' AND i.prisoner_class = 'Insular'    AND i.sex = 'Female' THEN 1 ELSE 0 END) AS insular_female,

                    SUM(CASE WHEN i.classification = 'Detainee'  THEN 1 ELSE 0 END) AS detainee_total,
                    SUM(CASE WHEN i.classification = 'Sentenced' THEN 1 ELSE 0 END) AS sentenced_total,
                    COUNT(*) AS grand_total
                FROM inmates i
                LEFT JOIN municipalities m ON m.id = i.municipality_id
                WHERE i.custody_status = 'In Custody'
                GROUP BY m.id, m.name
                ORDER BY m.name ASC
            ";
            $result = $this->db->query($sql);
            $rows = $result->fetch_all(MYSQLI_ASSOC);

            foreach ($rows as &$r) {
                foreach ($r as $k => $v) {
                    if ($k !== 'municipality') $r[$k] = (int) $v;
                }
            }
            return $rows;
        } catch (\Throwable $e) {
            error_log('actualPopulationByMunicipality: ' . $e->getMessage());
            return [];
        }
    }

    /* ============================================================
       ESCORTED PDL — by municipality × purpose × sex
       ============================================================ */

    public function escortedByMunicipality(string $monthYyyyMm): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    COALESCE(m.name, 'Unspecified') AS municipality,

                    SUM(CASE WHEN e.purpose='Court Hearing'    AND i.sex='Male'   THEN 1 ELSE 0 END) AS court_male,
                    SUM(CASE WHEN e.purpose='Court Hearing'    AND i.sex='Female' THEN 1 ELSE 0 END) AS court_female,
                    SUM(CASE WHEN e.purpose='Hospital'         AND i.sex='Male'   THEN 1 ELSE 0 END) AS hospital_male,
                    SUM(CASE WHEN e.purpose='Hospital'         AND i.sex='Female' THEN 1 ELSE 0 END) AS hospital_female,
                    SUM(CASE WHEN e.purpose='Drug Exam'        AND i.sex='Male'   THEN 1 ELSE 0 END) AS drug_exam_male,
                    SUM(CASE WHEN e.purpose='Drug Exam'        AND i.sex='Female' THEN 1 ELSE 0 END) AS drug_exam_female,
                    SUM(CASE WHEN e.purpose='Rehab'            AND i.sex='Male'   THEN 1 ELSE 0 END) AS rehab_male,
                    SUM(CASE WHEN e.purpose='Rehab'            AND i.sex='Female' THEN 1 ELSE 0 END) AS rehab_female,
                    SUM(CASE WHEN e.purpose='Inquest'          AND i.sex='Male'   THEN 1 ELSE 0 END) AS inquest_male,
                    SUM(CASE WHEN e.purpose='Inquest'          AND i.sex='Female' THEN 1 ELSE 0 END) AS inquest_female,
                    SUM(CASE WHEN e.purpose='Transfer'         AND i.sex='Male'   THEN 1 ELSE 0 END) AS transfer_male,
                    SUM(CASE WHEN e.purpose='Transfer'         AND i.sex='Female' THEN 1 ELSE 0 END) AS transfer_female,
                    SUM(CASE WHEN e.purpose='Visitation'       AND i.sex='Male'   THEN 1 ELSE 0 END) AS visitation_male,
                    SUM(CASE WHEN e.purpose='Visitation'       AND i.sex='Female' THEN 1 ELSE 0 END) AS visitation_female,
                    SUM(CASE WHEN e.purpose='Other'            AND i.sex='Male'   THEN 1 ELSE 0 END) AS other_male,
                    SUM(CASE WHEN e.purpose='Other'            AND i.sex='Female' THEN 1 ELSE 0 END) AS other_female,

                    COUNT(*) AS total
                FROM escorts e
                INNER JOIN inmates i ON i.id = e.inmate_id
                LEFT JOIN municipalities m ON m.id = i.municipality_id
                WHERE DATE_FORMAT(e.escort_date, '%Y-%m') = ?
                GROUP BY m.id, m.name
                ORDER BY m.name ASC
            ");
            $stmt->bind_param('s', $monthYyyyMm);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            foreach ($rows as &$r) {
                foreach ($r as $k => $v) {
                    if ($k !== 'municipality') $r[$k] = (int) $v;
                }
            }
            return $rows;
        } catch (\Throwable $e) {
            error_log('escortedByMunicipality: ' . $e->getMessage());
            return [];
        }
    }
}