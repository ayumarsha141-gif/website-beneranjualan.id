<?php
require_once __DIR__ . '/includes/config.php';

$q = clean_text($_GET['q'] ?? '', 80);
$category_slug = clean_text($_GET['kategori'] ?? '', 120);
$min_price = ($_GET['min'] ?? '') !== '' ? max(0, (float) $_GET['min']) : null;
$max_price = ($_GET['max'] ?? '') !== '' ? max(0, (float) $_GET['max']) : null;
$in_stock = ($_GET['stok'] ?? '') === '1';
$sort = $_GET['sort'] ?? 'newest';
$allowed_sorts = [
    'newest' => 'p.created_at DESC',
    'price_asc' => 'p.harga ASC',
    'price_desc' => 'p.harga DESC',
    'name' => 'p.nama ASC',
];
if (!isset($allowed_sorts[$sort])) {
    $sort = 'newest';
}

$active_category = null;
if ($category_slug !== '') {
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE slug = ? AND is_active = 1');
    $stmt->execute([$category_slug]);
    $active_category = $stmt->fetch() ?: null;
    if (!$active_category) {
        $category_slug = '';
    }
}

$where = ['p.is_active = 1', 'p.is_demo = 0'];
$params = [];
if ($q !== '') {
    $where[] = '(p.nama LIKE ? OR p.deskripsi LIKE ? OR p.kategori LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if ($category_slug !== '') {
    $where[] = '(c.slug = ? OR p.kategori = ?)';
    $params[] = $category_slug;
    $params[] = $active_category['nama'] ?? $category_slug;
}
if ($min_price !== null) {
    $where[] = 'p.harga >= ?';
    $params[] = $min_price;
}
if ($max_price !== null) {
    $where[] = 'p.harga <= ?';
    $params[] = $max_price;
}
if ($in_stock) {
    $where[] = "p.availability_mode = 'stock' AND p.stok > 0";
}

$whereSql = implode(' AND ', $where);
$countStmt = $pdo->prepare(
    "SELECT COUNT(*) FROM produk p LEFT JOIN categories c ON c.id = p.category_id WHERE $whereSql"
);
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$pagination = paginate($total, 12, max(1, (int) ($_GET['page'] ?? 1)));
$orderSql = $allowed_sorts[$sort];

$stmt = $pdo->prepare(
    "SELECT p.*, c.slug AS category_slug
     FROM produk p
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE $whereSql
     ORDER BY $orderSql
     LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}"
);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = $pdo->query(
    "SELECT c.*, COUNT(p.id) AS product_count
     FROM categories c
     LEFT JOIN produk p ON p.category_id = c.id AND p.is_active = 1 AND p.is_demo = 0
     WHERE c.is_active = 1
     GROUP BY c.id
     HAVING COUNT(p.id) > 0
     ORDER BY c.sort_order, c.nama"
)->fetchAll();

$bounds = $pdo->query('SELECT MIN(harga) AS min_price, MAX(harga) AS max_price FROM produk WHERE is_active = 1 AND is_demo = 0')->fetch();
$filter_query = array_filter([
    'q' => $q,
    'kategori' => $category_slug,
    'min' => $min_price !== null ? (int) $min_price : null,
    'max' => $max_price !== null ? (int) $max_price : null,
    'stok' => $in_stock ? '1' : null,
    'sort' => $sort !== 'newest' ? $sort : null,
], fn ($v) => $v !== null && $v !== '');

$page_title = $active_category ? 'Koleksi ' . $active_category['nama'] : 'Katalog Buket';
$canonical_url = $active_category
    ? site_url('katalog.php?kategori=' . rawurlencode((string) $active_category['slug']))
    : site_url('katalog.php');
$meta_description = $active_category
    ? 'Koleksi buket ' . $active_category['nama'] . ' di Beneranjualan.id.'
    : 'Jelajahi katalog buket bunga Beneranjualan.id dan cek ketersediaan stok.';
$current_page = 'katalog';

include __DIR__ . '/includes/header.php';
?>

