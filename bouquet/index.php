<?php
require_once __DIR__ . '/includes/config.php';

$page_title = 'Buket Bunga untuk Momen yang Berarti';
$meta_description = 'Katalog buket bunga Beneranjualan.id. Pilih buket, cek stok, dan pesan dengan konfirmasi admin.';
$current_page = 'home';
$body_class = 'home-page';
$canonical_url = site_url();

$featured = $pdo->query(
    "SELECT * FROM produk WHERE is_active = 1 AND is_demo = 0 ORDER BY is_featured DESC, created_at DESC LIMIT 4"
)->fetchAll();

$categories = $pdo->query(
    "SELECT c.*, COUNT(p.id) AS product_count
     FROM categories c
     LEFT JOIN produk p ON p.category_id = c.id AND p.is_active = 1 AND p.is_demo = 0
     WHERE c.is_active = 1
     GROUP BY c.id
     HAVING COUNT(p.id) > 0
     ORDER BY c.sort_order, c.nama"
)->fetchAll();

$store_wa = whatsapp_url($pdo, 'Halo, saya ingin bertanya tentang buket.');
$contact_email = trim(setting($pdo, 'contact_email'));
$contact_phone = trim(setting($pdo, 'contact_phone'));
$contact_address = trim(setting($pdo, 'address'));
$contact_hours = trim(setting($pdo, 'operating_hours'));
$has_contact = $store_wa !== '' || $contact_email !== '' || $contact_phone !== '' || $contact_address !== '';
$shipping_note = trim(setting($pdo, 'shipping_note'));

include __DIR__ . '/includes/header.php';
?>

