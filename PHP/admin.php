<?php
/**
 * Coffee Shop Menu - Admin Dashboard
 * admin.php
 * 
 * View all orders and statistics
 */

// Load orders from JSON file
$orders_file = __DIR__ . '/orders.json';
$orders = [];
$stats = [
    'total_orders' => 0,
    'total_revenue' => 0,
    'popular_products' => [],
    'recommended_sold' => 0
];

if (file_exists($orders_file)) {
    $orders = json_decode(file_get_contents($orders_file), true);
    
    // Calculate statistics
    $stats['total_orders'] = count($orders);
    $product_count = [];
    $recommended_products = ['Manual Brew', 'Vanilla Latte', 'Orange Soda', 'Muffin'];
    
    foreach ($orders as $order) {
        // Calculate revenue
        $price = (float) str_replace('₱', '', $order['product_price']);
        $stats['total_revenue'] += $price;
        
        // Count products
        $product = $order['product_name'];
        if (!isset($product_count[$product])) {
            $product_count[$product] = 0;
        }
        $product_count[$product]++;
        
        // Count recommended products sold
        if (in_array($product, $recommended_products)) {
            $stats['recommended_sold']++;
        }
    }
    
    // Sort products by count
    arsort($product_count);
    $stats['popular_products'] = $product_count;
}

// Reverse to show newest first
$orders = array_reverse($orders);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Coffee Shop Orders</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-green: #3d5a3d;
            --dark-green: #264d2a;
            --light-bg: #f5f5f0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            color: #333;
        }

        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        header {
            background: linear-gradient(135deg, var(--dark-green) 0%, var(--primary-green) 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header h1 {
            font-size: 2rem;
        }

        header .refresh-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid white;
            padding: 0.6rem 1.2rem;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        header .refresh-btn:hover {
            background: white;
            color: var(--primary-green);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .stat-card h3 {
            color: var(--primary-green);
            font-size: 0.9rem;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
            opacity: 0.8;
        }

        .stat-card .value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark-green);
        }

        .stat-card .subtext {
            font-size: 0.85rem;
            color: #999;
            margin-top: 0.5rem;
        }

        .section {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .section h2 {
            color: var(--dark-green);
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--light-bg);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: var(--light-bg);
            color: var(--dark-green);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #ddd;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }

        tr:hover {
            background-color: #fafaf8;
        }

        .recommended-badge {
            display: inline-block;
            background: #ff4444;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-badge {
            display: inline-block;
            padding: 0.4rem 0.9rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #999;
        }

        .empty-state svg {
            width: 100px;
            height: 100px;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        .popular-list {
            list-style: none;
        }

        .popular-list li {
            padding: 0.8rem 0;
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid #eee;
        }

        .popular-list li:last-child {
            border-bottom: none;
        }

        .count-badge {
            background: var(--primary-green);
            color: white;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 1rem;
            color: var(--primary-green);
            text-decoration: none;
            font-weight: 600;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            table {
                font-size: 0.9rem;
            }

            th, td {
                padding: 0.7rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <a href="index.php" class="back-link">← Back to Menu</a>

        <header>
            <h1>☕ Admin Dashboard</h1>
            <button class="refresh-btn" onclick="location.reload()">Refresh</button>
        </header>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Orders</h3>
                <div class="value"><?php echo $stats['total_orders']; ?></div>
                <div class="subtext">All time orders</div>
            </div>

            <div class="stat-card">
                <h3>Total Revenue</h3>
                <div class="value">₱<?php echo number_format($stats['total_revenue'], 2); ?></div>
                <div class="subtext">From all orders</div>
            </div>

            <div class="stat-card">
                <h3>Recommended Sold</h3>
                <div class="value"><?php echo $stats['recommended_sold']; ?></div>
                <div class="subtext">Recommended products ordered</div>
            </div>

            <?php if (!empty($stats['popular_products'])): ?>
                <div class="stat-card">
                    <h3>Most Popular</h3>
                    <div class="value"><?php echo array_key_first($stats['popular_products']); ?></div>
                    <div class="subtext"><?php echo reset($stats['popular_products']); ?> orders</div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Popular Products Section -->
        <?php if (!empty($stats['popular_products'])): ?>
            <div class="section">
                <h2>Popular Products</h2>
                <ul class="popular-list">
                    <?php foreach ($stats['popular_products'] as $product => $count): ?>
                        <li>
                            <span><?php echo htmlspecialchars($product); ?></span>
                            <span class="count-badge"><?php echo $count; ?> orders</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Orders Table -->
        <div class="section">
            <h2>Recent Orders</h2>
            <?php if (!empty($orders)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Date & Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($order['order_id']); ?></strong></td>
                                <td>
                                    <?php echo htmlspecialchars($order['product_name']); ?>
                                    <?php if (in_array($order['product_name'], ['Manual Brew', 'Vanilla Latte', 'Orange Soda', 'Muffin'])): ?>
                                        <span class="recommended-badge">Recommended</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($order['product_price']); ?></td>
                                <td><?php echo date('M d, Y • H:i', strtotime($order['order_date'])); ?></td>
                                <td><span class="status-badge status-pending">Pending</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="50" cy="50" r="45" fill="none" stroke="currentColor" stroke-width="2"/>
                        <path d="M50 30 L50 50 L65 65" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/>
                    </svg>
                    <h3>No Orders Yet</h3>
                    <p>Orders will appear here when customers place them.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>