<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();

$admin_store_name = setting($pdo, 'store_name', SITE_NAME);
$admin_title = $admin_title ?? 'Dashboard';
$admin_page = $admin_page ?? 'dashboard';
$admin_user = current_user();

$admin_new_orders = 0;
$admin_pending_payments = 0;
try {
    $admin_new_orders = (int) $pdo->query("SELECT COUNT(*) FROM pesanan WHERE status = 'baru'")->fetchColumn();
    $admin_pending_payments = (int) $pdo->query("SELECT COUNT(*) FROM pesanan WHERE payment_status = 'menunggu_pembayaran' AND status <> 'dibatalkan'")->fetchColumn();
} catch (Throwable $e) {
    // Empty state is fine on a fresh database.
}

$admin_nav = [
    ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => 'dashboard.php', 'icon' => 'i-chart'],
    ['key' => 'orders', 'label' => 'Pesanan', 'href' => 'pesanan.php', 'icon' => 'i-package', 'count' => $admin_new_orders],
    ['key' => 'products', 'label' => 'Produk', 'href' => 'produk.php', 'icon' => 'i-flower'],
    ['key' => 'categories', 'label' => 'Kategori', 'href' => 'kategori.php', 'icon' => 'i-tag'],
    ['key' => 'inventory', 'label' => 'Inventaris', 'href' => 'inventaris.php', 'icon' => 'i-refresh'],
    ['key' => 'customers', 'label' => 'Pelanggan', 'href' => 'pelanggan.php', 'icon' => 'i-users'],
    ['key' => 'reports', 'label' => 'Laporan', 'href' => 'laporan.php', 'icon' => 'i-trend'],
    ['key' => 'settings', 'label' => 'Pengaturan', 'href' => 'pengaturan.php', 'icon' => 'i-settings'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <meta name="theme-color" content="#285742">
    <title><?= e($admin_title) ?> — Admin <?= e($admin_store_name) ?></title>
    <?= csrf_meta() ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_url('admin.css')) ?>?v=20261108-pagination">
</head>
<body class="admin-body">
<?php include __DIR__ . '/../../includes/icons.php'; ?>
<div class="admin-shell">
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="admin-brand">
            <a href="<?= e(site_url('admin/dashboard.php')) ?>">
                <span class="wordmark-flower" aria-hidden="true">
                    <svg viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.4">
                        <path d="M16 29V15"/>
                        <path d="M16 21c-5-1-7.5-4.5-7.5-8.5 4.4 0 7 3 7.5 8.5Z"/>
                        <path d="M16 24c5-1 7.5-4.5 7.5-8.5-4.4 0-7 3-7.5 8.5Z"/>
                        <circle cx="16" cy="10" r="3.4"/>
                    </svg>
                </span>
                <span>
                    <strong>Beneranjualan</strong>
                    <small>Admin panel</small>
                </span>
            </a>
            <button class="admin-sidebar-close icon-btn" type="button" data-admin-menu aria-label="Tutup menu">
                <svg class="icon" aria-hidden="true"><use href="#i-close"></use></svg>
            </button>
        </div>

        <nav class="admin-nav" aria-label="Navigasi admin">
            <?php foreach ($admin_nav as $item): ?>
            <a href="<?= e(site_url('admin/' . $item['href'])) ?>" class="<?= $admin_page === $item['key'] ? 'is-active' : '' ?>">
                <svg class="icon" aria-hidden="true"><use href="#<?= e($item['icon']) ?>"></use></svg>
                <span><?= e($item['label']) ?></span>
                <?php if (!empty($item['count'])): ?><span class="admin-nav-count"><?= (int) $item['count'] ?></span><?php endif; ?>
            </a>
            <?php endforeach; ?>
        </nav>

        <div class="admin-sidebar-foot">
            <?php if ($admin_pending_payments > 0): ?>
                <a class="admin-alert-link" href="<?= e(site_url('admin/pesanan.php?payment=menunggu_pembayaran')) ?>">
                    <svg class="icon" aria-hidden="true"><use href="#i-alert"></use></svg>
                    <?= $admin_pending_payments ?> pembayaran menunggu verifikasi
                </a>
            <?php endif; ?>
            <a class="admin-store-link" href="<?= e(site_url()) ?>" target="_blank" rel="noopener">
                <svg class="icon" aria-hidden="true"><use href="#i-arrow-right"></use></svg> Lihat toko
            </a>
            <form action="<?= e(site_url('logout.php')) ?>" method="post">
                <?= csrfField() ?>
                <button type="submit" class="admin-logout">Keluar</button>
            </form>
        </div>
    </aside>

    <div class="admin-overlay" data-admin-menu></div>

    <div class="admin-main">
        <header class="admin-topbar">
            <button class="icon-btn admin-menu-btn" type="button" data-admin-menu aria-label="Buka menu admin">
                <svg class="icon" aria-hidden="true"><use href="#i-menu"></use></svg>
            </button>
            <h1><?= e($admin_title) ?></h1>
            <div class="admin-topbar-actions">
                <a class="admin-topbar-link" href="<?= e(site_url()) ?>" target="_blank" rel="noopener">Toko</a>
                <span class="admin-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($admin_user['nama_lengkap'] ?? 'A', 0, 1))) ?></span>
                <span class="admin-user"><?= e($admin_user['nama_lengkap'] ?? '') ?></span>
            </div>
        </header>
        <div class="admin-content">
