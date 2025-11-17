-- =======================================================
-- CRAFTED COMMUNE CAFÉ - MASTER SCHEMA V4.0 (FINAL with PROPER INGREDIENTS)
-- This script:
-- 1. Drops and recreates the database for a clean start.
-- 2. Defines the complete schema.
-- 3. Inserts all 42 products and categories.
-- 4. Inserts a comprehensive list of 21 inventory ingredients.
-- 5. Links ALL 42 products to their proper ingredients (including the food/snack items).
-- 6. Correctly calculates and updates stock statuses for all products.
-- =======================================================

-- ========================================
-- 1. DATABASE CLEANUP AND CREATION
-- ========================================

DROP DATABASE IF EXISTS crafted_commune;

-- Create Database
CREATE DATABASE IF NOT EXISTS crafted_commune;
USE crafted_commune;

-- ========================================
-- 2. SCHEMA DEFINITION (ALL TABLES)
-- ========================================
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    full_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active TINYINT(1) DEFAULT 1
);

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(50) UNIQUE NOT NULL,
    icon VARCHAR(255),
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    points INT NOT NULL,
    average_rating DECIMAL(3,2) DEFAULT 0.00,
    rating_count INT DEFAULT 0,
    image VARCHAR(255),
    is_recommended TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    stock_status ENUM('in_stock', 'low_stock', 'out_of_stock') DEFAULT 'in_stock',
    stock_quantity INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS loyalty_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    points INT DEFAULT 0,
    total_purchases DECIMAL(10, 2) DEFAULT 0.00,
    total_orders INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_purchase TIMESTAMP NULL,
    is_active TINYINT(1) DEFAULT 1,
    expiration_date DATE NULL COMMENT 'Membership expiration date (NULL = no expiration)'
);

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    total_points INT NOT NULL,
    loyalty_member_id INT NULL,
    payment_method VARCHAR(50) DEFAULT 'cash',
    order_status ENUM('pending', 'completed', 'cancelled') DEFAULT 'completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (loyalty_member_id) REFERENCES loyalty_members(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(100) NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    unit_points INT NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    subtotal_points INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS loyalty_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    order_id INT,
    transaction_type ENUM('earn', 'redeem', 'adjustment') DEFAULT 'earn',
    points_change INT NOT NULL,
    points_balance INT NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES loyalty_members(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS product_ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    member_id INT NOT NULL,
    order_id INT NOT NULL,
    order_item_id INT NOT NULL,
    rating INT NOT NULL COMMENT '1-5 stars',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES loyalty_members(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE,
    UNIQUE KEY unique_rating (member_id, order_item_id)
);

CREATE TABLE IF NOT EXISTS rating_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unique_code VARCHAR(64) UNIQUE NOT NULL,
    member_id INT NOT NULL,
    order_id INT NOT NULL,
    order_number VARCHAR(50) NOT NULL,
    points_earned INT NOT NULL,
    total_points INT NOT NULL,
    status ENUM('pending', 'completed', 'expired') DEFAULT 'pending',
    email_sent TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    FOREIGN KEY (member_id) REFERENCES loyalty_members(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS milestone_achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    milestone_type VARCHAR(50) NOT NULL,
    milestone_value INT NOT NULL,
    notified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_milestone (member_id, milestone_type, milestone_value),
    FOREIGN KEY (member_id) REFERENCES loyalty_members(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS product_inventory (
  id INT(11) NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  stock INT(11) NOT NULL DEFAULT 0,
  category VARCHAR(255) NOT NULL,
  low_stock_threshold INT(11) DEFAULT 10,
  unit VARCHAR(50) DEFAULT 'units',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY name (name)
);

CREATE TABLE IF NOT EXISTS product_ingredients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    ingredient_id INT NOT NULL,
    quantity_needed DECIMAL(10,2) DEFAULT 1.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (ingredient_id) REFERENCES product_inventory(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_ingredient (product_id, ingredient_id)
);

CREATE TABLE IF NOT EXISTS product_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    view_count INT DEFAULT 0,
    add_to_cart_count INT DEFAULT 0,
    purchase_count INT DEFAULT 0,
    last_viewed TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
);

-- ========================================
-- 3. VIEWS FOR DASHBOARD (FIXED)
-- ========================================

CREATE OR REPLACE VIEW v_best_selling_products AS
SELECT 
    p.id,
    p.name,
    c.name as category,
    COUNT(DISTINCT oi.order_id) as times_ordered,
    COALESCE(SUM(oi.quantity), 0) as total_quantity_sold,
    COALESCE(SUM(oi.subtotal), 0) as total_revenue,
    p.price,
    p.is_recommended
FROM products p
LEFT JOIN categories c ON p.category_id = c.id
LEFT JOIN order_items oi ON p.id = oi.product_id
LEFT JOIN orders o ON oi.order_id = o.id AND o.order_status = 'completed'
GROUP BY p.id, p.name, c.name, p.price, p.is_recommended
ORDER BY total_quantity_sold DESC;

CREATE OR REPLACE VIEW v_daily_sales AS
SELECT 
    DATE(created_at) as sale_date,
    COUNT(id) as total_orders,
    COALESCE(SUM(total_amount), 0) as total_revenue,
    COALESCE(SUM(total_points), 0) as total_points,
    COALESCE(AVG(total_amount), 0) as average_order_value
FROM orders
WHERE order_status = 'completed'
GROUP BY DATE(created_at)
ORDER BY sale_date DESC;

CREATE OR REPLACE VIEW v_category_performance AS
SELECT 
    c.id,
    c.name as category_name,
    COUNT(DISTINCT p.id) as product_count,
    COUNT(DISTINCT oi.order_id) as times_ordered,
    COALESCE(SUM(oi.quantity), 0) as total_items_sold,
    COALESCE(SUM(oi.subtotal), 0) as total_revenue
FROM categories c
LEFT JOIN products p ON c.id = p.category_id
LEFT JOIN order_items oi ON p.id = oi.product_id
LEFT JOIN orders o ON oi.order_id = o.id AND o.order_status = 'completed'
GROUP BY c.id, c.name
ORDER BY total_revenue DESC;

CREATE OR REPLACE VIEW v_product_rating_stats AS
SELECT 
    p.id as product_id,
    p.name as product_name,
    p.category_id,
    c.name as category_name,
    p.average_rating,
    p.rating_count,
    p.is_recommended,
    COUNT(CASE WHEN pr.rating = 5 THEN 1 END) as five_star,
    COUNT(CASE WHEN pr.rating = 4 THEN 1 END) as four_star,
    COUNT(CASE WHEN pr.rating = 3 THEN 1 END) as three_star,
    COUNT(CASE WHEN pr.rating = 2 THEN 1 END) as two_star,
    COUNT(CASE WHEN pr.rating = 1 THEN 1 END) as one_star
FROM products p
LEFT JOIN categories c ON p.category_id = c.id
LEFT JOIN product_ratings pr ON p.id = pr.product_id
GROUP BY p.id, p.name, p.category_id, c.name, p.average_rating, p.rating_count, p.is_recommended;

CREATE OR REPLACE VIEW v_product_stock_status AS
SELECT 
    p.id as product_id,
    p.name as product_name,
    p.price,
    p.is_active,
    p.stock_status,
    COUNT(DISTINCT pi.ingredient_id) as total_ingredients,
    SUM(CASE WHEN inv.stock < pi.quantity_needed THEN 1 ELSE 0 END) as out_of_stock_ingredients,
    CASE 
        WHEN SUM(CASE WHEN inv.stock < pi.quantity_needed THEN 1 ELSE 0 END) > 0 THEN 'out_of_stock'
        WHEN SUM(CASE WHEN inv.stock < inv.low_stock_threshold THEN 1 ELSE 0 END) > 0 THEN 'low_stock'
        ELSE 'in_stock'
    END as calculated_status
FROM products p
LEFT JOIN product_ingredients pi ON p.id = pi.product_id
LEFT JOIN product_inventory inv ON pi.ingredient_id = inv.id
WHERE p.is_active = 1
GROUP BY p.id, p.name, p.price, p.is_active, p.stock_status;

-- ========================================
-- 3. STORED PROCEDURES & TRIGGERS
-- ========================================
DELIMITER //

CREATE PROCEDURE IF NOT EXISTS update_product_rating(IN prod_id INT)
BEGIN
    DECLARE avg_rating DECIMAL(3,2);
    DECLARE rating_cnt INT;
    
    SELECT 
        COALESCE(AVG(rating), 0),
        COUNT(*)
    INTO avg_rating, rating_cnt
    FROM product_ratings
    WHERE product_id = prod_id;
    
    UPDATE products
    SET average_rating = avg_rating,
        rating_count = rating_cnt,
        is_recommended = IF(avg_rating >= 4.0 AND rating_cnt >= 5, 1, is_recommended)
    WHERE id = prod_id;
END //

CREATE PROCEDURE IF NOT EXISTS update_product_stock_status(IN prod_id INT)
BEGIN
    DECLARE new_status VARCHAR(20);
    
    SELECT 
        CASE 
            WHEN SUM(CASE WHEN inv.stock < pi.quantity_needed THEN 1 ELSE 0 END) > 0 
            THEN 'out_of_stock'
            WHEN SUM(CASE WHEN inv.stock < inv.low_stock_threshold THEN 1 ELSE 0 END) > 0 
            THEN 'low_stock'
            ELSE 'in_stock'
        END
    INTO new_status
    FROM products p
    LEFT JOIN product_ingredients pi ON p.id = pi.product_id
    LEFT JOIN product_inventory inv ON pi.ingredient_id = inv.id
    WHERE p.id = prod_id
    GROUP BY p.id;

    -- If a product has no ingredients linked, treat it as in_stock by default (e.g., outsourced food)
    IF new_status IS NULL THEN
        SET new_status = 'in_stock';
    END IF;

    UPDATE products 
    SET stock_status = new_status
    WHERE id = prod_id;
END //

CREATE PROCEDURE IF NOT EXISTS update_all_product_stock_statuses()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE prod_id INT;
    DECLARE cur CURSOR FOR SELECT id FROM products WHERE is_active = 1;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN cur;
    update_loop: LOOP
        FETCH cur INTO prod_id;
        IF done THEN
            LEAVE update_loop;
        END IF;
        CALL update_product_stock_status(prod_id);
    END LOOP;
    CLOSE cur;
END //

CREATE TRIGGER IF NOT EXISTS after_product_rating_insert
AFTER INSERT ON product_ratings
FOR EACH ROW
BEGIN
    CALL update_product_rating(NEW.product_id);
END //

CREATE TRIGGER IF NOT EXISTS after_product_rating_update
AFTER UPDATE ON product_ratings
FOR EACH ROW
BEGIN
    CALL update_product_rating(NEW.product_id);
END //

CREATE TRIGGER IF NOT EXISTS after_product_rating_delete
AFTER DELETE ON product_ratings
FOR EACH ROW
BEGIN
    CALL update_product_rating(OLD.product_id);
END //

CREATE TRIGGER IF NOT EXISTS after_inventory_update
AFTER UPDATE ON product_inventory
FOR EACH ROW
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE prod_id INT;
    DECLARE cur CURSOR FOR 
        SELECT DISTINCT product_id 
        FROM product_ingredients 
        WHERE ingredient_id = NEW.id;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN cur;
    update_loop: LOOP
        FETCH cur INTO prod_id;
        IF done THEN
            LEAVE update_loop;
        END IF;
        CALL update_product_stock_status(prod_id);
    END LOOP;
    CLOSE cur;
END //

DELIMITER ;

-- ========================================
-- 4. INITIAL MASTER DATA INSERTS
-- ========================================

-- Admin User (password: admin123)
INSERT INTO admin_users (username, password, email, full_name) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@craftedcommune.com', 'Admin User');

-- Categories
INSERT INTO categories (name, slug, icon, display_order) VALUES
('Coffee', 'coffee', '../images/icons/coffee-icon.jpg', 1),
('Non-Coffee', 'non-coffee', '../images/icons/non-coffee-icon.jpg', 2),
('Breakfast', 'breakfast', '../images/icons/breakfast-icon.jpg', 3),
('Snacks', 'snacks', '../images/icons/snacks-icon.jpg', 4),
('Lunch', 'lunch', '../images/icons/lunch-icon.jpg', 5);

-- Products (Full Menu)
INSERT INTO products (category_id, name, price, points, image, is_recommended) VALUES
(1, 'Americano', 90.00, 9, '../images/products/coffee/americano.jpg', 0), (1, 'Cappuccino', 100.00, 10, '../images/products/coffee/cappuccino.jpg', 0),
(1, 'Caffe Latte', 100.00, 10, '../images/products/coffee/caffelatte.jpg', 0), (1, 'Crafted Coffee', 120.00, 12, '../images/products/coffee/crafted_coffee.jpg', 1),
(1, 'Trapped Souls Latte', 120.00, 12, '../images/products/coffee/trapped_souls_latte.jpg', 1), (1, 'Chocnut Latte', 120.00, 12, '../images/products/coffee/chocnut_latte.jpg', 0),
(1, 'Spanish Latte', 120.00, 12, '../images/products/coffee/spanish_latte.jpg', 0), (1, 'Vanilla Latte', 120.00, 12, '../images/products/coffee/vanilla_latte.jpg', 0),
(1, 'Caramel Latte', 120.00, 12, '../images/products/coffee/caramel_latte.jpg', 0), (1, 'Caffe Mocha', 120.00, 12, '../images/products/coffee/caffe_mocha.jpg', 0),
(1, 'Biscoff Latte', 140.00, 14, '../images/products/coffee/biscoff_latte.jpg', 0), (1, 'Peanut Butter Latte', 130.00, 13, '../images/products/coffee/peanut_butter_latte.jpg', 0),
(1, 'Coffee Cream Soda', 120.00, 12, '../images/products/coffee/coffee_cream_soda.jpg', 0), (1, 'Royal Coffee', 100.00, 10, '../images/products/coffee/royal_coffee.jpg', 0),
(1, 'Soy Latte', 130.00, 13, '../images/products/coffee/soy_latte.jpg', 0), (1, 'Espressoynana', 130.00, 13, '../images/products/coffee/espressoynana.jpg', 0),
(1, 'Panutsa Oat Latte', 150.00, 15, '../images/products/coffee/panutsa_oat_latte.jpg', 1), (1, 'Manual Brew', 180.00, 18, '../images/products/coffee/manual_brew.jpg', 0),
(2, 'Artisan\'s Chocolate', 120.00, 12, '../images/products/noncoffee/artisans_chocolate.jpg', 1), (2, 'Chocominto', 120.00, 12, '../images/products/noncoffee/chocominto.jpg', 0),
(2, 'Black Forest', 120.00, 12, '../images/products/noncoffee/black_forest.jpg', 0), (2, 'Matcha', 140.00, 14, '../images/products/noncoffee/matcha.jpg', 0),
(2, 'Earl Grey Matcha', 140.00, 14, '../images/products/noncoffee/earl_grey_matcha.jpg', 0), (2, 'Strawberry Matcha', 160.00, 16, '../images/products/noncoffee/strawberry_matcha.jpg', 0),
(2, 'Crafted Matcha', 160.00, 16, '../images/products/noncoffee/crafted_matcha.jpg', 0), (2, 'Cloud Matchanana', 180.00, 18, '../images/products/noncoffee/cloud_matchanana.jpg', 1),
(2, 'Fruit Latte', 100.00, 10, '../images/products/noncoffee/fruit_latte.jpg', 0), (2, 'Jam Fizz', 100.00, 10, '../images/products/noncoffee/jam_fizz.jpg', 0),
(2, 'Crafted Butter Beer', 120.00, 12, '../images/products/noncoffee/crafted_butter_beer.jpg', 1), (2, 'Loose Tea', 100.00, 10, '../images/products/noncoffee/loose_tea.jpg', 0),
(2, 'Peach Jasmine Tea', 100.00, 10, '../images/products/noncoffee/peach_jasmine_tea.jpg', 1), (2, 'Strawberry Hibiscus Tea', 100.00, 10, '../images/products/noncoffee/strawberry_hibiscus_tea.jpg', 0),
(2, 'Chocolate Earl', 100.00, 10, '../images/products/noncoffee/chocolate_earl.jpg', 0),
(3, 'Plain Waffle', 100.00, 10, '../images/products/breakfast/plain_waffle.jpg', 0), (3, 'Croffle', 140.00, 14, '../images/products/breakfast/croffle.jpg', 0),
(3, 'French\'s Toast', 80.00, 8, '../images/products/breakfast/frenchs_toast.jpg', 0), (3, 'Big Breakfast', 250.00, 25, '../images/products/breakfast/big_breakfast.jpg', 1),
(4, 'Nachos', 150.00, 15, '../images/products/snacks/nachos.jpg', 0), (4, 'Fries', 120.00, 12, '../images/products/snacks/fries.jpg', 0),
(4, 'Hungarian Sausage', 80.00, 8, '../images/products/snacks/hungarian_sausage.jpg', 0), (4, 'Sriracha Egg Sammie', 80.00, 8, '../images/products/snacks/sriracha_egg_sammie.jpg', 0),
(5, 'Lunch Bowl', 80.00, 8, '../images/products/lunch/lunch_bowl.jpg', 0);

-- System Settings
INSERT INTO settings (setting_key, setting_value, description) VALUES
('site_name', 'Crafted Commune', 'Website name'), ('points_ratio', '10', 'How many pesos equal 1 point (₱10 = 1 point)'),
('tax_rate', '0', 'Tax rate percentage'), ('currency_symbol', '₱', 'Currency symbol'),
('carousel_autoplay', '1', 'Enable carousel autoplay (1=yes, 0=no)'), ('carousel_interval', '5000', 'Carousel autoplay interval in milliseconds');

-- REVISED INVENTORY (Initial Stock) - Includes new food ingredients
INSERT INTO product_inventory (name, stock, category, low_stock_threshold, unit) VALUES
('20oz Cup', 500, 'cups', 50, 'pieces'), ('Espresso Shot', 200, 'coffee', 20, 'shots'),
('Milk', 5000, 'dairy', 500, 'ml'), ('Matcha Powder', 200, 'powder', 20, 'grams'),
('Chocolate Syrup', 1000, 'syrups', 100, 'ml'), ('Whipped Cream', 500, 'dairy', 50, 'servings'),
('Ice', 1000, 'ice', 100, 'scoops'), ('Sugar', 5000, 'dry goods', 500, 'grams'),
('Vanilla Syrup', 800, 'syrups', 80, 'ml'), ('Caramel Syrup', 800, 'syrups', 80, 'ml'),
('Eggs', 150, 'raw foods', 20, 'pieces'), ('Bread', 100, 'raw foods', 10, 'slices'),
('Hotdog', 150, 'raw foods', 15, 'pieces'), ('Coffee Beans', 2000, 'coffee', 200, 'grams'),
-- NEW FOOD INGREDIENTS
('Food Tray', 300, 'packaging', 30, 'pieces'),
('Tortilla Chips', 2000, 'raw foods', 200, 'grams'),
('Nacho Cheese Sauce', 1000, 'sauces', 100, 'ml'),
('Salsa', 500, 'sauces', 50, 'ml'),
('Potato Fries', 3000, 'raw foods', 300, 'grams'),
('Rice', 5000, 'raw foods', 500, 'grams'),
('Meat/Protein', 2000, 'raw foods', 200, 'grams')
ON DUPLICATE KEY UPDATE stock = VALUES(stock), category = VALUES(category);

-- Analytics (Initialize)
INSERT INTO product_analytics (product_id, view_count, add_to_cart_count, purchase_count)
SELECT id, 0, 0, 0 FROM products;

-- ========================================
-- 5. FRESH START: CLEANUP & ALL PRODUCT LINKING
-- ========================================

-- STEP 5.1: CLEAN SLATE
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE product_ingredients; -- Clear all previous links
UPDATE products SET stock_status = 'in_stock';
SET FOREIGN_KEY_CHECKS = 1;

-- STEP 5.2: LINK ALL PRODUCTS TO INGREDIENTS

-- COFFEE CATEGORY (18 products) - Kept as provided by user
-- 1. Americano = Cup + Espresso + Ice
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Americano'), (SELECT id FROM product_inventory WHERE name = '20oz Cup'), 1),
((SELECT id FROM products WHERE name = 'Americano'), (SELECT id FROM product_inventory WHERE name = 'Espresso Shot'), 2),
((SELECT id FROM products WHERE name = 'Americano'), (SELECT id FROM product_inventory WHERE name = 'Ice'), 1);

-- 2. Cappuccino = Cup + Espresso + Milk
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Cappuccino'), (SELECT id FROM product_inventory WHERE name = '20oz Cup'), 1),
((SELECT id FROM products WHERE name = 'Cappuccino'), (SELECT id FROM product_inventory WHERE name = 'Espresso Shot'), 2),
((SELECT id FROM products WHERE name = 'Cappuccino'), (SELECT id FROM product_inventory WHERE name = 'Milk'), 200);

-- 3. Caffe Latte = Cup + Espresso + Milk
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Caffe Latte'), (SELECT id FROM product_inventory WHERE name = '20oz Cup'), 1),
((SELECT id FROM products WHERE name = 'Caffe Latte'), (SELECT id FROM product_inventory WHERE name = 'Espresso Shot'), 2),
((SELECT id FROM products WHERE name = 'Caffe Latte'), (SELECT id FROM product_inventory WHERE name = 'Milk'), 250);

-- 4. Crafted Coffee = Cup + Espresso + Milk + Ice
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Crafted Coffee'), (SELECT id FROM product_inventory WHERE name = '20oz Cup'), 1),
((SELECT id FROM products WHERE name = 'Crafted Coffee'), (SELECT id FROM product_inventory WHERE name = 'Espresso Shot'), 2),
((SELECT id FROM products WHERE name = 'Crafted Coffee'), (SELECT id FROM product_inventory WHERE name = 'Milk'), 200),
((SELECT id FROM products WHERE name = 'Crafted Coffee'), (SELECT id FROM product_inventory WHERE name = 'Ice'), 1);

-- 5. Trapped Souls Latte = Cup + Espresso + Milk + Chocolate
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Trapped Souls Latte'), (SELECT id FROM product_inventory WHERE name = '20oz Cup'), 1),
((SELECT id FROM products WHERE name = 'Trapped Souls Latte'), (SELECT id FROM product_inventory WHERE name = 'Espresso Shot'), 2),
((SELECT id FROM products WHERE name = 'Trapped Souls Latte'), (SELECT id FROM product_inventory WHERE name = 'Milk'), 250),
((SELECT id FROM products WHERE name = 'Trapped Souls Latte'), (SELECT id FROM product_inventory WHERE name = 'Chocolate Syrup'), 30);

-- 6. Chocnut Latte = Cup + Espresso + Milk + Chocolate
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Chocnut Latte'), (SELECT id FROM product_inventory WHERE name = '20oz Cup'), 1),
((SELECT id FROM products WHERE name = 'Chocnut Latte'), (SELECT id FROM product_inventory WHERE name = 'Espresso Shot'), 2),
((SELECT id FROM products WHERE name = 'Chocnut Latte'), (SELECT id FROM product_inventory WHERE name = 'Milk'), 250),
((SELECT id FROM products WHERE name = 'Chocnut Latte'), (SELECT id FROM product_inventory WHERE name = 'Chocolate Syrup'), 30);

-- 7. Spanish Latte = Cup + Espresso + Milk + Sugar
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Spanish Latte'), (SELECT id FROM product_inventory WHERE name = '20oz Cup'), 1),
((SELECT id FROM products WHERE name = 'Spanish Latte'), (SELECT id FROM product_inventory WHERE name = 'Espresso Shot'), 2),
((SELECT id FROM products WHERE name = 'Spanish Latte'), (SELECT id FROM product_inventory WHERE name = 'Milk'), 250),
((SELECT id FROM products WHERE name = 'Spanish Latte'), (SELECT id FROM product_inventory WHERE name = 'Sugar'), 20);

-- 8. Vanilla Latte = Cup + Espresso + Milk + Vanilla
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Vanilla Latte'), (SELECT id FROM product_inventory WHERE name = '20oz Cup'), 1),
((SELECT id FROM products WHERE name = 'Vanilla Latte'), (SELECT id FROM product_inventory WHERE name = 'Espresso Shot'), 2),
((SELECT id FROM products WHERE name = 'Vanilla Latte'), (SELECT id FROM product_inventory WHERE name = 'Milk'), 250),
((SELECT id FROM products WHERE name = 'Vanilla Latte'), (SELECT id FROM product_inventory WHERE name = 'Vanilla Syrup'), 30);

-- 9. Caramel Latte = Cup + Espresso + Milk + Caramel
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Caramel Latte'), (SELECT id FROM product_inventory WHERE name = '20oz Cup'), 1),
((SELECT id FROM products WHERE name = 'Caramel Latte'), (SELECT id FROM product_inventory WHERE name = 'Espresso Shot'), 2),
((SELECT id FROM products WHERE name = 'Caramel Latte'), (SELECT id FROM product_inventory WHERE name = 'Milk'), 250),
((SELECT id FROM products WHERE name = 'Caramel Latte'), (SELECT id FROM product_inventory WHERE name = 'Caramel Syrup'), 30);

-- 10. Caffe Mocha = Cup + Espresso + Milk + Chocolate + Whipped Cream
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Caffe Mocha'), (SELECT id FROM product_inventory WHERE name = '20oz Cup'), 1),
((SELECT id FROM products WHERE name = 'Caffe Mocha'), (SELECT id FROM product_inventory WHERE name = 'Espresso Shot'), 2),
((SELECT id FROM products WHERE name = 'Caffe Mocha'), (SELECT id FROM product_inventory WHERE name = 'Milk'), 250),
((SELECT id FROM products WHERE name = 'Caffe Mocha'), (SELECT id FROM product_inventory WHERE name = 'Chocolate Syrup'), 30),
((SELECT id FROM products WHERE name = 'Caffe Mocha'), (SELECT id FROM product_inventory WHERE name = 'Whipped Cream'), 1);

-- 11. Biscoff Latte = Cup + Espresso + Milk + Caramel
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Biscoff Latte'), (SELECT id FROM product_inventory WHERE name = '20oz Cup'), 1),
((SELECT id FROM products WHERE name = 'Biscoff Latte'), (SELECT id FROM product_inventory WHERE name = 'Espresso Shot'), 2),
((SELECT id FROM products WHERE name = 'Biscoff Latte'), (SELECT id FROM product_inventory WHERE name = 'Milk'), 250),
((SELECT id FROM products WHERE name = 'Biscoff Latte'), (SELECT id FROM product_inventory WHERE name = 'Caramel Syrup'), 30);

-- 12. Peanut Butter Latte = Cup + Espresso + Milk
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Peanut Butter Latte'), (SELECT id FROM product_inventory WHERE name = '20oz Cup'), 1),
((SELECT id FROM products WHERE name = 'Peanut Butter Latte'), (SELECT id FROM product_inventory WHERE name = 'Espresso Shot'), 2),
((SELECT id FROM products WHERE name = 'Peanut Butter Latte'), (SELECT id FROM product_inventory WHERE name = 'Milk'), 250);

-- 13. Coffee Cream Soda = Cup + Espresso + Ice
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Coffee Cream Soda'), (SELECT id FROM product_inventory WHERE name = '20oz Cup'), 1),
((SELECT id FROM products WHERE name = 'Coffee Cream Soda'), (SELECT id FROM product_inventory WHERE name = 'Espresso Shot'), 2),
((SELECT id FROM products WHERE name = 'Coffee Cream Soda'), (SELECT id FROM product_inventory WHERE name = 'Ice'), 1);

-- 14. Royal Coffee = Cup + Coffee Beans + Ice
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Royal Coffee'), (SELECT id FROM product_inventory WHERE name = '20oz Cup'), 1),
((SELECT id FROM products WHERE name = 'Royal Coffee'), (SELECT id FROM product_inventory WHERE name = 'Coffee Beans'), 30),
((SELECT id FROM products WHERE name = 'Royal Coffee'), (SELECT id FROM product_inventory WHERE name = 'Ice'), 1);

-- 15. Soy Latte = Cup + Espresso + Milk
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Soy Latte'), (SELECT id FROM product_inventory WHERE name = '20oz Cup'), 1),
((SELECT id FROM products WHERE name = 'Soy Latte'), (SELECT id FROM product_inventory WHERE name = 'Espresso Shot'), 2),
((SELECT id FROM products WHERE name = 'Soy Latte'), (SELECT id FROM product_inventory WHERE name = 'Milk'), 250);

-- 16. Espressoynana = Cup + Espresso + Milk
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Espressoynana'), (SELECT id FROM product_inventory WHERE name = '20oz Cup'), 1),
((SELECT id FROM products WHERE name = 'Espressoynana'), (SELECT id FROM product_inventory WHERE name = 'Espresso Shot'), 2),
((SELECT id FROM products WHERE name = 'Espressoynana'), (SELECT id FROM product_inventory WHERE name = 'Milk'), 250);

-- 17. Panutsa Oat Latte = Cup + Espresso + Milk + Caramel
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Panutsa Oat Latte'), (SELECT id FROM product_inventory WHERE name = '20oz Cup'), 1),
((SELECT id FROM products WHERE name = 'Panutsa Oat Latte'), (SELECT id FROM product_inventory WHERE name = 'Espresso Shot'), 2),
((SELECT id FROM products WHERE name = 'Panutsa Oat Latte'), (SELECT id FROM product_inventory WHERE name = 'Milk'), 250),
((SELECT id FROM products WHERE name = 'Panutsa Oat Latte'), (SELECT id FROM product_inventory WHERE name = 'Caramel Syrup'), 30);

-- 18. Manual Brew = Cup + Coffee Beans
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Manual Brew'), (SELECT id FROM product_inventory WHERE name = '20oz Cup'), 1),
((SELECT id FROM products WHERE name = 'Manual Brew'), (SELECT id FROM product_inventory WHERE name = 'Coffee Beans'), 50);

-- NON-COFFEE CATEGORY (15 products) - Kept as provided by user
-- 19. Artisan's Chocolate = Cup + Chocolate + Milk + Whipped Cream
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Artisan\'s Chocolate'), (SELECT id FROM product_inventory WHERE name = '20oz Cup'), 1),
((SELECT id FROM products WHERE name = 'Artisan\'s Chocolate'), (SELECT id FROM product_inventory WHERE name = 'Chocolate Syrup'), 50),
((SELECT id FROM products WHERE name = 'Artisan\'s Chocolate'), (SELECT id FROM product_inventory WHERE name = 'Milk'), 250),
((SELECT id FROM products WHERE name = 'Artisan\'s Chocolate'), (SELECT id FROM product_inventory WHERE name = 'Whipped Cream'), 1);

