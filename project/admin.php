<?php require_once 'data.php';

$err = '';
// ----- HANDLE ADMIN LOGIN -----
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['form'] ?? '') === 'login') {
    $id = $_POST['admin_id'] ?? '';
    $pw = $_POST['admin_pw'] ?? '';
    if ($id === 'RGMN' && $pw === 'Dont look at the password') {
        $_SESSION['admin_authenticated'] = true;
        log_admin_event('login', 'Admin logged in');
    } else {
        $_SESSION['admin_authenticated'] = false;
        $err = 'Invalid admin credentials.';
        log_admin_event('login_failed', 'Bad credentials');
    }
}

$authed = $_SESSION['admin_authenticated'] ?? false;

// ----- HANDLE ADMIN ACTIONS (only if authenticated) -----
if ($authed && $_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['form']) && $_POST['form'] !== 'login') {
    $form = $_POST['form'];

    // Block / unblock customer
    if ($form === 'customer_status') {
        $cid = $_POST['customer_id'] ?? '';
        $action = $_POST['action'] ?? 'block';
        if ($cid) {
            $_SESSION['customer_status'][$cid] = ($action === 'block') ? 'blocked' : 'active';
            log_admin_event('customer_'.$action, "Customer {$cid}");
        }
    }

    // Suspend / unsuspend vendor
    if ($form === 'vendor_status') {
        $vid = $_POST['vendor_id'] ?? '';
        $action = $_POST['action'] ?? 'suspend';
        if ($vid) {
            $_SESSION['vendor_status'][$vid] = ($action === 'suspend') ? 'suspended' : 'active';
            log_admin_event('vendor_'.$action, "Vendor {$vid}");
        }
    }

    // Mark order as refunded
    if ($form === 'refund_order') {
        $oid = $_POST['order_id'] ?? '';
        $amount = (int)($_POST['refund_amount'] ?? 0);
        foreach ($_SESSION['orders'] as &$o) {
            if ($o['order_id'] === $oid) {
                $o['status'] = 'Refunded';
                $o['refund_amount'] = $amount;
            }
        }
        unset($o);
        log_admin_event('refund', "Order {$oid} refunded ₹{$amount}");
    }

    // Add coupon
    if ($form === 'add_coupon') {
        $code = strtoupper(trim($_POST['coupon_code'] ?? ''));
        $rate = (float)($_POST['discount_percent'] ?? 0) / 100.0;
        if ($code !== '' && $rate > 0 && $rate < 1) {
            $_SESSION['admin_coupons'][$code] = $rate;
            log_admin_event('coupon_add', "Code {$code} at ".($rate*100)."%");
        }
    }

    // Flash sale config
    if ($form === 'flash_sale') {
        $cat  = $_POST['category'] ?? '';
        $rate = (float)($_POST['discount_percent'] ?? 0) / 100.0;
        if ($cat && $rate > 0 && $rate < 1) {
            $_SESSION['flash_sale'] = ['category'=>$cat,'discount'=>$rate];
            log_admin_event('flash_sale_set', "{$cat} @ ".($rate*100)."%");
        } else {
            $_SESSION['flash_sale'] = null;
            log_admin_event('flash_sale_clear', 'Cleared');
        }
    }
}

// ----- METRICS CALCULATION -----
$vendors      = all_vendors();
$orders       = $_SESSION['orders'];
$customersArr = $_SESSION['customers'];

$vendorsCount   = count($vendors);
$ordersCount    = count($orders);
$customersCount = count($customersArr);
$productsCount  = count(all_products());
$customersOnline = rand(10, 80);

// Revenue & top products/vendors
$totalRevenue = 0;
$productRevenue = []; // name => revenue
$vendorRevenue  = []; // vendorName => revenue
foreach ($orders as $o) {
    $rev = $o['amount']; // base amount only (for demo)
    $totalRevenue += $rev;
    $productRevenue[$o['product_name']] = ($productRevenue[$o['product_name']] ?? 0) + $rev;
    $vendorRevenue[$o['vendor_name']]   = ($vendorRevenue[$o['vendor_name']] ?? 0)   + $rev;
}
arsort($productRevenue);
arsort($vendorRevenue);
$topProducts = array_slice($productRevenue, 0, 3, true);
$topVendors  = array_slice($vendorRevenue, 0, 3, true);

// Simple abandoned cart info (current session only)
$currentCart = $_SESSION['cart'] ?? [];
$abandonedItems = count($currentCart);

