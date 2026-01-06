<?php require_once 'data.php';
require_login('buyer');

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
  $pid = (int)$_POST['product_id'];
  if (get_product($pid)) {
    add_to_cart($user['id'], $pid, 1, true);
  }
}
header('Location: checkout.php');
exit;
