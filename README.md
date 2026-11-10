# Beneranjualan.id — PHP/MySQL bouquet store

Server-rendered PHP storefront and admin for MySQL/MariaDB. The app uses PDO, vanilla JavaScript/CSS and a local Three.js module for the progressive 3D homepage; the commerce backend remains PHP.

## Struktur proyek

| Path | Isi |
|---|---|
| `bouquet/` | Aplikasi website aktif; jadikan document root dan gunakan `index.php` sebagai beranda. |
| `bouquet/assets/vendor/` | Three.js lokal (0.187.0, MIT) untuk storytelling 3D; tidak bergantung CDN/build. |
| `catalog-source/` | Foto asli sumber, dikelompokkan per koleksi; belum otomatis menjadi produk jual. Lihat `asset-manifest.md`. |
| `references/` | Video target, rekaman website saat ini, dan keterangan perannya. |
| `database/` | Skema, migrasi, template seed demo, dan petunjuk backup. |
| `scripts/`, `tests/` | Setup/admin bootstrap, optimasi gambar, dan regression checks. |
| `archive/legacy-database-fragments/` | Fragmen database lama; bukan dump yang bisa dipulihkan. |
| `backups/` | Cadangan lama; pertahankan sebagai arsip, jangan dipakai sebagai document root. |
| `visual-evidence/`, `VISUAL_ACCEPTANCE.md` | Screenshot QA dan status kesesuaian mekanik visual V2. |

Dokumen desain, prompt, audit, dan laporan test tetap di root agar mudah ditemukan.

## Local setup (Windows / Laragon)

1. Install PHP 8.1+ with `pdo_mysql`, `mbstring`, and `fileinfo`, plus MySQL 8 / MariaDB.
2. Copy `.env.example` to `.env`, set the database values and set `APP_URL` to the local URL. Keep `.env` private.
3. Create the database and a least-privilege application user, then run:

   ```powershell
   php scripts/setup_db.php
   php scripts/setup_db.php --seed-demo  # optional; inserts clearly marked sample products
   ```

   The installer can create the configured database if the configured DB user is permitted to do so. See `database/README.md` for a root-assisted local setup and backup instructions.
4. Create the first administrator interactively with `php scripts/create_admin.php`. The password is not stored in source. For automation, supply a protected `ADMIN_PASSWORD` environment variable and explicit `--username`, `--email`, and `--name` options.
5. For local development, run `php -S 127.0.0.1:8000 -t bouquet` and set `APP_URL=http://127.0.0.1:8000`.

Fill in real bank, contact, address, shipping, and policy details under Admin → Pengaturan. Demo products are test data; replace/archive them before accepting real orders. The default payment method is manual transfer; payment is not marked paid until an admin verifies it.

## Deployment

Deploy the `bouquet/` directory as the web document root, serve over HTTPS, configure `APP_URL`, database credentials, `SESSION_COOKIE_SECURE=1`, and production `APP_DEBUG=0`. Keep `scripts/`, `database/`, backups, `.env`, and logs outside the public document root where the host allows it. Restrict database permissions, configure scheduled/offsite backups, and verify file permissions and upload limits. Do not seed demo products on production. The PHP built-in server is for local development only.

Apache deployments use `bouquet/.htaccess` to serve the generated `robots.txt` from `robots.php`; on Nginx or another server, configure `/robots.txt` to execute that PHP endpoint. `sitemap.php` lists active non-demo products and their non-empty categories, and uses the configured `APP_URL`.

## Operations

- Create an admin: `php scripts/create_admin.php` (CLI only).
- Apply schema migrations: `php scripts/setup_db.php` (CLI only; safe to rerun).
- Manual transfer, order lifecycle, stock movements and administrative changes are recorded in the database.
- Review `AUDIT_REPORT.md`, `TEST_REPORT.md`, and `REMAINING_RISKS.md` before launch.
- Run the read-only regression checks with `php tests/run.php` after database setup.
- Existing catalog photographs have checked-in responsive WebP derivatives. To regenerate them after changing source photos, install Pillow and run `python scripts/optimize_images.py`; originals are preserved and the PHP storefront falls back to them if derivatives are absent.

## Security and limitations

All cart and admin mutations use POST and CSRF checks. Checkout derives prices from MySQL and uses a transaction with row locks and conditional stock updates. Password reset is intentionally informational until a real mail delivery/token workflow is configured. No payment gateway, automated shipping quote, or production business details are configured by default.
