<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$cart_count = getCartCount();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= SITE_NAME ?> - Toko Buket Bunga Premium Online">
    <title><?= isset($page_title) ? $page_title . ' - ' : '' ?><?= SITE_NAME ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="[fonts.googleapis.com](https://fonts.googleapis.com)">
    <link href="[fonts.googleapis.com](https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap)" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="[cdnjs.cloudflare.com](https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css)">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="http://localhost/bouquet/assets/styles.css">
</head>
<body>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="spinner"></div>
</div>

<!-- Navbar -->
<nav class="navbar">
    <div class="container navbar-container">
        <a href="<?= SITE_URL ?>" class="logo">
            <i class="fas fa-seedling"></i>
            <?= SITE_NAME ?>
        </a>
        
        <button class="nav-toggle" id="navToggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <ul class="nav-menu" id="navMenu">
            <li><a href="<?= SITE_URL ?>" class="nav-link"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="<?= SITE_URL ?>#produk" class="nav-link"><i class="fas fa-shopping-bag"></i> Produk</a></li>
            <li><a href="<?= SITE_URL ?>#tentang" class="nav-link"><i class="fas fa-info-circle"></i> Tentang</a></li>
            <li><a href="<?= SITE_URL ?>#kontak" class="nav-link"><i class="fas fa-phone"></i> Kontak</a></li>
        </ul>
        
        <div class="nav-actions">
            <a href="<?= SITE_URL ?>/cart.php" class="cart-btn">
                <i class="fas fa-shopping-cart"></i>
                <?php if ($cart_count > 0): ?>
                    <span class="cart-badge"><?= $cart_count ?></span>
                <?php endif; ?>
            </a>
            
            <?php if (isLoggedIn()): ?>
                <div class="user-dropdown">
                    <button class="user-btn">
                        <i class="fas fa-user-circle"></i>
                        <span><?= sanitize($_SESSION['username']) ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="dropdown-menu">
                        <?php if (isAdmin()): ?>
                            <a href="<?= SITE_URL ?>/admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                        <?php endif; ?>
                        <a href="<?= SITE_URL ?>/profil.php"><i class="fas fa-user"></i> Profil</a>
                        <a href="<?= SITE_URL ?>/pesanan.php"><i class="fas fa-box"></i> Pesanan Saya</a>
                        <hr>
                        <a href="<?= SITE_URL ?>/logout.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= SITE_URL ?>/login.php" class="btn btn-outline btn-sm">Login</a>
                <a href="<?= SITE_URL ?>/register.php" class="btn btn-primary btn-sm">Daftar</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main>
