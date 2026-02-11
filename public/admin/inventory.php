<?php 
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!is_logged_in()) header('Location: /public/login.php');
refresh_user_session($pdo);
$u = current_user();

if (!in_array($u['role'], ['admin', 'staff'])) {
    echo 'Access denied';
    exit;
}

$items = $pdo->query("SELECT * FROM inventory ORDER BY name")->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Inventory Management - i-Tea Ordering</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
  font-family: 'Poppins', sans-serif;
  background-color: #f5f7f2;
  color: #2f3b2f;
  display: flex;
  height: 100vh;
  margin: 0;
}
.sidebar {
  width: 230px;
  background-color: #8a9a5b;
  color: #fff;
  padding-top: 1.5rem;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  height: 100vh;
  position: fixed;
  top: 0;
  left: 0;
}
.sidebar a {
  color: #fff;
  text-decoration: none;
  display: block;
  padding: 12px 20px;
  transition: 0.3s;
  border-left: 4px solid transparent;
}
.sidebar a:hover, .sidebar a.active {
  background-color: #9cab6d;
  border-left: 4px solid #fff;
}
.sidebar-footer {
  padding: 1rem;
  text-align: center;
  border-top: 1px solid rgba(255,255,255,0.2);
}
.main-content {
  margin-left: 230px;
  padding: 2rem;
  width: 100%;
}
.table thead {
  background-color: #8a9a5b;
  color: white;
}
.btn-success {
  background-color: #8a9a5b;
  border: none;
}
.btn-success:hover {
  background-color: #7c8b52;
}
.search-bar {
  display: flex;
  align-items: center;
  margin-bottom: 1rem;
}
.search-bar input {
  flex: 1;
  padding: 8px;
  border: 1px solid #ccc;
  border-radius: 6px;
  margin-right: 8px;
}
</style>
</head>
<body>
  <!-- Sidebar -->
  <div class="sidebar">
    <div>
      <h5 class="text-center mb-4">🍵 i - Tea <?= ucfirst($u['role']) ?></h5>

      <?php if ($u['role'] === 'admin'): ?>
        <a href="profile.php">Profile</a>
        <a href="dashboard.php">Dashboard</a>
        <a href="inventory.php" class="active">Inventory</a>
        <a href="users.php">Users</a>
        <a href="ordering.php">Create Order</a>
        <a href="order.php">Orders</a>
      <?php elseif ($u['role'] === 'staff'): ?>
        <a href="profile.php">Profile</a>
        <a href="inventory.php" class="active">Inventory</a>
        <a href="ordering.php">Create Order</a>
        <a href="order.php">Orders</a>
      <?php endif; ?>
    </div>

    <div class="sidebar-footer">
      <p class="mb-1 small">Logged in as:</p>
      <strong><?= htmlspecialchars($u['full_name'] ?? $u['username']) ?></strong><br>
      <a href="/Ordering/public/logout.php" class="btn btn-light btn-sm mt-2">Logout</a>
    </div>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <h4 class="mb-4">Inventory Management</h4>

    <?php if ($u['role'] === 'admin'): ?>
      <a href="inventory_add.php" class="btn btn-success mb-3">➕ Add New Item</a>
    <?php endif; ?>

    <!-- ✅ Live Search -->
    <div class="search-bar">
      <input type="text" id="searchBox" placeholder="Search item name or description...">
    </div>

    <div class="card">
      <div class="card-header bg-success bg-opacity-25 fw-semibold">Product List</div>
      <div class="card-body">
        <table class="table table-bordered align-middle">
          <thead>
            <tr><th>Name</th><th>Price</th><th>Stock</th><th>Actions</th></tr>
          </thead>
          <tbody id="inventoryTable">
            <?php foreach ($items as $it): ?>
              <tr>
                <td><?= htmlspecialchars($it['name']) ?></td>
                <td>₱<?= number_format($it['price'], 2) ?></td>
                <td><?= $it['stock_qty'] > 0 ? htmlspecialchars($it['stock_qty']) : '<span class="text-danger">Out of Stock</span>' ?></td>
                <td>
                  <button class="btn btn-sm btn-success add-stock-btn" data-id="<?= $it['id'] ?>">+1 Stock</button>
                  <?php if ($u['role'] === 'admin'): ?>
                    <a href="inventory_edit.php?id=<?= $it['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                    <a href="inventory_delete.php?id=<?= $it['id'] ?>" class="btn btn-sm btn-danger">Delete</a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($items)): ?>
              <tr><td colspan="4" class="text-center text-muted">No items found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

<script>
// ✅ Live search using AJAX
document.getElementById('searchBox').addEventListener('input', function() {
  const query = this.value.trim();
  fetch('inventory_search.php?search=' + encodeURIComponent(query))
    .then(res => res.text())
    .then(html => {
      document.getElementById('inventoryTable').innerHTML = html;
    });
});

// ✅ Add +1 stock handler with temporary button disable
document.addEventListener('click', function(e) {
  if (e.target.classList.contains('add-stock-btn')) {
    const id = e.target.dataset.id;
    const btn = e.target;
    btn.disabled = true;
    btn.textContent = '...';

    fetch('inventory_add_stock.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'id=' + encodeURIComponent(id)
    })
    .then(res => res.text())
    .then(newQty => {
      if (newQty) {
        const row = btn.closest('tr');
        const stockCell = row.querySelector('td:nth-child(3)');
        stockCell.textContent = newQty;
      }
    })
    .catch(err => console.error('Error updating stock:', err))
    .finally(() => {
      btn.disabled = false;
      btn.textContent = '+1 Stock';
    });
  }
});
</script>

</body>
</html>
