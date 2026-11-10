# Asset manifest — foto produk

Inventaris statis file proyek (backup aplikasi lama dikecualikan). Foto pada folder katalog adalah milik toko berdasarkan penamaan folder, tetapi nama produk, SKU, harga, stok, status aktif, dan relasi file-ke-produk **belum terverifikasi**. Karena itu foto-foto ini belum otomatis dipublikasikan sebagai SKU atau diberi harga.

## Folder katalog

| Folder sumber | File foto | Catatan |
|---|---:|---|
| `catalog-source/[1] Petite Katalog/` | 13 | JPG/PNG/HEIC; perlu mapping produk oleh owner |
| `catalog-source/[2] Signature Katalog/` | 16 | Termasuk beberapa produk dengan foto bernomor 1/2 |
| `catalog-source/[3] Grand Katalog/` | 8 | Termasuk beberapa foto bernomor 1/2 |
| `catalog-source/[4] Basket Katalog/` | 3 | Perlu konfirmasi foto utama |
| `catalog-source/[5] Sash Flower & Trophy Katalog/` | 3 | Jenis produk harus dikonfirmasi masuk katalog storefront |
| `catalog-source/[6] Chocolate & Snack Katalog/` | 10 | Sebagian format HEIC |
| `catalog-source/[7] Photo Katalog/` | 3 | Sebagian format HEIC |
| `catalog-source/[8] Souvenir Katalog/` | 14 | Sebagian format HEIC dan foto bernomor |
| `catalog-source/[9] Keychain Katalog/` | 7 | Bukan buket; konfirmasi apakah dijual sebagai add-on/produk |
| `catalog-source/[10] Money Bouquet Katalog/` | 1 | Perlu data produk dan harga aktual |
| `catalog-source/[11] Valentine_s Day Katalog/` | 10 | Perlu cek apakah koleksi musiman masih aktif |
| `catalog-source/[12] Teacher_s Day Katalog/` | 3 | Perlu cek apakah koleksi musiman masih aktif |
| `catalog-source/[13] Mother_s Day Katalog/` | 2 | Perlu cek apakah koleksi musiman masih aktif |
| `catalog-source/[14] Hampers Katalog/` | 15 | Termasuk PNG/JPG; perlu mapping produk |

Total **108 file foto di 14 folder katalog** di `catalog-source/`. Aplikasi menyertakan 9 foto raster di `bouquet/assets/images/` (6 aset lama dan 3 foto cerita dari katalog) serta 18 derivative WebP responsif (480/960 px); placeholder/favicon SVG tidak dihitung sebagai foto. Satu foto Suflosha lama yang unik dipindahkan dari folder legacy ke `catalog-source/legacy-app-photos/`. Salinan Valencia yang identik byte dibersihkan; foto aktif tetap berada di `bouquet/assets/images/Valencia.png`. Derivative WebP bukan SKU baru.

## Pemetaan storefront yang terpasang

