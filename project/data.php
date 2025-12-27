<?php
// data.php – shared data & helpers
session_start();

/* ---------- BASE PRODUCTS (INR, with price history, vendor types) ---------- */

$PRODUCTS_BASE = [
    1 => [
        'id' => 1,
        'name' => 'Dell XPS 15',
        'category' => 'laptop',
        'brand' => 'Dell',
        'price' => 185000,
        'vendor_id' => 'V001',
        'vendor' => 'TechWorld',
        'rating' => 4.7,
        'image' => 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=900&q=80',
        'history' => ['3m' => 179000, '6m' => 189000, '12m' => 195000],
    ],
    2 => [
        'id' => 2,
        'name' => 'MacBook Air M2',
        'category' => 'laptop',
        'brand' => 'Apple',
        'price' => 135000,
        'vendor_id' => 'V002',
        'vendor' => 'Prime Electronics',
        'rating' => 4.9,
        'image' => 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=900&q=80',
        'history' => ['3m' => 132000, '6m' => 138000, '12m' => 145000],
    ],
    3 => [
        'id' => 3,
        'name' => 'HP Pavilion 14',
        'category' => 'laptop',
        'brand' => 'HP',
        'price' => 72000,
        'vendor_id' => 'V003',
        'vendor' => 'Bangalore Systems',
        'rating' => 4.3,
        'image' => 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=900&q=80',
        'history' => ['3m' => 69999, '6m' => 74999, '12m' => 78000],
    ],
    4 => [
        'id' => 4,
        'name' => 'Lenovo IdeaPad Slim 5',
        'category' => 'laptop',
        'brand' => 'Lenovo',
        'price' => 68000,
        'vendor_id' => 'V004',
        'vendor' => 'Mumbai Computers',
        'rating' => 4.4,
        'image' => 'https://images.unsplash.com/photo-1511385348-a52b4a160dc2?auto=format&fit=crop&w=900&q=80',
        'history' => ['3m' => 65999, '6m' => 69999, '12m' => 73000],
    ],
    5 => [
        'id' => 5,
        'name' => 'Samsung Galaxy S24',
        'category' => 'phone',
        'brand' => 'Samsung',
        'price' => 89999,
        'vendor_id' => 'V005',
        'vendor' => 'MobileMart',
        'rating' => 4.8,
        'image' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=900&q=80',
        'history' => ['3m' => 87999, '6m' => 91999, '12m' => 94999],
    ],
    6 => [
        'id' => 6,
        'name' => 'iPhone 15 Pro',
        'category' => 'phone',
        'brand' => 'Apple',
        'price' => 129999,
        'vendor_id' => 'V002',
        'vendor' => 'Prime Electronics',
        'rating' => 4.9,
        'image' => 'https://images.unsplash.com/photo-1510557880182-3d4d3cba35a5?auto=format&fit=crop&w=900&q=80',
        'history' => ['3m' => 127999, '6m' => 134999, '12m' => 139999],
    ],
    7 => [
        'id' => 7,
        'name' => 'OnePlus 12R 5G',
        'category' => 'phone',
        'brand' => 'OnePlus',
        'price' => 42999,
        'vendor_id' => 'V006',
        'vendor' => 'Ahmedabad Mobiles',
        'rating' => 4.5,
        'image' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=900&q=80',
        'history' => ['3m' => 41999, '6m' => 44999, '12m' => 45999],
    ],
    8 => [
        'id' => 8,
        'name' => 'Redmi Note 13 Pro',
        'category' => 'phone',
        'brand' => 'Xiaomi',
        'price' => 27999,
        'vendor_id' => 'V007',
        'vendor' => 'Chennai Digital',
        'rating' => 4.2,
        'image' => 'https://images.unsplash.com/photo-1480694313141-fce5e697ee25?auto=format&fit=crop&w=900&q=80',
        'history' => ['3m' => 26999, '6m' => 28999, '12m' => 30999],
    ],
    9 => [
        'id' => 9,
        'name' => 'Logitech MX Master 3S Mouse',
        'category' => 'accessory',
        'brand' => 'Logitech',
        'price' => 7999,
        'vendor_id' => 'V008',
        'vendor' => 'Accessory Zone',
        'rating' => 4.6,
        'image' => 'https://images.unsplash.com/photo-1527814050087-3793815479db?auto=format&fit=crop&w=900&q=80',
        'history' => ['3m' => 7599, '6m' => 8499, '12m' => 8999],
    ],
    10 => [
        'id' => 10,
        'name' => 'Sony WH‑1000XM5 Headphones',
        'category' => 'accessory',
        'brand' => 'Sony',
        'price' => 29999,
        'vendor_id' => 'V009',
        'vendor' => 'SoundLab',
        'rating' => 4.8,
        'image' => 'https://images.unsplash.com/photo-1519666213635-f953892fa780?auto=format&fit=crop&w=900&q=80',
        'history' => ['3m' => 28999, '6m' => 30999, '12m' => 32999],
    ],
];

