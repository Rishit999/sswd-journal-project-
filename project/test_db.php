<?php
require_once __DIR__ . "/db.php";

try {
    $pdo = Database::getInstance()->pdo();
    echo "? Database connection successful!<br>";

    $stmt = $pdo->query("SELECT COUNT(*) AS count FROM products");
    $result = $stmt->fetch();
    echo "? Found {$result['count']} products in database.<br>";

    echo "? Your database is working correctly.";
} catch (Throwable $e) {
    echo "? Error: " . $e->getMessage();
}
?>
