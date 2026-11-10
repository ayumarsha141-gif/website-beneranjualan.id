<?php
/**
 * Lightweight, read-only regression checks for shared business rules.
 * Run with: php tests/run.php
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only.\n");
}

require_once dirname(__DIR__) . '/bouquet/includes/config.php';

$checks = 0;
$failures = [];
$assert = static function (bool $condition, string $label) use (&$checks, &$failures): void {
    $checks++;
    if (!$condition) {
        $failures[] = $label;
    }
};

$assert(slugify('Ulang Tahun Bahagia!') === 'ulang-tahun-bahagia', 'slug generation');
$assert(clean_text("Nama\0\nBuket", 20) === "Nama\nBuket", 'control-character cleaning');
$assert(valid_email('shopper@example.test'), 'valid email accepted');
$assert(!valid_email('invalid-email'), 'invalid email rejected');
$assert(valid_phone('+62 812-3456-7890'), 'valid phone accepted');
$assert(!valid_phone('123'), 'short phone rejected');

$assert(order_transition_allowed('baru', 'diproses'), 'new order can be processed');
$assert(order_transition_allowed('diproses', 'dibatalkan'), 'unpaid in-process order can be cancelled');
$assert(!order_transition_allowed('selesai', 'dibatalkan'), 'completed order cannot be cancelled');
$assert(payment_transition_allowed('menunggu_pembayaran', 'dibayar'), 'pending payment can be verified');
$assert(payment_transition_allowed('dibayar', 'refund'), 'verified payment can be refunded');
$assert(!payment_transition_allowed('dibatalkan', 'dibayar'), 'cancelled payment cannot be marked paid');

$page = paginate(25, 12, 2);
$assert($page['page'] === 2 && $page['pages'] === 3 && $page['offset'] === 12, 'pagination calculation');
$assert(badge_class('menunggu_pembayaran') === 'badge badge-menunggu_pembayaran', 'status badge class');
$assert(str_contains(product_image_url('Valencia.png'), '/assets/images/Valencia.png'), 'original image remains a compatibility fallback');
$assert((bool) preg_match('#/assets/images/Valencia\.png\?v=\d+$#', product_image_url('Valencia.png')), 'product image URL is cache-busted');
$assert(product_image_srcset('Valencia.png') === '' || str_contains(product_image_srcset('Valencia.png'), '480w'), 'responsive image srcset');
$assert(str_ends_with(product_image_url('../secret.php'), '/assets/images/placeholder.svg'), 'unsafe image name falls back to placeholder');

$csrf = csrf_token();
$assert(strlen($csrf) === 64 && validateCsrf($csrf), 'CSRF token is generated and validates');
$assert(!validateCsrf(str_repeat('0', 64)), 'invalid CSRF token is rejected');

$requiredTables = [
    'users', 'categories', 'produk', 'product_images', 'pesanan', 'detail_pesanan',
    'order_status_history', 'stock_movements', 'store_settings', 'admin_audit_logs',
    'login_attempts', 'schema_migrations',
];
$tableStmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
foreach ($requiredTables as $table) {
    $tableStmt->execute([$table]);
    $assert((int) $tableStmt->fetchColumn() === 1, 'required database table: ' . $table);
}

if ($failures !== []) {
    fwrite(STDERR, "FAIL ({$checks} checks):\n - " . implode("\n - ", $failures) . "\n");
    exit(1);
}

fwrite(STDOUT, "PASS: {$checks} read-only checks.\n");