// vendor main category – used so phone-only vendors only sell phones
$VENDOR_TYPES = [
    'V001' => 'laptop',
    'V002' => 'laptop',
    'V003' => 'laptop',
    'V004' => 'laptop',
    'V005' => 'phone',
    'V006' => 'phone',
    'V007' => 'phone',
    'V008' => 'accessory',
    'V009' => 'accessory',
];

// base vendors
$BASE_VENDORS = [
    'V001' => ['id' => 'V001', 'name' => 'TechWorld',         'city' => 'Mumbai'],
    'V002' => ['id' => 'V002', 'name' => 'Prime Electronics', 'city' => 'Delhi'],
    'V003' => ['id' => 'V003', 'name' => 'Bangalore Systems', 'city' => 'Bangalore'],
    'V004' => ['id' => 'V004', 'name' => 'Mumbai Computers',  'city' => 'Mumbai'],
    'V005' => ['id' => 'V005', 'name' => 'MobileMart',        'city' => 'Hyderabad'],
    'V006' => ['id' => 'V006', 'name' => 'Ahmedabad Mobiles', 'city' => 'Ahmedabad'],
    'V007' => ['id' => 'V007', 'name' => 'Chennai Digital',   'city' => 'Chennai'],
    'V008' => ['id' => 'V008', 'name' => 'Accessory Zone',    'city' => 'Pune'],
    'V009' => ['id' => 'V009', 'name' => 'SoundLab',          'city' => 'Kolkata'],
];

/* ---------- SESSION STATE ---------- */

$_SESSION['dynamic_products']      = $_SESSION['dynamic_products']      ?? []; // vendor-added products
$_SESSION['extra_vendors']         = $_SESSION['extra_vendors']         ?? []; // vendors via signup
$_SESSION['customers']             = $_SESSION['customers']             ?? []; // email => [id,name,pw]
$_SESSION['vendors_auth']          = $_SESSION['vendors_auth']          ?? []; // email => [id,name,pw,city,category]
$_SESSION['cart']                  = $_SESSION['cart']                  ?? []; // productId => qty
$_SESSION['orders']                = $_SESSION['orders']                ?? []; // list of orders
$_SESSION['reviews']               = $_SESSION['reviews']               ?? []; // productId => reviews
$_SESSION['visited']               = $_SESSION['visited']               ?? []; // customerId => [productId=>count]
$_SESSION['wishlist']              = $_SESSION['wishlist']              ?? []; // customerId => [ids]
$_SESSION['notifications']         = $_SESSION['notifications']         ?? []; // customerId => [msgs]
$_SESSION['admin_authenticated']   = $_SESSION['admin_authenticated']   ?? false;
// admin-side state
$_SESSION['admin_coupons']   = $_SESSION['admin_coupons']   ?? []; // code => rate (0.10 = 10%)
$_SESSION['admin_logs']      = $_SESSION['admin_logs']      ?? []; // list of audit entries
$_SESSION['vendor_status']   = $_SESSION['vendor_status']   ?? []; // vendorId => active|suspended
$_SESSION['customer_status'] = $_SESSION['customer_status'] ?? []; // customerId => active|blocked
$_SESSION['flash_sale']      = $_SESSION['flash_sale']      ?? null; // ['category'=>'laptop','discount'=>0.10]
$_SESSION['dynamic_products']      = $_SESSION['dynamic_products']      ?? [];
$_SESSION['extra_vendors']         = $_SESSION['extra_vendors']         ?? [];
$_SESSION['customers']             = $_SESSION['customers']             ?? [];
$_SESSION['vendors_auth']          = $_SESSION['vendors_auth']          ?? [];
$_SESSION['cart']                  = $_SESSION['cart']                  ?? [];
$_SESSION['orders']                = $_SESSION['orders']                ?? [];
$_SESSION['reviews']               = $_SESSION['reviews']               ?? [];
$_SESSION['visited']               = $_SESSION['visited']               ?? [];
$_SESSION['wishlist']              = $_SESSION['wishlist']              ?? [];
$_SESSION['notifications']         = $_SESSION['notifications']         ?? [];
$_SESSION['admin_authenticated']   = $_SESSION['admin_authenticated']   ?? false;
$_SESSION['admin_coupons']         = $_SESSION['admin_coupons']         ?? [];
$_SESSION['admin_logs']            = $_SESSION['admin_logs']            ?? [];
$_SESSION['vendor_status']         = $_SESSION['vendor_status']         ?? [];
$_SESSION['customer_status']       = $_SESSION['customer_status']       ?? [];
$_SESSION['flash_sale']            = $_SESSION['flash_sale']            ?? null;
$_SESSION['support_tickets']       = $_SESSION['support_tickets']       ?? []; // support tickets
/* ---------- PRODUCTS & VENDORS HELPERS ---------- */

