<?php require_once 'data.php';

$user = current_user();
$role = $user['role'] ?? 'guest';
$uid  = $user['id']   ?? null;

update_order_statuses();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order_id']) && $role === 'buyer') {
    $cid = $_POST['cancel_order_id'];
    $reason = $_POST['cancel_reason'] ?? 'Other';
    cancel_order($cid, $reason, $uid);
}

$filters = [];
if ($role === 'vendor') {
    $filters['vendor_id'] = $uid;
} elseif ($role === 'buyer') {
    $filters['customer_id'] = $uid;
}
$rows = get_orders($filters);

$search       = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';
$period       = $_GET['period'] ?? 'all';
$sort         = $_GET['sort'] ?? 'date_desc';

$orderCards = [];
foreach ($rows as $row) {
    $id = $row['order_id'];
    if (!isset($orderCards[$id])) {
        $orderCards[$id] = [
            'order_id'   => $id,
            'customer_id'=> $row['customer_id'],
            'created_at' => strtotime($row['created_at']),
            'status'     => $row['status'],
            'lines'      => [],
        ];
    }
    $orderCards[$id]['lines'][] = $row;
}

function aggregate_status(array $lines): string {
    foreach ($lines as $line) {
        if (in_array($line['status'], ['Cancelled','Refunded'], true)) {
            return $line['status'];
        }
    }
    return $lines[0]['status'] ?? 'Placed';
}

function aggregate_amount(array $lines): int {
    return array_sum(array_column($lines, 'line_total'));
}

$now = time();
$filteredCards = [];
foreach ($orderCards as $card) {
    $status = aggregate_status($card['lines']);
    $amount = aggregate_amount($card['lines']);
    $created= $card['created_at'];

    if ($search !== '') {
        $match = stripos($card['order_id'], $search) !== false;
        if (!$match) {
            foreach ($card['lines'] as $line) {
                if (stripos($line['product_name'], $search) !== false) {
                    $match = true; break;
                }
            }
        }
        if (!$match) continue;
    }

    if ($statusFilter === 'delivered' && $status !== 'Delivered') continue;
    if ($statusFilter === 'cancelled' && !in_array($status, ['Cancelled','Refunded'])) continue;
    if ($statusFilter === 'active' && in_array($status, ['Cancelled','Refunded','Delivered'])) continue;

    $age = $now - $created;
    if ($period === '30d' && $age > 30*24*3600) continue;
    if ($period === '6m'  && $age > 180*24*3600) continue;

    $card['agg_status'] = $status;
    $card['agg_amount'] = $amount;
    $filteredCards[] = $card;
}

