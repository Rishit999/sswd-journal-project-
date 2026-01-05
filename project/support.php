<?php require_once 'data.php';

$user = current_user();
$uid  = $user['id']   ?? null;
$uname= $user['name'] ?? '';
$uemail = $user['email'] ?? '';

$successMsg = '';
$errorMsg   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = $_POST['form'] ?? '';

    if ($form === 'support_ticket') {
        $name   = trim($_POST['name'] ?? '');
        $email  = trim($_POST['email'] ?? '');
        $orderId= trim($_POST['order_id'] ?? '');
        $role   = $_POST['role'] ?? 'customer';
        $issue  = $_POST['issue_type'] ?? 'Other';
        $msg    = trim($_POST['message'] ?? '');

        if ($name === '' || $email === '' || $msg === '') {
            $errorMsg = 'Please fill in name, email and issue details.';
        } else {
            $ticketId = create_support_ticket([
                'user_id'    => $uid,
                'role'       => $role,
                'name'       => $name,
                'email'      => $email,
                'order_id'   => $orderId,
                'issue_type' => $issue,
                'message'    => $msg,
            ]);
            $successMsg = "Support request submitted. Ticket ID: {$ticketId}";
        }
    }

    if ($form === 'ticket_status' && $uid) {
        $tid = $_POST['ticket_id'] ?? '';
        $action = $_POST['action'] ?? 'close';
        if ($tid) {
            update_ticket_status($tid, $action === 'reopen' ? 'open' : 'closed', $uid);
        }
    }
}

$userTickets = $uid ? get_support_tickets($uid) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Support | ElectroHub</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="style.css">
  <style>
    .role-card {
      border-radius: 1rem;
      border: 1px solid var(--border-subtle);
      background: var(--bg-card);
      padding: 1.2rem 1.4rem;
      cursor: pointer;
      transition: transform .15s, box-shadow .15s, border-color .15s;
    }
    .role-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 16px 40px rgba(0,0,0,.7);
      border-color: #ffffff;
    }
    .quick-action-btn {
      min-width: 140px;
    }
  </style>
</head>
<body>
<?php include 'nav.php'; ?>

