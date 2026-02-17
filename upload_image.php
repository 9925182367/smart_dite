<?php
// upload_image.php
require_once 'config.php';
require_once 'db.php';
require_once 'helpers.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['image'])) {
    $upload_dir = IMAGE_UPLOAD_PATH;
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_name = $_FILES['image']['name'];
    $file_tmp = $_FILES['image']['tmp_name'];
    $file_size = $_FILES['image']['size'];
    $file_error = $_FILES['image']['error'];
    
    // Get file extension
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    // Allowed extensions
    $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif', 'webp');
    
    // Check if file extension is allowed
    if (in_array($file_ext, $allowed_extensions)) {
        // Check for errors
        if ($file_error === 0) {
            // Check file size (5MB max)
            if ($file_size <= 5000000) {
                // Create unique file name
                $new_file_name = uniqid('', true) . '.' . $file_ext;
                $file_destination = $upload_dir . $new_file_name;
                
                // Move file to upload directory
                if (move_uploaded_file($file_tmp, $file_destination)) {
                    // Return the file path for database storage
                    echo json_encode([
                        'success' => true,
                        'file_path' => $new_file_name,
                        'file_url' => IMAGE_PATH . $new_file_name
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Error uploading file'
                    ]);
                }
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'File is too large'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error uploading file'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid file type'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No file uploaded'
    ]);
}
?>