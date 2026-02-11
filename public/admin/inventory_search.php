<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!is_logged_in()) exit;
refresh_user_session($pdo);
$u = current_user();

$search = $_GET['search'] ?? '';
if (!empty($search)) {
    $stmt = $pdo->prepare("SELECT * FROM inventory WHERE name LIKE ? OR description LIKE ? ORDER BY name");
    $stmt->execute(["%$search%", "%$search%"]);
    $items = $stmt->fetchAll();
} else {
    $items = $pdo->query("SELECT * FROM inventory ORDER BY name")->fetchAll();
}

if (empty($items)) {
    echo '<tr><td colspan="4" class="text-center text-muted">No items found.</td></tr>';
    exit;
}

foreach ($items as $it): ?>
<tr>
  <td><?= htmlspecialchars($it['name']) ?></td>
  <td>₱<?= number_format($it['price'], 2) ?></td>
  <td><?= $it['stock_qty'] > 0 ? htmlspecialchars($it['stock_qty']) : '<span class="text-danger">Out of Stock</span>' ?></td>
  <td>
    <a href="inventory_edit.php?id=<?= $it['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
    <?php if ($u['role'] === 'admin'): ?>
      <a href="inventory_delete.php?id=<?= $it['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this item?')">Delete</a>
    <?php endif; ?>
  </td>
</tr>
<?php endforeach; ?>
