<?php
$footer_store = setting($pdo, 'store_name', SITE_NAME);
$footer_email = trim(setting($pdo, 'contact_email'));
$footer_phone = trim(setting($pdo, 'contact_phone'));
$footer_address = trim(setting($pdo, 'address'));
$footer_hours = trim(setting($pdo, 'operating_hours'));
$footer_wa = whatsapp_url($pdo, 'Halo, saya ingin bertanya tentang buket.');
$socials = [];
foreach (['instagram' => 'Instagram', 'facebook' => 'Facebook', 'tiktok' => 'TikTok'] as $key => $label) {
    $url = trim(setting($pdo, $key));
    if ($url !== '') {
        $socials[$key] = $url;
    }
}
$footer_categories = [];
try {
    $footer_categories = $pdo->query('SELECT c.nama, c.slug FROM categories c WHERE c.is_active = 1 AND EXISTS (SELECT 1 FROM produk p WHERE p.category_id = c.id AND p.is_active = 1 AND p.is_demo = 0) ORDER BY c.sort_order, c.nama LIMIT 5')->fetchAll();
} catch (Throwable $e) {
    $footer_categories = [];
}
$has_contact = $footer_wa !== '' || $footer_email !== '' || $footer_phone !== '' || $footer_address !== '' || $footer_hours !== '';
$show_demo_notice = demo_products_exist($pdo);
?>
</main>

<footer class="site-footer">
    <div class="container footer-grid">
        <div class="footer-brand">
            <a class="wordmark wordmark-light" href="<?= e(site_url()) ?>">
                <span class="wordmark-text"><?= e($footer_store) ?></span>
            </a>
            <p class="footer-tagline"><?= e(setting($pdo, 'tagline', 'Buket bunga untuk momen yang berarti.')) ?></p>
            <?php if ($socials): ?>
            <div class="social-links" aria-label="Media sosial">
                <?php foreach ($socials as $key => $url): ?>
                    <a href="<?= e($url) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= e(ucfirst($key)) ?>">
                        <svg class="icon" aria-hidden="true"><use href="#i-<?= e($key) ?>"></use></svg>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="footer-col">
            <h2 class="footer-title">Jelajahi</h2>
            <ul>
                <li><a href="<?= e(site_url('katalog.php')) ?>">Semua koleksi</a></li>
                <?php foreach ($footer_categories as $cat): ?>
                    <li><a href="<?= e(site_url('katalog.php?kategori=' . urlencode($cat['slug']))) ?>"><?= e($cat['nama']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="footer-col">
            <h2 class="footer-title">Bantuan</h2>
            <ul>
                <li><a href="<?= e($current_page === 'home' ? '#cara-pesan' : site_url('index.php#cara-pesan')) ?>">Cara pesan</a></li>
                <li><a href="<?= e(site_url('lacak-pesanan.php')) ?>">Lacak pesanan</a></li>
                <li><a href="<?= e(site_url('pesanan-saya.php')) ?>">Pesanan saya</a></li>
                <li><a href="<?= e(site_url('kebijakan.php')) ?>">Kebijakan & ketentuan</a></li>
            </ul>
        </div>

        <?php if ($has_contact): ?>
        <div class="footer-col">
            <h2 class="footer-title">Kontak</h2>
            <ul class="footer-contact">
                <?php if ($footer_wa !== ''): ?><li><a href="<?= e($footer_wa) ?>" target="_blank" rel="noopener noreferrer"><svg class="icon" aria-hidden="true"><use href="#i-whatsapp"></use></svg> WhatsApp</a></li><?php endif; ?>
                <?php if ($footer_phone !== ''): ?><li><a href="tel:<?= e(preg_replace('/\s+/', '', $footer_phone)) ?>"><svg class="icon" aria-hidden="true"><use href="#i-phone"></use></svg> <?= e($footer_phone) ?></a></li><?php endif; ?>
                <?php if ($footer_email !== ''): ?><li><a href="mailto:<?= e($footer_email) ?>"><svg class="icon" aria-hidden="true"><use href="#i-mail"></use></svg> <?= e($footer_email) ?></a></li><?php endif; ?>
                <?php if ($footer_address !== ''): ?><li><svg class="icon" aria-hidden="true"><use href="#i-pin"></use></svg> <?= nl2br(e($footer_address)) ?></li><?php endif; ?>
                <?php if ($footer_hours !== ''): ?><li><svg class="icon" aria-hidden="true"><use href="#i-clock"></use></svg> <?= e($footer_hours) ?></li><?php endif; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>

    <div class="container footer-bottom">
        <p>&copy; <?= date('Y') ?> <?= e($footer_store) ?>. Hak cipta dilindungi.</p>
        <p class="footer-payment-note"><svg class="icon icon-xs" aria-hidden="true"><use href="#i-lock"></use></svg> Pembayaran transfer manual diverifikasi admin.</p>
    </div>

    <?php if ($show_demo_notice): ?>
    <div class="container">
        <p class="demo-notice" role="note"><strong>Katalog demo:</strong> sebagian produk masih berupa contoh data untuk pengujian. Ganti dengan produk dan stok nyata melalui panel admin sebelum toko dibuka untuk umum.</p>
    </div>
    <?php endif; ?>
</footer>

<button class="back-to-top" id="backToTop" type="button" aria-label="Kembali ke atas">
    <svg class="icon" aria-hidden="true"><use href="#i-arrow-right"></use></svg>
</button>

<?php if ($footer_wa !== ''): ?>
<a class="whatsapp-float" href="<?= e($footer_wa) ?>" target="_blank" rel="noopener noreferrer" aria-label="Hubungi via WhatsApp">
    <svg class="icon" aria-hidden="true"><use href="#i-whatsapp"></use></svg>
</a>
<?php endif; ?>

<div class="toast-region" id="toastRegion" role="status" aria-live="polite"></div>

<script src="<?= e(asset_url('main.js')) ?>?v=20261108-v2" defer></script>
<?php if (($current_page ?? '') === 'home'): ?><script type="module" src="<?= e(asset_url('florist-world.js')) ?>?v=20261108-anchor"></script><?php endif; ?>
</body>
</html>
