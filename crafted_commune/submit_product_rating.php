<?php
/**
 * Submit Product Ratings
 * Handles individual product ratings from customers
 */

// Prevent any output before JSON
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

require_once 'config.php';

ob_clean();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data) {
        throw new Exception('Invalid request data');
    }
    
    $code = trim($data['code'] ?? '');
    $ratings = $data['ratings'] ?? [];
    
    // Validate code
    if (empty($code) || strlen($code) !== 64) {
        throw new Exception('Invalid rating code');
    }
    
    // Validate ratings
    if (empty($ratings) || !is_array($ratings)) {
        throw new Exception('No ratings provided');
    }
    
    // Validate each rating
    foreach ($ratings as $itemId => $rating) {
        if (!is_numeric($rating) || $rating < 1 || $rating > 5) {
            throw new Exception('Invalid rating value. Must be 1-5 stars.');
        }
    }
    
    // Get rating link
    $stmt = $pdo->prepare("
        SELECT id, status, expires_at, member_id, order_id 
        FROM rating_links 
        WHERE unique_code = ?
    ");
    $stmt->execute([$code]);
    $ratingLink = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ratingLink) {
        throw new Exception('Invalid or expired rating link');
    }
    
    // Check if already completed
    if ($ratingLink['status'] === 'completed') {
        throw new Exception('You have already submitted ratings for this order.');
    }
    
    // Check if expired
    if ($ratingLink['status'] === 'expired' || strtotime($ratingLink['expires_at']) < time()) {
        $pdo->prepare("UPDATE rating_links SET status = 'expired' WHERE id = ?")
            ->execute([$ratingLink['id']]);
        throw new Exception('This rating link has expired');
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    $insertStmt = $pdo->prepare("
        INSERT INTO product_ratings (product_id, member_id, order_id, order_item_id, rating)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE rating = VALUES(rating)
    ");
    
    $successCount = 0;
    
    foreach ($ratings as $orderItemId => $rating) {
        // Get product_id for this order item
        $itemStmt = $pdo->prepare("
            SELECT product_id 
            FROM order_items 
            WHERE id = ? AND order_id = ?
        ");
        $itemStmt->execute([$orderItemId, $ratingLink['order_id']]);
        $item = $itemStmt->fetch();
        
        if ($item) {
            $insertStmt->execute([
                $item['product_id'],
                $ratingLink['member_id'],
                $ratingLink['order_id'],
                $orderItemId,
                $rating
            ]);
            $successCount++;
        }
    }
    
    // Mark rating link as completed
    $updateLinkStmt = $pdo->prepare("
        UPDATE rating_links 
        SET status = 'completed', completed_at = NOW() 
        WHERE id = ?
    ");
    $updateLinkStmt->execute([$ratingLink['id']]);
    
    // Award bonus points
    $bonusPoints = 5;
    $bonusStmt = $pdo->prepare("
        UPDATE loyalty_members 
        SET points = points + ? 
        WHERE id = ?
    ");
    $bonusStmt->execute([$bonusPoints, $ratingLink['member_id']]);
    
    // Log bonus transaction
    $memberStmt = $pdo->prepare("SELECT points FROM loyalty_members WHERE id = ?");
    $memberStmt->execute([$ratingLink['member_id']]);
    $member = $memberStmt->fetch();
    
    $logStmt = $pdo->prepare("
        INSERT INTO loyalty_transactions 
        (member_id, order_id, transaction_type, points_change, points_balance, description)
        VALUES (?, ?, 'earn', ?, ?, ?)
    ");
    $logStmt->execute([
        $ratingLink['member_id'],
        $ratingLink['order_id'],
        $bonusPoints,
        $member['points'],
        "Bonus points for rating products"
    ]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Thank you for rating!',
        'ratings_submitted' => $successCount,
        'bonus_points' => $bonusPoints
    ]);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>