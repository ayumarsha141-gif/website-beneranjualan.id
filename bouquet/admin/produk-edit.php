<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

$id = (int) ($_GET['id'] ?? 0);
$isNew = $id <= 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = $_POST['action'] ?? 'save';
    $adminId = (int) $_SESSION['user_id'];

    if ($action === 'delete_image') {
        $imageId = (int) ($_POST['image_id'] ?? 0);
        $stmt = $pdo->prepare('SELECT * FROM product_images WHERE id = ? AND produk_id = ?');
        $stmt->execute([$imageId, $id]);
        $image = $stmt->fetch();
        if ($image) {
            $delete = $pdo->prepare('DELETE FROM product_images WHERE id = ? AND produk_id = ?');
            $delete->execute([$imageId, $id]);
            $path = APP_ROOT . '/assets/uploads/' . basename($image['filename']);
            if ($delete->rowCount() === 1 && is_file($path) && str_starts_with(basename($image['filename']), 'produk-')) {
                @unlink($path);
            }
            log_admin_action($pdo, 'product_image_delete', 'product', $id, ['filename' => $image['filename']]);
            redirect('admin/produk-edit.php?id=' . $id, 'Foto tambahan dihapus.', 'success');
        }
        redirect('admin/produk-edit.php?id=' . $id, 'Foto tidak ditemukan.', 'error');
    }

    $nama = clean_text($_POST['nama'] ?? '', 160);
    $slugInput = clean_text($_POST['slug'] ?? '', 190);
    $deskripsi = clean_text($_POST['deskripsi'] ?? '', 5000);
    $pricingMode = clean_text($_POST['pricing_mode'] ?? 'fixed', 16);
    $availabilityMode = clean_text($_POST['availability_mode'] ?? 'stock', 16);
    $pricingNote = clean_text($_POST['pricing_note'] ?? '', 500);
    $hargaInput = preg_replace('/\D+/', '', (string) ($_POST['harga'] ?? ''));
    $harga = $pricingMode === 'quote' ? 0 : ($hargaInput === '' ? -1 : (float) $hargaInput);
    $stok = (int) ($_POST['stok'] ?? 0);
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $isActive = !empty($_POST['is_active']) ? 1 : 0;
    $isFeatured = !empty($_POST['is_featured']) ? 1 : 0;
    $removeImage = !empty($_POST['remove_image']);

    $old = null;
    if (!$isNew) {
        $stmt = $pdo->prepare('SELECT * FROM produk WHERE id = ?');
        $stmt->execute([$id]);
        $old = $stmt->fetch();
        if (!$old) {
            redirect('admin/produk.php', 'Produk tidak ditemukan.', 'error');
        }
    }

    $errors = [];
    if (mb_strlen($nama) < 2) {
        $errors[] = 'Nama produk wajib diisi.';
    }
    if (!in_array($pricingMode, ['fixed', 'from', 'quote'], true)) {
        $errors[] = 'Mode harga tidak valid.';
    }
    if (!in_array($availabilityMode, ['stock', 'request'], true)) {
        $errors[] = 'Mode ketersediaan tidak valid.';
    }
    if ($pricingMode !== 'quote' && $harga <= 0) {
        $errors[] = 'Masukkan harga katalog atau harga mulai yang benar-benar berlaku.';
    }
    if ($availabilityMode === 'stock' && $pricingMode !== 'fixed') {
        $errors[] = 'Produk harga mulai/penawaran harus menggunakan konfirmasi WhatsApp, bukan checkout harga tetap.';
    }
    if ($stok < 0) {
        $errors[] = 'Stok tidak boleh negatif.';
    }

    $categoryName = 'Umum';
    if ($categoryId > 0) {
        $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
        $stmt->execute([$categoryId]);
        $category = $stmt->fetch();
        if (!$category) {
            $errors[] = 'Kategori tidak ditemukan.';
        } else {
            $categoryName = $category['nama'];
        }
    }

    $newImage = null;
    $newGalleryFiles = [];
    if (!empty($_FILES['gambar']['name'])) {
        $upload = handle_product_upload($_FILES['gambar']);
        if ($upload['success']) {
            $newImage = $upload['filename'];
        } else {
            $errors[] = $upload['message'];
        }
    }

    if ($errors) {
        if ($newImage) {
            @unlink(APP_ROOT . '/assets/uploads/' . basename($newImage));
        }
        flash(implode(' ', $errors), 'error');
        redirect($isNew ? 'admin/produk-edit.php' : 'admin/produk-edit.php?id=' . $id);
    }

    // Resolve unique slug.
    $baseSlug = slugify($slugInput !== '' ? $slugInput : $nama);
    $slug = $baseSlug;
    $suffix = 2;
    while (true) {
        $stmt = $pdo->prepare('SELECT id FROM produk WHERE slug = ? AND id <> ?');
        $stmt->execute([$slug, $id]);
        if (!$stmt->fetch()) {
            break;
        }
        $slug = $baseSlug . '-' . $suffix++;
    }

    $oldImage = $old['gambar'] ?? null;
    $image = $newImage ?? $oldImage;
    if ($removeImage && $newImage === null) {
        $image = null;
    }
    try {
        $pdo->beginTransaction();
        if ($isNew) {
            $stmt = $pdo->prepare(
                'INSERT INTO produk (nama, slug, deskripsi, harga, pricing_mode, availability_mode, pricing_note, stok, kategori, category_id, gambar, is_active, is_featured, is_demo, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW(), NOW())'
            );
            $stmt->execute([$nama, $slug, $deskripsi, $harga, $pricingMode, $availabilityMode, $pricingNote, $stok, $categoryName, $categoryId ?: null, $image, $isActive, $isFeatured]);
            $id = (int) $pdo->lastInsertId();
            if ($stok > 0) {
                $pdo->prepare(
                    'INSERT INTO stock_movements (produk_id, qty_change, movement_type, reference_type, reference_id, note, created_by, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
                )->execute([$id, $stok, 'initial', 'product', $id, 'Stok awal produk', $adminId]);
            }
            log_admin_action($pdo, 'product_create', 'product', $id, ['nama' => $nama]);
        } else {
            $stmt = $pdo->prepare(
                'UPDATE produk SET nama = ?, slug = ?, deskripsi = ?, harga = ?, pricing_mode = ?, availability_mode = ?, pricing_note = ?, stok = ?, kategori = ?, category_id = ?,
                    gambar = ?, is_active = ?, is_featured = ?, updated_at = NOW() WHERE id = ?'
            );
            $stmt->execute([$nama, $slug, $deskripsi, $harga, $pricingMode, $availabilityMode, $pricingNote, $stok, $categoryName, $categoryId ?: null, $image, $isActive, $isFeatured, $id]);
            $diff = $stok - (int) $old['stok'];
            if ($diff !== 0) {
                $pdo->prepare(
                    'INSERT INTO stock_movements (produk_id, qty_change, movement_type, reference_type, reference_id, note, created_by, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
                )->execute([$id, $diff, 'adjustment', 'product', $id, 'Penyesuaian stok dari form produk', $adminId]);
            }
            log_admin_action($pdo, 'product_update', 'product', $id, ['nama' => $nama, 'stok_diff' => $diff]);
        }

        // Additional images (max 5 per request).
        if (!empty($_FILES['images']['name'][0])) {
            $count = min(5, count($_FILES['images']['name']));
            $insertImage = $pdo->prepare('INSERT INTO product_images (produk_id, filename, alt_text, sort_order, created_at) VALUES (?, ?, ?, ?, NOW())');
            for ($i = 0; $i < $count; $i++) {
                $file = [
                    'name' => $_FILES['images']['name'][$i],
                    'type' => $_FILES['images']['type'][$i],
                    'tmp_name' => $_FILES['images']['tmp_name'][$i],
                    'error' => $_FILES['images']['error'][$i],
                    'size' => $_FILES['images']['size'][$i],
                ];
                if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $upload = handle_product_upload($file);
                if ($upload['success'] && $upload['filename']) {
                    $insertImage->execute([$id, $upload['filename'], $nama, $i]);
                    $newGalleryFiles[] = $upload['filename'];
                } else {
                    flash('Sebagian foto tambahan gagal diunggah: ' . ($upload['message'] ?? 'format tidak didukung'), 'error');
                }
            }
        }

        $pdo->commit();
        // Do not delete the old file until the database update is committed.
        if ($oldImage && $image !== $oldImage) {
            $oldPath = APP_ROOT . '/assets/uploads/' . basename($oldImage);
            if (is_file($oldPath) && str_starts_with(basename($oldImage), 'produk-')) {
                @unlink($oldPath);
            }
        }
        redirect('admin/produk-edit.php?id=' . $id, $isNew ? 'Produk berhasil dibuat.' : 'Produk berhasil diperbarui.', 'success');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        foreach (array_merge($newImage ? [$newImage] : [], $newGalleryFiles) as $filename) {
            $path = APP_ROOT . '/assets/uploads/' . basename($filename);
            if (is_file($path) && str_starts_with(basename($filename), 'produk-')) {
                @unlink($path);
            }
        }
        error_log('Product save failed: ' . $e->getMessage());
        flash('Produk gagal disimpan karena kendala sistem.', 'error');
        redirect($isNew ? 'admin/produk-edit.php' : 'admin/produk-edit.php?id=' . $id);
    }
}

