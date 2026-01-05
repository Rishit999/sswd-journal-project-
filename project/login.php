<?php require_once 'data.php';

$error = '';
$next = $_GET['next'] ?? 'index.php';

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $role = $_POST['role'] ?? 'buyer';
  $email = trim($_POST['email'] ?? '');
  $pw = $_POST['password'] ?? '';
  $next = $_POST['next'] ?? $next;

  if ($role === 'buyer') {
    $user = authenticate_customer($email, $pw);
  } else {
    $user = authenticate_vendor($email, $pw);
  }

  if ($user) {
    login_user($user);
    header('Location: '.$next);
    exit;
  } else {
    $error = 'Invalid credentials.';
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login | ElectroHub</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'nav.php'; ?>

<section class="signup-section py-5">
  <div class="container">
    <h2 class="section-title mb-3">Login</h2>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post" class="vendor-form">
      <input type="hidden" name="next" value="<?= htmlspecialchars($next) ?>">
      <div class="mb-3">
        <label class="form-label">I am a</label>
        <select name="role" class="form-select dark-select">
          <option value="buyer">Buyer</option>
          <option value="vendor">Vendor</option>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control dark-input" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control dark-input" required>
      </div>
      <button class="btn btn-primary rounded-pill btn-soft" type="submit">
        Login
      </button>
      <p class="mt-2 small">
        <a href="forgot_password.php">Forgot password?</a>
        &nbsp;|&nbsp;
        <a href="signup.php">Sign up</a>
      </p>
    </form>
  </div>
</section>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>