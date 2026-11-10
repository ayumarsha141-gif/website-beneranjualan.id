<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $fields = [
        'store_name', 'tagline', 'contact_whatsapp', 'contact_email', 'contact_phone',
        'address', 'operating_hours', 'instagram', 'facebook', 'tiktok',
        'shipping_flat', 'free_shipping_min', 'shipping_note', 'low_stock_threshold',
        'promo_banner', 'bank_name', 'bank_account_number', 'bank_account_name',
        'payment_note', 'terms', 'privacy',
    ];
    $values = [];
    foreach ($fields as $field) {
        $values[$field] = clean_text($_POST[$field] ?? '', $field === 'terms' || $field === 'privacy' ? 10000 : 1000);
    }
    $values['promo_active'] = !empty($_POST['promo_active']) ? '1' : '0';
    $values['cod_enabled'] = !empty($_POST['cod_enabled']) ? '1' : '0';

    $errors = [];
    if (mb_strlen($values['store_name']) < 2) {
        $errors[] = 'Nama toko wajib diisi.';
    }
    if ($values['contact_email'] !== '' && !valid_email($values['contact_email'])) {
        $errors[] = 'Email kontak tidak valid.';
    }
    $waDigits = preg_replace('/\D+/', '', $values['contact_whatsapp']);
    if ($waDigits !== '' && strlen($waDigits) < 9) {
        $errors[] = 'Nomor WhatsApp tidak valid.';
    }
    foreach (['instagram', 'facebook', 'tiktok'] as $social) {
        if ($values[$social] !== '' && !filter_var($values[$social], FILTER_VALIDATE_URL)) {
            $errors[] = 'Tautan ' . ucfirst($social) . ' harus berupa URL lengkap (https://...).';
        }
    }
    foreach (['shipping_flat', 'free_shipping_min'] as $moneyField) {
        $values[$moneyField] = preg_replace('/\D+/', '', $values[$moneyField]);
        if ($values[$moneyField] === '') {
            $values[$moneyField] = '0';
        }
    }
    $values['low_stock_threshold'] = (string) max(0, (int) $values['low_stock_threshold']);
    $bankFilled = $values['bank_name'] !== '' || $values['bank_account_number'] !== '' || $values['bank_account_name'] !== '';
    if ($bankFilled && ($values['bank_name'] === '' || $values['bank_account_number'] === '' || $values['bank_account_name'] === '')) {
        $errors[] = 'Lengkapi nama bank, nomor rekening, dan nama pemilik rekening sekaligus.';
    }

    if ($errors) {
        flash(implode(' ', $errors), 'error');
        redirect('admin/pengaturan.php');
    }

    foreach ($values as $key => $value) {
        setting_set($pdo, $key, $value);
    }
    log_admin_action($pdo, 'settings_update', 'settings', null, ['fields' => array_keys($values)]);
    flash('Pengaturan toko disimpan.', 'success');
    redirect('admin/pengaturan.php');
}

$s = settings_all($pdo);
$hasContact = trim($s['contact_whatsapp'] ?? '') !== '' || trim($s['contact_email'] ?? '') !== '' || trim($s['contact_phone'] ?? '') !== '' || trim($s['address'] ?? '') !== '';
$hasBank = trim($s['bank_name'] ?? '') !== '' && trim($s['bank_account_number'] ?? '') !== '';
$demoCount = (int) $pdo->query('SELECT COUNT(*) FROM produk WHERE is_demo = 1 AND is_active = 1')->fetchColumn();
$adminCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$realProducts = (int) $pdo->query('SELECT COUNT(*) FROM produk WHERE is_demo = 0 AND is_active = 1')->fetchColumn();

$admin_title = 'Pengaturan';
$admin_page = 'settings';
include __DIR__ . '/includes/admin_header.php';
?>

<?php showFlash(); ?>

<div class="admin-page-head">
    <div>
        <p class="admin-eyebrow">Konfigurasi toko</p>
        <h2>Pengaturan</h2>
        <p class="admin-muted">Isi data bisnis nyata. Nilai kosong tidak ditampilkan sebagai klaim palsu di storefront.</p>
    </div>
</div>