// Existing dynamic coupons / flash sale
$dynamicCoupons = $_SESSION['admin_coupons'] ?? [];
$flashSale      = $_SESSION['flash_sale'] ?? null;
$adminLogs      = array_reverse($_SESSION['admin_logs'] ?? []); // newest first
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin | ElectroHub</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="style.css">
  <style>
    .chart-bar {
      height: 10px;
      border-radius: 999px;
      background: linear-gradient(90deg,#4ade80,#16a34a);
    }
    .chart-row {
      margin-bottom: 0.4rem;
    }
  </style>
</head>
<body>
<?php include 'nav.php'; ?>

<section class="signup-section py-5">
  <div class="container">
    <h2 class="section-title mb-3">Admin console</h2>

    <?php if (!$authed): ?>
      <?php if ($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>
      <form method="post" class="vendor-form">
        <input type="hidden" name="form" value="login">
        <div class="mb-3">
          <label class="form-label">Admin ID</label>
          <input type="text" name="admin_id" class="form-control dark-input" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <div class="input-group">
            <input type="password" name="admin_pw" id="adminPw" class="form-control dark-input" required>
            <button class="btn btn-outline-light btn-soft" type="button" onclick="togglePw()">Show</button>
          </div>
          <small class="text-muted">ID: RGMN, password as per your project spec.</small>
        </div>
        <button class="btn btn-primary rounded-pill btn-soft" type="submit">
          Login as admin
        </button>
      </form>
    <?php else: ?>
      <!-- OVERVIEW CARDS -->
      <div class="row g-3 mb-4">
        <div class="col-md-3">
          <div class="stat-card fade-up">
            <h3><?= $vendorsCount ?></h3>
            <p>Active vendors</p>
          </div>
        </div>
        <div class="col-md-3">
          <div class="stat-card fade-up">
            <h3><?= $customersCount ?></h3>
            <p>Registered customers</p>
          </div>
        </div>
        <div class="col-md-3">
          <div class="stat-card fade-up">
            <h3><?= $ordersCount ?></h3>
            <p>Total orders (demo)</p>
          </div>
        </div>
        <div class="col-md-3">
          <div class="stat-card fade-up">
            <h3><?= $productsCount ?></h3>
            <p>Products live</p>
          </div>
        </div>
      </div>

      <!-- SALES & TOP PRODUCTS/VENDORS -->
      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <div class="vendor-highlight fade-up">
            <h4 class="mb-2">Sales overview</h4>
            <p class="mb-1">Total revenue (pre‑GST): ₹<?= number_format($totalRevenue) ?></p>
            <p class="mb-0 small text-muted">
              Charts could be extended using a chart library in a real deployment.
            </p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="vendor-highlight fade-up">
            <h4 class="mb-2">Top products by revenue</h4>
            <?php if (!$topProducts): ?>
              <p class="text-muted small mb-0">No orders yet.</p>
            <?php else:
              $max = max($topProducts);
              foreach ($topProducts as $name => $rev):
                $width = $max ? (int)(80 * $rev / $max) : 0;
            ?>
              <div class="chart-row small">
                <strong><?= htmlspecialchars($name) ?></strong>
                <span class="text-muted"> – ₹<?= number_format($rev) ?></span>
                <div class="chart-bar mt-1" style="width:<?= $width ?>%;"></div>
              </div>
            <?php endforeach; endif; ?>
          </div>
        </div>
        <div class="col-md-4">
          <div class="vendor-highlight fade-up">
            <h4 class="mb-2">Top vendors by revenue</h4>
            <?php if (!$topVendors): ?>
              <p class="text-muted small mb-0">No orders yet.</p>
            <?php else:
              $maxV = max($topVendors);
              foreach ($topVendors as $vname => $rev):
                $width = $maxV ? (int)(80 * $rev / $maxV) : 0;
            ?>
              <div class="chart-row small">
                <strong><?= htmlspecialchars($vname) ?></strong>
                <span class="text-muted"> – ₹<?= number_format($rev) ?></span>
                <div class="chart-bar mt-1" style="width:<?= $width ?>%;"></div>
              </div>
            <?php endforeach; endif; ?>
          </div>
        </div>
      </div>

      <!-- CUSTOMER MANAGEMENT -->
      <div class="vendor-highlight fade-up mb-4">
        <h4 class="mb-2">Customer management</h4>
        <div class="table-responsive">
          <table class="table table-dark table-striped align-middle mb-0">
            <thead>
            <tr>
              <th>Customer ID</th>
              <th>Name</th>
              <th>Email</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$customersArr): ?>
              <tr><td colspan="5" class="text-muted small">No customers yet.</td></tr>
            <?php else: foreach ($customersArr as $email => $c):
              $cid = $c['id'];
              $status = $_SESSION['customer_status'][$cid] ?? 'active';
            ?>
              <tr>
                <td><?= htmlspecialchars($cid) ?></td>
                <td><?= htmlspecialchars($c['name']) ?></td>
                <td><?= htmlspecialchars($email) ?></td>
                <td><?= htmlspecialchars(ucfirst($status)) ?></td>
                <td>
                  <form method="post" class="d-inline">
                    <input type="hidden" name="form" value="customer_status">
                    <input type="hidden" name="customer_id" value="<?= htmlspecialchars($cid) ?>">
                    <?php if ($status === 'blocked'): ?>
                      <input type="hidden" name="action" value="unblock">
                      <button class="btn btn-sm btn-outline-light rounded-pill btn-soft" type="submit">
                        Unblock
                      </button>
                    <?php else: ?>
                      <input type="hidden" name="action" value="block">
                      <button class="btn btn-sm btn-outline-light rounded-pill btn-soft" type="submit">
                        Block
                      </button>
                    <?php endif; ?>
                  </form>
                </td>
              </tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- VENDOR MANAGEMENT -->
      <div class="vendor-highlight fade-up mb-4">
        <h4 class="mb-2">Vendor management</h4>
        <div class="table-responsive">
          <table class="table table-dark table-striped align-middle mb-0">
            <thead>
            <tr>
              <th>Vendor ID</th>
              <th>Name</th>
              <th>City</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$vendors): ?>
              <tr><td colspan="5" class="text-muted small">No vendors yet.</td></tr>
            <?php else: foreach ($vendors as $v):
              $vid = $v['id'];
              $vstatus = $_SESSION['vendor_status'][$vid] ?? 'active';
            ?>
              <tr>
                <td><?= htmlspecialchars($vid) ?></td>
                <td><?= htmlspecialchars($v['name']) ?></td>
                <td><?= htmlspecialchars($v['city']) ?></td>
                <td><?= htmlspecialchars(ucfirst($vstatus)) ?></td>
                <td>
                  <form method="post" class="d-inline">
                    <input type="hidden" name="form" value="vendor_status">
                    <input type="hidden" name="vendor_id" value="<?= htmlspecialchars($vid) ?>">
                    <?php if ($vstatus === 'suspended'): ?>
                      <input type="hidden" name="action" value="unsuspend">
                      <button class="btn btn-sm btn-outline-light rounded-pill btn-soft" type="submit">
                        Unsuspend
                      </button>
                    <?php else: ?>
                      <input type="hidden" name="action" value="suspend">
                      <button class="btn btn-sm btn-outline-light rounded-pill btn-soft" type="submit">
                        Suspend
                      </button>
                    <?php endif; ?>
                  </form>
                </td>
              </tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ORDER / REFUND WORKFLOW -->
      <div class="vendor-highlight fade-up mb-4">
        <h4 class="mb-2">Orders, payments & refunds</h4>
        <div class="table-responsive">
          <table class="table table-dark table-striped align-middle mb-0">
            <thead>
            <tr>
              <th>Order ID</th>
              <th>Customer</th>
              <th>Product</th>
              <th>Vendor</th>
              <th>Amount (₹)</th>
              <th>GST (18%)</th>
              <th>Status</th>
              <th>Refund</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$orders): ?>
              <tr><td colspan="8" class="text-muted small">No orders yet.</td></tr>
            <?php else: foreach ($orders as $o):
              $gst = (int) round($o['amount'] * 0.18);
              $refund = $o['refund_amount'] ?? 0;
            ?>
              <tr>
                <td><?= htmlspecialchars($o['order_id']) ?></td>
                <td><?= htmlspecialchars($o['customer_id']) ?></td>
                <td><?= htmlspecialchars($o['product_name']) ?></td>
                <td><?= htmlspecialchars($o['vendor_id']) ?></td>
                <td><?= number_format($o['amount']) ?></td>
                <td><?= number_format($gst) ?></td>
                <td><?= htmlspecialchars($o['status']) ?></td>
                <td>
                  <?php if ($o['status'] !== 'Refunded'): ?>
                    <form method="post" class="d-inline">
                      <input type="hidden" name="form" value="refund_order">
                      <input type="hidden" name="order_id" value="<?= htmlspecialchars($o['order_id']) ?>">
                      <input type="number" name="refund_amount"
                             class="form-control form-control-sm dark-input mb-1"
                             placeholder="₹ amount" min="0">
                      <button class="btn btn-sm btn-outline-light rounded-pill btn-soft" type="submit">
                        Mark refunded
                      </button>
                    </form>
                  <?php else: ?>
                    <span class="small text-success">
                      Refunded ₹<?= number_format($refund) ?>
                    </span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- COUPON & PROMOTION MANAGEMENT -->
      <div class="row g-3 mb-4">
        <div class="col-md-6">
          <div class="vendor-highlight fade-up">
            <h4 class="mb-2">Coupon management</h4>
            <p class="small text-muted">
              Existing static coupons: SASTANASHA (15%), JALDIWALAAAYA (25%), UTHA LE RE (50%).
            </p>
            <p class="small mb-2">Admin‑defined coupons:</p>
            <?php if (!$dynamicCoupons): ?>
              <p class="text-muted small">No extra coupons yet.</p>
            <?php else: ?>
              <ul class="small">
                <?php foreach ($dynamicCoupons as $code => $rate): ?>
                  <li><?= htmlspecialchars($code) ?> – <?= $rate*100 ?>%</li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>

            <form method="post" class="mt-2">
              <input type="hidden" name="form" value="add_coupon">
              <div class="mb-2">
                <label class="form-label small">New coupon code</label>
                <input type="text" name="coupon_code" class="form-control dark-input" required>
              </div>
              <div class="mb-2">
                <label class="form-label small">Discount %</label>
                <input type="number" name="discount_percent" class="form-control dark-input"
                       min="1" max="90" required>
              </div>
              <button class="btn btn-primary btn-soft btn-sm rounded-pill" type="submit">
                Add coupon
              </button>
            </form>
          </div>
        </div>

        <div class="col-md-6">
          <div class="vendor-highlight fade-up">
            <h4 class="mb-2">Flash sale configuration</h4>
            <p class="small text-muted">
              Store-wide demo promotion. You can later use this config in pricing logic.
            </p>
            <?php if ($flashSale): ?>
              <p class="mb-2 small">
                Current flash sale: <strong><?= htmlspecialchars($flashSale['category']) ?></strong>
                – <?= $flashSale['discount']*100 ?>% off
              </p>
            <?php else: ?>
              <p class="mb-2 small text-muted">No flash sale configured.</p>
            <?php endif; ?>

            <form method="post">
              <input type="hidden" name="form" value="flash_sale">
              <div class="mb-2">
                <label class="form-label small">Category</label>
                <select name="category" class="form-select dark-select">
                  <option value="">(clear)</option>
                  <option value="laptop">Laptops</option>
                  <option value="phone">Phones</option>
                  <option value="accessory">Accessories</option>
                </select>
              </div>
              <div class="mb-2">
                <label class="form-label small">Discount %</label>
                <input type="number" name="discount_percent" class="form-control dark-input"
                       min="1" max="90">
              </div>
              <button class="btn btn-primary btn-soft btn-sm rounded-pill" type="submit">
                Save flash sale
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- SIMPLE ABANDONED CART & SECURITY LOG -->
      <div class="row g-3">
        <div class="col-md-6">
          <div class="vendor-highlight fade-up">
            <h4 class="mb-2">Abandoned cart (demo)</h4>
            <p class="small text-muted">
              Shows current session cart only. In a real system you would aggregate carts across users.
            </p>
            <?php if (!$abandonedItems): ?>
              <p class="text-muted small">No active cart in this session.</p>
            <?php else: ?>
              <p class="small mb-1">Items currently in cart: <?= $abandonedItems ?></p>
            <?php endif; ?>
          </div>
        </div>
        <div class="col-md-6">
          <div class="vendor-highlight fade-up">
            <h4 class="mb-2">Security / admin activity log</h4>
            <?php if (!$adminLogs): ?>
              <p class="text-muted small mb-0">No admin events recorded yet.</p>
            <?php else: ?>
              <ul class="small mb-0">
                <?php foreach ($adminLogs as $log): ?>
                  <li>
                    <?= date('d M H:i', $log['time']) ?> –
                    <strong><?= htmlspecialchars($log['action']) ?></strong>:
                    <?= htmlspecialchars($log['details']) ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePw() {
  const inp = document.getElementById('adminPw');
  inp.type = inp.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>