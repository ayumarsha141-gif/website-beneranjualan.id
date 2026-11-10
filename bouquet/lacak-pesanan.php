<?php
require_once __DIR__ . '/includes/config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $attempts = $_SESSION['track_attempts'] ?? ['count' => 0, 'start' => time()];
    if (time() - (int) $attempts['start'] > 600) {
        $attempts = ['count' => 0, 'start' => time()];
    }
    $attempts['count'] = (int) $attempts['count'] + 1;
    $_SESSION['track_attempts'] = $attempts;

    if ($attempts['count'] > 10) {
        $error = 'Terlalu banyak percobaan. Coba lagi beberapa menit lagi.';
    } else {
        $orderNumber = clean_text($_POST['order_number'] ?? '', 40);
        $contact = clean_text($_POST['contact'] ?? '', 190);

        if ($orderNumber === '' || $contact === '') {
            $error = 'Nomor pesanan dan email/telepon wajib diisi.';
        } else {
            $stmt = $pdo->prepare('SELECT * FROM pesanan WHERE order_number = ? LIMIT 1');
            $stmt->execute([$orderNumber]);
            $order = $stmt->fetch();

            $emailMatch = $order && hash_equals(mb_strtolower((string) $order['email']), mb_strtolower($contact));
            $phoneMatch = $order && preg_replace('/\D+/', '', (string) $order['telepon']) === preg_replace('/\D+/', '', $contact);

            if ($order && ($emailMatch || $phoneMatch)) {
                unset($_SESSION['track_attempts']);
                redirect('terima-kasih.php?order=' . urlencode($order['order_number']) . '&token=' . urlencode($order['order_token']));
            }
            $error = 'Pesanan tidak ditemukan. Periksa kembali nomor pesanan dan kontak yang digunakan saat checkout.';
        }
    }
}

$page_title = 'Lacak Pesanan';
$meta_description = 'Lacak status pesanan Beneranjualan.id dengan nomor pesanan dan email.';
$current_page = 'track';
include __DIR__ . '/includes/header.php';
?>

<section class="page-hero page-hero-compact">
    <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="<?= e(site_url()) ?>">Beranda</a><span aria-hidden="true">/</span><span>Lacak pesanan</span>
        </nav>
        <p class="eyebrow">Status pesanan</p>
        <h1 class="page-title">Lacak <em>pesanan Anda.</em></h1>
        <p class="page-lede">Masukkan nomor pesanan dan email atau nomor telepon yang Anda gunakan saat checkout.</p>
    </div>
</section>

<section class="section section-tight">
    <div class="container narrow-container">
        <div class="form-card">
            <?php if ($error !== ''): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>
            <?php showFlash(); ?>
            <form method="post" action="<?= e(site_url('lacak-pesanan.php')) ?>" class="form-grid">
                <?= csrfField() ?>
                <div class="field field-full">
                    <label for="order_number">Nomor pesanan</label>
                    <input id="order_number" type="text" name="order_number" required placeholder="BJ-YYYYMMDD-XXXXXX" value="<?= e($_POST['order_number'] ?? '') ?>">
                </div>
                <div class="field field-full">
                    <label for="contact">Email atau nomor telepon</label>
                    <input id="contact" type="text" name="contact" required autocomplete="email" value="<?= e($_POST['contact'] ?? ($_SESSION['email'] ?? '')) ?>">
                </div>
                <div class="field field-full">
                    <button class="btn btn-primary btn-block btn-lg" type="submit">Cari pesanan</button>
                </div>
            </form>
            <p class="summary-note">Tautan halaman pesanan yang Anda terima setelah checkout juga dapat dibuka langsung tanpa mengisi formulir ini.</p>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
