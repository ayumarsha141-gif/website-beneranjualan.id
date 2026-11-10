-- DEMO CATALOG ONLY.
-- These rows exist so a fresh install can be tested. They are marked
-- is_demo = 1 and must be replaced/archived by the owner before going live.
-- This file deliberately does NOT create fake customers, orders, payments,
-- reviews, ratings, or revenue.

INSERT INTO categories (nama, slug, deskripsi, sort_order, is_active) VALUES
    ('Wisuda', 'wisuda', 'Rangkaian bunga untuk momen kelulusan.', 10, 1),
    ('Ulang Tahun', 'ulang-tahun', 'Hadiah bunga untuk merayakan ulang tahun.', 20, 1),
    ('Anniversary', 'anniversary', 'Buket untuk merayakan perjalanan bersama.', 30, 1),
    ('Hadiah', 'hadiah', 'Pilihan buket untuk berbagai kesempatan.', 40, 1)
ON DUPLICATE KEY UPDATE nama = VALUES(nama), deskripsi = VALUES(deskripsi);

INSERT INTO produk (nama, slug, deskripsi, harga, stok, kategori, category_id, gambar, is_active, is_featured, is_demo)
SELECT 'Crona Bouquet', 'crona-bouquet',
       '[DEMO] Contoh katalog. Foto berasal dari arsip toko. Harga dan stok adalah data uji, bukan inventaris final. Komposisi dan ketersediaan bunga dikonfirmasi saat pemesanan.',
       125000.00, 10, 'Anniversary', (SELECT id FROM categories WHERE slug = 'anniversary'),
       'Crona.jpeg', 1, 1, 1
WHERE NOT EXISTS (SELECT 1 FROM produk WHERE slug = 'crona-bouquet');

INSERT INTO produk (nama, slug, deskripsi, harga, stok, kategori, category_id, gambar, is_active, is_featured, is_demo)
SELECT 'Pirousha Bouquet', 'pirousha-bouquet',
       '[DEMO] Contoh katalog. Foto berasal dari arsip toko. Harga dan stok adalah data uji, bukan inventaris final. Komposisi dan ketersediaan bunga dikonfirmasi saat pemesanan.',
       145000.00, 8, 'Ulang Tahun', (SELECT id FROM categories WHERE slug = 'ulang-tahun'),
       'Pirousha.jpeg', 1, 1, 1
WHERE NOT EXISTS (SELECT 1 FROM produk WHERE slug = 'pirousha-bouquet');

INSERT INTO produk (nama, slug, deskripsi, harga, stok, kategori, category_id, gambar, is_active, is_featured, is_demo)
SELECT 'Raisha Bouquet', 'raisha-bouquet',
       '[DEMO] Contoh katalog. Foto berasal dari arsip toko. Harga dan stok adalah data uji, bukan inventaris final. Komposisi dan ketersediaan bunga dikonfirmasi saat pemesanan.',
       165000.00, 6, 'Wisuda', (SELECT id FROM categories WHERE slug = 'wisuda'),
       'Raisha.jpeg', 1, 1, 1
WHERE NOT EXISTS (SELECT 1 FROM produk WHERE slug = 'raisha-bouquet');

INSERT INTO produk (nama, slug, deskripsi, harga, stok, kategori, category_id, gambar, is_active, is_featured, is_demo)
SELECT 'Sisha Bouquet', 'sisha-bouquet',
       '[DEMO] Contoh katalog. Foto berasal dari arsip toko. Harga dan stok adalah data uji, bukan inventaris final. Komposisi dan ketersediaan bunga dikonfirmasi saat pemesanan.',
       135000.00, 9, 'Hadiah', (SELECT id FROM categories WHERE slug = 'hadiah'),
       'Sisha.jpeg', 1, 0, 1
WHERE NOT EXISTS (SELECT 1 FROM produk WHERE slug = 'sisha-bouquet');

INSERT INTO produk (nama, slug, deskripsi, harga, stok, kategori, category_id, gambar, is_active, is_featured, is_demo)
SELECT 'Suflosha Bouquet', 'suflosha-bouquet',
       '[DEMO] Contoh katalog. Foto berasal dari arsip toko. Harga dan stok adalah data uji, bukan inventaris final. Komposisi dan ketersediaan bunga dikonfirmasi saat pemesanan.',
       90000.00, 12, 'Ulang Tahun', (SELECT id FROM categories WHERE slug = 'ulang-tahun'),
       'Suflosha.jpeg', 1, 1, 1
WHERE NOT EXISTS (SELECT 1 FROM produk WHERE slug = 'suflosha-bouquet');

INSERT INTO produk (nama, slug, deskripsi, harga, stok, kategori, category_id, gambar, is_active, is_featured, is_demo)
SELECT 'Valencia Bloom Box', 'valencia-bloom-box',
       '[DEMO] Contoh katalog. Foto berasal dari arsip toko. Harga dan stok adalah data uji, bukan inventaris final. Komposisi dan ketersediaan bunga dikonfirmasi saat pemesanan.',
       170000.00, 5, 'Wisuda', (SELECT id FROM categories WHERE slug = 'wisuda'),
       'Valencia.png', 1, 1, 1
WHERE NOT EXISTS (SELECT 1 FROM produk WHERE slug = 'valencia-bloom-box');
