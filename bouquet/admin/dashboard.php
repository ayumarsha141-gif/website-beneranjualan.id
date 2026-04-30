<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isAdmin()) {
    redirect('login.php', 'Akses ditolak!', 'error');
}

$page_title = 'Dashboard Admin';

//statistik
$total_orders = $pdo->query("SELECT COUNT(*) FROM pesanan")->fetchColumn();
$total_products = $pdo->query("SELECT COUNT(*) FROM produk")->fetchColumn();
$total_customers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
$total_revenue = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM pesanan WHERE status != 'batal'")->fetchColumn();

// Recent orders
$recent_orders = $pdo->query("SELECT * FROM pesanan ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - <?= SITE_NAME ?></title>
    <link href="[fonts.googleapis.com](https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap)" rel="stylesheet">
    <link rel="stylesheet" href="[cdnjs.cloudflare.com](https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css)">
    <link rel="stylesheet" href="http://localhost/bouquet/assets/styles.css">
    <style>
        .admin-layout { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: var(--dark); color: var(--white); padding: 20px 0; position: fixed; height: 100vh; }
        .sidebar-header { padding: 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 20px; }
        .sidebar-menu a { display: flex; align-items: center; gap: 12px; padding: 15px 25px; color: rgba(255,255,255,0.7); }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: rgba(255,255,255,0.1); color: var(--white); }
        .sidebar-menu a i { width: 20px; }
        .main-content { margin-left: 260px; flex: 1; background: var(--light); }
        .admin-header { background: var(--white); padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: var(--shadow-sm); }
        .admin-body { padding: 30px; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 25px; margin-bottom: 30px; }
        .stat-card { background: var(--white); padding: 25px; border-radius: var(--radius-lg); box-shadow: var(--shadow); }
        .stat-card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .stat-card-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .stat-card h3 { font-size: 28px; color: var(--dark); margin-bottom: 5px; }
        .stat-card p { color: var(--gray); font-size: 14px; }
        .data-table { width: 100%; background: var(--white); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow); }
        .data-table th, .data-table td { padding: 15px 20px; text-align: left; }
        .data-table th { background: var(--light); font-weight: 600; color: var(--dark); }
        .data-table tr:not(:last-child) td { border-bottom: 1px solid var(--gray-light); }
        .badge { padding: 5px 12px; border-radius: var(--radius-full); font-size: 12px; font-weight: 500; }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-proses { background: #cce5ff; color: #004085; }
        .badge-selesai { background: #d4edda; color: #155724; }
        .badge-batal { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2 style="font-size: 20px;"><i class="fas fa-seedling"></i> Admin Panel</h2>
            </div>
            <nav class="sidebar-menu">
                <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="pesanan.php"><i class="fas fa-shopping-bag"></i> Pesanan</a>
                <a href="produk.php"><i class="fas fa-box"></i> Produk</a>
                <a href="pelanggan.php"><i class="fas fa-users"></i> Pelanggan</a>
                <a href="laporan.php"><i class="fas fa-chart-bar"></i> Laporan</a>
                <a href="pengaturan.php"><i class="fas fa-cog"></i> Pengaturan</a>
                <hr style="border-color: rgba(255,255,255,0.1); margin: 20px;">
                <a href="<?= SITE_URL ?>"><i class="fas fa-store"></i> Lihat Toko</a>
                <a href="<?= SITE_URL ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <div class="main-content">
            <header class="admin-header">
                <h1 style="font-size: 24px; color: var(--dark);">Dashboard</h1>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <span>Halo, <?= sanitize($_SESSION['nama_lengkap']) ?></span>
                    <div style="width: 40px; height: 40px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <?= strtoupper(substr($_SESSION['nama_lengkap'], 0, 1)) ?>
                    </div>
                </div>
            </header>
            
            <div class="admin-body">
                <?php showFlash(); ?>
                
                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div class="stat-card-icon" style="background: rgba(233,30,99,0.1); color: var(--primary);">
                                <i class="fas fa-shopping-bag"></i>
                            </div>
                        </div>
                        <h3><?= number_format($total_orders) ?></h3>
                        <p>Total Pesanan</p>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div class="stat-card-icon" style="background: rgba(40,167,69,0.1); color: var(--success);">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                        </div>
                        <h3><?= formatRupiah($total_revenue) ?></h3>
                        <p>Total Pendapatan</p>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div class="stat-card-icon" style="background: rgba(23,162,184,0.1); color: var(--info);">
                                <i class="fas fa-box"></i>
                            </div>
                        </div>
                        <h3><?= number_format($total_products) ?></h3>
                        <p>Total Produk</p>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div class="stat-card-icon" style="background: rgba(255,193,7,0.1); color: var(--warning);">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                        <h3><?= number_format($total_customers) ?></h3>
                        <p>Total Pelanggan</p>
                    </div>
                </div>
                
                <!-- Recent Orders -->
                <div style="background: var(--white); border-radius: var(--radius-lg); box-shadow: var(--shadow); overflow: hidden;">
                    <div style="padding: 20px 25px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="font-size: 18px; color: var(--dark);">Pesanan Terbaru</h3>
                        <a href="pesanan.php" class="btn btn-sm btn-outline">Lihat Semua</a>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Pelanggan</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                            <tr>
                                <td>#<?= $order['id'] ?></td>
                                <td><?= sanitize($order['nama']) ?></td>
                                <td><?= formatRupiah($order['total']) ?></td>
                                <td><span class="badge badge-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span></td>
                                <td><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                                <td>
                                    <a href="detail-pesanan.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline">Detail</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
