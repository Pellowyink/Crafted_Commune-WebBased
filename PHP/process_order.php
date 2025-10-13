<?php
/**
 * Coffee Shop Menu - Order Processing Backend
 * process_order.php
 * 
 * This file handles order submissions from the menu
 */

// Set JSON response header
header('Content-Type: application/json');

// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// ==========================================
// DATABASE CONFIGURATION
// ==========================================

$db_host = 'localhost';
$db_user = 'root';
$db_password = '';
$db_name = 'coffee_shop';

// Uncomment to use database
/*
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}
*/

// ==========================================
// PROCESS ORDER REQUEST
// ==========================================

$response = [
    'success' => false,
    'message' => '',
    'data' => []
];

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    $response['message'] = 'Method not allowed';
    echo json_encode($response);
    exit;
}

// Get product data
$product_name = isset($_POST['product']) ? trim($_POST['product']) : null;
$product_price = isset($_POST['price']) ? trim($_POST['price']) : null;

// Validate input
if (!$product_name || empty($product_name)) {
    http_response_code(400);
    $response['message'] = 'Product name is required';
    echo json_encode($response);
    exit;
}

// ==========================================
// ORDER DATA PREPARATION
// ==========================================

$order_id = uniqid('ORD_');
$order_date = date('Y-m-d H:i:s');
$customer_ip = $_SERVER['REMOTE_ADDR'];

// Prepare order data
$order_data = [
    'order_id' => $order_id,
    'product_name' => $product_name,
    'product_price' => $product_price,
    'order_date' => $order_date,
    'customer_ip' => $customer_ip,
    'user_agent' => $_SERVER['HTTP_USER_AGENT'],
    'status' => 'pending'
];

// ==========================================
// SAVE TO DATABASE (if enabled)
// ==========================================

/*
try {
    $sql = "INSERT INTO orders (order_id, product_name, product_price, order_date, customer_ip, status) 
            VALUES (:order_id, :product_name, :product_price, :order_date, :customer_ip, :status)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':order_id' => $order_id,
        ':product_name' => $product_name,
        ':product_price' => $product_price,
        ':order_date' => $order_date,
        ':customer_ip' => $customer_ip,
        ':status' => 'pending'
    ]);
    
    $response['success'] = true;
    $response['message'] = 'Order processed successfully';
    $response['data'] = $order_data;
    
} catch (PDOException $e) {
    http_response_code(500);
    $response['message'] = 'Database error: ' . $e->getMessage();
    echo json_encode($response);
    exit;
}
*/

// ==========================================
// SAVE TO FILE (if database not available)
// ==========================================

$orders_file = __DIR__ . '/orders.json';

// Create or load existing orders
if (file_exists($orders_file)) {
    $existing_orders = json_decode(file_get_contents($orders_file), true);
} else {
    $existing_orders = [];
}

// Add new order
$existing_orders[] = $order_data;

// Save orders to file
if (file_put_contents($orders_file, json_encode($existing_orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
    $response['success'] = true;
    $response['message'] = 'Order processed successfully';
    $response['data'] = $order_data;
} else {
    http_response_code(500);
    $response['success'] = false;
    $response['message'] = 'Failed to save order';
    echo json_encode($response);
    exit;
}

// ==========================================
// SEND EMAIL NOTIFICATION (Optional)
// ==========================================

/*
$to = 'admin@coffeeshop.com';
$subject = "New Order - {$product_name}";
$message = "
    <html>
        <head>
            <title>New Order</title>
        </head>
        <body>
            <h2>New Order Received</h2>
            <p><strong>Order ID:</strong> {$order_id}</p>
            <p><strong>Product:</strong> {$product_name}</p>
            <p><strong>Price:</strong> ₱{$product_price}</p>
            <p><strong>Time:</strong> {$order_date}</p>
            <p><strong>Customer IP:</strong> {$customer_ip}</p>
        </body>
    </html>
";

$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";

mail($to, $subject, $message, $headers);
*/

// ==========================================
// RETURN RESPONSE
// ==========================================

http_response_code(200);
echo json_encode($response);
exit;

// ==========================================
// ADDITIONAL HELPER FUNCTIONS
// ==========================================

/**
 * Get all orders from file
 */
function getAllOrders() {
    $orders_file = __DIR__ . '/orders.json';
    if (file_exists($orders_file)) {
        return json_decode(file_get_contents($orders_file), true);
    }
    return [];
}

/**
 * Get orders by date range
 */
function getOrdersByDateRange($start_date, $end_date) {
    $orders = getAllOrders();
    $filtered = [];
    
    foreach ($orders as $order) {
        $order_date = strtotime($order['order_date']);
        if ($order_date >= strtotime($start_date) && $order_date <= strtotime($end_date)) {
            $filtered[] = $order;
        }
    }
    
    return $filtered;
}

/**
 * Get recommended product orders
 */
function getRecommendedOrders() {
    $recommended_products = ['Manual Brew', 'Vanilla Latte', 'Orange Soda', 'Muffin'];
    $orders = getAllOrders();
    $filtered = [];
    
    foreach ($orders as $order) {
        if (in_array($order['product_name'], $recommended_products)) {
            $filtered[] = $order;
        }
    }
    
    return $filtered;
}

/**
 * Get order statistics
 */
function getOrderStats() {
    $orders = getAllOrders();
    $stats = [
        'total_orders' => count($orders),
        'total_revenue' => 0,
        'most_popular_product' => null,
        'product_count' => []
    ];
    
    foreach ($orders as $order) {
        $price = (float) str_replace('₱', '', $order['product_price']);
        $stats['total_revenue'] += $price;
        
        $product = $order['product_name'];
        if (!isset($stats['product_count'][$product])) {
            $stats['product_count'][$product] = 0;
        }
        $stats['product_count'][$product]++;
    }
    
    if (!empty($stats['product_count'])) {
        $stats['most_popular_product'] = array_key_first($stats['product_count']);
    }
    
    return $stats;
}
?>