-- 20. Chocominto = Cup + Chocolate + Milk + Ice
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Chocominto'), (SELECT id FROM product_inventory WHERE name = '20oz Cup'), 1),
((SELECT id FROM products WHERE name = 'Chocominto'), (SELECT id FROM product_inventory WHERE name = 'Chocolate Syrup'), 40),
((SELECT id FROM products WHERE name = 'Chocominto'), (SELECT id FROM product_inventory WHERE name = 'Milk'), 250),
((SELECT id FROM products WHERE name = 'Chocominto'), (SELECT id FROM product_inventory WHERE name = 'Ice'), 1);

-- 21. Black Forest = Cup + Chocolate + Milk + Whipped Cream
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Black Forest'), (SELECT id FROM product_inventory WHERE name = '20oz Cup'), 1),
((SELECT id FROM products WHERE name = 'Black Forest'), (SELECT id FROM product_inventory WHERE name = 'Chocolate Syrup'), 40),
((SELECT id FROM products WHERE name = 'Black Forest'), (SELECT id FROM product_inventory WHERE name = 'Milk'), 250),
((SELECT id FROM products WHERE name = 'Black Forest'), (SELECT id FROM product_inventory WHERE name = 'Whipped Cream'), 1);

-- 22. Matcha = Cup + Matcha Powder + Milk + Ice
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Matcha'), (SELECT id FROM product_inventory WHERE name = '20oz Cup'), 1),
((SELECT id FROM products WHERE name = 'Matcha'), (SELECT id FROM product_inventory WHERE name = 'Matcha Powder'), 15),
((SELECT id FROM products WHERE name = 'Matcha'), (SELECT id FROM product_inventory WHERE name = 'Milk'), 250),
((SELECT id FROM products WHERE name = 'Matcha'), (SELECT id FROM product_inventory WHERE name = 'Ice'), 1);

