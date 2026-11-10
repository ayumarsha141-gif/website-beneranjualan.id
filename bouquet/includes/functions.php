<?php
/**
 * Shared helpers. All HTML output must be escaped with e().
 */
declare(strict_types=1);

// ---- Output / input -------------------------------------------------------

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Legacy alias: trims input only. Never use for HTML output. */
function sanitize(mixed $data): string
{
    return trim((string) $data);
}

function clean_text(mixed $value, int $max = 5000): string
{
    $value = trim((string) $value);
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
    return mb_substr($value, 0, $max);
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function wants_json(): bool
{
    if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') {
        return true;
    }
    if (($_GET['ajax'] ?? '') === '1') {
        return true;
    }
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    return str_contains($accept, 'application/json');
}

// ---- URLs / output helpers ----------------------------------------------

function site_url(string $path = ''): string
{
    $path = ltrim($path, '/');
    return SITE_URL . ($path === '' ? '' : '/' . $path);
}

function asset_url(string $path): string
{
    return site_url('assets/' . ltrim($path, '/'));
}

function product_image_url(?string $filename): string
{
    $filename = trim((string) $filename);
    if ($filename !== '' && basename($filename) === $filename) {
        $upload = APP_ROOT . '/assets/uploads/' . $filename;
        if (is_file($upload)) {
            return site_url('assets/uploads/' . rawurlencode($filename));
        }
        $file = APP_ROOT . '/assets/images/' . $filename;
        if (is_file($file)) {
            return asset_url('images/' . rawurlencode($filename)) . '?v=' . (filemtime($file) ?: 0);
        }
    }
    return asset_url('images/placeholder.svg');
}

/** Return responsive WebP candidates when generated variants are present. */
function product_image_srcset(?string $filename): string
{
    $filename = trim((string) $filename);
    if ($filename === '' || basename($filename) !== $filename || !is_file(APP_ROOT . '/assets/images/' . $filename)) {
        return '';
    }
    $stem = pathinfo($filename, PATHINFO_FILENAME);
    $candidates = [];
    foreach ([480, 960] as $width) {
        $variant = APP_ROOT . '/assets/images/optimized/' . $stem . '-' . $width . '.webp';
        if (is_file($variant)) {
            $candidates[] = asset_url('images/optimized/' . rawurlencode(basename($variant))) . '?v=' . (filemtime($variant) ?: 0) . ' ' . $width . 'w';
        }
    }
    return implode(', ', $candidates);
}

function formatRupiah(mixed $angka): string
{
    return CURRENCY . ' ' . number_format((float) $angka, 0, ',', '.');
}

function product_price_label(array $product): string
{
    $mode = (string) ($product['pricing_mode'] ?? 'fixed');
    if ($mode === 'quote') {
        return 'Tanya harga';
    }
    $amount = formatRupiah($product['harga'] ?? 0);
    return $mode === 'from' ? 'Mulai dari ' . $amount : $amount;
}

function product_requires_whatsapp(array $product): bool
{
    return (string) ($product['availability_mode'] ?? 'stock') === 'request'
        || !in_array((string) ($product['pricing_mode'] ?? 'fixed'), ['fixed'], true);
}

function product_inquiry_url(PDO $pdo, array $product): string
{
    $name = trim((string) ($product['nama'] ?? 'produk pilihan'));
    $label = product_price_label($product);
    $note = trim((string) ($product['pricing_note'] ?? ''));
    $message = 'Halo Beneranjualan.id, saya tertarik dengan ' . $name . '. Harga katalog: ' . $label . '.';
    if ($note !== '') {
        $message .= ' Catatan: ' . $note . '.';
    }
    $message .= ' Mohon konfirmasi ketersediaan dan harga akhir sesuai kebutuhan saya. Detail request (warna/ukuran/isi/budget):';
    return whatsapp_url($pdo, $message);
}

function format_date(?string $date, string $format = 'd M Y'): string
{
    if (!$date) {
        return '-';
    }
    $ts = strtotime($date);
    return $ts ? date($format, $ts) : '-';
}

function excerpt(string $text, int $length = 120): string
{
    $text = trim(strip_tags($text));
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length - 1) . '…';
}

