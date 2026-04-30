<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$id = intval($_GET['id'] ?? 0);
$action = $_GET['act'] ?? '';
$qty = intval($_GET['qty'] ?? 0);
$ajax = isset($_GET['ajax']);

if ($id <= 0 || !isset($_SESSION['cart'][$id])) {
    if ($ajax) {
        echo json_encode(['success' => false]);
        exit;
    }
    redirect('cart.php');
}

// Update quantity
if ($qty > 0) {
    $_SESSION['cart'][$id] = $qty;
} elseif ($action === 'plus') {
    $_SESSION['cart'][$id]++;
} elseif ($action === 'min') {
    $_SESSION['cart'][$id]--;
    if ($_SESSION['cart'][$id] <= 0) {
        unset($_SESSION['cart'][$id]);
    }
}

if ($ajax) {
    // Calculate new subtotal and total
    $stmt = $pdo->prepare("SELECT harga FROM produk WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    
    $subtotal = isset($_SESSION['cart'][$id]) ? $product['harga'] * $_SESSION['cart'][$id] : 0;
    $total = getCartTotal($pdo);
    $shipping = $total >= 500000 ? 0 : 25000;
    
    echo json_encode([
        'success' => true,
        'subtotal' => formatRupiah($subtotal),
        'total' => formatRupiah($total + $shipping),
        'cart_count' => getCartCount()
    ]);
    exit;
}

redirect('cart.php');
