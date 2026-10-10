<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Response;
use App\Services\Auth;
use mysqli;

class ProfileController
{
    public function __construct(private mysqli $db) {}

    public function handle(string $action): void
    {
        if (!Auth::isLoggedIn()) {
            Response::json(false, 'Access denied.');
        }

        switch ($action) {
            case 'get':    $this->get();    break;
            case 'update': $this->update(); break;
            default:       Response::json(false, 'Unsupported request.');
        }
    }

    /* ============================================================
       GET — current user's profile
       ============================================================ */

    private function get(): void
    {
        $userId = (int) Auth::user()['id'];

        try {
            $stmt = $this->db->prepare(
                'SELECT u.id, u.username, u.jail_unit_id,
                        u.first_name, u.middle_name, u.last_name, u.full_name,
                        u.email, u.phone, u.birthdate, u.sex, u.civil_status,
                        u.employee_no, u.`position`, u.`rank`, u.department, u.date_hired, u.employment_status,
                        u.bjmp_rank, u.salary_grade, u.personnel_type, u.eligibility,
                        u.role, u.is_active, u.created_at, u.updated_at,
                        j.name AS jail_unit_name
                 FROM users u
                 LEFT JOIN jail_units j ON j.id = u.jail_unit_id
                 WHERE u.id = ? LIMIT 1'
            );
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$row) Response::json(false, 'Profile not found.');

            $row['id']           = (int) $row['id'];
            $row['jail_unit_id'] = $row['jail_unit_id'] !== null ? (int) $row['jail_unit_id'] : null;
            $row['is_active']    = (bool) $row['is_active'];
            $row['salary_grade'] = $row['salary_grade'] !== null ? (int) $row['salary_grade'] : null;

            Response::json(true, 'OK', ['profile' => $row]);
        } catch (\Throwable $e) {
            error_log('Profile get: ' . $e->getMessage());
            Response::json(false, 'Unable to load profile.');
        }
    }

    /* ============================================================
       UPDATE — update own profile
       ============================================================ */

    private function update(): void
    {
        $userId = (int) Auth::user()['id'];
        $p      = Response::readJsonInput();

        $firstName   = trim((string) ($p['first_name'] ?? ''));
        $middleName  = trim((string) ($p['middle_name'] ?? ''));
        $lastName    = trim((string) ($p['last_name'] ?? ''));
        $dob         = trim((string) ($p['birthdate'] ?? '')) ?: null;
        $sex         = trim((string) ($p['sex'] ?? '')) ?: null;
        $civilStatus = trim((string) ($p['civil_status'] ?? '')) ?: null;
        $email       = trim((string) ($p['email'] ?? '')) ?: null;
        $phone       = trim((string) ($p['phone'] ?? '')) ?: null;

        if ($firstName === '' || $lastName === '') {
            Response::json(false, 'First name and last name are required.');
        }

        if ($sex !== null && !in_array($sex, ['Male', 'Female'], true)) {
            $sex = null;
        }
        if ($civilStatus !== null && !in_array($civilStatus, ['Single', 'Married', 'Widowed', 'Separated'], true)) {
            $civilStatus = null;
        }

        $fullName = trim(implode(' ', array_filter([$firstName, $middleName, $lastName])));

        // Password change (optional)
        $currentPassword = (string) ($p['current_password'] ?? '');
        $newPassword     = (string) ($p['new_password'] ?? '');
        $confirmPassword = (string) ($p['confirm_password'] ?? '');

        $passwordToSave = null;

        if ($newPassword !== '') {
            if (strlen($newPassword) < 8) {
                Response::json(false, 'New password must be at least 8 characters.');
            }
            if ($newPassword !== $confirmPassword) {
                Response::json(false, 'New password and confirmation do not match.');
            }
            if ($currentPassword === '') {
                Response::json(false, 'Please enter your current password to change your password.');
            }

            // Verify current password
            $stmt = $this->db->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$row || !password_verify($currentPassword, $row['password'])) {
                Response::json(false, 'Current password is incorrect.');
            }

            $passwordToSave = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        try {
            if ($passwordToSave !== null) {
                $stmt = $this->db->prepare(
                    'UPDATE users SET
                        first_name = ?, middle_name = ?, last_name = ?, full_name = ?,
                        birthdate = ?, sex = ?, civil_status = ?,
                        email = ?, phone = ?,
                        password = ?
                     WHERE id = ?'
                );
                $stmt->bind_param(
                    'ssssssssssi',
                    $firstName,
                    $middleName,
                    $lastName,
                    $fullName,
                    $dob,
                    $sex,
                    $civilStatus,
                    $email,
                    $phone,
                    $passwordToSave,
                    $userId
                );
            } else {
                $stmt = $this->db->prepare(
                    'UPDATE users SET
                        first_name = ?, middle_name = ?, last_name = ?, full_name = ?,
                        birthdate = ?, sex = ?, civil_status = ?,
                        email = ?, phone = ?
                     WHERE id = ?'
                );
                $stmt->bind_param(
                    'sssssssssi',
                    $firstName,
                    $middleName,
                    $lastName,
                    $fullName,
                    $dob,
                    $sex,
                    $civilStatus,
                    $email,
                    $phone,
                    $userId
                );
            }

            $stmt->execute();
            $stmt->close();

            // Refresh the session's full_name so the sidebar reflects the new name
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['full_name'] = $fullName;

            Response::json(true, 'Profile updated.');
        } catch (\Throwable $e) {
            error_log('Profile update: ' . $e->getMessage());
            Response::json(false, 'Unable to update profile: ' . $e->getMessage());
        }
    }
}