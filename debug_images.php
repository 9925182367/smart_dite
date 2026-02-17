<?php
// debug_images.php
require_once 'config.php';
require_once 'db.php';
require_once 'helpers.php';

echo "<h1>Image Debug Information</h1>";

echo "<h2>Configuration</h2>";
echo "<p>SITE_URL: " . SITE_URL . "</p>";
echo "<p>IMAGE_PATH: " . IMAGE_PATH . "</p>";
echo "<p>IMAGE_UPLOAD_PATH: " . IMAGE_UPLOAD_PATH . "</p>";

echo "<h2>Directory Check</h2>";
if (file_exists(IMAGE_UPLOAD_PATH)) {
    echo "<p style='color: green;'>✓ Upload directory exists</p>";
    if (is_writable(IMAGE_UPLOAD_PATH)) {
        echo "<p style='color: green;'>✓ Upload directory is writable</p>";
    } else {
        echo "<p style='color: red;'>✗ Upload directory is not writable</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Upload directory does not exist</p>";
}

echo "<h2>Database Images</h2>";
$sql = "SELECT item_id, name, image_url FROM menu_items";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Name</th><th>Image URL</th><th>Generated URL</th><th>Test</th></tr>";
    
    while($row = $result->fetch_assoc()) {
        $generated_url = get_image_url($row['image_url']);
        echo "<tr>";
        echo "<td>" . $row['item_id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['image_url']) . "</td>";
        echo "<td>" . $generated_url . "</td>";
        echo "<td><img src='" . $generated_url . "' width='50' height='50' onerror=\"this.style.border='1px solid red'\"></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No menu items found in database</p>";
}
?>