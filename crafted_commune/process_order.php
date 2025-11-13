<?php
/**
 * Enhanced Process Order with Loyalty Program
 * Handles order processing and loyalty member management
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
    
    if (!$data || !isset($data['action'])) {
        throw new Exception('Invalid request data');
    }
    
    $action = $data['action'];
    
    // ========================================
    // ACTION: Check if member exists by email
    // ========================================
    if ($action === 'check_member') {
        $email = trim($data['email'] ?? '');
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid email address'
            ]);
            exit;
        }
        
        $stmt = $pdo->prepare("SELECT id, name, email, points FROM loyalty_members WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($member) {
            echo json_encode([
                'success' => true,
                'found' => true,
                'member' => $member
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'found' => false,
                'message' => 'Member not found'
            ]);
        }
        exit;
    }
    
    // ========================================
    // ACTION: Register new loyalty member
    // ========================================
    if ($action === 'register_member') {
        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        
        // Validation
        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Name is required']);
            exit;
        }
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Valid email is required']);
            exit;
        }
        
        // Check if email already exists
        $checkStmt = $pdo->prepare("SELECT id FROM loyalty_members WHERE email = ?");
        $checkStmt->execute([$email]);
        
        if ($checkStmt->fetch()) {
            echo json_encode([
                'success' => false,
                'message' => 'This email is already registered. Please use the member lookup instead.'
            ]);
            exit;
        }
        
        // Insert new member
        $insertStmt = $pdo->prepare("
            INSERT INTO loyalty_members (name, email, points, created_at) 
            VALUES (?, ?, 0, NOW())
        ");
        $insertStmt->execute([$name, $email]);
        $memberId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Member registered successfully!',
            'member' => [
                'id' => $memberId,
                'name' => $name,
                'email' => $email,
                'points' => 0
            ]
        ]);
        exit;
    }
    
    // ========================================
    // ACTION: Complete order with optional loyalty
    // ========================================
    if ($action === 'complete_order') {
        if (!isset($data['items']) || empty($data['items'])) {
            throw new Exception('No items in order');
        }
        
        $items = $data['items'];
        $totalAmount = $data['total'];
        $totalPoints = $data['points'];
        $cashReceived = $data['cash_received'] ?? 0;
        $memberId = $data['member_id'] ?? null;
        
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
        $itemStmt = $pdo->prepare("
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
                
                $itemStmt->execute([
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
        
        // Handle loyalty member points
        $memberData = null;
        if ($memberId) {
            // Get current member data
            $memberStmt = $pdo->prepare("SELECT id, name, email, points FROM loyalty_members WHERE id = ?");
            $memberStmt->execute([$memberId]);
            $member = $memberStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($member) {
                $newPoints = $member['points'] + $totalPoints;
                
                // Update member points and stats
                $updateStmt = $pdo->prepare("
                    UPDATE loyalty_members 
                    SET points = ?, 
                        total_purchases = total_purchases + ?,
                        total_orders = total_orders + 1,
                        last_purchase = NOW()
                    WHERE id = ?
                ");
                $updateStmt->execute([$newPoints, $totalAmount, $memberId]);
                
                // Log loyalty transaction
                $logStmt = $pdo->prepare("
                    INSERT INTO loyalty_transactions 
                    (member_id, order_id, transaction_type, points_change, points_balance, description)
                    VALUES (?, ?, 'earn', ?, ?, ?)
                ");
                $logStmt->execute([
                    $memberId,
                    $orderId,
                    $totalPoints,
                    $newPoints,
                    "Earned from order #{$orderNumber}"
                ]);
                
                $memberData = [
                    'id' => $member['id'],
                    'name' => $member['name'],
                    'email' => $member['email'],
                    'previous_points' => $member['points'],
                    'points_earned' => $totalPoints,
                    'new_points' => $newPoints
                ];
            }
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Calculate change
        $change = $cashReceived - $totalAmount;
        
        echo json_encode([
            'success' => true,
            'message' => 'Order completed successfully!',
            'order_number' => $orderNumber,
            'order_id' => $orderId,
            'total_amount' => $totalAmount,
            'cash_received' => $cashReceived,
            'change' => $change,
            'member' => $memberData
        ]);
        exit;
    }
    
    // Unknown action
    throw new Exception('Unknown action: ' . $action);
    
} catch (Exception $e) {
    // Rollback on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>