<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

$q = clean_text($_GET['q'] ?? '', 80);
$like = '%' . $q . '%';

$where = "u.role = 'customer'";
$params = [];
if ($q !== '') {
    $where .= ' AND (u.nama_lengkap LIKE ? OR u.username LIKE ? OR u.email LIKE ?)';
    array_push($params, $like, $like, $like);
}
$stmt = $pdo->prepare(
    "SELECT u.*,
            COUNT(o.id) AS order_count,
            MAX(o.created_at) AS last_order,
            COALESCE(SUM(CASE WHEN o.payment_status = 'dibayar' AND o.status <> 'dibatalkan' THEN o.total ELSE 0 END), 0) AS verified_total
     FROM users u LEFT JOIN pesanan o ON o.user_id = u.id
     WHERE $where
     GROUP BY u.id
     ORDER BY u.created_at DESC
     LIMIT 100"
);
$stmt->execute($params);
$customers = $stmt->fetchAll();

$guestWhere = 'user_id IS NULL';
$guestParams = [];
if ($q !== '') {
    $guestWhere .= ' AND (nama LIKE ? OR email LIKE ? OR telepon LIKE ?)';
    array_push($guestParams, $like, $like, $like);
}
$stmt = $pdo->prepare(
    "SELECT nama, email, telepon, COUNT(*) AS order_count, COALESCE(SUM(total), 0) AS total, MAX(created_at) AS last_order
     FROM pesanan WHERE $guestWhere
     GROUP BY nama, email, telepon
     ORDER BY last_order DESC LIMIT 100"
);
$stmt->execute($guestParams);
$guests = $stmt->fetchAll();

$admin_title = 'Pelanggan';
$admin_page = 'customers';
include __DIR__ . '/includes/admin_header.php';
?>

<?php showFlash(); ?>

<div class="admin-page-head">
    <div>
        <p class="admin-eyebrow">Relasi pelanggan</p>
        <h2>Pelanggan</h2>
        <p class="admin-muted">Data pribadi hanya ditampilkan seperlunya untuk operasional. Jangan membagikan data pelanggan di luar kebutuhan pesanan.</p>
    </div>
</div>

<form class="admin-filter-bar" method="get" action="<?= e(site_url('admin/pelanggan.php')) ?>">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari nama, email, telepon">
    <button class="btn btn-primary btn-sm" type="submit">Cari</button>
    <a class="btn btn-ghost btn-sm" href="<?= e(site_url('admin/pelanggan.php')) ?>">Reset</a>
</form>

<section class="admin-card">
    <div class="admin-card-head"><h3>Pelanggan terdaftar</h3><span class="admin-table-sub"><?= count($customers) ?> akun</span></div>
    <?php if ($customers): ?>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th>Nama</th><th>Kontak</th><th>Pesanan</th><th>Total terverifikasi</th><th>Pesanan terakhir</th><th>Status</th></tr></thead>
            <tbody>
                <?php foreach ($customers as $customer): ?>
                <tr>
                    <td>
                        <span class="admin-table-title"><?= e($customer['nama_lengkap']) ?></span>
                        <span class="admin-table-sub">@<?= e($customer['username']) ?> · daftar <?= e(format_date($customer['created_at'], 'd M Y')) ?></span>
                    </td>
                    <td>
                        <a href="mailto:<?= e($customer['email']) ?>"><?= e($customer['email']) ?></a>
                        <?php if ($customer['telepon']): ?><span class="admin-table-sub"><?= e($customer['telepon']) ?></span><?php endif; ?>
                    </td>
                    <td><a class="admin-link" href="<?= e(site_url('admin/pesanan.php?q=' . urlencode($customer['email']))) ?>"><?= (int) $customer['order_count'] ?> pesanan</a></td>
                    <td class="admin-money"><?= e(formatRupiah($customer['verified_total'])) ?></td>
                    <td><?= $customer['last_order'] ? e(format_date($customer['last_order'], 'd M Y')) : '—' ?></td>
                    <td><span class="badge <?= (int) $customer['is_active'] ? 'badge-selesai' : 'badge-dibatalkan' ?>"><?= (int) $customer['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="admin-empty admin-empty-sm"><p>Belum ada pelanggan terdaftar<?= $q !== '' ? ' yang cocok dengan pencarian' : '' ?>.</p></div>
    <?php endif; ?>
</section>

<section class="admin-card">
    <div class="admin-card-head"><h3>Pembeli tamu</h3><span class="admin-table-sub">Dikelompokkan dari data pesanan</span></div>
    <?php if ($guests): ?>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th>Nama</th><th>Kontak</th><th>Pesanan</th><th>Total pesanan</th><th>Terakhir</th></tr></thead>
            <tbody>
                <?php foreach ($guests as $guest): ?>
                <tr>
                    <td><?= e($guest['nama']) ?></td>
                    <td>
                        <a href="mailto:<?= e($guest['email']) ?>"><?= e($guest['email']) ?></a>
                        <span class="admin-table-sub"><?= e($guest['telepon']) ?></span>
                    </td>
                    <td><a class="admin-link" href="<?= e(site_url('admin/pesanan.php?q=' . urlencode($guest['email']))) ?>"><?= (int) $guest['order_count'] ?> pesanan</a></td>
                    <td class="admin-money"><?= e(formatRupiah($guest['total'])) ?></td>
                    <td><?= e(format_date($guest['last_order'], 'd M Y')) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="admin-hint">Total pembeli tamu adalah nilai pesanan, bukan pendapatan terverifikasi. Lihat laporan untuk pendapatan yang sudah dibayar.</p>
    <?php else: ?>
    <div class="admin-empty admin-empty-sm"><p>Belum ada pembeli tamu<?= $q !== '' ? ' yang cocok dengan pencarian' : '' ?>.</p></div>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
