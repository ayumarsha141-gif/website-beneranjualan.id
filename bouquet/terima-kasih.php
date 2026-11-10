<?php
require_once __DIR__ . '/includes/config.php';

$orderNumber = clean_text($_GET['order'] ?? '', 40);
$token = clean_text($_GET['token'] ?? '', 64);
$order = null;
$items = [];

if ($orderNumber !== '' && $token !== '') {
    $stmt = $pdo->prepare('SELECT * FROM pesanan WHERE order_number = ? LIMIT 1');
    $stmt->execute([$orderNumber]);
    $candidate = $stmt->fetch();
    if ($candidate && hash_equals((string) $candidate['order_token'], $token)) {
        $order = $candidate;
        $stmt = $pdo->prepare('SELECT * FROM detail_pesanan WHERE pesanan_id = ? ORDER BY id');
        $stmt->execute([(int) $order['id']]);
        $items = $stmt->fetchAll();
    }
}

if (!$order) {
    http_response_code(404);
    $page_title = 'Pesanan tidak ditemukan';
    $meta_robots = 'noindex,follow';
    include __DIR__ . '/includes/header.php';
    echo '<section class="section"><div class="container"><div class="empty-state empty-state-lg">
        <svg class="icon icon-lg" aria-hidden="true"><use href="#i-package"></use></svg>
        <h1>Pesanan tidak ditemukan</h1>
        <p>Tautan pesanan tidak valid atau sudah tidak berlaku. Masukkan nomor pesanan dan email Anda untuk mencari pesanan.</p>
        <a class="btn btn-primary" href="' . e(site_url('lacak-pesanan.php')) . '">Lacak pesanan</a>
    </div></div></section>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$page_title = 'Pesanan ' . $order['order_number'];
$meta_description = 'Konfirmasi pesanan ' . $order['order_number'];
$meta_robots = 'noindex,nofollow';
$current_page = 'receipt';

$bankName = trim(setting($pdo, 'bank_name'));
$bankNumber = trim(setting($pdo, 'bank_account_number'));
$bankHolder = trim(setting($pdo, 'bank_account_name'));
$hasBank = $bankName !== '' && $bankNumber !== '';
$wa = whatsapp_url($pdo, 'Halo admin, saya ingin mengonfirmasi pembayaran pesanan ' . $order['order_number'] . ' sebesar ' . formatRupiah($order['total']) . '.');

$timeline = [
    'baru' => 'Pesanan diterima',
    'diproses' => 'Sedang disiapkan',
    'siap_dikirim' => 'Siap dikirim',
    'dikirim' => 'Dalam pengiriman',
    'selesai' => 'Selesai',
];
$statusKeys = array_keys($timeline);
$currentIndex = array_search($order['status'], $statusKeys, true);
$cancelled = $order['status'] === 'dibatalkan';

include __DIR__ . '/includes/header.php';
?>

<section class="page-hero page-hero-compact">
    <div class="container">
        <p class="eyebrow">Konfirmasi pesanan</p>
        <h1 class="page-title">Terima kasih, <em><?= e($order['nama']) ?>.</em></h1>
        <p class="page-lede">Pesanan Anda tercatat dengan nomor <strong><?= e($order['order_number']) ?></strong>. Simpan nomor dan tautan halaman ini untuk memantau status.</p>
    </div>
</section>

<section class="receipt-page">
    <div class="container receipt-layout">
        <div class="receipt-main">
            <?php if ($cancelled): ?>
                <div class="alert alert-error">Pesanan ini telah dibatalkan. Jika Anda sudah melakukan pembayaran, hubungi admin untuk proses pengembalian dana.</div>
            <?php endif; ?>

            <section class="receipt-card">
                <div class="receipt-card-head">
                    <div>
                        <p class="eyebrow">Status pesanan</p>
                        <h2><?= e(order_status_label($order['status'])) ?></h2>
                    </div>
                    <span class="<?= e(badge_class($order['status'])) ?>"><?= e(order_status_label($order['status'])) ?></span>
                </div>

                <?php if (!$cancelled): ?>
                <ol class="timeline">
                    <?php foreach ($timeline as $key => $label): $idx = array_search($key, $statusKeys, true); ?>
                        <li class="timeline-step <?= $currentIndex !== false && $idx <= $currentIndex ? 'is-done' : '' ?> <?= $currentIndex === $idx ? 'is-current' : '' ?>">
                            <span class="timeline-dot" aria-hidden="true"></span>
                            <span class="timeline-label"><?= e($label) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ol>
                <?php endif; ?>

                <?php if ($order['tracking_number']): ?>
                    <p class="receipt-tracking">Nomor resi / referensi pengiriman: <strong><?= e($order['tracking_number']) ?></strong></p>
                <?php endif; ?>
                <?php if (!empty($order['admin_note'])): ?>
                    <div class="receipt-note"><strong>Catatan admin:</strong> <?= nl2br(e($order['admin_note'])) ?></div>
                <?php endif; ?>
            </section>

            <section class="receipt-card">
                <div class="receipt-card-head">
                    <h2>Pembayaran</h2>
                    <span class="<?= e(badge_class($order['payment_status'])) ?>"><?= e(payment_status_label($order['payment_status'])) ?></span>
                </div>

                <div class="payment-detail">
                    <div class="payment-total">
                        <span>Total pesanan</span>
                        <strong><?= e(formatRupiah($order['total'])) ?></strong>
                    </div>
                    <?php if (trim((string) $order['catatan']) !== ''): ?>
                        <p class="muted"><strong>Catatan Anda:</strong> <?= nl2br(e((string) $order['catatan'])) ?></p>
                    <?php endif; ?>

                    <?php if ($order['payment_status'] === 'menunggu_pembayaran'): ?>
                        <?php if ($hasBank): ?>
                            <p>Silakan transfer ke rekening berikut, lalu konfirmasi ke admin:</p>
                            <dl class="bank-box">
                                <div><dt>Bank</dt><dd><?= e($bankName) ?></dd></div>
                                <div><dt>Nomor rekening</dt><dd><?= e($bankNumber) ?></dd></div>
                                <div><dt>Atas nama</dt><dd><?= e($bankHolder) ?></dd></div>
                                <div><dt>Nominal</dt><dd><?= e(formatRupiah($order['total'])) ?></dd></div>
                                <div><dt>Berita transfer</dt><dd><?= e($order['order_number']) ?></dd></div>
                            </dl>
                        <?php else: ?>
                            <p>Instruksi pembayaran akan dikirim oleh admin melalui kontak yang Anda berikan. Pesanan belum dianggap lunas sampai pembayaran diverifikasi.</p>
                        <?php endif; ?>
                        <p class="summary-note"><?= e(setting($pdo, 'payment_note', 'Pembayaran diverifikasi manual oleh admin.')) ?></p>
                        <?php if ($wa !== ''): ?>
                            <a class="btn btn-whatsapp" href="<?= e($wa) ?>" target="_blank" rel="noopener noreferrer">
                                <svg class="icon" aria-hidden="true"><use href="#i-whatsapp"></use></svg> Konfirmasi via WhatsApp
                            </a>
                        <?php endif; ?>
                    <?php elseif ($order['payment_status'] === 'dibayar'): ?>
                        <p class="payment-ok"><svg class="icon" aria-hidden="true"><use href="#i-check"></use></svg> Pembayaran telah diverifikasi admin<?= $order['payment_confirmed_at'] ? ' pada ' . e(format_date($order['payment_confirmed_at'], 'd M Y H:i')) : '' ?>.</p>
                    <?php endif; ?>
                </div>
            </section>

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
                    <?php if ((float) $order['discount'] > 0): ?><div><dt>Diskon</dt><dd>-<?= e(formatRupiah($order['discount'])) ?></dd></div><?php endif; ?>
                    <div class="summary-total"><dt>Total</dt><dd><?= e(formatRupiah($order['total'])) ?></dd></div>
                </dl>
            </section>
        </div>

        <aside class="receipt-side">
            <section class="receipt-card">
                <h2>Detail pengiriman</h2>
                <dl class="detail-list">
                    <div><dt>Nama</dt><dd><?= e($order['nama']) ?></dd></div>
                    <div><dt>Telepon</dt><dd><?= e($order['telepon']) ?></dd></div>
                    <div><dt>Email</dt><dd><?= e($order['email']) ?></dd></div>
                    <div><dt>Alamat</dt><dd><?= nl2br(e($order['alamat'])) ?></dd></div>
                    <?php if ($order['delivery_date']): ?><div><dt>Permintaan tanggal</dt><dd><?= e(format_date($order['delivery_date'], 'd M Y')) ?><?= $order['delivery_time'] ? ' · ' . e($order['delivery_time']) : '' ?></dd></div><?php endif; ?>
                    <div><dt>Metode bayar</dt><dd><?= $order['metode_bayar'] === 'cod' ? 'Bayar di tempat' : 'Transfer bank manual' ?></dd></div>
                    <div><dt>Dibuat</dt><dd><?= e(format_date($order['created_at'], 'd M Y H:i')) ?></dd></div>
                </dl>
            </section>

            <section class="receipt-card receipt-actions">
                <a class="btn btn-primary btn-block" href="<?= e(site_url('lacak-pesanan.php')) ?>">Lacak pesanan</a>
                <a class="btn btn-outline btn-block" href="<?= e(site_url('katalog.php')) ?>">Lanjut belanja</a>
                <button class="btn btn-ghost btn-block" type="button" onclick="window.print()">Cetak halaman</button>
            </section>
        </aside>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
