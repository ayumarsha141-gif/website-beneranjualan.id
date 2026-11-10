<?php
require_once __DIR__ . '/includes/config.php';
$page_title = 'Kebijakan & Ketentuan';
$meta_description = 'Informasi pemesanan, pembayaran, pengiriman, dan privasi pelanggan.';
$current_page = 'policy';
$store_terms = trim(setting($pdo, 'terms'));
$store_privacy = trim(setting($pdo, 'privacy'));
include __DIR__ . '/includes/header.php';
?>
<section class="page-hero page-hero-compact">
    <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= e(site_url()) ?>">Beranda</a><span aria-hidden="true">/</span><span>Kebijakan</span></nav>
        <p class="eyebrow">Informasi toko</p>
        <h1 class="page-title">Kebijakan & <em>ketentuan.</em></h1>
        <p class="page-lede">Ketentuan berikut menjelaskan alur pesanan saat ini. Pastikan detail alamat dan kontak benar sebelum membuat pesanan.</p>
    </div>
</section>
<section class="section section-tight">
    <div class="container narrow-container policy-content">
        <?php if ($store_terms !== ''): ?>
            <article class="form-card"><h2>Syarat & ketentuan toko</h2><p><?= nl2br(e($store_terms)) ?></p></article>
        <?php else: ?>
            <article class="form-card"><h2>Pemesanan & ketersediaan</h2><p>Pesanan dibuat berdasarkan harga dan stok yang tercatat di sistem saat checkout. Admin akan mengonfirmasi ketersediaan bunga, jadwal, dan rincian pengiriman sebelum pesanan disiapkan. Permintaan tanggal pengiriman bukan jaminan sebelum dikonfirmasi.</p></article>
            <article class="form-card"><h2>Pembayaran</h2><p>Pembayaran transfer manual berstatus menunggu pembayaran sampai diverifikasi admin. Jangan menganggap pesanan telah lunas sebelum status pembayaran berubah menjadi terverifikasi. Detail rekening hanya ditampilkan jika telah diisi oleh pemilik toko.</p></article>
            <article class="form-card"><h2>Pengiriman & pembatalan</h2><p>Biaya dan jangkauan pengiriman dikonfirmasi admin apabila belum tersedia perhitungan ongkir otomatis. Hubungi toko melalui kontak yang tercantum di situs untuk perubahan atau pembatalan; status pesanan dan kemungkinan pengembalian dana mengikuti kondisi pemrosesan dan pembayaran.</p></article>
        <?php endif; ?>
        <?php if ($store_privacy !== ''): ?>
            <article class="form-card"><h2>Kebijakan privasi</h2><p><?= nl2br(e($store_privacy)) ?></p></article>
        <?php else: ?>
            <article class="form-card"><h2>Data pribadi</h2><p>Nama, email, telepon, dan alamat digunakan untuk memproses, mengonfirmasi, serta melacak pesanan. Jangan mengirimkan informasi pembayaran sensitif selain melalui kanal yang secara resmi dikonfirmasi oleh toko. Akun dapat digunakan untuk melihat riwayat pesanan yang terhubung dengannya.</p></article>
        <?php endif; ?>
        <p class="muted">Informasi bawaan ini bersifat umum. Pemilik toko perlu meninjau dan melengkapinya sesuai proses bisnis dan kewajiban hukum yang berlaku sebelum toko dibuka untuk umum.</p>
    </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
