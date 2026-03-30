<?php
session_start();
include '../db.php';

// Simple authentication
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

// Get date range for reports
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get sales data
$sales_sql = "SELECT DATE(order_date) as date, SUM(total_amount) as total_sales, COUNT(*) as order_count 
              FROM orders 
              WHERE order_date BETWEEN ? AND ? 
              GROUP BY DATE(order_date) 
              ORDER BY date";
$stmt = $conn->prepare($sales_sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$sales_result = $stmt->get_result();

// Get top selling items
$top_items_sql = "SELECT mi.name, SUM(oi.quantity) as total_sold, SUM(oi.quantity * oi.price) as revenue 
                  FROM order_items oi 
                  JOIN menu_items mi ON oi.item_id = mi.item_id 
                  JOIN orders o ON oi.order_id = o.order_id 
                  WHERE o.order_date BETWEEN ? AND ? 
                  GROUP BY mi.item_id 
                  ORDER BY total_sold DESC 
                  LIMIT 10";
$stmt = $conn->prepare($top_items_sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$top_items_result = $stmt->get_result();

// Get category performance
$category_sql = "SELECT c.name, COUNT(DISTINCT oi.order_id) as orders, SUM(oi.quantity) as items_sold, SUM(oi.quantity * oi.price) as revenue 
                 FROM categories c 
                 LEFT JOIN menu_items mi ON c.category_id = mi.category_id 
                 LEFT JOIN order_items oi ON mi.item_id = oi.item_id 
                 LEFT JOIN orders o ON oi.order_id = o.order_id 
                 WHERE (o.order_date BETWEEN ? AND ? OR o.order_date IS NULL) 
                 GROUP BY c.category_id 
                 ORDER BY revenue DESC";
$stmt = $conn->prepare($category_sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$category_result = $stmt->get_result();

// Get overall statistics
$total_sales_sql = "SELECT SUM(total_amount) as total_revenue, COUNT(*) as total_orders, AVG(total_amount) as avg_order_value 
                    FROM orders 
                    WHERE order_date BETWEEN ? AND ?";
$stmt = $conn->prepare($total_sales_sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$stats_result = $stmt->get_result();
$stats = $stats_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { background-color: #343a40; min-height: 100vh; }
        .sidebar .nav-link { color: #fff; padding: 10px 15px; }
        .sidebar .nav-link:hover { background-color: #495057; }
        .sidebar .nav-link.active { background-color: #dc3545; }
        .main-content { padding: 20px; }
        .card { border: none; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075); }
        .card-header { background-color: #f8f9fa; border-bottom: 1px solid rgba(0,0,0,.125); }
        .stat-card { transition: transform 0.3s; }
        .stat-card:hover { transform: translateY(-5px); }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="p-3 text-white">
                    <h4><i class="fas fa-utensils"></i>dite food Admin</h4>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">
                            <i class="fas fa-clipboard-list me-2"></i> Orders
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="menu.php">
                            <i class="fas fa-utensils me-2"></i> Menu Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="categories.php">
                            <i class="fas fa-tags me-2"></i> Categories
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="reports.php">
                            <i class="fas fa-chart-bar me-2"></i> Reports
                        </a>
                    </li>
                    <li class="nav-item mt-auto">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-chart-bar me-2"></i> Reports & Analytics</h1>
                    <button class="btn btn-primary" onclick="window.print()">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                </div>
                
                <!-- Date Range Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="get" action="" class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label">Start Date</label>
                                <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">End Date</label>
                                <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i> Apply Filter
                                </button>
                                <a href="reports.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-redo"></i> Reset
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Total Revenue</h6>
                                        <h3 class="mb-0">$<?php echo number_format($stats['total_revenue'] ?: 0, 2); ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-dollar-sign fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Total Orders</h6>
                                        <h3 class="mb-0"><?php echo $stats['total_orders'] ?: 0; ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-shopping-cart fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-info stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Avg Order Value</h6>
                                        <h3 class="mb-0">$<?php echo number_format($stats['avg_order_value'] ?: 0, 2); ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-chart-line fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Date Range</h6>
                                        <h3 class="mb-0"><?php echo (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24) + 1; ?></h3>
                                        <small>Days</small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-calendar fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Charts Row -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Sales Trend</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="salesChart" height="100"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Category Performance</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="categoryChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Top Selling Items -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Top Selling Items</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Item</th>
                                                <th>Units Sold</th>
                                                <th>Revenue</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($item = $top_items_result->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                                    <td><?php echo $item['total_sold']; ?></td>
                                                    <td>$<?php echo number_format($item['revenue'], 2); ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Category Performance</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Category</th>
                                                <th>Orders</th>
                                                <th>Items Sold</th>
                                                <th>Revenue</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($category = $category_result->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($category['name']); ?></td>
                                                    <td><?php echo $category['orders']; ?></td>
                                                    <td><?php echo $category['items_sold']; ?></td>
                                                    <td>$<?php echo number_format($category['revenue'], 2); ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sales Trend Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: [
                    <?php 
                    $sales_result->data_seek(0);
                    while ($sale = $sales_result->fetch_assoc()) {
                        echo "'" . date('M j', strtotime($sale['date'])) . "', ";
                    }
                    ?>
                ],
                datasets: [{
                    label: 'Daily Sales',
                    data: [
                        <?php 
                        $sales_result->data_seek(0);
                        while ($sale = $sales_result->fetch_assoc()) {
                            echo $sale['total_sales'] . ", ";
                        }
                        ?>
                    ],
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toFixed(0);
                            }
                        }
                    }
                }
            }
        });

        // Category Performance Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        const categoryChart = new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: [
                    <?php 
                    $category_result->data_seek(0);
                    while ($category = $category_result->fetch_assoc()) {
                        echo "'" . htmlspecialchars($category['name']) . "', ";
                    }
                    ?>
                ],
                datasets: [{
                    data: [
                        <?php 
                        $category_result->data_seek(0);
                        while ($category = $category_result->fetch_assoc()) {
                            echo $category['revenue'] . ", ";
                        }
                        ?>
                    ],
                    backgroundColor: [
                        '#FF6384',
                        '#36A2EB',
                        '#FFCE56',
                        '#4BC0C0',
                        '#9966FF',
                        '#FF9F40'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>