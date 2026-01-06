<?php require_once 'data.php';
require_login('any');

$user = current_user();
$role = $user['role'] ?? 'buyer'; // buyer | vendor | admin
$uid  = $user['id']   ?? '';
$uname= $user['name'] ?? '';
$uemail = $user['email'] ?? '';

// Very simple “created at” demo (real app would store this)
$createdAt = $_SESSION['user_created_at'][$uid] ?? ($_SESSION['user_created_at'][$uid] = time());

// Derive role label & badge color
$roleLabel = 'Customer';
$roleColor = 'primary';
if ($role === 'vendor') {
    $roleLabel = 'Vendor';
    $roleColor = 'success';
} elseif ($role === 'admin') {
    $roleLabel = 'Admin';
    $roleColor = 'warning';
}

// Vendor info if vendor
$vendorInfo = null;
$vendorStatus = 'active';
if ($role === 'vendor') {
    // Find vendor auth record by id
    foreach ($_SESSION['vendors_auth'] as $email => $v) {
        if ($v['id'] === $uid) {
            $vendorInfo = $v;
            $vendorStatus = $_SESSION['vendor_status'][$uid] ?? 'active';
            break;
        }
    }
}

// Orders (customer or vendor)
$allOrders = $_SESSION['orders'] ?? [];
$customerOrders = [];
$vendorOrders   = [];

foreach ($allOrders as $o) {
    if ($role === 'buyer' && $o['customer_id'] === $uid) {
        $customerOrders[] = $o;
    }
    if ($role === 'vendor' && $o['vendor_id'] === $uid) {
        $vendorOrders[] = $o;
    }
}

// Wishlist (customer)
$wishlistItems = ($role === 'buyer') ? wishlist_for_current() : [];

// Support tickets
$allTickets = $_SESSION['support_tickets'] ?? [];
$userTickets = [];
$vendorTickets = [];
foreach ($allTickets as $t) {
    if ($t['user_id'] === $uid) {
        if ($t['role'] === 'vendor') $vendorTickets[] = $t;
        else $userTickets[] = $t;
    }
}

