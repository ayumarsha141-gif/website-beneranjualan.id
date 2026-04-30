<?php
$page_title = 'Keranjang Belanja';
include 'includes/header.php';

$cart = $_SESSION['cart'] ?? [];
$total = 0;
$items = [];

// Get cart items from database
if (!empty($cart)) {
    $ids = array_keys($cart);
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    $stmt = $pdo->prepare("SELECT * FROM produk WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $items = $stmt->fetchAll();
}
?>

<section class="cart-page">
    <div class="container">
        <?php showFlash(); ?>
        
        <div class="cart-header">
            <h1><i class="fas fa-shopping-cart"></i> Keranjang Belanja</h1>
        </div>
        
        <?php if (empty($cart)): ?>
        <!-- Empty Cart -->
        <div class="cart-items">
            <div class="empty-cart">
                <i class="fas fa-shopping-basket"></i>
                <h3>Keranjang Anda Kosong</h3>
                <p>Belum ada produk di keranjang. Yuk mulai belanja!</p>
                <a href="<?= SITE_URL ?>" class="btn btn-primary">
                    <i class="fas fa-shopping-bag"></i> Mulai Belanja
                </a>
            </div>
        </div>
        
        <?php else: ?>
        <div class="cart-content">
            <!-- Cart Items -->
            <div class="cart-items">
                <?php foreach ($items as $item): 
                    $qty = $cart[$item['id']];
                    $subtotal = $item['harga'] * $qty;
                    $total += $subtotal;
                ?>
                <div class="cart-item" data-product-id="<?= $item['id'] ?>">
                    <div class="cart-item-image">
                        <img src="<?= SITE_URL ?>/assets/images/<?= sanitize($item['gambar']) ?>" 
                             alt="<?= sanitize($item['nama']) ?>">
                    </div>
                    
                    <div class="cart-item-details">
                        <h4><?= sanitize($item['nama']) ?></h4>
                        <p><?= sanitize($item['kategori']) ?></p>
                        <p class="item-price"><?= formatRupiah($item['harga']) ?></p>
                    </div>
                    
                    <div class="quantity-control">
                        <a href="update_cart.php?id=<?= $item['id'] ?>&act=min" class="qty-btn qty-minus">
                            <i class="fas fa-minus"></i>
                        </a>
                        <input type="number" class="qty-input" value="<?= $qty ?>" min="1" readonly>
                        <a href="update_cart.php?id=<?= $item['id'] ?>&act=plus" class="qty-btn qty-plus">
                            <i class="fas fa-plus"></i>
                        </a>
                    </div>
                    
                    <div class="cart-item-price">
                        <span class="item-subtotal"><?= formatRupiah($subtotal) ?></span>
                    </div>
                    
                    <a href="hapus_cart.php?id=<?= $item['id'] ?>" class="cart-item-remove" 
                       onclick="return confirm('Hapus produk ini dari keranjang?')">
                        <i class="fas fa-trash-alt"></i>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Cart Summary -->
            <div class="cart-summary">
                <h3>Ringkasan Belanja</h3>
                
                <div class="summary-row">
                    <span>Subtotal (<?= getCartCount() ?> item)</span>
                    <span><?= formatRupiah($total) ?></span>
                </div>
                
                <div class="summary-row">
                    <span>Ongkos Kirim</span>
                    <span><?= $total >= 500000 ? '<span style="color:var(--success)">GRATIS</span>' : formatRupiah(25000) ?></span>
                </div>
                
                <?php 
                $shipping = $total >= 500000 ? 0 : 25000;
                $grandTotal = $total + $shipping;
                ?>
                
                <div class="summary-row total">
                    <span>Total</span>
                    <span class="amount cart-total-amount"><?= formatRupiah($grandTotal) ?></span>
                </div>
                
                <div class="promo-code">
                    <input type="text" placeholder="Kode Promo" class="form-control">
                    <button class="btn btn-outline">Terapkan</button>
                </div>
                
                <a href="<?= SITE_URL ?>/checkout.php" class="btn btn-primary btn-block btn-lg">
                    <i class="fas fa-lock"></i> Checkout Sekarang
                </a>
                
                <a href="<?= SITE_URL ?>" class="btn btn-outline btn-block" style="margin-top: 10px;">
                    <i class="fas fa-arrow-left"></i> Lanjut Belanja
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
