<?php
require_once __DIR__ . '/includes/config.php';

$page_title = 'Keranjang Belanja';
$meta_description = 'Ringkasan keranjang belanja Beneranjualan.id.';
$current_page = 'cart';

$cart = cart_items($pdo);
$shipping = shipping_cost($pdo, $cart['subtotal']);
$total = $cart['subtotal'] + $shipping;
$flat = (float) setting($pdo, 'shipping_flat', '0');
$shipping_note = trim(setting($pdo, 'shipping_note'));

include __DIR__ . '/includes/header.php';
?>

<section class="page-hero page-hero-compact">
    <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="<?= e(site_url()) ?>">Beranda</a><span aria-hidden="true">/</span><span>Keranjang</span>
        </nav>
        <h1 class="page-title">Keranjang <em>Anda.</em></h1>
    </div>
</section>

<section class="cart-page">
    <div class="container">
        <?php showFlash(); ?>
        <?php foreach ($cart['warnings'] as $warning): ?>
            <div class="alert alert-info" role="status"><?= e($warning) ?></div>
        <?php endforeach; ?>

        <?php if (!$cart['items']): ?>
        <div class="empty-state empty-state-lg">
            <svg class="icon icon-lg" aria-hidden="true"><use href="#i-cart"></use></svg>
            <h2>Keranjang masih kosong</h2>
            <p>Pilih buket dari katalog untuk memulai pesanan.</p>
            <a class="btn btn-primary btn-lg" href="<?= e(site_url('katalog.php')) ?>">Jelajahi katalog</a>
        </div>
        <?php else: ?>
        <div class="cart-layout">
            <div class="cart-items">
                <?php foreach ($cart['items'] as $item): $p = $item['product']; ?>
                <article class="cart-item" data-product-id="<?= (int) $p['id'] ?>">
                    <a class="cart-item-image" href="<?= e(site_url('produk.php?id=' . (int) $p['id'])) ?>">
                        <?php $cartSrcset = product_image_srcset($p['gambar']); ?>
                        <picture>
                            <?php if ($cartSrcset !== ''): ?><source type="image/webp" srcset="<?= e($cartSrcset) ?>" sizes="110px"><?php endif; ?>
                            <img src="<?= e(product_image_url($p['gambar'])) ?>" alt="<?= e($p['nama']) ?>" width="180" height="225" loading="lazy">
                        </picture>
                    </a>
                    <div class="cart-item-info">
                        <p class="cart-item-category"><?= e($p['kategori']) ?></p>
                        <h2 class="cart-item-name"><a href="<?= e(site_url('produk.php?id=' . (int) $p['id'])) ?>"><?= e($p['nama']) ?></a></h2>
                        <p class="cart-item-unit"><?= e(formatRupiah($p['harga'])) ?> / unit</p>
                        <p class="cart-item-stock">Stok tersedia: <?= (int) $p['stok'] ?></p>
                    </div>
                    <div class="cart-item-controls">
                        <form action="<?= e(site_url('cart_update.php')) ?>" method="post" class="cart-qty-form" data-cart-form>
                            <?= csrfField() ?>
                            <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
                            <div class="qty-stepper qty-stepper-sm">
                                <button class="qty-btn" type="submit" name="action" value="minus" aria-label="Kurangi jumlah <?= e($p['nama']) ?>">
                                    <svg class="icon" aria-hidden="true"><use href="#i-minus"></use></svg>
                                </button>
                                <input class="qty-input" type="number" name="qty" value="<?= (int) $item['qty'] ?>" min="1" max="<?= (int) $p['stok'] ?>" aria-label="Jumlah <?= e($p['nama']) ?>" data-qty-input>
                                <button class="qty-btn" type="submit" name="action" value="plus" aria-label="Tambah jumlah <?= e($p['nama']) ?>">
                                    <svg class="icon" aria-hidden="true"><use href="#i-plus"></use></svg>
                                </button>
                            </div>
                            <button class="cart-remove" type="submit" name="action" value="remove" aria-label="Hapus <?= e($p['nama']) ?> dari keranjang">
                                <svg class="icon icon-xs" aria-hidden="true"><use href="#i-trash"></use></svg> Hapus
                            </button>
                        </form>
                        <p class="cart-line-subtotal" data-line-subtotal><?= e(formatRupiah($item['subtotal'])) ?></p>
                    </div>
                </article>
                <?php endforeach; ?>

                <a class="text-link cart-continue" href="<?= e(site_url('katalog.php')) ?>">
                    <svg class="icon icon-xs" aria-hidden="true"><use href="#i-arrow-left"></use></svg> Lanjut belanja
                </a>
            </div>

            <aside class="cart-summary" aria-labelledby="summaryTitle">
                <h2 id="summaryTitle" class="summary-title">Ringkasan</h2>
                <dl class="summary-list">
                    <div>
                        <dt>Subtotal <span class="summary-count" data-cart-count-label><?= $cart['count'] ?> item</span></dt>
                        <dd data-cart-subtotal><?= e(formatRupiah($cart['subtotal'])) ?></dd>
                    </div>
                    <div>
                        <dt>Ongkos kirim</dt>
                        <dd data-cart-shipping>
                            <?php if ($flat > 0): ?>
                                <?= $shipping > 0 ? e(formatRupiah($shipping)) : 'Gratis' ?>
                            <?php else: ?>
                                Dikonfirmasi admin
                            <?php endif; ?>
                        </dd>
                    </div>
                </dl>
                <div class="summary-total">
                    <span>Total saat ini</span>
                    <strong data-cart-total><?= e(formatRupiah($total)) ?></strong>
                </div>
                <?php if ($shipping_note !== ''): ?>
                    <p class="summary-note"><?= e($shipping_note) ?></p>
                <?php endif; ?>
                <a class="btn btn-primary btn-block btn-lg" href="<?= e(site_url('checkout.php')) ?>">
                    Lanjut ke checkout
                    <svg class="icon" aria-hidden="true"><use href="#i-arrow-right"></use></svg>
                </a>
                <p class="summary-secure"><svg class="icon icon-xs" aria-hidden="true"><use href="#i-lock"></use></svg> Total dihitung ulang dari database saat checkout.</p>
            </aside>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
