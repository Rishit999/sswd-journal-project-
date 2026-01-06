<?php require_once 'data.php';

$allProducts = all_products();

// Optional vendor filter from query (?vendor=V001)
$vendorFilter = $_GET['vendor'] ?? 'all';
$vendorNameFilter = null;
if ($vendorFilter !== 'all') {
    $allVendors = all_vendors();
    if (isset($allVendors[$vendorFilter])) {
        $vendorNameFilter = $allVendors[$vendorFilter]['name'];
    }
}

// Other filters
$selectedCategory = $_GET['category'] ?? 'all';
$search           = trim($_GET['search'] ?? '');
$brand            = $_GET['brand'] ?? 'all';
$sort             = $_GET['sort'] ?? 'none';

// Base filtering
$filtered = array_filter($allProducts, function ($p) use ($selectedCategory, $search, $brand, $vendorFilter) {
    if ($vendorFilter !== 'all' && $p['vendor_id'] !== $vendorFilter) return false;

    $catOk = $selectedCategory === 'all' || $p['category'] === $selectedCategory;
    if (!$catOk) return false;

    $brandOk = $brand === 'all' || $p['brand'] === $brand;
    if (!$brandOk) return false;

    if ($search !== '') {
        $q = mb_strtolower($search);
        $nameOk   = mb_stripos($p['name'],   $q) !== false;
        $vendorOk = mb_stripos($p['vendor'], $q) !== false;
        if (!$nameOk && !$vendorOk) return false;
    }

    return true;
});

// Sorting
if ($sort === 'price_asc') {
    usort($filtered, fn($a,$b) => $a['price'] <=> $b['price']);
} elseif ($sort === 'price_desc') {
    usort($filtered, fn($a,$b) => $b['price'] <=> $a['price']);
}

// For brand dropdown options
$laptopBrands = ['Dell','HP','Lenovo','ASUS','Acer','Apple','MSI'];
$phoneBrands  = ['Samsung','Apple','OnePlus','Xiaomi','Realme','Vivo','Oppo','Nothing'];
$accBrands    = ['Logitech','Sony','Boat','JBL','Dell','HP'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Catalog | ElectroHub</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'nav.php'; ?>

<section class="catalog-section py-4">
  <div class="container catalog-container">
    <h2 class="section-title mb-1">Explore electronics</h2>
    <p class="section-subtitle mb-1">
      Filter by category, brand and sort by price.
    </p>
    <?php if ($vendorNameFilter): ?>
      <p class="section-subtitle mb-3">
        Showing products from vendor <strong><?= htmlspecialchars($vendorNameFilter) ?></strong>.
      </p>
    <?php else: ?>
      <p class="section-subtitle mb-3">
        Showing products from all vendors.
      </p>
    <?php endif; ?>

    <!-- Filters under title -->
    <form class="catalog-filters d-flex flex-wrap gap-2 mb-4" method="get" id="filterForm">
      <?php if ($vendorFilter !== 'all'): ?>
        <input type="hidden" name="vendor" value="<?= htmlspecialchars($vendorFilter) ?>">
      <?php endif; ?>

      <select name="category" id="categorySelect"
              class="form-select form-select-sm dark-select">
        <option value="all"      <?= $selectedCategory==='all'?'selected':'' ?>>All categories</option>
        <option value="laptop"   <?= $selectedCategory==='laptop'?'selected':'' ?>>Laptops</option>
        <option value="phone"    <?= $selectedCategory==='phone'?'selected':'' ?>>Mobiles</option>
        <option value="accessory"<?= $selectedCategory==='accessory'?'selected':'' ?>>Accessories</option>
      </select>

      <select name="brand" id="brandSelect"
              class="form-select form-select-sm dark-select">
        <option value="all" <?= $brand==='all'?'selected':'' ?>>All brands</option>
        <!-- JS will repopulate based on category, this is a fallback -->
      </select>

      <select name="sort" class="form-select form-select-sm dark-select">
        <option value="none"        <?= $sort==='none'?'selected':'' ?>>Sort by</option>
        <option value="price_asc"   <?= $sort==='price_asc'?'selected':'' ?>>Price: Low to High</option>
        <option value="price_desc"  <?= $sort==='price_desc'?'selected':'' ?>>Price: High to Low</option>
      </select>

      <input type="text" name="search"
             class="form-control form-control-sm dark-input"
             placeholder="Search by name or vendor"
             value="<?= htmlspecialchars($search) ?>">

      <button type="submit" class="btn btn-sm btn-primary rounded-pill btn-soft">
        Apply
      </button>
    </form>

    <div class="row g-4">
      <?php if (!$filtered): ?>
        <div class="col-12 text-center text-muted">
          No products match your filters.
        </div>
      <?php else: ?>
        <?php foreach ($filtered as $p): ?>
          <div class="col-md-4 col-lg-3">
            <div class="product-card h-100 fade-up">
              <a href="product.php?id=<?= $p['id'] ?>" class="product-link">
                <div class="product-image-wrapper">
                  <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
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
                    <span class="product-rating">★ <?= number_format($p['rating'], 1) ?></span>
                  </div>
                </div>
              </a>
              <form method="post" action="cart.php" class="p-3 pt-0">
                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                <button class="btn btn-sm btn-outline-light w-100 rounded-pill btn-soft" type="submit">
                  Add to cart
                </button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Brand dropdown logic – only show relevant brands by category
const brandsByCategory = {
  laptop: <?= json_encode($laptopBrands) ?>,
  phone:  <?= json_encode($phoneBrands) ?>,
  accessory: <?= json_encode($accBrands) ?>
};

const categorySelect = document.getElementById('categorySelect');
const brandSelect    = document.getElementById('brandSelect');
const currentBrand   = "<?= addslashes($brand) ?>";

function refreshBrandOptions() {
  const cat = categorySelect.value;
  brandSelect.innerHTML = "";
  const optAll = new Option("All brands", "all");
  brandSelect.appendChild(optAll);

  let list = [];
  if (cat === "all") {
    const set = new Set();
    Object.values(brandsByCategory).forEach(arr => arr.forEach(b => set.add(b)));
    list = Array.from(set);
  } else if (brandsByCategory[cat]) {
    list = brandsByCategory[cat];
  }
  list.forEach(b => {
    const opt = new Option(b, b);
    if (b === currentBrand) opt.selected = true;
    brandSelect.appendChild(opt);
  });
}

refreshBrandOptions();
categorySelect.addEventListener('change', () => {
  brandSelect.value = 'all';
  refreshBrandOptions();
});
</script>
</body>
</html>