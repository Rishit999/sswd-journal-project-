<?php require_once 'data.php';

$allProducts = all_products();
$vendors     = all_vendors();
$user        = current_user();

/* ---- Trending deals: 4 lowest-price products ---- */
$trending = $allProducts;
usort($trending, fn($a,$b) => $a['price'] <=> $b['price']);
$trending = array_slice($trending, 0, 4);

/* ---- Top vendors by simple order revenue (for home cards) ---- */
$orders = $_SESSION['orders'] ?? [];
$vendorRevenue = [];
foreach ($orders as $o) {
    $vendorRevenue[$o['vendor_name']] = ($vendorRevenue[$o['vendor_name']] ?? 0) + $o['amount'];
}
arsort($vendorRevenue);
$topVendorNames = array_slice(array_keys($vendorRevenue), 0, 3);
if (!$topVendorNames) {
    // fall back to first vendors if there are no orders yet
    $topVendorNames = array_slice(array_column($vendors, 'name'), 0, 3);
}

/* ---- Build a lightweight product list for search suggestions ---- */
$searchIndex = [];
foreach ($allProducts as $p) {
    $searchIndex[] = [
        'id'       => $p['id'],
        'name'     => $p['name'],
        'brand'    => $p['brand'],
        'category' => $p['category'],
    ];
}

/* ---- Stats ---- */
$vendorsCount   = count($vendors);
$storesCount    = $vendorsCount;
$productsCount  = count($allProducts);
$activeUsers    = rand(24, 132);

/* personalisation helpers */
$notes = notifications_for_current();
$freq  = frequently_visited_for_current();
$wish  = wishlist_for_current();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>ElectroHub | Home</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="style.css">
  <style>
    .search-suggestions {
      position: absolute;
      left: 0;
      right: 0;
      top: 100%;
      z-index: 20;
      background: #15171d;
      border: 1px solid #2a2d36;
      border-top: none;
      border-radius: 0 0 .75rem .75rem;
      max-height: 220px;
      overflow-y: auto;
      display: none;
    }
    .search-suggestions-item {
      padding: .4rem .75rem;
      font-size: .85rem;
      cursor: pointer;
    }
    .search-suggestions-item:hover {
      background: #181b24;
    }
    .filter-shortcuts .chip {
      cursor: pointer;
      text-decoration: none;
    }
    .category-card {
      border-radius: 1rem;
      overflow: hidden;
      border: 1px solid var(--border-subtle);
      background: var(--bg-card);
      transition: transform .2s, box-shadow .2s;
    }
    .category-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 16px 40px rgba(0,0,0,.7);
    }
    .category-card img {
      width: 100%;
      height: 140px;
      object-fit: cover;
    }
    .category-card-body {
      padding: .75rem .9rem 1rem;
    }
  </style>
</head>
<body>
<?php include 'nav.php'; ?>

