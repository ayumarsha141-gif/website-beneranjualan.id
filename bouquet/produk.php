<?php
require_once __DIR__ . '/includes/config.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect('katalog.php', 'Produk tidak ditemukan.', 'error');
}

$stmt = $pdo->prepare(
    'SELECT p.*, c.slug AS category_slug, c.nama AS category_name
     FROM produk p LEFT JOIN categories c ON c.id = p.category_id
      WHERE p.id = ? AND p.is_active = 1 AND p.is_demo = 0'
);
$stmt->execute([$id]);
$product = $stmt->fetch();
if (!$product) {
    http_response_code(404);
    $page_title = 'Produk tidak ditemukan';
    include __DIR__ . '/includes/header.php';
    echo '<section class="section"><div class="container"><div class="empty-state empty-state-lg"><svg class="icon icon-lg" aria-hidden="true"><use href="#i-flower"></use></svg><h1>Produk tidak ditemukan</h1><p>Produk mungkin sudah diarsipkan atau tautannya berubah.</p><a class="btn btn-primary" href="' . e(site_url('katalog.php')) . '">Kembali ke katalog</a></div></div></section>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$images = [];
if (!empty($product['gambar'])) {
    $images[] = ['filename' => $product['gambar'], 'alt' => $product['nama']];
}
$imgStmt = $pdo->prepare('SELECT filename, alt_text FROM product_images WHERE produk_id = ? ORDER BY sort_order, id');
$imgStmt->execute([$id]);
foreach ($imgStmt->fetchAll() as $img) {
    $images[] = ['filename' => $img['filename'], 'alt' => $img['alt_text'] ?: $product['nama']];
}

$relatedStmt = $pdo->prepare(
    "SELECT p.*, c.slug AS category_slug FROM produk p
     LEFT JOIN categories c ON c.id = p.category_id
      WHERE p.is_active = 1 AND p.is_demo = 0 AND p.id <> ?
       AND (p.category_id = ? OR p.kategori = ?)
     ORDER BY p.is_featured DESC, p.created_at DESC
     LIMIT 4"
);
$relatedStmt->execute([$id, $product['category_id'], $product['kategori']]);
$related = $relatedStmt->fetchAll();

$stock = (int) $product['stok'];
$low = (int) setting($pdo, 'low_stock_threshold', '3');
$contactQuote = product_requires_whatsapp($product);
$wa = product_inquiry_url($pdo, $product);
$current = '/produk.php?id=' . $id;

$page_title = $product['nama'];
$meta_description = excerpt((string) $product['deskripsi'], 155) ?: 'Detail produk ' . $product['nama'];
$current_page = 'katalog';
$canonical_url = site_url('produk.php?id=' . $id);
$og_type = 'product';
$og_image = product_image_url($product['gambar']);
if ((int) $product['is_demo'] === 1) {
    $meta_robots = 'noindex,follow';
}

$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $product['nama'],
    'description' => excerpt((string) $product['deskripsi'], 300),
    'image' => product_image_url($product['gambar']),
];
if (!$contactQuote) {
    $jsonLd['offers'] = [
        '@type' => 'Offer',
        'priceCurrency' => 'IDR',
        'price' => (float) $product['harga'],
        'availability' => $stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        'url' => site_url('produk.php?id=' . $id),
    ];
}

include __DIR__ . '/includes/header.php';
?>
<script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>

