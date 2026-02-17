<?php
session_start();
include 'db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simple authentication
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

// Handle status updates
if (isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    
    // Check if the order exists first
    $check_sql = "SELECT order_id FROM orders WHERE order_id = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Order exists, update status
        $sql = "UPDATE orders SET status = ? WHERE order_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $status, $order_id);
        
        if ($stmt->execute()) {
            // Success
            header("Location: orders.php?success=1");
            exit();
        } else {
            // Error
            header("Location: orders.php?error=1");
            exit();
        }
    } else {
        // Order doesn't exist
        header("Location: orders.php?error=2");
        exit();
    }
}

// Get all orders
$sql = "SELECT * FROM orders ORDER BY order_date DESC";
$orders_result = $conn->query($sql);

// Check for errors in query
if (!$orders_result) {
    die("Query failed: " . $conn->error);
}

// Store orders in an array to avoid multiple data_seek calls
$orders = [];
if ($orders_result->num_rows > 0) {
    while ($order = $orders_result->fetch_assoc()) {
        $orders[] = $order;
    }
}

// Calculate statistics
$total_orders = count($orders);
$pending_count = 0;
$preparing_count = 0;
$completed_count = 0;

foreach ($orders as $order) {
    if ($order['status'] == 'pending') {
        $pending_count++;
    } elseif ($order['status'] == 'preparing') {
        $preparing_count++;
    } elseif ($order['status'] == 'delivered') {
        $completed_count++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { background-color: #343a40; min-height: 100vh; }
        .sidebar .nav-link { color: #fff; padding: 10px 15px; }
        .sidebar .nav-link:hover { background-color: #495057; }
        .sidebar .nav-link.active { background-color: #dc3545; }
        .order-row:hover { background-color: #f8f9fa; }
        .status-badge { font-size: 0.85rem; }
        .btn-update { font-size: 0.8rem; }
        .main-content { padding: 20px; }
        .card { border: none; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075); }
        .card-header { background-color: #f8f9fa; border-bottom: 1px solid rgba(0,0,0,.125); }
        .alert-container { position: fixed; top: 20px; right: 20px; z-index: 1050; width: 300px; }
    </style>
</head>
<body>
    <!-- Alert Container -->
    <div class="alert-container">
        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> Order status updated successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> 
                <?php 
                if ($_GET['error'] == 1) {
                    echo "Failed to update order status.";
                } elseif ($_GET['error'] == 2) {
                    echo "Order not found.";
                } else {
                    echo "An error occurred.";
                }
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="p-3 text-white">
                    <h4><i class="fas fa-utensils"></i> smart diet food </h4>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="orders.php">
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
                        <a class="nav-link" href="reports.php">
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
                    <h1><i class="fas fa-clipboard-list me-2"></i> Manage Orders</h1>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                            <i class="fas fa-print"></i> Print
                        </button>
                        <button class="btn btn-outline-success btn-sm" onclick="exportOrders()">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Total Orders</h6>
                                        <h3 class="mb-0"><?php echo $total_orders; ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-shopping-cart fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Pending</h6>
                                        <h3 class="mb-0"><?php echo $pending_count; ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-clock fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Preparing</h6>
                                        <h3 class="mb-0"><?php echo $preparing_count; ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-fire fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Completed</h6>
                                        <h3 class="mb-0"><?php echo $completed_count; ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-check-circle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Orders Table -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">All Orders</h5>
                        <div class="d-flex gap-2">
                            <input type="text" class="form-control form-control-sm" id="searchOrders" placeholder="Search orders..." style="width: 200px;">
                            <select class="form-select form-select-sm" id="filterStatus" style="width: 150px;">
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="preparing">Preparing</option>
                                <option value="ready">Ready</option>
                                <option value="delivered">Delivered</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($orders)): ?>
                            <div class="text-center p-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <h5>No orders found</h5>
                                <p class="text-muted">There are no orders in the system yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0" id="ordersTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Customer</th>
                                            <th>Contact</th>
                                            <th>Date & Time</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $order): ?>
                                            <tr class="order-row" data-status="<?php echo $order['status']; ?>">
                                                <td>
                                                    <strong>#<?php echo str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?></strong>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 35px; height: 35px;">
                                                            <i class="fas fa-user text-white"></i>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div>
                                                        <i class="fas fa-phone text-muted me-1"></i>
                                                        <?php echo htmlspecialchars($order['customer_phone']); ?>
                                                    </div>
                                                    <?php if (!empty($order['customer_email'])): ?>
                                                        <div class="small text-muted">
                                                            <i class="fas fa-envelope me-1"></i>
                                                            <?php echo htmlspecialchars($order['customer_email']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="fw-bold"><?php echo date('M j, Y', strtotime($order['order_date'])); ?></div>
                                                    <div class="small text-muted"><?php echo date('g:i A', strtotime($order['order_date'])); ?></div>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-success">$<?php echo number_format($order['total_amount'], 2); ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge status-badge 
                                                        <?php 
                                                        echo $order['status'] == 'pending' ? 'bg-warning' : 
                                                             ($order['status'] == 'preparing' ? 'bg-info' : 
                                                             ($order['status'] == 'ready' ? 'bg-primary' : 'bg-success')); 
                                                        ?>">
                                                        <?php echo ucfirst($order['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewOrderModal<?php echo $order['order_id']; ?>">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        
                                                        <form method="post" action="" class="d-inline">
                                                            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                            <select name="status" class="form-select form-select-sm d-inline-block" style="width: auto;">
                                                                <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                                <option value="preparing" <?php echo $order['status'] == 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                                                                <option value="ready" <?php echo $order['status'] == 'ready' ? 'selected' : ''; ?>>Ready</option>
                                                                <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                            </select>
                                                            <button type="submit" name="update_status" class="btn btn-sm btn-success btn-update">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                            
                                            <!-- View Order Modal -->
                                            <div class="modal fade" id="viewOrderModal<?php echo $order['order_id']; ?>" tabindex="-1">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Order Details #<?php echo str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <h6>Customer Information</h6>
                                                                    <p><strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                                                                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
                                                                    <?php if (!empty($order['customer_email'])): ?>
                                                                        <p><strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?></p>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <h6>Order Information</h6>
                                                                    <p><strong>Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($order['order_date'])); ?></p>
                                                                    <p><strong>Status:</strong> 
                                                                        <span class="badge 
                                                                            <?php 
                                                                            echo $order['status'] == 'pending' ? 'bg-warning' : 
                                                                                 ($order['status'] == 'preparing' ? 'bg-info' : 
                                                                                 ($order['status'] == 'ready' ? 'bg-primary' : 'bg-success')); 
                                                                            ?>">
                                                                            <?php echo ucfirst($order['status']); ?>
                                                                        </span>
                                                                    </p>
                                                                    <p><strong>Total:</strong> $<?php echo number_format($order['total_amount'], 2); ?></p>
                                                                </div>
                                                            </div>
                                                            
                                                            <hr>
                                                            
                                                            <h6>Order Items</h6>
                                                            <?php
                                                            // Get order items
                                                            $items_sql = "SELECT oi.*, mi.name as item_name 
                                                                         FROM order_items oi 
                                                                         JOIN menu_items mi ON oi.item_id = mi.item_id 
                                                                         WHERE oi.order_id = ?";
                                                            $stmt = $conn->prepare($items_sql);
                                                            $stmt->bind_param("i", $order['order_id']);
                                                            $stmt->execute();
                                                            $items_result = $stmt->get_result();
                                                            
                                                            // Check for errors
                                                            if (!$items_result) {
                                                                echo "<div class='alert alert-danger'>Error loading order items: " . $conn->error . "</div>";
                                                            } else {
                                                            ?>
                                                                <div class="table-responsive">
                                                                    <table class="table table-sm">
                                                                        <thead>
                                                                            <tr>
                                                                                <th>Item</th>
                                                                                <th>Quantity</th>
                                                                                <th>Price</th>
                                                                                <th>Total</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            <?php while ($item = $items_result->fetch_assoc()): ?>
                                                                                <tr>
                                                                                    <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                                                                    <td><?php echo $item['quantity']; ?></td>
                                                                                    <td>$<?php echo number_format($item['price'], 2); ?></td>
                                                                                    <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                                                                </tr>
                                                                            <?php endwhile; ?>
                                                                        </tbody>
                                                                        <tfoot>
                                                                            <tr>
                                                                                <th colspan="3">Total</th>
                                                                                <th>$<?php echo number_format($order['total_amount'], 2); ?></th>
                                                                            </tr>
                                                                        </tfoot>
                                                                    </table>
                                                                </div>
                                                            <?php } ?>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            <button type="button" class="btn btn-primary" onclick="window.print()">
                                                                <i class="fas fa-print"></i> Print Order
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Search functionality
        document.getElementById('searchOrders').addEventListener('keyup', function() {
            let searchValue = this.value.toLowerCase();
            let rows = document.querySelectorAll('#ordersTable tbody tr');
            
            rows.forEach(row => {
                let text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchValue) ? '' : 'none';
            });
        });
        
        // Filter by status
        document.getElementById('filterStatus').addEventListener('change', function() {
            let filterValue = this.value;
            let rows = document.querySelectorAll('#ordersTable tbody tr');
            
            rows.forEach(row => {
                if (filterValue === '') {
                    row.style.display = '';
                } else {
                    row.style.display = row.getAttribute('data-status') === filterValue ? '' : 'none';
                }
            });
        });
        
        // Export orders function
        function exportOrders() {
            alert('Export functionality would be implemented here. This would generate a CSV or Excel file of all orders.');
        }
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            let alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                let bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>