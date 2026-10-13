<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Response;
use App\Models\Incident;
use App\Services\Auth;
use mysqli;

class IncidentController
{
    private Incident $model;

    public function __construct(mysqli $db)
    {
        $this->model = new Incident($db);
    }

    public function handle(string $action): void
    {
        if (!Auth::isLoggedIn()) {
            Response::json(false, 'Access denied.');
        }

        switch ($action) {
            case 'list':   $this->list();   break;
            case 'get':    $this->get();    break;
            case 'create': $this->create(); break;
            case 'update': $this->update(); break;
            case 'delete': $this->delete(); break;
            default:       Response::json(false, 'Unsupported request.');
        }
    }

    /* ============================================================
       LIST
       ============================================================ */

    private function list(): void
    {
        $q              = trim((string) ($_GET['q'] ?? ''));
        $jailUnitId     = (int) ($_GET['jail_unit_id'] ?? 0);
        $type           = trim((string) ($_GET['type'] ?? ''));
        $severity       = trim((string) ($_GET['severity'] ?? ''));
        $status         = trim((string) ($_GET['status'] ?? ''));
        $date           = trim((string) ($_GET['date'] ?? ''));
        $municipalityId = (int) ($_GET['municipality_id'] ?? 0);
        $barangayId     = (int) ($_GET['barangay_id'] ?? 0);
        $limit          = max(1, min(100, (int) ($_GET['limit'] ?? 20)));
        $offset         = max(0, (int) ($_GET['offset'] ?? 0));

        // Staff with an assigned unit only see their own unit's incidents.
        $sessionJailUnitId = Auth::jailUnitId();
        if (!Auth::isAdmin() && $sessionJailUnitId !== null) {
            $jailUnitId = $sessionJailUnitId;
        }

        $result = $this->model->all(
            $q, $jailUnitId, $type, $severity, $status, $date,
            $municipalityId, $barangayId,
            $limit, $offset
        );

        Response::json(true, 'OK', [
            'incidents' => $result['rows'],
            'total'     => $result['total'],
            'limit'     => $limit,
            'offset'    => $offset,
        ]);
    }

    /* ============================================================
       GET
       ============================================================ */

    private function get(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) Response::json(false, 'Invalid incident.');

        $row = $this->model->find($id);
        if (!$row) Response::json(false, 'Incident not found.');

        // Staff can only view incidents from their own unit.
        $sessionJailUnitId = Auth::jailUnitId();
        if (!Auth::isAdmin() && $sessionJailUnitId !== null && (int) $row['jail_unit_id'] !== $sessionJailUnitId) {
            Response::json(false, 'Access denied.');
        }