<section class="florist-world" id="flower-journey" data-florist-world data-world-scenes="6" aria-label="Perjalanan buket dalam dunia florist 3D">
    <div class="world-scroll-track" data-world-track>
        <div class="world-pin">
            <canvas class="world-canvas" data-world-canvas aria-hidden="true"></canvas>
            <div class="world-atmosphere" aria-hidden="true"></div>

            <div class="world-ui">
                <div class="world-copy-stack" aria-live="polite" aria-atomic="true">
                    <article class="world-copy is-active" data-world-copy="0">
                        <p class="world-kicker"><span>01</span> · Floral studio</p>
                        <h1>Bunga untuk momen <em>yang berharga.</em></h1>
                        <p>Setiap cerita bermula dari sebuah buket. Temukan karya yang paling dekat dengan momenmu.</p>
                        <a class="btn btn-primary btn-lg" href="<?= e(site_url('katalog.php')) ?>">Jelajahi koleksi <svg class="icon" aria-hidden="true"><use href="#i-arrow-right"></use></svg></a>
                    </article>
                    <article class="world-copy" data-world-copy="1" aria-hidden="true">
                        <p class="world-kicker"><span>02</span> · The collection</p>
                        <h2>Temukan yang cocok <em>untuk momennya.</em></h2>
                        <p>Lihat foto, detail, harga, dan ketersediaan yang benar-benar tercatat di katalog.</p>
                        <?php if ($featured): ?>
                            <div class="world-product-rail">
                                <?php foreach (array_slice($featured, 0, 2) as $worldProduct): ?>
                                <a class="world-product-card" href="<?= e(site_url('produk.php?id=' . (int) $worldProduct['id'])) ?>">
                                    <?php $worldProductSet = product_image_srcset($worldProduct['gambar']); ?>
                                    <picture>
                                        <?php if ($worldProductSet !== ''): ?><source type="image/webp" srcset="<?= e($worldProductSet) ?>" sizes="62px"><?php endif; ?>
                                        <img src="<?= e(product_image_url($worldProduct['gambar'])) ?>" alt="<?= e($worldProduct['nama']) ?>" width="96" height="120" loading="lazy">
                                    </picture>
                                    <span>
                                        <small><?= e($worldProduct['kategori']) ?> · <?= product_requires_whatsapp($worldProduct) ? 'Harga via WhatsApp' : 'Katalog aktif' ?></small>
                                        <strong><?= e($worldProduct['nama']) ?></strong>
                                        <b><?= e(product_price_label($worldProduct)) ?></b>
                                    </span>
                                    <svg class="icon" aria-hidden="true"><use href="#i-arrow-right"></use></svg>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="world-empty-note">Koleksi jual sedang diperbarui. Foto cerita menampilkan karya asli, bukan stok aktif.</p>
                        <?php endif; ?>
                        <a class="btn btn-outline" href="<?= e(site_url('katalog.php')) ?>">Lihat semua koleksi</a>
                    </article>
                    <article class="world-copy" data-world-copy="2" aria-hidden="true">
                        <p class="world-kicker"><span>03</span> · The workbench</p>
                        <h2>Dirangkai dengan <em>perhatian.</em></h2>
                        <p>Setiap karya punya karakter. Buka detail buket untuk melihat foto, isi, dan informasi yang tersedia.</p>
                        <a class="btn btn-primary" href="<?= e(site_url('katalog.php')) ?>">Lihat buket aktif <svg class="icon" aria-hidden="true"><use href="#i-arrow-right"></use></svg></a>
                    </article>
                    <article class="world-copy" data-world-copy="3" aria-hidden="true">
                        <p class="world-kicker"><span>04</span> · A personal touch</p>
                        <h2>Sampaikan hal yang <em>ingin dikenang.</em></h2>
                        <p>Catatan dan tanggal yang diinginkan dapat disampaikan saat checkout. Permintaan khusus menunggu konfirmasi toko.</p>
                        <a class="btn btn-primary" href="#cara-pesan">Lihat cara pesan <svg class="icon" aria-hidden="true"><use href="#i-arrow-right"></use></svg></a>
                    </article>
                    <article class="world-copy" data-world-copy="4" aria-hidden="true">
                        <p class="world-kicker"><span>05</span> · Packing & handover</p>
                        <h2>Detail antaran dipastikan <em>bersama.</em></h2>
                        <p>Admin mengonfirmasi jadwal dan ongkos kirim sebelum pesanan diproses. Tidak ada estimasi kurir yang ditebak.</p>
                        <a class="btn btn-primary" href="<?= e(site_url('kebijakan.php')) ?>">Ketentuan pemesanan <svg class="icon" aria-hidden="true"><use href="#i-arrow-right"></use></svg></a>
                    </article>
                    <article class="world-copy" data-world-copy="5" aria-hidden="true">
                        <p class="world-kicker"><span>06</span> · A moment to give</p>
                        <h2>Siap memilih <em>ceritamu?</em></h2>
                        <p>Mulai dari koleksi yang aktif. Harga, stok, serta pembayaran tetap mengikuti informasi toko.</p>
                        <a class="btn btn-primary btn-lg" href="<?= e(site_url('katalog.php')) ?>">Pilih buket <svg class="icon" aria-hidden="true"><use href="#i-arrow-right"></use></svg></a>
                    </article>
                </div>

                <nav class="world-scene-nav" aria-label="Adegan cerita buket">
                    <span class="world-scene-nav-line" aria-hidden="true"><span data-world-nav-progress></span></span>
                    <button type="button" class="is-active" data-world-jump="0" aria-label="Adegan 1: Floral studio" aria-current="step"><span>01</span></button>
                    <button type="button" data-world-jump="1" aria-label="Adegan 2: The collection"><span>02</span></button>
                    <button type="button" data-world-jump="2" aria-label="Adegan 3: The workbench"><span>03</span></button>
                    <button type="button" data-world-jump="3" aria-label="Adegan 4: A personal touch"><span>04</span></button>
                    <button type="button" data-world-jump="4" aria-label="Adegan 5: Packing and handover"><span>05</span></button>
                    <button type="button" data-world-jump="5" aria-label="Adegan 6: Finale"><span>06</span></button>
                </nav>
                <a class="world-skip" href="#koleksi">Langsung belanja <svg class="icon icon-xs" aria-hidden="true"><use href="#i-arrow-right"></use></svg></a>
                <div class="world-scroll-indicator" aria-hidden="true"><span></span> Gulir untuk mengikuti perjalanan</div>
            </div>

            <div class="world-fallback" data-world-fallback>
                <div class="world-fallback-intro">
                    <p class="eyebrow">A flower's journey</p>
                    <h1>Ruang kecil untuk <em>cerita yang besar.</em></h1>
                    <p>Jelajahi karya asli dan lanjutkan ke katalog yang terhubung dengan stok toko.</p>
                    <a class="btn btn-primary btn-lg" href="<?= e(site_url('katalog.php')) ?>">Jelajahi koleksi</a>
                </div>
                <div class="world-fallback-scenes">
                    <?php foreach ([
                        ['Clawsha','story-petite-clawsha.jpg','Bunga untuk momen yang berharga.'],
                        ['Nasha','story-signature-nasha.jpg','Lihat koleksi sesuai momen.'],
                        ['Suflosha','story-grand-suflosha.jpg','Dirangkai dengan perhatian.'],
                        ['','', 'Tambahkan catatan personal.'],
                        ['','', 'Konfirmasi detail antaran bersama toko.'],
                        ['','', 'Pilih buket dari katalog aktif.'],
                    ] as $fallbackIndex => [$fallbackName, $fallbackImage, $fallbackCaption]): ?>
                    <article class="world-fallback-scene">
                        <?php if ($fallbackImage !== ''): ?>
                            <img src="<?= e(asset_url('images/' . $fallbackImage)) ?>" alt="Foto karya <?= e($fallbackName) ?> dari katalog toko" loading="lazy" width="480" height="600">
                        <?php else: ?>
                            <div class="world-fallback-symbol" aria-hidden="true">
                                <span>0<?= $fallbackIndex + 1 ?></span>
                                <svg viewBox="0 0 100 100" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <?php if ($fallbackIndex === 3): ?>
                                        <path d="M24 25h52v48H24zM34 38h32M34 48h23M34 58h17M64 63c8-9 14-8 19-4-2 9-9 13-19 4Z"/>
                                    <?php elseif ($fallbackIndex === 4): ?>
                                        <path d="m20 38 30-16 30 16v38H20zM20 38h60M50 22v54M43 31c-17-13-20 8 7 8 27 0 24-21 7-8"/>
                                    <?php else: ?>
                                        <path d="M24 74h52M31 74l6-28h26l6 28M37 46c-8-19 5-30 13-31M50 47c-1-22 13-30 23-26M43 46c-13-15-24-11-27-4 8 8 17 9 27 4ZM54 44c8-15 19-13 24-7-6 9-14 11-24 7Z"/>
                                    <?php endif; ?>
                                </svg>
                            </div>
                        <?php endif; ?>
                        <p><span>0<?= $fallbackIndex + 1 ?> · </span><?= e($fallbackCaption) ?></p>
                    </article>
                    <?php endforeach; ?>
                </div>
                <p class="world-fallback-note" data-world-fallback-note>Menyiapkan ruang 3D…</p>
            </div>
        </div>
    </div>
