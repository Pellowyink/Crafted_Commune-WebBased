<?php
/**
 * Image Upload Handler
 * Handles drag & drop image uploads for products
 */
require_once '../config.php';
requireAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    // Check if file was uploaded
    if (!isset($_FILES['image'])) {
        throw new Exception('No file uploaded');
    }
    
    $file = $_FILES['image'];
    
    // Validate file
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.');
    }
    
    // Check file size (max 5MB)
    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxSize) {
        throw new Exception('File size exceeds 5MB limit.');
    }
    
    // Get product ID if updating existing product
    $productId = $_POST['product_id'] ?? null;
    $productName = $_POST['product_name'] ?? 'product';
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = strtolower(str_replace(' ', '_', $productName)) . '_' . time() . '.' . $extension;
    
    // Set upload directory
    $uploadDir = '../images/products/';
    
    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $uploadPath = $uploadDir . $filename;
    $relativePath = '../images/products/' . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        throw new Exception('Failed to move uploaded file');
    }
    
    // If updating product, update database
    if ($productId) {
        $stmt = $pdo->prepare("
            UPDATE products 
            SET image = ? 
            WHERE id = ?
        ");
        $stmt->execute([$relativePath, $productId]);
        
        logActivity($_SESSION['admin_id'], 'update_product_image', "Updated image for product ID: $productId");
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Image uploaded successfully!',
        'filename' => $filename,
        'path' => $relativePath,
        'url' => '../' . $relativePath
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>