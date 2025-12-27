<?php require_once 'data.php';

$user = current_user();
$role = $user['role'] ?? 'guest';
$uid  = $user['id']   ?? null;

// ---------- UPDATE STATUSES DYNAMICALLY ----------
$allOrders = $_SESSION['orders'] ?? [];
foreach ($allOrders as $idx => $o) {
    $created = $o['created_at'] ?? time();
    $elapsed = time() - $created;
    if ($o['status'] === 'Placed' && $elapsed > 60) {
        $allOrders[$idx]['status'] = 'Packed';
    } elseif ($o['status'] === 'Packed' && $elapsed > 120) {
        $allOrders[$idx]['status'] = 'Shipped';
    } elseif ($o['status'] === 'Shipped' && $elapsed > 180) {
        $allOrders[$idx]['status'] = 'Out for delivery';
    } elseif ($o['status'] === 'Out for delivery' && $elapsed > 240) {
        $allOrders[$idx]['status'] = 'Delivered';
    }
}
$_SESSION['orders'] = $allOrders;

// ---------- HANDLE CANCELLATION ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order_id'])) {
    $cid = $_POST['cancel_order_id'];
    $reason = $_POST['cancel_reason'] ?? 'Other';
    foreach ($_SESSION['orders'] as &$order) {
        if ($order['order_id'] === $cid && !in_array($order['status'], ['Cancelled','Delivered','Refunded'])) {
            $order['status'] = 'Cancelled';
            $order['cancel_reason'] = $reason . ' (cancelled by customer)';
        }
    }
    unset($order);
}

// ---------- SELECT ORDERS VISIBLE TO CURRENT USER ----------
$allOrders = $_SESSION['orders'];

if ($role === 'vendor') {
    $ordersForView = array_filter($allOrders, fn($o) => $o['vendor_id'] === $uid);
} elseif ($role === 'buyer') {
    $ordersForView = array_filter($allOrders, fn($o) => $o['customer_id'] === $uid);
} else { // admin / guest
    $ordersForView = $allOrders;
}

// ---------- FILTERS & SEARCH (CARD VIEW FOR BUYER) ----------
$search       = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';     // all, delivered, cancelled, active
$period       = $_GET['period'] ?? 'all';     // all, 30d, 6m
$sort         = $_GET['sort'] ?? 'date_desc'; // date_desc, date_asc, amount_desc, amount_asc

// For buyers, group by order_id to build order cards
$orderCards = [];
if ($role === 'buyer') {
    foreach ($ordersForView as $o) {
        $id = $o['order_id'];
        if (!isset($orderCards[$id])) {
            $orderCards[$id] = [
                'order_id'   => $id,
                'customer_id'=> $o['customer_id'],
                'created_at' => $o['created_at'] ?? time(),
                'lines'      => [],
            ];
        }
        $orderCards[$id]['lines'][] = $o;
    }

    // Helper to compute aggregate status/order amount
    function aggregate_status(array $lines): string {
        $priority = ['Delivered'=>5,'Out for delivery'=>4,'Shipped'=>3,'Packed'=>2,'Placed'=>1,'Refunded'=>6,'Cancelled'=>0];
        $best = 'Placed';
        $bestScore = -1;
        foreach ($lines as $l) {
            $s = $l['status'];
            $score = $priority[$s] ?? 0;
            if ($s === 'Cancelled') { // if any cancelled, treat whole order as cancelled
                return 'Cancelled';
            }
            if ($s === 'Refunded') {
                return 'Refunded';
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $s;
            }
        }
        return $best;
    }

    function aggregate_amount(array $lines): int {
        $sum = 0;
        foreach ($lines as $l) $sum += $l['amount'];
        return $sum;
    }

    // Apply filters to cards
    $now = time();
    $filteredCards = [];
    foreach ($orderCards as $oid => $card) {
        $status = aggregate_status($card['lines']);
        $amount = aggregate_amount($card['lines']);
        $created= $card['created_at'];

        // search (order id or product name)
        if ($search !== '') {
            $match = stripos($oid, $search) !== false;
            if (!$match) {
                foreach ($card['lines'] as $l) {
                    if (stripos($l['product_name'], $search) !== false) {
                        $match = true; break;
                    }
                }
            }
            if (!$match) continue;
        }

        // status filter
        if ($statusFilter === 'delivered' && $status !== 'Delivered') continue;
        if ($statusFilter === 'cancelled' && $status !== 'Cancelled' && $status !== 'Refunded') continue;
        if ($statusFilter === 'active' && in_array($status, ['Cancelled','Refunded','Delivered'])) continue;

        // period filter
        $age = $now - $created;
        if ($period === '30d' && $age > 30*24*3600) continue;
        if ($period === '6m'  && $age > 180*24*3600) continue;

        $card['agg_status'] = $status;
        $card['agg_amount'] = $amount;
        $filteredCards[] = $card;
    }

    // sort
    usort($filteredCards, function($a,$b) use ($sort) {
        if ($sort === 'date_asc')   return ($a['created_at'] <=> $b['created_at']);
        if ($sort === 'amount_desc')return ($b['agg_amount'] <=> $a['agg_amount']);
        if ($sort === 'amount_asc') return ($a['agg_amount'] <=> $b['agg_amount']);
        // default date_desc
        return ($b['created_at'] <=> $a['created_at']);
    });
}

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

      <!-- Filters & search -->
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

          $product = get_product($main['product_id']);
          $img = $product['image'] ?? 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=900&q=80';

          $cancelReason = null;
          foreach ($lines as $l) {
            if (!empty($l['cancel_reason'])) { $cancelReason = $l['cancel_reason']; break; }
          }
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
                    Cancelled by you · <?= htmlspecialchars($cancelReason) ?>
                  </div>
                <?php elseif ($status === 'Refunded'): ?>
                  <div class="small text-info mt-1">
                    Refund initiated / completed (demo)
                  </div>
                <?php endif; ?>
              </div>
            </div>

            <!-- Actions -->
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
                <!-- Track order just goes to details for now -->
                <a href="order_details.php?order_id=<?= urlencode($card['order_id']) ?>#tracking"
                   class="btn btn-sm btn-outline-light rounded-pill btn-soft">
                  Track order
                </a>

                <!-- Cancel order -->
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

              <!-- Return / replace just links to support form -->
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
      <!-- For vendors/admin keep (simplified) table view -->
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
          <?php if (!$ordersForView): ?>
            <tr><td colspan="7" class="text-muted small">No orders yet.</td></tr>
          <?php else: foreach ($ordersForView as $o): ?>
            <tr>
              <td><?= htmlspecialchars($o['order_id']) ?></td>
              <td><?= htmlspecialchars($o['product_name']) ?></td>
              <td><?= htmlspecialchars($o['customer_name']) ?></td>
              <td><?= htmlspecialchars($o['vendor_name']) ?></td>
              <td><?= $o['qty'] ?></td>
              <td><?= number_format($o['amount']) ?></td>
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