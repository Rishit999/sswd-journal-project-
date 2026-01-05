<?php require_once 'data.php';

$vendors  = all_vendors();
$products = all_products();
$orders   = $_SESSION['orders'] ?? [];
$statuses = $_SESSION['vendor_status'] ?? [];

// Build vendor meta: rating, products, categories, performance, etc.
$vendorList = [];
foreach ($vendors as $v) {
    $vid = $v['id'];

    // Products for this vendor
    $plist = [];
    foreach ($products as $p) {
        if ($p['vendor_id'] === $vid) {
            $plist[] = $p;
        }
    }

    $categories = [];
    $ratingSum = 0;
    $ratingCount = 0;
    foreach ($plist as $p) {
        $categories[$p['category']] = true;
        $ratingSum += $p['rating'];
        $ratingCount++;
    }
    $avgRating = $ratingCount ? $ratingSum / $ratingCount : 4.5;
    $reviewCount = $ratingCount * 2; // simple approximation

    // Orders for this vendor
    $vendorOrders = [];
    foreach ($orders as $o) {
        if ($o['vendor_id'] === $vid) {
            $vendorOrders[] = $o;
        }
    }
    $orderCount = count($vendorOrders);
    $cancelled  = 0;
    foreach ($vendorOrders as $o) {
        if (in_array($o['status'], ['Cancelled','Refunded'], true)) {
            $cancelled++;
        }
    }
    $fulfillRate = $orderCount ? round(100 * ($orderCount - $cancelled) / $orderCount) : 100;
    $cancelRate  = $orderCount ? round(100 *  $cancelled               / $orderCount) : 0;

    $status   = $statuses[$vid] ?? 'active';   // active | suspended
    $verified = ($status !== 'suspended');

    // simple "since" year (you can extend if you store real dates)
    $sinceYear = 2023;

    $vendorList[] = [
        'id'            => $vid,
        'name'          => $v['name'],
        'city'          => $v['city'],
        'rating'        => $avgRating,
        'reviews'       => $reviewCount,
        'categories'    => array_keys($categories),
        'product_count' => count($plist),
        'order_count'   => $orderCount,
        'fulfill_rate'  => $fulfillRate,
        'cancel_rate'   => $cancelRate,
        'status'        => $status,
        'verified'      => $verified,
        'since_year'    => $sinceYear,
    ];
}

// Build filter option data
$cities     = [];
$catOptions = [];
foreach ($vendorList as $v) {
    $cities[$v['city']] = true;
    foreach ($v['categories'] as $c) {
        $catOptions[$c] = true;
    }
}
ksort($cities);
ksort($catOptions);

// Filters
$searchName  = trim($_GET['search'] ?? '');
$filterCity  = $_GET['city'] ?? 'all';
$filterCat   = $_GET['category'] ?? 'all';
$filterRating= $_GET['rating'] ?? 'all'; // all, 4plus
$filterVerif = isset($_GET['verified']) ? true : false;
$sort        = $_GET['sort'] ?? 'rating_desc'; // rating_desc, products_desc, fulfill_desc, name_asc

// Apply filters
$filtered = array_filter($vendorList, function($v) use ($searchName,$filterCity,$filterCat,$filterRating,$filterVerif) {
    if ($filterCity !== 'all' && $v['city'] !== $filterCity) return false;
    if ($filterCat !== 'all'  && !in_array($filterCat, $v['categories'], true)) return false;
    if ($filterRating === '4plus' && $v['rating'] < 4.0) return false;
    if ($filterVerif && !$v['verified']) return false;

    if ($searchName !== '') {
        $q = mb_strtolower($searchName);
        if (mb_stripos($v['name'], $q) === false && mb_stripos($v['city'], $q) === false) {
            return false;
        }
    }
    return true;
});

// Sorting
usort($filtered, function($a,$b) use ($sort) {
    switch ($sort) {
        case 'products_desc': return $b['product_count'] <=> $a['product_count'];
        case 'fulfill_desc':  return $b['fulfill_rate']  <=> $a['fulfill_rate'];
        case 'name_asc':      return strcmp($a['name'], $b['name']);
        case 'rating_desc':
        default:              return $b['rating']        <=> $a['rating'];
    }
});

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Vendors | ElectroHub</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="style.css">
  <style>
    .vendor-card-header-name {
      font-size: 1.05rem;
      font-weight: 600;
    }
  </style>
</head>
<body>
<?php include 'nav.php'; ?>

