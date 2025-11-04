<?php
/**
 * Analytics & Reports Page
 * Sales analytics, charts, and reports
 */
require_once '../config.php';
requireAdmin();

// Date range filter
$startDate = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$endDate = $_GET['end_date'] ?? date('Y-m-d'); // Today

// Summary Statistics
$stats = [];

// Total Revenue in date range
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(total_amount), 0) as revenue 
    FROM orders 
    WHERE DATE(created_at) BETWEEN ? AND ? AND order_status = 'completed'
");
$stmt->execute([$startDate, $endDate]);
$stats['revenue'] = $stmt->fetch()['revenue'];

// Total Orders in date range
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM orders 
    WHERE DATE(created_at) BETWEEN ? AND ? AND order_status = 'completed'
");
$stmt->execute([$startDate, $endDate]);
$stats['orders'] = $stmt->fetch()['count'];

// Average Order Value
$stats['avg_order'] = $stats['orders'] > 0 ? $stats['revenue'] / $stats['orders'] : 0;

// Total Points Issued
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(total_points), 0) as points 
    FROM orders 
    WHERE DATE(created_at) BETWEEN ? AND ? AND order_status = 'completed'
");
$stmt->execute([$startDate, $endDate]);
$stats['points'] = $stmt->fetch()['points'];

