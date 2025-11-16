<?php
/**
 * Enhanced Process Order with Loyalty Program & Rating System
 * FIXED VERSION - Proper error handling for JSON responses
 */

// CRITICAL: Prevent any output before JSON
error_reporting(0); // Suppress all PHP errors from displaying
ini_set('display_errors', 0); // Don't display errors

// Start output buffering to catch any accidental output
ob_start();

require_once 'config.php';
require_once 'email_helper.php';

// Clear any output that happened during includes
ob_clean();

// Set response headers
header('Content-Type: application/json');

// Custom error handler to log errors instead of displaying them
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error: $errstr in $errfile on line $errline");
    return true; // Don't execute PHP's internal error handler
});

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean(); // Clear buffer
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
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
            ob_clean();
            echo json_encode([
                'success' => false,
                'message' => 'Invalid email address'
            ]);
            exit;
        }
        
        $stmt = $pdo->prepare("SELECT id, name, email, points FROM loyalty_members WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);
        
        ob_clean();
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
        
        if (empty($name)) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Name is required']);
            exit;
        }
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Valid email is required']);
            exit;
        }
        
        $checkStmt = $pdo->prepare("SELECT id FROM loyalty_members WHERE email = ?");
        $checkStmt->execute([$email]);
        
        if ($checkStmt->fetch()) {
            ob_clean();
            echo json_encode([
                'success' => false,
                'message' => 'This email is already registered. Please use the member lookup instead.'
            ]);
            exit;
        }
        
        $insertStmt = $pdo->prepare("
            INSERT INTO loyalty_members (name, email, points, created_at) 
            VALUES (?, ?, 0, NOW())
        ");
        $insertStmt->execute([$name, $email]);
        $memberId = $pdo->lastInsertId();
        
        ob_clean();
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
        
        $pdo->beginTransaction();
        
        $orderNumber = generateOrderNumber();
        
        // Insert order WITH loyalty_member_id
        $stmt = $pdo->prepare("
            INSERT INTO orders (order_number, total_amount, total_points, order_status, loyalty_member_id, completed_at) 
            VALUES (?, ?, ?, 'completed', ?, NOW())
        ");
        $stmt->execute([$orderNumber, $totalAmount, $totalPoints, $memberId]);
        $orderId = $pdo->lastInsertId();
        
        // Insert order items
        $itemStmt = $pdo->prepare("
            INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, unit_points, subtotal, subtotal_points) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($items as $item) {
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
                
                $analyticsStmt = $pdo->prepare("
                    UPDATE product_analytics 
                    SET purchase_count = purchase_count + ? 
                    WHERE product_id = ?
                ");
                $analyticsStmt->execute([$item['qty'], $productId]);
            }
        }
        
        $memberData = null;
        $ratingLink = null;
        
        if ($memberId) {
            $memberStmt = $pdo->prepare("SELECT id, name, email, points FROM loyalty_members WHERE id = ?");
            $memberStmt->execute([$memberId]);
            $member = $memberStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($member) {
                $previousPoints = $member['points'];
                $newPoints = $previousPoints + $totalPoints;
                
                // Update member
                $updateStmt = $pdo->prepare("
                    UPDATE loyalty_members 
                    SET points = ?, 
                        total_purchases = total_purchases + ?,
                        total_orders = total_orders + 1,
                        last_purchase = NOW()
                    WHERE id = ?
                ");
                $updateStmt->execute([$newPoints, $totalAmount, $memberId]);
                
                // Log transaction
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
                
                // Generate rating link
                $uniqueCode = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
                
                $ratingStmt = $pdo->prepare("
                    INSERT INTO rating_links 
                    (unique_code, member_id, order_id, order_number, points_earned, total_points, expires_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $ratingStmt->execute([
                    $uniqueCode,
                    $memberId,
                    $orderId,
                    $orderNumber,
                    $totalPoints,
                    $newPoints,
                    $expiresAt
                ]);
                
                $ratingUrl = SITE_URL . '/rate_products.php?code=' . $uniqueCode;
                $ratingLink = $ratingUrl;
                
                // Check milestones
                checkMilestones($pdo, $memberId, $previousPoints, $newPoints);
                
                // Send email
                try {
                    sendRatingEmail($member['email'], $member['name'], $totalPoints, $newPoints, $ratingUrl, $orderNumber);
                } catch (Exception $e) {
                    error_log("Email failed: " . $e->getMessage());
                }
                
                $memberData = [
                    'id' => $member['id'],
                    'name' => $member['name'],
                    'email' => $member['email'],
                    'previous_points' => $previousPoints,
                    'points_earned' => $totalPoints,
                    'new_points' => $newPoints,
                    'rating_link' => $ratingLink
                ];
            }
        }
        
        $pdo->commit();
        
        $change = $cashReceived - $totalAmount;
        
        ob_clean();
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
    
    throw new Exception('Unknown action: ' . $action);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

// ========================================
// HELPER FUNCTIONS
// ========================================

function checkMilestones($pdo, $memberId, $oldPoints, $newPoints) {
    $milestones = [100, 250, 500, 1000, 2500, 5000];
    
    foreach ($milestones as $milestone) {
        if ($oldPoints < $milestone && $newPoints >= $milestone) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO milestone_achievements (member_id, milestone_type, milestone_value, notified)
                    VALUES (?, 'points', ?, 1)
                ");
                $stmt->execute([$memberId, $milestone]);
            } catch (Exception $e) {
                error_log("Milestone error: " . $e->getMessage());
            }
        }
    }
}
?>