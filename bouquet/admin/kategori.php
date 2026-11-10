<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = $_POST['action'] ?? 'save';

    if ($action === 'toggle') {
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
        $stmt->execute([$categoryId]);
        $category = $stmt->fetch();
        if ($category) {
            $newActive = (int) $category['is_active'] === 1 ? 0 : 1;
            $pdo->prepare('UPDATE categories SET is_active = ?, updated_at = NOW() WHERE id = ?')->execute([$newActive, $categoryId]);
            log_admin_action($pdo, 'category_toggle', 'category', $categoryId, ['is_active' => $newActive]);
            redirect('admin/kategori.php', $newActive ? 'Kategori diaktifkan.' : 'Kategori dinonaktifkan dari navigasi.', 'success');
        }
        redirect('admin/kategori.php', 'Kategori tidak ditemukan.', 'error');
    }

    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $nama = clean_text($_POST['nama'] ?? '', 100);
    $slugInput = clean_text($_POST['slug'] ?? '', 120);
    $deskripsi = clean_text($_POST['deskripsi'] ?? '', 1000);
    $sortOrder = (int) ($_POST['sort_order'] ?? 0);
    $isActive = !empty($_POST['is_active']) ? 1 : 0;

    if (mb_strlen($nama) < 2) {
        flash('Nama kategori wajib diisi.', 'error');
        redirect('admin/kategori.php' . ($categoryId ? '?edit=' . $categoryId : ''));
    }

    $baseSlug = slugify($slugInput !== '' ? $slugInput : $nama);
    $slug = $baseSlug;
    $suffix = 2;
    while (true) {
        $stmt = $pdo->prepare('SELECT id FROM categories WHERE slug = ? AND id <> ?');
        $stmt->execute([$slug, $categoryId]);
        if (!$stmt->fetch()) {
            break;
        }
        $slug = $baseSlug . '-' . $suffix++;
    }

    if ($categoryId > 0) {
        $pdo->prepare('UPDATE categories SET nama = ?, slug = ?, deskripsi = ?, sort_order = ?, is_active = ?, updated_at = NOW() WHERE id = ?')
            ->execute([$nama, $slug, $deskripsi, $sortOrder, $isActive, $categoryId]);
        log_admin_action($pdo, 'category_update', 'category', $categoryId, ['nama' => $nama]);
        redirect('admin/kategori.php', 'Kategori diperbarui.', 'success');
    }

    $pdo->prepare('INSERT INTO categories (nama, slug, deskripsi, sort_order, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())')
        ->execute([$nama, $slug, $deskripsi, $sortOrder, $isActive]);
    $newId = (int) $pdo->lastInsertId();
    log_admin_action($pdo, 'category_create', 'category', $newId, ['nama' => $nama]);
    redirect('admin/kategori.php', 'Kategori dibuat.', 'success');
}

$editId = (int) ($_GET['edit'] ?? 0);
$editCategory = null;
if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([$editId]);
    $editCategory = $stmt->fetch();
}

$categories = $pdo->query(
    'SELECT c.*, COUNT(p.id) AS product_count
     FROM categories c LEFT JOIN produk p ON p.category_id = c.id
     GROUP BY c.id ORDER BY c.sort_order, c.nama'
)->fetchAll();

$admin_title = 'Kategori';
$admin_page = 'categories';
include __DIR__ . '/includes/admin_header.php';
?>

<?php showFlash(); ?>

<div class="admin-page-head">
    <div>
        <p class="admin-eyebrow">Katalog</p>
        <h2>Kategori <span class="admin-count"><?= count($categories) ?></span></h2>
        <p class="admin-muted">Kategori nonaktif tidak tampil di navigasi toko. Produk tetap menyimpan kategori lamanya.</p>
    </div>
</div>

<div class="admin-two-col admin-two-col-form">
    <section class="admin-card">
        <div class="admin-card-head">
            <h3><?= $editCategory ? 'Edit kategori' : 'Kategori baru' ?></h3>
            <?php if ($editCategory): ?><a class="admin-link" href="<?= e(site_url('admin/kategori.php')) ?>">Batal edit</a><?php endif; ?>
        </div>
        <form method="post" class="admin-form-stack">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="category_id" value="<?= (int) ($editCategory['id'] ?? 0) ?>">
            <label>Nama
                <input type="text" name="nama" required maxlength="100" value="<?= e($editCategory['nama'] ?? '') ?>">
            </label>
            <label>Slug
                <input type="text" name="slug" maxlength="120" value="<?= e($editCategory['slug'] ?? '') ?>" placeholder="otomatis">
            </label>
            <label>Deskripsi
                <textarea name="deskripsi" rows="3" maxlength="1000"><?= e($editCategory['deskripsi'] ?? '') ?></textarea>
            </label>
            <label>Urutan
                <input type="number" name="sort_order" step="1" value="<?= (int) ($editCategory['sort_order'] ?? 0) ?>">
            </label>
            <label class="admin-check-inline"><input type="checkbox" name="is_active" value="1" <?= !isset($editCategory) || (int) $editCategory['is_active'] ? 'checked' : '' ?>> Tampil di navigasi</label>
            <button class="btn btn-primary btn-block" type="submit"><?= $editCategory ? 'Simpan perubahan' : 'Buat kategori' ?></button>
        </form>
    </section>

    <section class="admin-card">
        <div class="admin-card-head"><h3>Daftar kategori</h3></div>
        <?php if ($categories): ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Nama</th><th>Produk</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                    <tr>
                        <td>
                            <span class="admin-table-title"><?= e($category['nama']) ?></span>
                            <span class="admin-table-sub">/<?= e($category['slug']) ?></span>
                        </td>
                        <td><?= (int) $category['product_count'] ?></td>
                        <td><span class="badge <?= (int) $category['is_active'] ? 'badge-selesai' : 'badge-dibatalkan' ?>"><?= (int) $category['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td>
                        <td>
                            <div class="admin-row-actions">
                                <a class="btn btn-outline btn-sm" href="<?= e(site_url('admin/kategori.php?edit=' . (int) $category['id'])) ?>">Edit</a>
                                <form method="post" data-confirm-form="Ubah status tampil kategori ini?">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="category_id" value="<?= (int) $category['id'] ?>">
                                    <button class="btn btn-ghost btn-sm" type="submit"><?= (int) $category['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="admin-empty admin-empty-sm"><p>Belum ada kategori. Buat kategori pertama di formulir.</p></div>
        <?php endif; ?>
    </section>
</div>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