function slugify(string $text): string
{
    $text = mb_strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-') ?: 'kategori';
}

// ---- Flash / redirect -----------------------------------------------------

function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function showFlash(): void
{
    if (empty($_SESSION['flash'])) {
        return;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $type = in_array($flash['type'] ?? '', ['success', 'error', 'info'], true) ? $flash['type'] : 'info';
    echo '<div class="alert alert-' . e($type) . '" role="status">' . e($flash['message']) . '</div>';
}

function redirect(string $path = '', string $message = '', string $type = 'success'): never
{
    if ($message !== '') {
        flash($message, $type);
    }
    $url = preg_match('#^https?://#i', $path) ? $path : site_url($path);
    header('Location: ' . $url);
    exit;
}

// ---- CSRF -----------------------------------------------------------------

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function validateCsrf(?string $token): bool
{
    return is_string($token) && $token !== '' && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function requireCsrf(): void
{
    if (!validateCsrf($_POST['csrf_token'] ?? null)) {
        if (wants_json()) {
            json_response(['success' => false, 'message' => 'Sesi kedaluwarsa. Muat ulang halaman.'], 419);
        }
        redirect('index.php', 'Sesi keamanan tidak valid. Silakan coba lagi.', 'error');
    }
}

// ---- Auth -----------------------------------------------------------------

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function current_user(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id' => (int) $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
        'nama_lengkap' => $_SESSION['nama_lengkap'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'role' => $_SESSION['role'] ?? 'customer',
    ];
}

function requireLogin(string $redirectPath = ''): void
{
    if (!isLoggedIn()) {
        $_SESSION['intended'] = $redirectPath !== '' ? $redirectPath : ($_SERVER['REQUEST_URI'] ?? '');
        redirect('login.php', 'Silakan masuk terlebih dahulu.', 'error');
    }
}

function requireAdmin(): void
{
    if (!isAdmin()) {
        if (wants_json()) {
            json_response(['success' => false, 'message' => 'Akses ditolak'], 403);
        }
        redirect('login.php', 'Akses ditolak. Masuk sebagai administrator.', 'error');
    }
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
    $_SESSION['role'] = $user['role'];
    unset($_SESSION['csrf_token']);
    csrf_token();
}

// ---- Settings -------------------------------------------------------------

function settings_all(PDO $pdo): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    try {
        $rows = $pdo->query('SELECT setting_key, setting_value FROM store_settings')->fetchAll();
        $cache = [];
        foreach ($rows as $row) {
            $cache[$row['setting_key']] = $row['setting_value'];
        }
    } catch (Throwable $e) {
        $cache = [];
    }
    return $cache;
}

function setting(PDO $pdo, string $key, string $default = ''): string
{
    $all = settings_all($pdo);
    $value = $all[$key] ?? null;
    if ($value === null || $value === '') {
        return $default;
    }
    return (string) $value;
}

function setting_set(PDO $pdo, string $key, string $value): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO store_settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    $stmt->execute([$key, $value]);
}

function whatsapp_url(PDO $pdo, string $message = ''): string
{
    $number = preg_replace('/\D+/', '', setting($pdo, 'contact_whatsapp'));
    if (!$number) {
        return '';
    }
    if (str_starts_with($number, '0')) {
        $number = '62' . substr($number, 1);
    }
    $url = 'https://wa.me/' . $number;
    if ($message !== '') {
        $url .= '?text=' . rawurlencode($message);
    }
    return $url;
}

// ---- Cart -----------------------------------------------------------------

function cart_raw(): array
{
    return isset($_SESSION['cart']) && is_array($_SESSION['cart']) ? $_SESSION['cart'] : [];
}

function cart_count(): int
{
    return array_sum(array_map('intval', cart_raw()));
}

/**
 * @return array{items: array<int, array>, subtotal: float, count: int, warnings: array}
 */