// Simple derived metrics for vendor
$vendorRating = 4.5;
$fulfillmentRate = 98;
$commissionRate  = 10; // percent (demo)
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Profile | ElectroHub</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="style.css">
  <style>
    .profile-avatar {
      width: 72px;
      height: 72px;
      border-radius: 999px;
      background: linear-gradient(135deg,#6366f1,#10b981);
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:1.8rem;
      font-weight:700;
    }
    .profile-nav a {
      font-size: .85rem;
    }
  </style>
</head>
<body>
<?php include 'nav.php'; ?>

<section class="catalog-section py-4">
  <div class="container">
    <!-- Top profile overview -->
    <div class="vendor-highlight mb-3 fade-up">
      <div class="d-flex gap-3 align-items-center">
        <div class="profile-avatar">
          <?= strtoupper(substr($uname,0,1)) ?>
        </div>
        <div class="flex-grow-1">
          <h2 class="mb-1 section-title" style="font-size:1.4rem;">
            <?= htmlspecialchars($uname) ?>
          </h2>
          <div class="small mb-1">
            <?= htmlspecialchars($uemail) ?>
          </div>
          <div class="d-flex flex-wrap gap-2 align-items-center small">
            <span class="badge bg-<?= $roleColor ?>">
              <?= $roleLabel ?>
            </span>
            <span class="badge bg-success">✔ Email verified</span>
            <span class="badge bg-success">✔ Phone verified (demo)</span>
            <?php if ($role === 'vendor' && $vendorStatus === 'active'): ?>
              <span class="badge bg-success">✔ Vendor verified</span>
            <?php elseif ($role === 'vendor' && $vendorStatus !== 'active'): ?>
              <span class="badge bg-warning text-dark">Vendor under review / suspended</span>
            <?php endif; ?>
            <span class="text-muted">
              · Member since <?= date('M Y', $createdAt) ?>
            </span>
          </div>
        </div>
        <div class="d-flex flex-column gap-2">
          <a href="#personal" class="btn btn-sm btn-outline-light rounded-pill btn-soft">
            Edit profile
          </a>
          <a href="forgot_password.php" class="btn btn-sm btn-outline-light rounded-pill btn-soft">
            Change password
          </a>
          <a href="logout.php" class="btn btn-sm btn-primary rounded-pill btn-soft">
            Logout
          </a>
        </div>
      </div>
    </div>

    <!-- Profile tabs (anchor shortcuts) -->
    <div class="d-flex flex-wrap gap-2 mb-4 profile-nav small">
      <a href="#overview"        class="chip">Overview</a>
      <a href="#personal"        class="chip">Personal info</a>
      <?php if ($role === 'buyer'): ?>
        <a href="#customer-sections" class="chip">Orders & wishlist</a>
      <?php endif; ?>
      <?php if ($role === 'vendor'): ?>
        <a href="#vendor-sections"   class="chip">Vendor dashboard</a>
      <?php endif; ?>
      <a href="#security"        class="chip">Security</a>
      <a href="#settings"        class="chip">Settings</a>
    </div>

    <!-- Overview: key stats -->
    <div id="overview" class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="stat-card fade-up">
          <h3><?= count($customerOrders) ?></h3>
          <p>Orders placed</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card fade-up">
          <h3><?= count($wishlistItems) ?></h3>
          <p>Items in wishlist</p>
        </div>
      </div>
      <?php if ($role === 'vendor'): ?>
        <div class="col-md-3">
          <div class="stat-card fade-up">
            <h3><?= count($vendorOrders) ?></h3>
            <p>Orders received</p>
          </div>
        </div>
        <div class="col-md-3">
          <div class="stat-card fade-up">
            <h3><?= $vendorRating ?></h3>
            <p>Vendor rating (demo)</p>
          </div>
        </div>
      <?php else: ?>
        <div class="col-md-3">
          <div class="stat-card fade-up">
            <h3><?= count($userTickets) ?></h3>
            <p>Support tickets</p>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <!-- Personal info -->
    <div id="personal" class="vendor-highlight mb-4 fade-up">
      <h3 class="section-title mb-2" style="font-size:1.3rem;">Personal information</h3>
      <div class="row g-3 small">
        <div class="col-md-6">
          <p class="mb-1"><strong>Full name</strong><br><?= htmlspecialchars($uname) ?></p>
          <p class="mb-1">
            <strong>Email</strong><br>
            <?= htmlspecialchars($uemail) ?> <span class="text-success ms-1">✔ Verified</span>
          </p>
          <p class="mb-1">
            <strong>Phone</strong><br>
            +91-90000-00000 <span class="text-muted">(demo)</span>
          </p>
        </div>
        <div class="col-md-6">
          <p class="mb-1">
            <strong>Default shipping address</strong><br>
            Not set (demo). You can collect and store this in another table.
          </p>
          <p class="mb-1">
            <strong>Language</strong><br>
            English (India)
          </p>
          <p class="mb-1">
            <strong>Notifications</strong><br>
            Email updates: On · SMS alerts: On (demo)
          </p>
        </div>
      </div>
      <?php if ($role === 'buyer'): ?>
        <p class="small mt-2 mb-0">
          Want to sell on ElectroHub?
          <a href="vendor_signup.php">Apply as vendor</a>.
        </p>
      <?php endif; ?>
    </div>

    <!-- Customer-specific sections -->
    <?php if ($role === 'buyer'): ?>
      <div id="customer-sections" class="mb-4">
        <div class="row g-3">
          <div class="col-lg-6">
            <div class="vendor-highlight fade-up">
              <h3 class="section-title mb-2" style="font-size:1.3rem;">Orders & shopping</h3>
              <ul class="small mb-2">
                <li><a href="orders.php">My orders</a></li>
                <li><a href="wishlist_page.php">Wishlist</a></li>
                <li><a href="cart.php">Cart</a></li>
                <li><a href="support.php#support-form">Returns & refunds</a> (via support form)</li>
              </ul>
              <p class="small mb-0 text-muted">
                Download invoices and manage returns from the order details pages.
              </p>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="vendor-highlight fade-up">
              <h3 class="section-title mb-2" style="font-size:1.3rem;">Support</h3>
              <ul class="small mb-2">
                <li><a href="support.php#support-form">Raise new ticket</a></li>
                <li><a href="support.php#support-form">Payment or refund issues</a></li>
                <li><a href="support.php#support-form">Account problems</a></li>
              </ul>
              <p class="small mb-0">
                You currently have <strong><?= count($userTickets) ?></strong> ticket(s).
              </p>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- Vendor-specific sections -->
    <?php if ($role === 'vendor'): ?>
      <div id="vendor-sections" class="mb-4">
        <div class="row g-3">
          <div class="col-lg-6">
            <div class="vendor-highlight fade-up">
              <h3 class="section-title mb-2" style="font-size:1.3rem;">Business information</h3>
              <?php if ($vendorInfo): ?>
                <p class="mb-1"><strong>Store name</strong><br><?= htmlspecialchars($vendorInfo['name']) ?></p>
                <p class="mb-1">
                  <strong>Business type</strong><br>Individual (demo)
                </p>
                <p class="mb-1">
                  <strong>GST / Tax registration</strong><br>***********1234 (masked demo)
                </p>
                <p class="mb-1">
                  <strong>Business address</strong><br>
                  <?= htmlspecialchars($vendorInfo['city']) ?>, India (demo)
                </p>
              <?php else: ?>
                <p class="small text-muted mb-0">
                  Vendor details not found in session. This is expected only in some demo flows.
                </p>
              <?php endif; ?>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="vendor-highlight fade-up">
              <h3 class="section-title mb-2" style="font-size:1.3rem;">Vendor operations</h3>
              <ul class="small mb-2">
                <li><a href="vendor_dashboard.php">Open vendor dashboard</a></li>
                <li><a href="catalog.php">Product listings</a> (filtered view from dashboard/catalog)</li>
                <li><a href="orders.php">Orders received</a></li>
              </ul>
              <p class="small mb-1">
                Commission rate: <strong><?= $commissionRate ?>%</strong> (demo)
              </p>
              <p class="small mb-0">
                Fulfilment rate: <strong><?= $fulfillmentRate ?>%</strong> · Rating: <strong><?= $vendorRating ?></strong>/5
              </p>
            </div>
          </div>
        </div>

        <div class="row g-3 mt-1">
          <div class="col-lg-6">
            <div class="vendor-highlight fade-up">
              <h3 class="section-title mb-2" style="font-size:1.3rem;">Vendor support</h3>
              <ul class="small mb-2">
                <li><a href="support.php#vendor-support">Vendor help centre</a></li>
                <li><a href="support.php#support-form">Vendor support tickets</a></li>
                <li><a href="admin.php">Policy & commission overview (admin demo)</a></li>
              </ul>
              <p class="small mb-0">
                You currently have <strong><?= count($vendorTickets) ?></strong> vendor ticket(s).
              </p>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="vendor-highlight fade-up">
              <h3 class="section-title mb-2" style="font-size:1.3rem;">Documents & compliance</h3>
              <p class="small mb-0 text-muted">
                In a full implementation, you would see uploaded KYC documents, accepted agreements
                and legal policies here. For this engineering project, these are represented conceptually.
              </p>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- Security & privacy -->
    <div id="security" class="vendor-highlight mb-4 fade-up">
      <h3 class="section-title mb-2" style="font-size:1.3rem;">Security & privacy</h3>
      <ul class="small mb-2">
        <li><a href="forgot_password.php">Change password</a></li>
        <li>Two-factor authentication (2FA): <span class="text-muted">Not enabled (demo)</span></li>
        <li>Active sessions: this device (demo)</li>
        <li>Login history & devices: concept only for this project</li>
      </ul>
      <p class="small mb-0 text-muted">
        For security, never share your password or OTP with anyone. Admins will never ask for it.
      </p>
    </div>

    <!-- Settings -->
    <div id="settings" class="vendor-highlight mb-4 fade-up">
      <h3 class="section-title mb-2" style="font-size:1.3rem;">Settings</h3>
      <ul class="small mb-2">
        <li>Email notifications: <span class="text-success">On</span> (demo)</li>
        <li>SMS alerts: <span class="text-success">On</span> (demo)</li>
        <li>Promotional emails: <span class="text-warning">Limited</span> (demo)</li>
        <li>Language & region: English (India)</li>
        <li>Theme: Dark (this UI)</li>
      </ul>
      <p class="small mb-0 text-muted">
        In a full build, this page would allow toggling each setting, choosing theme and region.
      </p>
    </div>

    <!-- Deactivation / data controls (conceptual) -->
    <div class="vendor-highlight mb-2 fade-up">
      <h3 class="section-title mb-2" style="font-size:1.3rem;">Account controls</h3>
      <ul class="small mb-2">
        <li>Deactivate account (demo – not wired)</li>
        <li>Request data export (demo – not wired)</li>
        <li>Delete account (conceptual for GDPR)</li>
      </ul>
      <p class="small mb-0 text-muted">
        These actions are not active in this project build, but included for completeness in your
        engineering documentation.
      </p>
    </div>
  </div>
</section>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>