<?php
/**
 * Products Management Page
 * Add, Edit, Delete, View Products
 */
require_once '../config.php';
requireAdmin();

// Handle actions
$action = $_GET['action'] ?? 'list';
$flash = getFlashMessage();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_product'])) {
        // Add new product
        $name = trim($_POST['name']);
        $category_id = $_POST['category_id'];
        $price = floatval($_POST['price']);
        $points = intval($_POST['points']);
        $is_recommended = isset($_POST['is_recommended']) ? 1 : 0;
        $image = $_POST['image'] ?? 'images/placeholder.jpg';
        
        $stmt = $pdo->prepare("
            INSERT INTO products (category_id, name, price, points, image, is_recommended, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, 1)
        ");
        
        if ($stmt->execute([$category_id, $name, $price, $points, $image, $is_recommended])) {
            logActivity($_SESSION['admin_id'], 'add_product', "Added product: $name");
            setFlashMessage('success', "Product '$name' added successfully!");
        } else {
            setFlashMessage('error', 'Failed to add product.');
        }
        redirect('products.php');
    }
    
    if (isset($_POST['edit_product'])) {
        // Edit product
        $id = intval($_POST['id']);
        $name = trim($_POST['name']);
        $category_id = $_POST['category_id'];
        $price = floatval($_POST['price']);
        $points = intval($_POST['points']);
        $is_recommended = isset($_POST['is_recommended']) ? 1 : 0;
        $image = $_POST['image'];
        
        $stmt = $pdo->prepare("
            UPDATE products 
            SET category_id = ?, name = ?, price = ?, points = ?, image = ?, is_recommended = ? 
            WHERE id = ?
        ");
        
        if ($stmt->execute([$category_id, $name, $price, $points, $image, $is_recommended, $id])) {
            logActivity($_SESSION['admin_id'], 'edit_product', "Edited product: $name");
            setFlashMessage('success', "Product '$name' updated successfully!");
        } else {
            setFlashMessage('error', 'Failed to update product.');
        }
        redirect('products.php');
    }
}

// Handle delete
if ($action === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT name FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    
    if ($product) {
        $deleteStmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        if ($deleteStmt->execute([$id])) {
            logActivity($_SESSION['admin_id'], 'delete_product', "Deleted product: " . $product['name']);
            setFlashMessage('success', "Product '{$product['name']}' deleted successfully!");
        } else {
            setFlashMessage('error', 'Failed to delete product.');
        }
    }
    redirect('products.php');
}

// Handle toggle active
if ($action === 'toggle' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("UPDATE products SET is_active = NOT is_active WHERE id = ?");
    if ($stmt->execute([$id])) {
        setFlashMessage('success', 'Product status updated!');
    }
    redirect('products.php');
}

// Get all categories for dropdown
$categories = $pdo->query("SELECT * FROM categories ORDER BY display_order")->fetchAll();

// Get product for editing
$editProduct = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $editProduct = $stmt->fetch();
}

// Get all products with category names
$products = $pdo->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.id ASC
")->fetchAll();

include 'includes/header.php';
?>

<div class="page-header">
    <h1>📦 Product Management</h1>
    <button class="btn btn-primary" onclick="toggleForm()">
        ➕ Add New Product
    </button>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?>">
        <?= e($flash['message']) ?>
    </div>
<?php endif; ?>

