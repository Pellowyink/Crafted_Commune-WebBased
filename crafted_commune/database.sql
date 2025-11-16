-- ========================================
-- Crafted Commune Café - Database Schema
-- (Updated with Title Case Product Names)
-- ========================================

-- Create Database
CREATE DATABASE IF NOT EXISTS crafted_commune;
USE crafted_commune;

-- ========================================
-- Admin Users Table
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

-- Insert default admin (password: admin123)
-- Password is hashed using PHP password_hash()
INSERT INTO admin_users (username, password, email, full_name) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@craftedcommune.com', 'Admin User');

-- ========================================
-- Categories Table
-- ========================================
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(50) UNIQUE NOT NULL,
    icon VARCHAR(255),
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert categories from the menu
INSERT INTO categories (name, slug, icon, display_order) VALUES
('Coffee', 'coffee', '../images/icons/coffee-icon.jpg', 1),
('Non-Coffee', 'non-coffee', '../images/icons/non-coffee-icon.jpg', 2),
('Breakfast', 'breakfast', '../images/icons/breakfast-icon.jpg', 3),
('Snacks', 'snacks', '../images/icons/snacks-icon.jpg', 4),
('Lunch', 'lunch', '../images/icons/lunch-icon.jpg', 5);

-- ========================================
-- Products Table
-- Inserted with full menu data (Title Case Names)
-- ========================================
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    points INT NOT NULL,
    image VARCHAR(255),
    is_recommended TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    stock_quantity INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Insert the full Crafted Commune menu products with Title Case names
INSERT INTO products (category_id, name, price, points, image, is_recommended) VALUES

-- Category 1: Coffee
(1, 'Americano', 90.00, 9, '../images/products/coffee/americano.jpg', 0),
(1, 'Cappuccino', 100.00, 10, '../images/products/coffee/cappuccino.jpg', 0),
(1, 'Caffe Latte', 100.00, 10, '../images/products/coffee/caffelatte.jpg', 0),
(1, 'Crafted Coffee', 120.00, 12, '../images/products/coffee/crafted_coffee.jpg', 1),
(1, 'Trapped Souls Latte', 120.00, 12, '../images/products/coffee/trapped_souls_latte.jpg', 1),
(1, 'Chocnut Latte', 120.00, 12, '../images/products/coffee/chocnut_latte.jpg', 0),
(1, 'Spanish Latte', 120.00, 12, '../images/products/coffee/spanish_latte.jpg', 0),
(1, 'Vanilla Latte', 120.00, 12, '../images/products/coffee/vanilla_latte.jpg', 0),
(1, 'Caramel Latte', 120.00, 12, '../images/products/coffee/caramel_latte.jpg', 0),
(1, 'Caffe Mocha', 120.00, 12, '../images/products/coffee/caffe_mocha.jpg', 0),
(1, 'Biscoff Latte', 140.00, 14, '../images/products/coffee/biscoff_latte.jpg', 0),
(1, 'Peanut Butter Latte', 130.00, 13, '../images/products/coffee/peanut_butter_latte.jpg', 0),
(1, 'Coffee Cream Soda', 120.00, 12, '../images/products/coffee/coffee_cream_soda.jpg', 0),
(1, 'Royal Coffee', 100.00, 10, '../images/products/coffee/royal_coffee.jpg', 0),
(1, 'Soy Latte', 130.00, 13, '../images/products/coffee/soy_latte.jpg', 0),
(1, 'Espressoynana', 130.00, 13, '../images/products/coffee/espressoynana.jpg', 0),
(1, 'Panutsa Oat Latte', 150.00, 15, '../images/products/coffee/panutsa_oat_latte.jpg', 1),
(1, 'Manual Brew', 180.00, 18, '../images/products/coffee/manual_brew.jpg', 0),

-- Category 2: Non-Coffee
(2, 'Artisan\'s Chocolate', 120.00, 12, '../images/products/noncoffee/artisans_chocolate.jpg', 1),
(2, 'Chocominto', 120.00, 12, '../images/products/noncoffee/chocominto.jpg', 0),
(2, 'Black Forest', 120.00, 12, '../images/products/noncoffee/black_forest.jpg', 0),
(2, 'Matcha', 140.00, 14, '../images/products/noncoffee/matcha.jpg', 0),
(2, 'Earl Grey Matcha', 140.00, 14, '../images/products/noncoffee/earl_grey_matcha.jpg', 0),
(2, 'Strawberry Matcha', 160.00, 16, '../images/products/noncoffee/strawberry_matcha.jpg', 0),
(2, 'Crafted Matcha', 160.00, 16, '../images/products/noncoffee/crafted_matcha.jpg', 0),
(2, 'Cloud Matchanana', 180.00, 18, '../images/products/noncoffee/cloud_matchanana.jpg', 1),
(2, 'Fruit Latte', 100.00, 10, '../images/products/noncoffee/fruit_latte.jpg', 0),
(2, 'Jam Fizz', 100.00, 10, '../images/products/noncoffee/jam_fizz.jpg', 0),
(2, 'Crafted Butter Beer', 120.00, 12, '../images/products/noncoffee/crafted_butter_beer.jpg', 1),
(2, 'Loose Tea', 100.00, 10, '../images/products/noncoffee/loose_tea.jpg', 0),
(2, 'Peach Jasmine Tea', 100.00, 10, '../images/products/noncoffee/peach_jasmine_tea.jpg', 1),
(2, 'Strawberry Hibiscus Tea', 100.00, 10, '../images/products/noncoffee/strawberry_hibiscus_tea.jpg', 0),
(2, 'Chocolate Earl', 100.00, 10, '../images/products/noncoffee/chocolate_earl.jpg', 0),

