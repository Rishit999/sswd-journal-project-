<?php require_once 'data.php';
require_login('buyer');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
  $pid = (int)$_POST['product_id'];
  if (get_product($pid)) {
    $_SESSION['cart'] = [$pid => 1];
  }
}
header('Location: checkout.php');
exit;