-- 23. Earl Grey Matcha = Cup + Matcha Powder + Milk
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Earl Grey Matcha'), (SELECT id FROM product_inventory WHERE name = '20oz Cup'), 1),
((SELECT id FROM products WHERE name = 'Earl Grey Matcha'), (SELECT id FROM product_inventory WHERE name = 'Matcha Powder'), 15),
((SELECT id FROM products WHERE name = 'Earl Grey Matcha'), (SELECT id FROM product_inventory WHERE name = 'Milk'), 250);

-- 24. Strawberry Matcha = Cup + Matcha Powder + Milk + Sugar
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Strawberry Matcha'), (SELECT id FROM product_inventory WHERE name = '20oz Cup'), 1),
((SELECT id FROM products WHERE name = 'Strawberry Matcha'), (SELECT id FROM product_inventory WHERE name = 'Matcha Powder'), 15),
((SELECT id FROM products WHERE name = 'Strawberry Matcha'), (SELECT id FROM product_inventory WHERE name = 'Milk'), 250),
((SELECT id FROM products WHERE name = 'Strawberry Matcha'), (SELECT id FROM product_inventory WHERE name = 'Sugar'), 20);

-- 25. Crafted Matcha = Cup + Matcha Powder + Milk + Whipped Cream
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Crafted Matcha'), (SELECT id FROM product_inventory WHERE name = '20oz Cup'), 1),
((SELECT id FROM products WHERE name = 'Crafted Matcha'), (SELECT id FROM product_inventory WHERE name = 'Matcha Powder'), 15),
((SELECT id FROM products WHERE name = 'Crafted Matcha'), (SELECT id FROM product_inventory WHERE name = 'Milk'), 250),
((SELECT id FROM products WHERE name = 'Crafted Matcha'), (SELECT id FROM product_inventory WHERE name = 'Whipped Cream'), 1);

