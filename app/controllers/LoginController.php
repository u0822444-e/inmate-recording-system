<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Response;
use App\Models\User;
use App\Services\Auth;
use mysqli;

class LoginController
{
    public function __construct(private mysqli $db) {}

    public function handle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::json(false, 'Method not allowed.');
        }

        $role     = trim((string) ($_POST['role'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if (!in_array($role, ['Administrator', 'Staff / Officer'], true)) {
            Response::json(false, 'Invalid account type.');
        }
        if ($username === '' || $password === '') {
            Response::json(false, 'Please enter username and password.');
        }
        if (strlen($username) > 100) {
            Response::json(false, 'Invalid username or password.');
        }

        $model = new User($this->db);
        $user  = $model->findByCredentials($username, $role);

        if (!$user || !password_verify($password, $user['password'])) {
            Response::json(false, 'Invalid username or password.');
        }

        Auth::login($user);

        Response::json(true, 'Authenticated.', [
            'user' => [
                'id'        => (int) $user['id'],
                'username'  => $user['username'],
                'role'      => $user['role'],
                'full_name' => $user['full_name'] ?? '',
            ],
        ]);
    }
}