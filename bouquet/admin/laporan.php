<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

$from = clean_text($_GET['from'] ?? date('Y-m-01'), 10);
$to = clean_text($_GET['to'] ?? date('Y-m-d'), 10);
$validDate = fn ($d) => (bool) DateTimeImmutable::createFromFormat('!Y-m-d', $d);
if (!$validDate($from)) {
    $from = date('Y-m-01');
}
if (!$validDate($to)) {
    $to = date('Y-m-d');
}
if ($from > $to) {
    [$from, $to] = [$to, $from];
}

function csv_safe(string $value): string
{
    if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
        return "'" . $value;
    }
    return $value;
}

if (($_GET['export'] ?? '') === 'csv') {
    $stmt = $pdo->prepare(
        'SELECT order_number, created_at, nama, email, telepon, status, payment_status, metode_bayar, subtotal, shipping_cost, total
         FROM pesanan WHERE DATE(created_at) BETWEEN ? AND ? ORDER BY created_at ASC'
    );
    $stmt->execute([$from, $to]);

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="beneranjualan-pesanan-' . $from . '-' . $to . '.csv"');
    header('Cache-Control: no-store');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Nomor pesanan', 'Tanggal', 'Nama', 'Email', 'Telepon', 'Status', 'Pembayaran', 'Metode', 'Subtotal', 'Ongkir', 'Total']);
    foreach ($stmt->fetchAll() as $row) {
        fputcsv($out, [
            csv_safe($row['order_number']),
            $row['created_at'],
            csv_safe($row['nama']),
            csv_safe($row['email']),
            csv_safe($row['telepon']),
            order_status_label($row['status']),
            payment_status_label($row['payment_status']),
            $row['metode_bayar'],
            $row['subtotal'],
            $row['shipping_cost'],
            $row['total'],
        ]);
    }
    fclose($out);
    exit;
}

$rangeParams = [$from, $to];

$stmt = $pdo->prepare(
    "SELECT COALESCE(SUM(total),0) FROM pesanan
     WHERE payment_status = 'dibayar' AND status <> 'dibatalkan' AND DATE(created_at) BETWEEN ? AND ?"
);
$stmt->execute($rangeParams);
$revenue = (float) $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM pesanan WHERE DATE(created_at) BETWEEN ? AND ?');
$stmt->execute($rangeParams);
$orderCount = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM pesanan WHERE payment_status = 'dibayar' AND status <> 'dibatalkan' AND DATE(created_at) BETWEEN ? AND ?"
);
$stmt->execute($rangeParams);
$paidCount = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM pesanan WHERE payment_status = 'menunggu_pembayaran' AND status <> 'dibatalkan' AND DATE(created_at) BETWEEN ? AND ?"
);
$stmt->execute($rangeParams);
$pendingCount = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM pesanan WHERE status = 'dibatalkan' AND DATE(created_at) BETWEEN ? AND ?");
$stmt->execute($rangeParams);
$cancelledCount = (int) $stmt->fetchColumn();

$avgOrder = $paidCount > 0 ? $revenue / $paidCount : 0;

$stmt = $pdo->prepare(
    'SELECT status, COUNT(*) AS total FROM pesanan WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY status ORDER BY total DESC'
);
$stmt->execute($rangeParams);
$statusCounts = $stmt->fetchAll();

$stmt = $pdo->prepare(
    "SELECT d.nama_produk, SUM(d.qty) AS qty, SUM(d.subtotal) AS revenue
     FROM detail_pesanan d JOIN pesanan o ON o.id = d.pesanan_id
     WHERE o.payment_status = 'dibayar' AND o.status <> 'dibatalkan' AND DATE(o.created_at) BETWEEN ? AND ?
     GROUP BY d.nama_produk ORDER BY qty DESC, revenue DESC LIMIT 10"
);
$stmt->execute($rangeParams);
$topProducts = $stmt->fetchAll();