<section class="product-detail">
    <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="<?= e(site_url()) ?>">Beranda</a>
            <span aria-hidden="true">/</span>
            <a href="<?= e(site_url('katalog.php')) ?>">Katalog</a>
            <?php if (!empty($product['category_slug'])): ?>
                <span aria-hidden="true">/</span>
                <a href="<?= e(site_url('katalog.php?kategori=' . urlencode($product['category_slug']))) ?>"><?= e($product['kategori']) ?></a>
            <?php endif; ?>
            <span aria-hidden="true">/</span>
            <span><?= e($product['nama']) ?></span>
        </nav>

        <div class="product-detail-grid">
            <div class="product-gallery">
                <div class="gallery-main">
                    <?php $gallerySrcset = product_image_srcset($images[0]['filename'] ?? null); ?>
                    <picture>
                        <?php if ($gallerySrcset !== ''): ?><source id="galleryWebp" type="image/webp" srcset="<?= e($gallerySrcset) ?>" sizes="(max-width: 760px) 100vw, 55vw"><?php endif; ?>
                        <img id="galleryMain" src="<?= e(product_image_url($images[0]['filename'] ?? null)) ?>" alt="<?= e($images[0]['alt'] ?? $product['nama']) ?>" width="900" height="1125" fetchpriority="high" decoding="async">
                    </picture>
                    <?php if ((int) $product['is_demo'] === 1): ?>
                        <span class="badge badge-demo gallery-badge">Produk demo</span>
                    <?php endif; ?>
                </div>
                <?php if (count($images) > 1): ?>
                <div class="gallery-thumbs" role="group" aria-label="Galeri foto produk">
                    <?php foreach ($images as $index => $img): ?>
                        <button type="button" class="gallery-thumb<?= $index === 0 ? ' is-active' : '' ?>"
                            data-gallery-src="<?= e(product_image_url($img['filename'])) ?>"
                            <?php if (($thumbSrcset = product_image_srcset($img['filename'])) !== ''): ?>data-gallery-srcset="<?= e($thumbSrcset) ?>"<?php endif; ?>
                            data-gallery-alt="<?= e($img['alt']) ?>"
                            aria-label="Lihat foto <?= $index + 1 ?>">
                        <?php $thumbSrcset = product_image_srcset($img['filename']); ?>
                        <picture>
                            <?php if ($thumbSrcset !== ''): ?><source type="image/webp" srcset="<?= e($thumbSrcset) ?>" sizes="76px"><?php endif; ?>
                            <img src="<?= e(product_image_url($img['filename'])) ?>" alt="" width="160" height="200" loading="lazy">
                        </picture>
                    </button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="product-info-panel">
                <div class="product-eyebrow">
                    <a href="<?= e(site_url('katalog.php?kategori=' . urlencode((string) $product['category_slug']))) ?>"><?= e($product['kategori']) ?></a>
                </div>
                <h1 class="product-title"><?= e($product['nama']) ?></h1>
                <p class="product-detail-price"><?= e(product_price_label($product)) ?></p>

                <div class="stock-line">
                    <?php if ($contactQuote): ?>
                        <span class="stock-dot in-stock" aria-hidden="true"></span>
                        <span>Ketersediaan dan harga akhir dikonfirmasi via WhatsApp</span>
                    <?php elseif ($stock > 0): ?>
                        <span class="stock-dot in-stock" aria-hidden="true"></span>
                        <span>Tersedia<?= $stock <= $low ? ' — sisa ' . $stock : '' ?></span>
                    <?php else: ?>
                        <span class="stock-dot out-stock" aria-hidden="true"></span>
                        <span>Habis</span>
                    <?php endif; ?>
                </div>

                <?php if (trim((string) $product['deskripsi']) !== ''): ?>
                <div class="product-description">
                    <?= nl2br(e((string) $product['deskripsi'])) ?>
                </div>
                <?php endif; ?>

                <?php if ($contactQuote && $wa !== ''): ?>
                <div class="product-quote-box">
                    <p>Butuh detail atau jumlah tertentu? Hubungi kami via WhatsApp.</p>
                    <a class="btn btn-whatsapp btn-block btn-lg" href="<?= e($wa) ?>" target="_blank" rel="noopener noreferrer">
                        <svg class="icon" aria-hidden="true"><use href="#i-whatsapp"></use></svg> Hubungi WhatsApp
                    </a>
                </div>
                <?php elseif ($contactQuote): ?>
                <div class="product-quote-box"><p>Hubungi toko via WhatsApp untuk ketersediaan dan harga akhir.</p></div>
                <?php elseif ($stock > 0): ?>
                <form class="product-buy-form" action="<?= e(site_url('cart_add.php')) ?>" method="post" data-add-form>
                    <?= csrfField() ?>
                    <input type="hidden" name="product_id" value="<?= $id ?>">
                    <input type="hidden" name="redirect_to" value="<?= e($current) ?>">
                    <label class="qty-label" for="qty">Jumlah</label>
                    <div class="buy-row">
                        <div class="qty-stepper">
                            <button type="button" class="qty-btn" data-qty="-1" aria-label="Kurangi jumlah">
                                <svg class="icon" aria-hidden="true"><use href="#i-minus"></use></svg>
                            </button>
                            <input id="qty" type="number" name="qty" value="1" min="1" max="<?= $stock ?>" inputmode="numeric">
                            <button type="button" class="qty-btn" data-qty="1" aria-label="Tambah jumlah">
                                <svg class="icon" aria-hidden="true"><use href="#i-plus"></use></svg>
                            </button>
                        </div>
                        <button class="btn btn-primary btn-lg btn-grow" type="submit" data-add-button>
                            <svg class="icon" aria-hidden="true"><use href="#i-cart"></use></svg>
                            Tambah ke keranjang
                        </button>
                    </div>
                </form>
                <?php else: ?>
                <div class="out-stock-box">
                    <p>Produk ini sedang habis. Hubungi admin untuk informasi ketersediaan berikutnya.</p>
                    <?php if ($wa !== ''): ?>
                        <a class="btn btn-outline" href="<?= e($wa) ?>" target="_blank" rel="noopener noreferrer">Tanya ketersediaan</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if (!$contactQuote && $wa !== ''): ?>
                <a class="btn btn-whatsapp btn-block" href="<?= e($wa) ?>" target="_blank" rel="noopener noreferrer">
                    <svg class="icon" aria-hidden="true"><use href="#i-whatsapp"></use></svg>
                    Tanya via WhatsApp
                </a>
                <?php endif; ?>

                <dl class="product-facts">
                    <div><dt>Kategori</dt><dd><?= e($product['kategori']) ?></dd></div>
                    <?php if (!$contactQuote): ?>
                    <div><dt>Ketersediaan</dt><dd><?= $stock > 0 ? $stock . ' unit' : 'Habis' ?></dd></div>
                    <?php endif; ?>
                    <div><dt>Pembayaran</dt><dd>Via WhatsApp</dd></div>
                    <div><dt>Ongkir</dt><dd><?= e(setting($pdo, 'shipping_note', 'Dikonfirmasi admin sebelum pengiriman.')) ?></dd></div>
                </dl>

                <?php if ((int) $product['is_demo'] === 1): ?>
                <p class="demo-inline"><strong>Catatan:</strong> ini produk demo. Harga dan stok adalah data uji, bukan inventaris final.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php if ($related): ?>
<section class="section section-related">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow">Anda mungkin suka</p>
                <h2 class="section-title">Buket lain untuk <em>momen serupa.</em></h2>
            </div>
            <a class="text-link" href="<?= e(site_url('katalog.php')) ?>">Semua koleksi <svg class="icon icon-xs" aria-hidden="true"><use href="#i-arrow-right"></use></svg></a>
        </div>
        <div class="product-grid">
            <?php foreach ($related as $product): ?>
                <?php include __DIR__ . '/includes/product-card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
