<?php
/**
 * CLI bootstrap for the first administrator. Refuses web access.
 *
 * Usage:
 *   php scripts/create_admin.php
 *   set ADMIN_PASSWORD=... && php scripts/create_admin.php --username=owner --email=owner@example.com --name="Nama Pemilik"
 *   php scripts/create_admin.php --generate-password
 *
 * Passwords are hashed with password_hash(). No default password exists.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only.\n");
}

require_once dirname(__DIR__) . '/bouquet/includes/env.php';

$options = [];
foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--([a-z-]+)(?:=(.*))?$/', $arg, $m)) {
        $options[$m[1]] = $m[2] ?? '1';
    }
}

function prompt(string $label, string $default = ''): string
{
    $suffix = $default !== '' ? " [{$default}]" : '';
    fwrite(STDOUT, $label . $suffix . ': ');
    $value = fgets(STDIN);
    if ($value === false) {
        return $default;
    }
    $value = trim($value);
    return $value === '' ? $default : $value;
}

$username = $options['username'] ?? prompt('Username admin');
$email = $options['email'] ?? prompt('Email admin');
$name = $options['name'] ?? prompt('Nama lengkap');
$password = bj_env('ADMIN_PASSWORD', '') ?? '';

if ($password === '') {
    if (isset($options['generate-password'])) {
        $password = bin2hex(random_bytes(12));
        fwrite(STDOUT, "\nPassword sementara (simpan sekarang, tidak ditampilkan lagi):\n{$password}\n\n");
    } else {
        $password = prompt('Password (minimal 10 karakter, akan terlihat di terminal)');
        $confirm = prompt('Ulangi password');
        if ($password !== $confirm) {
            fwrite(STDERR, "Password tidak cocok.\n");
            exit(1);
        }
    }
}

$username = trim($username);
$email = trim($email);
$name = trim($name);

if ($username === '' || !preg_match('/^[a-zA-Z0-9._-]{3,60}$/', $username)) {
    fwrite(STDERR, "Username tidak valid (3-60 karakter: huruf, angka, titik, garis bawah, strip).\n");
    exit(1);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Email tidak valid.\n");
    exit(1);
}
if ($name === '') {
    fwrite(STDERR, "Nama lengkap wajib diisi.\n");
    exit(1);
}
if (strlen($password) < 10) {
    fwrite(STDERR, "Password minimal 10 karakter.\n");
    exit(1);
}

try {
    $pdo = new PDO(
        'mysql:host=' . bj_env('DB_HOST', '127.0.0.1') . ';port=' . bj_env('DB_PORT', '3306')
        . ';dbname=' . bj_env('DB_NAME', 'beneranjualan') . ';charset=utf8mb4',
        bj_env('DB_USER', 'beneranjualan_app'),
        bj_env('DB_PASS', ''),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        fwrite(STDERR, "Username atau email sudah dipakai. Gunakan kredensial lain.\n");
        exit(1);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare(
        "INSERT INTO users (nama_lengkap, username, email, password, role, is_active)
         VALUES (?, ?, ?, ?, 'admin', 1)"
    );
    $stmt->execute([$name, $username, $email, $hash]);
    $id = (int) $pdo->lastInsertId();

    $log = $pdo->prepare(
        'INSERT INTO admin_audit_logs (user_id, action, entity_type, entity_id, details, ip_address, created_at)
         VALUES (NULL, ?, ?, ?, ?, ?, NOW())'
    );
    $log->execute([
        'create_admin_cli',
        'user',
        $id,
        json_encode(['username' => $username], JSON_UNESCAPED_UNICODE),
        'cli',
    ]);

    fwrite(STDOUT, "Admin dibuat: #{$id} {$username} <{$email}>\n");
    fwrite(STDOUT, "Masuk di " . rtrim(bj_env('APP_URL', 'http://localhost'), '/') . "/login.php\n");
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "Gagal membuat admin: " . $e->getMessage() . "\n");
    exit(1);
}
