<?php
require_once __DIR__ . '/includes/config.php';

$cart = cart_items($pdo);
if (!$cart['items']) {
    redirect('cart.php', 'Keranjang Anda kosong. Pilih buket terlebih dahulu.', 'error');
}

if (empty($_SESSION['checkout_token'])) {
    $_SESSION['checkout_token'] = bin2hex(random_bytes(32));
}

$shipping = shipping_cost($pdo, $cart['subtotal']);
$total = $cart['subtotal'] + $shipping;
$flat = (float) setting($pdo, 'shipping_flat', '0');
$shipping_note = trim(setting($pdo, 'shipping_note'));
$cod_enabled = setting($pdo, 'cod_enabled', '0') === '1';

$prefill = ['nama' => '', 'email' => '', 'telepon' => '', 'alamat' => ''];
if (isLoggedIn()) {
    $stmt = $pdo->prepare('SELECT nama_lengkap, email, telepon, alamat FROM users WHERE id = ?');
    $stmt->execute([(int) $_SESSION['user_id']]);
    $u = $stmt->fetch();
    if ($u) {
        $prefill = [
            'nama' => $u['nama_lengkap'],
            'email' => $u['email'],
            'telepon' => $u['telepon'] ?? '',
            'alamat' => $u['alamat'] ?? '',
        ];
    }
}

$page_title = 'Checkout';
$meta_description = 'Selesaikan pesanan buket Anda.';
$current_page = 'checkout';

include __DIR__ . '/includes/header.php';
?>

<section class="page-hero page-hero-compact">
    <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="<?= e(site_url()) ?>">Beranda</a><span aria-hidden="true">/</span>
            <a href="<?= e(site_url('cart.php')) ?>">Keranjang</a><span aria-hidden="true">/</span><span>Checkout</span>
        </nav>
        <h1 class="page-title">Selesaikan <em>pesanan.</em></h1>
        <p class="page-lede">Tiga langkah singkat untuk data pemesan, pengantaran, dan pembayaran. Total dihitung ulang dari database saat pesanan dibuat.</p>
    </div>
</section>