function cart_items(PDO $pdo): array
{
    $raw = cart_raw();
    if ($raw === []) {
        return ['items' => [], 'subtotal' => 0.0, 'count' => 0, 'warnings' => []];
    }

    $ids = array_values(array_filter(array_map('intval', array_keys($raw)), fn ($id) => $id > 0));
    if ($ids === []) {
        return ['items' => [], 'subtotal' => 0.0, 'count' => 0, 'warnings' => []];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM produk WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $products = [];
    foreach ($stmt->fetchAll() as $p) {
        $products[(int) $p['id']] = $p;
    }

    $items = [];
    $subtotal = 0.0;
    $count = 0;
    $warnings = [];

    foreach ($ids as $id) {
        $qty = max(1, (int) ($raw[$id] ?? 1));
        if (!isset($products[$id])) {
            unset($_SESSION['cart'][$id]);
            $warnings[] = 'Satu produk di keranjang sudah tidak tersedia dan telah diabaikan.';
            continue;
        }
        $p = $products[$id];
        if ((int) $p['is_active'] !== 1 || (int) ($p['is_demo'] ?? 0) === 1) {
            unset($_SESSION['cart'][$id]);
            $warnings[] = $p['nama'] . ' sedang tidak dijual dan telah diabaikan.';
            continue;
        }
        if (product_requires_whatsapp($p)) {
            unset($_SESSION['cart'][$id]);
            $warnings[] = $p['nama'] . ' perlu dikonfirmasi langsung melalui WhatsApp dan tidak masuk ke keranjang harga tetap.';
            continue;
        }
        $stock = (int) $p['stok'];
        if ($stock <= 0) {
            $warnings[] = $p['nama'] . ' sedang habis dan belum dapat dipesan.';
        } elseif ($qty > $stock) {
            $qty = $stock;
            $_SESSION['cart'][$id] = $qty;
            $warnings[] = 'Jumlah ' . $p['nama'] . ' disesuaikan dengan stok tersedia (' . $stock . ').';
        }
        $line = (float) $p['harga'] * $qty;
        $items[] = [
            'product' => $p,
            'qty' => $qty,
            'subtotal' => $line,
            'stock' => $stock,
        ];
        $subtotal += $line;
        $count += $qty;
    }

    return ['items' => $items, 'subtotal' => $subtotal, 'count' => $count, 'warnings' => array_values(array_unique($warnings))];
}

function cart_add(PDO $pdo, int $productId, int $qty = 1): array
{
    if ($productId <= 0 || $qty <= 0) {
        return ['success' => false, 'message' => 'Produk atau jumlah tidak valid.'];
    }
    $stmt = $pdo->prepare('SELECT * FROM produk WHERE id = ?');
    $stmt->execute([$productId]);
    $p = $stmt->fetch();
    if (!$p || (int) $p['is_active'] !== 1 || (int) $p['is_demo'] === 1) {
        return ['success' => false, 'message' => 'Produk tidak tersedia.'];
    }
    if (product_requires_whatsapp($p)) {
        return ['success' => false, 'message' => 'Harga atau ketersediaan produk ini dikonfirmasi melalui WhatsApp.'];
    }
    $stock = (int) $p['stok'];
    if ($stock <= 0) {
        return ['success' => false, 'message' => 'Produk sedang habis.'];
    }
    $current = (int) (cart_raw()[$productId] ?? 0);
    $new = $current + $qty;
    if ($new > $stock) {
        $new = $stock;
        $_SESSION['cart'][$productId] = $new;
        return ['success' => true, 'message' => 'Jumlah disesuaikan dengan stok tersedia (' . $stock . ').', 'qty' => $new, 'cart_count' => cart_count()];
    }
    $_SESSION['cart'][$productId] = $new;
    return ['success' => true, 'message' => $p['nama'] . ' ditambahkan ke keranjang.', 'qty' => $new, 'cart_count' => cart_count()];
}

function cart_set_qty(PDO $pdo, int $productId, int $qty): array
{
    if ($productId <= 0) {
        return ['success' => false, 'message' => 'Produk tidak valid.'];
    }
    if ($qty <= 0) {
        unset($_SESSION['cart'][$productId]);
        return ['success' => true, 'message' => 'Produk dihapus dari keranjang.', 'cart_count' => cart_count()];
    }
    $stmt = $pdo->prepare('SELECT stok FROM produk WHERE id = ?');
    $stmt->execute([$productId]);
    $stock = $stmt->fetchColumn();
    if ($stock === false) {
        return ['success' => false, 'message' => 'Produk tidak ditemukan.'];
    }
    if ($qty > (int) $stock) {
        $qty = (int) $stock;
        if ($qty <= 0) {
            unset($_SESSION['cart'][$productId]);
            return ['success' => true, 'message' => 'Produk habis dan dihapus dari keranjang.', 'cart_count' => cart_count()];
        }
    }
    $_SESSION['cart'][$productId] = $qty;
    return ['success' => true, 'message' => 'Jumlah diperbarui.', 'qty' => $qty, 'cart_count' => cart_count()];
}

function cart_remove(int $productId): void
{
    unset($_SESSION['cart'][$productId]);
}

function shipping_cost(PDO $pdo, float $subtotal): float
{
    $flat = (float) setting($pdo, 'shipping_flat', '0');
    $freeMin = (float) setting($pdo, 'free_shipping_min', '0');
    if ($flat <= 0) {
        return 0.0;
    }
    if ($freeMin > 0 && $subtotal >= $freeMin) {
        return 0.0;
    }
    return $flat;
}

// ---- Orders ---------------------------------------------------------------

function order_status_labels(): array
{
    return [
        'baru' => 'Baru',
        'diproses' => 'Diproses',
        'siap_dikirim' => 'Siap Dikirim',
        'dikirim' => 'Dikirim',
        'selesai' => 'Selesai',
        'dibatalkan' => 'Dibatalkan',
    ];
}

function payment_status_labels(): array
{
    return [
        'menunggu_pembayaran' => 'Menunggu Pembayaran',
        'dibayar' => 'Pembayaran Terverifikasi',
        'dibatalkan' => 'Pembayaran Dibatalkan',
        'refund' => 'Dana Dikembalikan',
    ];
}

function order_status_label(string $status): string
{
    return order_status_labels()[$status] ?? ucfirst(str_replace('_', ' ', $status));
}

function payment_status_label(string $status): string
{
    return payment_status_labels()[$status] ?? ucfirst(str_replace('_', ' ', $status));
}

function badge_class(string $status): string
{
    return 'badge badge-' . preg_replace('/[^a-z_]/', '', strtolower($status));
}

function order_transition_allowed(string $from, string $to): bool
{
    $map = [
        'baru' => ['diproses', 'dibatalkan'],
        'diproses' => ['siap_dikirim', 'dibatalkan'],
        'siap_dikirim' => ['dikirim', 'dibatalkan'],
        'dikirim' => ['selesai'],
        'selesai' => [],
        'dibatalkan' => [],
    ];
    return in_array($to, $map[$from] ?? [], true);
}

function payment_transition_allowed(string $from, string $to): bool
{
    $map = [
        'menunggu_pembayaran' => ['dibayar', 'dibatalkan'],
        'dibayar' => ['refund'],
        'dibatalkan' => [],
        'refund' => [],
    ];
    return in_array($to, $map[$from] ?? [], true);
}

function generate_order_number(PDO $pdo): string
{
    for ($i = 0; $i < 10; $i++) {
        $candidate = 'BJ-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM pesanan WHERE order_number = ?');
        $stmt->execute([$candidate]);
        if ((int) $stmt->fetchColumn() === 0) {
            return $candidate;
        }
    }
    throw new RuntimeException('Tidak dapat membuat nomor pesanan unik.');
}

