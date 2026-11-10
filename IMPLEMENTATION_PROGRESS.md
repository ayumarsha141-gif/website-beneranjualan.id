# Implementation progress

## Audit dan perlindungan

- Aplikasi canonical yang diperiksa: `bouquet/` (PHP server-rendered, PDO/MySQL, vanilla JS/CSS); bukan proyek baru.
- Membaca penuh `desain.md` dan `PROMPT_OPENCODE.md`.
- Repo berada di branch `main` dengan banyak perubahan lokal/untracked yang sudah ada saat audit. Tidak ada perubahan tersebut yang di-reset.
- Target `references/video-referensi.mp4` dan current capture `references/current-recording-2026-11-07-235934.mp4` ditinjau. Target: 79,1 detik, 1024×576, 30 fps; current capture: 6,83 detik, 2454×1450, 30 fps. Nama current-file `20261107-1544-04.5897627.mp4` yang tertulis di brief tidak ditemukan; capture yang tersedia dipertahankan. Target menunjukkan hero isometrik, satu rute oranye dan kamera antar-scene yang dikendalikan scroll. Implementasi sebelumnya masih terlalu flat; lihat status prototipe V2 di bawah, jangan klaim parity sebelum kamera 3D lulus QA.
- Fragmen database lama dipindahkan utuh ke `archive/legacy-database-fragments/`; isinya tetap bukan dump SQL dan tidak dipulihkan.
- Inventaris foto ditulis di `asset-manifest.md`; relasi foto katalog massal ke SKU belum terverifikasi.
- Cleanup route: menghapus `bouquet/index.html`, `bouquet/page2.html`, dan `bouquet/style.css` setelah memastikan hanya dipakai oleh halaman statis legacy; menghapus salinan byte-identik `bouquet/images/Valencia.png`, menetapkan `DirectoryIndex index.php`, dan mempertahankan foto Suflosha unik serta semua folder foto/backup/database.
- Struktur proyek dirapikan: 14 folder katalog ke `catalog-source/`, video ke `references/`, fragmen DB ke `archive/legacy-database-fragments/`, dan foto Suflosha unik ke `catalog-source/legacy-app-photos/`.

## Status fase

- [x] Aplikasi yang ada dan alur utama ditinjau: katalog/detail, keranjang, checkout, login, halaman order, admin.
- [x] Skema DB baru versioned tersedia di `database/migrations/001_initial.sql`, dengan ledger migrasi, tabel order/item, history, stok, pengaturan, dan audit.
- [x] Setup CLI dan bootstrap admin tersedia (`scripts/setup_db.php`, `scripts/create_admin.php`); seed demo eksplisit.
- [x] MySQL tersambung. `php scripts/setup_db.php` memverifikasi tabel; migrasi `001_initial.sql` telah tercatat/applied sebelumnya dan di-skip aman, bukan dijalankan ulang.
- [x] Enam produk `is_demo=1` diarsipkan dengan `is_active=0` (tidak dihapus; tidak ada order historis). Query katalog/detail/beranda/dashboard dan fungsi cart/checkout juga menolak produk demo.
- [x] Checkout source implements harga dari DB, transaksi/row lock, conditional stock decrement, snapshot harga, CSRF, idempotency token, dan status pembayaran menunggu verifikasi.
- [x] Checkout storefront diperbarui menjadi 3 langkah progresif (kontak → pengantaran → pesan/pembayaran), dengan validasi browser per langkah, tombol kembali/lanjut, progress aksesibel, dan fallback tanpa JavaScript.
- [x] Ringkasan checkout dapat diciutkan di mobile, menampilkan total ketika tertutup, dan otomatis terbuka di tahap konfirmasi.
- [x] V2 mengganti hero foto 2D sebagai first viewport dengan canvas Three.js lokal dan pinned scroll story 6 scene; customer nav tetap floating pill dan `Langsung belanja` menaut ke katalog.
- [x] Dashboard admin dan komponen operasional diselaraskan ke canvas ivory, forest/sage, aksen blush hemat, dan font storefront; overflow tablet diperbaiki tanpa mengubah query atau aksi admin.
- [x] Kamera orthographic, enam set miniatur prosedural 3D, dan satu TubeGeometry ribbon pada satu world; progress scroll menginterpolasi camera position/look-at. Scene label/nav active tersinkron dan CTA tetap route PHP nyata.
- [x] Layout mobile mempertahankan WebGL story dengan DPR/kompleksitas lebih rendah; reduced-motion/WebGL fallback menampilkan story statis, foto owner, dan CTA katalog.
- [x] Token warna storefront diselaraskan ke canvas ivory, forest, sage, dan blush yang hemat; foto produk dan identitas tekstual toko tetap menjadi fokus.
- [x] Foto `[3] Grand Katalog/Suflosha.jpg` dan aset asli Petite/Clawsha serta Signature/Nasha tersedia pada sequence/fallback; tidak dipakai sebagai SKU atau diberi harga fiktif.
- [x] Halaman operasional admin yang ada mencakup produk, kategori, pesanan, inventaris, pelanggan, laporan, dan pengaturan.
- [x] Regression test dan checkout guest E2E berjalan setelah MySQL aktif.
- [x] Home dan katalog dirender via PHP built-in server dan diperiksa dengan browser pada desktop/mobile; produk demo lama menghasilkan 404 di detail publik.
- [x] Pinned story diuji di Edge/WebGL SwiftShader pada 0/20/40/60/80/100%; camera position berubah di semua titik (enam view berbeda), enam scene/ribbon terlihat, 0 page errors. Screenshot bukti ada di `visual-evidence/v2-six-scenes/`.
- [x] Reduced-motion dan `--disable-webgl` fallback diuji; story statis dan CTA katalog tampil.
- [x] Admin login/dashboard dirender pada browser desktop/mobile menggunakan akun QA sementara; menu seluler dan tidak adanya horizontal overflow diperiksa. Akun QA dan login log dibersihkan.
- [ ] Fresh install ke database kosong belum dijalankan; DB proyek yang ada sudah berisi schema/migrasi dan 2 akun owner/admin. Jangan overwrite DB ini. Setelah pengarsipan: 0 produk aktif (0 demo/0 real), 0 order.
- [ ] Live launch: tunggu produk/harga/stok tervalidasi dan konfigurasi owner/hosting.