-- 26. Cloud Matchanana = Cup + Matcha Powder + Milk + Whipped Cream
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Cloud Matchanana'), (SELECT id FROM product_inventory WHERE name = '20oz Cup'), 1),
((SELECT id FROM products WHERE name = 'Cloud Matchanana'), (SELECT id FROM product_inventory WHERE name = 'Matcha Powder'), 20),
((SELECT id FROM products WHERE name = 'Cloud Matchanana'), (SELECT id FROM product_inventory WHERE name = 'Milk'), 250),
((SELECT id FROM products WHERE name = 'Cloud Matchanana'), (SELECT id FROM product_inventory WHERE name = 'Whipped Cream'), 1);

-- 27. Fruit Latte = Cup + Milk + Sugar + Ice
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Fruit Latte'), (SELECT id FROM product_inventory WHERE name = '20oz Cup'), 1),
((SELECT id FROM products WHERE name = 'Fruit Latte'), (SELECT id FROM product_inventory WHERE name = 'Milk'), 250),
((SELECT id FROM products WHERE name = 'Fruit Latte'), (SELECT id FROM product_inventory WHERE name = 'Sugar'), 20),
((SELECT id FROM products WHERE name = 'Fruit Latte'), (SELECT id FROM product_inventory WHERE name = 'Ice'), 1);

