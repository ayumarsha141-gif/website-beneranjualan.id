<?php
$page_title = 'Login';
include 'includes/header.php';

if (isLoggedIn()) {
    redirect('index.php');
}
?>

<section class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <a href="<?= SITE_URL ?>" class="logo">
                    <i class="fas fa-seedling"></i>
                    <?= SITE_NAME ?>
                </a>
                <h2>Selamat Datang Kembali!</h2>
                <p>Masuk ke akun Anda untuk melanjutkan</p>
            </div>
            
            <?php showFlash(); ?>
            
            <form action="proses_login.php" method="POST" data-validate>
                <?= csrfField() ?>
                
                <div class="form-group">
                    <label>Email atau Username</label>
                    <input type="text" name="username" class="form-control" 
                           placeholder="Masukkan email atau username" required>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" 
                           placeholder="Masukkan password" required>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="remember"> Ingat Saya
                    </label>
                    <a href="lupa-password.php" style="color: var(--primary);">Lupa Password?</a>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            
            <div class="divider"><span>atau</span></div>
            
            <div class="social-login">
                <button class="social-btn google">
                    <i class="fab fa-google"></i> Google
                </button>
                <button class="social-btn facebook">
                    <i class="fab fa-facebook-f"></i> Facebook
                </button>
            </div>
            
            <div class="auth-footer">
                Belum punya akun? <a href="register.php">Daftar Sekarang</a>
            </div>
        </div>
    </div>
</section>
