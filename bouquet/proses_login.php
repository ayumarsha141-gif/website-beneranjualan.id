<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('login.php');
}

if (!validateCsrf($_POST['csrf_token'] ?? '')) {
    redirect('login.php', 'Token tidak valid!', 'error');
}

$username = sanitize($_POST['username']);
$password = $_POST['password'];

if (empty($username) || empty($password)) {
    redirect('login.php', 'Username dan password wajib diisi!', 'error');
}

// Check user by username or email
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
$stmt->execute([$username, $username]);
$user = $stmt->fetch();

if (!$user || !verifyPassword($password, $user['password'])) {
    redirect('login.php', 'Username atau password salah!', 'error');
}

// Set session
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['email'] = $user['email'];
$_SESSION['nama_lengkap'] = $user['nama_lengkap'];
$_SESSION['role'] = $user['role'];

// Redirect based on role
if ($user['role'] === 'admin') {
    redirect('admin/dashboard.php', 'Selamat datang, ' . $user['nama_lengkap'] . '!', 'success');
} else {
    redirect('index.php', 'Login berhasil!', 'success');
}
