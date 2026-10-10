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

    public function __construct(mysqli $db)
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
            case 'provinces':      $this->provinces();      break;
            case 'municipalities': $this->municipalities(); break;
            case 'barangays':      $this->barangays();      break;
            case 'offenses':       $this->offenses();       break;
            case 'offense':        $this->offense();        break;
            case 'jail-units':     $this->jailUnits();      break;
            case 'next-pdl':       $this->nextPdl();        break;
            case 'next-case':      $this->nextCase();       break;
            default:               Response::json(false, 'Unsupported request.');
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

    private function nextPdl(): void
    {
        Response::json(true, 'OK', ['pdl_no' => $this->model->nextPdlNumber()]);
    }

    private function nextCase(): void
    {
        Response::json(true, 'OK', ['case_no' => $this->model->nextCaseNumber()]);
    }
}