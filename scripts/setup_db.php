<?php
/**
 * CLI database installer. Web execution is refused.
 *
 * Usage:
 *   php scripts/setup_db.php
 *   php scripts/setup_db.php --seed-demo
 *
 * Reads .env (DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS). The database must
 * exist, or DB_USER must have permission to create it. For a fresh local
 * install with root, see database/README.md.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This installer is CLI-only.\n");
}

require_once dirname(__DIR__) . '/bouquet/includes/env.php';

$seedDemo = in_array('--seed-demo', $argv, true);

$host = bj_env('DB_HOST', '127.0.0.1');
$port = bj_env('DB_PORT', '3306');
$name = bj_env('DB_NAME', 'beneranjualan');
$user = bj_env('DB_USER', 'beneranjualan_app');
$pass = bj_env('DB_PASS', '');

echo "Beneranjualan.id — database setup\n";
echo "Server : {$host}:{$port}\n";
echo "Database: {$name}\n";
echo "User   : {$user}\n\n";

function connect_mysql(string $host, string $port, string $db, string $user, string $pass): PDO
{
    $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
    if ($db !== '') {
        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
    }
    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function split_sql(string $sql): array
{
    $statements = [];
    $buffer = '';
    foreach (preg_split('/\R/', $sql) ?: [] as $line) {
        $trim = trim($line);
        if ($trim === '' || str_starts_with($trim, '--') || str_starts_with($trim, '#')) {
            continue;
        }
        $buffer .= $line . "\n";
        if (str_ends_with($trim, ';')) {
            $statement = trim($buffer);
            if ($statement !== '') {
                $statements[] = $statement;
            }
            $buffer = '';
        }
    }
    if (trim($buffer) !== '') {
        $statements[] = trim($buffer);
    }
    return $statements;
}

function run_sql_file(PDO $pdo, string $path): void
{
    if (!is_file($path)) {
        throw new RuntimeException('SQL file not found: ' . $path);
    }
    foreach (split_sql((string) file_get_contents($path)) as $statement) {
        $pdo->exec($statement);
    }
}

try {
    // Try to connect to the database. If it does not exist, create it.
    try {
        $pdo = connect_mysql($host, $port, $name, $user, $pass);
    } catch (PDOException $e) {
        if ((int) $e->getCode() !== 1049) {
            throw $e;
        }
        echo "Database belum ada — mencoba membuat '{$name}'...\n";
        $server = connect_mysql($host, $port, '', $user, $pass);
        $server->exec("CREATE DATABASE `" . str_replace('`', '', $name) . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo = connect_mysql($host, $port, $name, $user, $pass);
        echo "Database dibuat.\n";
    }

    echo "Koneksi berhasil.\n\n";

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS schema_migrations (
            migration VARCHAR(190) NOT NULL PRIMARY KEY,
            applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $applied = $pdo->query('SELECT migration FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
    $migrationFiles = glob(dirname(__DIR__) . '/database/migrations/*.sql') ?: [];
    sort($migrationFiles);

    $ran = 0;
    foreach ($migrationFiles as $file) {
        $key = basename($file);
        if (in_array($key, $applied, true)) {
            echo "SKIP  {$key} (sudah diterapkan)\n";
            continue;
        }
        echo "APPLY {$key}\n";
        // MySQL DDL causes implicit commits, so migrations are not wrapped in a
        // transaction. Statements use IF NOT EXISTS where possible and the
        // migration is recorded only after the whole file succeeds.
        run_sql_file($pdo, $file);
        $stmt = $pdo->prepare('INSERT INTO schema_migrations (migration) VALUES (?)');
        $stmt->execute([$key]);
        $ran++;
    }

    if ($seedDemo) {
        echo "SEED  database/seed_demo.sql (katalog demo)\n";
        run_sql_file($pdo, dirname(__DIR__) . '/database/seed_demo.sql');
        echo "Katalog demo dimuat. Tandai dan ganti sebelum produksi.\n";
    }

    $tables = ['users', 'categories', 'produk', 'pesanan', 'detail_pesanan', 'store_settings', 'stock_movements', 'order_status_history'];
    echo "\nVerifikasi tabel:\n";
    foreach ($tables as $table) {
        $count = (int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
        echo "  - {$table}: {$count} baris\n";
    }

    echo "\nSelesai. {$ran} migration baru diterapkan.\n";
    echo "Buat admin lewat: php scripts/create_admin.php\n";
    if ($seedDemo) {
        echo "PERINGATAN: produk demo aktif. Arsipkan/ganti sebelum toko online.\n";
    }
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "\nGAGAL: " . $e->getMessage() . "\n");
    fwrite(STDERR, "Periksa .env dan pastikan MySQL berjalan.\n");
    exit(1);
}