</section>





<?php if ($categories): ?>
<section class="section section-moments" id="momen">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow">Pilih berdasarkan momen</p>
                <h2 class="section-title">Setiap kesempatan punya <em>bahasanya sendiri.</em></h2>
            </div>
            <a class="text-link" href="<?= e(site_url('katalog.php')) ?>">Lihat semua <svg class="icon icon-xs" aria-hidden="true"><use href="#i-arrow-right"></use></svg></a>
        </div>
        <div class="moment-grid">
            <?php foreach ($categories as $index => $cat): ?>
            <a class="moment-card reveal" href="<?= e(site_url('katalog.php?kategori=' . urlencode($cat['slug']))) ?>" style="--stagger: <?= $index * 60 ?>ms">
                <span class="moment-index">0<?= $index + 1 ?></span>
                <span class="moment-icon" aria-hidden="true">
                    <svg viewBox="0 0 80 80" fill="none" stroke="currentColor" stroke-width="1.2">
                        <path d="M40 70V36"/>
                        <path d="M40 50c-10-2-16-8-18-18 9 0 16 6 18 18Z"/>
                        <path d="M40 60c10-2 16-8 18-18-9 0-16 6-18 18Z"/>
                        <circle cx="40" cy="28" r="7"/>
                        <circle cx="28" cy="34" r="5.5"/>
                        <circle cx="52" cy="34" r="5.5"/>
                    </svg>
                </span>
                <h3><?= e($cat['nama']) ?></h3>
                <p><?= (int) $cat['product_count'] ?> buket tersedia</p>
                <span class="moment-arrow"><svg class="icon icon-xs" aria-hidden="true"><use href="#i-arrow-right"></use></svg></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="section section-products" id="koleksi">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow">Koleksi pilihan</p>
                <h2 class="section-title">Rangkaian yang sedang <em>tersedia.</em></h2>
            </div>
            <a class="text-link" href="<?= e(site_url('katalog.php')) ?>">Semua buket <svg class="icon icon-xs" aria-hidden="true"><use href="#i-arrow-right"></use></svg></a>
        </div>

        <?php if ($featured): ?>
        <div class="product-grid">
            <?php foreach ($featured as $product): ?>
                <?php include __DIR__ . '/includes/product-card.php'; ?>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <svg class="icon icon-lg" aria-hidden="true"><use href="#i-flower"></use></svg>
            <h3>Belum ada produk yang ditampilkan</h3>
            <p>Admin perlu menambahkan produk aktif terlebih dahulu.</p>
        </div>
        <?php endif; ?>
    </div>