function all_products(): array {
    global $PRODUCTS_BASE;
    return $PRODUCTS_BASE + ($_SESSION['dynamic_products'] ?? []);
}

function get_product(int $id): ?array {
    $all = all_products();
    return $all[$id] ?? null;
}

function all_vendors(): array {
    global $BASE_VENDORS;
    return $BASE_VENDORS + ($_SESSION['extra_vendors'] ?? []);
}

function products_for_vendor(string $vendorId): array {
    global $VENDOR_TYPES;
    $all = all_products();
    $type = $VENDOR_TYPES[$vendorId] ?? null;
    $out = [];
    foreach ($all as $p) {
        if ($p['vendor_id'] === $vendorId) {
            if (!$type || $p['category'] === $type) {
                $out[] = $p;
            }
        }
    }
    return $out;
}

function gst_breakup(int $price): array {
    $gst = (int) round($price * 0.18);
    return [$gst, $price + $gst];
}

/* ---------- AUTH HELPERS ---------- */

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function require_login(string $role = 'any'): void {
    $u = current_user();
    if (!$u) {
        $next = urlencode($_SERVER['REQUEST_URI']);
        header("Location: login.php?next={$next}");
        exit;
    }
    if ($role !== 'any' && $u['role'] !== $role) {
        header("Location: index.php");
        exit;
    }
}

function register_customer(string $name, string $email, string $password): array {
    $id = 'C' . (1000 + count($_SESSION['customers']) + 1);
    $_SESSION['customers'][$email] = [
        'id' => $id,
        'name' => $name,
        'password' => $password,
    ];
    return $_SESSION['customers'][$email];
}

function authenticate_customer(string $email, string $password): ?array {
    $c = $_SESSION['customers'][$email] ?? null;
    if ($c && $c['password'] === $password) {
        return ['role' => 'buyer', 'id' => $c['id'], 'name' => $c['name'], 'email' => $email];
    }
    return null;
}

function register_vendor(string $name, string $email, string $city, string $category): array {
    $id = 'V' . (200 + count($_SESSION['vendors_auth']) + count($_SESSION['extra_vendors']) + 1);
    $password = 'vendor123'; // demo initial password (vendor contacts admin to change)
    $_SESSION['vendors_auth'][$email] = [
        'id' => $id,
        'name' => $name,
        'password' => $password,
        'city' => $city,
        'category' => strtolower($category), // laptop / phone / accessory
    ];
    $_SESSION['extra_vendors'][$id] = ['id' => $id, 'name' => $name, 'city' => $city];
    return $_SESSION['vendors_auth'][$email];
}

function authenticate_vendor(string $email, string $password): ?array {
    $v = $_SESSION['vendors_auth'][$email] ?? null;
    if ($v && $v['password'] === $password) {
        return [
            'role' => 'vendor',
            'id' => $v['id'],
            'name' => $v['name'],
            'email' => $email,
            'category' => $v['category'],
        ];
    }
    return null;
}

function login_user(array $user): void {
    $_SESSION['user'] = $user;
}

function log_admin_event(string $action, string $details = ''): void {
    $_SESSION['admin_logs'][] = [
        'time'   => time(),
        'action' => $action,
        'details'=> $details,
    ];
}

/* ---------- VISITS / WISHLIST / NOTIFICATIONS ---------- */

function record_visit(int $productId): void {
    $u = current_user();
    if (!$u || $u['role'] !== 'buyer') return;
    $cid = $u['id'];
    $_SESSION['visited'][$cid][$productId] = ($_SESSION['visited'][$cid][$productId] ?? 0) + 1;
}

