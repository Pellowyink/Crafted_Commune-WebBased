<?php
/**
 * Settings Page
 * Manage system configuration and admin account
 */
require_once '../config.php';
requireAdmin();

$flash = getFlashMessage();

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_settings'])) {
        $settings = [
            'site_name' => trim($_POST['site_name']),
            'points_ratio' => intval($_POST['points_ratio']),
            'tax_rate' => floatval($_POST['tax_rate']),
            'currency_symbol' => trim($_POST['currency_symbol']),
            'carousel_autoplay' => isset($_POST['carousel_autoplay']) ? '1' : '0',
            'carousel_interval' => intval($_POST['carousel_interval'])
        ];
        
        foreach ($settings as $key => $value) {
            updateSetting($key, $value);
        }
        
        logActivity($_SESSION['admin_id'], 'update_settings', 'Updated system settings');
        setFlashMessage('success', 'Settings updated successfully!');
        redirect('settings.php');
    }
    
    if (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        // Get current admin
        $stmt = $pdo->prepare("SELECT password FROM admin_users WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch();
        
        if (!password_verify($currentPassword, $admin['password'])) {
            setFlashMessage('error', 'Current password is incorrect!');
        } elseif ($newPassword !== $confirmPassword) {
            setFlashMessage('error', 'New passwords do not match!');
        } elseif (strlen($newPassword) < 6) {
            setFlashMessage('error', 'New password must be at least 6 characters!');
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt = $pdo->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
            
            if ($updateStmt->execute([$hashedPassword, $_SESSION['admin_id']])) {
                logActivity($_SESSION['admin_id'], 'change_password', 'Changed admin password');
                setFlashMessage('success', 'Password changed successfully!');
            } else {
                setFlashMessage('error', 'Failed to change password!');
            }
        }
        redirect('settings.php');
    }
}

// Get current settings
$settingsData = [
    'site_name' => getSetting('site_name', 'Crafted Commune'),
    'points_ratio' => getSetting('points_ratio', '10'),
    'tax_rate' => getSetting('tax_rate', '0'),
    'currency_symbol' => getSetting('currency_symbol', '₱'),
    'carousel_autoplay' => getSetting('carousel_autoplay', '1'),
    'carousel_interval' => getSetting('carousel_interval', '5000')
];

// Get admin info
$adminStmt = $pdo->prepare("SELECT * FROM admin_users WHERE id = ?");
$adminStmt->execute([$_SESSION['admin_id']]);
$adminInfo = $adminStmt->fetch();

// Get system stats
$totalProducts = $pdo->query("SELECT COUNT(*) as count FROM products")->fetch()['count'];
$totalOrders = $pdo->query("SELECT COUNT(*) as count FROM orders")->fetch()['count'];
$totalRevenue = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE order_status = 'completed'")->fetch()['total'];

include 'includes/header.php';
?>

<div class="page-header">
    <h1>⚙️ System Settings</h1>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?>">
        <?= e($flash['message']) ?>
    </div>
<?php endif; ?>

<div class="dashboard-grid">
    <!-- General Settings -->
    <div class="dashboard-card">
        <div class="card-header">
            <h2>🔧 General Settings</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="site_name" class="form-label">Site Name</label>
                    <input 
                        type="text" 
                        id="site_name" 
                        name="site_name" 
                        class="form-control" 
                        value="<?= e($settingsData['site_name']) ?>"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="points_ratio" class="form-label">Points Ratio</label>
                    <input 
                        type="number" 
                        id="points_ratio" 
                        name="points_ratio" 
                        class="form-control" 
                        value="<?= e($settingsData['points_ratio']) ?>"
                        min="1"
                        required
                    >
                    <small class="form-hint">How many pesos equal 1 point (e.g., 10 = ₱10 = 1 point)</small>
                </div>
                
                <div class="form-group">
                    <label for="tax_rate" class="form-label">Tax Rate (%)</label>
                    <input 
                        type="number" 
                        id="tax_rate" 
                        name="tax_rate" 
                        class="form-control" 
                        value="<?= e($settingsData['tax_rate']) ?>"
                        min="0"
                        step="0.01"
                    >
                    <small class="form-hint">Enter 0 for no tax</small>
                </div>
                
                <div class="form-group">
                    <label for="currency_symbol" class="form-label">Currency Symbol</label>
                    <input 
                        type="text" 
                        id="currency_symbol" 
                        name="currency_symbol" 
                        class="form-control" 
                        value="<?= e($settingsData['currency_symbol']) ?>"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input 
                            type="checkbox" 
                            name="carousel_autoplay" 
                            <?= $settingsData['carousel_autoplay'] == '1' ? 'checked' : '' ?>
                        >
                        <span>Enable Carousel Autoplay</span>
                    </label>
                </div>
                
                <div class="form-group">
                    <label for="carousel_interval" class="form-label">Carousel Interval (ms)</label>
                    <input 
                        type="number" 
                        id="carousel_interval" 
                        name="carousel_interval" 
                        class="form-control" 
                        value="<?= e($settingsData['carousel_interval']) ?>"
                        min="1000"
                        step="1000"
                    >
                    <small class="form-hint">Time between slides in milliseconds (5000 = 5 seconds)</small>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="update_settings" class="btn btn-primary">
                        💾 Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Change Password -->
    <div class="dashboard-card">
        <div class="card-header">
            <h2>🔐 Change Password</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="current_password" class="form-label">Current Password</label>
                    <input 
                        type="password" 
                        id="current_password" 
                        name="current_password" 
                        class="form-control" 
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="new_password" class="form-label">New Password</label>
                    <input 
                        type="password" 
                        id="new_password" 
                        name="new_password" 
                        class="form-control" 
                        minlength="6"
                        required
                    >
                    <small class="form-hint">Minimum 6 characters</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        class="form-control" 
                        minlength="6"
                        required
                    >
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="change_password" class="btn btn-primary">
                        🔑 Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Admin Info & System Stats -->
