<?php
session_start();
include 'db.php';

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle add to cart
if (isset($_POST['add_to_cart'])) {
    $item_id = $_POST['item_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    if (isset($_SESSION['cart'][$item_id])) {
        $_SESSION['cart'][$item_id] += $quantity;
    } else {
        $_SESSION['cart'][$item_id] = $quantity;
    }

    header("Location: index.php");
    exit();
}

// Get categories
$categories_sql = "SELECT * FROM categories ORDER BY name";
$categories_result = $conn->query($categories_sql);

// Get menu items
$menu_sql = "SELECT mi.*, c.name as category_name 
             FROM menu_items mi 
             LEFT JOIN categories c ON mi.category_id = c.category_id 
             WHERE mi.available = 1 
             ORDER BY c.name, mi.name";
$menu_result = $conn->query($menu_sql);

// Calculate cart totals
$total_items = 0;
foreach ($_SESSION['cart'] as $item_id => $quantity) {
    $total_items += $quantity;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Dite Food</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .navbar { background-color: #dc3545; }
        .category-section { margin-bottom: 30px; }
        .menu-item { margin-bottom: 20px; transition: transform 0.3s; }
        .menu-item:hover { transform: translateY(-5px); }
        .menu-item img { height: 200px; object-fit: cover; }
        .cart-badge { background-color: #ffc107; color: #000; border-radius: 50%; padding: 2px 6px; font-size: 0.8em; }
        .category-title { border-bottom: 2px solid #dc3545; padding-bottom: 10px; margin-bottom: 20px; }
    </style>
</head>
<body>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-hamburger"></i> Smart Dite Food
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <!-- Login/Signup or Greeting + Logout -->
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="signup.php"><i class="fas fa-user-plus"></i> Sign Up</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <span class="nav-link">Hi, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </li>
                <?php endif; ?>
                
                <!-- Cart -->
                <li class="nav-item">
                    <a class="nav-link" href="cart.php">
                        <i class="fas fa-shopping-cart"></i> Cart
                        <?php if ($total_items > 0): ?>
                            <span class="cart-badge"><?php echo $total_items; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Page Content -->
<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h1 class="text-center mb-4">Our Menu</h1>
        </div>
    </div>

    <div class="row">
        <?php
        // Group items by category
        $menu_by_category = [];
        if ($menu_result->num_rows > 0) {
            while ($item = $menu_result->fetch_assoc()) {
                $category_name = $item['category_name'] ?: 'Other';
                if (!isset($menu_by_category[$category_name])) {
                    $menu_by_category[$category_name] = [];
                }
                $menu_by_category[$category_name][] = $item;
            }
        }

        // Display items by category
        foreach ($menu_by_category as $category_name => $items):
        ?>
            <div class="col-12 category-section">
                <h2 class="category-title"><?php echo htmlspecialchars($category_name); ?></h2>
                <div class="row">
                    <?php foreach ($items as $item): ?>
                        <div class="col-md-4 menu-item">
                            <div class="card h-100">
                                <?php if (!empty($item['image_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                <?php else: ?>
                                    <img src="https://via.placeholder.com/300x200?text=No+Image" class="card-img-top" alt="No Image">
                                <?php endif; ?>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title"><?php echo htmlspecialchars($item['name']); ?></h5>
                                    <p class="card-text"><?php echo htmlspecialchars($item['description']); ?></p>
                                    <div class="mt-auto">
                                        <h4 class="text-danger">$<?php echo number_format($item['price'], 2); ?></h4>
                                        <form method="post" action="" class="d-inline">
                                            <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" name="add_to_cart" class="btn btn-danger">
                                                <i class="fas fa-plus"></i> Add to Cart
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
