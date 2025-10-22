<?php
/**
 * Admin Dashboard - Main Statistics & Overview
 */
require_once '../config.php';
requireAdmin();

// Get dashboard statistics
$stats = [];

// Total Revenue (Today)
$stmt = $pdo->query("
    SELECT COALESCE(SUM(total_amount), 0) as revenue 
    FROM orders 
    WHERE DATE(created_at) = CURDATE() AND order_status = 'completed'
");
$stats['today_revenue'] = $stmt->fetch()['revenue'];

// Total Orders (Today)
$stmt = $pdo->query("
    SELECT COUNT(*) as count 
    FROM orders 
    WHERE DATE(created_at) = CURDATE() AND order_status = 'completed'
");
$stats['today_orders'] = $stmt->fetch()['count'];

// Total Revenue (All Time)
$stmt = $pdo->query("
    SELECT COALESCE(SUM(total_amount), 0) as revenue 
    FROM orders 
    WHERE order_status = 'completed'
");
$stats['total_revenue'] = $stmt->fetch()['revenue'];

// Total Orders (All Time)
$stmt = $pdo->query("
    SELECT COUNT(*) as count 
    FROM orders 
    WHERE order_status = 'completed'
");
$stats['total_orders'] = $stmt->fetch()['count'];

// Average Order Value
$stats['avg_order'] = $stats['total_orders'] > 0 ? $stats['total_revenue'] / $stats['total_orders'] : 0;

// Total Products
$stmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE is_active = 1");
$stats['total_products'] = $stmt->fetch()['count'];

// Best Selling Products (Top 5)
$bestSelling = $pdo->query("
    SELECT * FROM v_best_selling_products 
    WHERE total_quantity_sold > 0
    LIMIT 5
")->fetchAll();

// Recent Orders (Last 10)
$recentOrders = $pdo->query("
    SELECT * FROM orders 
    ORDER BY created_at DESC 
    LIMIT 10
")->fetchAll();

// Daily Sales (Last 7 days)
$dailySales = $pdo->query("
    SELECT * FROM v_daily_sales 
    ORDER BY sale_date DESC 
    LIMIT 7
")->fetchAll();

// Category Performance
$categoryPerformance = $pdo->query("
    SELECT * FROM v_category_performance 
    ORDER BY total_revenue DESC
")->fetchAll();

// Get flash message
$flash = getFlashMessage();

include 'includes/header.php';
?>

<!-- Dashboard Content -->
<div class="dashboard-header">
    <h1>📊 Dashboard Overview</h1>
    <p style="center">Welcome back, <strong><?= e($_SESSION['admin_name']) ?></strong>!</p>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?>">
        <?= e($flash['message']) ?>
    </div>
<?php endif; ?>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card stat-green">
        <div class="stat-icon">💰</div>
        <div class="stat-content">
            <div class="stat-label">Today's Revenue</div>
            <div class="stat-value"><?= formatCurrency($stats['today_revenue']) ?></div>
        </div>
    </div>
    
    <div class="stat-card stat-blue">
        <div class="stat-icon">🛒</div>
        <div class="stat-content">
            <div class="stat-label">Today's Orders</div>
            <div class="stat-value"><?= $stats['today_orders'] ?></div>
        </div>
    </div>
    
    <div class="stat-card stat-purple">
        <div class="stat-icon">📈</div>
        <div class="stat-content">
            <div class="stat-label">Total Revenue</div>
            <div class="stat-value"><?= formatCurrency($stats['total_revenue']) ?></div>
        </div>
    </div>
    
    <div class="stat-card stat-orange">
        <div class="stat-icon">📦</div>
        <div class="stat-content">
            <div class="stat-label">Active Products</div>
            <div class="stat-value"><?= $stats['total_products'] ?></div>
        </div>
    </div>
</div>

<!-- Two Column Layout -->
<div class="dashboard-grid">
    <!-- Best Selling Products -->
    <div class="dashboard-card">
        <div class="card-header">
            <h2>🏆 Best Selling Products</h2>
            <a href="products.php" class="view-all">View All →</a>
        </div>
        <div class="card-body">
            <?php if (empty($bestSelling)): ?>
                <p class="no-data">No sales data yet</p>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Sold</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bestSelling as $product): ?>
                            <tr>
                                <td>
                                    <strong><?= e($product['name']) ?></strong>
                                    <?php if ($product['is_recommended']): ?>
                                        <span class="badge badge-red">★ Recommended</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($product['category']) ?></td>
                                <td><?= $product['total_quantity_sold'] ?> units</td>
                                <td><?= formatCurrency($product['total_revenue']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Recent Orders -->
    <div class="dashboard-card">
        <div class="card-header">
            <h2>📋 Recent Orders</h2>
            <a href="orders.php" class="view-all">View All →</a>
        </div>
        <div class="card-body">
            <?php if (empty($recentOrders)): ?>
                <p class="no-data">No orders yet</p>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Amount</th>
                            <th>Points</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td><strong><?= e($order['order_number']) ?></strong></td>
                                <td><?= formatCurrency($order['total_amount']) ?></td>
                                <td><span class="badge badge-gold"><?= $order['total_points'] ?> pts</span></td>
                                <td><?= formatDateTime($order['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Category Performance -->
<div class="dashboard-card full-width">
    <div class="card-header">
        <h2>📊 Category Performance</h2>
    </div>
    <div class="card-body">
        <?php if (empty($categoryPerformance)): ?>
            <p class="no-data">No category data yet</p>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Products</th>
                        <th>Items Sold</th>
                        <th>Total Orders</th>
                        <th>Revenue</th>
                        <th>Performance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $maxRevenue = max(array_column($categoryPerformance, 'total_revenue'));
                    foreach ($categoryPerformance as $cat): 
                        $percentage = $maxRevenue > 0 ? ($cat['total_revenue'] / $maxRevenue) * 100 : 0;
                    ?>
                        <tr>
                            <td><strong><?= e($cat['category_name']) ?></strong></td>
                            <td><?= $cat['product_count'] ?></td>
                            <td><?= $cat['total_items_sold'] ?? 0 ?> units</td>
                            <td><?= $cat['times_ordered'] ?? 0 ?></td>
                            <td><?= formatCurrency($cat['total_revenue'] ?? 0) ?></td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= $percentage ?>%"></div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Daily Sales Chart -->
<div class="dashboard-card full-width">
    <div class="card-header">
        <h2>📈 Sales Trend (Last 7 Days)</h2>
    </div>
    <div class="card-body">
        <?php if (empty($dailySales)): ?>
            <p class="no-data">No sales data yet</p>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Orders</th>
                        <th>Revenue</th>
                        <th>Points Issued</th>
                        <th>Avg. Order Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dailySales as $day): ?>
                        <tr>
                            <td><strong><?= formatDate($day['sale_date']) ?></strong></td>
                            <td><?= $day['total_orders'] ?></td>
                            <td><?= formatCurrency($day['total_revenue']) ?></td>
                            <td><span class="badge badge-gold"><?= $day['total_points'] ?> pts</span></td>
                            <td><?= formatCurrency($day['average_order_value']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>