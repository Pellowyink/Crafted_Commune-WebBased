<?php
/**
 * Product Inventory Management Page
 * Manage raw ingredients and view stock levels
 */
require_once '../config.php';
requireAdmin();

$flash = getFlashMessage();

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    try {
        $stmt = $pdo->prepare("SELECT name FROM product_inventory WHERE id = ?");
        $stmt->execute([$id]);
        $ingredient = $stmt->fetch();
        
        if ($ingredient) {
            $deleteStmt = $pdo->prepare("DELETE FROM product_inventory WHERE id = ?");
            $deleteStmt->execute([$id]);
            
            logActivity($_SESSION['admin_id'], 'delete_ingredient', "Deleted ingredient: " . $ingredient['name']);
            setFlashMessage('success', "Ingredient '{$ingredient['name']}' deleted successfully!");
        }
    } catch (PDOException $e) {
        setFlashMessage('error', 'Error deleting ingredient: ' . $e->getMessage());
    }
    
    redirect('inventory.php');
}

// Fetch all ingredients with stock status
$stmt = $pdo->prepare("
    SELECT 
        id, 
        name, 
        stock,
        category,
        low_stock_threshold,
        unit,
        updated_at
    FROM product_inventory 
    ORDER BY 
        CASE 
            WHEN stock = 0 THEN 1
            WHEN stock <= low_stock_threshold THEN 2
            ELSE 3
        END,
        id ASC
");
$stmt->execute();
$ingredients = $stmt->fetchAll();

// Calculate statistics
$totalIngredients = count($ingredients);
$totalStock = array_sum(array_column($ingredients, 'stock'));
$outOfStockCount = count(array_filter($ingredients, function($i) { return $i['stock'] == 0; }));
$lowStockCount = count(array_filter($ingredients, function($i) { 
    return $i['stock'] > 0 && $i['stock'] <= $i['low_stock_threshold']; 
}));

// Get affected products count
$affectedStmt = $pdo->query("
    SELECT COUNT(DISTINCT p.id) as affected_products
    FROM products p
    WHERE p.stock_status IN ('low_stock', 'out_of_stock')
");
$affectedProducts = $affectedStmt->fetch()['affected_products'];

$pageTitle = "Product Inventory";
include 'includes/header.php';
?>

<div class="page-header">
    <h1>📦 Product Inventory Management</h1>
    <a href="productadd_edit.php" class="btn btn-primary">➕ Add New Ingredient</a>
    <a href="affected_products.php" class="btn btn-primary">→ Ingredients Manager</a>
</div>
    


<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?>">
        <?= e($flash['message']) ?>
    </div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card stat-blue">
        <div class="stat-icon">🏷️</div>
        <div class="stat-content">
            <div class="stat-label">Total Ingredients</div>
            <div class="stat-value"><?= $totalIngredients ?></div>
        </div>
    </div>
    
    <div class="stat-card stat-green">
        <div class="stat-icon">📦</div>
        <div class="stat-content">
            <div class="stat-label">Total Stock Units</div>
            <div class="stat-value"><?= number_format($totalStock) ?></div>
        </div>
    </div>
    
    <div class="stat-card stat-orange">
        <div class="stat-icon">⚠️</div>
        <div class="stat-content">
            <div class="stat-label">Low Stock Items</div>
            <div class="stat-value"><?= $lowStockCount ?></div>
        </div>
    </div>
    
    <div class="stat-card stat-red">
        <div class="stat-icon">🚫</div>
        <div class="stat-content">
            <div class="stat-label">Out of Stock</div>
            <div class="stat-value"><?= $outOfStockCount ?></div>
        </div>
    </div>
</div>

<?php if ($affectedProducts > 0): ?>
<div class="alert alert-warning" style="margin-bottom: 20px;">
    ⚠️ <strong>Warning:</strong> <?= $affectedProducts ?> menu product(s) are affected by low/out-of-stock ingredients!
    <a href="affected_products.php" style="margin-left: 10px; text-decoration: underline;">View Affected Products →</a>
</div>
<?php endif; ?>

<!-- Ingredients Table -->
<div class="dashboard-card">
    <div class="card-header">
        <h2>🧾 All Ingredients (<?= $totalIngredients ?>)</h2>
        <input type="text" 
               id="searchInput" 
               class="search-input" 
               placeholder="🔍 Search ingredients..." 
               onkeyup="searchTable()">
    </div>
    <div class="card-body">
        <?php if (empty($ingredients)): ?>
            <p class="no-data">No ingredients found. Add your first ingredient!</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table" id="ingredientsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Current Stock</th>
                            <th>Low Stock Alert</th>
                            <th>Status</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ingredients as $ingredient): ?>
                            <?php
                            $isOutOfStock = $ingredient['stock'] == 0;
                            $isLowStock = $ingredient['stock'] > 0 && $ingredient['stock'] <= $ingredient['low_stock_threshold'];
                            $rowClass = $isOutOfStock ? 'out-of-stock-row' : ($isLowStock ? 'low-stock-row' : '');
                            ?>
                            <tr class="<?= $rowClass ?>">
                                <td><?= $ingredient['id'] ?></td>
                                <td><strong><?= e($ingredient['name']) ?></strong></td>
                                <td><?= e($ingredient['category']) ?></td>
                                <td class="stock-cell">
                                    <span class="stock-amount"><?= $ingredient['stock'] ?></span>
                                    <span class="stock-unit"><?= e($ingredient['unit']) ?></span>
                                </td>
                                <td><?= $ingredient['low_stock_threshold'] ?> <?= e($ingredient['unit']) ?></td>
                                <td>
                                    <?php if ($isOutOfStock): ?>
                                        <span class="status-badge badge-red">🚫 Out of Stock</span>
                                    <?php elseif ($isLowStock): ?>
                                        <span class="status-badge badge-orange">⚠️ Low Stock</span>
                                    <?php else: ?>
                                        <span class="status-badge badge-green">✅ In Stock</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('M d, Y', strtotime($ingredient['updated_at'])) ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="productadd_edit.php?action=edit&id=<?= $ingredient['id'] ?>" 
                                           class="btn-icon btn-icon-blue" 
                                           title="Edit">
                                            ✏️
                                        </a>
                                        <button onclick="quickUpdateStock(<?= $ingredient['id'] ?>, '<?= e($ingredient['name']) ?>', <?= $ingredient['stock'] ?>)" 
                                                class="btn-icon btn-icon-green" 
                                                title="Quick Update Stock">
                                            📊
                                        </button>
                                        <a href="inventory.php?action=delete&id=<?= $ingredient['id'] ?>" 
                                           class="btn-icon btn-icon-danger" 
                                           onclick="return confirm('Delete this ingredient? This will also remove it from all product recipes!')"
                                           title="Delete">
                                            🗑️
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Stock Update Modal -->
<div id="quickUpdateModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeQuickUpdate()">&times;</span>
        <h2>📊 Quick Stock Update</h2>
        <div id="quickUpdateContent">
            <p><strong id="ingredientName"></strong></p>
            <div class="form-group">
                <label>Current Stock: <span id="currentStock"></span></label>
            </div>
            <div class="form-group">
                <label for="newStock">New Stock Amount:</label>
                <input type="number" id="newStock" min="0" class="form-control" placeholder="Enter new stock">
            </div>
            <div class="button-group">
                <button onclick="saveQuickUpdate()" class="btn btn-primary">💾 Save</button>
                <button onclick="closeQuickUpdate()" class="btn btn-secondary">Cancel</button>
            </div>
        </div>
    </div>
</div>

<style>

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
    transition: transform 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.stat-icon {
    font-size: 2.5rem;
    margin-right: 15px;
    opacity: 0.9;
}

.stat-content {
    flex-grow: 1;
}

.stat-label {
    font-size: 0.9rem;
    font-weight: 400;
    opacity: 0.95;
    margin-bottom: 5px;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
}

.stat-blue { background: linear-gradient(135deg, #98abffff 0%, #a05fe0ff 100%); }
.stat-green { background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%); }
.stat-orange { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
.stat-red { background: linear-gradient(135deg, #df9898ff 0%, #ee5a6f 100%); }

.out-of-stock-row {
    background-color: #ffe6e6 !important;
}

.low-stock-row {
    background-color: #fff9e6 !important;
}

.stock-cell {
    font-weight: bold;
    font-size: 1.1rem;
}

.stock-amount {
    color: #2c3e50;
}

.stock-unit {
    font-size: 0.85rem;
    color: #666;
    margin-left: 3px;
}

.status-badge {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
    white-space: nowrap;
}

.badge-green { background-color: #d4edda; color: #155724; }
.badge-orange { background-color: #fff3cd; color: #856404; }
.badge-red { background-color: #f8e4e6ff; color: #721c24; }

.action-buttons {
    display: flex;
    gap: 8px;
}

.btn-icon {
    background: none;
    border: none;
    font-size: 1.3rem;
    cursor: pointer;
    padding: 5px;
    transition: transform 0.2s;
}

.btn-icon:hover {
    transform: scale(1.2);
}

.search-input {
    padding: 8px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 0.95rem;
    width: 300px;
}

.search-input:focus {
    outline: none;
    border-color: #3d5a3d;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.6);
}

.modal.show {
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    padding: 30px;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
}

.modal-content h2 {
    margin-bottom: 20px;
    color: #2c3e50;
}

.close {
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    color: #999;
}

.close:hover {
    color: #333;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 5px;
}

.form-control {
    width: 100%;
    padding: 10px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 1rem;
}

.button-group {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.alert-warning {
    background: #fff3cd;
    color: #856404;
    border: 2px solid #ffc107;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 2px solid #28a745;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 2px solid #dc3545;
}
</style>

<script>
let currentIngredientId = null;

function searchTable() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toUpperCase();
    const table = document.getElementById('ingredientsTable');
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const text = row.textContent || row.innerText;
        row.style.display = text.toUpperCase().indexOf(filter) > -1 ? '' : 'none';
    }
}

function quickUpdateStock(id, name, currentStock) {
    currentIngredientId = id;
    document.getElementById('ingredientName').textContent = name;
    document.getElementById('currentStock').textContent = currentStock;
    document.getElementById('newStock').value = currentStock;
    document.getElementById('quickUpdateModal').classList.add('show');
}

function closeQuickUpdate() {
    document.getElementById('quickUpdateModal').classList.remove('show');
    currentIngredientId = null;
}

function saveQuickUpdate() {
    const newStock = parseInt(document.getElementById('newStock').value);
    
    if (isNaN(newStock) || newStock < 0) {
        alert('Please enter a valid stock amount');
        return;
    }
    
    // Send update to server
    fetch('update_stock.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            ingredient_id: currentIngredientId,
            new_stock: newStock
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Stock updated successfully!');
            location.reload();
        } else {
            alert('❌ Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('❌ Error updating stock: ' + error.message);
    });
}

// Close modal on outside click
window.onclick = function(event) {
    const modal = document.getElementById('quickUpdateModal');
    if (event.target == modal) {
        closeQuickUpdate();
    }
}
</script>

<?php include 'includes/footer.php'; ?>