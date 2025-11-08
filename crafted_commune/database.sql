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
('Coffee', 'coffee', 'images/icons/coffee-icon.png', 1),
('Non-Coffee', 'non-coffee', 'images/icons/non-coffee-icon.png', 2),
('Breakfast', 'breakfast', 'images/icons/breakfast-icon.png', 3),
('Snacks', 'snacks', 'images/icons/snacks-icon.png', 4),
('Lunch', 'lunch', 'images/icons/lunch-icon.png', 5);

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
(1, 'Americano', 90.00, 9, 'images/americano.jpg', 0),
(1, 'Cappuccino', 100.00, 10, 'images/cappuccino.jpg', 0),
(1, 'Caffe Latte', 100.00, 10, 'images/caffelatte.jpg', 0),
(1, 'Crafted Coffee', 120.00, 12, 'images/crafted_coffee.jpg', 1),
(1, 'Trapped Souls Latte', 120.00, 12, 'images/trapped_souls_latte.jpg', 1),
(1, 'Chocnut Latte', 120.00, 12, 'images/chocnut_latte.jpg', 0),
(1, 'Spanish Latte', 120.00, 12, 'images/spanish_latte.jpg', 0),
(1, 'Vanilla Latte', 120.00, 12, 'images/vanilla_latte.jpg', 0),
(1, 'Caramel Latte', 120.00, 12, 'images/caramel_latte.jpg', 0),
(1, 'Caffe Mocha', 120.00, 12, 'images/caffe_mocha.jpg', 0),
(1, 'Biscoff Latte', 140.00, 14, 'images/biscoff_latte.jpg', 0),
(1, 'Peanut Butter Latte', 130.00, 13, 'images/peanut_butter_latte.jpg', 0),
(1, 'Coffee Cream Soda', 120.00, 12, 'images/coffee_cream_soda.jpg', 0),
(1, 'Royal Coffee', 100.00, 10, 'images/royal_coffee.jpg', 0),
(1, 'Soy Latte', 130.00, 13, 'images/soy_latte.jpg', 0),
(1, 'Espressoynanana', 130.00, 13, 'images/espressoynanana.jpg', 0),
(1, 'Panutsa Oat Latte', 150.00, 15, 'images/panutsa_oat_latte.jpg', 1),
(1, 'Manual Brew', 180.00, 18, 'images/manual_brew.jpg', 0),

-- Category 2: Non-Coffee
(2, 'Artisan\'s Chocolate', 120.00, 12, 'images/artisans_chocolate.jpg', 1),
(2, 'Chocomini', 120.00, 12, 'images/chocomini.jpg', 0),
(2, 'Black Forest', 120.00, 12, 'images/black_forest.jpg', 0),
(2, 'Matcha', 140.00, 14, 'images/matcha.jpg', 0),
(2, 'Earl Grey Matcha', 140.00, 14, 'images/earl_grey_matcha.jpg', 0),
(2, 'Strawberry Matcha', 160.00, 16, 'images/strawberry_matcha.jpg', 0),
(2, 'Crafted Matcha', 160.00, 16, 'images/crafted_matcha.jpg', 0),
(2, 'Cloud Matchanana', 180.00, 18, 'images/cloud_matchanana.jpg', 1),
(2, 'Fruit Latte', 100.00, 10, 'images/fruit_latte.jpg', 0),
(2, 'Jam Fizz', 100.00, 10, 'images/jam_fizz.jpg', 0),
(2, 'Crafted Butter Beer', 120.00, 12, 'images/crafted_butter_beer.jpg', 1),
(2, 'Loose Tea', 100.00, 10, 'images/loose_tea.jpg', 0),
(2, 'Peach Jasmine Tea', 100.00, 10, 'images/peach_jasmine_tea.jpg', 1),
(2, 'Strawberry Hibiscus Tea', 100.00, 10, 'images/strawberry_hibiscus_tea.jpg', 0),
(2, 'Chocolate Earl', 100.00, 10, 'images/chocolate_earl.jpg', 0),

-- Category 3: Breakfast
(3, 'Plain Waffle', 100.00, 10, 'images/plain_waffle.jpg', 0),
(3, 'Croffle', 140.00, 14, 'images/croffle.jpg', 0),
(3, 'French\'s Toast', 80.00, 8, 'images/frenchs_toast.jpg', 0),
(3, 'Big Breakfast', 250.00, 25, 'images/big_breakfast.jpg', 1),

-- Category 4: Snacks
(4, 'Nachos', 150.00, 15, 'images/nachos.jpg', 0),
(4, 'Fries', 120.00, 12, 'images/fries.jpg', 0),
(4, 'Hungarian Sausage', 80.00, 8, 'images/hungarian_sausage.jpg', 0),
(4, 'Sriracha Egg Sammie', 80.00, 8, 'images/sriracha_egg_sammie.jpg', 0),

-- Category 5: Lunch
(5, 'Lunch Bowl', 80.00, 8, 'images/lunch_bowl.jpg', 0);

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