<?php
include 'includes/header.php';

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    redirect('index.php');
}

$stmt = $pdo->prepare("SELECT * FROM produk WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    redirect('index.php', 'Produk tidak ditemukan!', 'error');
}

$page_title = $product['nama'];

// Get related products
$stmt = $pdo->prepare("SELECT * FROM produk WHERE kategori = ? AND id != ? LIMIT 4");
$stmt->execute([$product['kategori'], $id]);
$related = $stmt->fetchAll();
?>

<section class="section" style="padding-top: 120px;">
    <div class="container">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: start;">
            <!-- Product Image -->
            <div>
                <div style="border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-lg);">
                    <img src="<?= SITE_URL ?>/assets/images/<?= sanitize($product['gambar']) ?>" 
                         alt="<?= sanitize($product['nama']) ?>"
                         style="width: 100%; height: 500px; object-fit: cover;">
                </div>
            </div>
            
            <!-- Product Info -->
            <div>
                <span class="section-badge"><?= sanitize($product['kategori']) ?></span>
                <h1 style="font-size: 36px; color: var(--dark); margin: 15px 0;"><?= sanitize($product['nama']) ?></h1>
                
                <div class="product-rating" style="margin-bottom: 20px;">
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star-half-alt"></i>
                    <span>(4.8 dari 120 review)</span>
                </div>
                
                <div class="product-price" style="margin-bottom: 25px;">
                    <span class="current-price" style="font-size: 32px;"><?= formatRupiah($product['harga']) ?></span>
                </div>
                
                <p style="color: var(--gray); margin-bottom: 30px; line-height: 1.8;">
                    <?= nl2br(sanitize($product['deskripsi'])) ?>
                </p>
                
                <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 25px;">
                    <span style="font-weight: 500;">Stok:</span>
                    <?php if ($product['stok'] > 0): ?>
                        <span style="color: var(--success);"><i class="fas fa-check-circle"></i> Tersedia (<?= $product['stok'] ?> pcs)</span>
                    <?php else: ?>
                        <span style="color: var(--danger);"><i class="fas fa-times-circle"></i> Habis</span>
                    <?php endif; ?>
                </div>
                
                <div style="display: flex; gap: 15px;">
                    <a href="tambah_cart.php?id=<?= $product['id'] ?>" class="btn btn-primary btn-lg">
                        <i class="fas fa-shopping-cart"></i> Tambah ke Keranjang
                    </a>
                    <a href="[wa.me](https://wa.me/6281234567890?text=Halo) saya tertarik dengan <?= urlencode($product['nama']) ?>" 
                       class="btn btn-outline btn-lg" target="_blank">
                        <i class="fab fa-whatsapp"></i> Chat Penjual
                    </a>
                </div>
                
                <hr style="margin: 30px 0; border: none; border-top: 1px solid var(--gray-light);">
                
                <div style="display: flex; gap: 30px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-truck" style="color: var(--primary);"></i>
                        <span>Free Ongkir min. 500K</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-shield-alt" style="color: var(--primary);"></i>
                        <span>Garansi Kualitas</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if (!empty($related)): ?>
<section class="section" style="background: var(--light);">
    <div class="container">
        <h2 class="section-title">Produk Terkait</h2>
        <div class="products-grid">
            <?php foreach ($related as $item): ?>
            <div class="product-card">
                <div class="product-image">
                    <img src="<?= SITE_URL ?>/assets/images/<?= sanitize($item['gambar']) ?>" 
                         alt="<?= sanitize($item['nama']) ?>">
                </div>
                <div class="product-info">
                    <span class="product-category"><?= sanitize($item['kategori']) ?></span>
                    <h3 class="product-name">
                        <a href="<?= SITE_URL ?>/produk.php?id=<?= $item['id'] ?>">
                            <?= sanitize($item['nama']) ?>
                        </a>
                    </h3>
                    <div class="product-price">
                        <span class="current-price"><?= formatRupiah($item['harga']) ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
