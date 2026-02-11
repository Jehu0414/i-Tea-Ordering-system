<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!is_logged_in()) header('Location: /public/login.php');
refresh_user_session($pdo);
$u = current_user();

// Fetch active items from inventory
$items = $pdo->prepare("SELECT id, name, price, stock_qty FROM inventory WHERE is_active=1 ORDER BY name");
$items->execute();
$items = $items->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Create Order - i - Tea Ordering</title>
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
    input[type="number"] {
      width: 70px;
      text-align: center;
    }
    #searchInput {
      max-width: 350px;
      border: 2px solid #8a9a5b;
      border-radius: 8px;
    }
    .text-danger {
      color: #b22222 !important;
      font-weight: bold;
    }
    .badge-out {
      background-color: #dc3545;
      color: white;
      font-size: 0.85rem;
      padding: 4px 10px;
      border-radius: 20px;
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
        <a href="inventory.php">Inventory</a>
        <a href="users.php">Users</a>
        <a href="ordering.php" class="active">Create Order</a>
        <a href="order.php">Orders</a>
      <?php elseif ($u['role'] === 'staff'): ?>
        <a href="profile.php">Profile</a>
        <a href="inventory.php">Inventory</a>
        <a href="ordering.php" class="active">Create Order</a>
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
    <h4 class="mb-4">Create New Order</h4>

    <div class="card p-4">
      <form method="post" action="process-order.php" id="orderForm">

        <!-- Customer Name + Submit Button in one row -->
        <div class="mb-3 d-flex align-items-end gap-3">
          <div class="flex-grow-1">
            <label class="form-label">Customer Name</label>
            <input name="customer_name" id="customer_name" class="form-control" placeholder="Enter customer name" required>
          </div>
          <div>
            <button class="btn btn-sage btn-lg mt-2">Submit Order</button>
          </div>
        </div>

        <!-- Search bar -->
        <div class="mb-3 d-flex justify-content-start align-items-center gap-2">
          <h6 class="m-0">Available Items</h6>
          <input type="text" id="searchInput" class="form-control" placeholder="Search item name, price, or stock..." style="max-width: 300px;">
        </div>

        <table class="table table-bordered align-middle text-center" id="itemsTable">
          <thead class="table-success">
            <tr>
              <th>Item</th>
              <th>Price (₱)</th>
              <th>Available</th>
              <th>Qty</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($items as $it): ?>
            <tr>
              <td class="text-start"><?= htmlspecialchars($it['name']) ?></td>
              <td><?= number_format($it['price'], 2) ?></td>
              <td class="stock-cell">
                <?= (int)$it['stock_qty'] > 0 ? (int)$it['stock_qty'] : '<span class="badge-out">Out of Stock</span>' ?>
              </td>
              <td>
                <div class="input-group input-group-sm mx-auto" style="width:140px;">
                  <button type="button" class="btn btn-sage btn-minus" data-id="<?= $it['id'] ?>">−</button>
                  <input type="number"
                         name="qty[<?= $it['id'] ?>]"
                         value="0"
                         min="0"
                         max="<?= $it['stock_qty'] ?>"
                         class="form-control text-center qty-input"
                         data-id="<?= $it['id'] ?>"
                         <?= $it['stock_qty'] == 0 ? 'disabled' : '' ?>>
                  <button type="button" class="btn btn-sage btn-add" data-id="<?= $it['id'] ?>">+</button>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

      </form>
    </div>
  </div>

  <script>
    // === Order Form Validation ===
    document.getElementById('orderForm').addEventListener('submit', function(e) {
      const name = document.getElementById('customer_name').value.trim();
      const qtyInputs = document.querySelectorAll('.qty-input');
      let hasOrder = false;

      if (name.length < 3) {
        alert('Customer name must be at least 3 letters long.');
        e.preventDefault();
        return;
      }

      qtyInputs.forEach(input => {
        if (parseInt(input.value) > 0) hasOrder = true;
      });

      if (!hasOrder) {
        alert('Please select at least one item to order.');
        e.preventDefault();
      }
    });

    // === Live Search ===
    document.getElementById('searchInput').addEventListener('keyup', function() {
      const filter = this.value.toLowerCase();
      const rows = document.querySelectorAll('#itemsTable tbody tr');
      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
      });
    });

    // === Add / Minus Buttons ===
    document.querySelectorAll('.btn-add, .btn-minus').forEach(btn => {
      btn.addEventListener('click', function() {
        const id = this.dataset.id;
        const input = document.querySelector(`.qty-input[data-id="${id}"]`);
        const stockCell = this.closest('tr').querySelector('.stock-cell');
        let currentQty = parseInt(input.value) || 0;
        let stockQty = parseInt(stockCell.textContent) || 0;
        const isAdd = this.classList.contains('btn-add');

        if (isAdd && currentQty < input.max && stockQty > 0) {
          currentQty++;
          stockQty--;
          input.value = currentQty;
        } else if (!isAdd && currentQty > 0) {
          currentQty--;
          stockQty++;
          input.value = currentQty;
        } else {
          return;
        }

        stockCell.textContent = stockQty > 0 ? stockQty : '0';
      });
    });
  </script>
</body>
</html>
