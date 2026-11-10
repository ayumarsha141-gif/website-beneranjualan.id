<?php
require_once __DIR__ . '/includes/config.php';

header('Content-Type: application/xml; charset=utf-8');
$urls = [
    ['loc' => site_url(), 'lastmod' => date('Y-m-d')],
    ['loc' => site_url('katalog.php'), 'lastmod' => date('Y-m-d')],
    ['loc' => site_url('kebijakan.php'), 'lastmod' => date('Y-m-d')],
];

try {
    $stmt = $pdo->query(
        "SELECT slug, MAX(updated_at) AS updated_at FROM categories c
         WHERE is_active = 1 AND EXISTS (
             SELECT 1 FROM produk p WHERE p.category_id = c.id AND p.is_active = 1 AND p.is_demo = 0
         ) GROUP BY id, slug"
    );
    foreach ($stmt->fetchAll() as $category) {
        $urls[] = [
            'loc' => site_url('katalog.php?kategori=' . rawurlencode((string) $category['slug'])),
            'lastmod' => substr((string) $category['updated_at'], 0, 10),
        ];
    }

    $stmt = $pdo->query("SELECT id, updated_at FROM produk WHERE is_active = 1 AND is_demo = 0 ORDER BY id");
    foreach ($stmt->fetchAll() as $product) {
        $urls[] = [
            'loc' => site_url('produk.php?id=' . (int) $product['id']),
            'lastmod' => substr((string) $product['updated_at'], 0, 10),
        ];
    }
} catch (Throwable $e) {
    error_log('Sitemap query failed: ' . $e->getMessage());
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $url) {
    echo "  <url>\n";
    echo '    <loc>' . htmlspecialchars($url['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc>\n";
    if ($url['lastmod'] !== '') {
        echo '    <lastmod>' . htmlspecialchars($url['lastmod'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</lastmod>\n";
    }
    echo "  </url>\n";
}
echo "</urlset>\n";
