<?php
$page_title = 'Home';
include 'includes/header.php';

// Get products
$stmt = $pdo->query("SELECT * FROM produk ORDER BY created_at DESC LIMIT 8");
$products = $stmt->fetchAll();

// Get categories
$categories = $pdo->query("SELECT DISTINCT kategori FROM produk")->fetchAll();
?>

<!-- Hero Section -->
<section class="hero">
    <div class="container hero-container">
        <div class="hero-content">
            <span class="hero-badge">
                <i class="fas fa-truck"></i> Free Delivery untuk order 500K+
            </span>
            
            <h1 class="hero-title">
                Hadirkan <span>Kebahagiaan</span> dengan Buket Bunga Premium
            </h1>
            
            <p class="hero-text">
                Rangkaian bunga segar berkualitas tinggi untuk setiap momen spesial. 
                Dari anniversary hingga wisuda, kami siap membuat hari Anda lebih berkesan.
            </p>
            
            <div class="hero-buttons">
                <a href="#produk" class="btn btn-primary btn-lg">
                    <i class="fas fa-shopping-bag"></i> Belanja Sekarang
                </a>
                <a href="#tentang" class="btn btn-outline btn-lg">
                    <i class="fas fa-play-circle"></i> Tentang Kami
                </a>
            </div>
            
            <div class="hero-stats">
                <div class="stat-item">
                    <div class="stat-number">5000+</div>
                    <div class="stat-label">Happy Customers</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">50+</div>
                    <div class="stat-label">Jenis Buket</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">4.9</div>
                    <div class="stat-label">Rating</div>
                </div>
            </div>
        </div>
        
        <div class="hero-image">
            <img src="<?= SITE_URL ?>/assets/images/hero-bouquet.jpg" alt="Luxury Bouquet">
            
            <div class="hero-float-card card-1">
                <i class="fas fa-star" style="color: #ffc107;"></i>
                <div>
                    <strong>4.9/5 Rating</strong>
                    <small>500+ Reviews</small>
                </div>
            </div>
            
            <div class="hero-float-card card-2">
                <i class="fas fa-shipping-fast" style="color: var(--primary);"></i>
                <div>
                    <strong>Same Day</strong>
                    <small>Delivery</small>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Products Section -->
<section class="section" id="produk">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Koleksi Kami</span>
            <h2 class="section-title">Buket Bunga Pilihan</h2>
            <p class="section-text">
                Temukan rangkaian bunga segar berkualitas premium untuk setiap momen spesial
            </p>
        </div>
        
        <div class="products-grid">
            <?php foreach ($products as $product): ?>
            <div class="product-card animate-on-scroll">
                <?php if ($product['kategori'] === 'Premium'): ?>
                    <span class="product-badge">Premium</span>
                <?php endif; ?>
                
                <div class="product-image">
                    <img src="<?= SITE_URL ?>/assets/images/<?= sanitize($product['gambar']) ?>" 
                         alt="<?= sanitize($product['nama']) ?>">
                    
                    <div class="product-actions">
                        <button class="product-action-btn" title="Quick View" 
                                data-product-id="<?= $product['id'] ?>">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="product-action-btn add-to-cart-btn" title="Add to Cart"
                                data-product-id="<?= $product['id'] ?>">
                            <i class="fas fa-shopping-cart"></i>
                        </button>
                        <button class="product-action-btn" title="Add to Wishlist">
                            <i class="fas fa-heart"></i>
                        </button>
                    </div>
                </div>
                
                <div class="product-info">
                    <span class="product-category"><?= sanitize($product['kategori']) ?></span>
                    <h3 class="product-name">
                        <a href="<?= SITE_URL ?>/produk.php?id=<?= $product['id'] ?>">
                            <?= sanitize($product['nama']) ?>
                        </a>
                    </h3>
                    <div class="product-price">
                        <span class="current-price"><?= formatRupiah($product['harga']) ?></span>
                    </div>
                    <div class="product-rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-alt"></i>
                        <span>(4.8)</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div style="text-align: center; margin-top: 40px;">
            <a href="<?= SITE_URL ?>/katalog.php" class="btn btn-outline btn-lg">
                Lihat Semua Produk <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="section" style="background: var(--light);">
    <div class="container">
        <div class="features-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 30px;">
            <div class="feature-card" style="text-align: center; padding: 30px;">
                <div style="width: 80px; height: 80px; background: var(--primary-light); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                    <i class="fas fa-truck" style="font-size: 30px; color: var(--primary);"></i>
                </div>
                <h4>Free Delivery</h4>
                <p style="color: var(--gray);">Gratis ongkir untuk order minimal Rp500.000</p>
            </div>
            <div class="feature-card" style="text-align: center; padding: 30px;">
                <div style="width: 80px; height: 80px; background: var(--primary-light); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                    <i class="fas fa-leaf" style="font-size: 30px; color: var(--primary);"></i>
                </div>
                <h4>Bunga Segar</h4>
                <p style="color: var(--gray);">100% bunga segar dengan kualitas terbaik</p>
            </div>
            <div class="feature-card" style="text-align: center; padding: 30px;">
                <div style="width: 80px; height: 80px; background: var(--primary-light); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                    <i class="fas fa-clock" style="font-size: 30px; color: var(--primary);"></i>
                </div>
                <h4>Same Day Delivery</h4>
                <p style="color: var(--gray);">Pesan sebelum jam 2 siang, kirim hari ini</p>
            </div>
            <div class="feature-card" style="text-align: center; padding: 30px;">
                <div style="width: 80px; height: 80px; background: var(--primary-light); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                    <i class="fas fa-headset" style="font-size: 30px; color: var(--primary);"></i>
                </div>
                <h4>Support 24/7</h4>
                <p style="color: var(--gray);">Tim kami siap membantu kapan saja</p>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
