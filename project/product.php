<?php require_once 'data.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = get_product($id);
if (!$product) {
  header('Location: catalog.php');
  exit;
}

record_visit($id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? 'Anonymous');
  $rating = (int)($_POST['rating'] ?? 5);
  $text = trim($_POST['review'] ?? '');
  if ($text !== '') {
    $_SESSION['reviews'][$id][] = [
      'user' => $name,
      'rating' => max(1, min(5, $rating)),
      'text' => $text,
      'time' => date('d M Y H:i')
    ];
  }
}

$reviews = $_SESSION['reviews'][$id] ?? [];
[$gst, $total] = gst_breakup($product['price']);

$all = all_products();
$fbt = [];
foreach ($all as $p2) {
  if ($p2['id'] !== $product['id'] && $p2['category'] === $product['category']) {
    $fbt[] = $p2;
    if (count($fbt) === 3) break;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($product['name']) ?> | ElectroHub</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'nav.php'; ?>

<section class="catalog-section py-4">
  <div class="container">
    <div class="row gy-4">
      <div class="col-lg-5 fade-up">
        <div class="product-image-large">
          <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
        </div>
      </div>
      <div class="col-lg-7 fade-up">
        <span class="product-category text-uppercase">
          <?= htmlspecialchars($product['category']) ?> • <?= htmlspecialchars($product['brand']) ?>
        </span>
        <h2 class="section-title mt-1 mb-2"><?= htmlspecialchars($product['name']) ?></h2>
        <p class="product-vendor mb-2">
          Sold by <strong><?= htmlspecialchars($product['vendor']) ?></strong>
        </p>

        <p class="mb-1">
          <span class="product-rating">★ <?= number_format($product['rating'],1) ?></span>
          <span class="text-muted ms-2">(demo rating)</span>
        </p>

        <div class="vendor-highlight mb-3">
          <p class="mb-1">Base price: ₹<?= number_format($product['price']) ?></p>
          <p class="mb-1">GST @ 18%: ₹<?= number_format($gst) ?></p>
          <p class="mb-0 fw-bold">Total: ₹<?= number_format($total) ?></p>
        </div>

        <div class="vendor-highlight mb-3">
          <h4 class="mb-2">Price history</h4>
          <p class="mb-1">3 months ago: ₹<?= number_format($product['history']['3m']) ?></p>
          <p class="mb-1">6 months ago: ₹<?= number_format($product['history']['6m']) ?></p>
          <p class="mb-0">12 months ago: ₹<?= number_format($product['history']['12m']) ?></p>
        </div>

        <div class="vendor-highlight mb-3">
          <h4 class="mb-2">Bank & UPI offers (demo)</h4>
          <p class="mb-1">HDFC Credit Card: 10% instant discount up to ₹1,500</p>
          <p class="mb-1">SBI Debit Card: 5% cashback up to ₹750</p>
          <p class="mb-0">Paytm UPI: Flat ₹100 off on orders above ₹5,000</p>
        </div>

        <?php if ($fbt): ?>
          <div class="vendor-highlight mb-3">
            <h4 class="mb-2">Frequently bought together</h4>
            <?php foreach ($fbt as $p2): ?>
              <p class="mb-1">
                <a href="product.php?id=<?= $p2['id'] ?>">
                  <?= htmlspecialchars($p2['name']) ?>
                </a> – ₹<?= number_format($p2['price']) ?>
              </p>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <form method="post" action="cart.php" class="d-inline">
          <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
          <button class="btn btn-primary rounded-pill btn-soft" type="submit">
            Add to cart
          </button>
        </form>

        <form method="post" action="buy_now.php" class="d-inline">
          <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
          <button class="btn btn-outline-light rounded-pill ms-2 btn-soft" type="submit">
            Buy now
          </button>
        </form>

        <form method="post" action="wishlist.php" class="d-inline">
          <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
          <button class="btn btn-outline-light rounded-pill ms-2 btn-soft" type="submit">
            Add to wishlist
          </button>
        </form>

        <a href="catalog.php" class="btn btn-outline-light rounded-pill ms-2 btn-soft">
          Back to catalog
        </a>
      </div>
    </div>

    <div class="row mt-4 gy-4">
      <div class="col-lg-6 fade-up">
        <h3 class="section-title mb-2" style="font-size:1.3rem;">Customer reviews</h3>
        <?php if (!$reviews): ?>
          <p class="text-muted">No reviews yet. Be the first to review this product.</p>
        <?php else: ?>
          <?php foreach ($reviews as $r): ?>
            <div class="vendor-highlight mb-2">
              <strong><?= htmlspecialchars($r['user']) ?></strong>
              <span class="product-rating ms-2">★ <?= $r['rating'] ?></span>
              <p class="mb-1 mt-1"><?= nl2br(htmlspecialchars($r['text'])) ?></p>
              <span class="text-muted small"><?= htmlspecialchars($r['time']) ?></span>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="col-lg-6 fade-up">
        <h3 class="section-title mb-2" style="font-size:1.3rem;">Write a review</h3>
        <form method="post">
          <div class="mb-2">
            <label class="form-label">Your name</label>
            <input type="text" name="name" class="form-control dark-input" placeholder="Optional">
          </div>
          <div class="mb-2">
            <label class="form-label">Rating (1–5)</label>
            <select name="rating" class="form-select dark-select">
              <option>5</option><option>4</option><option>3</option><option>2</option><option>1</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Your review</label>
            <textarea name="review" rows="4" class="form-control dark-input" placeholder="Share your experience"></textarea>
          </div>
          <button type="submit" class="btn btn-primary rounded-pill btn-soft">
            Submit review
          </button>
        </form>
      </div>
    </div>
  </div>
</section>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>