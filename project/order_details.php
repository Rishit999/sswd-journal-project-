<?php require_once 'data.php';

$orderId = $_GET['order_id'] ?? '';
if ($orderId === '') {
    header('Location: orders.php');
    exit;
}

$allOrders = $_SESSION['orders'] ?? [];
$lines = [];
foreach ($allOrders as $o) {
    if ($o['order_id'] === $orderId) {
        $lines[] = $o;
    }
}

if (!$lines) {
    header('Location: orders.php');
    exit;
}

$main   = $lines[0];
$created= $main['created_at'] ?? time();
$address= $main['address'] .' , '. $main['city'] .' - '. $main['pincode'];

[$gstPerItem, $dummy] = gst_breakup($main['amount']); // per line demo
$totalBase   = 0;
foreach ($lines as $l) $totalBase += $l['amount'];
[$totalGst, $totalWithGst] = gst_breakup($totalBase);

$status  = $main['status'];
$badge   = status_badge($status);
$badgeText  = $badge[0];
$badgeStyle = $badge[1];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Order <?= htmlspecialchars($orderId) ?> | ElectroHub</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'nav.php'; ?>

<section class="catalog-section py-4" id="tracking">
  <div class="container">
    <h2 class="section-title mb-1">Order details</h2>
    <p class="section-subtitle mb-3">
      Order #<?= htmlspecialchars($orderId) ?> • <?= date('d M Y H:i', $created) ?>
    </p>

    <div class="row gy-4">
      <!-- Left: products & tracking -->
      <div class="col-lg-7">
        <div class="vendor-highlight mb-3 fade-up">
          <h4 class="mb-2">Items in this order</h4>
          <?php foreach ($lines as $l):
            $product = get_product($l['product_id']);
            $img = $product['image'] ?? 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=900&q=80';
          ?>
            <div class="d-flex align-items-center mb-2">
              <div style="width:60px;height:60px;border-radius:.5rem;overflow:hidden;background:#000;margin-right:.75rem;">
                <img src="<?= htmlspecialchars($img) ?>" alt="Product image"
                     style="width:100%;height:100%;object-fit:cover;">
              </div>
              <div class="flex-grow-1">
                <div class="fw-semibold"><?= htmlspecialchars($l['product_name']) ?></div>
                <div class="small text-muted">
                  Qty: <?= $l['qty'] ?> · Sold by <?= htmlspecialchars($l['vendor_name']) ?>
                </div>
              </div>
              <div class="text-end">
                <div class="fw-bold">₹<?= number_format($l['amount']) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="vendor-highlight fade-up">
          <h4 class="mb-2">Status & tracking</h4>
          <p class="mb-1">
            <span class="badge bg-<?= $badgeStyle ?>"><?= $badgeText ?></span>
          </p>
          <p class="small text-muted mb-0">
            Current status: <?= htmlspecialchars($status) ?> (demo timeline)<br>
            For changes, you can track from this page or contact support.
          </p>
        </div>
      </div>

      <!-- Right: address & price breakdown -->
      <div class="col-lg-5">
        <div class="vendor-highlight mb-3 fade-up">
          <h4 class="mb-2">Delivery address</h4>
          <p class="mb-1"><?= nl2br(htmlspecialchars($address)) ?></p>
          <p class="small text-muted mb-0">
            Name: <?= htmlspecialchars($main['customer_name']) ?><br>
            Payment mode: <?= htmlspecialchars($main['payment_mode']) ?>
          </p>
        </div>

        <div class="vendor-highlight fade-up">
          <h4 class="mb-2">Price summary</h4>
          <p class="mb-1">Items total (before GST): ₹<?= number_format($totalBase) ?></p>
          <p class="mb-1">GST @ 18%: ₹<?= number_format($totalGst) ?></p>
          <?php if (!empty($main['coupon_code']) && $main['discount']>0): ?>
            <p class="mb-1">
              Coupon <?= htmlspecialchars($main['coupon_code']) ?>:
              −₹<?= number_format($main['discount']) ?>
            </p>
          <?php endif; ?>
          <?php if (($main['shipping'] ?? '') === 'express'): ?>
            <p class="mb-1">Express delivery fee: ₹<?= number_format($main['shipping_fee'] ?? 79) ?></p>
          <?php else: ?>
            <p class="mb-1">Standard delivery (Free)</p>
          <?php endif; ?>
          <p class="fw-bold mb-0">
            Final amount: ₹<?= number_format($main['final_total'] ?? $totalWithGst) ?>
          </p>
        </div>

        <div class="vendor-highlight mt-3 fade-up">
          <h4 class="mb-2">Need help?</h4>
          <p class="small text-muted mb-2">
            For issues with this order, raise a support ticket and mention the order ID.
          </p>
          <a href="support.php#support-form" class="btn btn-primary rounded-pill btn-soft">
            Contact support
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>