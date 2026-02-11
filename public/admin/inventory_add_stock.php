<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!is_logged_in()) {
  http_response_code(403);
  exit('Not logged in');
}

$u = current_user();

if (!in_array($u['role'], ['admin'])) {
  http_response_code(403);
  exit('Access denied');
}

if (isset($_POST['id'])) {
  $id = (int)$_POST['id'];
  
  // Increase stock by 1
  $stmt = $pdo->prepare("UPDATE inventory SET stock_qty = stock_qty + 1, updated_at = NOW() WHERE id = ?");
  $stmt->execute([$id]);

  // Return the updated stock value
  $newQty = $pdo->query("SELECT stock_qty FROM inventory WHERE id = $id")->fetchColumn();
  echo $newQty;
}