$stmt = $pdo->prepare(
    "SELECT DATE(created_at) AS day, SUM(total) AS revenue, COUNT(*) AS orders
     FROM pesanan WHERE payment_status = 'dibayar' AND status <> 'dibatalkan' AND DATE(created_at) BETWEEN ? AND ?
     GROUP BY DATE(created_at) ORDER BY day ASC LIMIT 90"
);
$stmt->execute($rangeParams);
$daily = $stmt->fetchAll();
$maxDaily = 0.0;
foreach ($daily as $day) {
    $maxDaily = max($maxDaily, (float) $day['revenue']);
}

$stmt = $pdo->prepare(
    'SELECT movement_type, COUNT(*) AS events, SUM(qty_change) AS net_change
     FROM stock_movements WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY movement_type ORDER BY movement_type'
);
$stmt->execute($rangeParams);
$movementSummary = $stmt->fetchAll();

$auditLogs = $pdo->query(
    'SELECT a.*, u.nama_lengkap FROM admin_audit_logs a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.created_at DESC, a.id DESC LIMIT 20'
)->fetchAll();

$admin_title = 'Laporan';
$admin_page = 'reports';
include __DIR__ . '/includes/admin_header.php';
?>

<?php showFlash(); ?>

<div class="admin-page-head">
    <div>
        <p class="admin-eyebrow">Analitik operasional</p>
        <h2>Laporan</h2>
        <p class="admin-muted">Pendapatan hanya berasal dari pesanan yang pembayarannya sudah diverifikasi dan tidak dibatalkan.</p>
    </div>
    <a class="btn btn-outline" href="<?= e(site_url('admin/laporan.php?export=csv&from=' . urlencode($from) . '&to=' . urlencode($to))) ?>">
        <svg class="icon" aria-hidden="true"><use href="#i-arrow-right"></use></svg> Export CSV
    </a>
</div>

<form class="admin-filter-bar" method="get" action="<?= e(site_url('admin/laporan.php')) ?>">
    <label class="admin-inline-date">Dari <input type="date" name="from" value="<?= e($from) ?>"></label>
    <label class="admin-inline-date">Sampai <input type="date" name="to" value="<?= e($to) ?>"></label>
    <button class="btn btn-primary btn-sm" type="submit">Terapkan</button>
    <a class="btn btn-ghost btn-sm" href="<?= e(site_url('admin/laporan.php')) ?>">Bulan ini</a>
</form>

<div class="admin-kpi-grid admin-kpi-grid-4">
    <article class="admin-kpi">
        <p class="admin-kpi-label">Pendapatan terverifikasi</p>
        <p class="admin-kpi-value admin-kpi-money"><?= e(formatRupiah($revenue)) ?></p>
        <p class="admin-kpi-note"><?= $paidCount ?> pesanan dibayar</p>
    </article>
    <article class="admin-kpi">
        <p class="admin-kpi-label">Total pesanan</p>
        <p class="admin-kpi-value"><?= $orderCount ?></p>
        <p class="admin-kpi-note">Semua status</p>
    </article>
    <article class="admin-kpi">
        <p class="admin-kpi-label">Rata-rata pesanan dibayar</p>
        <p class="admin-kpi-value admin-kpi-money"><?= e(formatRupiah($avgOrder)) ?></p>
        <p class="admin-kpi-note">Dari transaksi terverifikasi</p>
    </article>
    <article class="admin-kpi">
        <p class="admin-kpi-label">Menunggu pembayaran</p>
        <p class="admin-kpi-value"><?= $pendingCount ?></p>
        <p class="admin-kpi-note">Belum boleh dihitung pendapatan</p>
    </article>
</div>

