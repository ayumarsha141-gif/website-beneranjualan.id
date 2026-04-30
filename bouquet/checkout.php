<?php
$page_title = 'Checkout';
include 'includes/header.php';

// Redirect if cart is empty
if (empty($_SESSION['cart'])) {
    redirect('cart.php', 'Keranjang Anda kosong!', 'error');
}

$cart = $_SESSION['cart'];
$total = 0;
$items = [];

// Get cart items
$ids = array_keys($cart);
$placeholders = str_repeat('?,', count($ids) - 1) . '?';
$stmt = $pdo->prepare("SELECT * FROM produk WHERE id IN ($placeholders)");
$stmt->execute($ids);
$items = $stmt->fetchAll();

foreach ($items as $item) {
    $total += $item['harga'] * $cart[$item['id']];
}

$shipping = $total >= 500000 ? 0 : 25000;
$grandTotal = $total + $shipping;
?>

<section class="checkout-page">
    <div class="container">
        <?php showFlash(); ?>
        
        <div class="cart-header">
            <h1><i class="fas fa-credit-card"></i> Checkout</h1>
        </div>
        
        <form action="proses_checkout.php" method="POST" data-validate>
            <?= csrfField() ?>
            
            <div class="checkout-grid">
                <!-- Checkout Form -->
                <div>
                    <div class="checkout-form">
                        <h2><i class="fas fa-user"></i> Informasi Pengiriman</h2>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nama Lengkap <span class="required">*</span></label>
                                <input type="text" name="nama" class="form-control" 
                                       placeholder="Masukkan nama lengkap" required
                                       value="<?= isLoggedIn() ? sanitize($_SESSION['nama_lengkap'] ?? '') : '' ?>">
                            </div>
                            <div class="form-group">
                                <label>Nomor Telepon <span class="required">*</span></label>
                                <input type="tel" name="telepon" class="form-control" 
                                       placeholder="08xxxxxxxxxx" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Email <span class="required">*</span></label>
                            <input type="email" name="email" class="form-control" 
                                   placeholder="email@example.com" required
                                   value="<?= isLoggedIn() ? sanitize($_SESSION['email'] ?? '') : '' ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Alamat Lengkap <span class="required">*</span></label>
                            <textarea name="alamat" class="form-control" rows="3" 
                                      placeholder="Masukkan alamat lengkap pengiriman" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Catatan (Opsional)</label>
                            <textarea name="catatan" class="form-control" rows="2" 
                                      placeholder="Catatan untuk penjual atau kurir"></textarea>
                        </div>
                    </div>
                    
                    <div class="checkout-form" style="margin-top: 30px;">
                        <h2><i class="fas fa-wallet"></i> Metode Pembayaran</h2>
                        
                        <div class="payment-methods">
                            <label class="payment-option selected">
                                <input type="radio" name="metode_bayar" value="transfer" checked>
                                <span class="payment-radio"></span>
                                <i class="fas fa-university" style="font-size: 24px; color: var(--primary);"></i>
                                <div class="payment-info">
                                    <h5>Transfer Bank</h5>
                                    <p>BCA, Mandiri, BNI, BRI</p>
                                </div>
                            </label>
                            
                            <label class="payment-option">
                                <input type="radio" name="metode_bayar" value="ewallet">
                                <span class="payment-radio"></span>
                                <i class="fas fa-mobile-alt" style="font-size: 24px; color: var(--primary);"></i>
                                <div class="payment-info">
                                    <h5>E-Wallet</h5>
                                    <p>GoPay, OVO, DANA, ShopeePay</p>
                                </div>
                            </label>
                            
                            <label class="payment-option">
                                <input type="radio" name="metode_bayar" value="cod">
                                <span class="payment-radio"></span>
                                <i class="fas fa-hand-holding-usd" style="font-size: 24px; color: var(--primary);"></i>
                                <div class="payment-info">
                                    <h5>Bayar di Tempat (COD)</h5>
                                    <p>Bayar saat barang diterima</p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Order Summary -->
                <div class="order-summary">
                    <h3>Ringkasan Pesanan</h3>
                    
                    <div class="order-items">
                        <?php foreach ($items as $item): 
                            $qty = $cart[$item['id']];
                            $subtotal = $item['harga'] * $qty;
                        ?>
                        <div class="order-item">
                            <div class="order-item-image">
                                <img src="<?= SITE_URL ?>/assets/images/<?= sanitize($item['gambar']) ?>" 
                                     alt="<?= sanitize($item['nama']) ?>">
                            </div>
                            <div class="order-item-details">
                                <h5><?= sanitize($item['nama']) ?></h5>
                                <p><?= $qty ?> x <?= formatRupiah($item['harga']) ?></p>
                            </div>
                            <div class="order-item-price">
                                <?= formatRupiah($subtotal) ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span><?= formatRupiah($total) ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Ongkos Kirim</span>
                        <span><?= $shipping > 0 ? formatRupiah($shipping) : '<span style="color:var(--success)">GRATIS</span>' ?></span>
                    </div>
                    
                    <div class="summary-row total">
                        <span>Total Pembayaran</span>
                        <span class="amount"><?= formatRupiah($grandTotal) ?></span>
                    </div>
                    
                    <input type="hidden" name="total" value="<?= $grandTotal ?>">
                    
                    <button type="submit" class="btn btn-primary btn-block btn-lg">
                        <i class="fas fa-check-circle"></i> Buat Pesanan
                    </button>
                    
                    <p style="text-align: center; margin-top: 15px; font-size: 13px; color: var(--gray);">
                        <i class="fas fa-lock"></i> Pembayaran Anda aman & terenkripsi
                    </p>
                </div>
            </div>
        </form>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
