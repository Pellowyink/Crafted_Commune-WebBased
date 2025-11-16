<?php
/**
 * Product Inventory Add/Edit Page
 * Manage raw ingredients (cups, eggs, powder, etc.)
 */
require_once '../config.php';
requireAdmin();

$action = $_GET['action'] ?? 'add';
$id = $_GET['id'] ?? null;
$flash = getFlashMessage();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $stock = intval($_POST['stock']);
    $category = trim($_POST['category']);
    $low_threshold = intval($_POST['low_stock_threshold'] ?? 10);
    $unit = trim($_POST['unit'] ?? 'units');
    
    try {
        if ($action === 'edit' && $id) {
            // Update existing ingredient
            $stmt = $pdo->prepare("
                UPDATE product_inventory 
                SET name = ?, stock = ?, category = ?, low_stock_threshold = ?, unit = ?
                WHERE id = ?
            ");
            $stmt->execute([$name, $stock, $category, $low_threshold, $unit, $id]);
            
            logActivity($_SESSION['admin_id'], 'edit_ingredient', "Updated ingredient: $name");
            setFlashMessage('success', "Ingredient '$name' updated successfully!");
        } else {
            // Add new ingredient
            $stmt = $pdo->prepare("
                INSERT INTO product_inventory (name, stock, category, low_stock_threshold, unit) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$name, $stock, $category, $low_threshold, $unit]);
            
            logActivity($_SESSION['admin_id'], 'add_ingredient', "Added ingredient: $name");
            setFlashMessage('success', "Ingredient '$name' added successfully!");
        }
        
        redirect('inventory.php');
    } catch (PDOException $e) {
        setFlashMessage('error', 'Error: ' . $e->getMessage());
    }
}

// Get ingredient data if editing
$ingredient = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM product_inventory WHERE id = ?");
    $stmt->execute([$id]);
    $ingredient = $stmt->fetch();
    
    if (!$ingredient) {
        setFlashMessage('error', 'Ingredient not found');
        redirect('inventory.php');
    }
}

// Get existing categories for dropdown
$categoriesStmt = $pdo->query("SELECT DISTINCT category FROM product_inventory ORDER BY category");
$existingCategories = $categoriesStmt->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = $action === 'edit' ? "Edit Ingredient" : "Add New Ingredient";
include 'includes/header.php';
?>

<div class="page-header">
    <h1><?= $action === 'edit' ? '✏️ Edit Ingredient' : '➕ Add New Ingredient' ?></h1>
    <a href="inventory.php" class="btn btn-secondary">← Back to Inventory</a>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?>">
        <?= e($flash['message']) ?>
    </div>
<?php endif; ?>

<div class="dashboard-card">
    <div class="card-header">
        <h2><?= $action === 'edit' ? 'Edit' : 'Add' ?> Ingredient Details</h2>
    </div>
    <div class="card-body">
        <form method="POST" class="ingredient-form">
            <div class="form-grid">
                <div class="form-group">
                    <label for="name">Ingredient Name *</label>
                    <input type="text" 
                           id="name" 
                           name="name" 
                           class="form-control" 
                           value="<?= $ingredient ? e($ingredient['name']) : '' ?>"
                           placeholder="e.g., 20oz Cup, Espresso Shot, Matcha Powder"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="stock">Current Stock *</label>
                    <input type="number" 
                           id="stock" 
                           name="stock" 
                           class="form-control" 
                           value="<?= $ingredient ? $ingredient['stock'] : '' ?>"
                           min="0"
                           placeholder="e.g., 100"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="category">Category *</label>
                    <input type="text" 
                           id="category" 
                           name="category" 
                           class="form-control" 
                           value="<?= $ingredient ? e($ingredient['category']) : '' ?>"
                           placeholder="e.g., cups, raw foods, powder"
                           list="categoryList"
                           required>
                    <datalist id="categoryList">
                        <?php foreach ($existingCategories as $cat): ?>
                            <option value="<?= e($cat) ?>">
                        <?php endforeach; ?>
                    </datalist>
                    <small class="form-hint">Type to search or create new category</small>
                </div>
                
                <div class="form-group">
                    <label for="unit">Unit of Measurement</label>
                    <select id="unit" name="unit" class="form-control">
                        <option value="pieces" <?= $ingredient && $ingredient['unit'] === 'pieces' ? 'selected' : '' ?>>Pieces</option>
                        <option value="grams" <?= $ingredient && $ingredient['unit'] === 'grams' ? 'selected' : '' ?>>Grams</option>
                        <option value="ml" <?= $ingredient && $ingredient['unit'] === 'ml' ? 'selected' : '' ?>>Milliliters (ml)</option>
                        <option value="liters" <?= $ingredient && $ingredient['unit'] === 'liters' ? 'selected' : '' ?>>Liters</option>
                        <option value="shots" <?= $ingredient && $ingredient['unit'] === 'shots' ? 'selected' : '' ?>>Shots</option>
                        <option value="scoops" <?= $ingredient && $ingredient['unit'] === 'scoops' ? 'selected' : '' ?>>Scoops</option>
                        <option value="servings" <?= $ingredient && $ingredient['unit'] === 'servings' ? 'selected' : '' ?>>Servings</option>
                        <option value="units" <?= $ingredient && $ingredient['unit'] === 'units' ? 'selected' : '' ?>>Units</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="low_stock_threshold">Low Stock Alert Threshold</label>
                    <input type="number" 
                           id="low_stock_threshold" 
                           name="low_stock_threshold" 
                           class="form-control" 
                           value="<?= $ingredient ? $ingredient['low_stock_threshold'] : '10' ?>"
                           min="0"
                           placeholder="10">
                    <small class="form-hint">Alert when stock falls below this number</small>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <?= $action === 'edit' ? '💾 Update Ingredient' : '➕ Add Ingredient' ?>
                </button>
                <a href="inventory.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<style>
.ingredient-form {
    max-width: 800px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #333;
}

.form-control {
    padding: 0.75rem;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: #3d5a3d;
}

.form-hint {
    font-size: 0.85rem;
    color: #666;
    margin-top: 0.25rem;
}

.form-actions {
    display: flex;
    gap: 1rem;
    padding-top: 1rem;
    border-top: 2px solid #f0f0f0;
}

@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include 'includes/footer.php'; ?>