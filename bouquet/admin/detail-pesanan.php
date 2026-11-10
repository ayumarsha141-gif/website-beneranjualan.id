<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect('admin/pesanan.php', 'Pesanan tidak ditemukan.', 'error');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = $_POST['action'] ?? '';
    $adminId = (int) $_SESSION['user_id'];

    $stmt = $pdo->prepare('SELECT * FROM pesanan WHERE id = ? FOR UPDATE');
    try {
        $pdo->beginTransaction();
        $stmt->execute([$id]);
        $current = $stmt->fetch();
        if (!$current) {
            throw new DomainException('Pesanan tidak ditemukan.');
        }

        if ($action === 'update_status') {
            $newStatus = clean_text($_POST['status'] ?? '', 30);
            if (!array_key_exists($newStatus, order_status_labels())) {
                throw new DomainException('Status tidak valid.');
            }
            if (!order_transition_allowed($current['status'], $newStatus)) {
                throw new DomainException('Perubahan status dari ' . order_status_label($current['status']) . ' ke ' . order_status_label($newStatus) . ' tidak diizinkan.');
            }

            $pdo->prepare('UPDATE pesanan SET status = ?, updated_at = NOW() WHERE id = ?')->execute([$newStatus, $id]);
            $pdo->prepare(
                'INSERT INTO order_status_history (pesanan_id, field_name, old_value, new_value, note, changed_by, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())'
            )->execute([$id, 'status', $current['status'], $newStatus, 'Diubah admin', $adminId]);

            if ($newStatus === 'dibatalkan' && empty($current['stock_restored_at'])) {
                $detailStmt = $pdo->prepare('SELECT produk_id, nama_produk, qty FROM detail_pesanan WHERE pesanan_id = ?');
                $detailStmt->execute([$id]);
                $restoreStmt = $pdo->prepare('UPDATE produk SET stok = stok + ? WHERE id = ?');
                $movement = $pdo->prepare(
                    'INSERT INTO stock_movements (produk_id, qty_change, movement_type, reference_type, reference_id, note, created_by, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
                );
                foreach ($detailStmt->fetchAll() as $line) {
                    if ($line['produk_id'] === null) {
                        continue;
                    }
                    $restoreStmt->execute([(int) $line['qty'], (int) $line['produk_id']]);
                    $movement->execute([
                        (int) $line['produk_id'],
                        (int) $line['qty'],
                        'cancel_restock',
                        'order',
                        $id,
                        'Pembatalan pesanan ' . $current['order_number'],
                        $adminId,
                    ]);
                }
                $pdo->prepare('UPDATE pesanan SET stock_restored_at = NOW() WHERE id = ?')->execute([$id]);
            }

            if ($newStatus === 'dibatalkan' && $current['payment_status'] === 'menunggu_pembayaran') {
                $pdo->prepare("UPDATE pesanan SET payment_status = 'dibatalkan' WHERE id = ?")->execute([$id]);
                $pdo->prepare(
                    'INSERT INTO order_status_history (pesanan_id, field_name, old_value, new_value, note, changed_by, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, NOW())'
                )->execute([$id, 'payment_status', 'menunggu_pembayaran', 'dibatalkan', 'Pesanan dibatalkan', $adminId]);
            }

            log_admin_action($pdo, 'order_status_update', 'order', $id, ['from' => $current['status'], 'to' => $newStatus]);
            $pdo->commit();
            redirect('admin/detail-pesanan.php?id=' . $id, 'Status pesanan diperbarui.', 'success');
        }

        if ($action === 'update_payment') {
            $newPayment = clean_text($_POST['payment_status'] ?? '', 30);
            if (!array_key_exists($newPayment, payment_status_labels())) {
                throw new DomainException('Status pembayaran tidak valid.');
            }
            if (!payment_transition_allowed($current['payment_status'], $newPayment)) {
                throw new DomainException('Perubahan pembayaran tidak diizinkan.');
            }
            if ($newPayment === 'dibayar') {
                $pdo->prepare('UPDATE pesanan SET payment_status = ?, payment_confirmed_at = NOW(), payment_confirmed_by = ?, updated_at = NOW() WHERE id = ?')
                    ->execute([$newPayment, $adminId, $id]);
            } else {
                $pdo->prepare('UPDATE pesanan SET payment_status = ?, updated_at = NOW() WHERE id = ?')
                    ->execute([$newPayment, $id]);
            }
            $pdo->prepare(
                'INSERT INTO order_status_history (pesanan_id, field_name, old_value, new_value, note, changed_by, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())'
            )->execute([$id, 'payment_status', $current['payment_status'], $newPayment, 'Diverifikasi admin', $adminId]);

            log_admin_action($pdo, 'order_payment_update', 'order', $id, ['from' => $current['payment_status'], 'to' => $newPayment]);
            $pdo->commit();
            redirect('admin/detail-pesanan.php?id=' . $id, 'Status pembayaran diperbarui.', 'success');
        }

        if ($action === 'update_shipping') {
            $tracking = clean_text($_POST['tracking_number'] ?? '', 100);
            $note = clean_text($_POST['admin_note'] ?? '', 2000);
            $pdo->prepare('UPDATE pesanan SET tracking_number = ?, admin_note = ?, updated_at = NOW() WHERE id = ?')
                ->execute([$tracking, $note, $id]);
            log_admin_action($pdo, 'order_shipping_update', 'order', $id, ['tracking' => $tracking]);
            $pdo->commit();
            redirect('admin/detail-pesanan.php?id=' . $id, 'Catatan pengiriman disimpan.', 'success');
        }

        throw new DomainException('Aksi tidak dikenal.');
    } catch (DomainException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if (!headers_sent()) {
            flash($e->getMessage(), 'error');
            redirect('admin/detail-pesanan.php?id=' . $id);
        }
        throw $e;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Admin order update failed: ' . $e->getMessage());
        flash('Perubahan gagal disimpan karena kendala sistem.', 'error');
        redirect('admin/detail-pesanan.php?id=' . $id);
    }
}