-- Category 3: Breakfast
(3, 'Plain Waffle', 100.00, 10, '../images/products/breakfast/plain_waffle.jpg', 0),
(3, 'Croffle', 140.00, 14, '../images/products/breakfast/croffle.jpg', 0),
(3, 'French\'s Toast', 80.00, 8, '../images/products/breakfast/frenchs_toast.jpg', 0),
(3, 'Big Breakfast', 250.00, 25, '../images/products/breakfast/big_breakfast.jpg', 1),

-- Category 4: Snacks
(4, 'Nachos', 150.00, 15, '../images/products/snacks/nachos.jpg', 0),
(4, 'Fries', 120.00, 12, '../images/products/snacks/fries.jpg', 0),
(4, 'Hungarian Sausage', 80.00, 8, '../images/products/snacks/hungarian_sausage.jpg', 0),
(4, 'Sriracha Egg Sammie', 80.00, 8, '../images/products/snacks/sriracha_egg_sammie.jpg', 0),

-- Category 5: Lunch
(5, 'Lunch Bowl', 80.00, 8, '../images/products/lunch/lunch_bowl.jpg', 0);

-- ========================================
-- Orders Table
-- ========================================
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    total_points INT NOT NULL,
    payment_method VARCHAR(50) DEFAULT 'cash',
    order_status ENUM('pending', 'completed', 'cancelled') DEFAULT 'completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL
);

-- ========================================
-- Order Items Table
-- ========================================
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

-- ========================================
-- Product Views/Analytics Table
-- ========================================
CREATE TABLE IF NOT EXISTS product_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    view_count INT DEFAULT 0,
    add_to_cart_count INT DEFAULT 0,
    purchase_count INT DEFAULT 0,
    last_viewed TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Initialize analytics for all products
INSERT INTO product_analytics (product_id, view_count, add_to_cart_count, purchase_count)
SELECT id, 0, 0, 0 FROM products;

-- ========================================
-- System Settings Table
-- ========================================
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default settings
INSERT INTO settings (setting_key, setting_value, description) VALUES
('site_name', 'Crafted Commune', 'Website name'),
('points_ratio', '10', 'How many pesos equal 1 point (₱10 = 1 point)'),
('tax_rate', '0', 'Tax rate percentage'),
('currency_symbol', '₱', 'Currency symbol'),
('carousel_autoplay', '1', 'Enable carousel autoplay (1=yes, 0=no)'),
('carousel_interval', '5000', 'Carousel autoplay interval in milliseconds');

-- ========================================
-- Activity Log Table
-- ========================================
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
-- Useful Views for Reports
-- ========================================

-- View: Best Selling Products
CREATE OR REPLACE VIEW v_best_selling_products AS
SELECT 
    p.id,
    p.name,
    c.name as category,
    COUNT(oi.id) as times_ordered,
    SUM(oi.quantity) as total_quantity_sold,
    SUM(oi.subtotal) as total_revenue,
    p.price,
    p.is_recommended
FROM products p
LEFT JOIN order_items oi ON p.id = oi.product_id
LEFT JOIN categories c ON p.category_id = c.id
GROUP BY p.id, p.name, c.name, p.price, p.is_recommended
ORDER BY total_quantity_sold DESC;

-- View: Daily Sales Summary
CREATE OR REPLACE VIEW v_daily_sales AS
SELECT 
    DATE(created_at) as sale_date,
    COUNT(id) as total_orders,
    SUM(total_amount) as total_revenue,
    SUM(total_points) as total_points,
    AVG(total_amount) as average_order_value
FROM orders
WHERE order_status = 'completed'
GROUP BY DATE(created_at)
ORDER BY sale_date DESC;

-- View: Category Performance
CREATE OR REPLACE VIEW v_category_performance AS
SELECT 
    c.id,
    c.name as category_name,
    COUNT(DISTINCT p.id) as product_count,
    COUNT(oi.id) as times_ordered,
    SUM(oi.quantity) as total_items_sold,
    SUM(oi.subtotal) as total_revenue
FROM categories c
LEFT JOIN products p ON c.id = p.category_id
LEFT JOIN order_items oi ON p.id = oi.product_id
GROUP BY c.id, c.name
ORDER BY total_revenue DESC;

-- ========================================
-- Indexes for Performance
-- ========================================
CREATE INDEX idx_orders_created_at ON orders(created_at);
CREATE INDEX idx_orders_status ON orders(order_status);
CREATE INDEX idx_order_items_order_id ON order_items(order_id);
CREATE INDEX idx_order_items_product_id ON order_items(product_id);
CREATE INDEX idx_products_category_id ON products(category_id);
CREATE INDEX idx_products_active ON products(is_active);
CREATE INDEX idx_activity_log_admin_id ON activity_log(admin_id);
CREATE INDEX idx_activity_log_created_at ON activity_log(created_at);

