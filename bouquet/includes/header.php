<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$store_name = setting($pdo, 'store_name', SITE_NAME);
$page_title = $page_title ?? 'Buket Bunga';
$meta_description = $meta_description ?? setting($pdo, 'tagline', 'Buket bunga untuk momen yang berarti.');
$current_page = $current_page ?? '';
if (!isset($meta_robots) && in_array($current_page, ['cart', 'checkout', 'login', 'register', 'profile', 'orders', 'track'], true)) {
    $meta_robots = 'noindex,nofollow';
}
$canonical_url = $canonical_url ?? ($current_page === 'home' ? site_url() : site_url(basename((string) (parse_url($_SERVER['SCRIPT_NAME'] ?? '', PHP_URL_PATH) ?: 'index.php'))));
$og_title = $og_title ?? ($page_title . ' — ' . $store_name);
$og_type = $og_type ?? 'website';
$cart_count = cart_count();
$nav_categories = [];
try {
    $nav_categories = $pdo->query('SELECT c.nama, c.slug FROM categories c WHERE c.is_active = 1 AND EXISTS (SELECT 1 FROM produk p WHERE p.category_id = c.id AND p.is_active = 1 AND p.is_demo = 0) ORDER BY c.sort_order, c.nama LIMIT 6')->fetchAll();
} catch (Throwable $e) {
    $nav_categories = [];
}
$promo_active = setting($pdo, 'promo_active', '0') === '1';
$promo_banner = trim(setting($pdo, 'promo_banner'));
$account = current_user();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#315846">
    <meta name="description" content="<?= e($meta_description) ?>">
    <?php if (!empty($meta_robots)): ?><meta name="robots" content="<?= e($meta_robots) ?>"><?php endif; ?>
    <link rel="canonical" href="<?= e($canonical_url) ?>">
    <link rel="sitemap" type="application/xml" href="<?= e(site_url('sitemap.php')) ?>">
    <meta property="og:type" content="<?= e($og_type) ?>">
    <meta property="og:site_name" content="<?= e($store_name) ?>">
    <meta property="og:title" content="<?= e($og_title) ?>">
    <meta property="og:description" content="<?= e($meta_description) ?>">
    <meta property="og:url" content="<?= e($canonical_url) ?>">
    <?php if (!empty($og_image)): ?><meta property="og:image" content="<?= e($og_image) ?>"><meta name="twitter:card" content="summary_large_image"><?php else: ?><meta name="twitter:card" content="summary">
    <?php endif; ?>
    <?= csrf_meta() ?>
    <title><?= e($page_title) ?> — <?= e($store_name) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_url('styles.css')) ?>?v=20261108-pagination">
    <?php if ($current_page === 'home'): ?><link rel="stylesheet" href="<?= e(asset_url('florist-world.css')) ?>?v=20261108-anchor"><?php endif; ?>
    <link rel="icon" href="<?= e(asset_url('images/favicon.svg')) ?>" type="image/svg+xml">
</head>
<body class="<?= e($body_class ?? '') ?>">
<a class="skip-link" href="#main">Lewati ke konten utama</a>
<?php include __DIR__ . '/icons.php'; ?>

<?php if ($promo_active && $promo_banner !== ''): ?>
<div class="announcement" role="region" aria-label="Pengumuman toko">
    <div class="container announcement-inner">
        <span><?= e($promo_banner) ?></span>
    </div>
</div>
<?php endif; ?>

