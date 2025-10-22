<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?= SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Calistoga&family=Cabin+Condensed:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/admin-style.css">
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">☕</div>
                <h2><?= SITE_NAME ?></h2>
                <p class="admin-label">Admin Panel</p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                    <span class="nav-icon">📊</span>
                    <span>Dashboard</span>
                </a>
                <a href="products.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : '' ?>">
                    <span class="nav-icon">📦</span>
                    <span>Products</span>
                </a>
                <a href="categories.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : '' ?>">
                    <span class="nav-icon">🗂️</span>
                    <span>Categories</span>
                </a>
                <a href="orders.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : '' ?>">
                    <span class="nav-icon">🛒</span>
                    <span>Orders</span>
                </a>
                <a href="analytics.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'active' : '' ?>">
                    <span class="nav-icon">📈</span>
                    <span>Analytics</span>
                </a>
                <a href="settings.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : '' ?>">
                    <span class="nav-icon">⚙️</span>
                    <span>Settings</span>
                </a>
                
                <div class="nav-divider"></div>
                
                <a href="../index.php" class="nav-link nav-link-secondary" target="_blank">
                    <span class="nav-icon">🌐</span>
                    <span>View Website</span>
                </a>
                <a href="logout.php" class="nav-link nav-link-danger">
                    <span class="nav-icon">🚪</span>
                    <span>Logout</span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar">👤</div>
                    <div>
                        <div class="user-name"><?= e($_SESSION['admin_name']) ?></div>
                        <div class="user-role">Administrator</div>
                    </div>
                </div>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="content-wrapper"></div>