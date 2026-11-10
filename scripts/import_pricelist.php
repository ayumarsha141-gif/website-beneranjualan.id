<?php
/**
 * Safe, idempotent import of the owner-provided PDF transcription.
 * Run `python scripts/prepare_pricelist_media.py` first, then:
 *   php scripts/import_pricelist.php              (dry run)
 *   php scripts/import_pricelist.php --apply      (insert missing rows only)
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only.\n");
}

ob_start();
require_once dirname(__DIR__) . '/bouquet/includes/config.php';
ob_end_clean();

$apply = in_array('--apply', $argv, true);
$csvPath = dirname(__DIR__) . '/database/pricelist_import.csv';
if (!is_file($csvPath)) {
    fwrite(STDERR, "Pricelist CSV not found.\n");
    exit(1);
}

$handle = fopen($csvPath, 'rb');
if ($handle === false) {
    fwrite(STDERR, "Cannot open pricelist CSV.\n");
    exit(1);
}
$headers = fgetcsv($handle);
$rows = [];
while (($row = fgetcsv($handle)) !== false) {
    if (count($row) === count($headers)) {
        $rows[] = array_combine($headers, $row);
    }
}
fclose($handle);

$validModes = ['fixed', 'from', 'quote'];
$validActive = ['0', '1'];
$errors = [];
foreach ($rows as $index => $row) {
    $line = $index + 2;
    if (trim((string) $row['name']) === '' || trim((string) $row['category']) === '') {
        $errors[] = "CSV line {$line}: name and category are required.";
    }
    if (!in_array((string) $row['pricing_mode'], $validModes, true)) {
        $errors[] = "CSV line {$line}: invalid pricing_mode.";
    }
    if (!in_array((string) $row['is_active'], $validActive, true)) {
        $errors[] = "CSV line {$line}: is_active must be 0 or 1.";
    }
    if ((string) $row['pricing_mode'] === 'quote' && (float) $row['price'] !== 0.0) {
        $errors[] = "CSV line {$line}: quote-only rows must not contain a made-up numeric price.";
    }
    $sourceFiles = array_filter(array_merge(
        [trim((string) $row['source_image'])],
        explode('|', (string) $row['gallery_images'])
    ), static fn (string $path): bool => $path !== '');
    foreach ($sourceFiles as $relative) {
        $source = realpath(dirname(__DIR__) . '/' . $relative);
        $root = realpath(dirname(__DIR__));
        if (!$source || !$root || !str_starts_with($source, $root . DIRECTORY_SEPARATOR) || !is_file($source)) {
            $errors[] = "CSV line {$line}: missing or unsafe source image {$relative}.";
            continue;
        }
        $filename = 'catalog-' . substr(hash_file('sha256', $source), 0, 16) . '.webp';
        if (!is_file(dirname(__DIR__) . '/bouquet/assets/images/' . $filename)) {
            $errors[] = "CSV line {$line}: prepared WebP is missing for {$relative}; run scripts/prepare_pricelist_media.py first.";
        }
    }
}

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors) . "\n");
    exit(1);
}

$migrationColumn = $pdo->query("SHOW COLUMNS FROM produk LIKE 'pricing_mode'")->fetch();
if (!$migrationColumn) {
    fwrite(STDERR, "Pricing-mode migration is not applied. Run php scripts/setup_db.php first.\n");
    exit(1);
}

$newSlugs = [];
$skipped = 0;
$activeRows = 0;
foreach ($rows as $row) {
    $base = slugify($row['category'] . '-' . $row['name']);
    $slug = $base;
    $suffix = 2;
    while (isset($newSlugs[$slug])) {
        $slug = $base . '-' . $suffix++;
    }
    $newSlugs[$slug] = true;
    $exists = $pdo->prepare('SELECT id FROM produk WHERE slug = ?');
    $exists->execute([$slug]);
    if ($exists->fetchColumn()) {
        $skipped++;
    } elseif ((int) $row['is_active'] === 1) {
        $activeRows++;
    }
}

echo 'Pricelist rows: ' . count($rows) . PHP_EOL;
echo 'New product rows: ' . (count($rows) - $skipped) . PHP_EOL;
echo 'Already imported (left unchanged): ' . $skipped . PHP_EOL;
echo 'Active WhatsApp-inquiry rows: ' . $activeRows . PHP_EOL;
echo "Stock for every imported row: 0 / availability confirmed manually via WhatsApp.\n";
if (!$apply) {
    echo "DRY RUN only. Review database/pricelist_import.csv, then rerun with --apply.\n";
    exit(0);
}

$categoryCache = [];
$inserted = 0;
$galleryInserted = 0;
$pdo->beginTransaction();
try {
    foreach ($rows as $row) {
        $categoryName = clean_text($row['category'], 100);
        $categorySlug = slugify($categoryName);
        if (!isset($categoryCache[$categorySlug])) {
            $find = $pdo->prepare('SELECT id FROM categories WHERE slug = ? LIMIT 1');
            $find->execute([$categorySlug]);
            $categoryId = $find->fetchColumn();
            if (!$categoryId) {
                $pdo->prepare('INSERT INTO categories (nama, slug, deskripsi, sort_order, is_active, created_at, updated_at) VALUES (?, ?, ?, 200, 1, NOW(), NOW())')
                    ->execute([$categoryName, $categorySlug, 'Kategori dari pricelist pemilik; produk dan ketersediaan dikonfirmasi via WhatsApp.']);
                $categoryId = (int) $pdo->lastInsertId();
            }
            $categoryCache[$categorySlug] = (int) $categoryId;
        }

        $slug = slugify($categoryName . '-' . $row['name']);
        $find = $pdo->prepare('SELECT id FROM produk WHERE slug = ? LIMIT 1');
        $find->execute([$slug]);
        if ($find->fetchColumn()) {
            $skipped++;
            continue;
        }
        $imageSources = array_values(array_filter(array_merge(
            [trim((string) $row['source_image'])],
            explode('|', (string) $row['gallery_images'])
        ), static fn (string $path): bool => $path !== ''));
        $imageFiles = [];
        foreach ($imageSources as $relative) {
            $real = realpath(dirname(__DIR__) . '/' . $relative);
            $imageFiles[] = 'catalog-' . substr(hash_file('sha256', (string) $real), 0, 16) . '.webp';
        }
        $mainImage = $imageFiles[0] ?? null;
        $price = (int) $row['price'];
        $pricingMode = (string) $row['pricing_mode'];
        $sourcePage = clean_text($row['source_page'], 30);
        // CSV notes and page references stay internal. Storefront copy only
        // directs buyers to WhatsApp, as requested by the owner.
        $pricingNote = '';
        $description = '';
        $isActive = (int) $row['is_active'];
        $pdo->prepare(
            'INSERT INTO produk (nama, slug, deskripsi, harga, pricing_mode, availability_mode, pricing_note, stok, kategori, category_id, gambar, is_active, is_featured, is_demo, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, 0, 0, NOW(), NOW())'
        )->execute([
            clean_text($row['name'], 160), $slug, $description, $price, $pricingMode,
            'request', $pricingNote, $categoryName, $categoryCache[$categorySlug], $mainImage, $isActive,
        ]);
        $productId = (int) $pdo->lastInsertId();
        if (count($imageFiles) > 1) {
            $gallery = $pdo->prepare('INSERT INTO product_images (produk_id, filename, alt_text, sort_order, created_at) VALUES (?, ?, ?, ?, NOW())');
            foreach (array_slice($imageFiles, 1) as $offset => $filename) {
                $gallery->execute([$productId, $filename, clean_text($row['name'], 160), $offset + 1]);
                $galleryInserted++;
            }
        }
        log_admin_action($pdo, 'pricelist_import', 'product', $productId, [
            'source_page' => $sourcePage,
            'pricing_mode' => $pricingMode,
            'availability_mode' => 'request',
            'is_active' => $isActive,
        ]);
        $inserted++;
    }
    $pdo->commit();
    echo "APPLIED: inserted {$inserted} products and {$galleryInserted} gallery images; skipped {$skipped} existing slugs. No stock was invented.\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, 'Import rolled back: ' . $e->getMessage() . "\n");
    exit(1);
}
