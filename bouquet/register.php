<?php
$page_title = 'Daftar';
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
                <h2>Buat Akun Baru</h2>
                <p>Daftar untuk menikmati berbagai keuntungan</p>
            </div>
            
            <?php showFlash(); ?>
            
            <form action="proses_register.php" method="POST" data-validate>
                <?= csrfField() ?>
                
                <div class="form-group">
                    <label>Nama Lengkap <span class="required">*</span></label>
                    <input type="text" name="nama_lengkap" class="form-control" 
                           placeholder="Masukkan nama lengkap" required>
                </div>
                
                <div class="form-group">
                    <label>Username <span class="required">*</span></label>
                    <input type="text" name="username" class="form-control" 
                           placeholder="Pilih username" required>
                </div>
                
                <div class="form-group">
                    <label>Email <span class="required">*</span></label>
                    <input type="email" name="email" class="form-control" 
                           placeholder="Masukkan email" required>
                </div>
                
                <div class="form-group">
                    <label>Password <span class="required">*</span></label>
                    <input type="password" name="password" class="form-control" 
                           placeholder="Buat password (min 6 karakter)" required>
                </div>
                
                <div class="form-group">
                    <label>Konfirmasi Password <span class="required">*</span></label>
                    <input type="password" name="password_confirm" class="form-control" 
                           placeholder="Ulangi password" required>
                </div>
                
                <label style="display: flex; align-items: flex-start; gap: 10px; margin-bottom: 20px; cursor: pointer;">
                    <input type="checkbox" name="agree" required style="margin-top: 4px;">
                    <span>Saya setuju dengan <a href="#" style="color: var(--primary);">Syarat & Ketentuan</a> serta <a href="#" style="color: var(--primary);">Kebijakan Privasi</a></span>
                </label>
                
                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    <i class="fas fa-user-plus"></i> Daftar Sekarang
                </button>
            </form>
            
            <div class="auth-footer">
                Sudah punya akun? <a href="login.php">Login</a>
            </div>
        </div>
    </div>
</section>
