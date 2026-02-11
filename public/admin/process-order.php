<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
if (!is_logged_in()) header('Location: /public/login.php');
refresh_user_session($pdo);
$user = current_user();

$customer = trim($_POST['customer_name'] ?? '');
$qtys = $_POST['qty'] ?? [];

// Convert quantities to integers
$itemsOrdered = [];
foreach ($qtys as $iid => $q) {
    $q = (int)$q;
    if ($q > 0) $itemsOrdered[$iid] = $q;
}

if (empty($itemsOrdered)) {
    echo "<script>
            alert('No items selected.');
            window.location.href='order.php';
          </script>";
    exit;
}

// 🧠 Ask Dine-in or Take-out
echo "<!DOCTYPE html>
<html>
<head>
  <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
</head>
<body>
<script>
Swal.fire({
  title: 'Order Type',
  text: 'Is this order for Dine-in or Take-out?',
  icon: 'question',
  showCancelButton: true,
  confirmButtonText: 'Take-out',
  cancelButtonText: 'Dine-in'
}).then((result) => {
  if (result.isConfirmed) {
    window.location.href = 'process-order-step2.php?type=Take-out&customer=" . urlencode($customer) . "&items=" . urlencode(json_encode($itemsOrdered)) . "';
  } else {
    window.location.href = 'process-order-step2.php?type=Dine-in&customer=" . urlencode($customer) . "&items=" . urlencode(json_encode($itemsOrdered)) . "';
  }
});
</script>
</body>
</html>";
exit;
?>