// Daily Sales Data (for chart)
$dailySales = $pdo->prepare("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as orders,
        SUM(total_amount) as revenue
    FROM orders 
    WHERE DATE(created_at) BETWEEN ? AND ? AND order_status = 'completed'
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
$dailySales->execute([$startDate, $endDate]);
$dailyData = $dailySales->fetchAll();

// Top Products in date range
$topProducts = $pdo->prepare("
    SELECT 
        oi.product_name,
        SUM(oi.quantity) as total_sold,
        SUM(oi.subtotal) as revenue,
        p.image
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE DATE(o.created_at) BETWEEN ? AND ? AND o.order_status = 'completed'
    GROUP BY oi.product_id, oi.product_name
    ORDER BY total_sold DESC
    LIMIT 10
");
$topProducts->execute([$startDate, $endDate]);
$topProductsData = $topProducts->fetchAll();

// Category Performance
$categoryPerf = $pdo->prepare("
    SELECT 
        c.name as category,
        COUNT(DISTINCT oi.order_id) as orders,
        SUM(oi.quantity) as items_sold,
        SUM(oi.subtotal) as revenue
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    JOIN products p ON oi.product_id = p.id
    JOIN categories c ON p.category_id = c.id
    WHERE DATE(o.created_at) BETWEEN ? AND ? AND o.order_status = 'completed'
    GROUP BY c.id, c.name
    ORDER BY revenue DESC
");
$categoryPerf->execute([$startDate, $endDate]);
$categoryData = $categoryPerf->fetchAll();

// Hourly Sales Pattern (for peak hours analysis)
$hourlySales = $pdo->prepare("
    SELECT 
        HOUR(created_at) as hour,
        COUNT(*) as orders,
        SUM(total_amount) as revenue
    FROM orders 
    WHERE DATE(created_at) BETWEEN ? AND ? AND order_status = 'completed'
    GROUP BY HOUR(created_at)
    ORDER BY hour ASC
");
$hourlySales->execute([$startDate, $endDate]);
$hourlyData = $hourlySales->fetchAll();

include 'includes/header.php';
?>

<div class="page-header">
    <h1>📈 Sales Analytics</h1>
</div>

<!-- Date Range Filter -->
<div class="dashboard-card">
    <div class="card-header">
        <h2>📅 Select Date Range</h2>
    </div>
    <div class="card-body">
        <form method="GET" action="analytics.php" class="filter-form">
            <div class="form-group">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" id="start_date" name="start_date" class="form-control" value="<?= $startDate ?>" required>
            </div>
            <div class="form-group">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" id="end_date" name="end_date" class="form-control" value="<?= $endDate ?>" required>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">🔍 Filter</button>
                <button type="button" class="btn btn-secondary" onclick="exportReport()">📊 Export CSV</button>
            </div>
        </form>
    </div>
</div>

<!-- Summary Stats -->
<div class="stats-grid">
    <div class="stat-card stat-green">
        <div class="stat-icon">💰</div>
        <div class="stat-content">
            <div class="stat-label">Total Revenue</div>
            <div class="stat-value"><?= formatCurrency($stats['revenue']) ?></div>
        </div>
    </div>
    
    <div class="stat-card stat-blue">
        <div class="stat-icon">🛒</div>
        <div class="stat-content">
            <div class="stat-label">Total Orders</div>
            <div class="stat-value"><?= $stats['orders'] ?></div>
        </div>
    </div>
    
    <div class="stat-card stat-purple">
        <div class="stat-icon">📊</div>
        <div class="stat-content">
            <div class="stat-label">Avg Order Value</div>
            <div class="stat-value"><?= formatCurrency($stats['avg_order']) ?></div>
        </div>
    </div>
    
    <div class="stat-card stat-orange">
        <div class="stat-icon">⭐</div>
        <div class="stat-content">
            <div class="stat-label">Points Issued</div>
            <div class="stat-value"><?= number_format($stats['points']) ?></div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="dashboard-grid">
    <!-- Daily Sales Chart -->
    <div class="dashboard-card">
        <div class="card-header">
            <h2>📈 Daily Sales Trend</h2>
        </div>
        <div class="card-body">
            <canvas id="dailySalesChart"></canvas>
        </div>
    </div>
    
    <!-- Category Performance Chart -->
    <div class="dashboard-card">
        <div class="card-header">
            <h2>🗂️ Category Performance</h2>
        </div>
        <div class="card-body">
            <canvas id="categoryChart"></canvas>
        </div>
    </div>
</div>

<!-- Top Products -->
<div class="dashboard-card">
    <div class="card-header">
        <h2>🏆 Top 10 Best-Selling Products</h2>
    </div>
    <div class="card-body">
        <?php if (empty($topProductsData)): ?>
            <p class="no-data">No sales data in selected period</p>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Image</th>
                        <th>Product</th>
                        <th>Units Sold</th>
                        <th>Revenue</th>
                        <th>Performance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $rank = 1;
                    $maxSold = $topProductsData[0]['total_sold'];
                    foreach ($topProductsData as $product): 
                        $percentage = ($product['total_sold'] / $maxSold) * 100;
                    ?>
                        <tr>
                            <td>
                                <?php if ($rank <= 3): ?>
                                    <span class="rank-medal rank-<?= $rank ?>"><?= $rank ?></span>
                                <?php else: ?>
                                    <span class="rank-number"><?= $rank ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <img src="../<?= e($product['image']) ?>" 
                                     alt="<?= e($product['product_name']) ?>" 
                                     class="product-thumb"
                                     onerror="this.src='../images/placeholder.jpg'">
                            </td>
                            <td><strong><?= e($product['product_name']) ?></strong></td>
                            <td><?= $product['total_sold'] ?> units</td>
                            <td><strong><?= formatCurrency($product['revenue']) ?></strong></td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= $percentage ?>%"></div>
                                </div>
                            </td>
                        </tr>
                    <?php 
                        $rank++;
                    endforeach; 
                    ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Hourly Sales Pattern -->
<div class="dashboard-card">
    <div class="card-header">
        <h2>🕐 Peak Hours Analysis</h2>
    </div>
    <div class="card-body">
        <?php if (empty($hourlyData)): ?>
            <p class="no-data">No data available</p>
        <?php else: ?>
            <div class="hourly-chart">
                <?php 
                $maxHourlyRevenue = max(array_column($hourlyData, 'revenue'));
                foreach ($hourlyData as $hour): 
                    $height = $maxHourlyRevenue > 0 ? ($hour['revenue'] / $maxHourlyRevenue) * 100 : 0;
                    $hourLabel = str_pad($hour['hour'], 2, '0', STR_PAD_LEFT) . ':00';
                ?>
                    <div class="hour-bar">
                        <div class="bar-fill" style="height: <?= $height ?>%" title="<?= formatCurrency($hour['revenue']) ?>"></div>
                        <div class="hour-label"><?= $hourLabel ?></div>
                        <div class="hour-orders"><?= $hour['orders'] ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Prepare data for charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Daily Sales Chart
const dailyCtx = document.getElementById('dailySalesChart').getContext('2d');
new Chart(dailyCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($dailyData, 'date')) ?>,
        datasets: [{
            label: 'Revenue (₱)',
            data: <?= json_encode(array_column($dailyData, 'revenue')) ?>,
            borderColor: '#3d5a3d',
            backgroundColor: 'rgba(61, 90, 61, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});

// Category Chart
const categoryCtx = document.getElementById('categoryChart').getContext('2d');
new Chart(categoryCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($categoryData, 'category')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($categoryData, 'revenue')) ?>,
            backgroundColor: [
                '#3d5a3d',
                '#28a745',
                '#17a2b8',
                '#ffc107',
                '#dc3545'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

function exportReport() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    alert(`Exporting report for ${startDate} to ${endDate}...\n\nThis will download a CSV file with all sales data.`);
    // In production, this would call a PHP script to generate CSV
}
</script>

<style>
canvas {
    max-height: 300px;
}

.rank-medal {
    display: inline-block;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    text-align: center;
    line-height: 30px;
    font-weight: bold;
    color: white;
}

.rank-1 { background: #FFD700; color: #333; }
.rank-2 { background: #C0C0C0; color: #333; }
.rank-3 { background: #CD7F32; color: white; }

.rank-number {
    display: inline-block;
    width: 30px;
    text-align: center;
    font-weight: 600;
    color: #666;
}

.hourly-chart {
    display: flex;
    align-items: flex-end;
    justify-content: space-around;
    height: 250px;
    padding: 20px 0;
    gap: 0.5rem;
}

.hour-bar {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
}

.bar-fill {
    width: 100%;
    background: linear-gradient(to top, #3d5a3d, #28a745);
    border-radius: 5px 5px 0 0;
    min-height: 5px;
    transition: all 0.3s ease;
    cursor: pointer;
}

.bar-fill:hover {
    background: linear-gradient(to top, #264d2a, #1e7e34);
}

.hour-label {
    font-size: 0.75rem;
    color: #666;
    font-weight: 600;
}

.hour-orders {
    font-size: 0.7rem;
    color: #999;
}
</style>

<?php include 'includes/footer.php'; ?>