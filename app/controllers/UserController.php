<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Response;
use App\Models\User;
use App\Services\Auth;
use mysqli;

class UserController
{
    private User $model;

    public function __construct(mysqli $db)
    {
        $this->model = new User($db);
    }

    public function handle(string $action): void
    {
        Auth::requireAdmin();

        $method = $_SERVER['REQUEST_METHOD'];

        if ($method === 'GET' && $action === 'list')
            $this->list();
        elseif ($method === 'GET' && $action === 'suggest-username')
            $this->suggestUsername();
        elseif ($method === 'POST' && $action === 'create')
            $this->create();
        elseif ($method === 'POST' && $action === 'update')
            $this->update();
        elseif ($method === 'POST' && $action === 'toggle')
            $this->toggle();
        elseif ($method === 'POST' && $action === 'archive')
            $this->archive();
        elseif ($method === 'POST' && $action === 'restore')
            $this->restore();
        elseif ($method === 'POST' && $action === 'delete')
            $this->delete();
        else
            Response::json(false, 'Unsupported request.');
    }

    private function list(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $role = trim((string) ($_GET['role'] ?? ''));
        $status = trim((string) ($_GET['status'] ?? ''));
        $scope = ($_GET['scope'] ?? 'active') === 'archived' ? 'archived' : 'active';
        $jailUnitId = (int) ($_GET['jail_unit_id'] ?? 0);

        Response::json(true, 'OK', [
            'users' => $this->model->all($q, $role, $status, $scope, $jailUnitId),
            'scope' => $scope,
        ]);
    }

    private function suggestUsername(): void
    {
        $first = trim((string) ($_GET['first_name'] ?? ''));
        $last = trim((string) ($_GET['last_name'] ?? ''));
        $id = (int) ($_GET['id'] ?? 0);

        if ($first === '' || $last === '') {
            Response::json(false, 'First and last name required.');
        }

        Response::json(true, 'OK', [
            'username' => $this->model->generateUsername($first, $last, $id),
        ]);
    }

    private function create(): void
    {
        $p = Response::readJsonInput();

        $fields = $this->extract($p);
        $this->validate($fields, null);

        if ($fields['username'] === '') {
            $fields['username'] = $this->model->generateUsername(
                $fields['first_name'],
                $fields['last_name']
            );
        }

        if ($this->model->usernameExists($fields['username'])) {
            Response::json(false, 'Username already exists.');
        }

        $fields['password'] = (string) ($p['password'] ?? '');
        if (strlen($fields['password']) < 8) {
            Response::json(false, 'Password must be at least 8 characters.');
        }

        $id = $this->model->create($fields);
        Response::json(true, 'User created.', ['id' => $id]);
    }

    private function update(): void
    {
        $p = Response::readJsonInput();
        $id = (int) ($p['id'] ?? 0);

        if ($id <= 0)
            Response::json(false, 'Invalid user.');

        if (!$this->model->find($id))
            Response::json(false, 'User not found.');

        $fields = $this->extract($p);
        $this->validate($fields, $id);

        $currentId = (int) Auth::user()['id'];
        if ($id === $currentId && $fields['role'] !== 'Administrator') {
            Response::json(false, 'You cannot change your own role.');
        }

        if ($fields['username'] !== '' && $this->model->usernameExists($fields['username'], $id)) {
            Response::json(false, 'Username already exists.');
        }

        $fields['password'] = (string) ($p['password'] ?? '');
        if ($fields['password'] !== '' && strlen($fields['password']) < 8) {
            Response::json(false, 'Password must be at least 8 characters.');
        }

        $this->model->update($id, $fields);
        Response::json(true, 'User updated.');
    }

    private function toggle(): void
    {
        $p = Response::readJsonInput();
        $id = (int) ($p['id'] ?? 0);

        if ($id <= 0)
            Response::json(false, 'Invalid user.');

        if (!$this->model->find($id))
            Response::json(false, 'User not found.');

        $currentId = (int) Auth::user()['id'];
        if ($id === $currentId)
            Response::json(false, 'You cannot deactivate yourself.');

        $this->model->toggle($id);
        Response::json(true, 'Status updated.');
    }

