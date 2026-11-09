<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Response;
use App\Models\JailUnit;
use App\Services\Auth;
use mysqli;

class JailUnitController
{
    private JailUnit $model;
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
        $this->model = new JailUnit($db);
    }

    public function handle(string $action): void
    {
        if (!Auth::isLoggedIn()) {
            Response::json(false, 'Access denied.');
        }

        switch ($action) {
            case 'list':
                $this->list();
                break;
            case 'create':
                $this->create();
                break;
            case 'update':
                $this->update();
                break;
            case 'toggle':
                $this->toggle();
                break;
            case 'cells':
                $this->getCells();
                break;
            case 'create-cell':
                $this->createCell();
                break;
            case 'delete-cell':
                $this->deleteCell();
                break;
            default:
                Response::json(false, 'Unsupported request.');
        }
    }

    private function getCells(): void
    {
        $unitId = (int) ($_GET['jail_unit_id'] ?? 0);
        if ($unitId <= 0) Response::json(false, 'Invalid jail unit.');

        try {
            $stmt = $this->db->prepare("SELECT id, cell_name FROM cells WHERE jail_unit_id = ? ORDER BY cell_name ASC");
            $stmt->bind_param('i', $unitId);
            $stmt->execute();
            $cells = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            Response::json(true, 'OK', ['cells' => $cells]);
        } catch (\Throwable $e) {
            error_log('getCells: ' . $e->getMessage());
            Response::json(false, 'Unable to load cells: ' . $e->getMessage());
        }
    }

    private function createCell(): void
    {
        if (!Auth::isAdmin()) {
            Response::json(false, 'Only administrators can manage cells.');
        }

        $p = Response::readJsonInput();
        $unitId = (int) ($p['jail_unit_id'] ?? 0);
        $cellName = trim((string) ($p['cell_name'] ?? ''));

        if ($unitId <= 0 || $cellName === '') {
            Response::json(false, 'Jail unit and cell name are required.');
        }

        try {
            $stmt = $this->db->prepare("INSERT INTO cells (jail_unit_id, cell_name) VALUES (?, ?)");
            $stmt->bind_param('is', $unitId, $cellName);
            $stmt->execute();
            $stmt->close();

            Response::json(true, 'Cell added successfully.');
        } catch (\Throwable $e) {
            error_log('createCell: ' . $e->getMessage());
            Response::json(false, 'Unable to add cell: ' . $e->getMessage());
        }
    }

    private function deleteCell(): void
    {
        if (!Auth::isAdmin()) {
            Response::json(false, 'Only administrators can manage cells.');
        }

        $p = Response::readJsonInput();
        $id = (int) ($p['id'] ?? 0);

        if ($id <= 0) Response::json(false, 'Invalid cell ID.');

        try {
            $stmt = $this->db->prepare("DELETE FROM cells WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();

            Response::json(true, 'Cell deleted successfully.');
        } catch (\Throwable $e) {
            error_log('deleteCell: ' . $e->getMessage());
            Response::json(false, 'Unable to delete cell: ' . $e->getMessage());
        }
    }

    private function list(): void
    {
        $activeOnly = ($_GET['active_only'] ?? '1') === '1';
        Response::json(true, 'OK', [
            'jail_units' => $this->model->all($activeOnly),
        ]);
    }

    private function create(): void
    {
        if (!Auth::isAdmin()) {
            Response::json(false, 'Only administrators can manage jail units.');
        }

        $p = Response::readJsonInput();

        $name = trim((string) ($p['name'] ?? ''));
        $type = trim((string) ($p['type'] ?? 'Municipal'));
        $dormitory = trim((string) ($p['dormitory'] ?? 'Combined'));
        $municipality = trim((string) ($p['municipality'] ?? ''));
        $province = trim((string) ($p['province'] ?? 'Zamboanga Sibugay'));
        $warden = trim((string) ($p['warden'] ?? ''));

        if ($name === '') {
            Response::json(false, 'Jail unit name is required.');
        }
        if (!in_array($type, ['District', 'Municipal', 'City'], true)) {
            $type = 'Municipal';
        }
        if (!in_array($dormitory, ['Male', 'Female', 'Combined'], true)) {
            $dormitory = 'Combined';
        }
        if ($this->model->nameExists($name)) {
            Response::json(false, 'A jail unit with this name already exists.');
        }

        $id = $this->model->create([
            'name' => $name,
            'type' => $type,
            'dormitory' => $dormitory,
            'municipality' => $municipality,
            'province' => $province,
            'warden' => $warden,
            'is_active' => 1,
        ]);

        Response::json(true, 'Jail unit created.', ['id' => $id]);
    }

    private function update(): void
    {
        if (!Auth::isAdmin()) {
            Response::json(false, 'Only administrators can manage jail units.');
        }

        $p = Response::readJsonInput();
        $id = (int) ($p['id'] ?? 0);

        if ($id <= 0) {
            Response::json(false, 'Invalid jail unit.');
        }
        if (!$this->model->find($id)) {
            Response::json(false, 'Jail unit not found.');
        }

        $name = trim((string) ($p['name'] ?? ''));
        if ($name === '') {
            Response::json(false, 'Jail unit name is required.');
        }
        if ($this->model->nameExists($name, $id)) {
            Response::json(false, 'A jail unit with this name already exists.');
        }

        $type = trim((string) ($p['type'] ?? 'Municipal'));
        $dormitory = trim((string) ($p['dormitory'] ?? 'Combined'));
        if (!in_array($type, ['District', 'Municipal', 'City'], true)) {
            $type = 'Municipal';
        }
        if (!in_array($dormitory, ['Male', 'Female', 'Combined'], true)) {
            $dormitory = 'Combined';
        }

        $this->model->update($id, [
            'name' => $name,
            'type' => $type,
            'dormitory' => $dormitory,
            'municipality' => trim((string) ($p['municipality'] ?? '')),
            'province' => trim((string) ($p['province'] ?? 'Zamboanga Sibugay')),
            'warden' => trim((string) ($p['warden'] ?? '')),
            'is_active' => !empty($p['is_active']) ? 1 : 0,
        ]);

        Response::json(true, 'Jail unit updated.');
    }

    private function toggle(): void
    {
        if (!Auth::isAdmin()) {
            Response::json(false, 'Only administrators can manage jail units.');
        }

        $p = Response::readJsonInput();
        $id = (int) ($p['id'] ?? 0);

        if ($id <= 0) {
            Response::json(false, 'Invalid jail unit.');
        }
        if (!$this->model->find($id)) {
            Response::json(false, 'Jail unit not found.');
        }

        $this->model->toggle($id);
        Response::json(true, 'Status updated.');
    }
}