$product = [
    'id' => 0,
    'nama' => '',
    'slug' => '',
    'deskripsi' => '',
    'harga' => '',
    'pricing_mode' => 'fixed',
    'availability_mode' => 'stock',
    'pricing_note' => '',
    'stok' => 0,
    'kategori' => 'Umum',
    'category_id' => null,
    'gambar' => null,
    'is_active' => 1,
    'is_featured' => 0,
    'is_demo' => 0,
    'updated_at' => null,
];
if (!$isNew) {
    $stmt = $pdo->prepare('SELECT * FROM produk WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        redirect('admin/produk.php', 'Produk tidak ditemukan.', 'error');
    }
    $product = $found;
}

$categories = $pdo->query('SELECT * FROM categories ORDER BY sort_order, nama')->fetchAll();
$images = [];
if (!$isNew) {
    $stmt = $pdo->prepare('SELECT * FROM product_images WHERE produk_id = ? ORDER BY sort_order, id');
    $stmt->execute([$id]);
    $images = $stmt->fetchAll();
}

$admin_title = $isNew ? 'Tambah Produk' : 'Edit Produk';
$admin_page = 'products';
include __DIR__ . '/includes/admin_header.php';
?>

<?php showFlash(); ?>

<div class="admin-page-head">
    <div>
        <a class="admin-back" href="<?= e(site_url('admin/produk.php')) ?>">← Kembali ke daftar produk</a>
        <h2><?= $isNew ? 'Tambah produk' : 'Edit ' . e($product['nama']) ?></h2>
        <p class="admin-muted"><?= $isNew ? 'Produk baru akan tampil di katalog jika status aktif.' : 'Terakhir diperbarui ' . e(format_date($product['updated_at'], 'd M Y H:i')) ?></p>
    </div>
    <?php if (!$isNew): ?>
    <a class="btn btn-outline" href="<?= e(site_url('produk.php?id=' . (int) $product['id'])) ?>" target="_blank" rel="noopener">Lihat di toko</a>
    <?php endif; ?>