        Response::json(true, 'OK', ['incident' => $row]);
    }

    /* ============================================================
       CREATE
       ============================================================ */

    private function create(): void
    {
        $p = Response::readJsonInput();

        // Jail unit: forced for staff with an assigned unit; taken from
        // the request for admins or unassigned users.
        $sessionJailUnitId = Auth::jailUnitId();
        if (!Auth::isAdmin() && $sessionJailUnitId !== null) {
            $jailUnitId = $sessionJailUnitId;
        } else {
            $jailUnitId = (int) ($p['jail_unit_id'] ?? 0) ?: null;
        }

        $incidentDate   = trim((string) ($p['incident_date'] ?? ''));
        $incidentTime   = trim((string) ($p['incident_time'] ?? ''));
        $incidentType   = trim((string) ($p['incident_type'] ?? ''));
        $province       = trim((string) ($p['province'] ?? '')) ?: null;
        $municipalityId = (int) ($p['municipality_id'] ?? 0) ?: null;
        $barangayId     = (int) ($p['barangay_id'] ?? 0) ?: null;
        $spot           = trim((string) ($p['spot'] ?? '')) ?: null;
        $severity       = trim((string) ($p['severity'] ?? 'Medium'));
        $status         = trim((string) ($p['status'] ?? 'Open'));
        $description    = trim((string) ($p['description'] ?? ''));
        $actionTaken    = trim((string) ($p['action_taken'] ?? '')) ?: null;
        $persons        = trim((string) ($p['persons_involved'] ?? '')) ?: null;
        $witnesses      = trim((string) ($p['witnesses'] ?? '')) ?: null;

        if ($incidentDate === '') Response::json(false, 'Incident date is required.');
        if ($incidentTime === '') Response::json(false, 'Incident time is required.');
        if ($incidentType === '') Response::json(false, 'Incident type is required.');
        if ($description === '')  Response::json(false, 'Description is required.');

        if (!in_array($severity, ['Low', 'Medium', 'High', 'Critical'], true)) {
            $severity = 'Medium';
        }
        if (!in_array($status, ['Open', 'Under Review', 'Resolved', 'Closed'], true)) {
            $status = 'Open';
        }

        $userId = (int) Auth::user()['id'];

        $id = $this->model->create([
            'jail_unit_id'     => $jailUnitId,
            'incident_date'    => $incidentDate,
            'incident_time'    => $incidentTime,
            'incident_type'    => $incidentType,
            'province'         => $province,
            'municipality_id'  => $municipalityId,
            'barangay_id'      => $barangayId,
            'spot'             => $spot,
            'severity'         => $severity,
            'status'           => $status,
            'description'      => $description,
            'action_taken'     => $actionTaken,
            'persons_involved' => $persons,
            'witnesses'        => $witnesses,
            'reported_by'      => $userId,
        ]);

        Response::json(true, 'Incident reported.', ['id' => $id]);
    }

    /* ============================================================
       UPDATE
       ============================================================ */

    private function update(): void
    {
        $p  = Response::readJsonInput();
        $id = (int) ($p['id'] ?? 0);

        if ($id <= 0) Response::json(false, 'Invalid incident.');
        $existing = $this->model->find($id);
        if (!$existing) Response::json(false, 'Incident not found.');

        // Jail unit: forced for staff with an assigned unit.
        $sessionJailUnitId = Auth::jailUnitId();
        if (!Auth::isAdmin() && $sessionJailUnitId !== null) {
            $jailUnitId = $sessionJailUnitId;

            // Staff can only edit incidents belonging to their own unit.
            if ((int) $existing['jail_unit_id'] !== $sessionJailUnitId) {
                Response::json(false, 'Access denied.');
            }
        } else {
            $jailUnitId = (int) ($p['jail_unit_id'] ?? 0) ?: null;
        }

        $incidentDate   = trim((string) ($p['incident_date'] ?? ''));
        $incidentTime   = trim((string) ($p['incident_time'] ?? ''));
        $incidentType   = trim((string) ($p['incident_type'] ?? ''));
        $province       = trim((string) ($p['province'] ?? '')) ?: null;
        $municipalityId = (int) ($p['municipality_id'] ?? 0) ?: null;
        $barangayId     = (int) ($p['barangay_id'] ?? 0) ?: null;
        $spot           = trim((string) ($p['spot'] ?? '')) ?: null;
        $severity       = trim((string) ($p['severity'] ?? 'Medium'));
        $status         = trim((string) ($p['status'] ?? 'Open'));
        $description    = trim((string) ($p['description'] ?? ''));
        $actionTaken    = trim((string) ($p['action_taken'] ?? '')) ?: null;
        $persons        = trim((string) ($p['persons_involved'] ?? '')) ?: null;
        $witnesses      = trim((string) ($p['witnesses'] ?? '')) ?: null;
        $resolvedAt     = trim((string) ($p['resolved_at'] ?? '')) ?: null;
        $reviewedBy     = (int) ($p['reviewed_by'] ?? 0) ?: null;

        if ($incidentDate === '') Response::json(false, 'Incident date is required.');
        if ($incidentTime === '') Response::json(false, 'Incident time is required.');
        if ($incidentType === '') Response::json(false, 'Incident type is required.');
        if ($description === '')  Response::json(false, 'Description is required.');

        if (!in_array($severity, ['Low', 'Medium', 'High', 'Critical'], true)) {
            $severity = 'Medium';
        }
        if (!in_array($status, ['Open', 'Under Review', 'Resolved', 'Closed'], true)) {
            $status = 'Open';
        }

        if (($status === 'Resolved' || $status === 'Closed') && $reviewedBy === null) {
            $reviewedBy = (int) Auth::user()['id'];
        }

        $this->model->update($id, [
            'jail_unit_id'     => $jailUnitId,
            'incident_date'    => $incidentDate,
            'incident_time'    => $incidentTime,
            'incident_type'    => $incidentType,
            'province'         => $province,
            'municipality_id'  => $municipalityId,
            'barangay_id'      => $barangayId,
            'spot'             => $spot,
            'severity'         => $severity,
            'status'           => $status,
            'description'      => $description,
            'action_taken'     => $actionTaken,
            'persons_involved' => $persons,
            'witnesses'        => $witnesses,
            'reviewed_by'      => $reviewedBy,
            'resolved_at'      => $resolvedAt,
        ]);

        Response::json(true, 'Incident updated.');
    }

    /* ============================================================
       DELETE
       ============================================================ */

    private function delete(): void
    {
        if (!Auth::isAdmin()) {
            Response::json(false, 'Only administrators can delete incident records.');
        }

        $p  = Response::readJsonInput();
        $id = (int) ($p['id'] ?? 0);

        if ($id <= 0) Response::json(false, 'Invalid incident.');
        if (!$this->model->find($id)) Response::json(false, 'Incident not found.');

        $this->model->delete($id);
        Response::json(true, 'Incident record deleted.');
    }
}