<?php
/**
 * Crafted Commune Café - Menu System
 * COMPLETELY DATABASE-DRIVEN - NO HARDCODED DATA!
 */

// Include database connection
require_once 'config.php';

// DEBUG: Check if database is connected
if (!isset($pdo)) {
    die("ERROR: Database connection failed! Check config.php");
}

try {
    // Fetch active categories from database
    $categoriesStmt = $pdo->query("
        SELECT * FROM categories 
        WHERE is_active = 1 
        ORDER BY display_order, name
    ");
    $dbCategories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // DEBUG: Check if categories loaded
    if (empty($dbCategories)) {
        die("ERROR: No categories found in database! Go to Admin → Categories and add some.");
    }
    
    // Build menu items array from database
    $menuItems = [];
    
    foreach ($dbCategories as $category) {
        // Get ONLY ACTIVE products for this category
        $productsStmt = $pdo->prepare("
            SELECT id, name, price, points, image, is_recommended 
            FROM products 
            WHERE category_id = ? AND is_active = 1 
            ORDER BY name
        ");
        $productsStmt->execute([$category['id']]);
        $products = $productsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format products array
        $formattedProducts = [];
        foreach ($products as $product) {
            $formattedProducts[] = [
                'id' => (int)$product['id'],
                'name' => $product['name'],
                'price' => (float)$product['price'],
                'points' => (int)$product['points'],
                'image' => $product['image'],
                'recommended' => (bool)$product['is_recommended']
            ];
        }
        
        // Add to menu items
        $menuItems[$category['slug']] = [
            'title' => $category['name'],
            'icon' => $category['icon'],
            'products' => $formattedProducts
        ];
    }
    
    // Carousel images
    $carouselImages = [
        'images/carousel/slide1.jpg',
        'images/carousel/slide2.jpg',
        'images/carousel/slide3.jpg',
        'images/carousel/slide4.jpg'
    ];
    
    // Convert to JSON
    $menuJSON = json_encode($menuItems, JSON_HEX_TAG | JSON_HEX_AMP);
    $carouselJSON = json_encode($carouselImages);
    
} catch (PDOException $e) {
    die("DATABASE ERROR: " . $e->getMessage());
}
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
    
    <!-- DEBUG INFO (Remove this after testing) -->
    <!-- 
    LOADED FROM DATABASE:
    Categories: <?php echo count($dbCategories); ?>
    <?php foreach($menuItems as $key => $cat): ?>
        - <?php echo $cat['title']; ?>: <?php echo count($cat['products']); ?> products
    <?php endforeach; ?>
    -->
    
    <!-- Top Navigation Bar -->
    <nav class="navbar">
        <div class="nav-container">
            <button class="nav-btn" onclick="showPage('home')" id="homeBtn">Home</button>
            <button class="nav-btn" onclick="showPage('menu')" id="menuBtn">Menu</button>
            <div class="logo"><img src="../images/icons/logo.jpg" alt=""></div>
            <button class="nav-btn" onclick="showPage('about')" id="aboutBtn">About Us</button>
            <button class="nav-btn" onclick="showPage('contact')" id="contactBtn">Contact</button>
        </div>
    </nav>

    <!-- HOME PAGE -->
    <div id="homePage" class="page-content active">
        <header class="hero home-hero">
            <h1 class="hero-title">Crafted Commune</h1>
            <p class="hero-subtitle">Where Every Cup Tells a Story</p>
            <button class="hero-cta" onclick="showPage('menu')">Explore Menu</button>
        </header>

        <section class="carousel-section">
            <h2 class="section-title">Discover Our Story</h2>
            <div class="carousel-container">
                <button class="carousel-btn prev" id="prevBtn">‹</button>
                <div class="carousel-wrapper">
                    <div class="carousel-track" id="carouselTrack">
                        <div class="carousel-slide">
                            <img src="../images/carousel/slide1.jpg" alt="Featured Product 1" onerror="this.src='https://via.placeholder.com/800x400/264d2a/ffffff?text=Product+1'">
                        </div>
                        <div class="carousel-slide">
                            <img src="../images/carousel/slide2.jpg" alt="Featured Product 2" onerror="this.src='https://via.placeholder.com/800x400/3d5a3d/ffffff?text=Product+2'">
                        </div>
                        <div class="carousel-slide">
                            <img src="../images/carousel/slide3.jpg" alt="Featured Product 3" onerror="this.src='https://via.placeholder.com/800x400/264d2a/ffffff?text=Product+3'">
                        </div>
                        <div class="carousel-slide">
                            <img src="../images/carousel/slide4.jpg" alt="Featured Product 4" onerror="this.src='https://via.placeholder.com/800x400/3d5a3d/ffffff?text=Product+4'">
                        </div>
                    </div>
                </div>
                <button class="carousel-btn next" id="nextBtn">›</button>
            </div>
            <div class="carousel-dots" id="carouselDots"></div>
        </section>

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
            <aside class="sidebar">
                <?php 
                $isFirst = true;
                foreach($menuItems as $slug => $category): 
                    $emojis = ['coffee' => '☕','non-coffee' => '🍵', 'latte' => '🧉','breakfast' => '🥞','lunch' => '🍚', 'soda' => '🥤', 'snacks' => '🍪'];
                    $emoji = $emojis[$slug] ?? '📁';
                ?>
                <a href="#" class="menu-item <?php echo $isFirst ? 'active' : ''; ?>" data-category="<?php echo htmlspecialchars($slug); ?>">
                    <div class="circle"><?php echo $emoji; ?></div>
                    <span><?php echo htmlspecialchars($category['title']); ?></span>
                </a>
                <?php 
                    $isFirst = false;
                endforeach; 
                ?>
            </aside>

            <main class="products-section">
                <div class="section-header">
                    <h2 class="category-title" id="categoryTitle">Menu</h2>
                    <p class="item-count" id="itemCount">Loading...</p>
                </div>
                <div class="products-grid" id="productGrid">
                    <!-- Products loaded by JavaScript -->
                </div>
            </main>

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
                    <img src="images/about/cafe-interior.jpg" alt="Café Interior" onerror="this.src='https://via.placeholder.com/500x600/264d2a/ffffff?text=Our+Café'">
                </div>
            </div>
        </section>
    </div>

    <!-- CONTACT PAGE -->
    <div id="contactPage" class="page-content">
        <header class="hero">
            <h1>Contact Us</h1>
            <p>Get in touch with us</p>
        </header>

        <section class="contact-section">
            <div class="contact-container">
                <div class="contact-info">
                    <h2>📍 Visit Us</h2>
                    <div class="info-card">
                        <h3>🏪 Crafted Commune Café</h3>
                        <p><strong>Address:</strong><br>
                        <!-- EDIT THIS: Your actual address -->
                        21 Aurea, Mabalacat City, Pampanga, Philippines<br>
                        Mabalacat City, Pampanga<br>
                        Philippines</p>
                        
                        <p><strong>Main Phone:</strong><br>
                        <!-- EDIT THIS: Your main phone number -->
                        📞 +63 912 345 6789</p>
                        
                        <p><strong>Email:</strong><br>
                        <!-- EDIT THIS: Your email -->
                        ✉️ hello@craftedcommune.com</p>
                        
                        <p><strong>Facebook:</strong><br>
                        <!-- EDIT THIS: Your Facebook page -->
                        📱 facebook.com/craftedcommune</p>
                    </div>
                    
                    <h3>⏰ Opening Hours</h3>
                    <div class="hours-card">
                        <!-- EDIT THESE: Your actual hours -->
                        <p><strong>Monday - Friday:</strong> 7:00 AM - 9:00 PM</p>
                        <p><strong>Saturday - Sunday:</strong> 8:00 AM - 10:00 PM</p>
                        <p><strong>Holidays:</strong> 9:00 AM - 8:00 PM</p>
                        <p style="margin-top: 1rem; color: #e74c3c;">
                            <strong>⚠️ Closed on:</strong> Christmas Day, New Year's Day
                        </p>
                    </div>

                    <h3>👥 Our Team</h3>
                    <div class="team-grid">
                        <!-- EDIT THESE: Replace with your actual team members -->
                        <div class="team-member">
                            <div class="member-avatar">👨‍💼</div>
                            <h4>Test Manager</h4>
                            <p>Store Manager</p>
                            <p>📞 0917-123-4567</p>
                            <p>📧 manager@test.com</p>
                        </div>
                        <div class="team-member">
                            <div class="member-avatar">👩‍🍳</div>
                            <h4>Test Barista</h4>
                            <p>Head Barista</p>
                            <p>📞 0918-765-4321</p>
                            <p>📧 barista@test.com</p>
                        </div>
                        <div class="team-member">
                            <div class="member-avatar">👨‍🍳</div>
                            <h4>Test Chef</h4>
                            <p>Head Chef</p>
                            <p>📞 0919-555-1234</p>
                            <p>📧 chef@test.com</p>
                        </div>
                        <div class="team-member">
                            <div class="member-avatar">👩‍💼</div>
                            <h4>Test Assistant</h4>
                            <p>Assistant Manager</p>
                            <p>📞 0920-999-8888</p>
                            <p>📧 assistant@test.com</p>
                        </div>
                    </div>
                </div>
                
                <div class="contact-right">
                    <!-- Google Map Embed -->
                    <div class="map-container">
                        <h2>🗺️ Find Us on Map</h2>
                        <div class="map-wrapper">
                            <!-- 
                            ============================================
                            HOW TO CHANGE THE MAP:
                            ============================================
                            1. Go to Google Maps (maps.google.com)
                            2. Search for YOUR cafe location
                            3. Click "Share" button
                            4. Click "Embed a map" tab
                            5. Copy the ENTIRE <iframe> code
                            6. Replace the iframe code below
                            
                            OR use these coordinates:
                            - Change "14.8870,120.2750" to your coordinates
                            ============================================
                            -->
                            <iframe 
                                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d962.6692926283775!2d120.5856482696131!3d15.17606389342154!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3396ed8f9b71be9d%3A0xeb897dfc68437e67!2s21%20Aurea%2C%20Mabalacat%20City%2C%20Pampanga%2C%20Philippines!5e0!3m2!1sen!2sus!4v1762588532142!5m2!1sen!2sus" 
                                width="800" 
                                height="600" 
                                style="border:0; border-radius: 12px;" 
                                allowfullscreen="" 
                                loading="lazy" 
                                referrerpolicy="no-referrer-when-downgrade">
                            </iframe>
                        </div>
                        <p class="map-note">
                            📍 <strong>Can't find us?</strong> Call us and we'll guide you!<br>
                            🚗 <strong>Parking:</strong> Free parking available behind the building<br>
                            🚌 <strong>Public Transport:</strong> Bus stop 50m away (Route 5, 12)
                        </p>
                    </div>
                    
                    <!-- Contact Form -->
                    <div class="contact-form-wrapper">
                        <h2>💌 Send Us a Message</h2>
                        <p style="margin-bottom: 1.5rem; color: #666;">
                            Have a question? Want to make a reservation? Send us a message and we'll get back to you within 24 hours!
                        </p>
                        <form class="contact-form" id="contactForm">
                            <div class="form-group">
                                <label for="contact_name">Your Name *</label>
                                <input type="text" id="contact_name" name="name" placeholder="Juan Dela Cruz" required>
                            </div>
                            <div class="form-group">
                                <label for="contact_email">Your Email *</label>
                                <input type="email" id="contact_email" name="email" placeholder="juan@example.com" required>
                            </div>
                            <div class="form-group">
                                <label for="contact_phone">Phone Number</label>
                                <input type="tel" id="contact_phone" name="phone" placeholder="0917-123-4567">
                            </div>
                            <div class="form-group">
                                <label for="contact_subject">Subject *</label>
                                <select id="contact_subject" name="subject" required>
                                    <option value="">Select a subject</option>
                                    <option value="general">General Inquiry</option>
                                    <option value="reservation">Reservation</option>
                                    <option value="catering">Catering Request</option>
                                    <option value="feedback">Feedback</option>
                                    <option value="complaint">Complaint</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="contact_message">Your Message *</label>
                                <textarea id="contact_message" name="message" rows="5" placeholder="Tell us what's on your mind..." required></textarea>
                            </div>
                            <button type="submit" class="submit-btn">
                                📤 Send Message
                            </button>
                        </form>
                    </div>

                    <!-- Quick Contact Cards -->
                    <div class="quick-contact-grid">
                        <div class="quick-contact-card">
                            <div class="quick-icon">📞</div>
                            <h4>Call Us</h4>
                            <p>+63 912 345 6789</p>
                            <a href="tel:+639123456789" class="quick-btn">Call Now</a>
                        </div>
                        <div class="quick-contact-card">
                            <div class="quick-icon">✉️</div>
                            <h4>Email Us</h4>
                            <p>hello@craftedcommune.com</p>
                            <a href="mailto:hello@craftedcommune.com" class="quick-btn">Send Email</a>
                        </div>
                        <div class="quick-contact-card">
                            <div class="quick-icon">📱</div>
                            <h4>Facebook</h4>
                            <p>@craftedcommune</p>
                            <a href="https://facebook.com" target="_blank" class="quick-btn">Message Us</a>
                        </div>
                    </div>
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

    <script>
        // CRITICAL: Load menu data from PHP/Database
        const menuData = <?php echo $menuJSON; ?>;
        const carouselImages = <?php echo $carouselJSON; ?>;
        
        // DEBUG: Check if data loaded
        console.log('Menu Data Loaded:', menuData);
        console.log('Total Categories:', Object.keys(menuData).length);
        
        // Secret admin access
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'A') {
                window.location.href = 'admin/login.php';
            }
        });

        // Contact form handler
        document.addEventListener('DOMContentLoaded', function() {
            const contactForm = document.getElementById('contactForm');
            if (contactForm) {
                contactForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    alert('Thank you for your message! We will get back to you soon.');
                    contactForm.reset();
                });
            }
        });
    </script>
    
    <script src="script.js"></script>

</body>
</html>