<!-- HERO -->
<section class="hero-section">
  <div class="container">
    <div class="row gy-4 align-items-center">
      <div class="col-lg-6 hero-left fade-up">
        <div class="d-inline-flex align-items-center mb-3 gap-2">
          <span class="badge-pill">Trusted marketplace</span>
          <span class="hero-small-text">Electronics from verified Indian vendors</span>
        </div>
        <h1 class="hero-title mb-3">
          Compare prices.<br>Buy electronics with confidence.
        </h1>
        <p class="hero-subtitle mb-4">
          Compare prices from trusted Indian sellers, track price history and read real reviews
          before you buy laptops, mobiles, and accessories.
        </p>

        <!-- Primary & secondary CTAs -->
        <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
          <a href="catalog.php?sort=price_desc" class="btn btn-primary btn-lg rounded-pill btn-soft">
            Explore deals
          </a>
          <a href="catalog.php" class="btn btn-outline-light btn-lg rounded-pill btn-soft">
            Compare prices
          </a>
        </div>

        <!-- Category chips -->
        <div class="hero-chips d-flex flex-wrap gap-2 mt-2">
          <a href="catalog.php?category=laptop" class="chip active">Laptops</a>
          <a href="catalog.php?category=phone" class="chip">Mobiles</a>
          <a href="catalog.php?category=accessory" class="chip">Accessories</a>
        </div>

        <!-- Search with suggestions -->
        <div class="mt-3 position-relative" style="max-width:480px;">
          <form method="get" action="catalog.php" id="homeSearchForm">
            <div class="input-group">
              <input type="text" name="search" id="homeSearchInput"
                     class="form-control dark-input"
                     autocomplete="off"
                     placeholder="Search laptops, mobiles, brands…">
              <button class="btn btn-primary btn-soft" type="submit">Search</button>
            </div>
          </form>
          <div id="searchSuggestions" class="search-suggestions"></div>
        </div>

        <!-- Filter shortcuts under search -->
        <div class="filter-shortcuts d-flex flex-wrap gap-2 mt-2">
          <a href="catalog.php?sort=price_asc" class="chip">Under best price</a>
          <a href="catalog.php?category=laptop&sort=price_asc" class="chip">Budget laptops</a>
          <a href="catalog.php?category=phone&sort=price_desc" class="chip">Top-rated phones</a>
        </div>
      </div>

      <!-- RIGHT: Live marketplace activity -->
      <div class="col-lg-6 hero-right-wrap fade-up">
        <div class="hero-right">
          <div class="hero-right-header">
            <span>Live marketplace activity</span>
            <span id="activityTimestamp">Updating…</span>
          </div>
          <div class="hero-right-body">
            <div class="placeholder-board">
              <ul id="activityList" class="mb-0" style="list-style:none;padding-left:0;font-size:.85rem;">
                <!-- JS will inject activity items -->
              </ul>
            </div>
          </div>
        </div>
        <p class="board-note mt-2">
          See what’s happening right now: price updates, recent orders and comparisons.
        </p>
      </div>
    </div>
  </div>
</section>

<!-- TRUST STATS -->
<section class="catalog-section py-4">
  <div class="container">
    <div class="row g-3 mb-3">
      <div class="col-md-3">
        <div class="stat-card fade-up">
          <h3><?= $vendorsCount ?></h3>
          <p>Verified vendors across India</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card fade-up">
          <h3><?= $storesCount ?></h3>
          <p>Active online stores</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card fade-up">
          <h3><?= $productsCount ?></h3>
          <p>Electronics listed</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card fade-up">
          <h3 id="activeUsers"><?= $activeUsers ?></h3>
          <p>Users browsing right now</p>
        </div>
      </div>
    </div>

    <!-- Trust badges row -->
    <div class="row g-3">
      <div class="col-md-3">
        <div class="stat-card fade-up">
          <h3>⭐ 4.6/5</h3>
          <p>Average rating </p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card fade-up">
          <h3>🛡</h3>
          <p>Secure payments (UPI, cards)</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card fade-up">
          <h3>✔</h3>
          <p>Verified sellers only</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card fade-up">
          <h3>🔄</h3>
          <p>Simple returns policy </p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CATEGORY GRID -->
<section class="catalog-section py-4">
  <div class="container">
    <h3 class="section-title mb-3">Shop by category</h3>
    <div class="row g-3">
      <div class="col-md-4">
        <a href="catalog.php?category=laptop" class="category-card d-block text-decoration-none text-white fade-up">
          <img loading="lazy"
               src="https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=900&q=80"
               alt="Laptops">
          <div class="category-card-body">
            <h5 class="mb-1">Laptops</h5>
            <p class="small mb-0 text-muted">From ₹68,000 · work, study & gaming</p>
          </div>
        </a>
      </div>
      <div class="col-md-4">
        <a href="catalog.php?category=phone" class="category-card d-block text-decoration-none text-white fade-up">
          <img loading="lazy"
               src="https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=900&q=80"
               alt="Mobiles">
          <div class="category-card-body">
            <h5 class="mb-1">Mobiles</h5>
            <p class="small mb-0 text-muted">5G smartphones from top brands</p>
          </div>
        </a>
      </div>
      <div class="col-md-4">
        <a href="catalog.php?category=accessory" class="category-card d-block text-decoration-none text-white fade-up">
          <img loading="lazy"
               src="images/accessories.jpg"
               alt="Accessories">
          <div class="category-card-body">
            <h5 class="mb-1">Accessories</h5>
            <p class="small mb-0 text-muted">Headphones, mice, chargers & more</p>
          </div>
        </a>
      </div>
    </div>
  </div>
</section>