<header class="site-header" id="siteHeader">
    <div class="container header-inner">
        <button class="icon-btn menu-toggle" type="button" id="menuToggle" aria-expanded="false" aria-controls="mobileDrawer" aria-label="Buka menu">
            <svg class="icon" aria-hidden="true"><use href="#i-menu"></use></svg>
        </button>

        <a class="wordmark" href="<?= e(site_url()) ?>" aria-label="<?= e($store_name) ?> — beranda">
            <span class="wordmark-flower" aria-hidden="true">
                <svg viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.4">
                    <path d="M16 29V15"/>
                    <path d="M16 21c-5-1-7.5-4.5-7.5-8.5 4.4 0 7 3 7.5 8.5Z"/>
                    <path d="M16 24c5-1 7.5-4.5 7.5-8.5-4.4 0-7 3-7.5 8.5Z"/>
                    <circle cx="16" cy="10" r="3.4"/>
                    <circle cx="11.5" cy="12.5" r="2.7"/>
                    <circle cx="20.5" cy="12.5" r="2.7"/>
                </svg>
            </span>
            <span class="wordmark-text"><?= e($store_name) ?></span>
        </a>

        <nav class="primary-nav" aria-label="Navigasi utama">
            <a href="<?= e(site_url('katalog.php')) ?>" class="<?= $current_page === 'katalog' ? 'is-active' : '' ?>">Katalog</a>
            <div class="nav-moments">
                <button type="button" class="nav-drop-toggle" aria-expanded="false">
                    Momen <svg class="icon icon-xs" aria-hidden="true"><use href="#i-chevron-down"></use></svg>
                </button>
                <div class="nav-drop">
                    <a href="<?= e(site_url('katalog.php')) ?>">Semua koleksi</a>
                    <?php foreach ($nav_categories as $cat): ?>
                        <a href="<?= e(site_url('katalog.php?kategori=' . urlencode($cat['slug']))) ?>"><?= e($cat['nama']) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <a href="<?= e($current_page === 'home' ? '#cara-pesan' : site_url('index.php#cara-pesan')) ?>">Cara Pesan</a>
            <a href="<?= e($current_page === 'home' ? '#kontak' : site_url('index.php#kontak')) ?>">Kontak</a>
        </nav>

        <div class="header-actions">
            <form class="header-search" role="search" action="<?= e(site_url('katalog.php')) ?>" method="get">
                <label class="visually-hidden" for="headerSearch">Cari buket</label>
                <input id="headerSearch" type="search" name="q" placeholder="Cari buket…" value="<?= e($_GET['q'] ?? '') ?>" autocomplete="off">
                <button type="submit" aria-label="Cari">
                    <svg class="icon" aria-hidden="true"><use href="#i-search"></use></svg>
                </button>
            </form>

            <?php if ($account): ?>
            <details class="account-menu">
                <summary>
                    <svg class="icon" aria-hidden="true"><use href="#i-user"></use></svg>
                    <span class="account-name"><?= e($account['nama_lengkap']) ?></span>
                    <svg class="icon icon-xs" aria-hidden="true"><use href="#i-chevron-down"></use></svg>
                </summary>
                <div class="account-drop">
                    <?php if (isAdmin()): ?>
                        <a href="<?= e(site_url('admin/dashboard.php')) ?>">Panel admin</a>
                    <?php endif; ?>
                    <a href="<?= e(site_url('profil.php')) ?>">Profil saya</a>
                    <a href="<?= e(site_url('pesanan-saya.php')) ?>">Pesanan saya</a>
                    <form action="<?= e(site_url('logout.php')) ?>" method="post">
                        <?= csrfField() ?>
                        <button type="submit">Keluar</button>
                    </form>
                </div>
            </details>
            <?php else: ?>
            <a class="login-link" href="<?= e(site_url('login.php')) ?>">
                <svg class="icon" aria-hidden="true"><use href="#i-user"></use></svg>
                <span>Masuk</span>
            </a>
            <?php endif; ?>

            <a class="cart-link" href="<?= e(site_url('cart.php')) ?>" aria-label="Keranjang belanja">
                <svg class="icon" aria-hidden="true"><use href="#i-cart"></use></svg>
                <span class="cart-label">Keranjang</span>
                <span class="cart-badge<?= $cart_count > 0 ? '' : ' is-empty' ?>" id="cartBadge"><?= (int) $cart_count ?></span>
            </a>
        </div>
    </div>
</header>

<div class="mobile-drawer" id="mobileDrawer" aria-hidden="true">
    <div class="drawer-backdrop" data-close-menu></div>
    <div class="drawer-panel" role="dialog" aria-modal="true" aria-label="Menu navigasi">
        <div class="drawer-head">
            <span class="wordmark-text"><?= e($store_name) ?></span>
            <button class="icon-btn" type="button" data-close-menu aria-label="Tutup menu">
                <svg class="icon" aria-hidden="true"><use href="#i-close"></use></svg>
            </button>
        </div>
        <form class="drawer-search" role="search" action="<?= e(site_url('katalog.php')) ?>" method="get">
            <label class="visually-hidden" for="drawerSearch">Cari buket</label>
            <input id="drawerSearch" type="search" name="q" placeholder="Cari buket…" value="<?= e($_GET['q'] ?? '') ?>">
            <button type="submit" aria-label="Cari"><svg class="icon" aria-hidden="true"><use href="#i-search"></use></svg></button>
        </form>
        <nav class="drawer-nav" aria-label="Navigasi seluler">
            <a href="<?= e(site_url('katalog.php')) ?>">Katalog</a>
            <?php foreach ($nav_categories as $cat): ?>
                <a class="drawer-sub" href="<?= e(site_url('katalog.php?kategori=' . urlencode($cat['slug']))) ?>"><?= e($cat['nama']) ?></a>
            <?php endforeach; ?>
            <a href="<?= e($current_page === 'home' ? '#cara-pesan' : site_url('index.php#cara-pesan')) ?>">Cara Pesan</a>
            <a href="<?= e($current_page === 'home' ? '#kontak' : site_url('index.php#kontak')) ?>">Kontak</a>
            <a href="<?= e(site_url('lacak-pesanan.php')) ?>">Lacak Pesanan</a>
        </nav>
        <div class="drawer-account">
            <?php if ($account): ?>
                <p>Halo, <?= e($account['nama_lengkap']) ?></p>
                <a class="btn btn-outline btn-block" href="<?= e(site_url('pesanan-saya.php')) ?>">Pesanan saya</a>
                <form action="<?= e(site_url('logout.php')) ?>" method="post">
                    <?= csrfField() ?>
                    <button class="btn btn-ghost btn-block" type="submit">Keluar</button>
                </form>
            <?php else: ?>
                <a class="btn btn-primary btn-block" href="<?= e(site_url('login.php')) ?>">Masuk</a>
                <a class="btn btn-ghost btn-block" href="<?= e(site_url('register.php')) ?>">Buat akun</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<main id="main">