| File aplikasi | Penggunaan | Status data |
|---|---|---|
| `bouquet/assets/images/optimized/{Valencia,Suflosha,Sisha,Raisha,Pirousha,Crona}-{480,960}.webp` | Ukuran responsif; dipakai bila filename produk cocok | Foto turunan; hubungan ke produk demo perlu divalidasi |
| `bouquet/assets/images/{Valencia.png,Suflosha.jpeg,Sisha.jpeg,Raisha.jpeg,Pirousha.jpeg,Crona.jpeg}` | Fallback/original untuk katalog | Foto nyata yang tersedia di aplikasi; katalog demo jangan dijadikan penawaran nyata tanpa validasi |
| `catalog-source/[3] Grand Katalog/Suflosha.jpg` → `bouquet/assets/images/Suflosha.jpeg` | Foto hero saat belum ada produk jual terverifikasi | Foto produk handmade yang sama secara visual; ditampilkan tanpa nama SKU atau harga contoh |
| `catalog-source/legacy-app-photos/Suflosha.jpeg` | Foto arsip dari aplikasi legacy | Foto asli unik yang dipertahankan; tidak dihapus dan belum dipakai pada halaman aktif |
| `catalog-source/[1] Petite Katalog/Clawsha.jpg` → `bouquet/assets/images/story-petite-clawsha.jpg` | Bab cerita 01: pilihan karya | Foto asli; tidak dipakai untuk membuat SKU/harga |
| `catalog-source/[2] Signature Katalog/Nasha.jpg` → `bouquet/assets/images/story-signature-nasha.jpg` | Bab cerita 02: catatan personal | Foto asli; tidak dipakai untuk membuat SKU/harga |
| `catalog-source/[3] Grand Katalog/Suflosha.jpg` → `bouquet/assets/images/story-grand-suflosha.jpg` | Bab cerita 03: konfirmasi toko | Foto asli; tidak dipakai untuk membuat SKU/harga |
| `story-{petite-clawsha,signature-nasha,grand-suflosha}-{480,960}.webp` | Transisi scroll desktop dan gambar chapter mobile | Derivative WebP, sumber JPG asli tetap disimpan |
| `bouquet/assets/images/placeholder.svg` | Fallback tanpa foto produk | Placeholder netral, bukan foto produk |

Tidak ada product ID yang ditetapkan dalam manifest ini karena ID database berubah antar-instalasi dan belum ada daftar SKU owner yang tervalidasi. Admin dapat mengunggah/mengaitkan foto saat membuat atau mengedit produk. Jangan memakai enam seed demo sebagai katalog live.

## Yang dibutuhkan untuk menyelesaikan mapping

Untuk setiap produk: nama jual, kategori, harga, stok awal, deskripsi/ukuran bila diketahui, filename foto utama, foto tambahan, dan apakah aktif dijual saat ini. Format/HEIC perlu dikonversi non-destruktif sebelum digunakan di browser; file asli tetap dipertahankan.

## Daftar file satu per satu

Seluruh file berikut terinventarisasi. Kolom mapping menandai **belum dipetakan** karena katalog sumber hanya berisi foto tanpa data produk terverifikasi. Foto bernomor/serupa harus dikonfirmasi sebagai sudut foto produk yang sama atau SKU berbeda.

