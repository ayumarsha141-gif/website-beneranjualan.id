<?php
require_once __DIR__ . '/includes/config.php';

$page_title = 'Lupa Password';
$meta_description = 'Panduan memulihkan akses akun Beneranjualan.id.';
$current_page = 'login';

$wa = whatsapp_url($pdo, 'Halo admin, saya lupa password akun Beneranjualan.id. Mohon bantuan pemulihan.');
$email = trim(setting($pdo, 'contact_email'));

include __DIR__ . '/includes/header.php';
?>

<section class="auth-page">
    <div class="container auth-layout">
        <div class="auth-card auth-card-wide">
            <p class="eyebrow">Pemulihan akun</p>
            <h1 class="auth-title">Lupa <em>password?</em></h1>
            <p class="muted">
                Pemulihan password dilakukan secara manual oleh admin toko. Kami tidak
                menampilkan formulir reset otomatis karena pengiriman email belum
                dikonfigurasi pada instalasi ini.
            </p>
            <div class="info-box">
                <h2>Langkah pemulihan</h2>
                <ol>
                    <li>Siapkan username atau email yang terdaftar.</li>
                    <li>Hubungi admin melalui kanal kontak toko.</li>
                    <li>Admin akan memverifikasi identitas sebelum memberikan akses baru.</li>
                </ol>
            </div>
            <?php if ($wa !== '' || $email !== ''): ?>
                <div class="contact-actions">
                    <?php if ($wa !== ''): ?>
                        <a class="btn btn-primary" href="<?= e($wa) ?>" target="_blank" rel="noopener noreferrer">Hubungi via WhatsApp</a>
                    <?php endif; ?>
                    <?php if ($email !== ''): ?>
                        <a class="btn btn-outline" href="mailto:<?= e($email) ?>">Kirim email</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <p class="summary-note">Kontak admin belum diisi pada pengaturan toko. Pemilik toko perlu melengkapi kontak agar pelanggan dapat meminta pemulihan akun.</p>
            <?php endif; ?>
            <p class="auth-footer"><a href="<?= e(site_url('login.php')) ?>">Kembali ke halaman masuk</a></p>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
