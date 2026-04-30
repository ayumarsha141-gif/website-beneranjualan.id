<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('register.php');
}

if (!validateCsrf($_POST['csrf_token'] ?? '')) {
    redirect('register.php', 'Token tidak valid!', 'error');
}

$nama_lengkap = sanitize($_POST['nama_lengkap']);
$username = sanitize($_POST['username']);
$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
$password = $_POST['password'];
$password_confirm = $_POST['password_confirm'];

// Validations
if (empty($nama_lengkap) || empty($username) || empty($email) || empty($password)) {
    redirect('register.php', 'Semua field wajib diisi!', 'error');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect('register.php', 'Format email tidak valid!', 'error');
}

if (strlen($password) < 6) {
    redirect('register.php', 'Password minimal 6 karakter!', 'error');
}

if ($password !== $password_confirm) {
    redirect('register.php', 'Konfirmasi password tidak cocok!', 'error');
}

// Check existing user
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
$stmt->execute([$username, $email]);
if ($stmt->fetch()) {
    redirect('register.php', 'Username atau email sudah terdaftar!', 'error');
}

// Create user
$hashed_password = hashPassword($password);
$stmt = $pdo->prepare("INSERT INTO users (nama_lengkap, username, email, password) VALUES (?, ?, ?, ?)");
$stmt->execute([$nama_lengkap, $username, $email, $hashed_password]);

redirect('login.php', 'Registrasi berhasil! Silakan login.', 'success');