<div class="dashboard-grid">
    <!-- Admin Info -->
    <div class="dashboard-card">
        <div class="card-header">
            <h2>👤 Admin Account Info</h2>
        </div>
        <div class="card-body">
            <div class="info-grid">
                <div class="info-item">
                    <label>Username:</label>
                    <strong><?= e($adminInfo['username']) ?></strong>
                </div>
                <div class="info-item">
                    <label>Full Name:</label>
                    <strong><?= e($adminInfo['full_name']) ?></strong>
                </div>
                <div class="info-item">
                    <label>Email:</label>
                    <strong><?= e($adminInfo['email']) ?></strong>
                </div>
                <div class="info-item">
                    <label>Last Login:</label>
                    <strong><?= $adminInfo['last_login'] ? formatDateTime($adminInfo['last_login']) : 'Never' ?></strong>
                </div>
                <div class="info-item">
                    <label>Account Created:</label>
                    <strong><?= formatDateTime($adminInfo['created_at']) ?></strong>
                </div>
                <div class="info-item">
                    <label>Status:</label>
                    <span class="badge badge-green">Active</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- System Stats -->
    <div class="dashboard-card">
        <div class="card-header">
            <h2>📊 System Statistics</h2>
        </div>
        <div class="card-body">
            <div class="info-grid">
                <div class="info-item">
                    <label>Total Products:</label>
                    <strong><?= $totalProducts ?></strong>
                </div>
                <div class="info-item">
                    <label>Total Orders:</label>
                    <strong><?= $totalOrders ?></strong>
                </div>
                <div class="info-item">
                    <label>Total Revenue:</label>
                    <strong><?= formatCurrency($totalRevenue) ?></strong>
                </div>
                <div class="info-item">
                    <label>Database:</label>
                    <strong><?= DB_NAME ?></strong>
                </div>
                <div class="info-item">
                    <label>PHP Version:</label>
                    <strong><?= PHP_VERSION ?></strong>
                </div>
                <div class="info-item">
                    <label>Server:</label>
                    <strong><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="dashboard-card">
    <div class="card-header">
        <h2>⚡ Quick Actions</h2>
    </div>
    <div class="card-body">
        <div class="quick-actions">
            <a href="products.php" class="action-btn">
                <span class="action-icon">📦</span>
                <span class="action-label">Manage Products</span>
            </a>
            <a href="categories.php" class="action-btn">
                <span class="action-icon">🗂️</span>
                <span class="action-label">Manage Categories</span>
            </a>
            <a href="orders.php" class="action-btn">
                <span class="action-icon">🛒</span>
                <span class="action-label">View Orders</span>
            </a>
            <a href="analytics.php" class="action-btn">
                <span class="action-icon">📈</span>
                <span class="action-label">View Analytics</span>
            </a>
            <a href="../index.php" class="action-btn" target="_blank">
                <span class="action-icon">🌐</span>
                <span class="action-label">View Website</span>
            </a>
            <a href="logout.php" class="action-btn action-btn-danger">
                <span class="action-icon">🚪</span>
                <span class="action-label">Logout</span>
            </a>
        </div>
    </div>
</div>

<style>
.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.info-item {
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 10px;
}

.info-item label {
    display: block;
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1.5rem;
}

.action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
    padding: 2rem 1rem;
    background: #f8f9fa;
    border-radius: 15px;
    text-decoration: none;
    color: var(--text-dark);
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.action-btn:hover {
    background: var(--primary);
    color: white;
    transform: translateY(-5px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
}

.action-btn-danger:hover {
    background: #dc3545;
}

.action-icon {
    font-size: 3rem;
}

.action-label {
    font-weight: 600;
    font-size: 0.95rem;
}
</style>

<?php include 'includes/footer.php'; ?>