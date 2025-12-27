<?php require_once 'data.php';

$createdVendor = null;
$vendorUser = current_user();
$viaSignupPost = ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['vendor_name']) && isset($_POST['vendor_email']));

// If coming from vendor_signup
if ($viaSignupPost) {
  $name  = trim($_POST['vendor_name'] ?? '');
  $email = trim($_POST['vendor_email'] ?? '');
  $city  = trim($_POST['vendor_city'] ?? '');
  $cat   = trim($_POST['vendor_category'] ?? 'laptop');

  if ($name && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $createdVendor = register_vendor($name, $email, $city, $cat);
    $vendorUser = [
      'role' => 'vendor',
      'id' => $createdVendor['id'],
      'name' => $createdVendor['name'],
      'email' => $email,
      'category' => $createdVendor['category'],
    ];
    login_user($vendorUser);
  }
} else {
  require_login('vendor');
  $vendorUser = current_user();
}

$id = $vendorUser['id'] ?? null;
$name = $vendorUser['name'] ?? '';
$email = $vendorUser['email'] ?? '';
$category = $vendorUser['category'] ?? 'laptop';

$vendorProducts = $_SESSION['vendor_products'][$id] ?? [];
$ordersForVendor = array_filter($_SESSION['orders'], fn($o)=>$o['vendor_id']===$id);
$reviewsCount = count($ordersForVendor);

$addMessage = '';
$addMessageType = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Vendor Dashboard | ElectroHub</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'nav.php'; ?>

<section class="catalog-section py-4">
  <div class="container">
    <h2 class="section-title mb-2">Vendor dashboard</h2>

    <?php if ($viaSignupPost && !$createdVendor): ?>
      <div class="alert alert-danger fade-up">
        Application data missing. Please register again.
      </div>
    <?php elseif ($createdVendor): ?>
      <div class="alert alert-success fade-up">
        Application received for <strong><?= htmlspecialchars($name) ?></strong> (<?= htmlspecialchars($email) ?>).
        Your vendor ID is <strong><?= htmlspecialchars($createdVendor['id']) ?></strong>.
        Initial password: <strong>vendor123</strong>.
      </div>
    <?php endif; ?>

    <?php
    // Handle demo add product (duplicate check)
    if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_demo']) && $id && !$viaSignupPost) {
      $pname = trim($_POST['pname'] ?? '');
      $pcat  = strtolower($_POST['pcat'] ?? $category);

      if ($pname !== '' && $pcat === $category) {
        $exists = false;
        foreach (all_products() as $prod) {
          if ($prod['vendor_id'] === $id && strcasecmp($prod['name'], $pname) === 0) {
            $exists = true;
            break;
          }
        }
        if ($exists) {
          $addMessage = 'This product is already listed in your catalog.';
          $addMessageType = 'warning';
        } else {
          $all = all_products();
          $newId = max(array_keys($all)) + 1;
          $fallbackImage = 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=900&q=80';
          $_SESSION['dynamic_products'][$newId] = [
            'id' => $newId,
            'name' => $pname,
            'category' => $pcat,
            'brand' => 'Custom',
            'price' => 50000,
            'vendor_id' => $id,
            'vendor' => $name,
            'rating' => 4.0,
            'image' => $fallbackImage,
            'history' => ['3m'=>48000,'6m'=>52000,'12m'=>54000],
          ];
          $_SESSION['vendor_products'][$id][] = $pname;
          notify_all_customers("New {$pcat} added by {$name}: {$pname}");
          $addMessage = 'Product added successfully. It will appear in the catalog.';
          $addMessageType = 'success';
          $vendorProducts = $_SESSION['vendor_products'][$id];
        }
      } else {
        $addMessage = 'Category must match your vendor type.';
        $addMessageType = 'danger';
      }
    }

    if ($addMessage): ?>
      <div class="alert alert-<?= $addMessageType ?> fade-up mt-2">
        <?= htmlspecialchars($addMessage) ?>
      </div>
    <?php endif; ?>

    <?php if ($id): ?>
      <div class="row gy-4 mt-2">
        <div class="col-lg-6">
          <div class="vendor-highlight">
            <h4 class="mb-3">Quick stats</h4>
            <ul class="list-unstyled mb-0">
              <li>Products listed: <?= count($vendorProducts) ?></li>
              <li>Orders received: <?= count($ordersForVendor) ?></li>
              <li>Customer feedback count (demo): <?= $reviewsCount ?></li>
            </ul>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="vendor-highlight">
            <h4 class="mb-3">Add a new product (demo)</h4>
            <form method="post">
              <div class="mb-2">
                <label class="form-label">Product name</label>
                <input type="text" name="pname" class="form-control dark-input" required>
              </div>
              <div class="mb-2">
                <label class="form-label">Category (must match your vendor type)</label>
                <select name="pcat" class="form-select dark-select">
                  <option value="laptop" <?= $category==='laptop'?'selected':'' ?>>Laptop</option>
                  <option value="phone" <?= $category==='phone'?'selected':'' ?>>Phone</option>
                  <option value="accessory" <?= $category==='accessory'?'selected':'' ?>>Accessory</option>
                </select>
              </div>
              <button class="btn btn-primary rounded-pill btn-soft" name="add_demo" value="1">
                Save product (local demo)
              </button>
            </form>
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