<section class="catalog-section py-4">
  <div class="container">
    <h2 class="section-title mb-1">Marketplace vendors</h2>
    <p class="section-subtitle mb-3">
      Discover trusted electronics sellers, compare their ratings and browse each store’s offers.
    </p>

    <!-- Filters & sorting -->
    <form class="row g-2 mb-3" method="get">
      <div class="col-md-3">
        <input type="text" name="search" class="form-control dark-input"
               placeholder="Search by vendor or city"
               value="<?= htmlspecialchars($searchName) ?>">
      </div>
      <div class="col-md-2">
        <select name="city" class="form-select dark-select">
          <option value="all" <?= $filterCity==='all'?'selected':''; ?>>All locations</option>
          <?php foreach ($cities as $city => $_): ?>
            <option value="<?= htmlspecialchars($city) ?>"
              <?= $filterCity===$city?'selected':''; ?>>
              <?= htmlspecialchars($city) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <select name="category" class="form-select dark-select">
          <option value="all" <?= $filterCat==='all'?'selected':''; ?>>All categories</option>
          <?php foreach ($catOptions as $cat => $_): ?>
            <option value="<?= htmlspecialchars($cat) ?>"
              <?= $filterCat===$cat?'selected':''; ?>>
              <?= htmlspecialchars(ucfirst($cat)) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <select name="rating" class="form-select dark-select">
          <option value="all"    <?= $filterRating==='all'?'selected':''; ?>>All ratings</option>
          <option value="4plus"  <?= $filterRating==='4plus'?'selected':''; ?>>4★ & above</option>
        </select>
      </div>
      <div class="col-md-2 d-flex align-items-center">
        <div class="form-check small">
          <input class="form-check-input" type="checkbox" name="verified" id="filterVerified"
                 <?= $filterVerif?'checked':''; ?>>
          <label class="form-check-label" for="filterVerified">
            Verified only
          </label>
        </div>
      </div>
      <div class="col-md-1">
        <select name="sort" class="form-select dark-select">
          <option value="rating_desc"   <?= $sort==='rating_desc'?'selected':''; ?>>Top rated</option>
          <option value="products_desc" <?= $sort==='products_desc'?'selected':''; ?>>Most products</option>
          <option value="fulfill_desc"  <?= $sort==='fulfill_desc'?'selected':''; ?>>Best fulfilment</option>
          <option value="name_asc"      <?= $sort==='name_asc'?'selected':''; ?>>Name A–Z</option>
        </select>
      </div>
    </form>

    <?php if (!$filtered): ?>
      <p class="text-muted">
        No vendors found for these filters. Try changing location or category.
      </p>
    <?php else: ?>
      <div class="row g-4">
        <?php foreach ($filtered as $v):
          $vid   = $v['id'];
          $online = (crc32($vid) + date('Hi')) % 3 === 0; // simple pseudo “online” indicator
          $catLabel = $v['categories'] ? implode(' • ', array_map('ucfirst', $v['categories'])) : 'No products yet';
        ?>
          <div class="col-md-6 col-lg-4">
            <div class="product-card fade-up">
              <div class="product-body">
                <!-- Primary info -->
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <div class="vendor-card-header-name">
                    <?= htmlspecialchars($v['name']) ?>
                    <?php if ($v['verified']): ?>
                      <span class="badge bg-success ms-2">✔ Verified</span>
                    <?php elseif ($v['status']==='suspended'): ?>
                      <span class="badge bg-danger ms-2">Temporarily unavailable</span>
                    <?php endif; ?>
                  </div>
                </div>
                <div class="small mb-1">
                  <span class="text-warning">
                    ⭐ <?= number_format($v['rating'],1) ?>
                  </span>
                  <span class="text-muted"> (<?= $v['reviews'] ?> reviews)</span>
                </div>
                <div class="small mb-1">
                  📍 <?= htmlspecialchars($v['city']) ?>, India
                </div>
                <div class="small mb-2 text-muted">
                  💼 <?= htmlspecialchars($catLabel) ?>
                </div>

                <!-- Primary CTAs -->
                <div class="d-flex flex-wrap gap-2 mb-2">
                  <a href="catalog.php?vendor=<?= urlencode($vid) ?>"
                     class="btn btn-sm btn-outline-light rounded-pill btn-soft">
                    View store
                  </a>
                  <?php if ($online && $v['status']!=='suspended'): ?>
                    <a href="support.php#vendor-support"
                       class="btn btn-sm btn-outline-light rounded-pill btn-soft">
                      🟢 Chat seller
                    </a>
                  <?php else: ?>
                    <a href="support.php#vendor-support"
                       class="btn btn-sm btn-outline-light rounded-pill btn-soft">
                      ⚪ Message seller
                    </a>
                  <?php endif; ?>
                  <!-- Policy icon (tooltip-style text) -->
                  <span class="small text-muted ms-auto">
                    🛡 Standard platform policy
                  </span>
                </div>

                <!-- More info (collapsed) -->
                <a class="small text-muted" data-bs-toggle="collapse"
                   href="#vendorMore-<?= htmlspecialchars($vid) ?>" role="button"
                   aria-expanded="false" aria-controls="vendorMore-<?= htmlspecialchars($vid) ?>">
                  ▼ More info
                </a>
                <div class="collapse mt-2" id="vendorMore-<?= htmlspecialchars($vid) ?>">
                  <ul class="small mb-0">
                    <li><?= $v['product_count'] ?> product(s)</li>
                    <li><?= $v['fulfill_rate'] ?>% order fulfilment · Cancel rate <?= $v['cancel_rate'] ?>%</li>
                    <li>Vendor since <?= $v['since_year'] ?></li>
                    <li><?= $v['order_count'] ?>+ orders completed</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>