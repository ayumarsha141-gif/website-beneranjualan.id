<?php
require_once __DIR__ . '/includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('register.php');
}
requireCsrf();

$nama = clean_text($_POST['nama_lengkap'] ?? '', 120);
$username = clean_text($_POST['username'] ?? '', 60);
$email = mb_strtolower(clean_text($_POST['email'] ?? '', 190));
$password = (string) ($_POST['password'] ?? '');
$confirm = (string) ($_POST['password_confirm'] ?? '');
$agree = !empty($_POST['agree']);

$errors = [];
if (mb_strlen($nama) < 2) {
    $errors[] = 'Nama lengkap wajib diisi.';
}
if (!preg_match('/^[A-Za-z0-9._-]{3,60}$/', $username)) {
    $errors[] = 'Username tidak valid.';
}
if (!valid_email($email)) {
    $errors[] = 'Format email tidak valid.';
}
if (strlen($password) < 8) {
    $errors[] = 'Password minimal 8 karakter.';
}
if ($password !== $confirm) {
    $errors[] = 'Konfirmasi password tidak cocok.';
}
if (!$agree) {
    $errors[] = 'Setujui kebijakan toko untuk melanjutkan.';
}
if ($errors) {
    flash(implode(' ', $errors), 'error');
    redirect('register.php');
}

$stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
$stmt->execute([$username, $email]);
if ($stmt->fetch()) {
    flash('Username atau email sudah terdaftar.', 'error');
    redirect('register.php');
}

$stmt = $pdo->prepare(
    "INSERT INTO users (nama_lengkap, username, email, password, role, is_active, created_at, updated_at)
     VALUES (?, ?, ?, ?, 'customer', 1, NOW(), NOW())"
);
$stmt->execute([$nama, $username, $email, hashPassword($password)]);

redirect('login.php', 'Akun berhasil dibuat. Silakan masuk.', 'success');
