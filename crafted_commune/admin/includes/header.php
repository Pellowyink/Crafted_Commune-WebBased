<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Admin Panel' ?> - <?= SITE_NAME ?></title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Calistoga&family=Cabin+Condensed:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Admin Styles -->
    <link rel="stylesheet" href="assets/admin-style.css">
</head>
<body>
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo">☕</div>
            <h2><?= SITE_NAME ?></h2>
        </div>
        
        <nav class="sidebar-nav">
            <a href="index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">
                <span class="nav-icon">📊</span>
                <span class="nav-text">Dashboard</span>
            </a>
            
            <a href="products.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'products.php' ? 'active' : '' ?>">
                <span class="nav-icon">📦</span>
                <span class="nav-text">Products</span>
            </a>
            
            <a href="categories.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'categories.php' ? 'active' : '' ?>">
                <span class="nav-icon">🗂️</span>
                <span class="nav-text">Categories</span>
            </a>
            
            <a href="orders.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'orders.php' ? 'active' : '' ?>">
                <span class="nav-icon">🛒</span>
                <span class="nav-text">Orders</span>
            </a>
            
            <a href="loyalty_members.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'loyalty_members.php' ? 'active' : '' ?>">
                <span class="nav-icon">🎁</span>
                <span class="nav-text">Loyalty Members</span>
                <?php
                // Show count of active members
                $activeCount = $pdo->query("SELECT COUNT(*) as count FROM loyalty_members WHERE is_active = 1")->fetch()['count'];
                if ($activeCount > 0):
                ?>
                    <span class="nav-badge"><?= $activeCount ?></span>
                <?php endif; ?>
            </a>
            
            <a href="sales_cutoff.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'sales_cutoff.php' ? 'active' : '' ?>">
                <span class="nav-icon">💰</span>
                <span class="nav-text">Sales Cutoff</span>
            </a>
            
            <a href="analytics.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'analytics.php' ? 'active' : '' ?>">
                <span class="nav-icon">📈</span>
                <span class="nav-text">Analytics</span>
            </a>

             <a href="inventory.php" class="nav-link <?php echo ($currentPage == 'inventory.php') ? 'active' : ''; ?>">
                <span class="nav-icon">📦</span>
                <span>Inventory</span>
            </a>
            
            <!-- <a href="product_ratings.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'product_ratings.php' ? 'active' : '' ?>">
                <span class="nav-icon">⭐</span>
                <span class="nav-text">Product Ratings</span>
            </a> -->
            
            <a href="settings.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : '' ?>">
                <span class="nav-icon">⚙️</span>
                <span class="nav-text">Settings</span>
            </a>
            
            <div class="nav-divider"></div>
            
            <a href="../index.php" class="nav-link" target="_blank">
                <span class="nav-icon">🌐</span>
                <span class="nav-text">View Website</span>
            </a>

            <a href="logout.php" class="nav-link nav-link-danger">
                <span class="nav-icon">🚪</span>
                <span class="nav-text">Logout</span>
            </a>
        </nav>
        
        <div class="sidebar-footer">
            <div class="admin-info">
                <div class="admin-avatar">
                    <?= strtoupper(substr($_SESSION['admin_username'] ?? 'A', 0, 1)) ?>
                </div>
                <div class="admin-details">
                    <div class="admin-name"><?= e($_SESSION['admin_name'] ?? 'Admin') ?></div>
                    <div class="admin-role">Administrator</div>
                </div>
            </div>
        </div>
    </aside>
    
    <!-- Main Content Area -->
    <main class="main-content">
        <div class="content-wrapper">

<style>
/* Sidebar Styles */
:root {
    --primary: #3d5a3d;
    --primary-dark: #273B08;
    --text-dark: #e4e6e9ff;
    --text-light: #e7ebeeff;
    --bg-light: #f8f9fa;
    --border-color: #e0e0e0;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Cabin Condensed', sans-serif;
    background: var(--bg-light);
    display: flex;
    min-height: 100vh;
}

.sidebar {
    width: 260px;
    background: #273B08;
    border-right: 1px solid var(--border-color);
    display: flex;
    flex-direction: column;
    position: fixed;
    height: 100vh;
    overflow-y: auto;
}

.sidebar-header {
    padding: 2rem 1.5rem;
    border-bottom: 1px solid var(--border-color);
    text-align: center;
}

.sidebar-header .logo {
    font-size: 3rem;
    margin-bottom: 0.5rem;
}

.sidebar-header h2 {
    font-family: 'Calistoga', serif;
    color: #e4e6e9ff;
    font-size: 1.3rem;
}

.sidebar-nav {
    flex: 1;
    padding: 1rem 0;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.875rem 1.5rem;
    color: var(--text-dark);
    text-decoration: none;
    transition: all 0.3s ease;
    position: relative;
}

.nav-link:hover {
    background: var(--bg-light);
    color: var(--primary);
}

.nav-link.active {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
}

.nav-link.active::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: white;
}

.nav-icon {
    font-size: 1.3rem;
}

.nav-text {
    font-weight: 600;
    font-size: 0.95rem;
    flex: 1;
}

.nav-badge {
    background: #dc3545;
    color: white;
    padding: 0.2rem 0.5rem;
    border-radius: 10px;
    font-size: 0.75rem;
    font-weight: bold;
}

.nav-divider {
    height: 1px;
    background: var(--border-color);
    margin: 1rem 1.5rem;
}

.nav-link-danger {
    color: #dc3545;
}

.nav-link-danger:hover {
    background: #fff5f5;
    color: #c82333;
}

.sidebar-footer {
    padding: 1.5rem;
    border-top: 1px solid var(--border-color);
}

.admin-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.admin-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.1rem;
}

.admin-details {
    flex: 1;
}

.admin-name {
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--text-dark);
}

.admin-role {
    font-size: 0.8rem;
    color: var(--text-light);
}

/* Main Content */
.main-content {
    flex: 1;
    margin-left: 260px;
    padding: 2rem;
    min-height: 100vh;
}

.content-wrapper {
    max-width: 1400px;
    margin: 0 auto;
}

/* Responsive */
@media (max-width: 768px) {
    .sidebar {
        width: 70px;
    }
    
    .sidebar-header h2,
    .nav-text,
    .admin-details,
    .nav-badge {
        display: none;
    }
    
    .sidebar-header {
        padding: 1.5rem 0.5rem;
    }
    
    .nav-link {
        justify-content: center;
        padding: 1rem;
    }
    
    .main-content {
        margin-left: 70px;
    }
}
</style>