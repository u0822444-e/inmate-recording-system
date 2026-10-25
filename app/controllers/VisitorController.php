<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\ActivityLog;
use App\Helpers\Response;
use App\Models\Visitor;
use App\Services\Auth;
use mysqli;

class VisitorController
{
    private Visitor $model;
    private ActivityLog $logger;

    public function __construct(mysqli $db)
    {
        $this->model = new Visitor($db);
        $this->logger = new ActivityLog($db);
    }

    public function handle(string $action): void
    {
        if (!Auth::isLoggedIn()) {
            Response::json(false, 'Access denied.');
        }

        switch ($action) {
            case 'list':     $this->list();     break;
            case 'get':      $this->get();      break;
            case 'create':   $this->create();   break;
            case 'update':   $this->update();   break;
            case 'checkout': $this->checkout(); break;
            case 'delete':   $this->delete();   break;
            default:         Response::json(false, 'Unsupported request.');
        }
    }

    /* ============================================================
       LIST
       ============================================================ */

    private function list(): void
    {
        $q          = trim((string) ($_GET['q'] ?? ''));
        $jailUnitId = (int) ($_GET['jail_unit_id'] ?? 0);
        $status     = trim((string) ($_GET['status'] ?? ''));
        $date       = trim((string) ($_GET['date'] ?? ''));
        $limit      = max(1, min(100, (int) ($_GET['limit'] ?? 20)));
        $offset     = max(0, (int) ($_GET['offset'] ?? 0));

        $result = $this->model->all($q, $jailUnitId, $status, $date, $limit, $offset);

        Response::json(true, 'OK', [
            'visitors' => $result['rows'],
            'total'    => $result['total'],
            'limit'    => $limit,
            'offset'   => $offset,
        ]);
    }

    /* ============================================================
       GET
       ============================================================ */

    private function get(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) Response::json(false, 'Invalid visitor.');

        $row = $this->model->find($id);
        if (!$row) Response::json(false, 'Visitor not found.');

