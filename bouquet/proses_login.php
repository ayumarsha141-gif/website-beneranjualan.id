<?php
require_once __DIR__ . '/includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('login.php');
}
requireCsrf();

$username = clean_text($_POST['username'] ?? '', 190);
$password = (string) ($_POST['password'] ?? '');
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

if ($username === '' || $password === '') {
    redirect('login.php', 'Email/username dan password wajib diisi.', 'error');
}

// Rate limit: max 8 failed attempts per IP in 15 minutes.
$stmt = $pdo->prepare(
    'SELECT COUNT(*) FROM login_attempts
     WHERE ip_address = ? AND success = 0 AND attempted_at > (NOW() - INTERVAL 15 MINUTE)'
);
$stmt->execute([$ip]);
if ((int) $stmt->fetchColumn() >= 8) {
    redirect('login.php', 'Terlalu banyak percobaan masuk. Coba lagi dalam 15 menit.', 'error');
}

$stmt = $pdo->prepare('SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1 LIMIT 1');
$stmt->execute([$username, $username]);
$user = $stmt->fetch();

$valid = $user && verifyPassword($password, (string) $user['password']);

if (!$valid) {
    $log = $pdo->prepare('INSERT INTO login_attempts (ip_address, username, success, attempted_at) VALUES (?, ?, 0, NOW())');
    $log->execute([$ip, mb_substr($username, 0, 190)]);
    redirect('login.php', 'Email/username atau password salah.', 'error');
}

// Upgrade legacy hashes if needed.
if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
    $upd = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
    $upd->execute([hashPassword($password), $user['id']]);
}

login_user($user);

$pdo->prepare('DELETE FROM login_attempts WHERE ip_address = ? AND success = 0')->execute([$ip]);
$pdo->prepare('INSERT INTO login_attempts (ip_address, username, success, attempted_at) VALUES (?, ?, 1, NOW())')
    ->execute([$ip, mb_substr($user['username'], 0, 190)]);

$intended = $_SESSION['intended'] ?? '';
unset($_SESSION['intended']);
if (is_string($intended) && str_starts_with($intended, '/') && !str_starts_with($intended, '//')) {
    redirect(ltrim($intended, '/'), 'Selamat datang kembali, ' . $user['nama_lengkap'] . '.', 'success');
}

if ($user['role'] === 'admin') {
    redirect('admin/dashboard.php', 'Selamat datang, ' . $user['nama_lengkap'] . '.', 'success');
}
redirect('index.php', 'Selamat datang kembali, ' . $user['nama_lengkap'] . '.', 'success');
