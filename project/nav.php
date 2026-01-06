<?php require_once 'data.php';

$user = current_user();
$role = $user['role'] ?? null;
$name = $user['name'] ?? null;
?>
<nav class="navbar navbar-expand-lg main-navbar sticky-top">
  <div class="container">
    <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="index.php">
      <img src="images/logo.jpeg" alt="ElectroHub logo" class="brand-logo" width="36" height="36" loading="lazy">
      <span>ElectroHub</span>
    </a>

    <button class="navbar-toggler text-white border-0" type="button"
            data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-lg-auto gap-2 nav-main-list">
        <!-- Primary navigation -->
        <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="catalog.php">Catalog</a></li>
        <li class="nav-item"><a class="nav-link" href="vendors.php">Vendors</a></li>

        <?php if ($role === 'vendor'): ?>
          <li class="nav-item">
            <a class="nav-link fw-semibold text-info" href="vendor_dashboard.php">
              Vendor dashboard
            </a>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link fw-semibold text-warning" href="vendor_signup.php">
              Become a vendor
            </a>
          </li>
        <?php endif; ?>

        <li class="nav-item"><a class="nav-link" href="support.php">Support</a></li>
        <!-- Admin is intentionally NOT shown in navbar; use direct URL /admin.php -->

        <?php if ($user): ?>
          <!-- Account dropdown on the far right -->
          <li class="nav-item dropdown ms-lg-3">
            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#"
               id="accountDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <span class="me-1">👤</span>
              <span><?= htmlspecialchars($name) ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li>
                <a class="dropdown-item" href="orders.php">Orders</a>
              </li>
              <li>
                <a class="dropdown-item" href="wishlist_page.php">Wishlist</a>
              </li>
              <li>
                <!-- simple profile placeholder; can be extended later -->
                <a class="dropdown-item" href="profile.php">Profile</a>
              </li>
              <li><hr class="dropdown-divider"></li>
              <li>
                <a class="dropdown-item" href="logout.php">Logout</a>
              </li>
            </ul>
          </li>
        <?php else: ?>
          <!-- Login / Sign up grouped -->
          <li class="nav-item auth-buttons">
            <a class="btn btn-sm btn-outline-light" href="login.php">Login</a>
            <a class="btn btn-sm btn-primary rounded-pill btn-soft" href="signup.php">Sign Up</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>