-- 28. Jam Fizz = Cup + Sugar + Ice
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Jam Fizz'), (SELECT id FROM product_inventory WHERE name = '20oz Cup'), 1),
((SELECT id FROM products WHERE name = 'Jam Fizz'), (SELECT id FROM product_inventory WHERE name = 'Sugar'), 30),
((SELECT id FROM products WHERE name = 'Jam Fizz'), (SELECT id FROM product_inventory WHERE name = 'Ice'), 1);

-- 29. Crafted Butter Beer = Cup + Caramel + Milk + Whipped Cream
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Crafted Butter Beer'), (SELECT id FROM product_inventory WHERE name = '20oz Cup'), 1),
((SELECT id FROM products WHERE name = 'Crafted Butter Beer'), (SELECT id FROM product_inventory WHERE name = 'Caramel Syrup'), 40),
((SELECT id FROM products WHERE name = 'Crafted Butter Beer'), (SELECT id FROM product_inventory WHERE name = 'Milk'), 250),
((SELECT id FROM products WHERE name = 'Crafted Butter Beer'), (SELECT id FROM product_inventory WHERE name = 'Whipped Cream'), 1);

-- 30-33. Tea drinks = Cup + Sugar (simplified)
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Loose Tea'), (SELECT id FROM product_inventory WHERE name = '20oz Cup'), 1),
((SELECT id FROM products WHERE name = 'Loose Tea'), (SELECT id FROM product_inventory WHERE name = 'Sugar'), 10);

INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Peach Jasmine Tea'), (SELECT id FROM product_inventory WHERE name = '20oz Cup'), 1),
((SELECT id FROM products WHERE name = 'Peach Jasmine Tea'), (SELECT id FROM product_inventory WHERE name = 'Sugar'), 10);

INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Strawberry Hibiscus Tea'), (SELECT id FROM product_inventory WHERE name = '20oz Cup'), 1),
((SELECT id FROM products WHERE name = 'Strawberry Hibiscus Tea'), (SELECT id FROM product_inventory WHERE name = 'Sugar'), 10);

INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Chocolate Earl'), (SELECT id FROM product_inventory WHERE name = '20oz Cup'), 1),
((SELECT id FROM products WHERE name = 'Chocolate Earl'), (SELECT id FROM product_inventory WHERE name = 'Chocolate Syrup'), 20),
((SELECT id FROM products WHERE name = 'Chocolate Earl'), (SELECT id FROM product_inventory WHERE name = 'Sugar'), 10);

-- BREAKFAST CATEGORY (4 products) - REVISED with Food Tray
-- 34. Plain Waffle = Eggs + Milk + Sugar + Food Tray
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Plain Waffle'), (SELECT id FROM product_inventory WHERE name = 'Eggs'), 1),
((SELECT id FROM products WHERE name = 'Plain Waffle'), (SELECT id FROM product_inventory WHERE name = 'Milk'), 50),
((SELECT id FROM products WHERE name = 'Plain Waffle'), (SELECT id FROM product_inventory WHERE name = 'Sugar'), 20),
((SELECT id FROM products WHERE name = 'Plain Waffle'), (SELECT id FROM product_inventory WHERE name = 'Food Tray'), 1);

