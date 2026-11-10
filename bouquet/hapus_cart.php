<?php
require_once __DIR__ . '/includes/config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('cart.php', 'Gunakan tombol hapus pada keranjang.', 'info');
}
requireCsrf();
$id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    redirect('cart.php', 'Produk tidak valid.', 'error');
}
cart_remove((int) $id);
redirect('cart.php', 'Produk dihapus dari keranjang.');
