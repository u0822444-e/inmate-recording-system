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
            case 'list':     $this->list();     break;
            case 'get':      $this->get();      break;
            case 'submit':   $this->submit();   break;
            case 'update':   $this->update();   break;
            case 'verify':   $this->verify();   break;
            case 'archive':  $this->archive();  break;
            case 'restore':  $this->restore();  break;
            case 'delete':   $this->delete();   break;
            case 'today':    $this->today();    break;
            case 'expected': $this->expected(); break;
            default:         Response::json(false, 'Unsupported request.');
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
        $scope      = ($_GET['scope'] ?? 'active') === 'archived' ? 'archived' : 'active';
        $limit      = max(1, min(100, (int) ($_GET['limit'] ?? 20)));
        $offset     = max(0, (int) ($_GET['offset'] ?? 0));

        // Staff with an assigned unit sees only their own.
        $sessionJailUnitId = Auth::jailUnitId();
        if (!Auth::isAdmin() && $sessionJailUnitId !== null) {
            $jailUnitId = $sessionJailUnitId;
        }

        $result = $this->model->all($jailUnitId, $shift, $status, $date, $limit, $offset, $scope);

        Response::json(true, 'OK', [
            'headcounts' => $result['rows'],
            'total'      => $result['total'],
            'limit'      => $limit,
            'offset'     => $offset,
            'scope'      => $scope,
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
       UPDATE — admin any, staff own (before verified)
       ============================================================ */

    private function update(): void
    {
        $p  = Response::readJsonInput();
        $id = (int) ($p['id'] ?? 0);

        if ($id <= 0) Response::json(false, 'Invalid headcount.');

        $row = $this->model->find($id);
        if (!$row) Response::json(false, 'Headcount not found.');

        // Cannot edit archived rows
        if (!empty($row['archived_at'])) {
            Response::json(false, 'Archived headcounts cannot be edited. Restore it first.');
        }

        $userId            = (int) Auth::user()['id'];
        $sessionJailUnitId = Auth::jailUnitId();
        $isAdmin           = Auth::isAdmin();

        if (!$isAdmin) {
            if ($sessionJailUnitId !== null && (int) $row['jail_unit_id'] !== $sessionJailUnitId) {
                Response::json(false, 'Access denied.');
            }
            if ((int) $row['recorded_by'] !== $userId) {
                Response::json(false, 'You can only edit your own headcount submissions.');
            }
            if ($row['status'] !== 'Submitted') {
                Response::json(false, 'This headcount has already been ' . strtolower($row['status']) . ' and can no longer be edited.');
            }
        }

        $shift      = trim((string) ($p['shift'] ?? ''));
        $recordedOn = trim((string) ($p['recorded_on'] ?? ''));
        $actual     = (int) ($p['actual_count'] ?? -1);
        $remarks    = trim((string) ($p['remarks'] ?? '')) ?: null;

        if (!in_array($shift, ['Morning', 'Afternoon', 'Night'], true)) {
            Response::json(false, 'Invalid shift.');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $recordedOn)) {
            Response::json(false, 'Invalid date.');
        }
        if ($actual < 0) {
            Response::json(false, 'Actual count is required.');
        }

        $jailUnitId = (int) $row['jail_unit_id'];
        if ($this->model->existsForShift($recordedOn, $shift, $jailUnitId, $id)) {
            Response::json(false, 'A headcount for this shift already exists for this unit on ' . $recordedOn . '.');
        }

        $this->model->update($id, [
            'shift'        => $shift,
            'recorded_on'  => $recordedOn,
            'actual_count' => $actual,
            'remarks'      => $remarks,
        ]);

        Response::json(true, 'Headcount updated.');
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

        if (!empty($row['archived_at'])) {
            Response::json(false, 'Archived headcounts cannot be verified.');
        }

        $discrepancy = !empty($p['discrepancy']);
        $userId      = (int) Auth::user()['id'];

        $this->model->verify($id, $userId, $discrepancy);
        Response::json(true, $discrepancy ? 'Flagged as discrepancy.' : 'Headcount verified.');
    }

    /* ============================================================
       ARCHIVE — admin any, staff own-unverified
       ============================================================ */

    private function archive(): void
    {
        $p  = Response::readJsonInput();
        $id = (int) ($p['id'] ?? 0);

        if ($id <= 0) Response::json(false, 'Invalid headcount.');

        $row = $this->model->find($id);
        if (!$row) Response::json(false, 'Headcount not found.');

        if (!empty($row['archived_at'])) {
            Response::json(false, 'This headcount is already archived.');
        }

        $userId            = (int) Auth::user()['id'];
        $sessionJailUnitId = Auth::jailUnitId();
        $isAdmin           = Auth::isAdmin();

        if (!$isAdmin) {
            if ($sessionJailUnitId !== null && (int) $row['jail_unit_id'] !== $sessionJailUnitId) {
                Response::json(false, 'Access denied.');
            }
            if ((int) $row['recorded_by'] !== $userId) {
                Response::json(false, 'You can only archive your own headcount submissions.');
            }
            if ($row['status'] !== 'Submitted') {
                Response::json(false, 'Verified or flagged headcounts can only be archived by administrators.');
            }
        }

        $this->model->archive($id);
        Response::json(true, 'Headcount archived.');
    }

    /* ============================================================
       RESTORE (admin only)
       ============================================================ */

    private function restore(): void
    {
        if (!Auth::isAdmin()) {
            Response::json(false, 'Only administrators can restore headcounts.');
        }

        $p  = Response::readJsonInput();
        $id = (int) ($p['id'] ?? 0);

        if ($id <= 0) Response::json(false, 'Invalid headcount.');

        $row = $this->model->find($id);
        if (!$row) Response::json(false, 'Headcount not found.');

        if (empty($row['archived_at'])) {
            Response::json(false, 'This headcount is not archived.');
        }

        // Check for a duplicate active row for the same (date, shift, unit)
        if ($this->model->existsForShift($row['recorded_on'], $row['shift'], (int) $row['jail_unit_id'], $id)) {
            Response::json(false, 'Cannot restore: another active headcount already exists for this date/shift/unit.');
        }

        $this->model->restore($id);
        Response::json(true, 'Headcount restored.');
    }

    /* ============================================================
       PERMANENT DELETE (admin only, archived rows only)
       ============================================================ */

    private function delete(): void
    {
        if (!Auth::isAdmin()) {
            Response::json(false, 'Only administrators can permanently delete headcounts.');
        }

        $p  = Response::readJsonInput();
        $id = (int) ($p['id'] ?? 0);

        if ($id <= 0) Response::json(false, 'Invalid headcount.');

        $row = $this->model->find($id);
        if (!$row) Response::json(false, 'Headcount not found.');

        if (empty($row['archived_at'])) {
            Response::json(false, 'Only archived headcounts can be permanently deleted. Archive it first.');
        }

        $this->model->delete($id);
        Response::json(true, 'Headcount permanently deleted.');
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