-- Beneranjualan.id — clean MySQL/MariaDB schema
-- Character set: utf8mb4. Engine: InnoDB. Money: DECIMAL(12,2).
-- This is a NEW schema inferred from the application. It does not restore
-- historical customer/order data from the old bouquet_db fragments.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama_lengkap VARCHAR(120) NOT NULL,
    username VARCHAR(60) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'customer',
    telepon VARCHAR(30) NULL,
    alamat TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role (role),
    CONSTRAINT chk_users_role CHECK (role IN ('admin', 'customer'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL,
    deskripsi TEXT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_slug (slug),
    KEY idx_categories_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS produk (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama VARCHAR(160) NOT NULL,
    slug VARCHAR(190) NOT NULL,
    deskripsi TEXT NULL,
    harga DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    stok INT NOT NULL DEFAULT 0,
    kategori VARCHAR(80) NOT NULL DEFAULT 'Umum',
    category_id BIGINT UNSIGNED NULL,
    gambar VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    is_demo TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_produk_slug (slug),
    KEY idx_produk_active (is_active),
    KEY idx_produk_featured (is_featured),
    KEY idx_produk_category (category_id),
    KEY idx_produk_kategori_legacy (kategori),
    KEY idx_produk_stock (stok),
    CONSTRAINT fk_produk_category FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE SET NULL,
    CONSTRAINT chk_produk_harga CHECK (harga >= 0),
    CONSTRAINT chk_produk_stok CHECK (stok >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_images (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    produk_id BIGINT UNSIGNED NOT NULL,
    filename VARCHAR(255) NOT NULL,
    alt_text VARCHAR(190) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_product_images_product (produk_id, sort_order),
    CONSTRAINT fk_product_images_product FOREIGN KEY (produk_id) REFERENCES produk (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pesanan (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_number VARCHAR(40) NOT NULL,
    order_token CHAR(48) NOT NULL,
    checkout_token CHAR(64) NULL,
    user_id BIGINT UNSIGNED NULL,
    nama VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    telepon VARCHAR(30) NOT NULL,
    alamat TEXT NOT NULL,
    delivery_date DATE NULL,
    delivery_time VARCHAR(40) NULL,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    shipping_cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    discount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    metode_bayar VARCHAR(40) NOT NULL DEFAULT 'transfer',
    catatan TEXT NULL,
    admin_note TEXT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'baru',
    payment_status VARCHAR(30) NOT NULL DEFAULT 'menunggu_pembayaran',
    payment_confirmed_at DATETIME NULL,
    payment_confirmed_by BIGINT UNSIGNED NULL,
    tracking_number VARCHAR(100) NULL,
    stock_restored_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_pesanan_number (order_number),
    UNIQUE KEY uq_pesanan_token (order_token),
    UNIQUE KEY uq_pesanan_checkout_token (checkout_token),
    KEY idx_pesanan_user (user_id),
    KEY idx_pesanan_status (status),
    KEY idx_pesanan_payment (payment_status),
    KEY idx_pesanan_created (created_at),
    KEY idx_pesanan_email (email),
    CONSTRAINT fk_pesanan_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_pesanan_confirmer FOREIGN KEY (payment_confirmed_by) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT chk_pesanan_total CHECK (total >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS detail_pesanan (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    pesanan_id BIGINT UNSIGNED NOT NULL,
    produk_id BIGINT UNSIGNED NULL,
    nama_produk VARCHAR(160) NOT NULL,
    gambar_produk VARCHAR(255) NULL,
    qty INT NOT NULL,
    harga DECIMAL(12,2) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_detail_order (pesanan_id),
    KEY idx_detail_product (produk_id),
    CONSTRAINT fk_detail_order FOREIGN KEY (pesanan_id) REFERENCES pesanan (id) ON DELETE CASCADE,
    CONSTRAINT fk_detail_product FOREIGN KEY (produk_id) REFERENCES produk (id) ON DELETE SET NULL,
    CONSTRAINT chk_detail_qty CHECK (qty > 0),
    CONSTRAINT chk_detail_harga CHECK (harga >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_status_history (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    pesanan_id BIGINT UNSIGNED NOT NULL,
    field_name VARCHAR(30) NOT NULL,
    old_value VARCHAR(60) NULL,
    new_value VARCHAR(60) NOT NULL,
    note VARCHAR(255) NULL,
    changed_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_history_order (pesanan_id, created_at),
    CONSTRAINT fk_history_order FOREIGN KEY (pesanan_id) REFERENCES pesanan (id) ON DELETE CASCADE,
    CONSTRAINT fk_history_user FOREIGN KEY (changed_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock_movements (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    produk_id BIGINT UNSIGNED NOT NULL,
    qty_change INT NOT NULL,
    movement_type VARCHAR(30) NOT NULL,
    reference_type VARCHAR(30) NULL,
    reference_id BIGINT UNSIGNED NULL,
    note VARCHAR(255) NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_stock_product (produk_id, created_at),
    KEY idx_stock_type (movement_type),
    CONSTRAINT fk_stock_product FOREIGN KEY (produk_id) REFERENCES produk (id) ON DELETE RESTRICT,
    CONSTRAINT fk_stock_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS store_settings (
    setting_key VARCHAR(80) NOT NULL,
    setting_value TEXT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_audit_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(60) NOT NULL,
    entity_type VARCHAR(40) NOT NULL,
    entity_id BIGINT UNSIGNED NULL,
    details JSON NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_created (created_at),
    KEY idx_audit_entity (entity_type, entity_id),
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ip_address VARCHAR(45) NOT NULL,
    username VARCHAR(190) NOT NULL,
    success TINYINT(1) NOT NULL DEFAULT 0,
    attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_login_ip (ip_address, attempted_at),
    KEY idx_login_user (username, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS schema_migrations (
    migration VARCHAR(190) NOT NULL,
    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (migration)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Safe defaults: no invented phone numbers, addresses, bank accounts,
-- testimonials, sales figures, or delivery promises.
INSERT INTO store_settings (setting_key, setting_value) VALUES
    ('store_name', 'Beneranjualan.id'),
    ('tagline', 'Buket bunga untuk momen yang berarti.'),
    ('contact_whatsapp', ''),
    ('contact_email', ''),
    ('contact_phone', ''),
    ('address', ''),
    ('operating_hours', ''),
    ('instagram', ''),
    ('facebook', ''),
    ('tiktok', ''),
    ('shipping_flat', '0'),
    ('free_shipping_min', '0'),
    ('shipping_note', 'Ongkos kirim (bila ada) dikonfirmasi admin sebelum pengiriman.'),
    ('low_stock_threshold', '3'),
    ('promo_active', '0'),
    ('promo_banner', ''),
    ('bank_name', ''),
    ('bank_account_number', ''),
    ('bank_account_name', ''),
    ('payment_note', 'Pembayaran transfer manual diverifikasi oleh admin. Pesanan tidak dianggap lunas sebelum dikonfirmasi.'),
    ('pickup_enabled', '0'),
    ('cod_enabled', '0'),
    ('terms', ''),
    ('privacy', '')
ON DUPLICATE KEY UPDATE setting_key = VALUES(setting_key);