<section class="page-hero page-hero-catalog">
    <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="<?= e(site_url()) ?>">Beranda</a>
            <span aria-hidden="true">/</span>
            <span>Katalog</span>
            <?php if ($active_category): ?>
                <span aria-hidden="true">/</span>
                <span><?= e($active_category['nama']) ?></span>
            <?php endif; ?>
        </nav>
        <div class="catalog-heading">
            <div>
                <p class="eyebrow"><?= $active_category ? 'Koleksi momen' : 'Semua koleksi' ?></p>
                <h1 class="page-title"><?= $active_category ? e($active_category['nama']) : 'Pilih buket, temukan momennya.' ?></h1>
                <p class="page-lede"><?= $active_category && $active_category['deskripsi'] ? e($active_category['deskripsi']) : 'Cari berdasarkan nama, momen, atau kisaran harga. Stok yang tampil berasal dari database toko.' ?></p>
            </div>
            <p class="result-count" aria-live="polite"><?= $total ?> produk</p>
        </div>
    </div>
</section>

<section class="catalog-body">
    <div class="container catalog-layout">
        <details class="filter-panel" id="filterPanel" open>
            <summary>
                <span>Filter & urutan</span>
                <svg class="icon icon-xs" aria-hidden="true"><use href="#i-chevron-down"></use></svg>
            </summary>
            <form method="get" action="<?= e(site_url('katalog.php')) ?>" class="filter-form">
                <div class="field">
                    <label for="filterQ">Cari</label>
                    <input id="filterQ" type="search" name="q" value="<?= e($q) ?>" placeholder="Nama buket atau momen" data-debounce>
                </div>

                <fieldset class="field">
                    <legend>Momen</legend>
                    <label class="check-row">
                        <input type="radio" name="kategori" value="" <?= $category_slug === '' ? 'checked' : '' ?>>
                        <span>Semua momen</span>
                    </label>
                    <?php foreach ($categories as $cat): ?>
                    <label class="check-row">
                        <input type="radio" name="kategori" value="<?= e($cat['slug']) ?>" <?= $category_slug === $cat['slug'] ? 'checked' : '' ?>>
                        <span><?= e($cat['nama']) ?> <small>(<?= (int) $cat['product_count'] ?>)</small></span>
                    </label>
                    <?php endforeach; ?>
                </fieldset>

                <div class="field">
                    <span class="field-label">Kisaran harga</span>
                    <div class="range-row">
                        <label class="visually-hidden" for="filterMin">Harga minimum</label>
                        <input id="filterMin" type="number" name="min" min="0" step="1000" placeholder="Min" value="<?= $min_price !== null ? (int) $min_price : '' ?>">
                        <span aria-hidden="true">—</span>
                        <label class="visually-hidden" for="filterMax">Harga maksimum</label>
                        <input id="filterMax" type="number" name="max" min="0" step="1000" placeholder="Maks" value="<?= $max_price !== null ? (int) $max_price : '' ?>">
                    </div>
                </div>

                <label class="check-row">
                    <input type="checkbox" name="stok" value="1" <?= $in_stock ? 'checked' : '' ?>>
                    <span>Hanya yang tersedia</span>
                </label>

                <div class="field">
                    <label for="filterSort">Urutkan</label>
                    <select id="filterSort" name="sort">
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Terbaru</option>
                        <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Harga terendah</option>
                        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Harga tertinggi</option>
                        <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Nama A–Z</option>
                    </select>
                </div>

                <div class="filter-actions">
                    <button class="btn btn-primary btn-block" type="submit">Terapkan</button>
                    <a class="btn btn-ghost btn-block" href="<?= e(site_url('katalog.php')) ?>">Reset filter</a>
                </div>
            </form>
        </details>

        <div class="catalog-results">
            <?php if ($products): ?>
                <div class="product-grid product-grid-catalog">
                    <?php foreach ($products as $product): ?>
                        <?php include __DIR__ . '/includes/product-card.php'; ?>
                    <?php endforeach; ?>
                </div>
                <?= pagination_links($pagination, $filter_query) ?>
            <?php else: ?>
                <div class="empty-state empty-state-lg">
                    <svg class="icon icon-lg" aria-hidden="true"><use href="#i-flower"></use></svg>
                    <h2>Belum ada buket yang cocok</h2>
                    <p>Coba ubah kata kunci, momen, atau kisaran harga. Anda juga dapat melihat seluruh koleksi.</p>
                    <a class="btn btn-primary" href="<?= e(site_url('katalog.php')) ?>">Lihat semua koleksi</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
