<?php require_once 'data.php';

$error = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $pw = $_POST['password'] ?? '';
  $cpw = $_POST['confirm_password'] ?? '';

  if ($name==='' || $email==='' || $pw==='' || $cpw==='') {
    $error = 'All fields are required.';
  } elseif ($pw !== $cpw) {
    $error = 'Passwords do not match.';
  } elseif (isset($_SESSION['customers'][$email])) {
    $error = 'Account already exists. Please login.';
  } else {
    $cust = register_customer($name,$email,$pw);
    login_user(['role'=>'buyer','id'=>$cust['id'],'name'=>$cust['name'],'email'=>$email]);
    header('Location: index.php');
    exit;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Sign up | ElectroHub</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'nav.php'; ?>

<section class="signup-section py-5">
  <div class="container">
    <h2 class="section-title mb-3">Create your account</h2>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" class="vendor-form">
      <div class="mb-3">
        <label class="form-label">Your name</label>
        <input type="text" name="name" class="form-control dark-input" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control dark-input" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control dark-input" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Confirm password</label>
        <input type="password" name="confirm_password" class="form-control dark-input" required>
      </div>
      <button class="btn btn-primary rounded-pill btn-soft" type="submit">
        Sign up as buyer
      </button>
    </form>
  </div>
</section>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>