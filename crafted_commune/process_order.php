<?php
/**
 * Process Order - Save order to database
 * Called via AJAX from script.js
 */
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    // Get JSON data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data || !isset($data['items']) || empty($data['items'])) {
        throw new Exception('Invalid order data');
    }
    
    $items = $data['items'];
    $totalAmount = $data['total'];
    $totalPoints = $data['points'];
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Generate order number
    $orderNumber = generateOrderNumber();
    
    // Insert order
    $stmt = $pdo->prepare("
        INSERT INTO orders (order_number, total_amount, total_points, order_status, completed_at) 
        VALUES (?, ?, ?, 'completed', NOW())
    ");
    $stmt->execute([$orderNumber, $totalAmount, $totalPoints]);
    $orderId = $pdo->lastInsertId();
    
    // Insert order items
    $stmt = $pdo->prepare("
        INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, unit_points, subtotal, subtotal_points) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($items as $item) {
        // Get product ID from name
        $productStmt = $pdo->prepare("SELECT id FROM products WHERE name = ? LIMIT 1");
        $productStmt->execute([$item['name']]);
        $product = $productStmt->fetch();
        
        if ($product) {
            $productId = $product['id'];
            $subtotal = $item['price'] * $item['qty'];
            $subtotalPoints = $item['points'] * $item['qty'];
            
            $stmt->execute([
                $orderId,
                $productId,
                $item['name'],
                $item['qty'],
                $item['price'],
                $item['points'],
                $subtotal,
                $subtotalPoints
            ]);
            
            // Update product analytics
            $analyticsStmt = $pdo->prepare("
                UPDATE product_analytics 
                SET purchase_count = purchase_count + ? 
                WHERE product_id = ?
            ");
            $analyticsStmt->execute([$item['qty'], $productId]);
        }
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Order placed successfully!',
        'order_number' => $orderNumber,
        'order_id' => $orderId
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Error processing order: ' . $e->getMessage()
    ]);
}
?>