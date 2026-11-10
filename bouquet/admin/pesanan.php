<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

$status = clean_text($_GET['status'] ?? '', 30);
$payment = clean_text($_GET['payment'] ?? '', 30);
$q = clean_text($_GET['q'] ?? '', 80);
$from = clean_text($_GET['from'] ?? '', 10);
$to = clean_text($_GET['to'] ?? '', 10);

$where = ['1=1'];
$params = [];
if ($status !== '' && array_key_exists($status, order_status_labels())) {
    $where[] = 'status = ?';
    $params[] = $status;
} else {
    $status = '';
}
if ($payment !== '' && array_key_exists($payment, payment_status_labels())) {
    $where[] = 'payment_status = ?';
    $params[] = $payment;
} else {
    $payment = '';
}
if ($q !== '') {
    $where[] = '(order_number LIKE ? OR nama LIKE ? OR email LIKE ? OR telepon LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like);
}
if ($from !== '' && DateTimeImmutable::createFromFormat('!Y-m-d', $from)) {
    $where[] = 'DATE(created_at) >= ?';
    $params[] = $from;
}
if ($to !== '' && DateTimeImmutable::createFromFormat('!Y-m-d', $to)) {
    $where[] = 'DATE(created_at) <= ?';
    $params[] = $to;
}
$whereSql = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM pesanan WHERE $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$pagination = paginate($total, 20, max(1, (int) ($_GET['page'] ?? 1)));

$stmt = $pdo->prepare("SELECT * FROM pesanan WHERE $whereSql ORDER BY created_at DESC LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}");
$stmt->execute($params);
$orders = $stmt->fetchAll();

$filterQuery = array_filter([
    'status' => $status,
    'payment' => $payment,
    'q' => $q,
    'from' => $from,
    'to' => $to,
], fn ($v) => $v !== '');

$admin_title = 'Pesanan';
$admin_page = 'orders';
include __DIR__ . '/includes/admin_header.php';
?>

<?php showFlash(); ?>

<div class="admin-page-head">
    <div>
        <p class="admin-eyebrow">Operasional</p>
        <h2>Daftar pesanan <span class="admin-count"><?= $total ?></span></h2>
        <p class="admin-muted">Status fulfillment dan status pembayaran dipisahkan. Pembayaran tidak dianggap lunas sebelum diverifikasi.</p>
    </div>
</div>

<form class="admin-filter-bar" method="get" action="<?= e(site_url('admin/pesanan.php')) ?>">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari nomor, nama, email, telepon">
    <select name="status">
        <option value="">Semua status</option>
        <?php foreach (order_status_labels() as $key => $label): ?>
            <option value="<?= e($key) ?>" <?= $status === $key ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="payment">
        <option value="">Semua pembayaran</option>
        <?php foreach (payment_status_labels() as $key => $label): ?>
            <option value="<?= e($key) ?>" <?= $payment === $key ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <label class="admin-inline-date">Dari <input type="date" name="from" value="<?= e($from) ?>"></label>
    <label class="admin-inline-date">Sampai <input type="date" name="to" value="<?= e($to) ?>"></label>
    <button class="btn btn-primary btn-sm" type="submit">Filter</button>
    <a class="btn btn-ghost btn-sm" href="<?= e(site_url('admin/pesanan.php')) ?>">Reset</a>
</form>

<section class="admin-card">
    <?php if ($orders): ?>
    <div class="admin-table-wrap">
        <table class="admin-table admin-table-orders">
            <thead>
                <tr>
                    <th>Pesanan</th>
                    <th>Pelanggan</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Pembayaran</th>
                    <th>Tanggal</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td>
                        <a class="admin-table-title" href="<?= e(site_url('admin/detail-pesanan.php?id=' . (int) $order['id'])) ?>"><?= e($order['order_number']) ?></a>
                        <span class="admin-table-sub"><?= $order['metode_bayar'] === 'cod' ? 'COD' : 'Transfer' ?></span>
                    </td>
                    <td>
                        <?= e($order['nama']) ?>
                        <span class="admin-table-sub"><?= e($order['telepon']) ?></span>
                    </td>
                    <td class="admin-money"><?= e(formatRupiah($order['total'])) ?></td>
                    <td><span class="<?= e(badge_class($order['status'])) ?>"><?= e(order_status_label($order['status'])) ?></span></td>
                    <td><span class="<?= e(badge_class($order['payment_status'])) ?>"><?= e(payment_status_label($order['payment_status'])) ?></span></td>
                    <td><?= e(format_date($order['created_at'], 'd M Y')) ?><span class="admin-table-sub"><?= e(format_date($order['created_at'], 'H:i')) ?></span></td>
                    <td><a class="btn btn-outline btn-sm" href="<?= e(site_url('admin/detail-pesanan.php?id=' . (int) $order['id'])) ?>">Kelola</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= pagination_links($pagination, $filterQuery) ?>
    <?php else: ?>
    <div class="admin-empty">
        <svg class="icon icon-lg" aria-hidden="true"><use href="#i-package"></use></svg>
        <h4>Belum ada pesanan yang cocok</h4>
        <p>Pesanan baru dari pelanggan akan muncul di sini. Filter tanggal atau status dapat direset.</p>
        <a class="btn btn-outline btn-sm" href="<?= e(site_url('admin/pesanan.php')) ?>">Reset filter</a>
    </div>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
