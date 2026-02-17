<?php
session_start();
include 'db.php';

// Redirect if cart is empty
if (empty($_SESSION['cart'])) {
    header("Location: index.php");
    exit();
}

// Handle order submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_name = $_POST['customer_name'];
    $customer_phone = $_POST['customer_phone'];
    $customer_email = $_POST['customer_email'];
    
    // Calculate total
    $total_price = 0;
    $item_ids = array_keys($_SESSION['cart']);
    $ids_string = implode(',', $item_ids);
    
    $sql = "SELECT * FROM menu_items WHERE item_id IN ($ids_string)";
    $result = $conn->query($sql);
    
    while ($item = $result->fetch_assoc()) {
        $quantity = $_SESSION['cart'][$item['item_id']];
        $total_price += $item['price'] * $quantity;
    }
    
    // Create order
    $sql = "INSERT INTO orders (customer_name, customer_phone, customer_email, total_amount) 
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssd", $customer_name, $customer_phone, $customer_email, $total_price);
    $stmt->execute();
    $order_id = $conn->insert_id;
    
    // Add order items
    $result->data_seek(0); // Reset result pointer
    while ($item = $result->fetch_assoc()) {
        $quantity = $_SESSION['cart'][$item['item_id']];
        $sql = "INSERT INTO order_items (order_id, item_id, quantity, price) 
                VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiid", $order_id, $item['item_id'], $quantity, $item['price']);
        $stmt->execute();
    }
    
    // Clear cart
    $_SESSION['cart'] = [];
    
    // Redirect to order confirmation
    header("Location: order_confirmation.php?order_id=$order_id");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Fast Food Express</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .navbar { background-color: #dc3545; }
    </style>
</head>
<body>
    <!-- Navigation (same as index.php) -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-hamburger"></i> smart dite food 
            </a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">
                            <i class="fas fa-shopping-cart"></i> Cart
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8">
                <h1>Checkout</h1>
                <form method="post" action="">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4>Contact Information</h4>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="customer_name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="customer_name" name="customer_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="customer_phone" class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control" id="customer_phone" name="customer_phone" required>
                            </div>
                            <div class="mb-3">
                                <label for="customer_email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="customer_email" name="customer_email">
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-danger btn-lg">
                        <i class="fas fa-credit-card"></i> Place Order
                    </button>
                </form>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h4>Order Summary</h4>
                    </div>
                    <div class="card-body">
                        <?php
                        $total_price = 0;
                        $item_ids = array_keys($_SESSION['cart']);
                        $ids_string = implode(',', $item_ids);
                        
                        $sql = "SELECT * FROM menu_items WHERE item_id IN ($ids_string)";
                        $result = $conn->query($sql);
                        
                        while ($item = $result->fetch_assoc()) {
                            $quantity = $_SESSION['cart'][$item['item_id']];
                            $subtotal = $item['price'] * $quantity;
                            $total_price += $subtotal;
                            
                            echo '<div class="d-flex justify-content-between mb-2">';
                            echo '<span>' . htmlspecialchars($item['name']) . ' x ' . $quantity . '</span>';
                            echo '<span>$' . number_format($subtotal, 2) . '</span>';
                            echo '</div>';
                        }
                        ?>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <strong>Total:</strong>
                            <strong>$<?php echo number_format($total_price, 2); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>