<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

$from = clean_text($_GET['from'] ?? date('Y-m-01'), 10);
$to = clean_text($_GET['to'] ?? date('Y-m-d'), 10);
$dateOk = fn ($d) => (bool) DateTimeImmutable::createFromFormat('!Y-m-d', $d);
if (!$dateOk($from)) {
    $from = date('Y-m-01');
}
if (!$dateOk($to)) {
    $to = date('Y-m-d');
}
if ($from > $to) {
    [$from, $to] = [$to, $from];
}

$newOrders = (int) $pdo->query("SELECT COUNT(*) FROM pesanan WHERE status = 'baru'")->fetchColumn();
$processing = (int) $pdo->query("SELECT COUNT(*) FROM pesanan WHERE status IN ('baru','diproses')")->fetchColumn();
$readyShip = (int) $pdo->query("SELECT COUNT(*) FROM pesanan WHERE status = 'siap_dikirim'")->fetchColumn();
$pendingPayments = (int) $pdo->query("SELECT COUNT(*) FROM pesanan WHERE payment_status = 'menunggu_pembayaran' AND status <> 'dibatalkan'")->fetchColumn();
$totalProducts = (int) $pdo->query('SELECT COUNT(*) FROM produk WHERE is_active = 1 AND is_demo = 0')->fetchColumn();
$totalCustomers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT COALESCE(SUM(total), 0) FROM pesanan
     WHERE payment_status = 'dibayar' AND status <> 'dibatalkan'
       AND DATE(created_at) BETWEEN ? AND ?"
);
$stmt->execute([$from, $to]);
$revenue = (float) $stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM pesanan
     WHERE payment_status = 'dibayar' AND status <> 'dibatalkan'
       AND DATE(created_at) BETWEEN ? AND ?"
);
$stmt->execute([$from, $to]);
$paidOrders = (int) $stmt->fetchColumn();

$lowThreshold = (int) setting($pdo, 'low_stock_threshold', '3');
$stmt = $pdo->prepare(
    "SELECT id, nama, stok, gambar FROM produk WHERE is_active = 1 AND is_demo = 0 AND availability_mode = 'stock' AND stok <= ? ORDER BY stok ASC, nama ASC LIMIT 6"
);
$stmt->execute([$lowThreshold]);
$lowStock = $stmt->fetchAll();

$recentOrders = $pdo->query('SELECT * FROM pesanan ORDER BY created_at DESC LIMIT 8')->fetchAll();

$admin_title = 'Dashboard';
$admin_page = 'dashboard';
include __DIR__ . '/includes/admin_header.php';
?>

<?php showFlash(); ?>

<div class="admin-page-head">
    <div>
        <p class="admin-eyebrow">Ringkasan operasional</p>
        <h2>Selamat datang, <?= e(current_user()['nama_lengkap']) ?>.</h2>
        <p class="admin-muted">Angka di bawah berasal dari transaksi nyata di database. Pendapatan hanya menghitung pembayaran yang sudah diverifikasi.</p>
    </div>
    <form class="admin-date-filter" method="get" action="<?= e(site_url('admin/dashboard.php')) ?>">
        <label>Dari <input type="date" name="from" value="<?= e($from) ?>"></label>
        <label>Sampai <input type="date" name="to" value="<?= e($to) ?>"></label>
        <button class="btn btn-outline btn-sm" type="submit">Terapkan</button>
    </form>
</div>

<div class="admin-kpi-grid">
    <article class="admin-kpi admin-kpi-accent">
        <span class="admin-kpi-icon"><svg class="icon" aria-hidden="true"><use href="#i-package"></use></svg></span>
        <p class="admin-kpi-label">Pesanan baru</p>
        <p class="admin-kpi-value"><?= $newOrders ?></p>
        <p class="admin-kpi-note">Belum diproses</p>
    </article>
    <article class="admin-kpi">
        <span class="admin-kpi-icon"><svg class="icon" aria-hidden="true"><use href="#i-refresh"></use></svg></span>
        <p class="admin-kpi-label">Perlu diproses</p>
        <p class="admin-kpi-value"><?= $processing ?></p>
        <p class="admin-kpi-note">Status baru + diproses</p>
    </article>
    <article class="admin-kpi">
        <span class="admin-kpi-icon"><svg class="icon" aria-hidden="true"><use href="#i-truck"></use></svg></span>
        <p class="admin-kpi-label">Siap dikirim</p>
        <p class="admin-kpi-value"><?= $readyShip ?></p>
        <p class="admin-kpi-note">Menunggu pengiriman</p>
    </article>
    <article class="admin-kpi">
        <span class="admin-kpi-icon"><svg class="icon" aria-hidden="true"><use href="#i-trend"></use></svg></span>
        <p class="admin-kpi-label">Pendapatan terverifikasi</p>
        <p class="admin-kpi-value admin-kpi-money"><?= e(formatRupiah($revenue)) ?></p>
        <p class="admin-kpi-note"><?= $paidOrders ?> pesanan · <?= e(format_date($from)) ?>–<?= e(format_date($to)) ?></p>
    </article>
