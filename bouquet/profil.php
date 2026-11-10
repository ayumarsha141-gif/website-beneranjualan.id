<?php
require_once __DIR__ . '/includes/config.php';
requireLogin('profil.php');

$userId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'profile') {
        $nama = clean_text($_POST['nama_lengkap'] ?? '', 120);
        $email = mb_strtolower(clean_text($_POST['email'] ?? '', 190));
        $telepon = clean_text($_POST['telepon'] ?? '', 30);
        $alamat = clean_text($_POST['alamat'] ?? '', 1000);

        $errors = [];
        if (mb_strlen($nama) < 2) {
            $errors[] = 'Nama lengkap wajib diisi.';
        }
        if (!valid_email($email)) {
            $errors[] = 'Format email tidak valid.';
        }
        if ($telepon !== '' && !valid_phone($telepon)) {
            $errors[] = 'Nomor telepon tidak valid.';
        }
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
        $stmt->execute([$email, $userId]);
        if ($stmt->fetch()) {
            $errors[] = 'Email sudah digunakan akun lain.';
        }
        if ($errors) {
            flash(implode(' ', $errors), 'error');
            redirect('profil.php');
        }

        $stmt = $pdo->prepare('UPDATE users SET nama_lengkap = ?, email = ?, telepon = ?, alamat = ? WHERE id = ?');
        $stmt->execute([$nama, $email, $telepon, $alamat, $userId]);
        $_SESSION['nama_lengkap'] = $nama;
        $_SESSION['email'] = $email;
        flash('Profil diperbarui.', 'success');
        redirect('profil.php');
    }

    if ($action === 'password') {
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $hash = (string) $stmt->fetchColumn();

        if (!verifyPassword($current, $hash)) {
            flash('Password saat ini tidak cocok.', 'error');
        } elseif (strlen($new) < 8) {
            flash('Password baru minimal 8 karakter.', 'error');
        } elseif ($new !== $confirm) {
            flash('Konfirmasi password baru tidak cocok.', 'error');
        } else {
            $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
            $stmt->execute([hashPassword($new), $userId]);
            session_regenerate_id(true);
            flash('Password berhasil diubah.', 'success');
        }
        redirect('profil.php');
    }
}

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM pesanan WHERE user_id = ?');
$stmt->execute([$userId]);
$orderCount = (int) $stmt->fetchColumn();

$page_title = 'Profil Saya';
$meta_description = 'Kelola data akun Beneranjualan.id.';
$current_page = 'profile';
include __DIR__ . '/includes/header.php';
?>

<section class="page-hero page-hero-compact">
    <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="<?= e(site_url()) ?>">Beranda</a><span aria-hidden="true">/</span><span>Profil</span>
        </nav>
        <p class="eyebrow">Akun pelanggan</p>
        <h1 class="page-title">Halo, <em><?= e($user['nama_lengkap']) ?>.</em></h1>
    </div>
</section>

<section class="section section-tight">
    <div class="container account-layout">
        <aside class="account-nav">
            <a class="is-active" href="<?= e(site_url('profil.php')) ?>">Profil</a>
            <a href="<?= e(site_url('pesanan-saya.php')) ?>">Pesanan saya <span><?= $orderCount ?></span></a>
            <a href="<?= e(site_url('lacak-pesanan.php')) ?>">Lacak pesanan</a>
            <?php if (isAdmin()): ?><a href="<?= e(site_url('admin/dashboard.php')) ?>">Panel admin</a><?php endif; ?>
            <form action="<?= e(site_url('logout.php')) ?>" method="post">
                <?= csrfField() ?>
                <button type="submit">Keluar</button>
            </form>
        </aside>

        <div class="account-main">
            <?php showFlash(); ?>

            <section class="form-card">
                <div class="form-card-head">
                    <span class="step-kicker">01</span>
                    <h2>Data profil</h2>
                </div>
                <form method="post" action="<?= e(site_url('profil.php')) ?>" class="form-grid">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="profile">
                    <div class="field">
                        <label for="username">Username</label>
                        <input id="username" type="text" value="<?= e($user['username']) ?>" disabled>
                        <small>Username tidak dapat diubah.</small>
                    </div>
                    <div class="field">
                        <label for="nama_lengkap">Nama lengkap</label>
                        <input id="nama_lengkap" type="text" name="nama_lengkap" required maxlength="120" value="<?= e($user['nama_lengkap']) ?>">
                    </div>
                    <div class="field">
                        <label for="email">Email</label>
                        <input id="email" type="email" name="email" required maxlength="190" value="<?= e($user['email']) ?>">
                    </div>
                    <div class="field">
                        <label for="telepon">Nomor telepon</label>
                        <input id="telepon" type="tel" name="telepon" maxlength="30" value="<?= e($user['telepon'] ?? '') ?>">
                    </div>
                    <div class="field field-full">
                        <label for="alamat">Alamat pengiriman</label>
                        <textarea id="alamat" name="alamat" rows="3" maxlength="1000"><?= e($user['alamat'] ?? '') ?></textarea>
                    </div>
                    <div class="field field-full">
                        <button class="btn btn-primary" type="submit">Simpan profil</button>
                    </div>
                </form>
            </section>

            <section class="form-card">
                <div class="form-card-head">
                    <span class="step-kicker">02</span>
                    <h2>Ganti password</h2>
                </div>
                <form method="post" action="<?= e(site_url('profil.php')) ?>" class="form-grid">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="password">
                    <div class="field field-full">
                        <label for="current_password">Password saat ini</label>
                        <input id="current_password" type="password" name="current_password" required autocomplete="current-password">
                    </div>
                    <div class="field">
                        <label for="new_password">Password baru</label>
                        <input id="new_password" type="password" name="new_password" required minlength="8" autocomplete="new-password">
                    </div>
                    <div class="field">
                        <label for="confirm_password">Ulangi password baru</label>
                        <input id="confirm_password" type="password" name="confirm_password" required minlength="8" autocomplete="new-password">
                    </div>
                    <div class="field field-full">
                        <button class="btn btn-outline" type="submit">Ubah password</button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
