<?php
require_once __DIR__ . '/includes/config.php';

function safe_redirect_path(string $path): string
{
    $path = trim($path);
    if ($path === '' || !str_starts_with($path, '/') || str_starts_with($path, '//') || str_contains($path, "\n")) {
        return '/cart.php';
    }
    return $path;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (wants_json()) {
        json_response(['success' => false, 'message' => 'Gunakan POST.'], 405);
    }
    redirect('cart.php');
}

requireCsrf();

$productId = (int) ($_POST['product_id'] ?? 0);
$qty = max(1, (int) ($_POST['qty'] ?? 1));
$redirectTo = safe_redirect_path($_POST['redirect_to'] ?? '/cart.php');

$result = cart_add($pdo, $productId, $qty);

if (wants_json()) {
    $cart = cart_items($pdo);
    $shipping = shipping_cost($pdo, $cart['subtotal']);
    json_response([
        'success' => $result['success'],
        'message' => $result['message'],
        'cart_count' => cart_count(),
        'subtotal' => formatRupiah($cart['subtotal']),
        'shipping' => formatRupiah($shipping),
        'total' => formatRupiah($cart['subtotal'] + $shipping),
    ], $result['success'] ? 200 : 422);
}

flash($result['message'], $result['success'] ? 'success' : 'error');
if (!$result['success']) {
    redirect('produk.php?id=' . $productId);
}
redirect(ltrim($redirectTo, '/'));
