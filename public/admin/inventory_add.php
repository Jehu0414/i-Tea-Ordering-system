<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
if (!is_logged_in()) header('Location: /public/login.php');
refresh_user_session($pdo);
$u = current_user();

if ($u['role'] !== 'admin') {
    echo 'Access denied';
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock_qty = $_POST['stock_qty'];

    // ✅ Check if name already exists (case-insensitive)
    $check = $pdo->prepare("SELECT COUNT(*) FROM inventory WHERE LOWER(name) = LOWER(?)");
    $check->execute([$name]);
    $exists = $check->fetchColumn();

    if ($exists) {
        $error = "⚠️ The product name '<strong>" . htmlspecialchars($name) . "</strong>' already exists. Please use a different name or navigate to search bar to edit";
    } else {
        // ✅ Insert new product
        $stmt = $pdo->prepare("INSERT INTO inventory (name, description, price, stock_qty) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $description, $price, $stock_qty]);
        header('Location: inventory.php');
        exit;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Add Inventory Item - i-Tea Ordering</title>
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
.btn-success {
  background-color: #8a9a5b;
  border: none;
}
.btn-success:hover {
  background-color: #7c8b52;
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
  <h4 class="mb-4">➕ Add New Item</h4>
  <div class="card shadow-sm">
    <div class="card-header bg-success bg-opacity-25 fw-semibold">Add Inventory Item</div>
    <div class="card-body">

      <?php if ($error): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
          <?= $error ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <form method="post" class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Name</label>
          <input name="name" class="form-control" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Price</label>
          <input name="price" type="number" step="0.01" class="form-control" value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" required>
        </div>
        <div class="col-12">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>
        <div class="col-md-4">
          <label class="form-label">Stock Qty</label>
          <input name="stock_qty" type="number" class="form-control" value="<?= htmlspecialchars($_POST['stock_qty'] ?? '') ?>" required>
        </div>
        <div class="col-12">
          <button class="btn btn-success">Save Item</button>
          <a href="inventory.php" class="btn btn-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