$stmt = $pdo->prepare('SELECT * FROM pesanan WHERE id = ?');
$stmt->execute([$id]);
$order = $stmt->fetch();
if (!$order) {
    redirect('admin/pesanan.php', 'Pesanan tidak ditemukan.', 'error');
}

$stmt = $pdo->prepare('SELECT * FROM detail_pesanan WHERE pesanan_id = ? ORDER BY id');
$stmt->execute([$id]);
$items = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT h.*, u.nama_lengkap AS admin_name FROM order_status_history h LEFT JOIN users u ON u.id = h.changed_by WHERE h.pesanan_id = ? ORDER BY h.created_at, h.id');
$stmt->execute([$id]);
$history = $stmt->fetchAll();

$nextStatuses = [];
foreach (order_status_labels() as $key => $label) {
    if (order_transition_allowed($order['status'], $key)) {
        $nextStatuses[$key] = $label;
    }
}
$nextPayments = [];
foreach (payment_status_labels() as $key => $label) {
    if (payment_transition_allowed($order['payment_status'], $key)) {
        $nextPayments[$key] = $label;
    }
}

$admin_title = 'Detail Pesanan';
$admin_page = 'orders';
include __DIR__ . '/includes/admin_header.php';
?>

<?php showFlash(); ?>

<div class="admin-page-head">
    <div>
        <a class="admin-back" href="<?= e(site_url('admin/pesanan.php')) ?>">← Kembali ke daftar pesanan</a>
        <h2><?= e($order['order_number']) ?></h2>
        <p class="admin-muted">Dibuat <?= e(format_date($order['created_at'], 'd M Y H:i')) ?> · <?= e($order['metode_bayar'] === 'cod' ? 'Bayar di tempat' : 'Transfer bank manual') ?></p>
    </div>
    <div class="admin-order-badges">
        <span class="<?= e(badge_class($order['status'])) ?>"><?= e(order_status_label($order['status'])) ?></span>
        <span class="<?= e(badge_class($order['payment_status'])) ?>"><?= e(payment_status_label($order['payment_status'])) ?></span>
    </div>
</div>