-- 35. Croffle = Eggs + Milk + Sugar + Food Tray
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Croffle'), (SELECT id FROM product_inventory WHERE name = 'Eggs'), 1),
((SELECT id FROM products WHERE name = 'Croffle'), (SELECT id FROM product_inventory WHERE name = 'Milk'), 50),
((SELECT id FROM products WHERE name = 'Croffle'), (SELECT id FROM product_inventory WHERE name = 'Sugar'), 20),
((SELECT id FROM products WHERE name = 'Croffle'), (SELECT id FROM product_inventory WHERE name = 'Food Tray'), 1);

-- 36. French's Toast = Bread + Eggs + Milk + Sugar + Food Tray
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'French\'s Toast'), (SELECT id FROM product_inventory WHERE name = 'Bread'), 2),
((SELECT id FROM products WHERE name = 'French\'s Toast'), (SELECT id FROM product_inventory WHERE name = 'Eggs'), 1),
((SELECT id FROM products WHERE name = 'French\'s Toast'), (SELECT id FROM product_inventory WHERE name = 'Milk'), 30),
((SELECT id FROM products WHERE name = 'French\'s Toast'), (SELECT id FROM product_inventory WHERE name = 'Sugar'), 10),
((SELECT id FROM products WHERE name = 'French\'s Toast'), (SELECT id FROM product_inventory WHERE name = 'Food Tray'), 1);

-- 37. Big Breakfast = Eggs + Bread + Hotdog + Food Tray
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Big Breakfast'), (SELECT id FROM product_inventory WHERE name = 'Eggs'), 2),
((SELECT id FROM products WHERE name = 'Big Breakfast'), (SELECT id FROM product_inventory WHERE name = 'Bread'), 2),
((SELECT id FROM products WHERE name = 'Big Breakfast'), (SELECT id FROM product_inventory WHERE name = 'Hotdog'), 1),
((SELECT id FROM products WHERE name = 'Big Breakfast'), (SELECT id FROM product_inventory WHERE name = 'Food Tray'), 1);

