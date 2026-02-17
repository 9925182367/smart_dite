<?php
session_start();
include 'db.php';

// Handle cart updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['quantities'] as $item_id => $quantity) {
            if ($quantity > 0) {
                $_SESSION['cart'][$item_id] = (int)$quantity;
            } else {
                unset($_SESSION['cart'][$item_id]);
            }
        }
    } elseif (isset($_POST['checkout'])) {
        header("Location: checkout.php");
        exit();
    }
}

// Get cart items with details
$cart_items = [];
$total_price = 0;

if (!empty($_SESSION['cart'])) {
    $item_ids = array_keys($_SESSION['cart']);
    $ids_string = implode(',', $item_ids);
    
    $sql = "SELECT * FROM menu_items WHERE item_id IN ($ids_string)";
    $result = $conn->query($sql);
    
    while ($item = $result->fetch_assoc()) {
        $quantity = $_SESSION['cart'][$item['item_id']];
        $item['quantity'] = $quantity;
        $item['subtotal'] = $item['price'] * $quantity;
        $cart_items[] = $item;
        $total_price += $item['subtotal'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - Fast Food Express</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .navbar { background-color: #dc3545; }
        .cart-item { margin-bottom: 15px; }
        .cart-item img { width: 80px; height: 80px; object-fit: cover; }
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
        <h1>Your Shopping Cart</h1>
        
        <?php if (empty($cart_items)): ?>
            <div class="alert alert-info">
                Your cart is empty. <a href="index.php">Continue shopping</a>
            </div>
        <?php else: ?>
            <form method="post" action="">
                <div class="row">
                    <div class="col-md-8">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="card cart-item">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-2">
                                            <?php if (!empty($item['image_url'])): ?>
                                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                            <?php else: ?>
                                                <img src="https://via.placeholder.com/80x80?text=No+Image" alt="No Image">
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-6">
                                            <h5><?php echo htmlspecialchars($item['name']); ?></h5>
                                            <p class="text-danger">$<?php echo number_format($item['price'], 2); ?> each</p>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="input-group">
                                                <input type="number" name="quantities[<?php echo $item['item_id']; ?>]" 
                                                       value="<?php echo $item['quantity']; ?>" min="0" class="form-control">
                                                <span class="input-group-text">= $<?php echo number_format($item['subtotal'], 2); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h4>Order Summary</h4>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Total:</span>
                                    <strong>$<?php echo number_format($total_price, 2); ?></strong>
                                </div>
                                <hr>
                                <div class="d-grid gap-2">
                                    <button type="submit" name="update_cart" class="btn btn-outline-primary">
                                        <i class="fas fa-sync"></i> Update Cart
                                    </button>
                                    <button type="submit" name="checkout" class="btn btn-danger">
                                        <i class="fas fa-credit-card"></i> Proceed to Checkout
                                    </button>
                                    <a href="index.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left"></i> Continue Shopping
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>