</section>

<section class="section section-how" id="cara-pesan">
    <div class="container">
        <div class="section-head section-head-center">
            <p class="eyebrow">Cara pesan</p>
            <h2 class="section-title">Empat langkah, <em>tanpa tebak-tebakan.</em></h2>
        </div>
        <ol class="steps-grid">
            <li class="step reveal">
                <span class="step-number">01</span>
                <h3>Pilih buket</h3>
                <p>Buka katalog, filter berdasarkan momen atau kisaran harga, lalu baca detail produk.</p>
            </li>
            <li class="step reveal" style="--stagger: 60ms">
                <span class="step-number">02</span>
                <h3>Checkout</h3>
                <p>Isi data penerima dan alamat. Total dihitung ulang oleh server dari harga dan stok terbaru.</p>
            </li>
            <li class="step reveal" style="--stagger: 120ms">
                <span class="step-number">03</span>
                <h3>Konfirmasi admin</h3>
                <p>Admin memeriksa ketersediaan, jadwal pengiriman, dan ongkos kirim sebelum pesanan diproses.</p>
            </li>
            <li class="step reveal" style="--stagger: 180ms">
                <span class="step-number">04</span>
                <h3>Bayar & terima</h3>
                <p>Pembayaran transfer diverifikasi manual. Status pesanan dapat dipantau dari halaman lacak pesanan.</p>
            </li>
        </ol>
    </div>
</section>