-- SNACKS CATEGORY (4 products) - REVISED with proper ingredients
-- 38. Nachos = Tortilla Chips + Nacho Cheese Sauce + Salsa + Food Tray
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Nachos'), (SELECT id FROM product_inventory WHERE name = 'Tortilla Chips'), 200),
((SELECT id FROM products WHERE name = 'Nachos'), (SELECT id FROM product_inventory WHERE name = 'Nacho Cheese Sauce'), 50),
((SELECT id FROM products WHERE name = 'Nachos'), (SELECT id FROM product_inventory WHERE name = 'Salsa'), 30),
((SELECT id FROM products WHERE name = 'Nachos'), (SELECT id FROM product_inventory WHERE name = 'Food Tray'), 1);

-- 39. Fries = Potato Fries + Food Tray
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Fries'), (SELECT id FROM product_inventory WHERE name = 'Potato Fries'), 250),
((SELECT id FROM products WHERE name = 'Fries'), (SELECT id FROM product_inventory WHERE name = 'Food Tray'), 1);

-- 40. Hungarian Sausage = Hotdog + Bread + Food Tray
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Hungarian Sausage'), (SELECT id FROM product_inventory WHERE name = 'Hotdog'), 1),
((SELECT id FROM products WHERE name = 'Hungarian Sausage'), (SELECT id FROM product_inventory WHERE name = 'Bread'), 1),
((SELECT id FROM products WHERE name = 'Hungarian Sausage'), (SELECT id FROM product_inventory WHERE name = 'Food Tray'), 1);

-- 41. Sriracha Egg Sammie = Bread + Eggs + Food Tray
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Sriracha Egg Sammie'), (SELECT id FROM product_inventory WHERE name = 'Bread'), 2),
((SELECT id FROM products WHERE name = 'Sriracha Egg Sammie'), (SELECT id FROM product_inventory WHERE name = 'Eggs'), 1),
((SELECT id FROM products WHERE name = 'Sriracha Egg Sammie'), (SELECT id FROM product_inventory WHERE name = 'Food Tray'), 1);

-- LUNCH CATEGORY (1 product) - REVISED with proper ingredients
-- 42. Lunch Bowl = Rice + Meat/Protein + Food Tray
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Lunch Bowl'), (SELECT id FROM product_inventory WHERE name = 'Rice'), 200),
((SELECT id FROM products WHERE name = 'Lunch Bowl'), (SELECT id FROM product_inventory WHERE name = 'Meat/Protein'), 150),
((SELECT id FROM products WHERE name = 'Lunch Bowl'), (SELECT id FROM product_inventory WHERE name = 'Food Tray'), 1);


-- STEP 5.3: UPDATE ALL STOCK STATUSES
CALL update_all_product_stock_statuses();

-- ========================================
-- 6. VERIFICATION REPORT
-- ========================================

SELECT '========================================' as '';
SELECT 'FRESH START DATABASE - SETUP COMPLETE!' as 'STATUS';
SELECT '========================================' as '';

SELECT 
    'Products Linked to Ingredients' as 'METRIC',
    COUNT(DISTINCT product_id) as 'VALUE'
FROM product_ingredients
UNION ALL
SELECT 
    'Total Ingredient Mappings' as 'METRIC',
    COUNT(*) as 'VALUE'
FROM product_ingredients
UNION ALL
SELECT 
    'Products In Stock' as 'METRIC',
    COUNT(*) as 'VALUE'
FROM products
WHERE stock_status = 'in_stock'
UNION ALL
SELECT 
    'Products Out of Stock' as 'METRIC',
    COUNT(*) as 'VALUE'
FROM products
WHERE stock_status = 'out_of_stock';

SELECT '========================================' as '';
SELECT '✅ Database is ready for use!' as 'RESULT';
SELECT '========================================' as '';

-- Show sample of products to confirm new ingredient linking
SELECT 
    p.name as 'Product',
    p.stock_status as 'Current Status',
    GROUP_CONCAT(
        CONCAT(inv.name, ' (', pi.quantity_needed, ' ', inv.unit, ')')
        ORDER BY inv.name
        SEPARATOR ', '
    ) as 'Ingredients List'
FROM products p
LEFT JOIN product_ingredients pi ON p.id = pi.product_id
LEFT JOIN product_inventory inv ON pi.ingredient_id = inv.id
WHERE p.name IN ('Lunch Bowl', 'Nachos', 'Fries', 'Caffe Latte')
GROUP BY p.id, p.name, p.stock_status;

-- =======================================================
-- SALES CUTOFF SYSTEM - DATABASE TABLES
-- Add these tables to your existing database
-- =======================================================

USE crafted_commune;

-- Sales Cutoffs Table
CREATE TABLE IF NOT EXISTS sales_cutoffs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cutoff_date DATE NOT NULL,
    cutoff_time TIME NOT NULL,
    total_orders INT NOT NULL DEFAULT 0,
    total_sales DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    total_points INT NOT NULL DEFAULT 0,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_cutoff_date (cutoff_date),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cutoff Orders Junction Table
CREATE TABLE IF NOT EXISTS cutoff_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cutoff_id INT NOT NULL,
    order_id INT NOT NULL,
    cutoff_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cutoff_id) REFERENCES sales_cutoffs(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    UNIQUE KEY unique_order_cutoff (order_id, cutoff_id),
    INDEX idx_cutoff_id (cutoff_id),
    INDEX idx_order_id (order_id),
    INDEX idx_cutoff_date (cutoff_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =======================================================
-- VERIFICATION QUERIES
-- =======================================================

-- Check if tables were created successfully
SHOW TABLES LIKE '%cutoff%';

-- Verify table structure
DESCRIBE sales_cutoffs;
DESCRIBE cutoff_orders;

-- Test query to see if everything works
SELECT 
    'Cutoff System Tables Created Successfully!' as Status,
    COUNT(*) as ExistingCutoffs
FROM sales_cutoffs;