## Pengujian yang benar-benar dijalankan pada sesi ini

- `php -l` untuk 47 file PHP (folder backup dikecualikan): **lulus**.
- `node --check bouquet/assets/main.js` dan `node --check bouquet/assets/admin.js`: **lulus**.
- `node --check bouquet/assets/florist-world.js`: **lulus**. Three.js 0.187.0 module/core served locally, no external CDN.
- Video target dan current capture: contact sheets ditinjau; target `video-referensi.mp4` berdurasi 79.1s, current capture tersedia sebagai `current-recording-2026-11-07-235934.mp4` 6.83s. Filename `20261107-1544-04.5897627.mp4` yang tertulis dalam brief tidak ditemukan.
- Checkout front-end: smoke test interaksi Chromium headless memakai fixture terisolasi lulus **9 assertion** pada mobile 390×844 dan **6 assertion** pada desktop 1440×1000 (tahap awal, submit terkunci, perpindahan tahap, nilai tersimpan saat kembali, ringkasan mobile collapse/expand). Screenshot fixture juga diperiksa pada kedua ukuran; ini **bukan** screenshot halaman PHP produksi.
- `git diff --check`: **lulus**; hanya peringatan normal konversi LF/CRLF pada working tree Windows.
- `php tests/run.php`: **lulus 31 pemeriksaan** setelah MySQL aktif.
- `php scripts/setup_db.php`: koneksi dan tabel terverifikasi; `001_initial.sql` **SKIP** karena telah diterapkan, 0 migrasi baru.
- Checkout guest E2E dengan produk uji lokal sementara: **5 pemeriksaan lulus** (CSRF add-to-cart, receipt checkout, harga server/stok/status pembayaran, retry idempotent, produk demo ditolak). Produk/order/movement uji dibersihkan setelah tes.
- Route browser: `/` dan `/katalog.php` HTTP 200; halaman detail produk demo dan ID acak HTTP 404 seperti yang diharapkan.
- Screenshot halaman PHP nyata dibuat dan diperiksa di desktop (1440px) dan mobile (390px). Katalog benar menampilkan empty state tanpa produk jual terverifikasi; harga/klaim demo tidak muncul.
- Camera probe desktop 1440×900: X/Z at 0/20/40/60/80/100% = `(11,16)`, `(2,3)`, `(37,15)`, `(27,2)`, `(64,15)`, `(55,3)`; mobile 390×844 also produced six distinct positions. Draw-count/triangle samples documented in `VISUAL_ACCEPTANCE.md`; FPS not claimed.
- `php -l` 47 file dan `node --check` storefront/admin JS: lulus.

`TEST_REPORT.md` menyimpan laporan historis dan hasil verifikasi ulang pada 2026-11-07 serta visual V2 pada 2026-11-08.

## Gate pemilik sebelum menerima order sungguhan

1. Nyalakan/konfigurasikan MySQL dan isi `.env` private; jalankan `php scripts/setup_db.php`, lalu buat admin dengan `php scripts/create_admin.php`.
2. Berikan mapping katalog tervalidasi (nama/foto/harga/stok/kategori/status) dan arsip/nonaktifkan seed demo.
3. Isi WhatsApp/kontak, rekening/metode bayar, area dan ongkir, jam operasional, alamat, serta tinjau kebijakan toko.
4. Konfigurasikan HTTPS, secret produksi, email reset password (fitur reset email belum aktif), backup offsite, dan document root yang aman.
5. Isi produk sungguhan melalui Admin → Produk (unggah foto dari folder katalog, lalu masukkan nama jual, harga, stok, kategori, dan status aktif yang benar). Alur unggah foto tersedia; file foto belum dibulk-import karena beberapa nama/HEIC/foto perlu dipilih dan dipetakan per SKU.
