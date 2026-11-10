<?php
require_once __DIR__ . '/includes/config.php';

header('Content-Type: text/plain; charset=utf-8');
echo "User-agent: *\n";
echo "Disallow: /admin/\n";
echo "Disallow: /cart.php\n";
echo "Disallow: /checkout.php\n";
echo "Disallow: /proses_\n";
echo "Disallow: /profil.php\n";
echo "Disallow: /pesanan-\n";
echo "Disallow: /terima-kasih.php\n";
echo "Disallow: /lacak-pesanan.php\n";
echo 'Sitemap: ' . site_url('sitemap.php') . "\n";