| File sumber | Produk / ID DB | Penggunaan / status duplikat |
|---|---|---|
| `catalog-source/[1] Petite Katalog/Casha.heic` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[1] Petite Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[1] Petite Katalog/Clawsha.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[1] Petite Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[1] Petite Katalog/Dacha.JPG` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[1] Petite Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[1] Petite Katalog/Edsha.JPG` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[1] Petite Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[1] Petite Katalog/Lebra.heic` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[1] Petite Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[1] Petite Katalog/Mira.heic` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[1] Petite Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[1] Petite Katalog/Mitsha.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[1] Petite Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[1] Petite Katalog/Rosbouq.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[1] Petite Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[1] Petite Katalog/Rousha.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[1] Petite Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[1] Petite Katalog/Seroe.JPG` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[1] Petite Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[1] Petite Katalog/Siera.heic` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[1] Petite Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[1] Petite Katalog/Sinra.heic` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[1] Petite Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[1] Petite Katalog/Sunsha.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[1] Petite Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[2] Signature Katalog/Claxflo.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[2] Signature Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[2] Signature Katalog/Crona.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[2] Signature Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[2] Signature Katalog/Crsha.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[2] Signature Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[2] Signature Katalog/Dnsha.PNG` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[2] Signature Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[2] Signature Katalog/Flincha.heic` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[2] Signature Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[2] Signature Katalog/Flyshia1.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[2] Signature Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[2] Signature Katalog/Flyshia2.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[2] Signature Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[2] Signature Katalog/Liera.JPG` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[2] Signature Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[2] Signature Katalog/Lisha.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[2] Signature Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[2] Signature Katalog/Nasha.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[2] Signature Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[2] Signature Katalog/Pirousha.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[2] Signature Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[2] Signature Katalog/Rowsha.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[2] Signature Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[2] Signature Katalog/Sisha.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[2] Signature Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[2] Signature Katalog/Sunny.heic` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[2] Signature Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[2] Signature Katalog/Viesha1.heic` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[2] Signature Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[2] Signature Katalog/Viesha2.heic` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[2] Signature Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[3] Grand Katalog/Belisha.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[3] Grand Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[3] Grand Katalog/Bluesha1.heic` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[3] Grand Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[3] Grand Katalog/Bluesha2.heic` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[3] Grand Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[3] Grand Katalog/Bookuet.heic` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[3] Grand Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[3] Grand Katalog/Brysha.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[3] Grand Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[3] Grand Katalog/Jisha.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[3] Grand Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[3] Grand Katalog/Suflosha.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[3] Grand Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[3] Grand Katalog/Talisha.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[3] Grand Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[4] Basket Katalog/Baysha1.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[4] Basket Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[4] Basket Katalog/Baysha2.HEIC` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[4] Basket Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[4] Basket Katalog/Pipo.JPG` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[4] Basket Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[5] Sash Flower & Trophy Katalog/Salicha.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[5] Sash Flower & Trophy Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[5] Sash Flower & Trophy Katalog/Sasha.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[5] Sash Flower & Trophy Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[5] Sash Flower & Trophy Katalog/Trovie.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[5] Sash Flower & Trophy Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[6] Chocolate & Snack Katalog/Darbo.heic` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[6] Chocolate & Snack Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[6] Chocolate & Snack Katalog/Daybho.heic` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[6] Chocolate & Snack Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[6] Chocolate & Snack Katalog/Dynsha.heic` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[6] Chocolate & Snack Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[6] Chocolate & Snack Katalog/Linsha.heic` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[6] Chocolate & Snack Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[6] Chocolate & Snack Katalog/Raisha.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[6] Chocolate & Snack Katalog`; Identik byte dengan: `Raisha.jpg` |
| `catalog-source/[6] Chocolate & Snack Katalog/Snabo.heic` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[6] Chocolate & Snack Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[6] Chocolate & Snack Katalog/Spysha.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[6] Chocolate & Snack Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[6] Chocolate & Snack Katalog/Valana.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[6] Chocolate & Snack Katalog`; Identik byte dengan: `Valana.jpg` |
| `catalog-source/[6] Chocolate & Snack Katalog/Valencia.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[6] Chocolate & Snack Katalog`; Identik byte dengan: `Valencia.jpg` |
| `catalog-source/[6] Chocolate & Snack Katalog/Vana.JPG` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[6] Chocolate & Snack Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[7] Photo Katalog/Phipo.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[7] Photo Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[7] Photo Katalog/Phodi.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[7] Photo Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[7] Photo Katalog/Phosha.heic` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[7] Photo Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[8] Souvenir Katalog/Antrosha1.heic` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[8] Souvenir Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[8] Souvenir Katalog/Antrosha2.heic` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[8] Souvenir Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[8] Souvenir Katalog/Candya.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[8] Souvenir Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[8] Souvenir Katalog/Ceera1.heic` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[8] Souvenir Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[8] Souvenir Katalog/Ceera2.heic` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[8] Souvenir Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[8] Souvenir Katalog/Ceesha1.heic` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[8] Souvenir Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[8] Souvenir Katalog/Ceesha2.heic` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[8] Souvenir Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[8] Souvenir Katalog/Handbouq.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[8] Souvenir Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[8] Souvenir Katalog/IMG_9443.JPG` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[8] Souvenir Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[8] Souvenir Katalog/Jesha Lumea.JPG` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[8] Souvenir Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[8] Souvenir Katalog/Orsha.heic` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[8] Souvenir Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[8] Souvenir Katalog/Phora.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[8] Souvenir Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[8] Souvenir Katalog/Rysha.heic` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[8] Souvenir Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[8] Souvenir Katalog/Siera.heic` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[8] Souvenir Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[9] Keychain Katalog/Flowkie.heic` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[9] Keychain Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[9] Keychain Katalog/Keipie.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[9] Keychain Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[9] Keychain Katalog/Kiesha.heic` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[9] Keychain Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[9] Keychain Katalog/Misha1.PNG` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[9] Keychain Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[9] Keychain Katalog/Misha2.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[9] Keychain Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[9] Keychain Katalog/Narisha.heic` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[9] Keychain Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[9] Keychain Katalog/Rochie.heic` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[9] Keychain Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[10] Money Bouquet Katalog/Monic.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[10] Money Bouquet Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[11] Valentine_s Day Katalog/Raisha.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[11] Valentine_s Day Katalog`; Identik byte dengan: `Raisha.jpg` |
| `catalog-source/[11] Valentine_s Day Katalog/Vaca.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[11] Valentine_s Day Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[11] Valentine_s Day Katalog/Valana.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[11] Valentine_s Day Katalog`; Identik byte dengan: `Valana.jpg` |
| `catalog-source/[11] Valentine_s Day Katalog/Vale.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[11] Valentine_s Day Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[11] Valentine_s Day Katalog/Valencia.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[11] Valentine_s Day Katalog`; Identik byte dengan: `Valencia.jpg` |
| `catalog-source/[11] Valentine_s Day Katalog/Vali.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[11] Valentine_s Day Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[11] Valentine_s Day Katalog/Vana.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[11] Valentine_s Day Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[11] Valentine_s Day Katalog/Vapiy.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[11] Valentine_s Day Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[11] Valentine_s Day Katalog/Vasha.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[11] Valentine_s Day Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[11] Valentine_s Day Katalog/Vini.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[11] Valentine_s Day Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[12] Teacher_s Day Katalog/Teary1.heic` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[12] Teacher_s Day Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[12] Teacher_s Day Katalog/Teary2.heic` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[12] Teacher_s Day Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[12] Teacher_s Day Katalog/Teary3.heic` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[12] Teacher_s Day Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[13] Mother_s Day Katalog/Mowra1.heic` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[13] Mother_s Day Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[13] Mother_s Day Katalog/Mowra2.heic` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[13] Mother_s Day Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[14] Hampers Katalog/Christmas14.PNG` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[14] Hampers Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[14] Hampers Katalog/Silent1.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[14] Hampers Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[14] Hampers Katalog/Silent10.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[14] Hampers Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[14] Hampers Katalog/Silent11.JPG` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[14] Hampers Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[14] Hampers Katalog/Silent12.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[14] Hampers Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[14] Hampers Katalog/Silent13.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[14] Hampers Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[14] Hampers Katalog/Silent15.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[14] Hampers Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[14] Hampers Katalog/Silent2.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[14] Hampers Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[14] Hampers Katalog/Silent3.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[14] Hampers Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[14] Hampers Katalog/Silent4.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[14] Hampers Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[14] Hampers Katalog/Silent5.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[14] Hampers Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[14] Hampers Katalog/Silent6.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[14] Hampers Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[14] Hampers Katalog/Silent7.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[14] Hampers Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[14] Hampers Katalog/Silent8.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[14] Hampers Katalog`; Tidak ada duplikat byte dalam folder katalog |
| `catalog-source/[14] Hampers Katalog/Silent9.jpg` | Belum dipetakan / tidak ada ID | Sumber katalog `catalog-source/[14] Hampers Katalog`; Tidak ada duplikat byte dalam folder katalog |

Total baris file katalog: **108**. Pemeriksaan duplikat dilakukan dengan SHA-256 (duplikat visual yang di-resize/diubah metadata mungkin tidak terdeteksi).
