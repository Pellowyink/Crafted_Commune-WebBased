<?php
/**
 * Product Ratings Analytics
 * View customer ratings for individual products
 */
require_once '../config.php';
requireAdmin();

// Get product ratings statistics
$productsStmt = $pdo->query("
    SELECT * FROM v_product_rating_stats
    WHERE rating_count > 0
    ORDER BY average_rating DESC, rating_count DESC
");
$products = $productsStmt->fetchAll();

// Get overall statistics
$overallStats = $pdo->query("
    SELECT 
        COUNT(DISTINCT product_id) as rated_products,
        COUNT(*) as total_ratings,
        AVG(rating) as overall_average,
        COUNT(CASE WHEN rating = 5 THEN 1 END) as five_star_count,
        COUNT(CASE WHEN rating >= 4 THEN 1 END) as positive_ratings
    FROM product_ratings
")->fetch();

$overallStats['positive_percentage'] = $overallStats['total_ratings'] > 0 
    ? ($overallStats['positive_ratings'] / $overallStats['total_ratings']) * 100 
    : 0;

// Get recent ratings with customer info
$recentRatings = $pdo->query("
    SELECT 
        pr.rating,
        pr.created_at,
        p.name as product_name,
        p.image as product_image,
        lm.name as customer_name,
        lm.email as customer_email,
        o.order_number
    FROM product_ratings pr
    JOIN products p ON pr.product_id = p.id
    JOIN loyalty_members lm ON pr.member_id = lm.id
    JOIN orders o ON pr.order_id = o.id
    ORDER BY pr.created_at DESC
    LIMIT 20
")->fetchAll();

// Get top rated products
$topRated = $pdo->query("
    SELECT * FROM v_product_rating_stats
    WHERE rating_count >= 3
    ORDER BY average_rating DESC, rating_count DESC
    LIMIT 10
")->fetchAll();

// Get products needing improvement (low ratings)
$needsImprovement = $pdo->query("
    SELECT * FROM v_product_rating_stats
    WHERE rating_count >= 3 AND average_rating < 3.5
    ORDER BY average_rating ASC
    LIMIT 5
")->fetchAll();

include 'includes/header.php';
?>

<div class="page-header">
    <h1>⭐ Product Ratings Analytics</h1>
</div>

<!-- Overall Stats -->
<div class="stats-grid">
    <div class="stat-card stat-blue">
        <div class="stat-icon">📦</div>
        <div class="stat-content">
            <div class="stat-label">Rated Products</div>
            <div class="stat-value"><?= $overallStats['rated_products'] ?></div>
        </div>
    </div>
    
    <div class="stat-card stat-gold">
        <div class="stat-icon">⭐</div>
        <div class="stat-content">
            <div class="stat-label">Overall Average</div>
            <div class="stat-value"><?= number_format($overallStats['overall_average'], 2) ?> / 5</div>
        </div>
    </div>
    
    <div class="stat-card stat-green">
        <div class="stat-icon">👍</div>
        <div class="stat-content">
            <div class="stat-label">Positive Ratings</div>
            <div class="stat-value"><?= number_format($overallStats['positive_percentage'], 1) ?>%</div>
        </div>
    </div>
    
    <div class="stat-card stat-purple">
        <div class="stat-icon">📊</div>
        <div class="stat-content">
            <div class="stat-label">Total Ratings</div>
            <div class="stat-value"><?= number_format($overallStats['total_ratings']) ?></div>
        </div>
    </div>
</div>

<!-- Top Rated Products -->
<?php if (!empty($topRated)): ?>
<div class="dashboard-card">
    <div class="card-header">
        <h2>🏆 Top Rated Products</h2>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Avg Rating</th>
                        <th>Total Ratings</th>
                        <th>Distribution</th>
                        <th>Auto-Recommended</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $rank = 1;
                    foreach ($topRated as $product): 
                    ?>
                        <tr>
                            <td>
                                <?php if ($rank <= 3): ?>
                                    <span class="rank-medal rank-<?= $rank ?>"><?= $rank ?></span>
                                <?php else: ?>
                                    <span class="rank-number"><?= $rank ?></span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= htmlspecialchars($product['product_name']) ?></strong></td>
                            <td><?= htmlspecialchars($product['category_name']) ?></td>
                            <td>
                                <div class="rating-display">
                                    <span class="star-rating-large"><?= str_repeat('⭐', round($product['average_rating'])) ?></span>
                                    <span class="rating-number"><?= number_format($product['average_rating'], 2) ?></span>
                                </div>
                            </td>
                            <td><?= $product['rating_count'] ?> ratings</td>
                            <td>
                                <div class="mini-distribution">
                                    <div class="dist-bar">
                                        <span title="5 stars: <?= $product['five_star'] ?>">⭐<?= $product['five_star'] ?></span>
                                        <span title="4 stars: <?= $product['four_star'] ?>">⭐<?= $product['four_star'] ?></span>
                                        <span title="3 stars: <?= $product['three_star'] ?>">⭐<?= $product['three_star'] ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if ($product['is_recommended']): ?>
                                    <span class="badge badge-green">✅ Yes</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">No (<?= $product['rating_count'] ?>/5 ratings)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php 
                        $rank++;
                    endforeach; 
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Products Needing Improvement -->
<?php if (!empty($needsImprovement)): ?>
<div class="dashboard-card">
    <div class="card-header">
        <h2>⚠️ Products Needing Attention</h2>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Avg Rating</th>
                        <th>Total Ratings</th>
                        <th>Issues</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($needsImprovement as $product): ?>
                        <tr class="warning-row">
                            <td><strong><?= htmlspecialchars($product['product_name']) ?></strong></td>
                            <td><?= htmlspecialchars($product['category_name']) ?></td>
                            <td>
                                <span class="rating-low"><?= number_format($product['average_rating'], 2) ?> ⭐</span>
                            </td>
                            <td><?= $product['rating_count'] ?> ratings</td>
                            <td>
                                <span class="badge badge-danger">
                                    <?= $product['one_star'] + $product['two_star'] ?> negative ratings
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p style="margin-top: 15px; color: #dc3545;">
            <strong>Action Needed:</strong> Consider reviewing recipe, quality, or removing from menu.
        </p>
    </div>
</div>
<?php endif; ?>

<!-- All Products with Ratings -->
<div class="dashboard-card">
    <div class="card-header">
        <h2>📋 All Product Ratings</h2>
        <input 
            type="text" 
            id="searchInput" 
            class="search-input" 
            placeholder="🔍 Search products..." 
            onkeyup="searchTable()"
        >
    </div>
    <div class="card-body">
        <?php if (empty($products)): ?>
            <p class="no-data">No product ratings yet</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table" id="productsTable">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Avg Rating</th>
                            <th>Ratings</th>
                            <th>5⭐</th>
                            <th>4⭐</th>
                            <th>3⭐</th>
                            <th>2⭐</th>
                            <th>1⭐</th>
                            <th>Auto-Recommended</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($product['product_name']) ?></strong></td>
                                <td><?= htmlspecialchars($product['category_name']) ?></td>
                                <td>
                                    <span class="rating-badge <?= $product['average_rating'] >= 4 ? 'rating-high' : ($product['average_rating'] >= 3 ? 'rating-mid' : 'rating-low') ?>">
                                        <?= number_format($product['average_rating'], 2) ?> ⭐
                                    </span>
                                </td>
                                <td><?= $product['rating_count'] ?></td>
                                <td><?= $product['five_star'] ?></td>
                                <td><?= $product['four_star'] ?></td>
                                <td><?= $product['three_star'] ?></td>
                                <td><?= $product['two_star'] ?></td>
                                <td><?= $product['one_star'] ?></td>
                                <td>
                                    <?php if ($product['is_recommended']): ?>
                                        <span class="badge badge-green">✅ Yes</span>
                                    <?php elseif ($product['average_rating'] >= 4.0 && $product['rating_count'] >= 5): ?>
                                        <span class="badge badge-warning">📊 Qualifies</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">No</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Recent Ratings -->
<div class="dashboard-card">
    <div class="card-header">
        <h2>🕐 Recent Customer Ratings</h2>
    </div>
    <div class="card-body">
        <?php if (empty($recentRatings)): ?>
            <p class="no-data">No recent ratings</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Product</th>
                            <th>Customer</th>
                            <th>Order #</th>
                            <th>Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentRatings as $rating): ?>
                            <tr>
                                <td><?= formatDateTime($rating['created_at']) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($rating['product_name']) ?></strong>
                                </td>
                                <td>
                                    <?= htmlspecialchars($rating['customer_name']) ?><br>
                                    <small><?= htmlspecialchars($rating['customer_email']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($rating['order_number']) ?></td>
                                <td>
                                    <span class="star-display"><?= str_repeat('⭐', $rating['rating']) ?></span>
                                    <span class="rating-number"><?= $rating['rating'] ?>/5</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="info-banner">
    <h3>📊 Auto-Recommendation System</h3>
    <p>Products are automatically marked as "Recommended" when they meet these criteria:</p>
    <ul>
        <li>✅ Average rating of <strong>4.0 stars or higher</strong></li>
        <li>✅ Minimum of <strong>5 customer ratings</strong></li>
    </ul>
    <p>This ensures only consistently high-quality products get the recommendation badge!</p>
</div>

<script>
function searchTable() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toUpperCase();
    const table = document.getElementById('productsTable');
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const text = row.textContent || row.innerText;
        row.style.display = text.toUpperCase().indexOf(filter) > -1 ? '' : 'none';
    }
}
</script>

<style>
.rating-display {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.star-rating-large {
    font-size: 1.2rem;
}

.rating-number {
    font-weight: 600;
    color: #666;
}

.rating-badge {
    padding: 5px 10px;
    border-radius: 5px;
    font-weight: 600;
}

.rating-high {
    background: #d4edda;
    color: #155724;
}

.rating-mid {
    background: #fff3cd;
    color: #856404;
}

.rating-low {
    background: #f8d7da;
    color: #721c24;
}

.mini-distribution {
    font-size: 0.85rem;
}

.dist-bar {
    display: flex;
    gap: 0.5rem;
}

.warning-row {
    background: #fff5f5;
}

.rank-medal {
    display: inline-block;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    text-align: center;
    line-height: 30px;
    font-weight: bold;
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

.info-banner {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 15px;
    margin-top: 30px;
}

.info-banner h3 {
    margin-bottom: 15px;
}

.info-banner ul {
    margin: 15px 0;
    padding-left: 20px;
}

.info-banner li {
    margin: 10px 0;
}
</style>

<?php include 'includes/footer.php'; ?>