<section class="section section-faq">
    <div class="container faq-grid">
        <div class="faq-intro">
            <p class="eyebrow">Pertanyaan umum</p>
            <h2 class="section-title">Hal yang perlu <em>Anda tahu.</em></h2>
            <p class="muted">Jawaban di bawah mengikuti pengaturan toko saat ini. Hubungi admin untuk detail pesanan tertentu.</p>
        </div>
        <div class="faq-list">
            <details class="faq-item">
                <summary>Bagaimana cara membayar?</summary>
                <p>Pembayaran dilakukan melalui transfer bank manual. Pesanan berstatus <strong>menunggu pembayaran</strong> sampai admin memverifikasi pembayaran Anda. Instruksi pembayaran ditampilkan pada halaman pesanan setelah checkout.</p>
            </details>
            <details class="faq-item">
                <summary>Apakah harga di keranjang bisa berubah?</summary>
                <p>Harga dan total selalu dihitung ulang oleh server saat checkout berdasarkan harga dan stok terbaru di database. Total yang dikirim dari browser tidak dipakai.</p>
            </details>
            <details class="faq-item">
                <summary>Bagaimana ongkos kirim dihitung?</summary>
                <p><?= e($shipping_note !== '' ? $shipping_note : 'Ongkos kirim dikonfirmasi admin sebelum pengiriman.') ?></p>
            </details>
            <details class="faq-item">
                <summary>Bisakah saya memantau pesanan?</summary>
                <p>Ya. Gunakan nomor pesanan dan email yang Anda isi saat checkout di halaman <a href="<?= e(site_url('lacak-pesanan.php')) ?>">Lacak Pesanan</a>, atau buka menu Pesanan Saya jika memiliki akun.</p>
            </details>
        </div>
    </div>
</section>

<section class="section section-contact" id="kontak">
    <div class="container contact-panel reveal">
        <div>
            <p class="eyebrow">Kontak</p>
            <h2 class="section-title">Butuh bantuan sebelum <em>memesan?</em></h2>
            <?php if ($has_contact): ?>
                <p class="muted">Tim toko dapat membantu pertanyaan tentang produk, jadwal, dan pengiriman.</p>
                <ul class="contact-list">
                    <?php if ($store_wa !== ''): ?><li><a href="<?= e($store_wa) ?>" target="_blank" rel="noopener noreferrer"><svg class="icon" aria-hidden="true"><use href="#i-whatsapp"></use></svg> WhatsApp</a></li><?php endif; ?>
                    <?php if ($contact_phone !== ''): ?><li><a href="tel:<?= e(preg_replace('/\s+/', '', $contact_phone)) ?>"><svg class="icon" aria-hidden="true"><use href="#i-phone"></use></svg> <?= e($contact_phone) ?></a></li><?php endif; ?>
                    <?php if ($contact_email !== ''): ?><li><a href="mailto:<?= e($contact_email) ?>"><svg class="icon" aria-hidden="true"><use href="#i-mail"></use></svg> <?= e($contact_email) ?></a></li><?php endif; ?>
                    <?php if ($contact_address !== ''): ?><li><svg class="icon" aria-hidden="true"><use href="#i-pin"></use></svg> <?= nl2br(e($contact_address)) ?></li><?php endif; ?>
                    <?php if ($contact_hours !== ''): ?><li><svg class="icon" aria-hidden="true"><use href="#i-clock"></use></svg> <?= e($contact_hours) ?></li><?php endif; ?>
                </ul>
            <?php else: ?>
                <p class="muted">
                    Kontak langsung toko belum diisi oleh pemilik. Anda tetap dapat memantau
                    pesanan melalui halaman lacak pesanan atau akun Anda.
                </p>
                <div class="contact-actions">
                    <a class="btn btn-primary" href="<?= e(site_url('lacak-pesanan.php')) ?>">Lacak pesanan</a>
                    <a class="btn btn-outline" href="<?= e(site_url('pesanan-saya.php')) ?>">Pesanan saya</a>
                </div>
            <?php endif; ?>
        </div>
        <div class="contact-art" aria-hidden="true">
            <svg viewBox="0 0 340 340" fill="none" stroke="currentColor" stroke-width="1.1">
                <path d="M170 320V140"/>
                <path d="M170 220c-46-6-72-30-80-78 40 0 70 28 80 78Z"/>
                <path d="M170 258c46-6 72-30 80-78-40 0-70 28-80 78Z"/>
                <circle cx="170" cy="112" r="26"/>
                <circle cx="132" cy="134" r="20"/>
                <circle cx="208" cy="134" r="20"/>
                <circle cx="170" cy="112" r="9" fill="currentColor" stroke="none"/>
                <path d="M80 320h180" stroke-dasharray="4 8"/>
            </svg>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
