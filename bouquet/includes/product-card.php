<?php
/** Reusable product card. Expects $product (array). */
$card_stock = (int) $product['stok'];
$card_quote = product_requires_whatsapp($product);
$card_whatsapp = $card_quote ? product_inquiry_url($pdo, $product) : '';
$card_low = (int) setting($pdo, 'low_stock_threshold', '3');
$card_category_slug = '';
try {
    $catStmt = $pdo->prepare('SELECT slug FROM categories WHERE id = ?');
    $catStmt->execute([$product['category_id'] ?? 0]);
    $card_category_slug = (string) ($catStmt->fetchColumn() ?: '');
} catch (Throwable $e) {
    $card_category_slug = '';
}
$card_current = $_SERVER['REQUEST_URI'] ?? 'index.php';
$card_current = preg_replace('#^' . preg_quote(parse_url(SITE_URL, PHP_URL_PATH) ?: '', '#') . '#', '', $card_current) ?: 'index.php';
$card_current = '/' . ltrim($card_current, '/');
?>
<article class="product-card reveal" data-product-card>
    <a class="product-media" href="<?= e(site_url('produk.php?id=' . (int) $product['id'])) ?>">
        <?php $srcset = product_image_srcset($product['gambar']); ?>
        <picture>
            <?php if ($srcset !== ''): ?><source type="image/webp" srcset="<?= e($srcset) ?>" sizes="(max-width: 760px) 46vw, (max-width: 1100px) 30vw, 23vw"><?php endif; ?>
            <img src="<?= e(product_image_url($product['gambar'])) ?>" alt="<?= e($product['nama']) ?>" width="800" height="1000" loading="lazy" decoding="async">
        </picture>
        <span class="product-badges">
            <?php if ((int) $product['is_demo'] === 1): ?><span class="badge badge-demo">Demo</span><?php endif; ?>
            <?php if ($card_quote): ?>
                <span class="badge badge-contact">Konfirmasi via WhatsApp</span>
            <?php elseif ($card_stock <= 0): ?>
                <span class="badge badge-out">Habis</span>
            <?php elseif ($card_stock <= $card_low): ?>
                <span class="badge badge-low">Sisa <?= $card_stock ?></span>
            <?php endif; ?>
        </span>
    </a>
    <div class="product-body">
        <div class="product-meta">
            <?php if ($card_category_slug !== ''): ?>
                <a class="product-category" href="<?= e(site_url('katalog.php?kategori=' . urlencode($card_category_slug))) ?>"><?= e($product['kategori']) ?></a>
            <?php else: ?>
                <span class="product-category"><?= e($product['kategori']) ?></span>
            <?php endif; ?>
        </div>
        <h3 class="product-name"><a href="<?= e(site_url('produk.php?id=' . (int) $product['id'])) ?>"><?= e($product['nama']) ?></a></h3>
        <div class="product-bottom">
            <p class="product-price"><?= e(product_price_label($product)) ?></p>
            <?php if ($card_quote && $card_whatsapp !== ''): ?>
                <a class="btn-icon-add btn-icon-whatsapp" href="<?= e($card_whatsapp) ?>" target="_blank" rel="noopener noreferrer" aria-label="Tanyakan harga dan ketersediaan <?= e($product['nama']) ?> via WhatsApp">
                    <svg class="icon" aria-hidden="true"><use href="#i-whatsapp"></use></svg>
                </a>
            <?php elseif ($card_stock > 0): ?>
            <form action="<?= e(site_url('cart_add.php')) ?>" method="post" class="quick-add" data-add-form>
                <?= csrfField() ?>
                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                <input type="hidden" name="qty" value="1">
                <input type="hidden" name="redirect_to" value="<?= e($card_current) ?>">
                <button type="submit" class="btn-icon-add" aria-label="Tambah <?= e($product['nama']) ?> ke keranjang" data-add-button>
                    <svg class="icon" aria-hidden="true"><use href="#i-plus"></use></svg>
                </button>
            </form>
            <?php else: ?>
            <span class="stock-note">Habis</span>
            <?php endif; ?>
        </div>
    </div>
</article>