function order_token(): string
{
    return bin2hex(random_bytes(24));
}

function log_admin_action(PDO $pdo, string $action, string $entityType, ?int $entityId, array $details = []): void
{
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO admin_audit_logs (user_id, action, entity_type, entity_id, details, ip_address, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([
            $_SESSION['user_id'] ?? null,
            $action,
            $entityType,
            $entityId,
            json_encode($details, JSON_UNESCAPED_UNICODE),
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    } catch (Throwable $e) {
        error_log('Audit log failed: ' . $e->getMessage());
    }
}

// ---- Validation -----------------------------------------------------------

function hashPassword(string $password): string
{
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}

function valid_email(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

function valid_phone(string $phone): bool
{
    $digits = preg_replace('/\D+/', '', $phone);
    return $digits !== '' && strlen($digits) >= 9 && strlen($digits) <= 15;
}

// ---- Uploads --------------------------------------------------------------

function handle_product_upload(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'filename' => null];
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload gagal (kode ' . (int) $file['error'] . ').'];
    }
    if (($file['size'] ?? 0) > 4 * 1024 * 1024) {
        return ['success' => false, 'message' => 'Ukuran gambar maksimal 4 MB.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    if (!isset($allowed[$mime])) {
        return ['success' => false, 'message' => 'Format gambar harus JPG, PNG, atau WebP.'];
    }
    $info = @getimagesize($file['tmp_name']);
    if ($info === false) {
        return ['success' => false, 'message' => 'Berkas bukan gambar yang valid.'];
    }

    $dir = APP_ROOT . '/assets/uploads';
    if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
        return ['success' => false, 'message' => 'Folder upload tidak dapat dibuat.'];
    }
    $filename = 'produk-' . date('Ymd') . '-' . bin2hex(random_bytes(6)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) {
        return ['success' => false, 'message' => 'Gambar gagal disimpan.'];
    }
    @chmod($dir . '/' . $filename, 0644);
    return ['success' => true, 'filename' => $filename];
}

function upload_url(?string $filename): string
{
    if (!$filename) {
        return product_image_url(null);
    }
    $file = APP_ROOT . '/assets/uploads/' . basename($filename);
    if (is_file($file)) {
        return site_url('assets/uploads/' . rawurlencode(basename($filename)));
    }
    return product_image_url($filename);
}

// ---- Pagination -----------------------------------------------------------

function paginate(int $total, int $perPage, int $page): array
{
    $perPage = max(1, $perPage);
    $pages = max(1, (int) ceil($total / $perPage));
    $page = min(max(1, $page), $pages);
    return [
        'page' => $page,
        'pages' => $pages,
        'per_page' => $perPage,
        'offset' => ($page - 1) * $perPage,
        'total' => $total,
    ];
}

function pagination_links(array $pagination, array $query = []): string
{
    $pages = (int) $pagination['pages'];
    if ($pages <= 1) {
        return '';
    }
    $page = (int) $pagination['page'];
    $link = static function (int $target, string $label, string $class = 'page-link', array $attrs = []) use ($query): string {
        $params = $query;
        $params['page'] = $target;
        $extra = '';
        foreach ($attrs as $name => $value) {
            $extra .= ' ' . $name . '="' . e($value) . '"';
        }
        return '<a class="' . e($class) . '" href="?' . e(http_build_query($params)) . '"' . $extra . '>' . e($label) . '</a>';
    };

    $html = '<nav class="pagination" aria-label="Navigasi halaman">';
    $html .= $page > 1
        ? $link($page - 1, '‹', 'page-link page-step', ['aria-label' => 'Halaman sebelumnya', 'rel' => 'prev'])
        : '<span class="page-link page-step is-disabled" aria-hidden="true">‹</span>';

    $lastShown = 0;
    for ($i = 1; $i <= $pages; $i++) {
        $show = $pages <= 7 || $i === 1 || $i === $pages || abs($i - $page) <= 1;
        if (!$show) {
            continue;
        }
        if ($lastShown > 0 && $i - $lastShown > 1) {
            $html .= '<span class="pagination-gap" aria-hidden="true">…</span>';
        }
        if ($i === $page) {
            $html .= '<span class="page-link is-active" aria-current="page">' . $i . '</span>';
        } else {
            $html .= $link($i, (string) $i);
        }
        $lastShown = $i;
    }

    $html .= $page < $pages
        ? $link($page + 1, '›', 'page-link page-step', ['aria-label' => 'Halaman berikutnya', 'rel' => 'next'])
        : '<span class="page-link page-step is-disabled" aria-hidden="true">›</span>';

    return $html . '</nav>';
}

// ---- Misc -----------------------------------------------------------------

function csrf_meta(): string
{
    return '<meta name="csrf-token" content="' . e(csrf_token()) . '">';
}

function demo_products_exist(PDO $pdo): bool
{
    try {
        return (int) $pdo->query('SELECT COUNT(*) FROM produk WHERE is_demo = 1 AND is_active = 1')->fetchColumn() > 0;
    } catch (Throwable $e) {
        return false;
    }
}
