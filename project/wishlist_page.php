<?php require_once 'data.php';
require_login('buyer');

$items = wishlist_for_current();
$user  = current_user();
$cid   = $user['id'] ?? null;

// Filters / sorting
$search = trim($_GET['search'] ?? '');
$sort   = $_GET['sort'] ?? 'date_desc'; // date_desc, date_asc, price_desc, price_asc, rating_desc
$category = $_GET['category'] ?? 'all';

// Simple derived meta for each wishlist item (price history, discount, stock hint)
$enriched = [];
foreach ($items as $p) {
    $current = $p['price'];
    // Use 3-month history as "original" price for discount calculation
    $original = $p['history']['3m'] ?? $current;
    $discount = 0;
    if ($original > $current) {
        $discount = (int) round(100 * ($original - $current) / $original);
    }
    $priceDrop = $original - $current;

    // Fake availability & urgency (demo)
    $inStock = true;
    $left    = rand(2, 12); // “Only X left”
    $sellingFast = $left <= 3;

    $p['_original']   = $original;
    $p['_discount']   = $discount;
    $p['_price_drop'] = $priceDrop;
    $p['_in_stock']   = $inStock;
    $p['_left']       = $left;
    $p['_fast']       = $sellingFast;

    $enriched[] = $p;
}

// Apply filters
$filtered = array_filter($enriched, function($p) use ($search, $category) {
    if ($category !== 'all' && $p['category'] !== $category) return false;
    if ($search === '') return true;
    $q = mb_strtolower($search);
    return mb_stripos($p['name'], $q) !== false
        || mb_stripos($p['brand'], $q) !== false
        || mb_stripos($p['vendor'], $q) !== false;
});

