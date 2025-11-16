<?php
/**
 * Affected Products & Recipe Management
 * View and manage which products are affected by inventory
 */
require_once '../config.php';
requireAdmin();

$flash = getFlashMessage();

// Get all products with their ingredient requirements
$stmt = $pdo->query("
    SELECT 
        p.id,
        p.name as product_name,
        p.price,
        p.stock_status,
        p.is_active,
        c.name as category_name,
        COUNT(DISTINCT pi.ingredient_id) as ingredient_count,
        GROUP_CONCAT(
            CONCAT(inv.name, ' (', pi.quantity_needed, ' ', inv.unit, ')')
            SEPARATOR ', '
        ) as ingredients_list
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN product_ingredients pi ON p.id = pi.product_id
    LEFT JOIN product_inventory inv ON pi.ingredient_id = inv.id
    WHERE p.is_active = 1
    GROUP BY p.id, p.name, p.price, p.stock_status, p.is_active, c.name
    ORDER BY p.stock_status DESC, p.name ASC
");
$products = $stmt->fetchAll();

// Get products with issues
$affectedProducts = array_filter($products, function($p) {
    return $p['stock_status'] !== 'in_stock';
});

// Get products without ingredients
$productsWithoutIngredients = array_filter($products, function($p) {
    return $p['ingredient_count'] == 0;
});

$pageTitle = "Product Ingredients & Stock Status";
include 'includes/header.php';
?>

<div class="page-header">
    <h1>🔗 Product Ingredients Manager</h1>
    <a href="inventory.php" class="btn btn-secondary">← Back to Inventory</a>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?>">
        <?= e($flash['message']) ?>
    </div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card stat-blue">
        <div class="stat-icon">📦</div>
        <div class="stat-content">
            <div class="stat-label">Total Products</div>
            <div class="stat-value"><?= count($products) ?></div>
        </div>
    </div>
    
    <div class="stat-card stat-red">
        <div class="stat-icon">⚠️</div>
        <div class="stat-content">
            <div class="stat-label">Affected by Stock</div>
            <div class="stat-value"><?= count($affectedProducts) ?></div>
        </div>
    </div>
    
    <div class="stat-card stat-orange">
        <div class="stat-icon">❌</div>
        <div class="stat-content">
            <div class="stat-label">No Ingredients Linked</div>
            <div class="stat-value"><?= count($productsWithoutIngredients) ?></div>
        </div>
    </div>
</div>

<!-- Products Table -->
<div class="dashboard-card">
    <div class="card-header">
        <h2>📋 All Products & Their Ingredients</h2>
        <input type="text" 
               id="searchInput" 
               class="search-input" 
               placeholder="🔍 Search products..." 
               onkeyup="searchTable()">
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table" id="productsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock Status</th>
                        <th>Ingredients</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <?php
                        $rowClass = '';
                        if ($product['stock_status'] === 'out_of_stock') {
                            $rowClass = 'out-of-stock-row';
                        } elseif ($product['stock_status'] === 'low_stock') {
                            $rowClass = 'low-stock-row';
                        } elseif ($product['ingredient_count'] == 0) {
                            $rowClass = 'no-ingredients-row';
                        }
                        ?>
                        <tr class="<?= $rowClass ?>">
                            <td><?= $product['id'] ?></td>
                            <td><strong><?= e($product['product_name']) ?></strong></td>
                            <td><?= e($product['category_name']) ?></td>
                            <td>₱<?= number_format($product['price'], 2) ?></td>
                            <td>
                                <?php if ($product['stock_status'] === 'out_of_stock'): ?>
                                    <span class="status-badge badge-red">🚫 Out of Stock</span>
                                <?php elseif ($product['stock_status'] === 'low_stock'): ?>
                                    <span class="status-badge badge-orange">⚠️ Low Stock</span>
                                <?php else: ?>
                                    <span class="status-badge badge-green">✅ In Stock</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($product['ingredient_count'] > 0): ?>
                                    <div class="ingredients-list">
                                        <?= e($product['ingredients_list']) ?>
                                    </div>
                                <?php else: ?>
                                    <span class="no-ingredients">❌ No ingredients linked</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="edit_product_ingredients.php?product_id=<?= $product['id'] ?>" 
                                       class="btn-icon btn-icon-blue" 
                                       title="Manage Ingredients">
                                        🔗
                                    </a>
                                    <a href="products.php?action=edit&id=<?= $product['id'] ?>" 
                                       class="btn-icon btn-icon-green" 
                                       title="Edit Product">
                                        ✏️
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Quick Guide
<div class="dashboard-card">
    <div class="card-header">
        <h2>📚 How to Link Ingredients to Products</h2>
    </div>
    <div class="card-body">
        <div class="guide-content">
            <h3>Option 1: Use phpMyAdmin (Quick)</h3>
            <ol>
                <li>Go to phpMyAdmin</li>
                <li>Select your database</li>
                <li>Run this SQL query to link ingredients:</li>
            </ol>
            <pre class="sql-example">
-- Example: Link Americano to ingredients
INSERT INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Americano' LIMIT 1), 
 (SELECT id FROM product_inventory WHERE name = '20oz Cup' LIMIT 1), 1),