usort($filteredCards, function($a,$b) use ($sort) {
    switch ($sort) {
        case 'date_asc':
            return $a['created_at'] <=> $b['created_at'];
        case 'amount_desc':
            return $b['agg_amount'] <=> $a['agg_amount'];
        case 'amount_asc':
            return $a['agg_amount'] <=> $b['agg_amount'];
        case 'date_desc':
        default:
            return $b['created_at'] <=> $a['created_at'];
    }
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Your orders | ElectroHub</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="style.css">
  <style>
    .order-card {
      border-radius: 1rem;
      border: 1px solid var(--border-subtle);
      background: var(--bg-card);
      padding: 1rem 1.2rem;
      margin-bottom: 1rem;
    }
    .order-card:hover {
      box-shadow: 0 16px 40px rgba(0,0,0,.7);
    }
    .order-thumb {
      width: 64px;
      height: 64px;
      border-radius: .5rem;
      overflow: hidden;
      background:#000;
      margin-right:.75rem;
    }
    .order-thumb img {
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
    <h2 class="section-title mb-1">Your recent orders</h2>

    <?php if ($role === 'buyer'): ?>
      <p class="section-subtitle mb-3">
        Track deliveries, manage cancellations and view details of your orders.
      </p>

      <form class="row g-2 mb-3" method="get">
        <div class="col-md-4">
          <input type="text" name="search" class="form-control dark-input"
                 placeholder="Search by order ID or product"
                 value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-2">
          <select name="status" class="form-select dark-select">
            <option value="all"       <?= $statusFilter==='all'?'selected':''; ?>>All statuses</option>
            <option value="active"    <?= $statusFilter==='active'?'selected':''; ?>>Active</option>
            <option value="delivered" <?= $statusFilter==='delivered'?'selected':''; ?>>Delivered</option>
            <option value="cancelled" <?= $statusFilter==='cancelled'?'selected':''; ?>>Cancelled / refunded</option>
          </select>
        </div>
        <div class="col-md-2">
          <select name="period" class="form-select dark-select">
            <option value="all"  <?= $period==='all'?'selected':''; ?>>All time</option>
            <option value="30d"  <?= $period==='30d'?'selected':''; ?>>Last 30 days</option>
            <option value="6m"   <?= $period==='6m'?'selected':''; ?>>Last 6 months</option>
          </select>
        </div>
        <div class="col-md-2">
          <select name="sort" class="form-select dark-select">
            <option value="date_desc"   <?= $sort==='date_desc'?'selected':''; ?>>Newest first</option>
            <option value="date_asc"    <?= $sort==='date_asc'?'selected':''; ?>>Oldest first</option>
            <option value="amount_desc" <?= $sort==='amount_desc'?'selected':''; ?>>Amount high → low</option>
            <option value="amount_asc"  <?= $sort==='amount_asc'?'selected':''; ?>>Amount low → high</option>
          </select>
        </div>
        <div class="col-md-2">
          <button class="btn btn-primary w-100 rounded-pill btn-soft" type="submit">
            Filter
          </button>
        </div>
      </form>

      <?php if (!$filteredCards): ?>
        <p class="text-muted mb-3">
          You haven’t placed any orders yet.
        </p>
        <a href="catalog.php" class="btn btn-primary rounded-pill btn-soft">Browse products</a>
      <?php else: ?>
        <?php foreach ($filteredCards as $card):
          $lines   = $card['lines'];
          $main    = $lines[0];
          $status  = $card['agg_status'];
          $amount  = $card['agg_amount'];
          $dateStr = date('d M Y', $card['created_at']);
          $itemsCount = count($lines);

          $badge = status_badge($status);
          $badgeText  = $badge[0];
          $badgeStyle = $badge[1];

          $product = get_product((int)$main['product_id']);
          $img = $product['image'] ?? 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=900&q=80';

          $cancelReason = $main['cancel_reason'] ?? null;
        ?>
          <div class="order-card fade-up">
            <div class="d-flex mb-2">
              <div class="order-thumb">
                <img src="<?= htmlspecialchars($img) ?>" alt="Product image">
              </div>
              <div class="flex-grow-1">
                <div class="d-flex justify-content-between">
                  <div>
                    <div class="fw-semibold"><?= htmlspecialchars($main['product_name']) ?></div>
                    <?php if ($itemsCount > 1): ?>
                      <div class="small text-muted"><?= $itemsCount-1 ?> more item(s)</div>
                    <?php endif; ?>
                    <div class="small text-muted">
                      Order #<?= htmlspecialchars($card['order_id']) ?> • <?= $dateStr ?>
                    </div>
                  </div>
                  <div class="text-end">
                    <div class="fw-bold">₹<?= number_format($amount) ?></div>
                    <span class="badge bg-<?= $badgeStyle ?> mt-1"><?= $badgeText ?></span>
                  </div>
                </div>

                <?php if ($status === 'Cancelled' && $cancelReason): ?>
                  <div class="small text-danger mt-1">
                    <?= htmlspecialchars($cancelReason) ?>
                  </div>
                <?php elseif ($status === 'Refunded'): ?>
                  <div class="small text-info mt-1">
                    Refund initiated / completed
                  </div>
                <?php endif; ?>
              </div>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-2">
              <a href="order_details.php?order_id=<?= urlencode($card['order_id']) ?>"
                 class="btn btn-sm btn-outline-light rounded-pill btn-soft">
                View details
              </a>

              <form method="post" action="buy_now.php" class="d-inline">
                <input type="hidden" name="product_id" value="<?= $main['product_id'] ?>">
                <button class="btn btn-sm btn-outline-light rounded-pill btn-soft" type="submit">
                  Reorder
                </button>
              </form>

              <?php if (!in_array($status, ['Cancelled','Delivered','Refunded'])): ?>
                <a href="order_details.php?order_id=<?= urlencode($card['order_id']) ?>#tracking"
                   class="btn btn-sm btn-outline-light rounded-pill btn-soft">
                  Track order
                </a>

                <form method="post" class="d-inline">
                  <input type="hidden" name="cancel_order_id"
                         value="<?= htmlspecialchars($card['order_id']) ?>">
                  <select name="cancel_reason"
                          class="form-select form-select-sm dark-select d-inline-block mb-1"
                          style="width: 170px;">
                    <option value="Ordered by mistake">Ordered by mistake</option>
                    <option value="Delivery taking too long">Delivery taking too long</option>
                    <option value="Found cheaper elsewhere">Found cheaper elsewhere</option>
                    <option value="Changed my mind">Changed my mind</option>
                  </select>
                  <button class="btn btn-sm btn-outline-light rounded-pill btn-soft" type="submit">
                    Cancel order
                  </button>
                </form>
              <?php endif; ?>

              <?php if (in_array($status, ['Delivered','Shipped','Out for delivery'])): ?>
                <a href="support.php#support-form"
                   class="btn btn-sm btn-outline-light rounded-pill btn-soft">
                  Return / replace
                </a>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

    <?php else: ?>
      <p class="section-subtitle mb-3">
        Viewing orders as <?= $role === 'vendor' ? 'Vendor' : 'Admin/Guest' ?>.
      </p>

      <div class="table-responsive fade-up">
        <table class="table table-dark table-striped align-middle">
          <thead>
          <tr>
            <th>Order ID</th>
            <th>Product</th>
            <th>Customer</th>
            <th>Vendor</th>
            <th>Qty</th>
            <th>Amount (₹)</th>
            <th>Status</th>
          </tr>
          </thead>
          <tbody>
          <?php if (!$rows): ?>
            <tr><td colspan="7" class="text-muted small">No orders yet.</td></tr>
          <?php else: foreach ($rows as $o): ?>
            <tr>
              <td><?= htmlspecialchars($o['order_id']) ?></td>
              <td><?= htmlspecialchars($o['product_name']) ?></td>
              <td><?= htmlspecialchars($o['customer_name']) ?></td>
              <td><?= htmlspecialchars($o['vendor_name']) ?></td>
              <td><?= $o['qty'] ?></td>
              <td><?= number_format($o['line_total']) ?></td>
              <td><?= htmlspecialchars($o['status']) ?></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
