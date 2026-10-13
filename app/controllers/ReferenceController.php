<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Response;
use App\Models\JailUnit;
use App\Models\Reference;
use App\Services\Auth;
use mysqli;

class ReferenceController
{
    private Reference $model;
    private JailUnit $jailUnitModel;

    public function __construct(private mysqli $db)
    {
        $this->model         = new Reference($db);
        $this->jailUnitModel = new JailUnit($db);
    }

    public function handle(string $action): void
    {
        if (!Auth::isLoggedIn()) {
            Response::json(false, 'Access denied.');
        }

        switch ($action) {
            case 'provinces':       $this->provinces();      break;
            case 'municipalities':  $this->municipalities(); break;
            case 'barangays':       $this->barangays();      break;
            case 'offenses':        $this->offenses();       break;
            case 'offense':         $this->offense();        break;
            case 'jail-units':      $this->jailUnits();      break;
            case 'inmates-by-unit': $this->inmatesByUnit();  break;
            case 'next-pdl':        $this->nextPdl();        break;
            case 'next-case':       $this->nextCase();       break;
            default:                Response::json(false, 'Unsupported request.');
        }
    }

    private function provinces(): void
    {
        Response::json(true, 'OK', ['provinces' => $this->model->provinces()]);
    }

    private function municipalities(): void
    {
        $province = trim((string) ($_GET['province'] ?? ''));
        Response::json(true, 'OK', [
            'municipalities' => $this->model->municipalities($province),
        ]);
    }

    private function barangays(): void
    {
        $municipalityId = (int) ($_GET['municipality_id'] ?? 0);
        Response::json(true, 'OK', [
            'barangays' => $this->model->barangays($municipalityId),
        ]);
    }

    private function offenses(): void
    {
        Response::json(true, 'OK', ['offenses' => $this->model->offenses()]);
    }

    private function offense(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $offense = $this->model->offense($id);
        if (!$offense) {
            Response::json(false, 'Offense not found.');
        }
        Response::json(true, 'OK', ['offense' => $offense]);
    }

    private function jailUnits(): void
    {
        Response::json(true, 'OK', [
            'jail_units' => $this->jailUnitModel->all(true),
        ]);
    }

    /* ============================================================
       INMATES BY UNIT
       Returns in-custody PDLs, optionally scoped to one jail unit.
       When jail_unit_id = 0, returns all in-custody PDLs.
       Used by the Visitors modal's "PDL Visited" dropdown.
       ============================================================ */

    private function inmatesByUnit(): void
    {
        $jailUnitId = (int) ($_GET['jail_unit_id'] ?? 0);
        $rows = [];

        try {
            if ($jailUnitId > 0) {
                $stmt = $this->db->prepare(
                    "SELECT i.id,
                            i.inmate_number,
                            TRIM(CONCAT_WS(' ', i.first_name, i.middle_name, i.last_name, i.suffix)) AS full_name,
                            i.jail_unit_id,
                            j.name AS jail_unit_name
                     FROM inmates i
                     LEFT JOIN jail_units j ON j.id = i.jail_unit_id
                     WHERE i.jail_unit_id = ?
                       AND i.custody_status = 'In Custody'
                     ORDER BY i.last_name ASC, i.first_name ASC"
                );
                $stmt->bind_param('i', $jailUnitId);
            } else {
                $stmt = $this->db->prepare(
                    "SELECT i.id,
                            i.inmate_number,
                            TRIM(CONCAT_WS(' ', i.first_name, i.middle_name, i.last_name, i.suffix)) AS full_name,
                            i.jail_unit_id,
                            j.name AS jail_unit_name
                     FROM inmates i
                     LEFT JOIN jail_units j ON j.id = i.jail_unit_id
                     WHERE i.custody_status = 'In Custody'
                     ORDER BY i.last_name ASC, i.first_name ASC"
                );
            }

            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            foreach ($rows as &$r) {
                $r['id']           = (int) $r['id'];
                $r['jail_unit_id'] = $r['jail_unit_id'] !== null ? (int) $r['jail_unit_id'] : null;
            }
        } catch (\Throwable $e) {
            error_log('inmatesByUnit: ' . $e->getMessage());
        }

        Response::json(true, 'OK', ['inmates' => $rows]);
    }

    private function nextPdl(): void
    {
        Response::json(true, 'OK', ['pdl_no' => $this->model->nextPdlNumber()]);
    }

    private function nextCase(): void
    {
        Response::json(true, 'OK', ['case_no' => $this->model->nextCaseNumber()]);
    }
}