function frequently_visited_for_current(int $limit = 4): array {
    $u = current_user();
    if (!$u || $u['role'] !== 'buyer') return [];
    $cid = $u['id'];
    $counts = $_SESSION['visited'][$cid] ?? [];
    arsort($counts);
    $ids = array_slice(array_keys($counts), 0, $limit);
    $out = [];
    foreach ($ids as $pid) {
        $p = get_product($pid);
        if ($p) $out[] = $p;
    }
    return $out;
}

function add_to_wishlist(int $productId): void {
    $u = current_user();
    if (!$u || $u['role'] !== 'buyer') return;
    $cid = $u['id'];

    if (!isset($_SESSION['wishlist'][$cid]) || !is_array($_SESSION['wishlist'][$cid])) {
        $_SESSION['wishlist'][$cid] = [];
    }

    // Store metadata (currently only date added)
    if (!isset($_SESSION['wishlist'][$cid][$productId])) {
        $_SESSION['wishlist'][$cid][$productId] = [
            'added_at' => time(),
        ];
    }
}

function remove_from_wishlist(int $productId): void {
    $u = current_user();
    if (!$u || $u['role'] !== 'buyer') return;
    $cid = $u['id'];
    if (isset($_SESSION['wishlist'][$cid][$productId])) {
        unset($_SESSION['wishlist'][$cid][$productId]);
    }
}

function wishlist_for_current(): array {
    $u = current_user();
    if (!$u || $u['role'] !== 'buyer') return [];
    $cid = $u['id'];

    $entries = $_SESSION['wishlist'][$cid] ?? [];

    // Handle legacy format where wishlist was [productId, ...]
    $normalized = [];
    foreach ($entries as $key => $value) {
        if (is_int($key) && is_array($value) && isset($value['added_at'])) {
            // new format already
            $normalized[$key] = $value;
        } elseif (is_int($value)) {
            // old format: numeric array of product IDs
            $normalized[$value] = ['added_at' => time()];
        }
    }
    if ($normalized) {
        $_SESSION['wishlist'][$cid] = $normalized;
        $entries = $normalized;
    }

    $out = [];
    foreach ($entries as $pid => $meta) {
        $p = get_product((int)$pid);
        if ($p) {
            $p['wishlist_added_at'] = $meta['added_at'] ?? time();
            $out[] = $p;
        }
    }
    return $out;
}

function notify_all_customers(string $message): void {
    foreach ($_SESSION['customers'] as $email => $c) {
        $cid = $c['id'];
        $_SESSION['notifications'][$cid][] = $message;
    }
}

function notifications_for_current(): array {
    $u = current_user();
    if (!$u || $u['role'] !== 'buyer') return [];
    $cid = $u['id'];
    return $_SESSION['notifications'][$cid] ?? [];
}

/* ---------- COUPONS ---------- */

function apply_coupon(string $code, int $amount): array {
    $code = strtoupper(trim($code));
    // Built-in coupons
    $map = [
        'SASTANASHA'    => 0.15,
        'JALDIWALAAAYA' => 0.25,
        'UTHA LE RE'    => 0.50,
    ];
    // Admin-defined coupons override or extend
    $dynamic = $_SESSION['admin_coupons'] ?? [];
    foreach ($dynamic as $c => $rate) {
        $map[$c] = $rate;
    }

    if (!isset($map[$code])) {
        return [0, $amount, null];
    }

    $rate = $map[$code];                       // e.g. 0.15 for 15%
    $discount = (int) round($amount * $rate);
    return [$discount, $amount - $discount, $rate * 100]; // [discount, newAmount, percent]
}

// ---------- ORDER STATUS BADGE HELPER (shared by orders.php & order_details.php) ----------
function status_badge(string $s): array {
    $s = ucfirst($s);
    switch ($s) {
        case 'Delivered':        return ['🟢 Delivered',        'success'];
        case 'Out for delivery': return ['🟡 Out for delivery', 'warning'];
        case 'Shipped':          return ['🟡 Shipped',          'warning'];
        case 'Packed':           return ['🟡 Packed',           'warning'];
        case 'Placed':           return ['🔵 Placed',           'primary'];
        case 'Refunded':         return ['🟣 Refunded',         'info'];
        case 'Cancelled':        return ['🔴 Cancelled',        'danger'];
        default:                 return [$s,                   'secondary'];
    }
}