((SELECT id FROM products WHERE name = 'Americano' LIMIT 1), 
 (SELECT id FROM product_inventory WHERE name = 'Espresso Shot' LIMIT 1), 2);

-- Update stock status
CALL update_product_stock_status(
    (SELECT id FROM products WHERE name = 'Americano' LIMIT 1)
);
            </pre>
            
            <h3>Option 2: Bulk Link via SQL (Recommended)</h3>
            <pre class="sql-example">
-- Link ALL coffee products to 20oz Cup
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed)
SELECT 
    p.id,
    (SELECT id FROM product_inventory WHERE name = '20oz Cup' LIMIT 1),
    1
FROM products p
WHERE p.category_id = 1; -- Coffee category

-- Update all products
CALL update_all_product_stock_statuses();
            </pre>
            
            <h3>Legend:</h3>
            <ul>
                <li><span class="status-badge badge-red">🚫 Out of Stock</span> - Missing required ingredients</li>
                <li><span class="status-badge badge-orange">⚠️ Low Stock</span> - Ingredients below threshold</li>
                <li><span class="status-badge badge-green">✅ In Stock</span> - All ingredients available</li>
                <li><span class="no-ingredients">❌ No ingredients linked</span> - Not tracking stock for this product</li>
            </ul>
        </div>
    </div>
</div> -->

<style>
.no-ingredients-row {
    background-color: #fff9e6;
}

.ingredients-list {
    font-size: 0.9rem;
    color: #555;
    line-height: 1.6;
}

.no-ingredients {
    color: #dc3545;
    font-weight: 600;
}

.guide-content {
    padding: 20px;
}

.guide-content h3 {
    color: #2c3e50;
    margin-top: 20px;
    margin-bottom: 10px;
}

.guide-content ol, .guide-content ul {
    margin-left: 20px;
    line-height: 1.8;
}

.sql-example {
    background: #f8f9fa;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    padding: 15px;
    overflow-x: auto;
    font-family: 'Courier New', monospace;
    font-size: 0.9rem;
    margin: 15px 0;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    display: flex;
    align-items: center;
    padding: 20px;
    border-radius: 12px;
    color: white;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.stat-icon {
    font-size: 2.5rem;
    margin-right: 15px;
}

.stat-label {
    font-size: 0.9rem;
    opacity: 0.95;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
}

.stat-blue { background: linear-gradient(135deg, #98abffff 0%, #a05fe0ff 100%); }
.stat-red { background: linear-gradient(135deg, #df9898ff 0%, #ee5a6f 100%); }
.stat-orange { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
</style>

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

<?php include 'includes/footer.php'; ?>