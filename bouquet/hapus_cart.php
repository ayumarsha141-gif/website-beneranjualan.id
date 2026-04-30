<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$id = intval($_GET['id'] ?? 0);

if ($id > 0 && isset($_SESSION['cart'][$id])) {
    unset($_SESSION['cart'][$id]);
}

redirect('cart.php', 'Produk dihapus dari keranjang', 'success');