</div>

<form method="post" action="<?= e(site_url('admin/produk-edit.php' . ($isNew ? '' : '?id=' . (int) $product['id']))) ?>" enctype="multipart/form-data" class="admin-product-layout">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="save">

    <div class="admin-product-main">
        <section class="admin-card">
            <div class="admin-card-head"><h3>Informasi produk</h3></div>
            <div class="admin-form-grid">
                <label class="admin-span-2">Nama produk
                    <input type="text" name="nama" required maxlength="160" value="<?= e($product['nama']) ?>">
                </label>
                <label class="admin-span-2">Slug URL
                    <input type="text" name="slug" maxlength="190" value="<?= e($product['slug']) ?>" placeholder="otomatis dari nama">
                    <small>Kosongkan untuk dibuat otomatis. Slug harus unik.</small>
                </label>
                <label class="admin-span-2">Deskripsi
                    <textarea name="deskripsi" rows="7" maxlength="5000"><?= e($product['deskripsi'] ?? '') ?></textarea>
                    <small>Jelaskan isi buket, bahan, dan catatan penting. Hindari klaim yang tidak dapat dipenuhi.</small>
                </label>
                <label>Harga (Rp)
                    <input type="text" name="harga" required inputmode="numeric" value="<?= e($product['harga'] !== '' ? number_format((float) $product['harga'], 0, ',', '.') : '') ?>" placeholder="125.000">
                    <small>Harga pricelist atau harga mulai; untuk mode “tanya via WhatsApp”, harga disimpan 0 dan tidak ditampilkan.</small>
                </label>
                <label>Model harga
                    <select name="pricing_mode">
                        <option value="fixed" <?= ($product['pricing_mode'] ?? 'fixed') === 'fixed' ? 'selected' : '' ?>>Harga katalog</option>
                        <option value="from" <?= ($product['pricing_mode'] ?? '') === 'from' ? 'selected' : '' ?>>Mulai dari (harga dasar)</option>
                        <option value="quote" <?= ($product['pricing_mode'] ?? '') === 'quote' ? 'selected' : '' ?>>Tanya harga via WhatsApp</option>
                    </select>
                    <small>Pilih “mulai dari”/“tanya harga” untuk custom order yang perlu penawaran final.</small>
                </label>
                <label>Stok
                    <input type="number" name="stok" required min="0" step="1" value="<?= (int) $product['stok'] ?>">
                    <small>Perubahan stok dicatat di inventaris.</small>
                </label>
                <label>Ketersediaan / cara pesan
                    <select name="availability_mode">
                        <option value="stock" <?= ($product['availability_mode'] ?? 'stock') === 'stock' ? 'selected' : '' ?>>Stok pasti · checkout keranjang</option>
                        <option value="request" <?= ($product['availability_mode'] ?? '') === 'request' ? 'selected' : '' ?>>Konfirmasi & pesan via WhatsApp</option>
                    </select>
                    <small>Mode WhatsApp tidak dianggap stok habis dan tidak masuk checkout harga tetap.</small>
                </label>
                <label class="admin-span-2">Catatan harga / custom
                    <input type="text" name="pricing_note" maxlength="500" value="<?= e($product['pricing_note'] ?? '') ?>" placeholder="Contoh: harga per pcs; bundle 2 pcs Rp...; final disepakati via WhatsApp">
                    <small>Catatan ini ditampilkan bersama harga bila relevan. Pastikan sesuai pricelist terbaru.</small>
                </label>
                <label>Kategori
                    <select name="category_id">
                        <option value="0">Tanpa kategori</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= (int) $category['id'] ?>" <?= (int) $product['category_id'] === (int) $category['id'] ? 'selected' : '' ?>><?= e($category['nama']) ?><?= (int) $category['is_active'] ? '' : ' (nonaktif)' ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small><a href="<?= e(site_url('admin/kategori.php')) ?>">Kelola kategori</a></small>
                </label>
                <div class="admin-flag-box">
                    <label class="admin-check-inline"><input type="checkbox" name="is_active" value="1" <?= (int) $product['is_active'] ? 'checked' : '' ?>> Tampil di katalog</label>
                    <label class="admin-check-inline"><input type="checkbox" name="is_featured" value="1" <?= (int) $product['is_featured'] ? 'checked' : '' ?>> Tandai unggulan</label>
                </div>
            </div>
        </section>

        <section class="admin-card">
            <div class="admin-card-head"><h3>Foto tambahan</h3><span class="admin-table-sub">Maks. 5 foto per unggahan</span></div>
            <div class="admin-form-grid">
                <label class="admin-span-2">Tambah foto galeri
                    <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple>
                    <small>JPG, PNG, atau WebP. Maksimal 4 MB per file.</small>
                </label>
            </div>
            <?php if ($images): ?>
            <div class="admin-gallery-grid">
                <?php foreach ($images as $image): ?>
                <figure class="admin-gallery-item">
                    <img src="<?= e(upload_url($image['filename'])) ?>" alt="<?= e($image['alt_text'] ?: $product['nama']) ?>" width="160" height="200">
                    <form method="post" action="<?= e(site_url('admin/produk-edit.php?id=' . (int) $product['id'])) ?>" data-confirm-form="Hapus foto ini dari galeri?">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="delete_image">
                        <input type="hidden" name="image_id" value="<?= (int) $image['id'] ?>">
                        <button class="btn btn-ghost btn-sm" type="submit">Hapus</button>
                    </form>
                </figure>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
                <p class="admin-muted">Belum ada foto tambahan. Foto utama diatur di panel kanan.</p>
            <?php endif; ?>
        </section>

        <div class="admin-form-actions">
            <button class="btn btn-primary btn-lg" type="submit">Simpan produk</button>
            <a class="btn btn-ghost" href="<?= e(site_url('admin/produk.php')) ?>">Batal</a>
        </div>
    </div>

    <aside class="admin-product-side">
        <section class="admin-card">
            <div class="admin-card-head"><h3>Foto utama</h3></div>
            <div class="admin-image-preview">
                <img src="<?= e(upload_url($product['gambar'])) ?>" alt="Pratinjau <?= e($product['nama']) ?>" width="300" height="375">
            </div>
            <label class="admin-file-input">Ganti foto utama
                <input type="file" name="gambar" accept="image/jpeg,image/png,image/webp">
                <small>Kosongkan jika tidak ingin mengganti.</small>
            </label>
            <?php if (!empty($product['gambar'])): ?>
            <label class="admin-check-inline"><input type="checkbox" name="remove_image" value="1"> Hapus foto utama saat menyimpan</label>
            <?php endif; ?>
            <?php if ((int) $product['is_demo'] === 1): ?>
                <p class="admin-hint">Produk ini masih ditandai <strong>DEMO</strong>. Menyimpan perubahan tidak otomatis menghapus tanda demo; arsipkan setelah produk nyata siap.</p>
            <?php endif; ?>
        </section>

        <section class="admin-card">
            <div class="admin-card-head"><h3>Ringkasan</h3></div>
            <dl class="admin-detail-list">
                <div><dt>ID</dt><dd><?= (int) $product['id'] ?: 'Baru' ?></dd></div>
                <div><dt>Kategori lama</dt><dd><?= e($product['kategori']) ?></dd></div>
                <div><dt>Status</dt><dd><?= (int) $product['is_active'] ? 'Aktif' : 'Diarsipkan' ?></dd></div>
                <div><dt>Unggulan</dt><dd><?= (int) $product['is_featured'] ? 'Ya' : 'Tidak' ?></dd></div>
            </dl>
        </section>
    </aside>
</form>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
