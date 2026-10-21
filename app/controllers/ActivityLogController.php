<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Response;
use App\Models\ActivityLog;
use App\Services\Auth;
use mysqli;

class ActivityLogController
{
    private ActivityLog $model;

    public function __construct(mysqli $db)
    {
        $this->model = new ActivityLog($db);
    }

        public function handle(string $action): void
    {
        if (!Auth::isLoggedIn() || !Auth::isAdmin()) {
            Response::json(false, 'Access denied.');
        }

        if ($action === 'list') {
            $limit  = max(1, min(100, (int) ($_GET['limit'] ?? 50)));
            $offset = max(0, (int) ($_GET['offset'] ?? 0));
            $q      = trim((string) ($_GET['q'] ?? ''));
            $module = trim((string) ($_GET['module'] ?? ''));

            $logs = $this->model->getAll($limit, $offset, $q, $module);
            Response::json(true, 'OK', ['logs' => $logs]);
        } elseif ($action === 'revert') {
            $this->revert();
        } else {
            Response::json(false, 'Unsupported request.');
        }
    }

            private function revert(): void
    {
        $p  = Response::readJsonInput();
        $id = (int) ($p['id'] ?? 0);

        if ($id <= 0) {
            Response::json(false, 'Invalid log ID.');
        }

        $adminId = (int) Auth::user()['id'];
        $result = $this->model->revertUpdate($id, $adminId);

        if ($result === true) {
            Response::json(true, 'Record successfully reverted.');
        } else {
            // Pass the exact error message back to the UI
            Response::json(false, 'Revert failed: ' . $result);
        }
    }
}