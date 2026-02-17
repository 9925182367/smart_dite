<?php
session_start();
include 'db.php';

// Simple authentication
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

// Create images directory if it doesn't exist
$upload_dir = '../images/menu_items/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
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
        
        header("Location: menu.php?success=category_added");
        exit();
    }
    
    // Add new menu item
    if (isset($_POST['add_item'])) {
        $item_name = $_POST['item_name'];
        $item_description = $_POST['item_description'];
        $item_price = $_POST['item_price'];
        $category_id = $_POST['category_id'];
        $item_image = '';
        
        // Handle image upload
        if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] == 0) {
            $allowed = array('jpg', 'jpeg', 'png', 'gif', 'webp');
            $filename = $_FILES['item_image']['name'];
            $filetype = pathinfo($filename, PATHINFO_EXTENSION);
            
            if (in_array(strtolower($filetype), $allowed)) {
                // Create unique filename
                $new_filename = uniqid() . '.' . $filetype;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['item_image']['tmp_name'], $upload_path)) {
                    $item_image = 'images/menu_items/' . $new_filename;
                }
            }
        }
        
        // If no image uploaded, use a default
        if (empty($item_image)) {
            $item_image = 'https://via.placeholder.com/300x200?text=No+Image';
        }
        
        $sql = "INSERT INTO menu_items (name, description, price, category_id, image_url) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdis", $item_name, $item_description, $item_price, $category_id, $item_image);
        $stmt->execute();
        
        header("Location: menu.php?success=item_added");
        exit();
    }
    
    // Update menu item
    if (isset($_POST['update_item'])) {
        $item_id = $_POST['item_id'];
        $item_name = $_POST['item_name'];
        $item_description = $_POST['item_description'];
        $item_price = $_POST['item_price'];
        $category_id = $_POST['category_id'];
        $available = isset($_POST['available']) ? 1 : 0;
        
        // Get current image
        $current_image = '';
        $current_sql = "SELECT image_url FROM menu_items WHERE item_id = ?";
        $current_stmt = $conn->prepare($current_sql);
        $current_stmt->bind_param("i", $item_id);
        $current_stmt->execute();
        $current_result = $current_stmt->get_result();
        if ($current_row = $current_result->fetch_assoc()) {
            $current_image = $current_row['image_url'];
        }
        
        // Handle image upload
        if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] == 0) {
            $allowed = array('jpg', 'jpeg', 'png', 'gif', 'webp');
            $filename = $_FILES['item_image']['name'];
            $filetype = pathinfo($filename, PATHINFO_EXTENSION);
            
            if (in_array(strtolower($filetype), $allowed)) {
                // Create unique filename
                $new_filename = uniqid() . '.' . $filetype;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['item_image']['tmp_name'], $upload_path)) {
                    $item_image = 'images/menu_items/' . $new_filename;
                    
                    // Delete old image if it's not a placeholder
                    if (!empty($current_image) && strpos($current_image, 'via.placeholder.com') === false) {
                        $old_file_path = '../' . $current_image;
                        if (file_exists($old_file_path)) {
                            unlink($old_file_path);
                        }
                    }
                }
            }
        } else {
            // Keep current image if no new image uploaded
            $item_image = $current_image;
        }
        
        $sql = "UPDATE menu_items SET name = ?, description = ?, price = ?, category_id = ?, image_url = ?, available = ? WHERE item_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdisii", $item_name, $item_description, $item_price, $category_id, $item_image, $available, $item_id);
        $stmt->execute();
        
        header("Location: menu.php?success=item_updated");
        exit();
    }
    
    // Delete menu item
    if (isset($_POST['delete_item'])) {
        $item_id = $_POST['item_id'];
        
        // Get image path before deletion
        $image_sql = "SELECT image_url FROM menu_items WHERE item_id = ?";
        $image_stmt = $conn->prepare($image_sql);
        $image_stmt->bind_param("i", $item_id);
        $image_stmt->execute();
        $image_result = $image_stmt->get_result();
        
        if ($image_row = $image_result->fetch_assoc()) {
            $image_path = '../' . $image_row['image_url'];
            
            // Delete image file if it's not a placeholder
            if (!empty($image_row['image_url']) && strpos($image_row['image_url'], 'via.placeholder.com') === false) {
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
        }
        
        $sql = "DELETE FROM menu_items WHERE item_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        
        header("Location: menu.php?success=item_deleted");
        exit();
    }
}