<!-- TRENDING DEALS -->
<section class="catalog-section py-4">
  <div class="container">
    <h3 class="section-title mb-3">Trending deals</h3>
    <div class="row g-4">
      <?php foreach ($trending as $p): ?>
        <div class="col-md-4 col-lg-3">
          <div class="product-card h-100 fade-up">
            <a href="product.php?id=<?= $p['id'] ?>" class="product-link">
              <div class="product-image-wrapper">
                <img loading="lazy"
                     src="<?= htmlspecialchars($p['image']) ?>"
                     alt="<?= htmlspecialchars($p['name']) ?>">
              </div>
              <div class="product-body">
                <span class="product-category text-uppercase">
                  <?= htmlspecialchars($p['category']) ?> • <?= htmlspecialchars($p['brand']) ?>
                </span>
                <h5 class="product-name"><?= htmlspecialchars($p['name']) ?></h5>
                <p class="product-vendor mb-1">
                  Sold by <strong><?= htmlspecialchars($p['vendor']) ?></strong>
                </p>
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="product-price">₹<?= number_format($p['price']) ?></span>
                  <span class="product-rating">★ <?= number_format($p['rating'],1) ?></span>
                </div>
              </div>
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- TOP VENDORS & POPULAR COMPARISONS -->
<section class="catalog-section py-4">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-6">
        <h3 class="section-title mb-3">Top vendors</h3>
        <?php if (!$topVendorNames): ?>
          <p class="text-muted small">No vendor data yet.</p>
        <?php else: ?>
          <?php foreach ($topVendorNames as $vname): ?>
            <div class="vendor-highlight mb-2 fade-up">
              <strong><?= htmlspecialchars($vname) ?></strong><br>
              <span class="small text-muted">
                ⭐ High-rated · Fast dispatch 
              </span><br>
              <a href="vendors.php" class="small">View store →</a>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
      <div class="col-lg-6">
        <h3 class="section-title mb-3">Popular comparisons</h3>
        <div class="vendor-highlight fade-up mb-2">
          <strong>MacBook Air M2 vs HP Pavilion 14</strong><br>
          <span class="small text-muted">
            Compare battery life, weight and price.
          </span><br>
          <a href="catalog.php?search=MacBook+Air+M2" class="small">View both →</a>
        </div>
        <div class="vendor-highlight fade-up mb-2">
          <strong>iPhone 15 Pro vs Samsung Galaxy S24</strong><br>
          <span class="small text-muted">
            Android vs iOS flagship showdown.
          </span><br>
          <a href="catalog.php?search=iPhone+15+Pro" class="small">Compare now →</a>
        </div>
        <div class="vendor-highlight fade-up">
          <strong>Noise‑cancelling headphones</strong><br>
          <span class="small text-muted">
            Sony WH‑1000XM5 and similar options.
          </span><br>
          <a href="images/sonyheadphone.jpg" class="small">See options →</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- PERSONALISATION: NOTIFICATIONS + RECENT & WISHLIST -->
<section class="catalog-section py-4" id="wishlist-section">
  <div class="container">
    <?php if ($notes): ?>
      <div class="vendor-highlight mb-4 fade-up">
        <h4 class="mb-2">Notifications</h4>
        <ul class="mb-0">
          <?php foreach (array_reverse($notes) as $msg): ?>
            <li><?= htmlspecialchars($msg) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if ($freq): ?>
      <h3 class="section-title mb-3">Continue browsing</h3>
      <div class="row g-4 mb-4">
        <?php foreach ($freq as $p): ?>
          <div class="col-md-4 col-lg-3">
            <div class="product-card h-100 fade-up">
              <a href="product.php?id=<?= $p['id'] ?>" class="product-link">
                <div class="product-image-wrapper">
                  <img loading="lazy"
                       src="<?= htmlspecialchars($p['image']) ?>"
                       alt="<?= htmlspecialchars($p['name']) ?>">
                </div>
                <div class="product-body">
                  <span class="product-category text-uppercase">
                    <?= htmlspecialchars($p['category']) ?> • <?= htmlspecialchars($p['brand']) ?>
                  </span>
                  <h5 class="product-name"><?= htmlspecialchars($p['name']) ?></h5>
                  <p class="product-vendor mb-1">
                    Sold by <strong><?= htmlspecialchars($p['vendor']) ?></strong>
                  </p>
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="product-price">₹<?= number_format($p['price']) ?></span>
                    <span class="product-rating">★ <?= number_format($p['rating'],1) ?></span>
                  </div>
                </div>
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($wish): ?>
      <h3 class="section-title mb-3">Your wishlist</h3>
      <div class="row g-4">
        <?php foreach ($wish as $p): ?>
          <div class="col-md-4 col-lg-3">
            <div class="product-card h-100 fade-up">
              <a href="product.php?id=<?= $p['id'] ?>" class="product-link">
                <div class="product-image-wrapper">
                  <img loading="lazy"
                       src="<?= htmlspecialchars($p['image']) ?>"
                       alt="<?= htmlspecialchars($p['name']) ?>">
                </div>
                <div class="product-body">
                  <span class="product-category text-uppercase">
                    <?= htmlspecialchars($p['category']) ?> • <?= htmlspecialchars($p['brand']) ?>
                  </span>
                  <h5 class="product-name"><?= htmlspecialchars($p['name']) ?></h5>
                  <p class="product-vendor mb-1">
                    Sold by <strong><?= htmlspecialchars($p['vendor']) ?></strong>
                  </p>
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="product-price">₹<?= number_format($p['price']) ?></span>
                    <span class="product-rating">★ <?= number_format($p['rating'],1) ?></span>
                  </div>
                </div>
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php elseif (!$freq && !$notes): ?>
      <p class="text-muted small">
        Sign in, browse a few products, and we’ll personalise this section for you.
      </p>
    <?php endif; ?>
  </div>
