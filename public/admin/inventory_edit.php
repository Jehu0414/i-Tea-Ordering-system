<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
if (!is_logged_in()) header('Location: /public/login.php');
refresh_user_session($pdo);
$u = current_user();

// ✅ Restrict to admin only
if ($u['role'] !== 'admin') {
    echo 'Access denied';
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) { header('Location: inventory.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM inventory WHERE id=?");
$stmt->execute([$id]);
$item = $stmt->fetch();
if (!$item) { echo 'Item not found'; exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("UPDATE inventory SET name=?, description=?, price=?, updated_at=NOW() WHERE id=?");
    $stmt->execute([
        $_POST['name'],
        $_POST['description'],
        $_POST['price'],
        $id
    ]);
    header('Location: inventory.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Edit Inventory Item - i-Tea Ordering</title>
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
  <h4 class="mb-4">✏️ Edit Item</h4>
  <div class="card shadow-sm">
    <div class="card-header bg-success bg-opacity-25 fw-semibold">Edit Inventory Item</div>
    <div class="card-body">
      <form method="post" class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Name</label>
          <input name="name" class="form-control" value="<?= htmlspecialchars($item['name']) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Price</label>
          <input name="price" type="number" step="0.01" class="form-control" value="<?= htmlspecialchars($item['price']) ?>" required>
        </div>
        <div class="col-12">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control"><?= htmlspecialchars($item['description']) ?></textarea>
        </div>
        <div class="col-12">
          <button class="btn btn-success">Update Item</button>
          <a href="inventory.php" class="btn btn-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>

</body>
</html>
