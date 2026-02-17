<?php
// test_image.php
require_once 'config.php';

$image_path = 'images/menu_items/your_test_image.jpg';
$full_path = $_SERVER['DOCUMENT_ROOT'] . '/your_project/' . $image_path;

echo "<h1>Image Test</h1>";
echo "<p>Looking for: " . $full_path . "</p>";

if (file_exists($full_path)) {
    echo "<p style='color: green;'>✓ File exists</p>";
    echo "<img src='" . SITE_URL . $image_path . "' alt='Test Image'>";
} else {
    echo "<p style='color: red;'>✗ File does not exist</p>";
}
?>