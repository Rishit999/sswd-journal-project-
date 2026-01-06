<?php

session_start();

require_once __DIR__ . '/db.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = Database::getInstance()->pdo();
    }
    return $pdo;
}

/**
 * Map base product IDs to local image paths in /images for this installation.
 * Falls back to the DB image URL when no local file is defined (e.g. custom products).
 */
function local_product_image_path(int $productId, string $current): string
{
    $map = [
        // Laptops
        1 => 'images/xps15-fi-hero.jpg',                         // Dell XPS 15
        2 => 'images/42925-83435-Apple-2015-MacBook-xl.jpg',     // MacBook Air M2
        3 => 'images/hp.webp',                                   // HP Pavilion 14
        4 => 'images/lenovo.webp',                               // Lenovo IdeaPad Slim 5
        // Phones
        5 => 'images/samsung.jpg',                               // Samsung Galaxy S24
        6 => 'images/apple-iphone-15-pro-max-v1-01.jpg',         // iPhone 15 Pro
        7 => 'images/OnePlus-12R-Genshin-Impact-design-1024x814.jpg', // OnePlus 12R 5G
        8 => 'images/redminote13pro5g.jpg',                      // Redmi Note 13 Pro
        // Accessories
        10 => 'images/sonyheadphone.jpg',                        // Sony WH-1000XM5 Headphones
    ];

    return $map[$productId] ?? $current;
}

function gst_breakup(int $price): array
{
    $gst = (int) round($price * 0.18);
    return [$gst, $price + $gst];
}

function generate_customer_id(): string
{
    $stmt = db()->query("SELECT id FROM customers ORDER BY CAST(SUBSTRING(id, 2) AS UNSIGNED) DESC LIMIT 1");
    $last = $stmt->fetchColumn();
    $next = $last ? ((int) substr($last, 1)) + 1 : 1001;
    return 'C' . $next;
}

function generate_vendor_id(): string
{
    $stmt = db()->query("SELECT id FROM vendors ORDER BY CAST(SUBSTRING(id, 2) AS UNSIGNED) DESC LIMIT 1");
    $last = $stmt->fetchColumn();
    $next = $last ? ((int) substr($last, 1)) + 1 : 2001;
    return 'V' . $next;
}

function generate_order_id(): string
{
    $stmt = db()->query("SELECT order_id FROM orders ORDER BY CAST(SUBSTRING(order_id, 4) AS UNSIGNED) DESC LIMIT 1");
    $last = $stmt->fetchColumn();
    $next = $last ? ((int) substr($last, 3)) + 1 : 1001;
    return 'ORD' . $next;
}

function all_products(): array
{
    $sql = "SELECT p.*, v.name AS vendor_name FROM products p
            JOIN vendors v ON v.id = p.vendor_id
            ORDER BY p.id";
    $rows = db()->query($sql)->fetchAll();
    $out = [];
    foreach ($rows as $row) {
        $history = [
            '3m'  => $row['price_3m'] ?? $row['price'],
            '6m'  => $row['price_6m'] ?? $row['price'],
            '12m' => $row['price_12m'] ?? $row['price'],
        ];
        $out[$row['id']] = [
            'id'        => (int) $row['id'],
            'name'      => $row['name'],
            'category'  => $row['category'],
            'brand'     => $row['brand'],
            'price'     => (int) $row['price'],
            'vendor_id' => $row['vendor_id'],
            'vendor'    => $row['vendor_name'],
            'rating'    => (float) $row['rating'],
            'image'     => local_product_image_path((int) $row['id'], $row['image']),
            'history'   => $history,
        ];
    }
    return $out;
}

