<?php
/**
 * Application bootstrap: environment, error handling, database, session.
 * No credentials are hard-coded here. Configure .env (see .env.example).
 */
declare(strict_types=1);

require_once __DIR__ . '/env.php';

define('APP_ROOT', dirname(__DIR__));
define('APP_ENV', bj_env('APP_ENV', 'production'));
define('APP_DEBUG', filter_var(bj_env('APP_DEBUG', '0'), FILTER_VALIDATE_BOOL));

date_default_timezone_set('Asia/Jakarta');

if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    $logDir = APP_ROOT . '/storage/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0775, true);
    }
    ini_set('error_log', $logDir . '/php-error.log');
}

define('DB_HOST', bj_env('DB_HOST', '127.0.0.1'));
define('DB_PORT', bj_env('DB_PORT', '3306'));
define('DB_NAME', bj_env('DB_NAME', 'beneranjualan'));
define('DB_USER', bj_env('DB_USER', 'beneranjualan_app'));
define('DB_PASS', bj_env('DB_PASS', ''));

define('SITE_NAME', 'Beneranjualan.id');
define('CURRENCY', 'Rp');

/**
 * Resolve the public base URL without hard-coding localhost.
 * APP_URL takes precedence; otherwise derive from the current request
 * relative to the document root.
 */
function bj_detect_base_url(): string
{
    $configured = bj_env('APP_URL');
    if ($configured) {
        return rtrim($configured, '/');
    }

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    $appRoot = realpath(APP_ROOT);
    $base = '';

    if ($docRoot && $appRoot && str_starts_with(str_replace('\\', '/', $appRoot), str_replace('\\', '/', $docRoot))) {
        $base = str_replace('\\', '/', substr($appRoot, strlen($docRoot)));
        $base = '/' . trim($base, '/');
        if ($base === '/') {
            $base = '';
        }
    }

    return $scheme . '://' . $host . $base;
}

define('SITE_URL', bj_detect_base_url());

// ---- Database (PDO) -------------------------------------------------------
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ]
    );
} catch (PDOException $e) {
    error_log('DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    if (APP_DEBUG) {
        exit('Koneksi database gagal. Jalankan <code>php scripts/setup_db.php</code> lalu periksa .env. Detail: ' . htmlspecialchars($e->getMessage()));
    }
    exit('Layanan sedang tidak tersedia. Silakan coba beberapa saat lagi.');
}

// ---- Session --------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    $secure = filter_var(bj_env('SESSION_COOKIE_SECURE', '0'), FILTER_VALIDATE_BOOL)
        || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

    session_name('BJ_SESSION');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}

// Basic hardening headers (CSP intentionally omitted: pages use small inline
// styles/scripts for progressive enhancement; configure CSP at the web server).
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: SAMEORIGIN');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

require_once __DIR__ . '/functions.php';
