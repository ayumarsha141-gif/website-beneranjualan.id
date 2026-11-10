<?php
/**
 * Transactional checkout core.
 * - Prices and totals are derived ONLY from the database.
 * - Product rows are locked with SELECT ... FOR UPDATE.
 * - Stock is decremented conditionally inside the same transaction.
 * - Duplicate submits are idempotent through checkout_token.
 */
require_once __DIR__ . '/includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('checkout.php');
}

requireCsrf();

$checkoutToken = (string) ($_POST['checkout_token'] ?? '');
if (!preg_match('/^[a-f0-9]{64}$/', $checkoutToken)) {
    redirect('checkout.php', 'Sesi checkout tidak valid. Muat ulang halaman.', 'error');
}

// Idempotency: if this token already produced an order, do not create another.
$stmt = $pdo->prepare('SELECT order_number, order_token FROM pesanan WHERE checkout_token = ? LIMIT 1');
$stmt->execute([$checkoutToken]);
$existing = $stmt->fetch();
if ($existing) {
    unset($_SESSION['cart'], $_SESSION['checkout_token']);
    redirect('terima-kasih.php?order=' . urlencode($existing['order_number']) . '&token=' . urlencode($existing['order_token']));
}

if (empty($_SESSION['checkout_token']) || !hash_equals((string) $_SESSION['checkout_token'], $checkoutToken)) {
    redirect('checkout.php', 'Sesi checkout kedaluwarsa. Muat ulang halaman lalu coba lagi.', 'error');
}

$rawCart = cart_raw();
if ($rawCart === []) {
    redirect('cart.php', 'Keranjang Anda kosong.', 'error');
}

// ---- Validate customer input ---------------------------------------------
$nama = clean_text($_POST['nama'] ?? '', 120);
$email = mb_strtolower(clean_text($_POST['email'] ?? '', 190));
$telepon = clean_text($_POST['telepon'] ?? '', 30);
$alamat = clean_text($_POST['alamat'] ?? '', 1000);
$catatan = clean_text($_POST['catatan'] ?? '', 1000);
$deliveryDate = clean_text($_POST['delivery_date'] ?? '', 10);
$deliveryTime = clean_text($_POST['delivery_time'] ?? '', 40);
$metodeBayar = clean_text($_POST['metode_bayar'] ?? 'transfer', 40);

$errors = [];
if (mb_strlen($nama) < 2) {
    $errors[] = 'Nama lengkap wajib diisi.';
}
if (!valid_email($email)) {
    $errors[] = 'Format email tidak valid.';
}
if (!valid_phone($telepon)) {
    $errors[] = 'Nomor telepon tidak valid (9–15 digit).';
}
if (mb_strlen($alamat) < 10) {
    $errors[] = 'Alamat pengiriman terlalu singkat. Mohon isi alamat lengkap.';
}
if ($deliveryDate !== '') {
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $deliveryDate);
    $today = new DateTimeImmutable('today');
    if (!$d || $d < $today || $d > $today->modify('+180 days')) {
        $errors[] = 'Tanggal pengiriman tidak valid.';
    }
}
$allowedMethods = ['transfer'];
if (setting($pdo, 'cod_enabled', '0') === '1') {
    $allowedMethods[] = 'cod';
}
if (!in_array($metodeBayar, $allowedMethods, true)) {
    $errors[] = 'Metode pembayaran tidak tersedia.';
}
if (empty($_POST['agree'])) {
    $errors[] = 'Centang persetujuan kebijakan sebelum membuat pesanan.';
}

if ($errors) {
    flash(implode(' ', $errors), 'error');
    redirect('checkout.php');
}

$userId = isLoggedIn() ? (int) $_SESSION['user_id'] : null;

