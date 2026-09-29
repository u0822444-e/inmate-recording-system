<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

$role     = $_POST['role'] ?? '';
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (!in_array($role, ['Administrator', 'Staff / Officer'], true)) {
    exit(json_encode(['success' => false, 'message' => 'Invalid role.']));
}

if ($username === '' || $password === '') {
    exit(json_encode(['success' => false, 'message' => 'Please enter both username and password.']));
}

$stmt = $conn->prepare(
    'SELECT id, username, password FROM users WHERE username = ? AND role = ? LIMIT 1'
);
$stmt->bind_param('ss', $username, $role);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    exit(json_encode(['success' => false, 'message' => 'Account not found for this role.']));
}

// Use password_verify() in production
if ($password !== $user['password']) {
    exit(json_encode(['success' => false, 'message' => 'Incorrect password.']));
}

$_SESSION['user_id']  = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role']     = $role;

echo json_encode([
    'success' => true,
    'message' => 'Authenticated. Launching portal…',
    'user'    => [
        'username' => $user['username'],
        'role'     => $role,
    ],
]);