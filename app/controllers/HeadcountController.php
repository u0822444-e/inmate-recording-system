<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Response;
use App\Models\Headcount;
use App\Services\Auth;
use mysqli;

class HeadcountController
{
    private Headcount $model;

    public function __construct(mysqli $db)
    {
        $this->model = new Headcount($db);
    }

    public function handle(string $action): void
    {
        if (!Auth::isLoggedIn()) {
            Response::json(false, 'Access denied.');
        }

        switch ($action) {
            case 'list':    $this->list();    break;
            case 'get':     $this->get();     break;
            case 'submit':  $this->submit();  break;
            case 'verify':  $this->verify();  break;
            case 'delete':  $this->delete();  break;
            case 'today':   $this->today();   break;
            case 'expected': $this->expected(); break;
            default:        Response::json(false, 'Unsupported request.');
        }
    }

    /* ============================================================
       LIST
       ============================================================ */

    private function list(): void
    {
        $jailUnitId = (int) ($_GET['jail_unit_id'] ?? 0);
        $shift      = trim((string) ($_GET['shift'] ?? ''));
        $status     = trim((string) ($_GET['status'] ?? ''));
        $date       = trim((string) ($_GET['date'] ?? ''));
        $limit      = max(1, min(100, (int) ($_GET['limit'] ?? 20)));
        $offset     = max(0, (int) ($_GET['offset'] ?? 0));

        // Staff with an assigned unit sees only their own.
        $sessionJailUnitId = Auth::jailUnitId();
        if (!Auth::isAdmin() && $sessionJailUnitId !== null) {
            $jailUnitId = $sessionJailUnitId;
        }

        $result = $this->model->all($jailUnitId, $shift, $status, $date, $limit, $offset);

        Response::json(true, 'OK', [
            'headcounts' => $result['rows'],
            'total'      => $result['total'],
            'limit'      => $limit,
            'offset'     => $offset,
        ]);
    }

    /* ============================================================
       GET
       ============================================================ */

    private function get(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) Response::json(false, 'Invalid headcount.');

        $row = $this->model->find($id);
        if (!$row) Response::json(false, 'Headcount not found.');

        // Staff can only see their own unit's headcounts
        $sessionJailUnitId = Auth::jailUnitId();
        if (!Auth::isAdmin() && $sessionJailUnitId !== null && (int) $row['jail_unit_id'] !== $sessionJailUnitId) {
            Response::json(false, 'Access denied.');
        }

        Response::json(true, 'OK', ['headcount' => $row]);
    }

    /* ============================================================
       SUBMIT
       ============================================================ */

    private function submit(): void
    {
        $p = Response::readJsonInput();

        // Jail unit — forced for staff with an assigned unit
        $sessionJailUnitId = Auth::jailUnitId();
        if (!Auth::isAdmin() && $sessionJailUnitId !== null) {
            $jailUnitId = $sessionJailUnitId;
        } else {
            $jailUnitId = (int) ($p['jail_unit_id'] ?? 0) ?: null;
        }

        $shift      = trim((string) ($p['shift'] ?? ''));
        $recordedOn = trim((string) ($p['recorded_on'] ?? '')) ?: date('Y-m-d');
        $actual     = (int) ($p['actual_count'] ?? -1);
        $remarks    = trim((string) ($p['remarks'] ?? '')) ?: null;

        if ($jailUnitId === null) {
            Response::json(false, 'Please select a jail unit.');
        }
        if (!in_array($shift, ['Morning', 'Afternoon', 'Night'], true)) {
            Response::json(false, 'Invalid shift.');
        }
        if ($actual < 0) {
            Response::json(false, 'Actual count is required.');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $recordedOn)) {
            Response::json(false, 'Invalid date.');
        }

        if ($this->model->existsForShift($recordedOn, $shift, $jailUnitId)) {
            Response::json(false, 'A headcount for this shift already exists for this unit on ' . $recordedOn . '.');
        }

        $expected = $this->model->computeExpected($jailUnitId);
        $userId   = (int) Auth::user()['id'];

        $id = $this->model->submit([
            'shift'          => $shift,
            'jail_unit_id'   => $jailUnitId,
            'recorded_on'    => $recordedOn,
            'expected_count' => $expected,
            'actual_count'   => $actual,
            'remarks'        => $remarks,
            'recorded_by'    => $userId,
        ]);

        Response::json(true, 'Headcount submitted.', [
            'id'             => $id,
            'expected_count' => $expected,
        ]);
    }

    /* ============================================================
       VERIFY (admin only)
       ============================================================ */

    private function verify(): void
    {
        if (!Auth::isAdmin()) {
            Response::json(false, 'Only administrators can verify headcounts.');
        }

        $p  = Response::readJsonInput();
        $id = (int) ($p['id'] ?? 0);

        if ($id <= 0) Response::json(false, 'Invalid headcount.');
        $row = $this->model->find($id);
        if (!$row) Response::json(false, 'Headcount not found.');

        $discrepancy = !empty($p['discrepancy']);
        $userId      = (int) Auth::user()['id'];

        $this->model->verify($id, $userId, $discrepancy);
        Response::json(true, $discrepancy ? 'Flagged as discrepancy.' : 'Headcount verified.');
    }

    /* ============================================================
       DELETE (admin only)
       ============================================================ */

    private function delete(): void
    {
        if (!Auth::isAdmin()) {
            Response::json(false, 'Only administrators can delete headcounts.');
        }

        $p  = Response::readJsonInput();
        $id = (int) ($p['id'] ?? 0);

        if ($id <= 0) Response::json(false, 'Invalid headcount.');
        if (!$this->model->find($id)) Response::json(false, 'Headcount not found.');

        $this->model->delete($id);
        Response::json(true, 'Headcount record deleted.');
    }

    /* ============================================================
       TODAY — shift status for the current user's unit
       ============================================================ */

    private function today(): void
    {
        $sessionJailUnitId = Auth::jailUnitId();
        $jailUnitId = (!Auth::isAdmin() && $sessionJailUnitId !== null)
            ? $sessionJailUnitId
            : (int) ($_GET['jail_unit_id'] ?? 0);

        $date   = date('Y-m-d');
        $status = $jailUnitId > 0
            ? $this->model->shiftStatusForToday($jailUnitId, $date)
            : [];

        Response::json(true, 'OK', [
            'date'   => $date,
            'unit'   => $jailUnitId,
            'shifts' => $status,
        ]);
    }

    /* ============================================================
       EXPECTED — current in-custody count
       ============================================================ */

    private function expected(): void
    {
        $sessionJailUnitId = Auth::jailUnitId();
        $jailUnitId = (!Auth::isAdmin() && $sessionJailUnitId !== null)
            ? $sessionJailUnitId
            : (int) ($_GET['jail_unit_id'] ?? 0);

        Response::json(true, 'OK', [
            'expected_count' => $this->model->computeExpected($jailUnitId),
        ]);
    }
}