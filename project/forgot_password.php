<?php require_once 'data.php';

$msg = ''; $err='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $email = trim($_POST['email'] ?? '');
  if (!isset($_SESSION['customers'][$email])) {
    $err = 'Customer not found.';
  } else {
    $_SESSION['customers'][$email]['password'] = 'reset123';
    $msg = 'Password reset to "reset123". Please login and change it.';
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Forgot password | ElectroHub</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'nav.php'; ?>
<section class="signup-section py-5">
  <div class="container">
    <h2 class="section-title mb-3">Forgot password (customers)</h2>
    <?php if ($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>
    <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

    <form method="post" class="vendor-form">
      <div class="mb-3">
        <label class="form-label">Registered email</label>
        <input type="email" name="email" class="form-control dark-input" required>
      </div>
      <button class="btn btn-primary rounded-pill btn-soft" type="submit">
        Reset password
      </button>
      <p class="mt-2 small text-muted">
        Vendors must email the admin for password reset.
      </p>
    </form>
  </div>
</section>
<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>