-- ========================================
-- Loyalty Members Table
-- Add this to your database.sql or run separately
-- ========================================

CREATE TABLE IF NOT EXISTS loyalty_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    points INT DEFAULT 0,
    total_purchases DECIMAL(10, 2) DEFAULT 0.00,
    total_orders INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_purchase TIMESTAMP NULL,
    is_active TINYINT(1) DEFAULT 1
);

-- Create index for faster email lookups
CREATE INDEX idx_loyalty_email ON loyalty_members(email);

-- -- Optional: Insert sample loyalty members for testing
-- --INSERT INTO loyalty_members (name, email, points, total_purchases, total_orders) VALUES
-- ('John Doe', 'john.doe@example.com', 150, 1500.00, 12),
-- ('Jane Smith', 'jane.smith@example.com', 250, 2500.00, 18),
-- ('Test Customer', 'test@test.com', 50, 500.00, 5);

-- ========================================
-- Loyalty Transactions Log (Optional but Recommended)
-- Track point earning/redemption history
-- ========================================

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

CREATE INDEX idx_loyalty_trans_member ON loyalty_transactions(member_id);
CREATE INDEX idx_loyalty_trans_order ON loyalty_transactions(order_id);

ALTER TABLE loyalty_members 
ADD COLUMN IF NOT EXISTS expiration_date DATE NULL 
COMMENT 'Membership expiration date (NULL = no expiration)';

-- Verify the column was added
DESCRIBE loyalty_members;

-- Optional: Update some test members with expiration dates
-- UPDATE loyalty_members SET expiration_date = DATE_ADD(CURDATE(), INTERVAL 1 YEAR) WHERE id = 1;
-- UPDATE loyalty_members SET expiration_date = DATE_SUB(CURDATE(), INTERVAL 1 MONTH) WHERE id = 2;

-- ========================================
-- Product Rating System Database
-- For rating individual products purchased
-- ========================================

-- Step 1: Add loyalty_member_id to orders (if not exists)
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS loyalty_member_id INT NULL 
AFTER total_points;

ALTER TABLE orders 
ADD CONSTRAINT fk_orders_loyalty_member 
FOREIGN KEY (loyalty_member_id) 
REFERENCES loyalty_members(id) 
ON DELETE SET NULL;

-- Step 2: Create product_ratings table
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
    UNIQUE KEY unique_rating (member_id, order_item_id),
    INDEX idx_product (product_id),
    INDEX idx_member (member_id),
    INDEX idx_rating (rating)
) COMMENT='Individual product ratings from customers';

-- Step 3: Create rating_links table (stores unique codes for each order)
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
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_unique_code (unique_code),
    INDEX idx_status (status),
    INDEX idx_member (member_id)
) COMMENT='Unique rating links sent to customers';

-- Step 4: Add average_rating to products table
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS average_rating DECIMAL(3,2) DEFAULT 0.00 
AFTER points;

ALTER TABLE products 
ADD COLUMN IF NOT EXISTS rating_count INT DEFAULT 0 
AFTER average_rating;

-- Step 5: Create view for product rating statistics
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

-- Step 6: Create stored procedure to update product ratings
DELIMITER //

CREATE PROCEDURE IF NOT EXISTS update_product_rating(IN prod_id INT)
BEGIN
    DECLARE avg_rating DECIMAL(3,2);
    DECLARE rating_cnt INT;
    
    -- Calculate average rating and count
    SELECT 
        COALESCE(AVG(rating), 0),
        COUNT(*)
    INTO avg_rating, rating_cnt
    FROM product_ratings
    WHERE product_id = prod_id;
    
    -- Update product table
    UPDATE products
    SET average_rating = avg_rating,
        rating_count = rating_cnt,
        is_recommended = IF(avg_rating >= 4.0 AND rating_cnt >= 5, 1, is_recommended)
    WHERE id = prod_id;
END //

DELIMITER ;

-- Step 7: Create trigger to auto-update ratings when new rating added
DELIMITER //

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

DELIMITER ;

-- Verify tables created
SELECT 'product_ratings' as table_name, COUNT(*) as row_count FROM product_ratings
UNION ALL
SELECT 'rating_links', COUNT(*) FROM rating_links;

-- ========================================
-- Milestone Achievements
-- Tracks when a member hits a points milestone
-- ========================================

CREATE TABLE IF NOT EXISTS milestone_achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    milestone_type VARCHAR(50) NOT NULL,
    milestone_value INT NOT NULL,
    notified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Ensure a member can only achieve a specific milestone once
    UNIQUE KEY unique_milestone (member_id, milestone_type, milestone_value),

    -- Foreign Key to the loyalty_members table
    FOREIGN KEY (member_id) REFERENCES loyalty_members(id) ON DELETE CASCADE
);

-- Add indexes for faster lookup
CREATE INDEX idx_milestone_member_id ON milestone_achievements(member_id);
