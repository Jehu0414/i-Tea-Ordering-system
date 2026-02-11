<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!is_logged_in()) header('Location: /public/login.php');
refresh_user_session($pdo);
$u = current_user();
if ($u['role'] !== 'admin') { echo 'Access denied'; exit; }

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;

// === CREATE or UPDATE ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'] ?? '';
    $full = $_POST['full_name'];
    $role = $_POST['role'];
    $id = $_POST['id'] ?? '';

    if ($id) {
        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET username=?, password=?, full_name=?, role=? WHERE id=?");
            $stmt->execute([$username, $hash, $full, $role, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username=?, full_name=?, role=? WHERE id=?");
            $stmt->execute([$username, $full, $role, $id]);
        }
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username,password,full_name,role) VALUES (?,?,?,?)");
        $stmt->execute([$username, $hash, $full, $role]);
    }

    header('Location: users.php');
    exit;
}

// === DELETE (with self-protection) ===
if ($action === 'delete' && $id) {
    if ($id == $u['id']) {
        echo "<script>alert('You cannot delete your own account.'); window.location='users.php';</script>";
        exit;
    }
    $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
    $stmt->execute([$id]);
    header('Location: users.php');
    exit;
}

// === FETCH USERS ===
$users = $pdo->query("SELECT id,username,full_name,role,created_at FROM users ORDER BY id DESC")->fetchAll();

// === EDIT MODE ===
$editUser = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
    $stmt->execute([$id]);
    $editUser = $stmt->fetch();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>User Management - i - Tea Ordering</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    /* === Sage Green Admin Theme === */
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
    .welcome-msg {
      background: #c8d3c0;
      padding: 1rem;
      border-radius: 10px;
      text-align: center;
      color: #2f3b2f;
      font-weight: 500;
      animation: fadeOut 1s ease 3s forwards;
    }
    @keyframes fadeOut { to { opacity: 0; visibility: hidden; } }
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
        <a href="profile.php" >Profile</a>
        <a href="dashboard.php">Dashboard</a>
        <a href="inventory.php">Inventory</a>
        <a href="users.php"class="active">Users</a>
        <a href="ordering.php">Create Order</a>
        <a href="order.php">Orders</a>

      <?php elseif ($u['role'] === 'staff'): ?>
       <a href="profile.php">Profile</a>
        <a href="inventory.php">Inventory</a>
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


    <h4 class="mb-4">User Management</h4>

    <!-- USERS TABLE -->
    <div class="card p-3">
      <h6>Existing Users</h6>
      <table class="table table-striped align-middle mt-2">
        <thead class="table-success">
          <tr>
            <th>Username</th>
            <th>Full Name</th>
            <th>Role</th>
            <th>Created</th>
            <th width="150">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $usr): ?>
          <tr>
            <td><?= htmlspecialchars($usr['username']) ?></td>
            <td><?= htmlspecialchars($usr['full_name']) ?></td>
            <td><span class="badge bg-<?= $usr['role']==='admin'?'dark':'secondary' ?>"><?= htmlspecialchars($usr['role']) ?></span></td>
            <td><?= htmlspecialchars($usr['created_at']) ?></td>
            <td>
              <a href="?action=edit&id=<?= $usr['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
              <?php if ($usr['id'] != $u['id']): ?>
                <a href="?action=delete&id=<?= $usr['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this user?')">Delete</a>
              <?php else: ?>
                <button class="btn btn-sm btn-secondary" disabled>Protected</button>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- ADD/EDIT FORM -->
    <div class="card p-3">
      <h6><?= $editUser ? 'Edit User' : 'Create New User' ?></h6>
      <form method="post" class="row g-3 mt-2">
        <input type="hidden" name="id" value="<?= htmlspecialchars($editUser['id'] ?? '') ?>">
        <div class="col-md-3">
          <label class="form-label">Username</label>
          <input name="username" class="form-control" required value="<?= htmlspecialchars($editUser['username'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label"><?= $editUser ? 'New Password (optional)' : 'Password' ?></label>
          <input name="password" type="password" class="form-control" <?= $editUser ? '' : 'required' ?>>
        </div>
        <div class="col-md-3">
          <label class="form-label">Full Name</label>
          <input name="full_name" class="form-control" value="<?= htmlspecialchars($editUser['full_name'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Role</label>
          <select name="role" class="form-select">
            <option value="staff" <?= ($editUser['role'] ?? '')==='staff'?'selected':'' ?>>Staff</option>
            <option value="admin" <?= ($editUser['role'] ?? '')==='admin'?'selected':'' ?>>Admin</option>
          </select>
        </div>
        <div class="col-12">
          <button class="btn btn-sage"><?= $editUser ? 'Update User' : 'Create User' ?></button>
          <?php if ($editUser): ?>
            <a href="users.php" class="btn btn-secondary">Cancel</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <script>
    setTimeout(() => document.getElementById('welcome')?.remove(), 3000);
  </script>
</body>
</html>
