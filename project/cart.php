<?php require_once 'data.php';
require_login('buyer');

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $pid = (int) $_POST['product_id'];
    if (get_product($pid)) {
        add_to_cart($user['id'], $pid);
    }
    header('Location: cart.php');
    exit;
}

$items = get_cart($user['id']);
$subtotal = array_sum(array_column($items, 'line'));
[$gstTotal, $grandTotal] = gst_breakup($subtotal);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Cart | ElectroHub</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'nav.php'; ?>

<section class="catalog-section py-4">
  <div class="container">
    <h2 class="section-title mb-2">Your cart</h2>
    <p class="section-subtitle mb-4">Price includes 18% GST (shown in the breakup).</p>

    <?php if (!$items): ?>
      <p class="text-muted">Your cart is empty. <a href="catalog.php">Browse products</a></p>
    <?php else: ?>
      <div class="table-responsive fade-up mb-4">
        <table class="table table-dark table-striped align-middle">
          <thead>
            <tr>
              <th>Product</th>
              <th>Vendor</th>
              <th>Qty</th>
              <th>Price (₹)</th>
              <th>Line total (₹)</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($items as $row): $p=$row['product']; ?>
            <tr>
              <td><?= htmlspecialchars($p['name']) ?></td>
              <td><?= htmlspecialchars($p['vendor']) ?></td>
              <td><?= $row['qty'] ?></td>
              <td><?= number_format($p['price']) ?></td>
              <td><?= number_format($row['line']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="vendor-highlight mb-3 fade-up">
        <h4 class="mb-3">Payment summary</h4>
        <p class="mb-1">Subtotal (before GST): ₹<?= number_format($subtotal) ?></p>
        <p class="mb-1">GST @ 18%: ₹<?= number_format($gstTotal) ?></p>
        <p class="mb-0 fw-bold">Total payable: ₹<?= number_format($grandTotal) ?></p>
      </div>

      <a href="checkout.php" class="btn btn-primary rounded-pill btn-soft">
        Proceed to payment
      </a>
    <?php endif; ?>
  </div>
</section>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
