<?php
/**
 * Categories Management Page
 * Add, Edit, Delete, View Categories
 */
require_once '../config.php';
requireAdmin();

$action = $_GET['action'] ?? 'list';
$flash = getFlashMessage();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        $name = trim($_POST['name']);
        $slug = strtolower(str_replace(' ', '-', $name));
        $icon = $_POST['icon'] ?? 'images/icons/default-icon.png';
        $display_order = intval($_POST['display_order']);
        
        $stmt = $pdo->prepare("
            INSERT INTO categories (name, slug, icon, display_order, is_active) 
            VALUES (?, ?, ?, ?, 1)
        ");
        
        if ($stmt->execute([$name, $slug, $icon, $display_order])) {
            logActivity($_SESSION['admin_id'], 'add_category', "Added category: $name");
            setFlashMessage('success', "Category '$name' added successfully!");
        } else {
            setFlashMessage('error', 'Failed to add category.');
        }
        redirect('categories.php');
    }
    
    if (isset($_POST['edit_category'])) {
        $id = intval($_POST['id']);
        $name = trim($_POST['name']);
        $slug = strtolower(str_replace(' ', '-', $name));
        $icon = $_POST['icon'];
        $display_order = intval($_POST['display_order']);
        
        $stmt = $pdo->prepare("
            UPDATE categories 
            SET name = ?, slug = ?, icon = ?, display_order = ? 
            WHERE id = ?
        ");
        
        if ($stmt->execute([$name, $slug, $icon, $display_order, $id])) {
            logActivity($_SESSION['admin_id'], 'edit_category', "Edited category: $name");
            setFlashMessage('success', "Category '$name' updated successfully!");
        } else {
            setFlashMessage('error', 'Failed to update category.');
        }
        redirect('categories.php');
    }
}

// Handle delete
if ($action === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $category = $stmt->fetch();
    
    if ($category) {
        // Check if category has products
        $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
        $checkStmt->execute([$id]);
        $count = $checkStmt->fetch()['count'];
        
        if ($count > 0) {
            setFlashMessage('error', "Cannot delete category '{$category['name']}' because it has {$count} products!");
        } else {
            $deleteStmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            if ($deleteStmt->execute([$id])) {
                logActivity($_SESSION['admin_id'], 'delete_category', "Deleted category: " . $category['name']);
                setFlashMessage('success', "Category '{$category['name']}' deleted successfully!");
            }
        }
    }
    redirect('categories.php');
}

// Handle toggle active
if ($action === 'toggle' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("UPDATE categories SET is_active = NOT is_active WHERE id = ?");
    if ($stmt->execute([$id])) {
        setFlashMessage('success', 'Category status updated!');
    }
    redirect('categories.php');
}

// Get category for editing
$editCategory = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $editCategory = $stmt->fetch();
}

// Get all categories with product count
$categories = $pdo->query("
    SELECT c.*, COUNT(p.id) as product_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id 
    GROUP BY c.id 
    ORDER BY c.display_order, c.name
")->fetchAll();

include 'includes/header.php';
?>

<div class="page-header">
    <h1>🗂️ Category Management</h1>
    <button class="btn btn-primary" onclick="toggleForm()">
        ➕ Add New Category
    </button>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?>">
        <?= e($flash['message']) ?>
    </div>
<?php endif; ?>

<!-- Add/Edit Category Form -->
<div class="form-card" id="categoryForm" style="display: <?= $editCategory ? 'block' : 'none' ?>">
    <div class="card-header">
        <h2><?= $editCategory ? '✏️ Edit Category' : '➕ Add New Category' ?></h2>
        <button class="btn btn-secondary btn-sm" onclick="toggleForm()">Cancel</button>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <?php if ($editCategory): ?>
                <input type="hidden" name="id" value="<?= $editCategory['id'] ?>">
            <?php endif; ?>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="name" class="form-label">Category Name *</label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        class="form-control" 
                        value="<?= $editCategory ? e($editCategory['name']) : '' ?>"
                        required
                    >
                    <small class="form-hint">Example: Coffee, Latte, Snacks</small>
                </div>
                
                <div class="form-group">
                    <label for="display_order" class="form-label">Display Order</label>
                    <input 
                        type="number" 
                        id="display_order" 
                        name="display_order" 
                        class="form-control" 
                        min="0"
                        value="<?= $editCategory ? $editCategory['display_order'] : count($categories) + 1 ?>"
                    >
                    <small class="form-hint">Lower numbers appear first</small>
                </div>
                
                <div class="form-group full-width">
                    <label for="icon" class="form-label">Icon Path</label>
                    <input 
                        type="text" 
                        id="icon" 
                        name="icon" 
                        class="form-control" 
                        value="<?= $editCategory ? e($editCategory['icon']) : 'images/icons/' ?>"
                        placeholder="images/icons/category-icon.png"
                    >
                    <small class="form-hint">Upload icon image to /images/icons/ folder</small>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="<?= $editCategory ? 'edit_category' : 'add_category' ?>" class="btn btn-primary">
                    <?= $editCategory ? '💾 Update Category' : '➕ Add Category' ?>
                </button>
                <button type="button" class="btn btn-secondary" onclick="toggleForm()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Categories Grid -->
<div class="categories-grid">
    <?php foreach ($categories as $category): ?>
        <div class="category-card">
            <div class="category-icon-large">
                <img src="../<?= e($category['icon']) ?>" 
                     alt="<?= e($category['name']) ?>"
                     onerror="this.innerHTML='📁'">
            </div>
            <h3><?= e($category['name']) ?></h3>
            <p class="category-stats">
                <?= $category['product_count'] ?> Products<br>
                Order: <?= $category['display_order'] ?>
            </p>
            <div class="category-status">
                <?php if ($category['is_active']): ?>
                    <span class="badge badge-green">Active</span>
                <?php else: ?>
                    <span class="badge badge-secondary">Inactive</span>
                <?php endif; ?>
            </div>
            <div class="category-actions">
                <a href="categories.php?action=edit&id=<?= $category['id'] ?>" 
                   class="btn btn-sm btn-primary">
                    ✏️ Edit
                </a>
                <a href="categories.php?action=toggle&id=<?= $category['id'] ?>" 
                   class="btn btn-sm btn-secondary">
                    🔄 Toggle
                </a>
                <a href="categories.php?action=delete&id=<?= $category['id'] ?>" 
                   class="btn btn-sm btn-danger"
                   onclick="return confirm('Delete this category? (Only if it has no products)')">
                    🗑️ Delete
                </a>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
function toggleForm() {
    const form = document.getElementById('categoryForm');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
    
    if (form.style.display === 'none') {
        window.location.href = 'categories.php';
    }
}
</script>

<style>
.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}

.category-card {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.category-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
}

.category-icon-large {
    width: 80px;
    height: 80px;
    margin: 0 auto 1rem;
    background: #f0f0f0;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
}

.category-icon-large img {
    width: 50px;
    height: 50px;
    object-fit: contain;
}

.category-card h3 {
    font-size: 1.5rem;
    color: var(--primary-dark);
    margin-bottom: 1rem;
}

.category-stats {
    color: #666;
    margin-bottom: 1rem;
    font-size: 0.9rem;
}

.category-status {
    margin-bottom: 1rem;
}

.category-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
}
</style>

<?php include 'includes/footer.php'; ?>