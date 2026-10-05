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

        if ($method === 'GET' && $action === 'list')         $this->list();
        elseif ($method === 'POST' && $action === 'create')  $this->create();
        elseif ($method === 'POST' && $action === 'update')  $this->update();
        elseif ($method === 'POST' && $action === 'toggle')  $this->toggle();
        elseif ($method === 'POST' && $action === 'delete')  $this->delete();
        else Response::json(false, 'Unsupported request.');
    }

    private function list(): void
    {
        $q      = trim((string) ($_GET['q'] ?? ''));
        $role   = trim((string) ($_GET['role'] ?? ''));
        $status = trim((string) ($_GET['status'] ?? ''));

        Response::json(true, 'OK', ['users' => $this->model->all($q, $role, $status)]);
    }

    private function create(): void
    {
        $p = Response::readJsonInput();

        $username = trim((string) ($p['username'] ?? ''));
        $fullName = trim((string) ($p['full_name'] ?? ''));
        $role     = trim((string) ($p['role'] ?? ''));
        $password = (string) ($p['password'] ?? '');

        if ($username === '' || $fullName === '' || $role === '' || $password === '') {
            Response::json(false, 'All fields are required.');
        }
        if (!in_array($role, ['Administrator', 'Staff / Officer'], true)) {
            Response::json(false, 'Invalid role.');
        }
        if (strlen($username) < 3 || strlen($username) > 100) {
            Response::json(false, 'Username must be 3-100 characters.');
        }
        if (strlen($password) < 8) {
            Response::json(false, 'Password must be at least 8 characters.');
        }
        if ($this->model->usernameExists($username)) {
            Response::json(false, 'Username already exists.');
        }

        $id = $this->model->create($username, $fullName, $role, $password);
        Response::json(true, 'User created.', ['id' => $id]);
    }

    private function update(): void
    {
        $p = Response::readJsonInput();

        $id       = (int) ($p['id'] ?? 0);
        $username = trim((string) ($p['username'] ?? ''));
        $fullName = trim((string) ($p['full_name'] ?? ''));
        $role     = trim((string) ($p['role'] ?? ''));
        $password = (string) ($p['password'] ?? '');

        if ($id <= 0 || $username === '' || $fullName === '' || $role === '') {
            Response::json(false, 'Missing required fields.');
        }
        if (!in_array($role, ['Administrator', 'Staff / Officer'], true)) {
            Response::json(false, 'Invalid role.');
        }
        if ($id === Auth::user()['id'] && $role !== 'Administrator') {
            Response::json(false, 'You cannot change your own role.');
        }
        if ($this->model->usernameExists($username, $id)) {
            Response::json(false, 'Username already exists.');
        }
        if ($password !== '' && strlen($password) < 8) {
            Response::json(false, 'Password must be at least 8 characters.');
        }

        $this->model->update($id, $username, $fullName, $role, $password);
        Response::json(true, 'User updated.');
    }

    private function toggle(): void
    {
        $p  = Response::readJsonInput();
        $id = (int) ($p['id'] ?? 0);

        if ($id <= 0) Response::json(false, 'Invalid user.');
        if ($id === Auth::user()['id']) Response::json(false, 'You cannot deactivate yourself.');

        $this->model->toggle($id);
        Response::json(true, 'Status updated.');
    }

    private function delete(): void
    {
        $p  = Response::readJsonInput();
        $id = (int) ($p['id'] ?? 0);

        if ($id <= 0) Response::json(false, 'Invalid user.');
        if ($id === Auth::user()['id']) Response::json(false, 'You cannot delete yourself.');

        $this->model->delete($id);
        Response::json(true, 'User deleted.');
    }
}