function get_product(int $id): ?array
{
    $stmt = db()->prepare("SELECT p.*, v.name AS vendor_name FROM products p JOIN vendors v ON v.id = p.vendor_id WHERE p.id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }
    return [
        'id'        => (int) $row['id'],
        'name'      => $row['name'],
        'category'  => $row['category'],
        'brand'     => $row['brand'],
        'price'     => (int) $row['price'],
        'vendor_id' => $row['vendor_id'],
        'vendor'    => $row['vendor_name'],
        'rating'    => (float) $row['rating'],
        'image'     => local_product_image_path((int) $row['id'], $row['image']),
        'history'   => [
            '3m'  => $row['price_3m'] ?? $row['price'],
            '6m'  => $row['price_6m'] ?? $row['price'],
            '12m' => $row['price_12m'] ?? $row['price'],
        ],
    ];
}

function all_vendors(): array
{
    $rows = db()->query("SELECT * FROM vendors ORDER BY name")->fetchAll();
    $vendors = [];
    foreach ($rows as $row) {
        $vendors[$row['id']] = [
            'id'     => $row['id'],
            'name'   => $row['name'],
            'city'   => $row['city'],
            'email'  => $row['email'],
            'status' => $row['status'],
            'category' => $row['category'],
        ];
    }
    return $vendors;
}

function products_for_vendor(string $vendorId): array
{
    $stmt = db()->prepare("SELECT * FROM products WHERE vendor_id = ? ORDER BY created_at DESC");
    $stmt->execute([$vendorId]);
    return $stmt->fetchAll();
}

function add_vendor_product(string $vendorId, string $productName, string $category): void
{
    $vendors = all_vendors();
    if (!isset($vendors[$vendorId])) {
        throw new RuntimeException('Vendor not found');
    }
    $vendor = $vendors[$vendorId];
    $stmt = db()->prepare("
        INSERT INTO products (name, category, brand, price, vendor_id, rating, image, price_3m, price_6m, price_12m)
        VALUES (?, ?, 'Custom', 50000, ?, 4.0, ?, 48000, 52000, 54000)
    ");
    $stmt->execute([
        $productName,
        $category,
        $vendorId,
        'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=900&q=80'
    ]);
    notify_all_customers("New {$category} added by {$vendor['name']}: {$productName}");
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function login_user(array $user): void
{
    $_SESSION['user'] = $user;
}

function require_login(string $role = 'any'): void
{
    $u = current_user();
    if (!$u) {
        $next = urlencode($_SERVER['REQUEST_URI'] ?? 'index.php');
        header("Location: login.php?next={$next}");
        exit;
    }
    if ($role !== 'any' && $u['role'] !== $role) {
        header('Location: index.php');
        exit;
    }
}

function register_customer(string $name, string $email, string $password): array
{
    $id = generate_customer_id();
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = db()->prepare("INSERT INTO customers (id, name, email, password, status) VALUES (?, ?, ?, ?, 'active')");
    $stmt->execute([$id, $name, $email, $hash]);
    return ['id' => $id, 'name' => $name, 'email' => $email];
}

function authenticate_customer(string $email, string $password): ?array
{
    $stmt = db()->prepare("SELECT * FROM customers WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    if ($row && password_verify($password, $row['password'])) {
        if ($row['status'] !== 'active') {
            return null;
        }
        return [
            'role' => 'buyer',
            'id'   => $row['id'],
            'name' => $row['name'],
            'email'=> $row['email'],
        ];
    }
    return null;
}

function register_vendor(string $name, string $email, string $city, string $category): array
{
    $id = generate_vendor_id();
    $password = password_hash('vendor123', PASSWORD_BCRYPT);
    $stmt = db()->prepare("INSERT INTO vendors (id, name, email, password, city, category, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->execute([$id, $name, $email, $password, $city, strtolower($category)]);
    return ['id' => $id, 'name' => $name, 'email' => $email, 'category' => strtolower($category)];
}

function authenticate_vendor(string $email, string $password): ?array
{
    $stmt = db()->prepare("SELECT * FROM vendors WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    if ($row && password_verify($password, $row['password']) && $row['status'] !== 'suspended') {
        return [
            'role' => 'vendor',
            'id'   => $row['id'],
            'name' => $row['name'],
            'email'=> $row['email'],
            'category' => $row['category'],
        ];
    }
    return null;
}

function set_customer_status(string $customerId, string $status): void
{
    $stmt = db()->prepare("UPDATE customers SET status = ? WHERE id = ?");
    $stmt->execute([$status, $customerId]);
    log_admin_event('customer_' . $status, "Customer {$customerId}");
}

function set_vendor_status(string $vendorId, string $status): void
{
    $stmt = db()->prepare("UPDATE vendors SET status = ? WHERE id = ?");
    $stmt->execute([$status, $vendorId]);
    log_admin_event('vendor_' . $status, "Vendor {$vendorId}");
}

function log_admin_event(string $action, string $details = ''): void
{
    $stmt = db()->prepare("INSERT INTO admin_logs (action, details) VALUES (?, ?)");
    $stmt->execute([$action, $details]);
}

function record_visit(int $productId): void
{
    $user = current_user();
    if (!$user || $user['role'] !== 'buyer') {
        return;
    }
    $stmt = db()->prepare("
        INSERT INTO visited_products (customer_id, product_id, visit_count)
        VALUES (?, ?, 1)
        ON DUPLICATE KEY UPDATE visit_count = visit_count + 1, last_visited = NOW()
    ");
    $stmt->execute([$user['id'], $productId]);
}

function frequently_visited_for_current(int $limit = 4): array
{
    $user = current_user();
    if (!$user || $user['role'] !== 'buyer') {
        return [];
    }
    $stmt = db()->prepare("
        SELECT p.* , v.name AS vendor_name
        FROM visited_products vp
        JOIN products p ON p.id = vp.product_id
        JOIN vendors v ON v.id = p.vendor_id
        WHERE vp.customer_id = ?
        ORDER BY vp.visit_count DESC, vp.last_visited DESC
        LIMIT ?
    ");
    $stmt->execute([$user['id'], $limit]);
    $rows = $stmt->fetchAll();
    $out = [];
    foreach ($rows as $row) {
        $out[] = [
            'id'        => (int) $row['id'],
            'name'      => $row['name'],
            'category'  => $row['category'],
            'brand'     => $row['brand'],
            'price'     => (int) $row['price'],
            'vendor_id' => $row['vendor_id'],
            'vendor'    => $row['vendor_name'],
            'rating'    => (float) $row['rating'],
            'image'     => local_product_image_path((int) $row['id'], $row['image']),
            'history'   => [
                '3m'  => $row['price_3m'] ?? $row['price'],
                '6m'  => $row['price_6m'] ?? $row['price'],
                '12m' => $row['price_12m'] ?? $row['price'],
            ],
        ];
    }
    return $out;
}

function add_to_wishlist(int $productId): void
{
    $user = current_user();
    if (!$user || $user['role'] !== 'buyer') {
        return;
    }
    $stmt = db()->prepare("
        INSERT INTO wishlists (customer_id, product_id)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE added_at = NOW()
    ");
    $stmt->execute([$user['id'], $productId]);
}

function remove_from_wishlist(int $productId): void
{
    $user = current_user();
    if (!$user || $user['role'] !== 'buyer') {
        return;
    }
    $stmt = db()->prepare("DELETE FROM wishlists WHERE customer_id = ? AND product_id = ?");
    $stmt->execute([$user['id'], $productId]);
}

function wishlist_for_current(): array
{
    $user = current_user();
    if (!$user || $user['role'] !== 'buyer') {
        return [];
    }
    $stmt = db()->prepare("
        SELECT w.*, p.*, v.name AS vendor_name
        FROM wishlists w
        JOIN products p ON p.id = w.product_id
        JOIN vendors v ON v.id = p.vendor_id
        WHERE w.customer_id = ?
        ORDER BY w.added_at DESC
    ");
    $stmt->execute([$user['id']]);
    $rows = $stmt->fetchAll();
    $wish = [];
    foreach ($rows as $row) {
        $wish[] = [
            'id'                => (int) $row['id'],
            'name'              => $row['name'],
            'category'          => $row['category'],
            'brand'             => $row['brand'],
            'price'             => (int) $row['price'],
            'vendor_id'         => $row['vendor_id'],
            'vendor'            => $row['vendor_name'],
            'rating'            => (float) $row['rating'],
            'image'             => local_product_image_path((int) $row['id'], $row['image']),
            'history'           => [
                '3m'  => $row['price_3m'] ?? $row['price'],
                '6m'  => $row['price_6m'] ?? $row['price'],
                '12m' => $row['price_12m'] ?? $row['price'],
            ],
            'wishlist_added_at' => strtotime($row['added_at']),
        ];
    }
    return $wish;
}

function notify_all_customers(string $message): void
{
    $stmt = db()->prepare("
        INSERT INTO notifications (customer_id, message)
        SELECT id, ? FROM customers WHERE status = 'active'
    ");
    $stmt->execute([$message]);
}

function notifications_for_current(): array
{
    $user = current_user();
    if (!$user || $user['role'] !== 'buyer') {
        return [];
    }
    $stmt = db()->prepare("SELECT * FROM notifications WHERE customer_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user['id']]);
    return $stmt->fetchAll();
}

function apply_coupon(string $code, int $amount): array
{
    $code = strtoupper(trim($code));
    if ($code === '') {
        return [0, $amount, null];
    }
    $stmt = db()->prepare("SELECT discount_rate FROM coupons WHERE code = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$code]);
    $rate = $stmt->fetchColumn();
    if ($rate === false) {
        return [0, $amount, null];
    }
    $discount = (int) round($amount * $rate);
    return [$discount, $amount - $discount, $rate * 100];
}

function get_flash_sale(): ?array
{
    $row = db()->query("SELECT * FROM flash_sales WHERE is_active = 1 ORDER BY updated_at DESC LIMIT 1")->fetch();
    if (!$row) {
        return null;
    }
    return [
        'category' => $row['category'],
        'discount' => (float) $row['discount_rate'],
    ];
}

function set_flash_sale(?string $category, ?float $rate): void
{
    if (!$category || !$rate) {
        db()->exec("UPDATE flash_sales SET is_active = 0");
        log_admin_event('flash_sale_clear', 'Cleared');
        return;
    }
    $stmt = db()->prepare("INSERT INTO flash_sales (category, discount_rate, is_active) VALUES (?, ?, 1)");
    $stmt->execute([$category, $rate]);
    log_admin_event('flash_sale_set', "{$category} @ " . ($rate * 100) . "%");
}

function add_coupon(string $code, float $percent): void
{
    $rate = $percent / 100;
    $stmt = db()->prepare("
        INSERT INTO coupons (code, discount_rate, is_active)
        VALUES (?, ?, 1)
        ON DUPLICATE KEY UPDATE discount_rate = VALUES(discount_rate), is_active = 1
    ");
    $stmt->execute([$code, $rate]);
    log_admin_event('coupon_add', "Code {$code} at {$percent}%");
}

function get_coupons(): array
{
    return db()->query("SELECT * FROM coupons ORDER BY created_at DESC")->fetchAll();
}

function get_admin_logs(int $limit = 20): array
{
    $stmt = db()->prepare("SELECT * FROM admin_logs ORDER BY created_at DESC LIMIT ?");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function get_customers(): array
{
    return db()->query("SELECT * FROM customers ORDER BY created_at DESC")->fetchAll();
}

function get_orders(array $filters = []): array
{
    $where = [];
    $params = [];
    if (isset($filters['customer_id'])) {
        $where[] = 'o.customer_id = ?';
        $params[] = $filters['customer_id'];
    }
    if (isset($filters['vendor_id'])) {
        $where[] = 'oi.vendor_id = ?';
        $params[] = $filters['vendor_id'];
    }
    $sql = "SELECT o.*, oi.product_id, oi.product_name, oi.vendor_id, oi.vendor_name,
                   oi.qty, oi.price, oi.line_total
            FROM orders o
            JOIN order_items oi ON oi.order_id = o.order_id";
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY o.created_at DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function add_to_cart(string $customerId, int $productId, int $qty = 1, bool $replace = false): void
{
    if ($replace) {
        $stmt = db()->prepare("DELETE FROM carts WHERE customer_id = ?");
        $stmt->execute([$customerId]);
    }
    $stmt = db()->prepare("
        INSERT INTO carts (customer_id, product_id, qty)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE qty = qty + VALUES(qty)
    ");
    $stmt->execute([$customerId, $productId, $qty]);
}

function get_cart(string $customerId): array
{
    $stmt = db()->prepare("
        SELECT c.product_id, c.qty, p.*, v.name AS vendor_name
        FROM carts c
        JOIN products p ON p.id = c.product_id
        JOIN vendors v ON v.id = p.vendor_id
        WHERE c.customer_id = ?
    ");
    $stmt->execute([$customerId]);
    $items = [];
    foreach ($stmt->fetchAll() as $row) {
        $items[] = [
            'product' => [
                'id'        => (int) $row['id'],
                'name'      => $row['name'],
                'vendor'    => $row['vendor_name'],
                'vendor_id' => $row['vendor_id'],
                'price'     => (int) $row['price'],
                'image'     => local_product_image_path((int) $row['id'], $row['image']),
                'category'  => $row['category'],
                'brand'     => $row['brand'],
                'rating'    => (float) $row['rating'],
            ],
            'qty'  => (int) $row['qty'],
            'line' => (int) $row['qty'] * (int) $row['price'],
        ];
    }
    return $items;
}

function clear_cart(string $customerId): void
{
    $stmt = db()->prepare("DELETE FROM carts WHERE customer_id = ?");
    $stmt->execute([$customerId]);
}

function create_order(array $payload): string
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $orderId = generate_order_id();
        $stmt = $pdo->prepare("
            INSERT INTO orders (
                order_id, customer_id, customer_name, status, payment_mode,
                shipping_type, shipping_fee, address, city, pincode,
                coupon_code, discount, subtotal, total_amount
            ) VALUES (?, ?, ?, 'Placed', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $orderId,
            $payload['customer_id'],
            $payload['customer_name'],
            $payload['payment_mode'],
            $payload['shipping'],
            $payload['shipping_fee'],
            $payload['address'],
            $payload['city'],
            $payload['pincode'],
            $payload['coupon_code'],
            $payload['discount'],
            $payload['subtotal'],
            $payload['final_total'],
        ]);

        $itemStmt = $pdo->prepare("
            INSERT INTO order_items (
                order_id, product_id, product_name, vendor_id, vendor_name,
                qty, price, line_total
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        foreach ($payload['items'] as $row) {
            $itemStmt->execute([
                $orderId,
                $row['product']['id'],
                $row['product']['name'],
                $row['product']['vendor_id'],
                $row['product']['vendor'],
                $row['qty'],
                $row['product']['price'],
                $row['line'],
            ]);
        }

        $pdo->commit();
        return $orderId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function update_order_statuses(): void
{
    $pdo = db();
    $statuses = [
        'Placed' => 'Packed',
        'Packed' => 'Shipped',
        'Shipped' => 'Out for delivery',
        'Out for delivery' => 'Delivered',
    ];
    foreach ($statuses as $from => $to) {
        $stmt = $pdo->prepare("
            UPDATE orders SET status = ?
            WHERE status = ? AND TIMESTAMPDIFF(MINUTE, created_at, NOW()) > 2
        ");
        $stmt->execute([$to, $from]);
    }
}

function cancel_order(string $orderId, string $reason, string $customerId): void
{
    $stmt = db()->prepare("
        UPDATE orders
        SET status = 'Cancelled', cancel_reason = ?
        WHERE order_id = ? AND customer_id = ? AND status NOT IN ('Cancelled','Delivered','Refunded')
    ");
    $stmt->execute([$reason . ' (cancelled by customer)', $orderId, $customerId]);
}

function refund_order(string $orderId, int $amount): void
{
    $stmt = db()->prepare("UPDATE orders SET status = 'Refunded', refund_amount = ? WHERE order_id = ?");
    $stmt->execute([$amount, $orderId]);
    log_admin_event('refund', "Order {$orderId} refunded ₹{$amount}");
}

function get_support_tickets(string $userId = null): array
{
    if ($userId) {
        $stmt = db()->prepare("SELECT * FROM support_tickets WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    return db()->query("SELECT * FROM support_tickets ORDER BY created_at DESC")->fetchAll();
}

function create_support_ticket(array $data): string
{
    $ticketId = 'SUP' . str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT);
    $stmt = db()->prepare("
        INSERT INTO support_tickets (ticket_id, user_id, role, name, email, order_id, issue_type, message)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $ticketId,
        $data['user_id'] ?? null,
        $data['role'] ?? 'customer',
        $data['name'],
        $data['email'],
        $data['order_id'] ?? null,
        $data['issue_type'],
        $data['message'],
    ]);
    return $ticketId;
}

function update_ticket_status(string $ticketId, string $status, string $userId): void
{
    $stmt = db()->prepare("UPDATE support_tickets SET status = ? WHERE ticket_id = ? AND user_id = ?");
    $stmt->execute([$status, $ticketId, $userId]);
}

function add_review(int $productId, string $name, int $rating, string $text): void
{
    $stmt = db()->prepare("INSERT INTO reviews (product_id, user_name, rating, review_text) VALUES (?, ?, ?, ?)");
    $stmt->execute([$productId, $name ?: 'Anonymous', max(1, min(5, $rating)), $text]);
}

function get_reviews(int $productId): array
{
    $stmt = db()->prepare("SELECT * FROM reviews WHERE product_id = ? ORDER BY created_at DESC");
    $stmt->execute([$productId]);
    return $stmt->fetchAll();
}

function status_badge(string $status): array
{
    $status = ucfirst($status);
    switch ($status) {
        case 'Delivered':
            return ['🟢 Delivered', 'success'];
        case 'Out for delivery':
            return ['🟡 Out for delivery', 'warning'];
        case 'Shipped':
            return ['🟡 Shipped', 'warning'];
        case 'Packed':
            return ['🟡 Packed', 'warning'];
        case 'Placed':
            return ['🔵 Placed', 'primary'];
        case 'Refunded':
            return ['🟣 Refunded', 'info'];
        case 'Cancelled':
            return ['🔴 Cancelled', 'danger'];
        default:
            return [$status, 'secondary'];
    }
}
