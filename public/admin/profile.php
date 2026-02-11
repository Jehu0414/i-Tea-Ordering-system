<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!is_logged_in()) header('Location: /public/login.php');
refresh_user_session($pdo);
$u = current_user();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Profile - i - Tea Ordering</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    /* === Sage Green Theme === */
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
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      position: fixed;
      top: 0;
      left: 0;
      height: 100vh;
      padding-top: 1.5rem;
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

    .card {
      background: #fff;
      border: none;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      margin-bottom: 1.5rem;
    }

    .btn-sage {
      background-color: #8a9a5b;
      color: #fff;
      border: none;
    }

    .btn-sage:hover {
      background-color: #7c8e54;
    }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <div class="sidebar">
    <div>
      <h5 class="text-center mb-4">🍵 i - Tea <?= ucfirst($u['role']) ?></h5>

      <?php if ($u['role'] === 'admin'): ?>
        <a href="profile.php" class="active">Profile</a>
        <a href="dashboard.php">Dashboard</a>
        <a href="inventory.php">Inventory</a>
        <a href="users.php">Users</a>
        <a href="ordering.php">Create Order</a>
        <a href="order.php">Orders</a>

      <?php elseif ($u['role'] === 'staff'): ?>
        <a href="profile.php" class="active">Profile</a>
        <a href="inventory.php">Inventory</a>
        <a href="ordering.php">Create Order</a>
        <a href="order.php">Orders</a>
      <?php endif; ?>
    </div>

    <!-- Footer (sticks at bottom) -->
    <div class="sidebar-footer">
      <p class="mb-1 small">Logged in as:</p>
      <strong><?= htmlspecialchars($u['full_name'] ?? $u['username']) ?></strong><br>
      <a href="/Ordering/public/logout.php" class="btn btn-light btn-sm mt-2">Logout</a>
    </div>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <h4 class="mb-4">My Profile</h4>

    <div class="card p-4 shadow-sm">
      <div class="row">
        <div class="col-md-6">
          <h6>Username:</h6>
          <p class="fw-semibold"><?= htmlspecialchars($u['username']) ?></p>
        </div>
        <div class="col-md-6">
          <h6>Full Name:</h6>
          <p class="fw-semibold"><?= htmlspecialchars($u['full_name'] ?: 'Not set') ?></p>
        </div>
        <div class="col-md-6">
          <h6>Role:</h6>
          <span class="badge <?= $u['role'] === 'admin' ? 'bg-success' : 'bg-secondary' ?>">
            <?= htmlspecialchars(ucfirst($u['role'])) ?>
          </span>
        </div>
      </div>
    </div>

    <div class="mt-3">
      <a href="<?= $u['role'] === 'admin' ? '/Ordering/public/admin/dashboard.php' : '/Ordering/public/staff/order.php' ?>" class="btn btn-sage">
        ← Back to <?= ucfirst($u['role']) ?> Panel
      </a>
    </div>
  </div>
</body>
</html>
