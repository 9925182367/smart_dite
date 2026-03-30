<?php
session_start();
include 'db.php';

// Simple authentication
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Add new category
    if (isset($_POST['add_category'])) {
        $category_name = $_POST['category_name'];
        $category_description = $_POST['category_description'];
        
        $sql = "INSERT INTO categories (name, description) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $category_name, $category_description);
        $stmt->execute();
        
        header("Location: categories.php");
        exit();
    }
    
    // Update category
    if (isset($_POST['update_category'])) {
        $category_id = $_POST['category_id'];
        $category_name = $_POST['category_name'];
        $category_description = $_POST['category_description'];
        
        $sql = "UPDATE categories SET name = ?, description = ? WHERE category_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $category_name, $category_description, $category_id);
        $stmt->execute();
        
        header("Location: categories.php");
        exit();
    }
    
    // Delete category
    if (isset($_POST['delete_category'])) {
        $category_id = $_POST['category_id'];
        
        // Check if category has menu items
        $check_sql = "SELECT COUNT(*) as count FROM menu_items WHERE category_id = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            $_SESSION['error'] = "Cannot delete category. It has menu items associated with it.";
        } else {
            $sql = "DELETE FROM categories WHERE category_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $category_id);
            $stmt->execute();
            $_SESSION['success'] = "Category deleted successfully.";
        }
        
        header("Location: categories.php");
        exit();
    }
}

// Get all categories
$categories_sql = "SELECT * FROM categories ORDER BY name";
$categories_result = $conn->query($categories_sql);

// Get menu items count for each category
$categories_with_counts = [];
if ($categories_result->num_rows > 0) {
    while ($category = $categories_result->fetch_assoc()) {
        $count_sql = "SELECT COUNT(*) as item_count FROM menu_items WHERE category_id = ?";
        $stmt = $conn->prepare($count_sql);
        $stmt->bind_param("i", $category['category_id']);
        $stmt->execute();
        $count_result = $stmt->get_result();
        $count_row = $count_result->fetch_assoc();
        
        $category['item_count'] = $count_row['item_count'];
        $categories_with_counts[] = $category;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { background-color: #343a40; min-height: 100vh; }
        .sidebar .nav-link { color: #fff; padding: 10px 15px; }
        .sidebar .nav-link:hover { background-color: #495057; }
        .sidebar .nav-link.active { background-color: #dc3545; }
        .category-card { transition: transform 0.3s, box-shadow 0.3s; }
        .category-card:hover { transform: translateY(-5px); box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15); }
        .category-icon { font-size: 2.5rem; color: #dc3545; }
        .main-content { padding: 20px; }
        .card { border: none; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075); }
        .card-header { background-color: #f8f9fa; border-bottom: 1px solid rgba(0,0,0,.125); }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="p-3 text-white">
                    <h4><i class="fas fa-utensils"></i> dite food  Admin</h4>
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
                        <a class="nav-link active" href="categories.php">
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
                    <h1><i class="fas fa-tags me-2"></i> Manage Categories</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="fas fa-plus"></i> Add New Category
                    </button>
                </div>
                
                <!-- Success/Error Messages -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Total Categories</h6>
                                        <h3 class="mb-0"><?php echo count($categories_with_counts); ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-tags fa-2x"></i>
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
                                        <h6 class="card-title">Total Menu Items</h6>
                                        <h3 class="mb-0">
                                            <?php 
                                            $total_items = 0;
                                            foreach ($categories_with_counts as $category) {
                                                $total_items += $category['item_count'];
                                            }
                                            echo $total_items;
                                            ?>
                                        </h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-utensils fa-2x"></i>
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
                                        <h6 class="card-title">Active Categories</h6>
                                        <h3 class="mb-0">
                                            <?php 
                                            $active_categories = 0;
                                            foreach ($categories_with_counts as $category) {
                                                if ($category['item_count'] > 0) $active_categories++;
                                            }
                                            echo $active_categories;
                                            ?>
                                        </h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-check-circle fa-2x"></i>
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
                                        <h6 class="card-title">Empty Categories</h6>
                                        <h3 class="mb-0">
                                            <?php 
                                            $empty_categories = 0;
                                            foreach ($categories_with_counts as $category) {
                                                if ($category['item_count'] == 0) $empty_categories++;
                                            }
                                            echo $empty_categories;
                                            ?>
                                        </h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-exclamation-circle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Categories Grid -->
                <div class="row">
                    <?php foreach ($categories_with_counts as $category): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card category-card h-100">
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <i class="fas fa-folder category-icon"></i>
                                    </div>
                                    <h5 class="card-title"><?php echo htmlspecialchars($category['name']); ?></h5>
                                    <p class="card-text text-muted"><?php echo htmlspecialchars($category['description']); ?></p>
                                    <div class="d-flex justify-content-around mb-3">
                                        <div class="text-center">
                                            <h4 class="text-primary"><?php echo $category['item_count']; ?></h4>
                                            <small class="text-muted">Items</small>
                                        </div>
                                        <div class="text-center">
                                            <h4 class="text-success">
                                                <?php 
                                                $available_items_sql = "SELECT COUNT(*) as count FROM menu_items WHERE category_id = ? AND available = 1";
                                                $stmt = $conn->prepare($available_items_sql);
                                                $stmt->bind_param("i", $category['category_id']);
                                                $stmt->execute();
                                                $available_result = $stmt->get_result();
                                                $available_row = $available_result->fetch_assoc();
                                                echo $available_row['count'];
                                                ?>
                                            </h4>
                                            <small class="text-muted">Available</small>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2 justify-content-center">
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editCategoryModal<?php echo $category['category_id']; ?>">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <form method="post" action="" class="d-inline">
                                            <input type="hidden" name="category_id" value="<?php echo $category['category_id']; ?>">
                                            <button type="submit" name="delete_category" class="btn btn-sm btn-outline-danger" 
                                                    onclick="return confirm('Are you sure you want to delete this category?<?php echo $category['item_count'] > 0 ? ' This category has menu items.' : ''; ?>')"
                                                    <?php echo $category['item_count'] > 0 ? 'disabled' : ''; ?>>
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Edit Category Modal -->
                        <div class="modal fade" id="editCategoryModal<?php echo $category['category_id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Category</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form method="post" action="">
                                            <input type="hidden" name="category_id" value="<?php echo $category['category_id']; ?>">
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Category Name</label>
                                                <input type="text" name="category_name" class="form-control" 
                                                       value="<?php echo htmlspecialchars($category['name']); ?>" required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Description</label>
                                                <textarea name="category_description" class="form-control" rows="3"><?php echo htmlspecialchars($category['description']); ?></textarea>
                                            </div>
                                            
                                            <div class="d-flex justify-content-between">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" name="update_category" class="btn btn-primary">
                                                    <i class="fas fa-save"></i> Update Category
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="">
                        <div class="mb-3">
                            <label class="form-label">Category Name</label>
                            <input type="text" name="category_name" class="form-control" placeholder="Enter category name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="category_description" class="form-control" rows="3" placeholder="Enter category description"></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="add_category" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add Category
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>