<div class="admin-settings-layout">
    <form method="post" action="<?= e(site_url('admin/pengaturan.php')) ?>" class="admin-settings-main">
        <?= csrfField() ?>

        <section class="admin-card">
            <div class="admin-card-head"><h3>Identitas toko</h3></div>
            <div class="admin-form-grid">
                <label>Nama toko
                    <input type="text" name="store_name" required maxlength="100" value="<?= e($s['store_name'] ?? 'Beneranjualan.id') ?>">
                </label>
                <label>Tagline singkat
                    <input type="text" name="tagline" maxlength="160" value="<?= e($s['tagline'] ?? '') ?>">
                </label>
                <label class="admin-span-2">Kebijakan promo / pengumuman
                    <textarea name="promo_banner" rows="2" maxlength="300" placeholder="Contoh: pengumuman promo yang benar-benar berlaku"><?= e($s['promo_banner'] ?? '') ?></textarea>
                    <small>Tampil di strip atas hanya jika "Aktifkan pengumuman" dicentang dan teks diisi.</small>
                </label>
                <label class="admin-check-inline"><input type="checkbox" name="promo_active" value="1" <?= ($s['promo_active'] ?? '0') === '1' ? 'checked' : '' ?>> Aktifkan pengumuman</label>
            </div>
        </section>

        <section class="admin-card">
            <div class="admin-card-head"><h3>Kontak & media sosial</h3></div>
            <div class="admin-form-grid">
                <label>Nomor WhatsApp
                    <input type="text" name="contact_whatsapp" maxlength="30" value="<?= e($s['contact_whatsapp'] ?? '') ?>" placeholder="08xxxxxxxxxx">
                    <small>Jika kosong, tombol WhatsApp tidak ditampilkan.</small>
                </label>
                <label>Telepon
                    <input type="text" name="contact_phone" maxlength="30" value="<?= e($s['contact_phone'] ?? '') ?>">
                </label>
                <label>Email
                    <input type="email" name="contact_email" maxlength="190" value="<?= e($s['contact_email'] ?? '') ?>">
                </label>
                <label>Jam operasional
                    <input type="text" name="operating_hours" maxlength="120" value="<?= e($s['operating_hours'] ?? '') ?>" placeholder="Contoh: Senin–Sabtu 09.00–18.00">
                </label>
                <label class="admin-span-2">Alamat toko
                    <textarea name="address" rows="2" maxlength="500"><?= e($s['address'] ?? '') ?></textarea>
                </label>
                <label>Instagram
                    <input type="url" name="instagram" maxlength="190" value="<?= e($s['instagram'] ?? '') ?>" placeholder="https://instagram.com/...">
                </label>
                <label>Facebook
                    <input type="url" name="facebook" maxlength="190" value="<?= e($s['facebook'] ?? '') ?>" placeholder="https://facebook.com/...">
                </label>
                <label>TikTok
                    <input type="url" name="tiktok" maxlength="190" value="<?= e($s['tiktok'] ?? '') ?>" placeholder="https://tiktok.com/@...">
                </label>
            </div>
        </section>

        <section class="admin-card">
            <div class="admin-card-head"><h3>Pengiriman & pembayaran</h3></div>
            <div class="admin-form-grid">
                <label>Ongkos kirim tetap (Rp)
                    <input type="text" name="shipping_flat" inputmode="numeric" value="<?= e($s['shipping_flat'] ?? '0') ?>">
                    <small>0 berarti belum ada biaya tetap; ongkir dikonfirmasi admin.</small>
                </label>
                <label>Gratis ongkir mulai (Rp)
                    <input type="text" name="free_shipping_min" inputmode="numeric" value="<?= e($s['free_shipping_min'] ?? '0') ?>">
                    <small>0 berarti tidak ada ambang gratis ongkir otomatis.</small>
                </label>
                <label class="admin-span-2">Catatan ongkos kirim
                    <textarea name="shipping_note" rows="2" maxlength="500"><?= e($s['shipping_note'] ?? '') ?></textarea>
                </label>
                <label>Nama bank
                    <input type="text" name="bank_name" maxlength="80" value="<?= e($s['bank_name'] ?? '') ?>">
                </label>
                <label>Nomor rekening
                    <input type="text" name="bank_account_number" maxlength="60" value="<?= e($s['bank_account_number'] ?? '') ?>">
                </label>
                <label>Nama pemilik rekening
                    <input type="text" name="bank_account_name" maxlength="120" value="<?= e($s['bank_account_name'] ?? '') ?>">
                </label>
                <label class="admin-span-2">Catatan pembayaran
                    <textarea name="payment_note" rows="2" maxlength="500"><?= e($s['payment_note'] ?? '') ?></textarea>
                </label>
                <label class="admin-check-inline"><input type="checkbox" name="cod_enabled" value="1" <?= ($s['cod_enabled'] ?? '0') === '1' ? 'checked' : '' ?>> Aktifkan opsi bayar di tempat (hanya jika layanan benar-benar tersedia)</label>
            </div>
        </section>

        <section class="admin-card">
            <div class="admin-card-head"><h3>Operasional</h3></div>
            <div class="admin-form-grid">
                <label>Ambang stok rendah
                    <input type="number" name="low_stock_threshold" min="0" step="1" value="<?= e($s['low_stock_threshold'] ?? '3') ?>">
                    <small>Produk dengan stok ≤ nilai ini muncul di peringatan dashboard.</small>
                </label>
            </div>
        </section>

        <section class="admin-card">
            <div class="admin-card-head"><h3>Kebijakan toko</h3></div>
            <div class="admin-form-grid">
                <label class="admin-span-2">Syarat & ketentuan
                    <textarea name="terms" rows="6" maxlength="10000"><?= e($s['terms'] ?? '') ?></textarea>
                </label>
                <label class="admin-span-2">Kebijakan privasi
                    <textarea name="privacy" rows="6" maxlength="10000"><?= e($s['privacy'] ?? '') ?></textarea>
                </label>
            </div>
        </section>

        <div class="admin-form-actions">
            <button class="btn btn-primary btn-lg" type="submit">Simpan pengaturan</button>
        </div>
    </form>

    <aside class="admin-settings-side">
        <section class="admin-card">
            <div class="admin-card-head"><h3>Status konfigurasi</h3></div>
            <ul class="admin-checklist">
                <li class="<?= $hasContact ? 'is-ok' : 'is-warn' ?>">
                    <span class="admin-check-icon"><?= $hasContact ? '✓' : '!' ?></span>
                    <div><strong>Kontak toko</strong><small><?= $hasContact ? 'Sudah diisi' : 'Belum diisi — tombol kontak disembunyikan' ?></small></div>
                </li>
                <li class="<?= $hasBank ? 'is-ok' : 'is-warn' ?>">
                    <span class="admin-check-icon"><?= $hasBank ? '✓' : '!' ?></span>
                    <div><strong>Rekening pembayaran</strong><small><?= $hasBank ? 'Sudah diisi' : 'Belum diisi — instruksi dikirim admin' ?></small></div>
                </li>
                <li class="<?= $adminCount > 0 ? 'is-ok' : 'is-warn' ?>">
                    <span class="admin-check-icon"><?= $adminCount > 0 ? '✓' : '!' ?></span>
                    <div><strong>Akun admin</strong><small><?= $adminCount ?> akun administrator</small></div>
                </li>
                <li class="<?= $demoCount === 0 ? 'is-ok' : 'is-warn' ?>">
                    <span class="admin-check-icon"><?= $demoCount === 0 ? '✓' : '!' ?></span>
                    <div><strong>Produk demo</strong><small><?= $demoCount === 0 ? 'Tidak ada produk demo aktif' : $demoCount . ' produk demo masih aktif' ?></small></div>
                </li>
                <li class="<?= $realProducts > 0 ? 'is-ok' : 'is-warn' ?>">
                    <span class="admin-check-icon"><?= $realProducts > 0 ? '✓' : '!' ?></span>
                    <div><strong>Produk nyata</strong><small><?= $realProducts ?> produk non-demo aktif</small></div>
                </li>
            </ul>
        </section>

        <section class="admin-card">
            <div class="admin-card-head"><h3>Catatan keamanan</h3></div>
            <p class="admin-muted">Jangan gunakan password admin yang lemah atau dibagikan. Akun admin dibuat lewat CLI <code>scripts/create_admin.php</code>, bukan dari halaman web.</p>
            <p class="admin-hint">Gunakan HTTPS saat online dan atur <code>SESSION_COOKIE_SECURE=1</code>.</p>
        </section>
    </aside>
</div>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
