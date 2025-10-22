<?php
/**
 * Crafted Commune Café - Complete Menu System
 * Main index file with PHP menu data and points system
 */

// Menu items array with points system (10 pesos = 1 point)
$menuItems = [
    'coffee' => [
        'title' => 'Coffee',
        'icon' => 'images/icons/coffee-icon.png', // Replace with your icon
        'products' => [
            ['name' => 'Americano', 'price' => 90, 'points' => 9, 'image' => 'images/americano.jpg', 'recommended' => false],
            ['name' => 'Cappuccino', 'price' => 100, 'points' => 10, 'image' => 'images/cappuccino.jpg', 'recommended' => false],
            ['name' => 'Caffè Latte', 'price' => 100, 'points' => 10, 'image' => 'images/caffelatte.jpg', 'recommended' => false],
            ['name' => 'Espressoyna', 'price' => 130, 'points' => 13, 'image' => 'images/espressoyna.jpg', 'recommended' => false],
            ['name' => 'Manual Brew', 'price' => 180, 'points' => 18, 'image' => 'images/manualbrew.jpg', 'recommended' => true]
        ]
    ],
    'latte' => [
        'title' => 'Latte',
        'icon' => 'images/icons/latte-icon.png',
        'products' => [
            ['name' => 'Classic Latte', 'price' => 95, 'points' => 10, 'image' => 'images/classiclatte.jpg', 'recommended' => false],
            ['name' => 'Caramel Latte', 'price' => 120, 'points' => 12, 'image' => 'images/caramellatte.jpg', 'recommended' => true],
            ['name' => 'Vanilla Latte', 'price' => 115, 'points' => 12, 'image' => 'images/vanillalatte.jpg', 'recommended' => false],
            ['name' => 'Mocha Latte', 'price' => 125, 'points' => 13, 'image' => 'images/mochalatte.jpg', 'recommended' => false]
        ]
    ],
    'soda' => [
        'title' => 'Soda',
        'icon' => 'images/icons/soda-icon.png',
        'products' => [
            ['name' => 'Cola', 'price' => 60, 'points' => 6, 'image' => 'images/cola.jpg', 'recommended' => false],
            ['name' => 'Lemon Soda', 'price' => 65, 'points' => 7, 'image' => 'images/lemonsoda.jpg', 'recommended' => true],
            ['name' => 'Orange Fizz', 'price' => 70, 'points' => 7, 'image' => 'images/orangefizz.jpg', 'recommended' => false],
            ['name' => 'Root Beer', 'price' => 60, 'points' => 6, 'image' => 'images/rootbeer.jpg', 'recommended' => false]
        ]
    ],
    'snacks' => [
        'title' => 'Snacks',
        'icon' => 'images/icons/snacks-icon.png',
        'products' => [
            ['name' => 'Croissant', 'price' => 80, 'points' => 8, 'image' => 'images/croissant.jpg', 'recommended' => false],
            ['name' => 'Muffin', 'price' => 75, 'points' => 8, 'image' => 'images/muffin.jpg', 'recommended' => true],
            ['name' => 'Cookie', 'price' => 50, 'points' => 5, 'image' => 'images/cookie.jpg', 'recommended' => false],
            ['name' => 'Brownie', 'price' => 90, 'points' => 9, 'image' => 'images/brownie.jpg', 'recommended' => false],
            ['name' => 'Donut', 'price' => 50, 'points' => 5, 'image' => 'images/donut.jpg', 'recommended' => false],
            ['name' => 'Bagel', 'price' => 85, 'points' => 9, 'image' => 'images/bagel.jpg', 'recommended' => false]
        ]
    ]
];

// Carousel images for homepage
$carouselImages = [
    'images/carousel/slide1.jpg',
    'images/carousel/slide2.jpg',
    'images/carousel/slide3.jpg',
    'images/carousel/slide4.jpg'
];

// Convert to JSON for JavaScript usage
$menuJSON = json_encode($menuItems);
$carouselJSON = json_encode($carouselImages);

// Get current page
$currentPage = isset($_GET['page']) ? $_GET['page'] : 'home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crafted Commune Café</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Calistoga&family=Cabin+Condensed:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Stylesheet -->
    <link rel="stylesheet" href="style.css">
