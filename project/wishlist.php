<?php require_once 'data.php';
require_login('buyer');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $pid    = (int)$_POST['product_id'];
    $action = $_POST['action'] ?? 'add';

    if ($action === 'remove') {
        remove_from_wishlist($pid);
    } else {
        add_to_wishlist($pid);
    }
}

// Go back to the page the user was on
$back = $_SERVER['HTTP_REFERER'] ?? 'wishlist_page.php';
header('Location: '.$back);
exit;