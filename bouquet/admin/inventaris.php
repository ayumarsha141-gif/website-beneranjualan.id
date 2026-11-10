<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $productId = (int) ($_POST['product_id'] ?? 0);
    $change = (int) ($_POST['qty_change'] ?? 0);
    $note = clean_text($_POST['note'] ?? '', 255);
    $adminId = (int) $_SESSION['user_id'];

    if ($productId <= 0 || $change === 0) {
        redirect('admin/inventaris.php', 'Pilih produk dan masukkan jumlah penyesuaian selain nol.', 'error');
    }

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT * FROM produk WHERE id = ? FOR UPDATE');
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        if (!$product) {
            throw new DomainException('Produk tidak ditemukan.');
        }
        $newStock = (int) $product['stok'] + $change;
        if ($newStock < 0) {
            throw new DomainException('Penyesuaian akan membuat stok negatif. Stok saat ini ' . (int) $product['stok'] . '.');
        }
        $pdo->prepare('UPDATE produk SET stok = ?, updated_at = NOW() WHERE id = ?')->execute([$newStock, $productId]);
        $type = $change > 0 ? 'restock' : 'adjustment';
        $pdo->prepare(
            'INSERT INTO stock_movements (produk_id, qty_change, movement_type, reference_type, reference_id, note, created_by, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
        )->execute([$productId, $change, $type, 'manual', null, $note !== '' ? $note : 'Penyesuaian manual admin', $adminId]);
        log_admin_action($pdo, 'stock_adjust', 'product', $productId, ['change' => $change, 'new_stock' => $newStock, 'note' => $note]);
        $pdo->commit();
        redirect('admin/inventaris.php', 'Stok ' . $product['nama'] . ' diperbarui menjadi ' . $newStock . '.', 'success');
    } catch (DomainException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        redirect('admin/inventaris.php', $e->getMessage(), 'error');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Stock adjustment failed: ' . $e->getMessage());
        redirect('admin/inventaris.php', 'Penyesuaian stok gagal disimpan.', 'error');
    }
}

$lowThreshold = (int) setting($pdo, 'low_stock_threshold', '3');
$lowOnly = ($_GET['low'] ?? '') === '1';
$q = clean_text($_GET['q'] ?? '', 80);

$where = ["is_active = 1", "is_demo = 0", "availability_mode = 'stock'"];
$params = [];
if ($lowOnly) {
    $where[] = 'stok <= ?';
    $params[] = $lowThreshold;
}
if ($q !== '') {
    $where[] = '(nama LIKE ? OR kategori LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like);
}
$whereSql = implode(' AND ', $where);
$stmt = $pdo->prepare("SELECT * FROM produk WHERE $whereSql ORDER BY stok ASC, nama ASC LIMIT 100");
$stmt->execute($params);
$products = $stmt->fetchAll();

$movements = $pdo->query(
    'SELECT m.*, p.nama AS product_name, u.nama_lengkap AS admin_name
     FROM stock_movements m
     JOIN produk p ON p.id = m.produk_id
     LEFT JOIN users u ON u.id = m.created_by
     ORDER BY m.created_at DESC, m.id DESC LIMIT 40'
)->fetchAll();

$admin_title = 'Inventaris';
$admin_page = 'inventory';
include __DIR__ . '/includes/admin_header.php';
?>

<?php showFlash(); ?>

<div class="admin-page-head">
    <div>
        <p class="admin-eyebrow">Stok</p>
        <h2>Inventaris</h2>
        <p class="admin-muted">Setiap penjualan, pembatalan, dan penyesuaian stok dicatat. Stok tidak dapat menjadi negatif.</p>
    </div>
</div>

<form class="admin-filter-bar" method="get" action="<?= e(site_url('admin/inventaris.php')) ?>">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari produk">
    <label class="admin-check-inline"><input type="checkbox" name="low" value="1" <?= $lowOnly ? 'checked' : '' ?>> Hanya stok ≤<?= $lowThreshold ?></label>
    <button class="btn btn-primary btn-sm" type="submit">Filter</button>
    <a class="btn btn-ghost btn-sm" href="<?= e(site_url('admin/inventaris.php')) ?>">Reset</a>
</form>

<div class="admin-two-col admin-two-col-form">
    <section class="admin-card">
        <div class="admin-card-head"><h3>Penyesuaian stok</h3></div>
        <form method="post" class="admin-form-stack">
            <?= csrfField() ?>
            <label>Produk
                <select name="product_id" required>
                    <option value="">Pilih produk</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?= (int) $product['id'] ?>"><?= e($product['nama']) ?> — stok <?= (int) $product['stok'] ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Perubahan stok
                <input type="number" name="qty_change" required step="1" placeholder="Contoh: 10 atau -2">
                <small>Gunakan angka positif untuk restock, negatif untuk koreksi/pengurangan.</small>
            </label>
            <label>Catatan
                <input type="text" name="note" maxlength="255" placeholder="Contoh: restock dari supplier">
            </label>
            <button class="btn btn-primary btn-block" type="submit">Simpan penyesuaian</button>
        </form>
        <p class="admin-hint">Produk yang tidak muncul di pilihan berarti tidak aktif atau tidak cocok dengan filter. Reset filter untuk melihat semua.</p>
    </section>

    <section class="admin-card">
        <div class="admin-card-head"><h3>Stok produk</h3><span class="admin-table-sub"><?= count($products) ?> produk</span></div>
        <?php if ($products): ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Produk</th><th>Kategori</th><th>Stok</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td><a class="admin-table-title" href="<?= e(site_url('admin/produk-edit.php?id=' . (int) $product['id'])) ?>"><?= e($product['nama']) ?></a></td>
                        <td><?= e($product['kategori']) ?></td>
                        <td><span class="admin-stock-qty <?= (int) $product['stok'] === 0 ? 'is-out' : ((int) $product['stok'] <= $lowThreshold ? 'is-low' : '') ?>"><?= (int) $product['stok'] ?></span></td>
                        <td><a class="admin-link" href="<?= e(site_url('admin/produk-edit.php?id=' . (int) $product['id'])) ?>">Edit</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="admin-empty admin-empty-sm"><p>Tidak ada produk yang cocok dengan filter.</p></div>
        <?php endif; ?>
    </section>
</div>

<section class="admin-card">
    <div class="admin-card-head"><h3>Riwayat pergerakan stok</h3><span class="admin-table-sub">40 terakhir</span></div>
    <?php if ($movements): ?>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th>Waktu</th><th>Produk</th><th>Perubahan</th><th>Jenis</th><th>Referensi</th><th>Catatan</th></tr></thead>
            <tbody>
                <?php foreach ($movements as $movement): ?>
                <tr>
                    <td><?= e(format_date($movement['created_at'], 'd M Y H:i')) ?></td>
                    <td><?= e($movement['product_name']) ?></td>
                    <td class="<?= (int) $movement['qty_change'] >= 0 ? 'admin-stock-up' : 'admin-stock-down' ?>"><?= (int) $movement['qty_change'] > 0 ? '+' : '' ?><?= (int) $movement['qty_change'] ?></td>
                    <td><?= e(str_replace('_', ' ', $movement['movement_type'])) ?></td>
                    <td><?= e($movement['reference_type'] ?: '—') ?><?= $movement['reference_id'] ? ' #' . (int) $movement['reference_id'] : '' ?></td>
                    <td><?= e($movement['note'] ?: '—') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="admin-empty admin-empty-sm"><p>Belum ada pergerakan stok.</p></div>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
