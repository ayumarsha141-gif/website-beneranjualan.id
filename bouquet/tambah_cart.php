<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$id = intval($_GET['id'] ?? 0);
$ajax = isset($_GET['ajax']);

if ($id <= 0) {
    if ($ajax) {
        echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
        exit;
    }
    redirect('index.php');
}

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Add to cart or increase quantity
if (isset($_SESSION['cart'][$id])) {
    $_SESSION['cart'][$id]++;
} else {
    $_SESSION['cart'][$id] = 1;
}

if ($ajax) {
    echo json_encode([
        'success' => true,
        'message' => 'Produk ditambahkan',
        'cart_count' => getCartCount()
    ]);
    exit;
}

redirect('index.php', 'Produk ditambahkan ke keranjang!', 'success');
