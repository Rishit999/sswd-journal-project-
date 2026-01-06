<?php require_once 'data.php';
require_login('buyer');

$user = current_user();
$items = get_cart($user['id']);
if (!$items) {
  header('Location: catalog.php');
  exit;
}

$subtotal = array_sum(array_column($items, 'line'));
[$gstTotal, $grandTotal] = gst_breakup($subtotal);

$paymentDone = false;
$summary = [];
$createdOrderId = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $shipping = $_POST['shipping'] ?? 'standard';
  $shippingFee = $shipping === 'express' ? 79 : 0;

  $couponCode = $_POST['coupon'] ?? '';
  [$discount, $afterCoupon, $rate] = apply_coupon($couponCode, $grandTotal);
  $finalTotal = $afterCoupon + $shippingFee;

  $address = trim($_POST['address'] ?? '');
  $city    = trim($_POST['city'] ?? '');
  $pincode = trim($_POST['pincode'] ?? '');
  $mode    = $_POST['payment_mode'] ?? 'UPI';

  if ($address && $city && $pincode) {
    $orderId = create_order([
      'customer_id'   => $user['id'],
      'customer_name' => $user['name'],
      'items'         => $items,
      'payment_mode'  => $mode,
      'shipping'      => $shipping,
      'shipping_fee'  => $shippingFee,
      'address'       => $address,
      'city'          => $city,
      'pincode'       => $pincode,
      'coupon_code'   => $couponCode,
      'discount'      => $discount,
      'subtotal'      => $grandTotal,
      'final_total'   => $finalTotal,
    ]);
    clear_cart($user['id']);
    $paymentDone = true;
    $createdOrderId = $orderId;
    $summary = compact('subtotal', 'gstTotal', 'grandTotal', 'discount', 'afterCoupon', 'shippingFee', 'finalTotal', 'couponCode', 'rate', 'mode', 'shipping');
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Checkout | ElectroHub</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'nav.php'; ?>

<section class="catalog-section py-4">
  <div class="container">
    <h2 class="section-title mb-2">Payment gateway</h2>

    <?php if ($paymentDone): ?>
      <div class="alert alert-success fade-up">
        Payment successful. Your order <?= htmlspecialchars($createdOrderId) ?> has been placed.
        <a href="orders.php" class="ms-1">View your orders</a>
      </div>

      <div class="vendor-highlight fade-up mt-3">
        <h4 class="mb-2">Final price breakup</h4>
        <p class="mb-1">Subtotal: ₹<?= number_format($summary['subtotal']) ?></p>
        <p class="mb-1">GST @ 18%: ₹<?= number_format($summary['gstTotal']) ?></p>
        <?php if ($summary['discount'] > 0): ?>
          <p class="mb-1">
            Coupon (<?= htmlspecialchars($summary['couponCode']) ?> – <?= $summary['rate'] ?>%):
            −₹<?= number_format($summary['discount']) ?>
          </p>
        <?php endif; ?>
        <?php if ($summary['shipping'] === 'express'): ?>
          <p class="mb-1">Express delivery fee: ₹<?= number_format($summary['shippingFee']) ?></p>
        <?php else: ?>
          <p class="mb-1">Standard delivery (free)</p>
        <?php endif; ?>
        <p class="mb-1">Total after coupon: ₹<?= number_format($summary['afterCoupon']) ?></p>
        <p class="mb-0 fw-bold">Grand total charged (<?= htmlspecialchars($summary['mode']) ?>):
          ₹<?= number_format($summary['finalTotal']) ?></p>
      </div>
    <?php else: ?>
      <p class="section-subtitle mb-3">
        Review your order, select delivery, apply coupons and complete payment.
      </p>

      <div class="vendor-highlight mb-3 fade-up">
        <h4 class="mb-3">Order summary</h4>
        <?php foreach ($items as $row): $p=$row['product']; ?>
          <p class="mb-1">
            <?= $row['qty'] ?> × <?= htmlspecialchars($p['name']) ?>
            <span class="text-muted">from <?= htmlspecialchars($p['vendor']) ?></span>
            – ₹<?= number_format($row['line']) ?>
          </p>
        <?php endforeach; ?>
        <hr>
        <p class="mb-1">Subtotal: ₹<?= number_format($subtotal) ?></p>
        <p class="mb-1">GST @ 18%: ₹<?= number_format($gstTotal) ?></p>
        <p class="mb-0 fw-bold">Total (before coupon & shipping): ₹<?= number_format($grandTotal) ?></p>
      </div>

      <form method="post" class="vendor-highlight fade-up">
        <h4 class="mb-3">Delivery address</h4>
        <div class="mb-2">
          <label class="form-label">Full address</label>
          <textarea name="address" class="form-control dark-input" rows="2" required></textarea>
        </div>
        <div class="mb-2">
          <label class="form-label">City</label>
          <input type="text" name="city" class="form-control dark-input" required>
        </div>
        <div class="mb-3">
          <label class="form-label">PIN code</label>
          <input type="text" name="pincode" class="form-control dark-input" required>
        </div>

        <h4 class="mb-3">Delivery options</h4>
        <div class="mb-3">
          <div class="form-check">
            <input class="form-check-input" type="radio" name="shipping" value="standard" checked>
            <label class="form-check-label">
              Standard (2–4 working days) – Free
            </label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="shipping" value="express">
            <label class="form-check-label">
              Express (1–2 days) – ₹79
            </label>
          </div>
        </div>

        <h4 class="mb-3">Payment</h4>
        <div class="mb-2">
          <label class="form-label">Coupon code (optional)</label>
          <input type="text" name="coupon" class="form-control dark-input"
                 placeholder="SASTANASHA / JALDIWALAAAYA / UTHA LE RE">
        </div>

        <?php $canCOD = ($grandTotal <= 10000); ?>
        <div class="mb-3">
          <label class="form-label">Payment mode</label>
          <select name="payment_mode" class="form-select dark-select">
            <option value="UPI">UPI</option>
            <option value="Card">Credit / Debit Card</option>
            <option value="NetBanking">Net banking</option>
            <?php if ($canCOD): ?>
              <option value="COD">Cash on delivery</option>
            <?php endif; ?>
          </select>
          <small class="text-muted d-block mt-1">
            Cash on delivery available only for orders up to ₹10,000.
          </small>
        </div>

        <button type="submit" class="btn btn-primary rounded-pill btn-soft">
          Pay now
        </button>
      </form>
    <?php endif; ?>
  </div>
</section>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
