</main>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-section">
                <h3><i class="fas fa-seedling"></i> <?= SITE_NAME ?></h3>
                <p>Toko buket bunga premium untuk setiap momen spesial dalam hidup Anda. Kualitas terbaik dengan harga terjangkau.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-whatsapp"></i></a>
                    <a href="#"><i class="fab fa-tiktok"></i></a>
                </div>
            </div>
            
            <div class="footer-section">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="<?= SITE_URL ?>">Home</a></li>
                    <li><a href="<?= SITE_URL ?>#produk">Produk</a></li>
                    <li><a href="<?= SITE_URL ?>#tentang">Tentang Kami</a></li>
                    <li><a href="<?= SITE_URL ?>#kontak">Kontak</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4>Kategori</h4>
                <ul>
                    <li><a href="#">Bouquet Premium</a></li>
                    <li><a href="#">Bouquet Classic</a></li>
                    <li><a href="#">Bouquet Budget</a></li>
                    <li><a href="#">Custom Order</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4>Kontak Kami</h4>
                <ul class="contact-info">
                    <li><i class="fas fa-map-marker-alt"></i> Jl. Bunga Indah No. 123, Jakarta</li>
                    <li><i class="fas fa-phone"></i> +62 812-3456-7890</li>
                    <li><i class="fas fa-envelope"></i> hello@beneranjualan.id</li>
                    <li><i class="fas fa-clock"></i> Senin - Minggu: 08:00 - 21:00</li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. All Rights Reserved.</p>
            <div class="payment-methods">
                <img src="<?= SITE_URL ?>/assets/images/bca.png" alt="BCA">
                <img src="<?= SITE_URL ?>/assets/images/mandiri.png" alt="Mandiri">
                <img src="<?= SITE_URL ?>/assets/images/gopay.png" alt="GoPay">
                <img src="<?= SITE_URL ?>/assets/images/ovo.png" alt="OVO">
            </div>
        </div>
    </div>
</footer>

<!-- Back to Top -->
<button class="back-to-top" id="backToTop">
    <i class="fas fa-chevron-up"></i>
</button>

<!-- WhatsApp Float -->
<a href="[wa.me](https://wa.me/6281234567890)" class="whatsapp-float" target="_blank">
    <i class="fab fa-whatsapp"></i>
</a>

<!-- Scripts -->
<script src="<?= SITE_URL ?>/assets/main.js"></script>
</body>
</html>