    private function archive(): void
    {
        $p = Response::readJsonInput();
        $id = (int) ($p['id'] ?? 0);

        if ($id <= 0)
            Response::json(false, 'Invalid user.');

        if (!$this->model->find($id))
            Response::json(false, 'User not found.');

        $currentId = (int) Auth::user()['id'];
        if ($id === $currentId)
            Response::json(false, 'You cannot archive yourself.');

        $this->model->archive($id);
        Response::json(true, 'User archived.');
    }

    private function restore(): void
    {
        $p = Response::readJsonInput();
        $id = (int) ($p['id'] ?? 0);

        if ($id <= 0)
            Response::json(false, 'Invalid user.');

        if (!$this->model->find($id))
            Response::json(false, 'User not found.');

        $this->model->restore($id);
        Response::json(true, 'User restored.');
    }

    private function delete(): void
    {
        $p = Response::readJsonInput();
        $id = (int) ($p['id'] ?? 0);

        if ($id <= 0)
            Response::json(false, 'Invalid user.');

        if (!$this->model->find($id))
            Response::json(false, 'User not found.');

        $currentId = (int) Auth::user()['id'];
        if ($id === $currentId)
            Response::json(false, 'You cannot delete yourself.');

        $this->model->delete($id);
        Response::json(true, 'User permanently deleted.');
    }

    /* ============================================================
       Helpers
       ============================================================ */

    private function extract(array $p): array
    {
        $first = trim((string) ($p['first_name'] ?? ''));
        $middle = trim((string) ($p['middle_name'] ?? ''));
        $last = trim((string) ($p['last_name'] ?? ''));

        $fullName = trim(implode(' ', array_filter([$first, $middle, $last])));

        return [
            'first_name' => $first,
            'middle_name' => $middle,
            'last_name' => $last,
            'full_name' => $fullName,
            'username' => trim((string) ($p['username'] ?? '')),
            'email' => trim((string) ($p['email'] ?? '')),
            'phone' => trim((string) ($p['phone'] ?? '')),
            'birthdate' => trim((string) ($p['birthdate'] ?? '')) ?: null,
            'sex' => trim((string) ($p['sex'] ?? '')) ?: null,
            'civil_status' => trim((string) ($p['civil_status'] ?? '')) ?: null,
            'employee_no' => trim((string) ($p['employee_no'] ?? '')) ?: null,
            'position' => trim((string) ($p['position'] ?? '')) ?: null,
            'rank' => trim((string) ($p['rank'] ?? '')) ?: null,
            'department' => trim((string) ($p['department'] ?? '')) ?: null,
            'date_hired' => trim((string) ($p['date_hired'] ?? '')) ?: null,
            'employment_status' => trim((string) ($p['employment_status'] ?? '')) ?: null,
            'bjmp_rank' => trim((string) ($p['bjmp_rank'] ?? '')) ?: null,
            'salary_grade' => isset($p['salary_grade']) && $p['salary_grade'] !== ''
                ? (int) $p['salary_grade'] : null,
            'personnel_type' => trim((string) ($p['personnel_type'] ?? '')) ?: null,
            'eligibility' => trim((string) ($p['eligibility'] ?? '')) ?: null,
            'role' => trim((string) ($p['role'] ?? '')),
            'jail_unit_id' => (int) ($p['jail_unit_id'] ?? 0) ?: null,
        ];
    }

    private function validate(array $f, ?int $id): void
    {
        if ($f['first_name'] === '' || $f['last_name'] === '') {
            Response::json(false, 'First name and last name are required.');
        }
        if (!in_array($f['role'], ['Administrator', 'Staff / Officer'], true)) {
            Response::json(false, 'Invalid role.');
        }

        if ($f['username'] !== '') {
            $len = strlen($f['username']);
            if ($len < 3 || $len > 100) {
                Response::json(false, 'Username must be 3-100 characters.');
            }
        }
    }
}