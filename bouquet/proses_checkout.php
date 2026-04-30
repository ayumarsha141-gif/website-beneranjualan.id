<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Validate request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('checkout.php');
}

// Validate CSRF
if (!validateCsrf($_POST['csrf_token'] ?? '')) {
    redirect('checkout.php', 'Token tidak valid!', 'error');
}

// Validate cart
if (empty($_SESSION['cart'])) {
    redirect('cart.php', 'Keranjang Anda kosong!', 'error');
}

// Sanitize inputs
$nama = sanitize($_POST['nama']);
$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
$telepon = sanitize($_POST['telepon']);
$alamat = sanitize($_POST['alamat']);
$catatan = sanitize($_POST['catatan'] ?? '');
$metode_bayar = sanitize($_POST['metode_bayar']);
$total = floatval($_POST['total']);

// Validate required fields
if (empty($nama) || empty($email) || empty($telepon) || empty($alamat)) {
    redirect('checkout.php', 'Mohon lengkapi semua data!', 'error');
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect('checkout.php', 'Format email tidak valid!', 'error');
}

try {
    $pdo->beginTransaction();
    
    // Get user_id if logged in
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    // Insert order
    $stmt = $pdo->prepare("
        INSERT INTO pesanan (user_id, nama, email, telepon, alamat, total, metode_bayar, catatan, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ");
    $stmt->execute([$user_id, $nama, $email, $telepon, $alamat, $total, $metode_bayar, $catatan]);
    $pesanan_id = $pdo->lastInsertId();
    
    // Insert order details
    $cart = $_SESSION['cart'];
    $ids = array_keys($cart);
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    
    $stmt = $pdo->prepare("SELECT * FROM produk WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $products = $stmt->fetchAll();
    
    $detail_stmt = $pdo->prepare("
        INSERT INTO detail_pesanan (pesanan_id, produk_id, qty, harga, subtotal)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    foreach ($products as $product) {
        $qty = $cart[$product['id']];
        $harga = $product['harga'];
        $subtotal = $harga * $qty;
        $detail_stmt->execute([$pesanan_id, $product['id'], $qty, $harga, $subtotal]);
        
        // Update stock
        $pdo->prepare("UPDATE produk SET stok = stok - ? WHERE id = ?")
            ->execute([$qty, $product['id']]);
    }
    
    $pdo->commit();
    
    // Clear cart
    unset($_SESSION['cart']);
    
    // Store order ID for thank you page
    $_SESSION['last_order_id'] = $pesanan_id;
    
    redirect('terima-kasih.php', 'Pesanan berhasil dibuat!', 'success');
    
} catch (Exception $e) {
    $pdo->rollBack();
    redirect('checkout.php', 'Terjadi kesalahan: ' . $e->getMessage(), 'error');
}
