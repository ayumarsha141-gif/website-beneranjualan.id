<?php
require_once __DIR__ . '/includes/config.php';
requireLogin('pesanan-saya.php');

$stmt = $pdo->prepare('SELECT * FROM pesanan WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([(int) $_SESSION['user_id']]);
$orders = $stmt->fetchAll();

$page_title = 'Pesanan Saya';
$meta_description = 'Riwayat pesanan akun Anda.';
$current_page = 'orders';
include __DIR__ . '/includes/header.php';
?>

<section class="page-hero page-hero-compact">
    <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="<?= e(site_url()) ?>">Beranda</a><span aria-hidden="true">/</span><span>Pesanan saya</span>
        </nav>
        <p class="eyebrow">Akun pelanggan</p>
        <h1 class="page-title">Pesanan <em>saya.</em></h1>
    </div>
</section>

<section class="section section-tight">
    <div class="container">
        <?php showFlash(); ?>
        <?php if (!$orders): ?>
            <div class="empty-state empty-state-lg">
                <svg class="icon icon-lg" aria-hidden="true"><use href="#i-package"></use></svg>
                <h2>Belum ada pesanan</h2>
                <p>Pesanan yang dibuat dengan akun ini akan tampil di sini.</p>
                <a class="btn btn-primary" href="<?= e(site_url('katalog.php')) ?>">Mulai belanja</a>
            </div>
        <?php else: ?>
            <div class="order-list">
                <?php foreach ($orders as $order): ?>
                <article class="order-card">
                    <div class="order-card-head">
                        <div>
                            <p class="order-number"><?= e($order['order_number']) ?></p>
                            <p class="order-date"><?= e(format_date($order['created_at'], 'd M Y H:i')) ?></p>
                        </div>
                        <div class="order-badges">
                            <span class="<?= e(badge_class($order['status'])) ?>"><?= e(order_status_label($order['status'])) ?></span>
                            <span class="<?= e(badge_class($order['payment_status'])) ?>"><?= e(payment_status_label($order['payment_status'])) ?></span>
                        </div>
                    </div>
                    <div class="order-card-body">
                        <div>
                            <p class="order-meta-label">Total</p>
                            <p class="order-total"><?= e(formatRupiah($order['total'])) ?></p>
                        </div>
                        <div>
                            <p class="order-meta-label">Penerima</p>
                            <p><?= e($order['nama']) ?></p>
                        </div>
                        <div>
                            <p class="order-meta-label">Metode</p>
                            <p><?= $order['metode_bayar'] === 'cod' ? 'Bayar di tempat' : 'Transfer bank' ?></p>
                        </div>
                        <a class="btn btn-outline btn-sm" href="<?= e(site_url('pesanan-detail.php?id=' . (int) $order['id'])) ?>">Lihat detail</a>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