<div class="admin-two-col">
    <section class="admin-card">
        <div class="admin-card-head"><h3>Pendapatan harian terverifikasi</h3></div>
        <?php if ($daily): ?>
        <div class="admin-bars" role="img" aria-label="Grafik pendapatan harian terverifikasi">
            <?php foreach ($daily as $day): $width = $maxDaily > 0 ? max(2, ((float) $day['revenue'] / $maxDaily) * 100) : 2; ?>
            <div class="admin-bar-row">
                <span class="admin-bar-label"><?= e(format_date($day['day'], 'd M')) ?></span>
                <span class="admin-bar-track"><span class="admin-bar-fill" style="width: <?= e((string) round($width, 1)) ?>%"></span></span>
                <span class="admin-bar-value"><?= e(formatRupiah($day['revenue'])) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="admin-empty admin-empty-sm"><p>Belum ada pendapatan terverifikasi pada rentang ini.</p></div>
        <?php endif; ?>
    </section>

    <section class="admin-card">
        <div class="admin-card-head"><h3>Pesanan per status</h3></div>
        <?php if ($statusCounts): ?>
        <ul class="admin-status-list">
            <?php foreach ($statusCounts as $row): ?>
            <li>
                <span class="<?= e(badge_class($row['status'])) ?>"><?= e(order_status_label($row['status'])) ?></span>
                <strong><?= (int) $row['total'] ?></strong>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <div class="admin-empty admin-empty-sm"><p>Belum ada pesanan pada rentang ini.</p></div>
        <?php endif; ?>
        <p class="admin-hint">Dibatalkan: <?= $cancelledCount ?> pesanan pada rentang ini.</p>
    </section>
</div>

<div class="admin-two-col">
    <section class="admin-card">
        <div class="admin-card-head"><h3>Produk terlaris</h3><span class="admin-table-sub">Dari pesanan dibayar</span></div>
        <?php if ($topProducts): ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Produk</th><th>Terjual</th><th>Nilai</th></tr></thead>
                <tbody>
                    <?php foreach ($topProducts as $row): ?>
                    <tr><td><?= e($row['nama_produk']) ?></td><td><?= (int) $row['qty'] ?></td><td class="admin-money"><?= e(formatRupiah($row['revenue'])) ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="admin-empty admin-empty-sm"><p>Belum ada produk terjual dengan pembayaran terverifikasi.</p></div>
        <?php endif; ?>
    </section>

    <section class="admin-card">
        <div class="admin-card-head"><h3>Pergerakan stok</h3></div>
        <?php if ($movementSummary): ?>
        <ul class="admin-status-list">
            <?php foreach ($movementSummary as $row): ?>
            <li>
                <span><?= e(str_replace('_', ' ', $row['movement_type'])) ?></span>
                <strong><?= (int) $row['net_change'] > 0 ? '+' : '' ?><?= (int) $row['net_change'] ?></strong>
                <span class="admin-table-sub"><?= (int) $row['events'] ?> catatan</span>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <div class="admin-empty admin-empty-sm"><p>Belum ada pergerakan stok pada rentang ini.</p></div>
        <?php endif; ?>
    </section>
</div>

<section class="admin-card">
    <div class="admin-card-head"><h3>Aktivitas admin terakhir</h3></div>
    <?php if ($auditLogs): ?>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th>Waktu</th><th>Admin</th><th>Aksi</th><th>Entitas</th><th>IP</th></tr></thead>
            <tbody>
                <?php foreach ($auditLogs as $log): ?>
                <tr>
                    <td><?= e(format_date($log['created_at'], 'd M Y H:i')) ?></td>
                    <td><?= e($log['nama_lengkap'] ?: 'CLI/sistem') ?></td>
                    <td><?= e($log['action']) ?></td>
                    <td><?= e($log['entity_type']) ?><?= $log['entity_id'] ? ' #' . (int) $log['entity_id'] : '' ?></td>
                    <td><?= e($log['ip_address'] ?: '—') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="admin-empty admin-empty-sm"><p>Belum ada aktivitas admin tercatat.</p></div>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