<div class="admin-order-layout">
    <div class="admin-order-main">
        <section class="admin-card">
            <div class="admin-card-head"><h3>Item pesanan</h3><span class="admin-table-sub"><?= count($items) ?> item</span></div>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead><tr><th>Produk</th><th>Harga</th><th>Qty</th><th>Subtotal</th></tr></thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <div class="admin-product-cell">
                                    <img src="<?= e(product_image_url($item['gambar_produk'])) ?>" alt="" width="40" height="50" loading="lazy">
                                    <div>
                                        <span class="admin-table-title"><?= e($item['nama_produk']) ?></span>
                                        <?php if ($item['produk_id']): ?><a class="admin-table-sub" href="<?= e(site_url('admin/produk-edit.php?id=' . (int) $item['produk_id'])) ?>">Kelola produk</a><?php else: ?><span class="admin-table-sub">Produk diarsipkan</span><?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="admin-money"><?= e(formatRupiah($item['harga'])) ?></td>
                            <td><?= (int) $item['qty'] ?></td>
                            <td class="admin-money"><?= e(formatRupiah($item['subtotal'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <dl class="admin-totals">
                <div><dt>Subtotal</dt><dd><?= e(formatRupiah($order['subtotal'])) ?></dd></div>
                <div><dt>Ongkos kirim</dt><dd><?= (float) $order['shipping_cost'] > 0 ? e(formatRupiah($order['shipping_cost'])) : 'Belum dibebankan' ?></dd></div>
                <?php if ((float) $order['discount'] > 0): ?><div><dt>Diskon</dt><dd>-<?= e(formatRupiah($order['discount'])) ?></dd></div><?php endif; ?>
                <div class="is-total"><dt>Total</dt><dd><?= e(formatRupiah($order['total'])) ?></dd></div>
            </dl>
        </section>

        <section class="admin-card">
            <div class="admin-card-head"><h3>Data pelanggan & pengiriman</h3></div>
            <div class="admin-detail-grid">
                <dl class="admin-detail-list">
                    <div><dt>Nama</dt><dd><?= e($order['nama']) ?></dd></div>
                    <div><dt>Telepon</dt><dd><a href="tel:<?= e($order['telepon']) ?>"><?= e($order['telepon']) ?></a></dd></div>
                    <div><dt>Email</dt><dd><a href="mailto:<?= e($order['email']) ?>"><?= e($order['email']) ?></a></dd></div>
                    <div><dt>Alamat</dt><dd><?= nl2br(e($order['alamat'])) ?></dd></div>
                </dl>
                <dl class="admin-detail-list">
                    <div><dt>Permintaan tanggal</dt><dd><?= $order['delivery_date'] ? e(format_date($order['delivery_date'], 'd M Y')) : '—' ?></dd></div>
                    <div><dt>Perkiraan jam</dt><dd><?= $order['delivery_time'] ? e($order['delivery_time']) : '—' ?></dd></div>
                    <div><dt>Catatan pelanggan</dt><dd><?= $order['catatan'] ? nl2br(e($order['catatan'])) : '—' ?></dd></div>
                    <div><dt>Pembayaran dikonfirmasi</dt><dd><?= $order['payment_confirmed_at'] ? e(format_date($order['payment_confirmed_at'], 'd M Y H:i')) : 'Belum' ?></dd></div>
                </dl>
            </div>
        </section>

        <section class="admin-card">
            <div class="admin-card-head"><h3>Riwayat status</h3></div>
            <?php if ($history): ?>
            <ul class="admin-timeline">
                <?php foreach ($history as $row): ?>
                <li>
                    <span class="admin-timeline-dot" aria-hidden="true"></span>
                    <div>
                        <p class="admin-timeline-title">
                            <?= $row['field_name'] === 'payment_status' ? 'Pembayaran' : 'Status' ?>:
                            <strong><?= e($row['field_name'] === 'payment_status' ? payment_status_label((string) $row['new_value']) : order_status_label((string) $row['new_value'])) ?></strong>
                        </p>
                        <p class="admin-table-sub"><?= e(format_date($row['created_at'], 'd M Y H:i')) ?><?= $row['admin_name'] ? ' · ' . e($row['admin_name']) : '' ?><?= $row['note'] ? ' · ' . e($row['note']) : '' ?></p>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <div class="admin-empty admin-empty-sm"><p>Belum ada riwayat.</p></div>
            <?php endif; ?>
        </section>
    </div>

    <aside class="admin-order-side">
        <section class="admin-card">
            <div class="admin-card-head"><h3>Status pesanan</h3></div>
            <?php if ($nextStatuses): ?>
            <form method="post" class="admin-form-stack" data-confirm-form="Perubahan status akan dicatat di riwayat pesanan.">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update_status">
                <label>Status berikutnya
                    <select name="status" required>
                        <?php foreach ($nextStatuses as $key => $label): ?><option value="<?= e($key) ?>"><?= e($label) ?></option><?php endforeach; ?>
                    </select>
                </label>
                <button class="btn btn-primary btn-block" type="submit">Simpan status</button>
            </form>
            <?php else: ?>
                <p class="admin-muted">Tidak ada transisi status yang tersedia untuk status saat ini.</p>
            <?php endif; ?>
            <p class="admin-hint">Pembatalan akan mengembalikan stok satu kali dan mencatat pergerakan stok.</p>
        </section>

        <section class="admin-card">
            <div class="admin-card-head"><h3>Pembayaran</h3></div>
            <?php if ($nextPayments): ?>
            <form method="post" class="admin-form-stack" data-confirm-form="Pastikan pembayaran benar-benar sudah diterima sebelum memverifikasi.">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update_payment">
                <label>Status pembayaran
                    <select name="payment_status" required>
                        <?php foreach ($nextPayments as $key => $label): ?><option value="<?= e($key) ?>"><?= e($label) ?></option><?php endforeach; ?>
                    </select>
                </label>
                <button class="btn btn-primary btn-block" type="submit">Simpan pembayaran</button>
            </form>
            <?php else: ?>
                <p class="admin-muted">Status pembayaran final: <strong><?= e(payment_status_label($order['payment_status'])) ?></strong>.</p>
            <?php endif; ?>
            <p class="admin-hint">Hanya verifikasi setelah dana/instruksi pembayaran benar-benar diterima.</p>
        </section>

        <section class="admin-card">
            <div class="admin-card-head"><h3>Pengiriman</h3></div>
            <form method="post" class="admin-form-stack">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update_shipping">
                <label>Nomor resi / referensi
                    <input type="text" name="tracking_number" maxlength="100" value="<?= e($order['tracking_number'] ?? '') ?>">
                </label>
                <label>Catatan admin (tampil di halaman pesanan pelanggan)
                    <textarea name="admin_note" rows="4" maxlength="2000"><?= e($order['admin_note'] ?? '') ?></textarea>
                </label>
                <button class="btn btn-outline btn-block" type="submit">Simpan pengiriman</button>
            </form>
        </section>
    </aside>
</div>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
