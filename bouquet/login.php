<?php
require_once __DIR__ . '/includes/config.php';

if (isLoggedIn()) {
    redirect(isAdmin() ? 'admin/dashboard.php' : 'index.php');
}

$page_title = 'Masuk';
$meta_description = 'Masuk ke akun Beneranjualan.id.';
$current_page = 'login';
include __DIR__ . '/includes/header.php';
?>

<section class="auth-page">
    <div class="container auth-layout">
        <div class="auth-visual" aria-hidden="true">
            <div class="auth-visual-inner">
                <p class="eyebrow">Akun pelanggan</p>
                <h2>Simpan riwayat pesanan Anda di satu tempat.</h2>
                <svg viewBox="0 0 320 380" fill="none" stroke="currentColor" stroke-width="1.1">
                    <path d="M160 360V170"/>
                    <path d="M160 250c-52-8-82-34-92-88 46 0 80 32 92 88Z"/>
                    <path d="M160 292c52-8 82-34 92-88-46 0-80 32-92 88Z"/>
                    <circle cx="160" cy="132" r="30"/>
                    <circle cx="116" cy="158" r="23"/>
                    <circle cx="204" cy="158" r="23"/>
                    <circle cx="160" cy="132" r="10" fill="currentColor" stroke="none"/>
                </svg>
            </div>
        </div>

        <div class="auth-card">
            <p class="eyebrow">Selamat datang kembali</p>
            <h1 class="auth-title">Masuk ke akun <em>Anda.</em></h1>
            <?php showFlash(); ?>

            <form action="<?= e(site_url('proses_login.php')) ?>" method="post" class="auth-form" autocomplete="on">
                <?= csrfField() ?>
                <div class="field">
                    <label for="username">Email atau username</label>
                    <input id="username" type="text" name="username" required autocomplete="username" autofocus>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" required autocomplete="current-password">
                </div>
                <div class="auth-links">
                    <a href="<?= e(site_url('lupa-password.php')) ?>">Lupa password?</a>
                </div>
                <button class="btn btn-primary btn-block btn-lg" type="submit">Masuk</button>
            </form>

            <p class="auth-footer">Belum punya akun? <a href="<?= e(site_url('register.php')) ?>">Buat akun</a></p>
            <p class="auth-guest">Ingin memesan tanpa akun? <a href="<?= e(site_url('katalog.php')) ?>">Lanjut ke katalog</a></p>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