</section>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// -------- Live activity panel --------
const activityMessages = [
  "💻 Dell XPS 15 viewed by a user in Mumbai",
  "📱 iPhone 15 Pro added to cart in Bengaluru",
  "🎧 Sony WH‑1000XM5 price updated by SoundLab",
  "📦 Order placed for Redmi Note 13 Pro",
  "🏷 Flash sale applied on laptops ",
  "⭐ New review on Samsung Galaxy S24 (4★)"
];
function renderActivities() {
  const list = document.getElementById('activityList');
  list.innerHTML = "";
  const shuffled = [...activityMessages].sort(() => 0.5 - Math.random());
  shuffled.slice(0,4).forEach(msg => {
    const li = document.createElement('li');
    li.textContent = msg;
    li.className = "mb-1";
    list.appendChild(li);
  });
}
function updateActivityTimestamp() {
  document.getElementById('activityTimestamp').textContent =
    'Updated: ' + new Date().toLocaleTimeString('en-IN');
}
renderActivities();
updateActivityTimestamp();
setInterval(() => { renderActivities(); updateActivityTimestamp(); }, 5000);

// -------- Active users counter --------
setInterval(() => {
  const el = document.getElementById('activeUsers');
  let n = parseInt(el.textContent, 10);
  n += Math.floor(Math.random() * 5) - 2;
  if (n < 10) n = 10;
  el.textContent = n;
}, 3000);

// -------- Search suggestions --------
const searchData = <?= json_encode($searchIndex, JSON_UNESCAPED_UNICODE) ?>;
const input = document.getElementById('homeSearchInput');
const suggBox = document.getElementById('searchSuggestions');

input.addEventListener('input', () => {
  const q = input.value.trim().toLowerCase();
  if (!q) {
    suggBox.style.display = 'none';
    suggBox.innerHTML = '';
    return;
  }
  const matches = searchData.filter(p =>
    p.name.toLowerCase().includes(q) ||
    p.brand.toLowerCase().includes(q) ||
    p.category.toLowerCase().includes(q)
  ).slice(0, 8);

  if (!matches.length) {
    suggBox.style.display = 'none';
    suggBox.innerHTML = '';
    return;
  }

  suggBox.innerHTML = matches.map(m =>
    `<div class="search-suggestions-item" data-name="${m.name}">
       ${m.name} <span class="text-muted">· ${m.brand} · ${m.category}</span>
     </div>`
  ).join('');
  suggBox.style.display = 'block';
});

suggBox.addEventListener('click', (e) => {
  const item = e.target.closest('.search-suggestions-item');
  if (!item) return;
  input.value = item.getAttribute('data-name');
  suggBox.style.display = 'none';
  suggBox.innerHTML = '';
});

// hide suggestions when clicking outside
document.addEventListener('click', (e) => {
  if (!suggBox.contains(e.target) && e.target !== input) {
    suggBox.style.display = 'none';
  }
});
</script>
</body>
</html>