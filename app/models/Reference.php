<?php
declare(strict_types=1);

namespace App\Models;

use mysqli;

class Reference
{
    public function __construct(private mysqli $db) {}

    public function provinces(): array
    {
        $res = $this->db->query(
            "SELECT DISTINCT province FROM municipalities ORDER BY province ASC"
        );
        return array_column($res->fetch_all(MYSQLI_ASSOC), 'province');
    }

    public function municipalities(string $province): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, name FROM municipalities
             WHERE province = ? ORDER BY name ASC"
        );
        $stmt->bind_param('s', $province);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($rows as &$r) $r['id'] = (int) $r['id'];
        return $rows;
    }

    public function barangays(int $municipalityId): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, name FROM barangays
             WHERE municipality_id = ? ORDER BY name ASC"
        );
        $stmt->bind_param('i', $municipalityId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($rows as &$r) $r['id'] = (int) $r['id'];
        return $rows;
    }

        public function offenses(): array
    {
        $res = $this->db->query(
            "SELECT id, code, name, default_sentence, min_years, max_years
             FROM offenses ORDER BY name ASC"
        );
        $rows = $res->fetch_all(MYSQLI_ASSOC);
        foreach ($rows as &$r) {
            $r['id']        = (int) $r['id'];
            $r['min_years'] = $r['min_years'] !== null ? (float) $r['min_years'] : null;
            $r['max_years'] = $r['max_years'] !== null ? (float) $r['max_years'] : null;
        }
        return $rows;
    }

    public function offense(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, code, name, default_sentence, min_years, max_years
             FROM offenses WHERE id = ? LIMIT 1"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $o = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$o) return null;
        $o['id']        = (int) $o['id'];
        $o['min_years'] = $o['min_years'] !== null ? (float) $o['min_years'] : null;
        $o['max_years'] = $o['max_years'] !== null ? (float) $o['max_years'] : null;
        return $o;
    }
    public function nextCaseNumber(): string
    {
        return $this->nextNumber('case_reference', 'CASE');
    }

    public function nextPdlNumber(): string
    {
        return $this->nextNumber('inmate_number', 'PDL');
    }

    private function nextNumber(string $column, string $prefix): string
    {
        $year = date('Y');
        $like = "$prefix-$year-%";

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS c FROM inmates WHERE $column LIKE ?"
        );
        $stmt->bind_param('s', $like);
        $stmt->execute();
        $count = (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0);
        $stmt->close();

        $next = $count + 1;

        while (true) {
            $candidate = sprintf('%s-%s-%04d', $prefix, $year, $next);
            $check = $this->db->prepare("SELECT 1 FROM inmates WHERE $column = ? LIMIT 1");
            $check->bind_param('s', $candidate);
            $check->execute();
            $exists = $check->get_result()->fetch_assoc();
            $check->close();

            if (!$exists) return $candidate;
            $next++;
        }
    }
}