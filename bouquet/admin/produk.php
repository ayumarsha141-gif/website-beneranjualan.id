<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = $_POST['action'] ?? '';
    $productId = (int) ($_POST['product_id'] ?? 0);
    if ($productId > 0 && in_array($action, ['archive', 'restore'], true)) {
        $active = $action === 'restore' ? 1 : 0;
        $pdo->prepare('UPDATE produk SET is_active = ?, updated_at = NOW() WHERE id = ?')->execute([$active, $productId]);
        log_admin_action($pdo, 'product_' . $action, 'product', $productId, []);
        redirect('admin/produk.php', $action === 'archive' ? 'Produk diarsipkan dari katalog.' : 'Produk diaktifkan kembali.', 'success');
    }
    redirect('admin/produk.php', 'Aksi produk tidak valid.', 'error');
}

$q = clean_text($_GET['q'] ?? '', 80);
$visibility = $_GET['visibility'] ?? 'all';
$lowOnly = ($_GET['low'] ?? '') === '1';
$lowThreshold = (int) setting($pdo, 'low_stock_threshold', '3');

$where = ['1=1'];
$params = [];
if ($q !== '') {
    $where[] = '(nama LIKE ? OR kategori LIKE ? OR slug LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like);
}
if ($visibility === 'active') {
    $where[] = 'is_active = 1';
} elseif ($visibility === 'archived') {
    $where[] = 'is_active = 0';
} else {
    $visibility = 'all';
}
if ($lowOnly) {
    $where[] = "availability_mode = 'stock' AND stok <= ?";
    $params[] = $lowThreshold;
}
$whereSql = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM produk WHERE $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$perPageOptions = [20 => '20 per halaman', 50 => '50 per halaman', 100 => '100 per halaman', 0 => 'Semua produk'];
$perPage = (int) ($_GET['per'] ?? 20);
if (!array_key_exists($perPage, $perPageOptions)) {
    $perPage = 20;
}
$pageSize = $perPage === 0 ? max(1, $total) : $perPage;
$pagination = paginate($total, $pageSize, max(1, (int) ($_GET['page'] ?? 1)));
$rangeStart = $total > 0 ? $pagination['offset'] + 1 : 0;
$rangeEnd = min($total, $pagination['offset'] + $pagination['per_page']);

$stmt = $pdo->prepare(
    "SELECT p.*, c.nama AS category_name
     FROM produk p LEFT JOIN categories c ON c.id = p.category_id
     WHERE $whereSql
     ORDER BY p.is_active DESC, p.created_at DESC
     LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}"
);
$stmt->execute($params);
$products = $stmt->fetchAll();

$admin_title = 'Produk';
$admin_page = 'products';
include __DIR__ . '/includes/admin_header.php';
?>

<?php showFlash(); ?>

<div class="admin-page-head">
    <div>
        <p class="admin-eyebrow">Katalog</p>
        <h2>Produk <span class="admin-count"><?= $total ?></span></h2>
        <p class="admin-muted">Produk diarsipkan tidak tampil di toko. Stok negatif tidak diizinkan oleh database.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(site_url('admin/produk-edit.php')) ?>">
        <svg class="icon" aria-hidden="true"><use href="#i-plus"></use></svg> Tambah produk
    </a>
</div>

<form class="admin-filter-bar" method="get" action="<?= e(site_url('admin/produk.php')) ?>">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari nama, kategori, slug">
    <select name="visibility">
        <option value="all" <?= $visibility === 'all' ? 'selected' : '' ?>>Semua visibilitas</option>
        <option value="active" <?= $visibility === 'active' ? 'selected' : '' ?>>Aktif</option>
        <option value="archived" <?= $visibility === 'archived' ? 'selected' : '' ?>>Diarsipkan</option>
    </select>
    <label class="admin-check-inline"><input type="checkbox" name="low" value="1" <?= $lowOnly ? 'checked' : '' ?>> Stok rendah (≤<?= $lowThreshold ?>)</label>
    <select name="per" aria-label="Jumlah produk per halaman">
        <?php foreach ($perPageOptions as $value => $label): ?>
            <option value="<?= $value ?>" <?= $perPage === $value ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn btn-primary btn-sm" type="submit">Filter</button>
    <a class="btn btn-ghost btn-sm" href="<?= e(site_url('admin/produk.php')) ?>">Reset</a>
