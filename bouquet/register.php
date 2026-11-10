<?php
require_once __DIR__ . '/includes/config.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$page_title = 'Buat Akun';
$meta_description = 'Buat akun Beneranjualan.id untuk memantau pesanan.';
$current_page = 'register';
include __DIR__ . '/includes/header.php';
?>

<section class="auth-page">
    <div class="container auth-layout auth-layout-reverse">
        <div class="auth-card">
            <p class="eyebrow">Akun baru</p>
            <h1 class="auth-title">Buat akun <em>Anda.</em></h1>
            <?php showFlash(); ?>

            <form action="<?= e(site_url('proses_register.php')) ?>" method="post" class="auth-form" autocomplete="on">
                <?= csrfField() ?>
                <div class="field">
                    <label for="nama_lengkap">Nama lengkap</label>
                    <input id="nama_lengkap" type="text" name="nama_lengkap" required maxlength="120" autocomplete="name">
                </div>
                <div class="field">
                    <label for="username">Username</label>
                    <input id="username" type="text" name="username" required minlength="3" maxlength="60" pattern="[A-Za-z0-9._-]{3,60}" autocomplete="username">
                    <small>3–60 karakter: huruf, angka, titik, garis bawah, atau strip.</small>
                </div>
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" required maxlength="190" autocomplete="email">
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" required minlength="8" autocomplete="new-password">
                    <small>Minimal 8 karakter.</small>
                </div>
                <div class="field">
                    <label for="password_confirm">Ulangi password</label>
                    <input id="password_confirm" type="password" name="password_confirm" required minlength="8" autocomplete="new-password">
                </div>
                <label class="check-row">
                    <input type="checkbox" name="agree" value="1" required>
                    <span>Saya menyetujui <a href="<?= e(site_url('kebijakan.php')) ?>" target="_blank" rel="noopener">kebijakan dan ketentuan toko</a>.</span>
                </label>
                <button class="btn btn-primary btn-block btn-lg" type="submit">Daftar</button>
            </form>

            <p class="auth-footer">Sudah punya akun? <a href="<?= e(site_url('login.php')) ?>">Masuk</a></p>
        </div>

        <div class="auth-visual" aria-hidden="true">
            <div class="auth-visual-inner">
                <p class="eyebrow">Kenapa membuat akun?</p>
                <h2>Pesanan dan statusnya tersimpan rapi.</h2>
                <ul class="auth-benefits">
                    <li>Riwayat pesanan dapat dibuka kembali.</li>
                    <li>Data penerima terisi otomatis saat checkout.</li>
                    <li>Status pembayaran dan pengiriman lebih mudah dipantau.</li>
                </ul>
                <svg viewBox="0 0 300 240" fill="none" stroke="currentColor" stroke-width="1.1">
                    <path d="M150 230V110"/>
                    <path d="M150 160c-42-6-66-28-74-70 37 0 64 26 74 70Z"/>
                    <path d="M150 190c42-6 66-28 74-70-37 0-64 26-74 70Z"/>
                    <circle cx="150" cy="82" r="24"/>
                    <circle cx="114" cy="102" r="18"/>
                    <circle cx="186" cy="102" r="18"/>
                </svg>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