<!-- Add/Edit Product Form -->
<div class="form-card" id="productForm" style="display: <?= $editProduct ? 'block' : 'none' ?>">
    <div class="card-header">
        <h2><?= $editProduct ? '✏️ Edit Product' : '➕ Add New Product' ?></h2>
        <button class="btn btn-secondary btn-sm" onclick="toggleForm()">Cancel</button>
    </div>
    <div class="card-body">
        <form method="POST" action="" id="productFormElement">
            <?php if ($editProduct): ?>
                <input type="hidden" name="id" value="<?= $editProduct['id'] ?>">
            <?php endif; ?>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="name" class="form-label">Product Name *</label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        class="form-control" 
                        value="<?= $editProduct ? e($editProduct['name']) : '' ?>"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="category_id" class="form-label">Category *</label>
                    <select id="category_id" name="category_id" class="form-control" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $editProduct && $editProduct['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                <?= e($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="price" class="form-label">Price (₱) *</label>
                    <input 
                        type="number" 
                        id="price" 
                        name="price" 
                        class="form-control" 
                        step="0.01" 
                        min="0"
                        value="<?= $editProduct ? $editProduct['price'] : '' ?>"
                        oninput="autoCalculatePoints()"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="points" class="form-label">Points *</label>
                    <input 
                        type="number" 
                        id="points" 
                        name="points" 
                        class="form-control" 
                        min="0"
                        value="<?= $editProduct ? $editProduct['points'] : '' ?>"
                        required
                    >
                    <small class="form-hint">Auto-calculated: ₱10 = 1 point</small>
                </div>
                
                <div class="form-group full-width">
                    <label for="image" class="form-label">Image Path</label>
                    <input 
                        type="text" 
                        id="image" 
                        name="image" 
                        class="form-control" 
                        value="<?= $editProduct ? e($editProduct['image']) : 'images/' ?>"
                        placeholder="images/product-name.jpg"
                    >
                    <small class="form-hint">Example: images/americano.jpg</small>
                </div>
                
                <div class="form-group full-width">
                    <label class="checkbox-label">
                        <input 
                            type="checkbox" 
                            name="is_recommended" 
                            <?= $editProduct && $editProduct['is_recommended'] ? 'checked' : '' ?>
                        >
                        <span>⭐ Mark as Recommended</span>
                    </label>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="<?= $editProduct ? 'edit_product' : 'add_product' ?>" class="btn btn-primary">
                    <?= $editProduct ? '💾 Update Product' : '➕ Add Product' ?>
                </button>
                <button type="button" class="btn btn-secondary" onclick="toggleForm()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Products List -->
<div class="dashboard-card">
    <div class="card-header">
        <h2>📋 All Products (<?= count($products) ?>)</h2>
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
            <p class="no-data">No products found. Add your first product!</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table" id="productsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Points</th>
                            <th>Status</th>
                            <th>Recommended</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?= $product['id'] ?></td>
                                <td>
                                    <img src="../<?= e($product['image']) ?>" 
                                         alt="<?= e($product['name']) ?>" 
                                         class="product-thumb"
                                         onerror="this.src='../images/placeholder.jpg'">
                                </td>
                                <td><strong><?= e($product['name']) ?></strong></td>
                                <td><?= e($product['category_name']) ?></td>
                                <td><?= formatCurrency($product['price']) ?></td>
                                <td><span class="badge badge-gold"><?= $product['points'] ?> pts</span></td>
                                <td>
                                    <?php if ($product['is_active']): ?>
                                        <span class="badge badge-green">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($product['is_recommended']): ?>
                                        <span class="badge badge-red">⭐ Yes</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">No</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="products.php?action=edit&id=<?= $product['id'] ?>" 
                                           class="btn-icon btn-icon-blue" 
                                           title="Edit">
                                            ✏️
                                        </a>
                                        <a href="products.php?action=toggle&id=<?= $product['id'] ?>" 
                                           class="btn-icon btn-icon-warning" 
                                           title="Toggle Active/Inactive">
                                            🔄
                                        </a>
                                        <a href="products.php?action=delete&id=<?= $product['id'] ?>" 
                                           class="btn-icon btn-icon-danger" 
                                           onclick="return confirm('Delete this product?')"
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

<script>
// Toggle form visibility
function toggleForm() {
    const form = document.getElementById('productForm');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
    
    // If hiding, reset form
    if (form.style.display === 'none') {
        window.location.href = 'products.php';
    }
}

// Auto-calculate points from price
function autoCalculatePoints() {
    const priceInput = document.getElementById('price');
    const pointsInput = document.getElementById('points');
    const price = parseFloat(priceInput.value) || 0;
    const points = Math.floor(price / 10);
    pointsInput.value = points;
}

// Search table
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