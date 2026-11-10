<?php
require_once __DIR__ . '/includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (wants_json()) {
        json_response(['success' => false, 'message' => 'Gunakan POST.'], 405);
    }
    redirect('cart.php');
}

requireCsrf();

$productId = (int) ($_POST['product_id'] ?? 0);
$action = $_POST['action'] ?? 'set';
$qty = (int) ($_POST['qty'] ?? 0);
$current = (int) (cart_raw()[$productId] ?? 0);

switch ($action) {
    case 'plus':
        $qty = $current + 1;
        break;
    case 'minus':
        $qty = $current - 1;
        break;
    case 'remove':
        $qty = 0;
        break;
    case 'set':
    default:
        $qty = max(0, $qty);
        break;
}

if ($action === 'remove') {
    cart_remove($productId);
    $result = ['success' => true, 'message' => 'Produk dihapus dari keranjang.', 'cart_count' => cart_count()];
} else {
    $result = cart_set_qty($pdo, $productId, $qty);
}

$cart = cart_items($pdo);
$shipping = shipping_cost($pdo, $cart['subtotal']);
$line = null;
foreach ($cart['items'] as $item) {
    if ((int) $item['product']['id'] === $productId) {
        $line = [
            'qty' => $item['qty'],
            'subtotal' => formatRupiah($item['subtotal']),
        ];
        break;
    }
}

if (wants_json()) {
    json_response([
        'success' => $result['success'],
        'message' => $result['message'],
        'cart_count' => cart_count(),
        'line' => $line,
        'subtotal' => formatRupiah($cart['subtotal']),
        'shipping' => formatRupiah($shipping),
        'total' => formatRupiah($cart['subtotal'] + $shipping),
        'empty' => $cart['items'] === [],
        'warnings' => $cart['warnings'],
    ], $result['success'] ? 200 : 422);
}

flash($result['message'], $result['success'] ? 'success' : 'error');
redirect('cart.php');