<section class="checkout-page">
    <div class="container">
        <?php showFlash(); ?>

        <form action="<?= e(site_url('proses_checkout.php')) ?>" method="post" class="checkout-layout" data-checkout-form>
            <?= csrfField() ?>
            <input type="hidden" name="checkout_token" value="<?= e($_SESSION['checkout_token']) ?>">

            <div class="checkout-main">
                <ol class="checkout-progress" aria-label="Tahap checkout" data-checkout-progress>
                    <li class="is-current" aria-current="step"><span>01</span><span>Kontak</span></li>
                    <li><span>02</span><span>Pengantaran</span></li>
                    <li><span>03</span><span>Pesan & bayar</span></li>
                </ol>

                <section class="form-card checkout-step" data-checkout-step="0" aria-labelledby="recipientTitle">
                    <div class="form-card-head">
                        <span class="step-kicker">01</span>
                        <h2 id="recipientTitle">Data pemesan</h2>
                    </div>
                    <div class="form-grid">
                        <div class="field">
                            <label for="nama">Nama lengkap <span aria-hidden="true">*</span></label>
                            <input id="nama" type="text" name="nama" required maxlength="120" autocomplete="name" value="<?= e($prefill['nama']) ?>">
                        </div>
                        <div class="field">
                            <label for="telepon">Nomor telepon / WhatsApp <span aria-hidden="true">*</span></label>
                            <input id="telepon" type="tel" name="telepon" required maxlength="30" autocomplete="tel" inputmode="tel" placeholder="08xxxxxxxxxx" value="<?= e($prefill['telepon']) ?>">
                            <small>Digunakan admin untuk konfirmasi pesanan.</small>
                        </div>
                        <div class="field field-full">
                            <label for="email">Email <span aria-hidden="true">*</span></label>
                            <input id="email" type="email" name="email" required maxlength="190" autocomplete="email" value="<?= e($prefill['email']) ?>">
                            <small>Untuk menerima nomor pesanan dan tautan pelacakan.</small>
                        </div>
                    </div>
                    <div class="checkout-step-actions">
                        <span class="muted">Langkah 1 dari 3</span>
                        <button class="btn btn-primary" type="button" data-checkout-next>Lanjut ke pengantaran <span aria-hidden="true">→</span></button>
                    </div>
                </section>

                <section class="form-card checkout-step" data-checkout-step="1" aria-labelledby="deliveryTitle">
                    <div class="form-card-head">
                        <span class="step-kicker">02</span>
                        <h2 id="deliveryTitle">Penerima & pengantaran</h2>
                    </div>
                    <div class="form-grid">
                        <div class="field field-full">
                            <label for="alamat">Alamat lengkap pengiriman <span aria-hidden="true">*</span></label>
                            <textarea id="alamat" name="alamat" rows="4" required maxlength="1000" autocomplete="street-address" placeholder="Nama jalan, nomor rumah, kelurahan, kecamatan, kota, kode pos"><?= e($prefill['alamat']) ?></textarea>
                        </div>
                        <div class="field">
                            <label for="delivery_date">Tanggal pengiriman yang diinginkan</label>
                            <input id="delivery_date" type="date" name="delivery_date" min="<?= e(date('Y-m-d')) ?>" max="<?= e(date('Y-m-d', strtotime('+180 days'))) ?>">
                            <small>Permintaan tanggal, bukan jaminan. Admin akan mengonfirmasi ketersediaan jadwal.</small>
                        </div>
                        <div class="field">
                            <label for="delivery_time">Perkiraan jam</label>
                            <input id="delivery_time" type="text" name="delivery_time" maxlength="40" placeholder="Contoh: pagi / 10.00–12.00">
                            <small>Opsional. Jam pasti dikonfirmasi admin.</small>
                        </div>
                    </div>
                    <div class="checkout-step-actions">
                        <button class="btn btn-ghost" type="button" data-checkout-back>Kembali</button>
                        <span class="muted">Langkah 2 dari 3</span>
                        <button class="btn btn-primary" type="button" data-checkout-next>Lanjut ke pesan & bayar <span aria-hidden="true">→</span></button>
                    </div>
                </section>

                <section class="form-card checkout-step" data-checkout-step="2" aria-labelledby="paymentTitle">
                    <div class="form-card-head">
                        <span class="step-kicker">03</span>
                        <h2 id="paymentTitle">Pesan personal & pembayaran</h2>
                    </div>
                    <div class="form-grid">
                        <div class="field field-full">
                            <label for="catatan">Pesan kartu atau catatan untuk admin</label>
                            <textarea id="catatan" name="catatan" rows="3" maxlength="1000" placeholder="Tuliskan pesan kartu atau instruksi yang ingin disampaikan."></textarea>
                        </div>
                    </div>
                    <div class="payment-options">
                        <label class="payment-option">
                            <input type="radio" name="metode_bayar" value="transfer" checked>
                            <span class="payment-option-body">
                                <strong>Transfer bank manual</strong>
                                <span>Pesanan berstatus menunggu pembayaran sampai admin memverifikasi transfer.</span>
                                <?php if (trim(setting($pdo, 'bank_account_number')) !== ''): ?>
                                    <span class="payment-bank">
                                        <?= e(setting($pdo, 'bank_name')) ?> ·
                                        <?= e(setting($pdo, 'bank_account_number')) ?> ·
                                        <?= e(setting($pdo, 'bank_account_name')) ?>
                                    </span>
                                <?php endif; ?>
                            </span>
                        </label>
                        <?php if ($cod_enabled): ?>
                        <label class="payment-option">
                            <input type="radio" name="metode_bayar" value="cod">
                            <span class="payment-option-body">
                                <strong>Bayar di tempat (COD)</strong>
                                <span>Pembayaran diterima saat pesanan diserahkan. Admin mengonfirmasi ketersediaan layanan COD.</span>
                            </span>
                        </label>
                        <?php endif; ?>
                    </div>
                    <p class="summary-note"><?= e(setting($pdo, 'payment_note', 'Pembayaran diverifikasi manual oleh admin.')) ?></p>
                    <div class="checkout-step-actions">
                        <button class="btn btn-ghost" type="button" data-checkout-back>Kembali</button>
                        <span class="muted">Langkah 3 dari 3</span>
                        <span></span>
                    </div>
                </section>
            </div>

            <aside class="checkout-summary" aria-labelledby="orderSummaryTitle">
                <details class="checkout-summary-details" data-checkout-summary open>
                <summary id="orderSummaryTitle" class="summary-title">
                    Pesanan Anda <span class="checkout-summary-mobile-total"><?= e(formatRupiah($total)) ?></span>
                </summary>
                <div class="checkout-summary-content">
                <div class="checkout-items">
                    <?php foreach ($cart['items'] as $item): $p = $item['product']; ?>
                    <div class="checkout-item">
                        <?php $checkoutSrcset = product_image_srcset($p['gambar']); ?>
                        <picture>
                            <?php if ($checkoutSrcset !== ''): ?><source type="image/webp" srcset="<?= e($checkoutSrcset) ?>" sizes="55px"><?php endif; ?>
                            <img src="<?= e(product_image_url($p['gambar'])) ?>" alt="<?= e($p['nama']) ?>" width="96" height="120" loading="lazy">
                        </picture>
                        <div>
                            <p class="checkout-item-name"><?= e($p['nama']) ?></p>
                            <p class="checkout-item-meta"><?= (int) $item['qty'] ?> × <?= e(formatRupiah($p['harga'])) ?></p>
                        </div>
                        <span class="checkout-item-subtotal"><?= e(formatRupiah($item['subtotal'])) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <dl class="summary-list">
                    <div><dt>Subtotal</dt><dd><?= e(formatRupiah($cart['subtotal'])) ?></dd></div>
                    <div>
                        <dt>Ongkos kirim</dt>
                        <dd><?php if ($flat > 0): ?><?= $shipping > 0 ? e(formatRupiah($shipping)) : 'Gratis' ?><?php else: ?>Dikonfirmasi admin<?php endif; ?></dd>
                    </div>
                </dl>
                <div class="summary-total">
                    <span>Total saat ini</span>
                    <strong><?= e(formatRupiah($total)) ?></strong>
                </div>
                <?php if ($shipping_note !== ''): ?>
                    <p class="summary-note"><?= e($shipping_note) ?></p>
                <?php endif; ?>

                <label class="check-row terms-check">
                    <input type="checkbox" name="agree" value="1" required>
                    <span>Saya telah memeriksa data pesanan dan menyetujui <a href="<?= e(site_url('kebijakan.php')) ?>" target="_blank" rel="noopener">kebijakan toko</a>.</span>
                </label>

                <button class="btn btn-primary btn-block btn-lg" type="submit" data-submit-checkout>
                    Buat pesanan
                    <svg class="icon" aria-hidden="true"><use href="#i-arrow-right"></use></svg>
                </button>
                <p class="summary-secure"><svg class="icon icon-xs" aria-hidden="true"><use href="#i-lock"></use></svg> Stok dikunci saat pesanan dibuat. Total tidak diambil dari browser.</p>
                </div>
                </details>
            </aside>
        </form>

        <a class="text-link checkout-back" href="<?= e(site_url('cart.php')) ?>">
            <svg class="icon icon-xs" aria-hidden="true"><use href="#i-arrow-left"></use></svg> Kembali ke keranjang
        </a>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
