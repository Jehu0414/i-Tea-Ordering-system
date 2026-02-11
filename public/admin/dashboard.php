<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!is_logged_in()) header('Location: /Ordering/public/login.php');
refresh_user_session($pdo);
$u = current_user();

// ======== FETCH REAL DATA ========

// Total users, orders, inventory
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_inventory = $pdo->query("SELECT COUNT(*) FROM inventory")->fetchColumn();

// Sales per product for bar/pie charts
$stmt = $pdo->query("
  SELECT i.name AS product, SUM(oi.line_total) AS total_sales
  FROM order_items oi
  JOIN inventory i ON oi.inventory_id = i.id
  GROUP BY i.id
  ORDER BY total_sales DESC
");
$products = [];
$sales = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $products[] = $row['product'];
  $sales[] = (float)$row['total_sales'];
}

// Monthly sales for line chart (last 6 months)
$stmt2 = $pdo->query("
  SELECT DATE_FORMAT(created_at, '%b') AS month, SUM(total_amount) AS total
  FROM orders
  WHERE status = 'completed'
  GROUP BY YEAR(created_at), MONTH(created_at)
  ORDER BY created_at DESC
  LIMIT 6
");
$months = [];
$month_sales = [];
while ($r = $stmt2->fetch(PDO::FETCH_ASSOC)) {
  array_unshift($months, $r['month']); // keep in chronological order
  array_unshift($month_sales, (float)$r['total']);
}

// Normalize chart data so total = 100%
$total_sales_sum = array_sum($sales) ?: 1;
$sales_percent = array_map(fn($v) => round(($v / $total_sales_sum) * 100, 2), $sales);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Dashboard - i - Tea Ordering</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    .chart-container {
      background: #fff;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      padding: 1rem;
      margin-bottom: 1.5rem;
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
    @keyframes fadeOut {
      to { opacity: 0; visibility: hidden; }
    }
  </style>
</head>
<body>
 <!-- Sidebar -->
  <div class="sidebar">
    <div>
      <h5 class="text-center mb-4">🍵 i -  Tea <?= ucfirst($u['role']) ?></h5>

      <?php if ($u['role'] === 'admin'): ?>
        <a href="profile.php" >Profile</a>
        <a href="dashboard.php"class="active">Dashboard</a>
        <a href="inventory.php">Inventory</a>
        <a href="users.php">Users</a>
        <a href="ordering.php">Create Order</a>
        <a href="order.php">Orders</a>

      <?php elseif ($u['role'] === 'staff'): ?>
       <a href="profile.php" >Profile</a>
        <a href="inventory.php"class="active">Inventory</a>
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
    <div id="welcome" class="welcome-msg">
      👋 Welcome, <?= htmlspecialchars($u['full_name'] ?? $u['username']) ?>!
    </div>

    <h4 class="mb-4">Dashboard Overview</h4>

    <div class="row mb-4 text-center">
      <div class="col-md-4">
        <div class="chart-container p-3">
          <h6>Total Users</h6>
          <h3><?= $total_users ?></h3>
        </div>
      </div>
      <div class="col-md-4">
        <div class="chart-container p-3">
          <h6>Total Orders</h6>
          <h3><?= $total_orders ?></h3>
        </div>
      </div>
      <div class="col-md-4">
        <div class="chart-container p-3">
          <h6>Total Products</h6>
          <h3><?= $total_inventory ?></h3>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-4">
        <div class="chart-container">
          <h6 class="text-center">Sales per Product (Bar)</h6>
          <canvas id="barChart"></canvas>
        </div>
      </div>
      <div class="col-md-4">
        <div class="chart-container">
          <h6 class="text-center">Monthly Sales Trend (Line)</h6>
          <canvas id="lineChart"></canvas>
        </div>
      </div>
      <div class="col-md-4">
        <div class="chart-container">
          <h6 class="text-center">Sales Share (Pie)</h6>
          <canvas id="pieChart"></canvas>
        </div>
      </div>
    </div>
  </div>

  <!-- Chart.js Script -->
  <script>
  const products = <?= json_encode($products) ?>;
  const sales = <?= json_encode($sales) ?>;
  const salesPercent = <?= json_encode($sales_percent) ?>;
  const months = <?= json_encode($months) ?>;
  const monthSales = <?= json_encode($month_sales) ?>;

  // Bar Chart
  new Chart(document.getElementById('barChart'), {
    type: 'bar',
    data: {
      labels: products,
      datasets: [{
        label: 'Sales (₱)',
        data: sales,
        backgroundColor: ['#8a9a5b', '#a3b18a', '#c8d3c0', '#e6e8e3']
      }]
    },
    options: {
      plugins: { legend: { display: false } },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: value => '₱' + value.toLocaleString()
          }
        }
      }
    }
  });

  // Line Chart
  new Chart(document.getElementById('lineChart'), {
    type: 'line',
    data: {
      labels: months,
      datasets: [{
        label: 'Sales (₱)',
        data: monthSales,
        borderColor: '#8a9a5b',
        backgroundColor: 'rgba(138,154,91,0.3)',
        fill: true,
        tension: 0.3
      }]
    },
    options: { plugins: { legend: { display: false } } }
  });

  // ✅ Pie Chart — with percent + value in tooltip and labels
  new Chart(document.getElementById('pieChart'), {
    type: 'pie',
    data: {
      labels: products.map((p, i) => `${p} (${salesPercent[i]}% / ₱${sales[i].toLocaleString()})`),
      datasets: [{
        data: sales,
        backgroundColor: ['#8a9a5b', '#a3b18a', '#c8d3c0', '#e6e8e3']
      }]
    },
    options: {
      plugins: {
        tooltip: {
          callbacks: {
            label: function(context) {
              const label = context.label || '';
              const value = context.raw || 0;
              const percent = salesPercent[context.dataIndex];
              return `${label}: ₱${value.toLocaleString()} (${percent}%)`;
            }
          }
        },
        legend: {
          position: 'bottom',
          labels: {
            boxWidth: 14,
            font: { size: 12 }
          }
        }
      }
    }
  });

  // Auto-hide welcome message
  setTimeout(() => {
    const msg = document.getElementById('welcome');
    if (msg) msg.style.display = 'none';
  }, 3000);
</script>

</body>
</html>
