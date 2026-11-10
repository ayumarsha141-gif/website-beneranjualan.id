<?php
require_once __DIR__ . '/includes/config.php';
requireLogin('pesanan-saya.php');

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM pesanan WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->execute([$id, (int) $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
    $page_title = 'Pesanan tidak ditemukan';
    include __DIR__ . '/includes/header.php';
    echo '<section class="section"><div class="container"><div class="empty-state empty-state-lg"><h1>Pesanan tidak ditemukan</h1><p>Pesanan ini tidak terhubung dengan akun Anda.</p><a class="btn btn-primary" href="' . e(site_url('pesanan-saya.php')) . '">Kembali</a></div></div></section>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM detail_pesanan WHERE pesanan_id = ? ORDER BY id');
$stmt->execute([$id]);
$items = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT * FROM order_status_history WHERE pesanan_id = ? ORDER BY created_at, id');
$stmt->execute([$id]);
$history = $stmt->fetchAll();

$page_title = 'Detail Pesanan ' . $order['order_number'];
$meta_robots = 'noindex,nofollow';
$current_page = 'orders';
include __DIR__ . '/includes/header.php';
?>

<section class="page-hero page-hero-compact">
    <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="<?= e(site_url('pesanan-saya.php')) ?>">Pesanan saya</a><span aria-hidden="true">/</span><span><?= e($order['order_number']) ?></span>
        </nav>
        <div class="catalog-heading">
            <div>
                <p class="eyebrow"><?= e(format_date($order['created_at'], 'd M Y H:i')) ?></p>
                <h1 class="page-title"><?= e($order['order_number']) ?></h1>
            </div>
            <div class="order-badges">
                <span class="<?= e(badge_class($order['status'])) ?>"><?= e(order_status_label($order['status'])) ?></span>
                <span class="<?= e(badge_class($order['payment_status'])) ?>"><?= e(payment_status_label($order['payment_status'])) ?></span>
            </div>
        </div>
    </div>
</section>

<section class="section section-tight">
    <div class="container receipt-layout">
        <div class="receipt-main">
            <section class="receipt-card">
                <h2>Produk</h2>
                <div class="receipt-items">
                    <?php foreach ($items as $item): ?>
                    <div class="receipt-item">
                        <?php $receiptSrcset = product_image_srcset($item['gambar_produk']); ?>
                        <picture>
                            <?php if ($receiptSrcset !== ''): ?><source type="image/webp" srcset="<?= e($receiptSrcset) ?>" sizes="55px"><?php endif; ?>
                            <img src="<?= e(product_image_url($item['gambar_produk'])) ?>" alt="<?= e($item['nama_produk']) ?>" width="80" height="100" loading="lazy">
                        </picture>
                        <div>
                            <p class="receipt-item-name"><?= e($item['nama_produk']) ?></p>
                            <p class="receipt-item-meta"><?= (int) $item['qty'] ?> × <?= e(formatRupiah($item['harga'])) ?></p>
                        </div>
                        <span><?= e(formatRupiah($item['subtotal'])) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <dl class="summary-list receipt-totals">
                    <div><dt>Subtotal</dt><dd><?= e(formatRupiah($order['subtotal'])) ?></dd></div>
                    <div><dt>Ongkos kirim</dt><dd><?= (float) $order['shipping_cost'] > 0 ? e(formatRupiah($order['shipping_cost'])) : 'Dikonfirmasi admin' ?></dd></div>
                    <div class="summary-total"><dt>Total</dt><dd><?= e(formatRupiah($order['total'])) ?></dd></div>
                </dl>
            </section>

            <section class="receipt-card">
                <h2>Riwayat status</h2>
                <?php if ($history): ?>
                <ul class="history-list">
                    <?php foreach ($history as $row): ?>
                    <li>
                        <span class="history-time"><?= e(format_date($row['created_at'], 'd M Y H:i')) ?></span>
                        <span class="history-text">
                            <?php if ($row['field_name'] === 'payment_status'): ?>
                                Pembayaran: <?= e(payment_status_label((string) $row['new_value'])) ?>
                            <?php else: ?>
                                Status: <?= e(order_status_label((string) $row['new_value'])) ?>
                            <?php endif; ?>
                            <?php if ($row['note']): ?><small><?= e($row['note']) ?></small><?php endif; ?>
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                    <p class="muted">Belum ada riwayat status.</p>
                <?php endif; ?>
            </section>
        </div>

        <aside class="receipt-side">
            <section class="receipt-card">
                <h2>Pengiriman</h2>
                <dl class="detail-list">
                    <div><dt>Nama</dt><dd><?= e($order['nama']) ?></dd></div>
                    <div><dt>Telepon</dt><dd><?= e($order['telepon']) ?></dd></div>
                    <div><dt>Email</dt><dd><?= e($order['email']) ?></dd></div>
                    <div><dt>Alamat</dt><dd><?= nl2br(e($order['alamat'])) ?></dd></div>
                    <?php if ($order['delivery_date']): ?><div><dt>Permintaan tanggal</dt><dd><?= e(format_date($order['delivery_date'], 'd M Y')) ?></dd></div><?php endif; ?>
                    <?php if ($order['tracking_number']): ?><div><dt>Resi</dt><dd><?= e($order['tracking_number']) ?></dd></div><?php endif; ?>
                </dl>
            </section>
            <section class="receipt-card receipt-actions">
                <a class="btn btn-primary btn-block" href="<?= e(site_url('terima-kasih.php?order=' . urlencode($order['order_number']) . '&token=' . urlencode($order['order_token']))) ?>">Halaman konfirmasi</a>
                <a class="btn btn-outline btn-block" href="<?= e(site_url('pesanan-saya.php')) ?>">Kembali</a>
            </section>
        </aside>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