<section class="signup-section py-5">
  <div class="container">
    <h2 class="section-title mb-2">How can we help you?</h2>
    <p class="section-subtitle mb-3">
      Choose whether you’re a customer or a vendor, then pick a quick action or submit a support request.
    </p>

    <?php if ($successMsg): ?>
      <div class="alert alert-success fade-up"><?= htmlspecialchars($successMsg) ?></div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
      <div class="alert alert-danger fade-up"><?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
      <div class="col-md-6">
        <div class="role-card fade-up" onclick="document.getElementById('customer-support').scrollIntoView({behavior:'smooth'});">
          <h4 class="mb-2">🧑‍💻 I’m a customer</h4>
          <p class="small text-muted mb-0">
            Help with orders, payments, returns, refunds and account issues.
          </p>
        </div>
      </div>
      <div class="col-md-6">
        <div class="role-card fade-up" onclick="document.getElementById('vendor-support').scrollIntoView({behavior:'smooth'});">
          <h4 class="mb-2">🏪 I’m a vendor</h4>
          <p class="small text-muted mb-0">
            Help with dashboard, listings, settlements and policy questions.
          </p>
        </div>
      </div>
    </div>

    <h4 class="mb-2">Quick actions</h4>
    <div class="d-flex flex-wrap gap-2 mb-4">
      <a href="orders.php" class="btn btn-primary btn-soft rounded-pill quick-action-btn">
        📦 Track my order
      </a>
      <a href="#support-form" class="btn btn-outline-light btn-soft rounded-pill quick-action-btn">
        💳 Payment issue
      </a>
      <a href="#support-form" class="btn btn-outline-light btn-soft rounded-pill quick-action-btn">
        🔄 Return / refund
      </a>
      <a href="#vendor-support" class="btn btn-outline-light btn-soft rounded-pill quick-action-btn">
        🏪 Vendor issue
      </a>
      <a href="#support-form" class="btn btn-outline-light btn-soft rounded-pill quick-action-btn">
        🔐 Account problem
      </a>
    </div>

    <div id="customer-support" class="mb-4">
      <div class="vendor-highlight fade-up">
        <h3 class="section-title mb-2" style="font-size:1.3rem;">Customer support</h3>
        <p class="small mb-2">
          🕘 Support hours: <strong>9 AM – 9 PM (Mon–Sat)</strong><br>
          ⏱ Typical response time: <strong>within 24 hours</strong><br>
          🚨 Urgent issues: call our toll-free number below.
        </p>
        <p class="mb-1">📞 1800‑123‑4567</p>
        <p class="mb-0">✉ support@electrohub.</p>
      </div>
    </div>

    <div id="vendor-support" class="mb-4">
      <div class="vendor-highlight fade-up">
        <h3 class="section-title mb-2" style="font-size:1.3rem;">Vendor support</h3>
        <p class="small mb-2">
          Links for vendors:
        </p>
        <ul class="small mb-2">
          <li><a href="vendor_dashboard.php">Open vendor dashboard</a></li>
          <li><a href="catalog.php?category=laptop">Catalog upload tips </a></li>
          <li><a href="admin.php">Commission & policy overview (admin console demo)</a></li>
        </ul>
        <p class="small mb-0">
          For password reset or settlement issues, email: <strong>vendors@electrohub.</strong>
        </p>
      </div>
    </div>

    <div class="vendor-highlight mb-4 fade-up">
      <h4 class="mb-2">Common issues</h4>
      <ul class="small mb-0">
        <li>Orders – delayed delivery, tracking not updating, wrong item delivered.</li>
        <li>Payments – double charge, refund not received, payment failure.</li>
        <li>Returns – how to request, eligibility, refund timelines.</li>
        <li>Accounts – login issues, password reset, phone/email change.</li>
        <li>Vendors – listing issues, policies, settlement cycles.</li>
      </ul>
    </div>

    <div id="support-form" class="mb-4">
      <div class="vendor-highlight fade-up">
        <h3 class="section-title mb-3" style="font-size:1.3rem;">Submit a support request</h3>
        <form method="post">
          <input type="hidden" name="form" value="support_ticket">
          <div class="row g-3">
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" name="name"
                       class="form-control dark-input"
                       value="<?= htmlspecialchars($uname) ?>"
                       required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email"
                       class="form-control dark-input"
                       value="<?= htmlspecialchars($uemail) ?>"
                       required>
              </div>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label">Order ID (optional)</label>
                <input type="text" name="order_id"
                       class="form-control dark-input"
                       placeholder="e.g. ORD1024">
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label">I am a</label>
                <select name="role" class="form-select dark-select">
                  <option value="customer" <?= (!$user || $user['role']==='buyer')?'selected':''; ?>>Customer</option>
                  <option value="vendor"   <?= ($user && $user['role']==='vendor')?'selected':''; ?>>Vendor</option>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label">Issue type</label>
                <select name="issue_type" class="form-select dark-select">
                  <option>Order / delivery</option>
                  <option>Payment / refund</option>
                  <option>Return / replacement</option>
                  <option>Account / login</option>
                  <option>Vendor / listing</option>
                  <option>Other</option>
                </select>
              </div>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Describe your issue</label>
            <textarea name="message" rows="4"
                      class="form-control dark-input"
                      placeholder="Share details so we can help faster" required></textarea>
          </div>

          <button type="submit" class="btn btn-primary rounded-pill btn-soft">
            Submit support request
          </button>
        </form>
      </div>
    </div>

    <?php if ($uid && $userTickets): ?>
      <div class="vendor-highlight mb-2 fade-up">
        <h3 class="section-title mb-2" style="font-size:1.3rem;">Your support tickets</h3>
        <div class="table-responsive">
          <table class="table table-dark table-striped align-middle mb-0">
            <thead>
            <tr>
              <th>Ticket ID</th>
              <th>Created</th>
              <th>Issue type</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($userTickets as $t): ?>
              <tr>
                <td><?= htmlspecialchars($t['ticket_id']) ?></td>
                <td><?= date('d M Y H:i', strtotime($t['created_at'])) ?></td>
                <td><?= htmlspecialchars($t['issue_type']) ?></td>
                <td><?= htmlspecialchars(ucfirst($t['status'])) ?></td>
                <td>
                  <form method="post" class="d-inline">
                    <input type="hidden" name="form" value="ticket_status">
                    <input type="hidden" name="ticket_id" value="<?= htmlspecialchars($t['ticket_id']) ?>">
                    <?php if ($t['status'] === 'open'): ?>
                      <input type="hidden" name="action" value="close">
                      <button class="btn btn-sm btn-outline-light rounded-pill btn-soft" type="submit">
                        Close
                      </button>
                    <?php else: ?>
                      <input type="hidden" name="action" value="reopen">
                      <button class="btn btn-sm btn-outline-light rounded-pill btn-soft" type="submit">
                        Reopen
                      </button>
                    <?php endif; ?>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php elseif ($uid): ?>
      <p class="text-muted small">You have no open support tickets yet.</p>
    <?php endif; ?>
  </div>
</section>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