</head>
<body>
    
    <!-- Top Navigation Bar -->
    <nav class="navbar">
        <div class="nav-container">
            <button class="nav-btn" onclick="showPage('home')" id="homeBtn">Home</button>
            <button class="nav-btn" onclick="showPage('menu')" id="menuBtn">Menu</button>
            <!-- 🖼️ LOGO: Replace with <img src="images/logo.png" alt="Logo" class="logo"> -->
            <div class="logo">☕</div>
            <button class="nav-btn" onclick="showPage('about')" id="aboutBtn">About Us</button>
            <button class="nav-btn">Contact</button>
        </div>
    </nav>

    <!-- HOME PAGE -->
    <div id="homePage" class="page-content active">
        <!-- Hero Section -->
        <header class="hero home-hero">
            <h1 class="hero-title">Crafted Commune</h1>
            <p class="hero-subtitle">Where Every Cup Tells a Story</p>
            <button class="hero-cta" onclick="showPage('menu')">Explore Menu</button>
        </header>

        <!-- Product Carousel -->
        <section class="carousel-section">
            <h2 class="section-title">Featured Products</h2>
            <div class="carousel-container">
                <button class="carousel-btn prev" id="prevBtn">‹</button>
                <div class="carousel-wrapper">
                    <div class="carousel-track" id="carouselTrack">
                        <!-- Carousel images will be loaded here -->
                        <div class="carousel-slide">
                            <img src="images/carousel/slide1.jpg" alt="Featured Product 1" onerror="this.src='https://via.placeholder.com/800x400/264d2a/ffffff?text=Product+1'">
                        </div>
                        <div class="carousel-slide">
                            <img src="images/carousel/slide2.jpg" alt="Featured Product 2" onerror="this.src='https://via.placeholder.com/800x400/3d5a3d/ffffff?text=Product+2'">
                        </div>
                        <div class="carousel-slide">
                            <img src="images/carousel/slide3.jpg" alt="Featured Product 3" onerror="this.src='https://via.placeholder.com/800x400/264d2a/ffffff?text=Product+3'">
                        </div>
                        <div class="carousel-slide">
                            <img src="images/carousel/slide4.jpg" alt="Featured Product 4" onerror="this.src='https://via.placeholder.com/800x400/3d5a3d/ffffff?text=Product+4'">
                        </div>
                    </div>
                </div>
                <button class="carousel-btn next" id="nextBtn">›</button>
            </div>
            <div class="carousel-dots" id="carouselDots"></div>
        </section>

        <!-- Quick Features -->
        <section class="features-section">
            <div class="feature-card">
                <div class="feature-icon">☕</div>
                <h3>Premium Coffee</h3>
                <p>Sourced from the finest beans worldwide</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🎯</div>
                <h3>Points Reward</h3>
                <p>Earn points with every purchase</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🍰</div>
                <h3>Fresh Pastries</h3>
                <p>Baked fresh daily</p>
            </div>
        </section>
    </div>

    <!-- MENU PAGE -->
    <div id="menuPage" class="page-content">
        <header class="hero">
            <h1>Menu</h1>
            <p>Handcrafted drinks and pastries</p>
        </header>

        <div class="container">
            <!-- Sidebar Category Navigation -->
            <aside class="sidebar">
                <?php foreach($menuItems as $key => $category): ?>
                <a href="#" class="menu-item <?= $key === 'coffee' ? 'active' : '' ?>" data-category="<?= $key ?>">
                    <!-- Replace emoji with: <img src="<?= $category['icon'] ?>" alt="<?= $category['title'] ?>" class="category-icon"> -->
                    <div class="circle"><?= ['coffee' => '☕', 'latte' => '🥛', 'soda' => '🥤', 'snacks' => '🍪'][$key] ?></div>
                    <span><?= $category['title'] ?></span>
                </a>
                <?php endforeach; ?>
            </aside>

            <!-- Products Section -->
            <main class="products-section">
                <div class="section-header">
                    <h2 class="category-title" id="categoryTitle">Coffee</h2>
                    <p class="item-count" id="itemCount">5 items</p>
                </div>

                <div class="products-grid" id="productGrid">
                    <!-- Products will be dynamically loaded here -->
                </div>
            </main>

            <!-- Floating Receipt Panel -->
            <aside class="receipt-panel" id="receiptPanel">
                <div class="receipt-header">
                    <h3>☕ Crafted Commune</h3>
                    <button class="close-receipt" id="closeReceipt">✕</button>
                </div>
                <div class="receipt-divider"></div>
                
                <div class="receipt-items" id="receiptItems">
                    <div class="empty-receipt">
                        <p>No items added yet</p>
                        <small>Click on products to add</small>
                    </div>
                </div>
                
                <div class="receipt-divider"></div>
                
                <div class="receipt-total">
                    <span class="total-label">Total:</span>
                    <span class="total-amount" id="totalAmount">₱0</span>
                </div>
                
                <div class="receipt-points">
                    <span class="points-label">Points Earned:</span>
                    <span class="points-amount" id="totalPoints">0 pts</span>
                </div>
                
                <button class="pay-btn" id="payBtn">
                    <span>Pay Now</span>
                </button>
            </aside>
        </div>
    </div>

    <!-- ABOUT PAGE -->
    <div id="aboutPage" class="page-content">
        <header class="hero">
            <h1>About Us</h1>
            <p>Our Story & Values</p>
        </header>

        <section class="about-section">
            <div class="about-container">
                <div class="about-content">
                    <h2>Welcome to Crafted Commune</h2>
                    <p>At Crafted Commune, we believe that every cup of coffee tells a story. Founded with a passion for exceptional coffee and community, we've created a space where quality meets comfort.</p>
                    
                    <h3>Our Mission</h3>
                    <p>To provide our community with the finest handcrafted beverages and freshly baked goods, while creating a welcoming environment where connections are made and memories are crafted.</p>
                    
                    <h3>What Makes Us Special</h3>
                    <ul class="about-list">
                        <li><strong>Quality First:</strong> We source premium beans from sustainable farms worldwide</li>
                        <li><strong>Artisan Crafted:</strong> Every drink is carefully prepared by our skilled baristas</li>
                        <li><strong>Fresh Daily:</strong> Our pastries and snacks are baked fresh every morning</li>
                        <li><strong>Community Focused:</strong> We're more than a café - we're a gathering place</li>
                        <li><strong>Rewards Program:</strong> Earn points with every purchase and enjoy exclusive benefits</li>
                    </ul>

                    <h3>Visit Us</h3>
                    <p>Come experience the Crafted Commune difference. Whether you're here for your morning coffee, a midday snack, or an afternoon break, we're here to serve you with a smile.</p>
                </div>
                
                <div class="about-image">
                    <!-- Replace with actual image -->
                    <img src="images/about/cafe-interior.jpg" alt="Café Interior" onerror="this.src='https://via.placeholder.com/500x600/264d2a/ffffff?text=Our+Café'">
                </div>
            </div>
        </section>
    </div>

    <!-- Payment Modal -->
    <div class="modal" id="paymentModal">
        <div class="modal-content">
            <span class="close" id="modalClose">×</span>
            <div class="modal-header">
                <h2>Thank You! 🎉</h2>
            </div>
            <div class="modal-body">
                <p class="thank-you-message">Thank you for your order at Crafted Commune!</p>
                <div class="modal-total">
                    <span>Total Amount:</span>
                    <span class="modal-amount" id="modalAmount">₱0</span>
                </div>
                <div class="modal-points">
                    <span>Points Earned:</span>
                    <span class="modal-points-amount" id="modalPoints">0 pts</span>
                </div>
            </div>
            <button class="modal-close-btn" id="modalCloseBtn">Continue Shopping</button>
        </div>
    </div>

    <!-- Pass PHP data to JavaScript -->
    <script>
        const menuData = <?php echo $menuJSON; ?>;
        const carouselImages = <?php echo $carouselJSON; ?>;
        
        // Secret admin access: Press Ctrl+Shift+A (or Cmd+Shift+A on Mac)
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'A') {
                window.location.href = 'admin/login.php';
            }
        });
    </script>
    
    <!-- Main JavaScript -->
    <script src="script.js"></script>

</body>
</html>