</div>

<div class="admin-mini-grid">
    <a class="admin-mini" href="<?= e(site_url('admin/pesanan.php?payment=menunggu_pembayaran')) ?>">
        <span>Pembayaran menunggu</span><strong><?= $pendingPayments ?></strong>
    </a>
    <a class="admin-mini" href="<?= e(site_url('admin/inventaris.php?low=1')) ?>">
        <span>Produk stok rendah</span><strong><?= count($lowStock) ?></strong>
    </a>
    <a class="admin-mini" href="<?= e(site_url('admin/produk.php')) ?>">
        <span>Produk aktif</span><strong><?= $totalProducts ?></strong>
    </a>
    <a class="admin-mini" href="<?= e(site_url('admin/pelanggan.php')) ?>">
        <span>Pelanggan terdaftar</span><strong><?= $totalCustomers ?></strong>
    </a>
</div>

<div class="admin-two-col">
    <section class="admin-card">
        <div class="admin-card-head">
            <h3>Pesanan terbaru</h3>
            <a class="admin-link" href="<?= e(site_url('admin/pesanan.php')) ?>">Lihat semua</a>
        </div>
        <?php if ($recentOrders): ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr><th>Pesanan</th><th>Pelanggan</th><th>Total</th><th>Status</th><th>Bayar</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOrders as $order): ?>
                    <tr>
                        <td>
                            <a class="admin-table-title" href="<?= e(site_url('admin/detail-pesanan.php?id=' . (int) $order['id'])) ?>"><?= e($order['order_number']) ?></a>
                            <span class="admin-table-sub"><?= e(format_date($order['created_at'], 'd M Y H:i')) ?></span>
                        </td>
                        <td><?= e($order['nama']) ?></td>
                        <td class="admin-money"><?= e(formatRupiah($order['total'])) ?></td>
                        <td><span class="<?= e(badge_class($order['status'])) ?>"><?= e(order_status_label($order['status'])) ?></span></td>
                        <td><span class="<?= e(badge_class($order['payment_status'])) ?>"><?= e(payment_status_label($order['payment_status'])) ?></span></td>
                        <td><a class="admin-link" href="<?= e(site_url('admin/detail-pesanan.php?id=' . (int) $order['id'])) ?>">Detail</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="admin-empty">
            <svg class="icon icon-lg" aria-hidden="true"><use href="#i-package"></use></svg>
            <h4>Belum ada pesanan nyata</h4>
            <p>Pesanan akan muncul di sini setelah pelanggan menyelesaikan checkout. Tidak ada data transaksi contoh yang ditampilkan.</p>
        </div>
        <?php endif; ?>
    </section>

    <section class="admin-card">
        <div class="admin-card-head">
            <h3>Stok perlu perhatian</h3>
            <a class="admin-link" href="<?= e(site_url('admin/inventaris.php')) ?>">Kelola stok</a>
        </div>
        <?php if ($lowStock): ?>
        <ul class="admin-stock-list">
            <?php foreach ($lowStock as $item): ?>
            <li>
                <img src="<?= e(product_image_url($item['gambar'])) ?>" alt="" width="44" height="55" loading="lazy">
                <span class="admin-stock-name"><?= e($item['nama']) ?></span>
                <span class="admin-stock-qty <?= (int) $item['stok'] === 0 ? 'is-out' : '' ?>"><?= (int) $item['stok'] ?> tersisa</span>
                <a class="admin-link" href="<?= e(site_url('admin/produk-edit.php?id=' . (int) $item['id'])) ?>">Ubah</a>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <div class="admin-empty admin-empty-sm">
            <p>Tidak ada produk dengan stok di bawah ambang <?= $lowThreshold ?>.</p>
        </div>
        <?php endif; ?>
    </section>
</div>

<section class="admin-card">
    <div class="admin-card-head"><h3>Aksi cepat</h3></div>
    <div class="admin-quick-actions">
        <a class="admin-quick" href="<?= e(site_url('admin/produk-edit.php')) ?>"><svg class="icon" aria-hidden="true"><use href="#i-plus"></use></svg><span>Tambah produk</span></a>
        <a class="admin-quick" href="<?= e(site_url('admin/pesanan.php?status=baru')) ?>"><svg class="icon" aria-hidden="true"><use href="#i-package"></use></svg><span>Proses pesanan baru</span></a>
        <a class="admin-quick" href="<?= e(site_url('admin/pengaturan.php')) ?>"><svg class="icon" aria-hidden="true"><use href="#i-settings"></use></svg><span>Atur kontak & pembayaran</span></a>
        <a class="admin-quick" href="<?= e(site_url('admin/laporan.php')) ?>"><svg class="icon" aria-hidden="true"><use href="#i-trend"></use></svg><span>Lihat laporan</span></a>
    </div>
</section>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