try {
    $pdo->beginTransaction();

    // Lock every product row involved in this cart.
    $ids = array_values(array_filter(array_map('intval', array_keys($rawCart)), fn ($id) => $id > 0));
    if ($ids === []) {
        throw new DomainException('Keranjang tidak berisi produk yang valid.');
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $lockStmt = $pdo->prepare("SELECT * FROM produk WHERE id IN ($placeholders) FOR UPDATE");
    $lockStmt->execute($ids);
    $locked = [];
    foreach ($lockStmt->fetchAll() as $row) {
        $locked[(int) $row['id']] = $row;
    }

    $subtotal = 0.0;
    $lines = [];
    foreach ($ids as $id) {
        if (!isset($locked[$id])) {
            throw new DomainException('Salah satu produk di keranjang sudah tidak tersedia.');
        }
        $p = $locked[$id];
        $qty = max(1, (int) ($rawCart[$id] ?? 1));
        if ((int) $p['is_active'] !== 1 || (int) $p['is_demo'] === 1) {
            throw new DomainException($p['nama'] . ' sudah tidak dijual. Hapus dari keranjang.');
        }
        if (product_requires_whatsapp($p)) {
            throw new DomainException($p['nama'] . ' memerlukan konfirmasi harga/ketersediaan via WhatsApp dan tidak dapat menggunakan checkout harga tetap.');
        }
        if ((int) $p['stok'] < $qty) {
            throw new DomainException('Stok ' . $p['nama'] . ' tidak mencukupi (tersisa ' . (int) $p['stok'] . '). Perbarui keranjang.');
        }
        $price = (float) $p['harga'];
        $lineSubtotal = round($price * $qty, 2);
        $lines[] = ['product' => $p, 'qty' => $qty, 'price' => $price, 'subtotal' => $lineSubtotal];
        $subtotal += $lineSubtotal;
    }

    $shipping = shipping_cost($pdo, $subtotal);
    $discount = 0.0;
    $total = round($subtotal + $shipping - $discount, 2);

    $orderNumber = generate_order_number($pdo);
    $orderToken = order_token();

    $insert = $pdo->prepare(
        'INSERT INTO pesanan
            (order_number, order_token, checkout_token, user_id, nama, email, telepon, alamat,
             delivery_date, delivery_time, subtotal, shipping_cost, discount, total,
             metode_bayar, catatan, status, payment_status, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
    );
    $insert->execute([
        $orderNumber,
        $orderToken,
        $checkoutToken,
        $userId,
        $nama,
        $email,
        $telepon,
        $alamat,
        $deliveryDate !== '' ? $deliveryDate : null,
        $deliveryTime !== '' ? $deliveryTime : null,
        $subtotal,
        $shipping,
        $discount,
        $total,
        $metodeBayar,
        $catatan,
        'baru',
        'menunggu_pembayaran',
    ]);
    $orderId = (int) $pdo->lastInsertId();

    $detailStmt = $pdo->prepare(
        'INSERT INTO detail_pesanan (pesanan_id, produk_id, nama_produk, gambar_produk, qty, harga, subtotal)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stockStmt = $pdo->prepare('UPDATE produk SET stok = stok - ? WHERE id = ? AND stok >= ?');
    $movementStmt = $pdo->prepare(
        'INSERT INTO stock_movements (produk_id, qty_change, movement_type, reference_type, reference_id, note, created_by, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
    );

    foreach ($lines as $line) {
        $p = $line['product'];
        $detailStmt->execute([
            $orderId,
            (int) $p['id'],
            $p['nama'],
            $p['gambar'],
            $line['qty'],
            $line['price'],
            $line['subtotal'],
        ]);

        $stockStmt->execute([$line['qty'], (int) $p['id'], $line['qty']]);
        if ($stockStmt->rowCount() !== 1) {
            throw new DomainException('Stok ' . $p['nama'] . ' berubah saat checkout. Silakan coba lagi.');
        }

        $movementStmt->execute([
            (int) $p['id'],
            -$line['qty'],
            'sale',
            'order',
            $orderId,
            'Penjualan ' . $orderNumber,
            $userId,
        ]);
    }

    $history = $pdo->prepare(
        'INSERT INTO order_status_history (pesanan_id, field_name, old_value, new_value, note, changed_by, created_at)
         VALUES (?, ?, ?, ?, ?, ?, NOW())'
    );
    $history->execute([$orderId, 'status', null, 'baru', 'Pesanan dibuat', $userId]);
    $history->execute([$orderId, 'payment_status', null, 'menunggu_pembayaran', 'Menunggu verifikasi pembayaran', $userId]);

    $pdo->commit();

    unset($_SESSION['cart'], $_SESSION['checkout_token']);
    $_SESSION['checkout_token'] = bin2hex(random_bytes(32));
    $_SESSION['last_order_number'] = $orderNumber;
    $_SESSION['last_order_token'] = $orderToken;

    redirect('terima-kasih.php?order=' . urlencode($orderNumber) . '&token=' . urlencode($orderToken), 'Pesanan berhasil dibuat. Simpan nomor pesanan Anda.', 'success');
} catch (DomainException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash($e->getMessage(), 'error');
    redirect('cart.php');
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // A unique checkout_token collision can happen if two identical submits race.
    if ($e->getCode() === '23000') {
        $stmt = $pdo->prepare('SELECT order_number, order_token FROM pesanan WHERE checkout_token = ? LIMIT 1');
        $stmt->execute([$checkoutToken]);
        $existing = $stmt->fetch();
        if ($existing) {
            unset($_SESSION['cart'], $_SESSION['checkout_token']);
            redirect('terima-kasih.php?order=' . urlencode($existing['order_number']) . '&token=' . urlencode($existing['order_token']));
        }
    }
    error_log('Checkout failed: ' . $e->getMessage());
    flash('Pesanan gagal dibuat karena kendala sistem. Tidak ada stok yang dikurangi. Silakan coba lagi.', 'error');
    redirect('checkout.php');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Checkout error: ' . $e->getMessage());
    flash('Pesanan gagal dibuat. Tidak ada stok yang dikurangi. Silakan coba lagi atau hubungi admin.', 'error');
    redirect('checkout.php');
}