        Response::json(true, 'OK', ['visitor' => $row]);
    }

    /* ============================================================
       CREATE
       ============================================================ */

    private function create(): void
    {
        $p = Response::readJsonInput();

        $fullName     = trim((string) ($p['full_name'] ?? ''));
        $inmateId     = (int) ($p['inmate_id'] ?? 0) ?: null;
        $relationship = trim((string) ($p['relationship'] ?? '')) ?: null;
        $contactNo    = trim((string) ($p['contact_no'] ?? '')) ?: null;
        $address      = trim((string) ($p['address'] ?? '')) ?: null;
        $idType       = trim((string) ($p['id_type'] ?? '')) ?: null;
        $idNumber     = trim((string) ($p['id_number'] ?? '')) ?: null;
        $purpose      = trim((string) ($p['purpose'] ?? '')) ?: null;
        $notes        = trim((string) ($p['notes'] ?? '')) ?: null;

        $timeIn  = trim((string) ($p['time_in'] ?? '')) ?: date('Y-m-d H:i:s');
        $timeOut = trim((string) ($p['time_out'] ?? '')) ?: null;

        if ($fullName === '') {
            Response::json(false, 'Visitor name is required.');
        }
        if ($inmateId === null) {
            Response::json(false, 'Please select the PDL being visited.');
        }
        if ($contactNo !== null && !$this->validateContact($contactNo)) {
            Response::json(false, 'Invalid contact number. Use 09XXXXXXXXX format.');
        }

        // ID validation
        if (($idType !== null && $idNumber === null) || ($idType === null && $idNumber !== null)) {
            Response::json(false, 'Please provide both ID type and ID number, or leave both blank.');
        }
        if ($idType !== null && $idNumber !== null && !$this->validateIdNumber($idType, $idNumber)) {
            Response::json(false, 'Invalid format for ' . $idType . '.');
        }

        $userId = (int) Auth::user()['id'];

        $id = $this->model->create([
            'inmate_id'    => $inmateId,
            'full_name'    => $fullName,
            'relationship' => $relationship,
            'contact_no'   => $contactNo,
            'address'      => $address,
            'id_type'      => $idType,
            'id_number'    => $idNumber,
            'time_in'      => $timeIn,
            'time_out'     => $timeOut,
            'purpose'      => $purpose,
            'notes'        => $notes,
            'recorded_by'  => $userId,
        ]);

        $this->logger->log(
            $userId,
            'CREATE',
            'Visitor',
            $id,
            'Logged visitor: ' . $fullName . ' (Visiting PDL ID: ' . $inmateId . ')',
            null,
            ['full_name' => $fullName, 'inmate_id' => $inmateId, 'time_in' => $timeIn]
        );

        Response::json(true, 'Visitor logged.', ['id' => $id]);
    }

    /* ============================================================
       UPDATE
       ============================================================ */

    private function update(): void
    {
        $p  = Response::readJsonInput();
        $id = (int) ($p['id'] ?? 0);

        if ($id <= 0) Response::json(false, 'Invalid visitor.');
        if (!$this->model->find($id)) Response::json(false, 'Visitor not found.');

        $fullName     = trim((string) ($p['full_name'] ?? ''));
        $inmateId     = (int) ($p['inmate_id'] ?? 0) ?: null;
        $relationship = trim((string) ($p['relationship'] ?? '')) ?: null;
        $contactNo    = trim((string) ($p['contact_no'] ?? '')) ?: null;
        $address      = trim((string) ($p['address'] ?? '')) ?: null;
        $idType       = trim((string) ($p['id_type'] ?? '')) ?: null;
        $idNumber     = trim((string) ($p['id_number'] ?? '')) ?: null;
        $purpose      = trim((string) ($p['purpose'] ?? '')) ?: null;
        $notes        = trim((string) ($p['notes'] ?? '')) ?: null;

        $timeIn  = trim((string) ($p['time_in'] ?? '')) ?: null;
        $timeOut = trim((string) ($p['time_out'] ?? '')) ?: null;

        if ($fullName === '') {
            Response::json(false, 'Visitor name is required.');
        }
        if ($inmateId === null) {
            Response::json(false, 'Please select the PDL being visited.');
        }
        if ($contactNo !== null && !$this->validateContact($contactNo)) {
            Response::json(false, 'Invalid contact number. Use 09XXXXXXXXX format.');
        }

        // ID validation
        if (($idType !== null && $idNumber === null) || ($idType === null && $idNumber !== null)) {
            Response::json(false, 'Please provide both ID type and ID number, or leave both blank.');
        }
        if ($idType !== null && $idNumber !== null && !$this->validateIdNumber($idType, $idNumber)) {
            Response::json(false, 'Invalid format for ' . $idType . '.');
        }

        $this->model->update($id, [
            'inmate_id'    => $inmateId,
            'full_name'    => $fullName,
            'relationship' => $relationship,
            'contact_no'   => $contactNo,
            'address'      => $address,
            'id_type'      => $idType,
            'id_number'    => $idNumber,
            'time_in'      => $timeIn,
            'time_out'     => $timeOut,
            'purpose'      => $purpose,
            'notes'        => $notes,
        ]);

        $oldRecord = $this->model->find($id);
        
        $this->logger->log(
            (int) Auth::user()['id'],
            'UPDATE',
            'Visitor',
            $id,
            'Updated visitor record: ' . $fullName,
            $oldRecord ?: null,      // ← Pass the old data
            ['full_name' => $fullName, 'inmate_id' => $inmateId]
        );

        Response::json(true, 'Visitor updated.');
    }

    /* ============================================================
       CHECKOUT
       ============================================================ */

    private function checkout(): void
    {
        $p  = Response::readJsonInput();
        $id = (int) ($p['id'] ?? 0);

        if ($id <= 0) Response::json(false, 'Invalid visitor.');
        $row = $this->model->find($id);
        if (!$row) Response::json(false, 'Visitor not found.');

        if ($row['time_out'] !== null) {
            Response::json(false, 'Visitor is already checked out.');
        }

        $this->model->checkout($id);
        Response::json(true, 'Visitor checked out.');
    }

    /* ============================================================
       DELETE
       ============================================================ */

    private function delete(): void
    {
        if (!Auth::isAdmin()) {
            Response::json(false, 'Only administrators can delete visitor records.');
        }

        $p  = Response::readJsonInput();
        $id = (int) ($p['id'] ?? 0);

        if ($id <= 0) Response::json(false, 'Invalid visitor.');
        if (!$this->model->find($id)) Response::json(false, 'Visitor not found.');

        $this->model->delete($id);
        Response::json(true, 'Visitor record deleted.');
    }

    /* ============================================================
       VALIDATORS
       ============================================================ */

    private function validateContact(string $value): bool
    {
        $clean = preg_replace('/[\s\-()]/', '', $value);
        return (bool) preg_match('/^09[0-9]{9}$/', $clean);
    }

    private function validateIdNumber(string $type, string $number): bool
    {
        $clean = strtoupper(preg_replace('/[\s\-]/', '', $number));

        switch ($type) {
            case 'National ID':
            case 'UMID':
            case "Voter's ID":
                return (bool) preg_match('/^[0-9]{8,16}$/', $clean);

            case "Driver's License":
                return (bool) preg_match('/^[A-Z][0-9]{9,13}$/', $clean);

            case 'Passport':
                return (bool) preg_match('/^[A-Z][0-9]{6,9}$/', $clean);

            case 'PRC ID':
                return (bool) preg_match('/^[0-9]{6,8}$/', $clean);

            case 'Barangay ID':
            case 'Other':
                return (bool) preg_match('/^[A-Z0-9]{3,30}$/', $clean);

            default:
                return strlen($clean) >= 3;
        }
    }
}