</form>

<section class="admin-card">
    <?php if ($products): ?>
    <p class="admin-range">Menampilkan <?= $rangeStart ?>–<?= $rangeEnd ?> dari <?= $total ?> produk<?= $perPage === 0 ? ' (semua)' : '' ?></p>
    <div class="admin-table-wrap">
        <table class="admin-table admin-table-products">
            <thead>
                <tr><th>Produk</th><th>Kategori</th><th>Harga</th><th>Stok</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td>
                        <div class="admin-product-cell">
                            <img src="<?= e(product_image_url($product['gambar'])) ?>" alt="" width="44" height="55" loading="lazy">
                            <div>
                                <a class="admin-table-title" href="<?= e(site_url('admin/produk-edit.php?id=' . (int) $product['id'])) ?>"><?= e($product['nama']) ?></a>
                                <span class="admin-table-sub">
                                    <?php if ((int) $product['is_demo'] === 1): ?><span class="badge badge-demo">Demo</span> <?php endif; ?>
                                    <?php if ((int) $product['is_featured'] === 1): ?>Unggulan <?php endif; ?>
                                    <?= e($product['slug']) ?>
                                </span>
                            </div>
                        </div>
                    </td>
                    <td><?= e($product['category_name'] ?: $product['kategori']) ?></td>
                    <td class="admin-money"><?= e(product_price_label($product)) ?><span class="admin-table-sub"><?= ($product['availability_mode'] ?? 'stock') === 'request' ? 'Konfirmasi via WhatsApp' : 'Stok internal' ?></span></td>
                    <td>
                        <?php if (($product['availability_mode'] ?? 'stock') === 'request'): ?>
                            <span class="admin-table-sub" title="Ketersediaan dikonfirmasi via WhatsApp">—</span>
                        <?php else: ?>
                            <span class="admin-stock-qty <?= (int) $product['stok'] === 0 ? 'is-out' : ((int) $product['stok'] <= $lowThreshold ? 'is-low' : '') ?>">
                                <?= (int) $product['stok'] ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge <?= (int) $product['is_active'] ? 'badge-selesai' : 'badge-dibatalkan' ?>"><?= (int) $product['is_active'] ? 'Aktif' : 'Diarsipkan' ?></span></td>
                    <td>
                        <div class="admin-row-actions">
                            <a class="btn btn-outline btn-sm" href="<?= e(site_url('admin/produk-edit.php?id=' . (int) $product['id'])) ?>">Edit</a>
                            <form method="post" data-confirm-form="<?= (int) $product['is_active'] ? 'Arsipkan produk ini dari katalog?' : 'Aktifkan produk ini kembali?' ?>">
                                <?= csrfField() ?>
                                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                                <input type="hidden" name="action" value="<?= (int) $product['is_active'] ? 'archive' : 'restore' ?>">
                                <button class="btn btn-ghost btn-sm" type="submit"><?= (int) $product['is_active'] ? 'Arsipkan' : 'Aktifkan' ?></button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= pagination_links($pagination, array_filter([
        'q' => $q,
        'visibility' => $visibility,
        'low' => $lowOnly ? '1' : '',
        'per' => $perPage === 20 ? '' : (string) $perPage,
    ], fn ($v) => $v !== '')) ?>
    <?php else: ?>
    <div class="admin-empty">
        <svg class="icon icon-lg" aria-hidden="true"><use href="#i-flower"></use></svg>
        <h4>Belum ada produk</h4>
        <p>Tambahkan produk pertama, unggah foto, dan tentukan stok awal. Produk demo juga dapat diarsipkan dari sini.</p>
        <a class="btn btn-primary btn-sm" href="<?= e(site_url('admin/produk-edit.php')) ?>">Tambah produk</a>
    </div>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
