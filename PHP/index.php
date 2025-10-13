<?php
// Coffee menu data with recommendation flag
$menuItems = [
    'coffee' => [
        'title' => 'Coffee',
        'products' => [
            ['name' => 'Americano', 'price' => 90, 'recommended' => false],
            ['name' => 'Cappuccino', 'price' => 100, 'recommended' => false],
            ['name' => 'Caffé Latte', 'price' => 100, 'recommended' => false],
            ['name' => 'Espressoyma', 'price' => 130, 'recommended' => false],
            ['name' => 'Manual Brew', 'price' => 180, 'recommended' => true]
        ]
    ],
    'latte' => [
        'title' => 'Latte',
        'products' => [
            ['name' => 'Classic Latte', 'price' => 95, 'recommended' => false],
            ['name' => 'Vanilla Latte', 'price' => 110, 'recommended' => true],
            ['name' => 'Caramel Latte', 'price' => 120, 'recommended' => false]
        ]
    ],
    'soda' => [
        'title' => 'Soda',
        'products' => [
            ['name' => 'Cola', 'price' => 50, 'recommended' => false],
            ['name' => 'Sprite', 'price' => 50, 'recommended' => false],
            ['name' => 'Orange Soda', 'price' => 60, 'recommended' => true],
            ['name' => 'Root Beer', 'price' => 60, 'recommended' => false]
        ]
    ],
    'snacks' => [
        'title' => 'Snacks',
        'products' => [
            ['name' => 'Croissant', 'price' => 80, 'recommended' => false],
            ['name' => 'Muffin', 'price' => 75, 'recommended' => true],
            ['name' => 'Cookie', 'price' => 40, 'recommended' => false],
            ['name' => 'Donut', 'price' => 50, 'recommended' => false],
            ['name' => 'Bagel', 'price' => 85, 'recommended' => false],
            ['name' => 'Brownie', 'price' => 60, 'recommended' => false]
        ]
    ]
];

// Set default category
$currentCategory = isset($_GET['category']) ? $_GET['category'] : 'coffee';
if (!array_key_exists($currentCategory, $menuItems)) {
    $currentCategory = 'coffee';
}

$categoryData = $menuItems[$currentCategory];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coffee Shop Menu</title>
    <link rel="stylesheet" href="../Css/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cabin+Condensed:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Calistoga&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-container">
            <button class="nav-btn">Home</button>
            <button class="nav-btn">Recent</button>
            <div class="logo">☕</div>
            <button class="nav-btn">About Us</button>
            <button class="nav-btn">Login</button>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="hero">
        <h1>Menu</h1>
        <p>Handcrafted drinks and pastries</p>
        <button class="about-btn">About us</button>
    </header>

    <!-- Main Content -->
    <div class="container">
        <!-- Sidebar Menu -->
        <aside class="sidebar">
            <?php foreach ($menuItems as $key => $item): ?>
                <a href="?category=<?php echo $key; ?>" class="menu-item <?php echo ($currentCategory === $key) ? 'active' : ''; ?>" data-category="<?php echo $key; ?>">
                    <div class="circle"></div>
                    <span><?php echo $item['title']; ?></span>
                </a>
            <?php endforeach; ?>
        </aside>

        <!-- Vertical Divider Line -->
        <div class="divider-line"></div>

        <!-- Products Section -->
        <main class="products-section">
            <div class="section-header">
                <h2 class="category-title"><?php echo $categoryData['title']; ?></h2>
                <p class="item-count"><?php echo count($categoryData['products']); ?> items</p>
            </div>

            <!-- Products Grid -->
            <div class="products-grid">
                <?php foreach ($categoryData['products'] as $product): ?>
                    <form method="POST" action="process_order.php" class="product-card-form <?php echo $product['recommended'] ? 'featured' : ''; ?>">
                        <button type="submit" class="product-card" name="product" value="<?php echo htmlspecialchars($product['name']); ?>">
                            <?php if ($product['recommended']): ?>
                                <div class="recommendation-badge"></div>
                            <?php endif; ?>
                            
                            <div class="product-image">
                                <svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 300'>
                                    <rect fill='#264d2a' width='200' height='300' rx='20'/>
                                    <rect x='50' y='80' width='100' height='140' fill='#f5f5f0' rx='10'/>
                                    <rect x='45' y='75' width='110' height='20' fill='#333'/>
                                    <circle cx='75' cy='140' r='8' fill='#d4a574'/>
                                    <circle cx='125' cy='160' r='6' fill='#d4a574'/>
                                </svg>
                            </div>
                            
                            <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="product-price">₱<?php echo number_format($product['price']); ?></p>
                        </button>
                    </form>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <!-- Hidden form for non-featured products -->
    <div id="orderConfirmation" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Order Added</h2>
            <p id="confirmationText"></p>
            <button id="continueShoppingBtn" class="btn-primary">Continue Shopping</button>
        </div>
    </div>

    <script src="../js/script.js"></script>
</body>
</html>