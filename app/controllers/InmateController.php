<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Response;
use App\Services\Auth;
use mysqli;

class InmateController
{
    public function __construct(private mysqli $db) {}

    public function handle(string $action): void
    {
        if (!Auth::isLoggedIn()) {
            Response::json(false, 'Access denied.');
        }

        switch ($action) {
            case 'create':  $this->create();  break;
            case 'list':    $this->list();    break;
            case 'get':     $this->get();     break;
            case 'update':  $this->update();  break;
            case 'export':  $this->exportCsv(); break;
            case 'release': $this->release(); break;
            default:        Response::json(false, 'Unsupported request.');
        }
    }

    /* ============================================================
       CREATE
       ============================================================ */

    private function create(): void
    {
        $p = Response::readJsonInput();

        $inmateNumber   = trim((string) ($p['inmate_number'] ?? ''));
        $jailUnitId     = (int) ($p['jail_unit_id'] ?? 0) ?: null;
        $caseReference  = trim((string) ($p['case_reference'] ?? ''));
        $firstName      = trim((string) ($p['first_name'] ?? ''));
        $middleName     = trim((string) ($p['middle_name'] ?? ''));
        $lastName       = trim((string) ($p['last_name'] ?? ''));
        $suffix         = trim((string) ($p['suffix'] ?? ''));
        $dob            = trim((string) ($p['date_of_birth'] ?? '')) ?: null;
        $sex            = trim((string) ($p['sex'] ?? 'Unspecified')) ?: 'Unspecified';
        $civilStatus    = trim((string) ($p['civil_status'] ?? '')) ?: null;
        $municipalityId = (int) ($p['municipality_id'] ?? 0) ?: null;
        $barangayId     = (int) ($p['barangay_id'] ?? 0) ?: null;
        $offenseId      = (int) ($p['offense_id'] ?? 0) ?: null;
        $isDrugCase     = !empty($p['is_drug_case']) ? 1 : 0;

        $sentence       = trim((string) ($p['sentence'] ?? '')) ?: null;
        $sentenceMin    = isset($p['sentence_years_min']) && $p['sentence_years_min'] !== ''
                            ? (float) $p['sentence_years_min'] : null;
        $sentenceMax    = isset($p['sentence_years_max']) && $p['sentence_years_max'] !== ''
                            ? (float) $p['sentence_years_max'] : null;
        $sentenceManual = !empty($p['sentence_is_manual']) ? 1 : 0;

        $admissionDate  = trim((string) ($p['admission_date'] ?? '')) ?: date('Y-m-d');
        $committedAt    = trim((string) ($p['committed_at'] ?? '')) ?: null;
        $custodyStatus  = trim((string) ($p['custody_status'] ?? 'In Custody'));
        $classification = trim((string) ($p['classification'] ?? '')) ?: null;
        $notes          = trim((string) ($p['notes'] ?? '')) ?: null;

        if ($firstName === '' || $lastName === '') {
            Response::json(false, 'First name and last name are required.');
        }
        if ($inmateNumber === '') {
            Response::json(false, 'Inmate number is missing.');
        }
        if (!in_array($custodyStatus, ['In Custody', 'Released', 'Transferred'], true)) {
            $custodyStatus = 'In Custody';
        }
        if (!in_array($sex, ['Male', 'Female', 'Other', 'Unspecified'], true)) {
            $sex = 'Unspecified';
        }
        if ($classification !== null && !in_array($classification, ['Detainee', 'Sentenced', 'Awaiting Trial'], true)) {
            $classification = null;
        }
        if ($custodyStatus === 'Released' && !Auth::isAdmin()) {
            Response::json(false, 'Only administrators can release inmates.');
        }

        $userId = (int) Auth::user()['id'];

        $stmt = $this->db->prepare(
            'INSERT INTO inmates
             (inmate_number, jail_unit_id, first_name, middle_name, last_name, suffix,
              date_of_birth, sex, civil_status,
              municipality_id, barangay_id, offense_id, is_drug_case,
              sentence, sentence_years_min, sentence_years_max, sentence_is_manual,
              admission_date, committed_at, custody_status, classification,
              case_reference, notes, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $stmt->bind_param(
            'sssssssssiiiisssissssssi',
            $inmateNumber,
            $jailUnitId,
            $firstName,
            $middleName,
            $lastName,
            $suffix,
            $dob,
            $sex,
            $civilStatus,
            $municipalityId,
            $barangayId,
            $offenseId,
            $isDrugCase,
            $sentence,
            $sentenceMin,
            $sentenceMax,
            $sentenceManual,
            $admissionDate,
            $committedAt,
            $custodyStatus,
            $classification,
            $caseReference,
            $notes,
            $userId
        );

        try {
            $stmt->execute();
            $id = (int) $stmt->insert_id;
            $stmt->close();
            Response::json(true, 'Inmate created.', ['id' => $id]);
        } catch (\Throwable $e) {
            error_log('Inmate create: ' . $e->getMessage());
            Response::json(false, 'Unable to save: ' . $e->getMessage());
        }
    }

    /* ============================================================
       LIST
       ============================================================ */

    private function list(): void
    {
        $q              = trim((string) ($_GET['q'] ?? ''));
        $status         = trim((string) ($_GET['status'] ?? ''));
        $province       = trim((string) ($_GET['province'] ?? ''));
        $municipalityId = (int) ($_GET['municipality_id'] ?? 0);
        $barangayId     = (int) ($_GET['barangay_id'] ?? 0);
        $offenseId      = (int) ($_GET['offense_id'] ?? 0);
        $jailUnitId     = (int) ($_GET['jail_unit_id'] ?? 0);
        $limit          = max(1, min(100, (int) ($_GET['limit'] ?? 20)));
        $offset         = max(0, (int) ($_GET['offset'] ?? 0));

        $sql = 'SELECT i.id, i.inmate_number, i.jail_unit_id,
                       i.first_name, i.middle_name, i.last_name,
                       i.suffix, i.date_of_birth, i.sex, i.custody_status, i.classification,
                       i.is_drug_case,
                       i.admission_date, i.committed_at, i.case_reference,
                       i.sentence, i.sentence_years_min, i.sentence_years_max,
                       i.created_at,
                       i.municipality_id, i.barangay_id,
                       j.name AS jail_unit_name,
                       m.name AS municipality_name,
                       b.name AS barangay_name,
                       o.name AS offense_name,
                       o.code AS offense_code
                FROM inmates i
                LEFT JOIN jail_units j     ON j.id = i.jail_unit_id
                LEFT JOIN municipalities m ON m.id = i.municipality_id
                LEFT JOIN barangays b      ON b.id = i.barangay_id
                LEFT JOIN offenses o       ON o.id = i.offense_id
                WHERE 1 = 1';

        $params = [];
        $types  = '';

        if ($q !== '') {
            $sql .= ' AND (i.inmate_number LIKE ?
                        OR i.case_reference LIKE ?
                        OR i.first_name LIKE ?
                        OR i.middle_name LIKE ?
                        OR i.last_name LIKE ?
                        OR CONCAT_WS(" ", i.first_name, i.middle_name, i.last_name) LIKE ?)';
            $like = '%' . $q . '%';
            for ($i = 0; $i < 6; $i++) { $params[] = $like; $types .= 's'; }
        }

        if ($status !== '') {
            $sql .= ' AND i.custody_status = ?';
            $params[] = $status;
            $types .= 's';
        }

        if ($province !== '') {
            $sql .= ' AND m.province = ?';
            $params[] = $province;
            $types .= 's';
        }

        if ($municipalityId > 0) {
            $sql .= ' AND i.municipality_id = ?';
            $params[] = $municipalityId;
            $types .= 'i';
        }

        if ($barangayId > 0) {
            $sql .= ' AND i.barangay_id = ?';
            $params[] = $barangayId;
            $types .= 'i';
        }

        if ($offenseId > 0) {
            $sql .= ' AND i.offense_id = ?';
            $params[] = $offenseId;
            $types .= 'i';
        }

        if ($jailUnitId > 0) {
            $sql .= ' AND i.jail_unit_id = ?';
            $params[] = $jailUnitId;
            $types .= 'i';
        }

        $countSql = 'SELECT COUNT(*) AS c FROM (' . $sql . ') AS t';
        try {
            $stmt = $this->db->prepare($countSql);
            if ($params) $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $total = (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0);
            $stmt->close();
        } catch (\Throwable $e) {
            error_log('Inmate count: ' . $e->getMessage());
            Response::json(false, 'Unable to count inmates.');
        }

        $sql .= ' ORDER BY i.created_at DESC, i.id DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            foreach ($rows as &$r) {
                $r['id']                 = (int) $r['id'];
                $r['jail_unit_id']       = $r['jail_unit_id'] !== null ? (int) $r['jail_unit_id'] : null;
                $r['municipality_id']    = $r['municipality_id'] !== null ? (int) $r['municipality_id'] : null;
                $r['barangay_id']        = $r['barangay_id'] !== null ? (int) $r['barangay_id'] : null;
                $r['is_drug_case']       = (int) $r['is_drug_case'];
                $r['sentence_years_min'] = $r['sentence_years_min'] !== null ? (float) $r['sentence_years_min'] : null;
                $r['sentence_years_max'] = $r['sentence_years_max'] !== null ? (float) $r['sentence_years_max'] : null;
                $r['full_name'] = trim(
                    ($r['first_name'] ?? '') . ' ' .
                    ($r['middle_name'] ?? '') . ' ' .
                    ($r['last_name'] ?? '') . ' ' .
                    ($r['suffix'] ?? '')
                );
            }

            Response::json(true, 'OK', [
                'inmates' => $rows,
                'total'   => $total,
                'limit'   => $limit,
                'offset'  => $offset,
            ]);
        } catch (\Throwable $e) {
            error_log('Inmate list: ' . $e->getMessage());
            Response::json(false, 'Unable to load inmates.');
        }
    }

    /* ============================================================
       GET — single inmate
       ============================================================ */

    private function get(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) Response::json(false, 'Invalid inmate.');

        try {
            $stmt = $this->db->prepare(
                'SELECT
                    i.id,
                    i.inmate_number,
                    i.jail_unit_id,
                    i.case_reference,
                    i.first_name,
                    i.middle_name,
                    i.last_name,
                    i.suffix,
                    i.date_of_birth,
                    i.sex,
                    i.civil_status,
                    i.municipality_id,
                    i.barangay_id,
                    i.offense_id,
                    i.is_drug_case,
                    i.sentence,
                    i.sentence_years_min,
                    i.sentence_years_max,
                    i.sentence_is_manual,
                    i.admission_date,
                    i.committed_at,
                    i.custody_status,
                    i.classification,
                    i.notes,
                    i.created_at,
                    i.updated_at,
                    j.name AS jail_unit_name,
                    m.name AS municipality_name,
                    b.name AS barangay_name,
                    o.name AS offense_name,
                    o.code AS offense_code
                 FROM inmates i
                 LEFT JOIN jail_units j     ON j.id = i.jail_unit_id
                 LEFT JOIN municipalities m ON m.id = i.municipality_id
                 LEFT JOIN barangays b      ON b.id = i.barangay_id
                 LEFT JOIN offenses o       ON o.id = i.offense_id
                 WHERE i.id = ? LIMIT 1'
            );
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$row) Response::json(false, 'Inmate not found.');

            $row['id']                 = (int) $row['id'];
            $row['jail_unit_id']       = $row['jail_unit_id'] !== null ? (int) $row['jail_unit_id'] : null;
            $row['municipality_id']    = $row['municipality_id'] !== null ? (int) $row['municipality_id'] : null;
            $row['barangay_id']        = $row['barangay_id'] !== null ? (int) $row['barangay_id'] : null;
            $row['offense_id']         = $row['offense_id'] !== null ? (int) $row['offense_id'] : null;
            $row['is_drug_case']       = (int) $row['is_drug_case'];
            $row['sentence_years_min'] = $row['sentence_years_min'] !== null ? (float) $row['sentence_years_min'] : null;
            $row['sentence_years_max'] = $row['sentence_years_max'] !== null ? (float) $row['sentence_years_max'] : null;

            Response::json(true, 'OK', ['inmate' => $row]);
        } catch (\Throwable $e) {
            error_log('Inmate get: ' . $e->getMessage());
            Response::json(false, 'Unable to load inmate: ' . $e->getMessage());
        }
    }

    /* ============================================================
       UPDATE
       ============================================================ */

    private function update(): void
    {
        $p  = Response::readJsonInput();
        $id = (int) ($p['id'] ?? 0);

        if ($id <= 0) Response::json(false, 'Invalid inmate.');

        $check = $this->db->prepare('SELECT id, custody_status FROM inmates WHERE id = ? LIMIT 1');
        $check->bind_param('i', $id);
        $check->execute();
        $existing = $check->get_result()->fetch_assoc();
        $check->close();
        if (!$existing) Response::json(false, 'Inmate not found.');

        if (($existing['custody_status'] ?? '') === 'Released' && !Auth::isAdmin()) {
            Response::json(false, 'Released records can only be edited by administrators.');
        }

        $inmateNumber   = trim((string) ($p['inmate_number'] ?? ''));
        $jailUnitId     = (int) ($p['jail_unit_id'] ?? 0) ?: null;
        $caseReference  = trim((string) ($p['case_reference'] ?? ''));
        $firstName      = trim((string) ($p['first_name'] ?? ''));
        $middleName     = trim((string) ($p['middle_name'] ?? ''));
        $lastName       = trim((string) ($p['last_name'] ?? ''));
        $suffix         = trim((string) ($p['suffix'] ?? ''));
        $dob            = trim((string) ($p['date_of_birth'] ?? '')) ?: null;
        $sex            = trim((string) ($p['sex'] ?? 'Unspecified')) ?: 'Unspecified';
        $civilStatus    = trim((string) ($p['civil_status'] ?? '')) ?: null;
        $municipalityId = (int) ($p['municipality_id'] ?? 0) ?: null;
        $barangayId     = (int) ($p['barangay_id'] ?? 0) ?: null;
        $offenseId      = (int) ($p['offense_id'] ?? 0) ?: null;
        $isDrugCase     = !empty($p['is_drug_case']) ? 1 : 0;

        $sentence       = trim((string) ($p['sentence'] ?? '')) ?: null;
        $sentenceMin    = isset($p['sentence_years_min']) && $p['sentence_years_min'] !== ''
                            ? (float) $p['sentence_years_min'] : null;
        $sentenceMax    = isset($p['sentence_years_max']) && $p['sentence_years_max'] !== ''
                            ? (float) $p['sentence_years_max'] : null;
        $sentenceManual = !empty($p['sentence_is_manual']) ? 1 : 0;

        $admissionDate  = trim((string) ($p['admission_date'] ?? '')) ?: null;
        $committedAt    = trim((string) ($p['committed_at'] ?? '')) ?: null;
        $custodyStatus  = trim((string) ($p['custody_status'] ?? 'In Custody'));
        $classification = trim((string) ($p['classification'] ?? '')) ?: null;
        $notes          = trim((string) ($p['notes'] ?? '')) ?: null;

        if ($firstName === '' || $lastName === '') {
            Response::json(false, 'First name and last name are required.');
        }
        if (!in_array($custodyStatus, ['In Custody', 'Released', 'Transferred'], true)) {
            $custodyStatus = 'In Custody';
        }
        if (!in_array($sex, ['Male', 'Female', 'Other', 'Unspecified'], true)) {
            $sex = 'Unspecified';
        }
        if ($classification !== null && !in_array($classification, ['Detainee', 'Sentenced', 'Awaiting Trial'], true)) {
            $classification = null;
        }
        if ($custodyStatus === 'Released' && !Auth::isAdmin()) {
            Response::json(false, 'Only administrators can release inmates.');
        }

        $userId = (int) Auth::user()['id'];

        $stmt = $this->db->prepare(
            'UPDATE inmates SET
                inmate_number = ?,
                jail_unit_id = ?,
                first_name = ?, middle_name = ?, last_name = ?, suffix = ?,
                date_of_birth = ?, sex = ?, civil_status = ?,
                municipality_id = ?, barangay_id = ?, offense_id = ?, is_drug_case = ?,
                sentence = ?, sentence_years_min = ?, sentence_years_max = ?, sentence_is_manual = ?,
                admission_date = ?, committed_at = ?, custody_status = ?, classification = ?,
                case_reference = ?, notes = ?,
                updated_by = ?
             WHERE id = ?'
        );

        $stmt->bind_param(
            'sssssssssiiiisssissssssii',
            $inmateNumber,
            $jailUnitId,
            $firstName,
            $middleName,
            $lastName,
            $suffix,
            $dob,
            $sex,
            $civilStatus,
            $municipalityId,
            $barangayId,
            $offenseId,
            $isDrugCase,
            $sentence,
            $sentenceMin,
            $sentenceMax,
            $sentenceManual,
            $admissionDate,
            $committedAt,
            $custodyStatus,
            $classification,
            $caseReference,
            $notes,
            $userId,
            $id
        );

        try {
            $stmt->execute();
            $stmt->close();
            Response::json(true, 'Inmate updated.');
        } catch (\Throwable $e) {
            error_log('Inmate update: ' . $e->getMessage());
            Response::json(false, 'Unable to update: ' . $e->getMessage());
        }
    }

    /* ============================================================
       RELEASE — administrator only
       ============================================================ */

    private function release(): void
    {
        if (!Auth::isAdmin()) {
            Response::json(false, 'Only administrators can release inmates.');
        }

        $p  = Response::readJsonInput();
        $id = (int) ($p['id'] ?? 0);

        if ($id <= 0) {
            Response::json(false, 'Invalid inmate.');
        }

        $stmt = $this->db->prepare(
            'SELECT id, inmate_number, custody_status
             FROM inmates
             WHERE id = ? LIMIT 1'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            Response::json(false, 'Inmate not found.');
        }
        if ($row['custody_status'] === 'Released') {
            Response::json(false, 'Inmate is already released.');
        }

        $userId = (int) Auth::user()['id'];

        $stmt = $this->db->prepare(
            'UPDATE inmates SET
                custody_status = "Released",
                updated_by     = ?
             WHERE id = ?'
        );
        $stmt->bind_param('ii', $userId, $id);

        try {
            $stmt->execute();
            $stmt->close();
            Response::json(true, 'Inmate released.', [
                'id'            => $id,
                'inmate_number' => $row['inmate_number'],
            ]);
        } catch (\Throwable $e) {
            error_log('Inmate release: ' . $e->getMessage());
            Response::json(false, 'Unable to release inmate.');
        }
    }

    /* ============================================================
       EXPORT CSV
       ============================================================ */

    private function exportCsv(): void
    {
        $q              = trim((string) ($_GET['q'] ?? ''));
        $status         = trim((string) ($_GET['status'] ?? ''));
        $province       = trim((string) ($_GET['province'] ?? ''));
        $municipalityId = (int) ($_GET['municipality_id'] ?? 0);
        $barangayId     = (int) ($_GET['barangay_id'] ?? 0);
        $offenseId      = (int) ($_GET['offense_id'] ?? 0);
        $jailUnitId     = (int) ($_GET['jail_unit_id'] ?? 0);

        $sql = 'SELECT i.inmate_number, i.case_reference,
                       j.name AS jail_unit_name,
                       i.first_name, i.middle_name, i.last_name, i.suffix,
                       i.date_of_birth, i.sex, i.civil_status,
                       i.classification, i.is_drug_case, i.committed_at,
                       m.name AS municipality_name, b.name AS barangay_name,
                       o.name AS offense_name, o.code AS offense_code,
                       i.sentence, i.sentence_years_min, i.sentence_years_max,
                       i.admission_date, i.custody_status, i.notes
                FROM inmates i
                LEFT JOIN jail_units j     ON j.id = i.jail_unit_id
                LEFT JOIN municipalities m ON m.id = i.municipality_id
                LEFT JOIN barangays b      ON b.id = i.barangay_id
                LEFT JOIN offenses o       ON o.id = i.offense_id
                WHERE 1 = 1';

        $params = [];
        $types  = '';

        if ($q !== '') {
            $sql .= ' AND (i.inmate_number LIKE ? OR i.case_reference LIKE ?
                        OR i.first_name LIKE ? OR i.last_name LIKE ?)';
            $like = '%' . $q . '%';
            for ($i = 0; $i < 4; $i++) { $params[] = $like; $types .= 's'; }
        }
        if ($status !== '') {
            $sql .= ' AND i.custody_status = ?';
            $params[] = $status;
            $types .= 's';
        }
        if ($province !== '') {
            $sql .= ' AND m.province = ?';
            $params[] = $province;
            $types .= 's';
        }
        if ($municipalityId > 0) {
            $sql .= ' AND i.municipality_id = ?';
            $params[] = $municipalityId;
            $types .= 'i';
        }
        if ($barangayId > 0) {
            $sql .= ' AND i.barangay_id = ?';
            $params[] = $barangayId;
            $types .= 'i';
        }
        if ($offenseId > 0) {
            $sql .= ' AND i.offense_id = ?';
            $params[] = $offenseId;
            $types .= 'i';
        }
        if ($jailUnitId > 0) {
            $sql .= ' AND i.jail_unit_id = ?';
            $params[] = $jailUnitId;
            $types .= 'i';
        }

        $sql .= ' ORDER BY i.created_at DESC';

        $stmt = $this->db->prepare($sql);
        if ($params) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="inmates-' . date('Ymd-His') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");

        $delimiter = ',';
        $enclosure = '"';
        $escape    = '';

        fputcsv($out, [
            'Inmate No.', 'Case Ref.', 'Jail Unit',
            'First Name', 'Middle Name', 'Last Name', 'Suffix',
            'DOB', 'Sex', 'Civil Status', 'Classification', 'Drug Case', 'Committed',
            'Municipality', 'Barangay',
            'Offense', 'Code', 'Sentence', 'Years Min', 'Years Max',
            'Admission', 'Custody', 'Notes'
        ], $delimiter, $enclosure, $escape);

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['inmate_number']      ?? '',
                $r['case_reference']     ?? '',
                $r['jail_unit_name']     ?? '',
                $r['first_name']         ?? '',
                $r['middle_name']        ?? '',
                $r['last_name']          ?? '',
                $r['suffix']             ?? '',
                $r['date_of_birth']      ?? '',
                $r['sex']                ?? '',
                $r['civil_status']       ?? '',
                $r['classification']     ?? '',
                ((int) ($r['is_drug_case'] ?? 0) === 1) ? 'Yes' : 'No',
                $r['committed_at']       ?? '',
                $r['municipality_name']  ?? '',
                $r['barangay_name']      ?? '',
                $r['offense_name']       ?? '',
                $r['offense_code']       ?? '',
                $r['sentence']           ?? '',
                $r['sentence_years_min'] ?? '',
                $r['sentence_years_max'] ?? '',
                $r['admission_date']     ?? '',
                $r['custody_status']     ?? '',
                $r['notes']              ?? '',
            ], $delimiter, $enclosure, $escape);
        }

        fclose($out);
        exit;
    }
}