// Sorting
usort($filtered, function($a,$b) use ($sort) {
    if ($sort === 'price_asc')  return $a['price']  <=> $b['price'];
    if ($sort === 'price_desc') return $b['price']  <=> $a['price'];
    if ($sort === 'rating_desc')return $b['rating'] <=> $a['rating'];
    if ($sort === 'date_asc')   return $a['wishlist_added_at'] <=> $b['wishlist_added_at'];
    // default date_desc
    return $b['wishlist_added_at'] <=> $a['wishlist_added_at'];
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Your wishlist | ElectroHub</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="style.css">
  <style>
    .wishlist-card {
      border-radius: 1rem;
      border: 1px solid var(--border-subtle);
      background: var(--bg-card);
      padding: 0.9rem 1rem;
      margin-bottom: 1rem;
    }
    .wishlist-card:hover {
      box-shadow: 0 16px 40px rgba(0,0,0,.7);
    }
    .wishlist-thumb {
      width: 72px;
      height: 72px;
      border-radius: .6rem;
      overflow: hidden;
      background:#000;
      margin-right:.9rem;
    }
    .wishlist-thumb img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
  </style>
</head>
<body>
<?php include 'nav.php'; ?>

<section class="catalog-section py-4">
  <div class="container">
    <h2 class="section-title mb-1">Your wishlist</h2>
    <p class="section-subtitle mb-3">
      Save products you love and buy them when the price or timing is right.
    </p>

    <!-- Filters / search / sort -->
    <form class="row g-2 mb-3" method="get">
      <div class="col-md-4">
        <input type="text" name="search" class="form-control dark-input"
               placeholder="Search in wishlist by name, brand or vendor"
               value="<?= htmlspecialchars($search) ?>">
      </div>
      <div class="col-md-3">
        <select name="category" class="form-select dark-select">
          <option value="all"      <?= $category==='all'?'selected':''; ?>>All categories</option>
          <option value="laptop"   <?= $category==='laptop'?'selected':''; ?>>Laptops</option>
          <option value="phone"    <?= $category==='phone'?'selected':''; ?>>Mobiles</option>
          <option value="accessory"<?= $category==='accessory'?'selected':''; ?>>Accessories</option>
        </select>
      </div>
      <div class="col-md-3">
        <select name="sort" class="form-select dark-select">
          <option value="date_desc"   <?= $sort==='date_desc'?'selected':''; ?>>Recently added</option>
          <option value="date_asc"    <?= $sort==='date_asc'?'selected':''; ?>>Oldest first</option>
          <option value="price_desc"  <?= $sort==='price_desc'?'selected':''; ?>>Price high → low</option>
          <option value="price_asc"   <?= $sort==='price_asc'?'selected':''; ?>>Price low → high</option>
          <option value="rating_desc" <?= $sort==='rating_desc'?'selected':''; ?>>Rating high → low</option>
        </select>
      </div>
      <div class="col-md-2">
        <button class="btn btn-primary w-100 rounded-pill btn-soft" type="submit">
          Filter
        </button>
      </div>
    </form>

    <?php if (!$filtered): ?>
      <p class="text-muted mb-3">
        Your wishlist is empty. Save products you like and we’ll keep them here for you.
      </p>
      <a href="catalog.php" class="btn btn-primary rounded-pill btn-soft">Browse products</a>
    <?php else: ?>

      <!-- Bulk action bar (MVP: just Add all to cart) -->
      <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="small text-muted">
          <?= count($filtered) ?> item(s) in your wishlist.
        </span>
        <form method="post" action="cart.php" class="d-inline">
          <!-- simple: add first N items; for full bulk you’d loop via JS -->
          <?php foreach ($filtered as $p): ?>
            <input type="hidden" name="product_ids[]" value="<?= $p['id'] ?>">
          <?php endforeach; ?>
          <!-- you can handle product_ids[] in cart.php later if you want bulk add -->
          <button class="btn btn-sm btn-outline-light rounded-pill btn-soft" type="submit">
            Add all to cart (demo)
          </button>
        </form>
      </div>

      <?php foreach ($filtered as $p):
        $addedAt   = $p['wishlist_added_at'] ?? time();
        $dateStr   = date('d M Y', $addedAt);
        $original  = $p['_original'];
        $discount  = $p['_discount'];
        $drop      = $p['_price_drop'];
        $inStock   = $p['_in_stock'];
        $left      = $p['_left'];
        $fast      = $p['_fast'];
      ?>
        <div class="wishlist-card fade-up">
          <div class="d-flex">
            <div class="wishlist-thumb">
              <img src="<?= htmlspecialchars($p['image']) ?>" alt="Product image">
            </div>
            <div class="flex-grow-1">
              <div class="d-flex justify-content-between">
                <div>
                  <div class="fw-semibold"><?= htmlspecialchars($p['name']) ?></div>
                  <div class="small text-muted">
                    <?= htmlspecialchars($p['brand']) ?> • Sold by <?= htmlspecialchars($p['vendor']) ?>
                  </div>
                  <div class="small text-muted">
                    ⭐ <?= number_format($p['rating'],1) ?>
                    · Added on <?= $dateStr ?>
                  </div>
                </div>
                <div class="text-end">
                  <div class="fw-bold">₹<?= number_format($p['price']) ?></div>
                  <?php if ($discount > 0): ?>
                    <div class="small">
                      <span class="text-muted text-decoration-line-through">
                        ₹<?= number_format($original) ?>
                      </span>
                      <span class="text-success ms-1"><?= $discount ?>% OFF</span>
                    </div>
                    <?php if ($drop > 0): ?>
                      <div class="small text-success">
                        ⬇ Price dropped ₹<?= number_format($drop) ?> since added
                      </div>
                    <?php endif; ?>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Availability / urgency -->
              <div class="small mt-1">
                <?php if ($inStock): ?>
                  <span class="text-success">✔ In stock</span>
                  <?php if ($fast): ?>
                    <span class="text-warning ms-2">Only <?= $left ?> left · Selling fast</span>
                  <?php else: ?>
                    <span class="text-muted ms-2">Limited stock</span>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="text-danger">✖ Out of stock (demo)</span>
                <?php endif; ?>
              </div>

              <!-- Action buttons -->
              <div class="d-flex flex-wrap gap-2 mt-2">
                <form method="post" action="cart.php" class="d-inline">
                  <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                  <button class="btn btn-sm btn-primary rounded-pill btn-soft" type="submit">
                    🛒 Add to cart
                  </button>
                </form>

                <form method="post" action="wishlist.php" class="d-inline">
                  <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                  <input type="hidden" name="action" value="remove">
                  <button class="btn btn-sm btn-outline-light rounded-pill btn-soft" type="submit">
                    ❤️ Remove
                  </button>
                </form>

                <a href="product.php?id=<?= $p['id'] ?>"
                   class="btn btn-sm btn-outline-light rounded-pill btn-soft">
                  🔍 View details
                </a>

                <!-- Compare toggle (MVP: just a checkbox; full compare later) -->
                <label class="small d-flex align-items-center ms-auto">
                  <input type="checkbox" class="form-check-input me-1 wishlist-compare"
                         value="<?= htmlspecialchars($p['name']) ?>">
                  Compare
                </label>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>

      <!-- Compare selected demo button -->
      <button class="btn btn-sm btn-outline-light rounded-pill btn-soft mt-2"
              type="button" onclick="compareSelected()">
        🔁 Compare selected (demo)
      </button>

    <?php endif; ?>
  </div>
</section>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Simple demo compare: shows alert with selected product names
function compareSelected() {
  const boxes = document.querySelectorAll('.wishlist-compare:checked');
  if (!boxes.length) {
    alert('Select products to compare.');
    return;
  }
  const names = Array.from(boxes).map(b => b.value);
  alert('Compare these products:\n\n' + names.join('\n'));
}
</script>
</body>
</html>