// Get categories
$categories_sql = "SELECT * FROM categories ORDER BY name";
$categories_result = $conn->query($categories_sql);

// Get menu items
$menu_sql = "SELECT mi.*, c.name as category_name 
             FROM menu_items mi 
             LEFT JOIN categories c ON mi.category_id = c.category_id 
             ORDER BY c.name, mi.name";
$menu_result = $conn->query($menu_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Menu - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .menu-item-img { width: 60px; height: 60px; object-fit: cover; }
        .category-section { margin-bottom: 30px; }
        .image-preview { max-width: 200px; max-height: 200px; margin-top: 10px; }
        .sidebar { background-color: #343a40; min-height: 100vh; }
        .sidebar .nav-link { color: #fff; padding: 10px 15px; }
        .sidebar .nav-link:hover { background-color: #495057; }
        .sidebar .nav-link.active { background-color: #dc3545; }
        .main-content { padding: 20px; }
        .card { border: none; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075); }
        .card-header { background-color: #f8f9fa; border-bottom: 1px solid rgba(0,0,0,.125); }
        .alert-container { position: fixed; top: 20px; right: 20px; z-index: 1050; width: 300px; }
    </style>
</head>
<body>
    <!-- Alert Container -->
    <div class="alert-container">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> 
                <?php 
                switch ($_GET['success']) {
                    case 'category_added':
                        echo "Category added successfully!";
                        break;
                    case 'item_added':
                        echo "Menu item added successfully!";
                        break;
                    case 'item_updated':
                        echo "Menu item updated successfully!";
                        break;
                    case 'item_deleted':
                        echo "Menu item deleted successfully!";
                        break;
                    default:
                        echo "Operation completed successfully!";
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
                    <h4><i class="fas fa-utensils"></i> Smart Diet Food</h4>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link text-white" href="orders.php">
                            <i class="fas fa-clipboard-list me-2"></i> Orders
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white active" href="menu.php">
                            <i class="fas fa-utensils me-2"></i> Menu
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="categories.php">
                            <i class="fas fa-tags me-2"></i> Categories
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="reports.php">
                            <i class="fas fa-chart-bar me-2"></i> Reports
                        </a>
                    </li>
                    <li class="nav-item mt-auto">
                        <a class="nav-link text-white" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-utensils me-2"></i> Manage Menu</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
                        <i class="fas fa-plus"></i> Add New Item
                    </button>
                </div>
                
                <!-- Add Category Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Add New Category</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-4">
                                    <input type="text" name="category_name" class="form-control" placeholder="Category Name" required>
                                </div>
                                <div class="col-md-6">
                                    <input type="text" name="category_description" class="form-control" placeholder="Description">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" name="add_category" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Add Category
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Menu Items List -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Current Menu Items</h5>
                        <div class="d-flex gap-2">
                            <input type="text" class="form-control form-control-sm" id="searchMenu" placeholder="Search menu..." style="width: 200px;">
                            <select class="form-select form-select-sm" id="filterCategory" style="width: 150px;">
                                <option value="">All Categories</option>
                                <?php 
                                $categories_result->data_seek(0);
                                while ($category = $categories_result->fetch_assoc()): ?>
                                    <option value="<?php echo $category['category_id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php
                        // Reset categories result pointer
                        $categories_result->data_seek(0);
                        $categories = [];
                        while ($category = $categories_result->fetch_assoc()) {
                            $categories[$category['category_id']] = $category['name'];
                        }
                        
                        // Group items by category
                        $menu_by_category = [];
                        if ($menu_result->num_rows > 0) {
                            $menu_result->data_seek(0);
                            while ($item = $menu_result->fetch_assoc()) {
                                $category_name = $item['category_name'] ?: 'Uncategorized';
                                if (!isset($menu_by_category[$category_name])) {
                                    $menu_by_category[$category_name] = [];
                                }
                                $menu_by_category[$category_name][] = $item;
                            }
                        }
                        
                        if (empty($menu_by_category)) {
                            echo '<div class="text-center py-5">
                                <i class="fas fa-utensils fa-3x text-muted mb-3"></i>
                                <h5>No menu items found</h5>
                                <p class="text-muted">Add your first menu item to get started.</p>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
                                    <i class="fas fa-plus"></i> Add Menu Item
                                </button>
                            </div>';
                        } else {
                            foreach ($menu_by_category as $category_name => $items):
                        ?>
                                <div class="category-section" data-category="<?php echo htmlspecialchars($category_name); ?>">
                                    <h5><?php echo htmlspecialchars($category_name); ?></h5>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Image</th>
                                                    <th>Name</th>
                                                    <th>Description</th>
                                                    <th>Price</th>
                                                    <th>Available</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($items as $item): ?>
                                                    <tr>
                                                        <td>
                                                            <?php if (!empty($item['image_url'])): ?>
                                                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" class="menu-item-img" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                                            <?php else: ?>
                                                                <img src="https://via.placeholder.com/60x60?text=No+Image" class="menu-item-img" alt="No Image">
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                                                        <td><?php echo htmlspecialchars($item['description']); ?></td>
                                                        <td>$<?php echo number_format($item['price'], 2); ?></td>
                                                        <td>
                                                            <?php if ($item['available']): ?>
                                                                <span class="badge bg-success">Yes</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-danger">No</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <!-- Edit Button -->
                                                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $item['item_id']; ?>">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            
                                                            <!-- Delete Form -->
                                                            <form method="post" action="" class="d-inline">
                                                                <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                                                <button type="submit" name="delete_item" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this item?');">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                    
                                                    <!-- Edit Modal -->
                                                    <div class="modal fade" id="editModal<?php echo $item['item_id']; ?>" tabindex="-1">
                                                        <div class="modal-dialog modal-lg">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Edit Menu Item</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <form method="post" action="" enctype="multipart/form-data">
                                                                        <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                                                        
                                                                        <div class="row">
                                                                            <div class="col-md-6">
                                                                                <div class="mb-3">
                                                                                    <label class="form-label">Name</label>
                                                                                    <input type="text" name="item_name" class="form-control" value="<?php echo htmlspecialchars($item['name']); ?>" required>
                                                                                </div>
                                                                                
                                                                                <div class="mb-3">
                                                                                    <label class="form-label">Description</label>
                                                                                    <textarea name="item_description" class="form-control"><?php echo htmlspecialchars($item['description']); ?></textarea>
                                                                                </div>
                                                                                
                                                                                <div class="mb-3">
                                                                                    <label class="form-label">Price</label>
                                                                                    <input type="number" name="item_price" step="0.01" class="form-control" value="<?php echo $item['price']; ?>" required>
                                                                                </div>
                                                                            </div>
                                                                            
                                                                            <div class="col-md-6">
                                                                                <div class="mb-3">
                                                                                    <label class="form-label">Category</label>
                                                                                    <select name="category_id" class="form-select" required>
                                                                                        <?php foreach ($categories as $cat_id => $cat_name): ?>
                                                                                            <option value="<?php echo $cat_id; ?>" <?php echo $item['category_id'] == $cat_id ? 'selected' : ''; ?>>
                                                                                                <?php echo htmlspecialchars($cat_name); ?>
                                                                                            </option>
                                                                                        <?php endforeach; ?>
                                                                                    </select>
                                                                                </div>
                                                                                
                                                                                <div class="mb-3">
                                                                                    <label class="form-label">Current Image</label>
                                                                                    <div>
                                                                                        <?php if (!empty($item['image_url'])): ?>
                                                                                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" class="image-preview" alt="Current Image">
                                                                                        <?php else: ?>
                                                                                            <img src="https://via.placeholder.com/200x200?text=No+Image" class="image-preview" alt="No Image">
                                                                                        <?php endif; ?>
                                                                                    </div>
                                                                                </div>
                                                                                
                                                                                <div class="mb-3">
                                                                                    <label class="form-label">Upload New Image</label>
                                                                                    <input type="file" name="item_image" class="form-control" accept="image/*" id="editImage<?php echo $item['item_id']; ?>">
                                                                                    <div id="editPreview<?php echo $item['item_id']; ?>"></div>
                                                                                </div>
                                                                                
                                                                                <div class="mb-3">
                                                                                    <div class="form-check">
                                                                                        <input type="checkbox" name="available" class="form-check-input" <?php echo $item['available'] ? 'checked' : ''; ?>>
                                                                                        <label class="form-check-label">Available</label>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        
                                                                        <div class="d-flex justify-content-end">
                                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                            <button type="submit" name="update_item" class="btn btn-primary ms-2">
                                                                                <i class="fas fa-save"></i> Update Item
                                                                            </button>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php endforeach; 
                        } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Item Modal -->
    <div class="modal fade" id="addItemModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Menu Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Name</label>
                                    <input type="text" name="item_name" class="form-control" placeholder="Enter item name" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea name="item_description" class="form-control" rows="3" placeholder="Enter item description"></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Price</label>
                                    <input type="number" name="item_price" step="0.01" class="form-control" placeholder="0.00" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Category</label>
                                    <select name="category_id" class="form-select" required>
                                        <option value="">Select Category</option>
                                        <?php 
                                        $categories_result->data_seek(0);
                                        while ($category = $categories_result->fetch_assoc()): ?>
                                            <option value="<?php echo $category['category_id']; ?>">
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Item Image</label>
                                    <input type="file" name="item_image" class="form-control" accept="image/*" id="addImage">
                                    <div class="form-text">Upload an image for this menu item (JPG, PNG, GIF, WebP)</div>
                                    <div id="addPreview"></div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" name="available" class="form-check-input" checked>
                                        <label class="form-check-label">Available</label>
                                    </div>
                                    <div class="form-text">Uncheck to hide this item from the menu</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="add_item" class="btn btn-primary ms-2">
                                <i class="fas fa-plus"></i> Add Item
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Image preview for add item modal
        document.getElementById('addImage').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('addPreview').innerHTML = 
                        '<img src="' + e.target.result + '" class="image-preview" alt="Preview">';
                }
                reader.readAsDataURL(file);
            } else {
                document.getElementById('addPreview').innerHTML = '';
            }
        });
        
        // Image preview for edit modals
        <?php 
        $menu_result->data_seek(0);
        while ($item = $menu_result->fetch_assoc()): 
        ?>
        document.getElementById('editImage<?php echo $item['item_id']; ?>').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('editPreview<?php echo $item['item_id']; ?>').innerHTML = 
                        '<img src="' + e.target.result + '" class="image-preview" alt="Preview">';
                }
                reader.readAsDataURL(file);
            } else {
                document.getElementById('editPreview<?php echo $item['item_id']; ?>').innerHTML = '';
            }
        });
        <?php endwhile; ?>
        
        // Search functionality
        document.getElementById('searchMenu').addEventListener('keyup', function() {
            let searchValue = this.value.toLowerCase();
            let rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                let text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchValue) ? '' : 'none';
            });
        });
        
        // Filter by category
        document.getElementById('filterCategory').addEventListener('change', function() {
            let filterValue = this.value;
            let categorySections = document.querySelectorAll('.category-section');
            
            categorySections.forEach(section => {
                if (filterValue === '') {
                    section.style.display = '';
                } else {
                    // Find the category ID for this section
                    let categoryName = section.getAttribute('data-category');
                    let categoryId = null;
                    
                    // Get all category options to find the ID
                    let options = document.querySelectorAll('#filterCategory option');
                    options.forEach(option => {
                        if (option.text === categoryName) {
                            categoryId = option.value;
                        }
                    });
                    
                    section.style.display = categoryId === filterValue ? '' : 'none';
                }
            });
        });
        
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