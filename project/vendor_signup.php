<?php require_once 'data.php';

// Any logged-in user can visit; we’ll branch by role below
require_login('any');

$user = current_user();
$role = $user['role'] ?? 'buyer';
$uname = $user['name'] ?? '';
$uemail = $user['email'] ?? '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Become a Vendor | ElectroHub</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="style.css">
  <style>
    .step-flow {
      display:flex;
      flex-wrap:wrap;
      gap:.3rem;
      font-size:.8rem;
      align-items:center;
    }
    .step-pill {
      border-radius:999px;
      padding:.15rem .65rem;
      background:#181b24;
      border:1px solid var(--border-subtle);
    }
    .vendor-benefit-icon {
      width:24px;
      display:inline-block;
      text-align:center;
    }
  </style>
</head>
<body>
<?php include 'nav.php'; ?>

<section class="signup-section py-5">
  <div class="container">
    <h2 class="section-title mb-2">Become a vendor</h2>

    <?php if ($role === 'vendor'): ?>
      <!-- Existing vendor state -->
      <div class="vendor-highlight fade-up mb-3">
        <h3 class="section-title mb-2" style="font-size:1.3rem;">You’re already a vendor</h3>
        <p class="small mb-2">
          Your account <strong><?= htmlspecialchars($uname) ?></strong> is already set up as a vendor.
        </p>
        <a href="vendor_dashboard.php" class="btn btn-primary rounded-pill btn-soft">
          Go to vendor dashboard
        </a>
      </div>
    <?php else: ?>
      <!-- Process indicator -->
      <div class="vendor-highlight fade-up mb-3">
        <div class="step-flow">
          <span class="step-pill">1 · Apply</span>
          <span>→</span>
          <span class="step-pill">2 · Review</span>
          <span>→</span>
          <span class="step-pill">3 · Verification</span>
          <span>→</span>
          <span class="step-pill">4 · Start selling</span>
        </div>
        <p class="small text-muted mb-0 mt-2">
          ⏱ Approval typically within <strong>24–48 hours</strong> . You can still browse as a customer while your
          vendor account is reviewed.
        </p>
      </div>

      <div class="row gy-4">
        <!-- LEFT: FORM -->
        <div class="col-lg-6">
          <div class="vendor-highlight fade-up">
            <h3 class="section-title mb-3" style="font-size:1.2rem;">Vendor information</h3>

            <form method="post" class="vendor-form"
                  target="_blank" action="vendor_dashboard.php">
              <div class="mb-3">
                <label class="form-label">Shop / Store name</label>
                <input type="text" name="vendor_name"
                       class="form-control dark-input"
                       maxlength="80"
                       placeholder="Example: Prime Electronics"
                       required>
                <div class="small text-muted mt-1">
                  This name will be visible to customers on product and vendor pages.
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label">Business email</label>
                <input type="email" name="vendor_email"
                       class="form-control dark-input"
                       value="<?= htmlspecialchars($uemail) ?>"
                       placeholder="Used for payouts & communication"
                       required>
                <div class="small text-muted mt-1">
                  We’ll use this for order notifications and vendor communication.
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label">City</label>
                <input type="text" name="vendor_city"
                       class="form-control dark-input"
                       placeholder="Example: Bengaluru"
                       required>
              </div>

              <div class="mb-3">
                <label class="form-label">Primary category</label>
                <select name="vendor_category" class="form-select dark-select" required>
                  <option value="">Select category</option>
                  <option value="laptop">Laptops</option>
                  <option value="phone">Phones</option>
                  <option value="accessory">Accessories</option>
                </select>
                <div class="small text-muted mt-1">
                  You can add more categories later. This helps us route your catalog correctly.
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label">Vendor type</label>
                <select name="vendor_type" class="form-select dark-select" required>
                  <option value="">Select vendor type</option>
                  <option value="individual">Individual seller</option>
                  <option value="business">Registered business</option>
                </select>
                <div class="small text-muted mt-1">
                  For this project, this field is informational; in a real system it would drive GST & KYC flows.
                </div>
              </div>

              <div class="mb-3 form-check">
                <input class="form-check-input" type="checkbox" id="vendorTerms"
                       name="vendor_terms" required>
              <label class="form-check-label small" for="vendorTerms">
                I agree to the
                <a href="vendor_terms.php#policy" class="text-decoration-underline">Vendor Policy</a>
                &amp;
                <a href="vendor_terms.php#terms" class="text-decoration-underline">Terms</a>.
              </label>
              </div>

              <button type="submit"
                      class="btn btn-primary rounded-pill btn-soft">
                Submit application
              </button>
              <p class="small text-muted mt-2 mb-0">
                ✔ No registration fee · ✔ Cancel anytime · ✔ Trusted marketplace 
              </p>

              <p class="small mt-2 mb-0">
                Need help applying?
                <a href="support.php#vendor-support">Contact vendor support</a>.
              </p>
            </form>
          </div>
        </div>

        <!-- RIGHT: BENEFITS / TESTIMONIALS -->
        <div class="col-lg-6">
          <div class="vendor-highlight mb-3 fade-up">
            <h3 class="section-title mb-2" style="font-size:1.2rem;">Why vendors choose ElectroHub</h3>
            <ul class="small mb-2">
              <li>
                <span class="vendor-benefit-icon">📊</span>
                Manage all laptops, mobiles & accessories from one dashboard.
              </li>
              <li>
                <span class="vendor-benefit-icon">👀</span>
                Reach buyers who are actively comparing prices across sellers.
              </li>
              <li>
                <span class="vendor-benefit-icon">💸</span>
                Transparent commissions and simple settlement reports .
              </li>
              <li>
                <span class="vendor-benefit-icon">🛡</span>
                Trusted marketplace presentation for your store.
              </li>
            </ul>
            <p class="small mb-0 text-muted">
              Stats : <strong>10+</strong> verified vendors ·
              <strong>₹5L+</strong> GMV simulated monthly.
            </p>
          </div>

          <div class="vendor-highlight fade-up">
            <h3 class="section-title mb-2" style="font-size:1.2rem;">What vendors say (demo)</h3>
            <p class="small mb-1">
              “ElectroHub makes it easy to manage laptops and phones from one place. The dark UI is
              great for long working hours.”
            </p>
            <p class="small text-muted mb-0">
              — Prime Electronics, Bangalore
            </p>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>