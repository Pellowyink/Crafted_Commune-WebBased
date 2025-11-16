<?php
/**
 * Quick Stock Update API
 * Handles AJAX requests for updating ingredient stock
 */
require_once '../config.php';
requireAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['ingredient_id']) || !isset($data['new_stock'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$ingredient_id = intval($data['ingredient_id']);
$new_stock = intval($data['new_stock']);

if ($new_stock < 0) {
    echo json_encode(['success' => false, 'message' => 'Stock cannot be negative']);
    exit;
}

try {
    // Update the stock
    $stmt = $pdo->prepare("UPDATE product_inventory SET stock = ? WHERE id = ?");
    $stmt->execute([$new_stock, $ingredient_id]);
    
    // Log the activity
    $ingredientStmt = $pdo->prepare("SELECT name FROM product_inventory WHERE id = ?");
    $ingredientStmt->execute([$ingredient_id]);
    $ingredient = $ingredientStmt->fetch();
    
    if ($ingredient) {
        logActivity($_SESSION['admin_id'], 'update_stock', 
            "Updated stock for {$ingredient['name']} to {$new_stock}");
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Stock updated successfully',
        'new_stock' => $new_stock
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}