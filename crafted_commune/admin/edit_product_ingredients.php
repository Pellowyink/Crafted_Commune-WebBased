<?php
/**
 * Edit Product Ingredients - Link/Unlink Ingredients to Products
 * User-friendly interface for managing product recipes
 */
require_once '../config.php';
requireAdmin();

$flash = getFlashMessage();

// Get product ID
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

if ($product_id === 0) {
    setFlashMessage('error', 'Invalid product ID');
    redirect('affected_products.php');
}

// Fetch product details
$productStmt = $pdo->prepare("
    SELECT p.*, c.name as category_name 
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.id = ?
");
$productStmt->execute([$product_id]);
$product = $productStmt->fetch();

if (!$product) {
    setFlashMessage('error', 'Product not found');
    redirect('affected_products.php');
}

// Handle form submission - Add ingredient
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'add_ingredient') {
        $ingredient_id = intval($_POST['ingredient_id']);
        $quantity_needed = floatval($_POST['quantity_needed']);
        
        try {
            // Check if already linked
            $checkStmt = $pdo->prepare("
                SELECT id FROM product_ingredients 
                WHERE product_id = ? AND ingredient_id = ?
            ");
            $checkStmt->execute([$product_id, $ingredient_id]);
            
            if ($checkStmt->fetch()) {
                setFlashMessage('error', 'This ingredient is already linked to this product!');
            } else {
                // Add the link
                $insertStmt = $pdo->prepare("
                    INSERT INTO product_ingredients (product_id, ingredient_id, quantity_needed) 
                    VALUES (?, ?, ?)
                ");
                $insertStmt->execute([$product_id, $ingredient_id, $quantity_needed]);
                
                // Update product stock status
                $pdo->query("CALL update_product_stock_status($product_id)");
                
                logActivity($_SESSION['admin_id'], 'link_ingredient', 
                    "Linked ingredient to product: {$product['name']}");
                
                setFlashMessage('success', 'Ingredient added successfully!');
            }
        } catch (PDOException $e) {
            setFlashMessage('error', 'Error adding ingredient: ' . $e->getMessage());
        }
        
        redirect("edit_product_ingredients.php?product_id=$product_id");
    }
    
    if ($_POST['action'] === 'remove_ingredient') {
        $link_id = intval($_POST['link_id']);
        
        try {
            $deleteStmt = $pdo->prepare("DELETE FROM product_ingredients WHERE id = ?");
            $deleteStmt->execute([$link_id]);
            
            // Update product stock status
            $pdo->query("CALL update_product_stock_status($product_id)");
            
            logActivity($_SESSION['admin_id'], 'unlink_ingredient', 
                "Removed ingredient from product: {$product['name']}");
            
            setFlashMessage('success', 'Ingredient removed successfully!');
        } catch (PDOException $e) {
            setFlashMessage('error', 'Error removing ingredient: ' . $e->getMessage());
        }
        
        redirect("edit_product_ingredients.php?product_id=$product_id");
    }
    
    if ($_POST['action'] === 'update_quantity') {
        $link_id = intval($_POST['link_id']);
        $new_quantity = floatval($_POST['new_quantity']);
        
        try {
            $updateStmt = $pdo->prepare("
                UPDATE product_ingredients 
                SET quantity_needed = ? 
                WHERE id = ?
            ");
            $updateStmt->execute([$new_quantity, $link_id]);
            
            // Update product stock status
            $pdo->query("CALL update_product_stock_status($product_id)");
            
            setFlashMessage('success', 'Quantity updated successfully!');
        } catch (PDOException $e) {
            setFlashMessage('error', 'Error updating quantity: ' . $e->getMessage());
        }
        
        redirect("edit_product_ingredients.php?product_id=$product_id");
    }
}

// Fetch current ingredients for this product
$currentIngredientsStmt = $pdo->prepare("
    SELECT 
        pi.id as link_id,
        pi.quantity_needed,
        inv.id as ingredient_id,
        inv.name as ingredient_name,
        inv.stock,
        inv.unit,
        inv.low_stock_threshold,
        CASE 
            WHEN inv.stock = 0 THEN 'out_of_stock'
            WHEN inv.stock <= inv.low_stock_threshold THEN 'low_stock'
            ELSE 'in_stock'
        END as ingredient_status
    FROM product_ingredients pi
    JOIN product_inventory inv ON pi.ingredient_id = inv.id
    WHERE pi.product_id = ?
    ORDER BY inv.name
");
$currentIngredientsStmt->execute([$product_id]);
$currentIngredients = $currentIngredientsStmt->fetchAll();

// Fetch available ingredients (not yet linked)
$availableIngredientsStmt = $pdo->prepare("
    SELECT 
        id, name, stock, unit, category
    FROM product_inventory
    WHERE id NOT IN (
        SELECT ingredient_id 
        FROM product_ingredients 
        WHERE product_id = ?
    )
    ORDER BY category, name
");
$availableIngredientsStmt->execute([$product_id]);
$availableIngredients = $availableIngredientsStmt->fetchAll();

$pageTitle = "Edit Product Ingredients";
include 'includes/header.php';
?>

<div class="page-header">
    <h1>🔗 Manage Product Ingredients</h1>
    <div class="header-actions">
        <a href="affected_products.php" class="btn btn-secondary">← Back to Products</a>
        <a href="products.php?action=edit&id=<?= $product_id ?>" class="btn btn-primary">✏️ Edit Product Details</a>
    </div>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?>">
        <?= e($flash['message']) ?>
    </div>
<?php endif; ?>

<!-- Product Info Card -->
<div class="product-info-card">
    
    <div class="product-details">
        <h2><?= e($product['name']) ?></h2>
        <div class="detail-row">
            <span class="label">Category:</span>
            <span class="value"><?= e($product['category_name']) ?></span>
        </div>
        <div class="detail-row">
            <span class="label">Price:</span>
            <span class="value">₱<?= number_format($product['price'], 2) ?></span>
        </div>
        <div class="detail-row">
            <span class="label">Points:</span>
            <span class="value"><?= $product['points'] ?> pts</span>
        </div>
        <div class="detail-row">
            <span class="label">Current Status:</span>
            <span class="value">
                <?php if ($product['stock_status'] === 'out_of_stock'): ?>
                    <span class="status-badge badge-red">🚫 Out of Stock</span>
                <?php elseif ($product['stock_status'] === 'low_stock'): ?>
                    <span class="status-badge badge-orange">⚠️ Low Stock</span>
                <?php else: ?>
                    <span class="status-badge badge-green">✅ In Stock</span>
                <?php endif; ?>
            </span>
        </div>
    </div>
</div>

<div class="ingredients-grid">
    <!-- LEFT: Current Ingredients -->
    <div class="dashboard-card">
        <div class="card-header">
            <h2>📦 Current Ingredients (<?= count($currentIngredients) ?>)</h2>
        </div>
        <div class="card-body">
            <?php if (empty($currentIngredients)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📭</div>
                    <p>No ingredients linked yet</p>
                    <small>Add ingredients from the right panel</small>
                </div>
            <?php else: ?>
                <div class="ingredients-list">
                    <?php foreach ($currentIngredients as $ing): ?>
                        <?php
                        $rowClass = '';
                        if ($ing['ingredient_status'] === 'out_of_stock') {
                            $rowClass = 'ingredient-out-of-stock';
                        } elseif ($ing['ingredient_status'] === 'low_stock') {
                            $rowClass = 'ingredient-low-stock';
                        }
                        ?>
                        <div class="ingredient-item <?= $rowClass ?>">
                            <div class="ingredient-info">
                                <div class="ingredient-name">
                                    <strong><?= e($ing['ingredient_name']) ?></strong>
                                    <?php if ($ing['ingredient_status'] === 'out_of_stock'): ?>
                                        <span class="mini-badge badge-red">OUT</span>
                                    <?php elseif ($ing['ingredient_status'] === 'low_stock'): ?>
                                        <span class="mini-badge badge-orange">LOW</span>
                                    <?php endif; ?>
                                </div>
                                <div class="ingredient-stats">
                                    <span class="stat">
                                        📊 Stock: <strong><?= $ing['stock'] ?> <?= e($ing['unit']) ?></strong>
                                    </span>
                                    <span class="stat">
                                        📏 Needs: <strong><?= $ing['quantity_needed'] ?> <?= e($ing['unit']) ?></strong>
                                    </span>
                                </div>
                            </div>
                            <div class="ingredient-actions">
                                <button onclick="editQuantity(<?= $ing['link_id'] ?>, '<?= e($ing['ingredient_name']) ?>', <?= $ing['quantity_needed'] ?>, '<?= e($ing['unit']) ?>')" 
                                        class="btn-icon btn-icon-blue" 
                                        title="Edit Quantity">
                                    ✏️
                                </button>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Remove this ingredient?')">
                                    <input type="hidden" name="action" value="remove_ingredient">
                                    <input type="hidden" name="link_id" value="<?= $ing['link_id'] ?>">
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Remove">
                                        🗑️
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- RIGHT: Add New Ingredients -->
    <div class="dashboard-card">
        <div class="card-header">
            <h2>➕ Add Ingredient</h2>
        </div>
        <div class="card-body">
            <?php if (empty($availableIngredients)): ?>
                <div class="empty-state">
                    <div class="empty-icon">✅</div>
                    <p>All ingredients are already linked!</p>
                    <a href="productadd_edit.php" class="btn btn-primary" style="margin-top: 15px;">
                        Create New Ingredient
                    </a>
                </div>
            <?php else: ?>
                <form method="POST" class="add-ingredient-form">
                    <input type="hidden" name="action" value="add_ingredient">
                    
                    <div class="form-group">
                        <label for="ingredient_id">Select Ingredient: *</label>
                        <select name="ingredient_id" id="ingredient_id" class="form-control" required onchange="updateIngredientPreview()">
                            <option value="">-- Choose an ingredient --</option>
                            <?php 
                            $currentCategory = '';
                            foreach ($availableIngredients as $ing): 
                                if ($currentCategory !== $ing['category']) {
                                    if ($currentCategory !== '') echo '</optgroup>';
                                    echo '<optgroup label="' . e($ing['category']) . '">';
                                    $currentCategory = $ing['category'];
                                }
                            ?>
                                <option value="<?= $ing['id'] ?>" 
                                        data-stock="<?= $ing['stock'] ?>" 
                                        data-unit="<?= e($ing['unit']) ?>">
                                    <?= e($ing['name']) ?> (Stock: <?= $ing['stock'] ?> <?= e($ing['unit']) ?>)
                                </option>
                            <?php 
                            endforeach; 
                            if ($currentCategory !== '') echo '</optgroup>';
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="quantity_needed">Quantity Needed: *</label>
                        <div class="quantity-input-group">
                            <input type="number" 
                                   name="quantity_needed" 
                                   id="quantity_needed" 
                                   class="form-control" 
                                   step="0.01" 
                                   min="0.01" 
                                   placeholder="0.00"
                                   required>
                            <span class="unit-display" id="unit_display">units</span>
                        </div>
                        <small class="form-hint">How much of this ingredient is needed per serving</small>
                    </div>
                    
                    <div id="ingredient_preview" class="ingredient-preview" style="display:none;">
                        <div class="preview-header">📊 Ingredient Preview</div>
                        <div class="preview-content">
                            <div class="preview-row">
                                <span>Current Stock:</span>
                                <span id="preview_stock">0</span>
                            </div>
                            <div class="preview-row">
                                <span>Can Make:</span>
                                <span id="preview_servings" class="highlight">0 servings</span>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">
                        ➕ Add Ingredient to Recipe
                    </button>
                </form>
                
                <div class="help-section">
                    <h3>💡 Quick Tips</h3>
                    <ul>
                        <li><strong>Cup/Container:</strong> Usually 1 per drink</li>
                        <li><strong>Espresso:</strong> 1-2 shots per coffee drink</li>
                        <li><strong>Milk:</strong> 200-300ml for lattes</li>
                        <li><strong>Syrups:</strong> 20-30ml for flavored drinks</li>
                        <li><strong>Powder (Matcha):</strong> 10-20g per drink</li>
                        <li><strong>Ice:</strong> 1 scoop per cold drink</li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Edit Quantity Modal -->
<div id="editQuantityModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeEditModal()">&times;</span>
        <h2>✏️ Edit Ingredient Quantity</h2>
        <form method="POST" id="editQuantityForm">
            <input type="hidden" name="action" value="update_quantity">
            <input type="hidden" name="link_id" id="edit_link_id">
            
            <p><strong>Ingredient:</strong> <span id="edit_ingredient_name"></span></p>
            
            <div class="form-group">
                <label for="new_quantity">New Quantity Needed:</label>
                <div class="quantity-input-group">
                    <input type="number" 
                           name="new_quantity" 
                           id="new_quantity" 
                           class="form-control" 
                           step="0.01" 
                           min="0.01" 
                           required>
                    <span class="unit-display" id="edit_unit"></span>
                </div>
            </div>
            
            <div class="button-group">
                <button type="submit" class="btn btn-primary">💾 Save</button>
                <button type="button" onclick="closeEditModal()" class="btn btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>

<style>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.header-actions {
    display: flex;
    gap: 10px;
}

.product-info-card {
    background: linear-gradient(135deg, #273B08 0%, #405c17ff 100%);
    color: white;
    border-radius: 16px;
    padding: 30px;
    display: flex;
    gap: 30px;
    margin-bottom: 30px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.product-image {
    flex-shrink: 0;
}

.product-image img {
    width: 150px;
    height: 150px;
    object-fit: cover;
    border-radius: 12px;
    border: 4px solid rgba(255,255,255,0.2);
}

.product-details {
    flex-grow: 1;
}

.product-details h2 {
    margin: 0 0 20px 0;
    font-size: 2rem;
}

.detail-row {
    display: flex;
    margin-bottom: 10px;
}

.detail-row .label {
    font-weight: 600;
    width: 120px;
    opacity: 0.9;
}

.detail-row .value {
    font-weight: 500;
}

.ingredients-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
}

@media (max-width: 1200px) {
    .ingredients-grid {
        grid-template-columns: 1fr;
    }
}

.ingredients-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.ingredient-item {
    background: #f8f9fa;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.3s;
}

.ingredient-item:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.ingredient-out-of-stock {
    background: #ffe6e6;
    border-color: #ff4444;
}

.ingredient-low-stock {
    background: #fff9e6;
    border-color: #ffa500;
}

.ingredient-info {
    flex-grow: 1;
}

.ingredient-name {
    font-size: 1.1rem;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.mini-badge {
    font-size: 0.65rem;
    padding: 2px 8px;
    border-radius: 8px;
    font-weight: 700;
}

.ingredient-stats {
    display: flex;
    gap: 20px;
    font-size: 0.9rem;
    color: #666;
}

.ingredient-actions {
    display: flex;
    gap: 8px;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 15px;
}

.add-ingredient-form {
    padding: 20px;
    background: #f8f9fa;
    border-radius: 12px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    color: #2c3e50;
}

.form-control {
    width: 100%;
    padding: 12px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.3s;
}

.form-control:focus {
    outline: none;
    border-color: #273B08;
}

.quantity-input-group {
    display: flex;
    gap: 10px;
    align-items: center;
}

.quantity-input-group input {
    flex: 1;
}

.unit-display {
    background: #273B08;
    color: white;
    padding: 12px 20px;
    border-radius: 8px;
    font-weight: 600;
    min-width: 80px;
    text-align: center;
}

.form-hint {
    display: block;
    margin-top: 5px;
    color: #666;
    font-size: 0.85rem;
}

.ingredient-preview {
    background: white;
    border: 2px solid #273B08;
    border-radius: 12px;
    padding: 15px;
    margin: 20px 0;
}

.preview-header {
    font-weight: 700;
    color: #273B08;
    margin-bottom: 10px;
}

.preview-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
}

.preview-row:last-child {
    border-bottom: none;
}

.preview-row .highlight {
    color: #273B08;
    font-weight: 700;
    font-size: 1.1rem;
}

.help-section {
    margin-top: 30px;
    padding: 20px;
    background: #e8f4f8;
    border-radius: 12px;
}

.help-section h3 {
    color: #2c3e50;
    margin-bottom: 15px;
}

.help-section ul {
    list-style: none;
    padding: 0;
}

.help-section li {
    padding: 8px 0;
    color: #555;
}

.btn-block {
    width: 100%;
    padding: 15px;
    font-size: 1.1rem;
    font-weight: 600;
}

/* Modal styles */
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
    border-radius: 16px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
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

.button-group {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.status-badge {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
}

.badge-green { background-color: #d4edda; color: #155724; }
.badge-orange { background-color: #fff3cd; color: #856404; }
.badge-red { background-color: #f8d7da; color: #721c24; }
</style>

<script>
function updateIngredientPreview() {
    const select = document.getElementById('ingredient_id');
    const option = select.options[select.selectedIndex];
    const quantityInput = document.getElementById('quantity_needed');
    const preview = document.getElementById('ingredient_preview');
    const unitDisplay = document.getElementById('unit_display');
    
    if (option.value) {
        const stock = parseFloat(option.getAttribute('data-stock'));
        const unit = option.getAttribute('data-unit');
        
        unitDisplay.textContent = unit;
        
        quantityInput.addEventListener('input', function() {
            const quantity = parseFloat(this.value) || 0;
            const servings = quantity > 0 ? Math.floor(stock / quantity) : 0;
            
            document.getElementById('preview_stock').textContent = stock + ' ' + unit;
            document.getElementById('preview_servings').textContent = servings + ' servings';
            
            if (quantity > 0) {
                preview.style.display = 'block';
            }
        });
    } else {
        preview.style.display = 'none';
        unitDisplay.textContent = 'units';
    }
}

function editQuantity(linkId, name, currentQty, unit) {
    document.getElementById('edit_link_id').value = linkId;
    document.getElementById('edit_ingredient_name').textContent = name;
    document.getElementById('new_quantity').value = currentQty;
    document.getElementById('edit_unit').textContent = unit;
    document.getElementById('editQuantityModal').classList.add('show');
}

function closeEditModal() {
    document.getElementById('editQuantityModal').classList.remove('show');
}

// Close modal on outside click
window.onclick = function(event) {
    const modal = document.getElementById('editQuantityModal');
    if (event.target == modal) {
        closeEditModal();
    }
}
</script>

<?php include 'includes/footer.php'; ?>