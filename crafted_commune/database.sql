-- ========================================
-- Crafted Commune Café - Database Schema
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

-- Insert default categories
INSERT INTO categories (name, slug, icon, display_order) VALUES
('Coffee', 'coffee', 'images/icons/coffee-icon.png', 1),
('Latte', 'latte', 'images/icons/latte-icon.png', 2),
('Soda', 'soda', 'images/icons/soda-icon.png', 3),
('Snacks', 'snacks', 'images/icons/snacks-icon.png', 4);

-- ========================================
-- Products Table
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

-- Insert sample products
INSERT INTO products (category_id, name, price, points, image, is_recommended) VALUES
-- Coffee
(1, 'Americano', 90, 9, 'images/americano.jpg', 0),
(1, 'Cappuccino', 100, 10, 'images/cappuccino.jpg', 0),
(1, 'Caffè Latte', 100, 10, 'images/caffelatte.jpg', 0),
(1, 'Espressoyna', 130, 13, 'images/espressoyna.jpg', 0),
(1, 'Manual Brew', 180, 18, 'images/manualbrew.jpg', 1),
-- Latte
(2, 'Classic Latte', 95, 10, 'images/classiclatte.jpg', 0),
(2, 'Caramel Latte', 120, 12, 'images/caramellatte.jpg', 1),
(2, 'Vanilla Latte', 115, 12, 'images/vanillalatte.jpg', 0),
(2, 'Mocha Latte', 125, 13, 'images/mochalatte.jpg', 0),
-- Soda
(3, 'Cola', 60, 6, 'images/cola.jpg', 0),
(3, 'Lemon Soda', 65, 7, 'images/lemonsoda.jpg', 1),
(3, 'Orange Fizz', 70, 7, 'images/orangefizz.jpg', 0),
(3, 'Root Beer', 60, 6, 'images/rootbeer.jpg', 0),
-- Snacks
(4, 'Croissant', 80, 8, 'images/croissant.jpg', 0),
(4, 'Muffin', 75, 8, 'images/muffin.jpg', 1),
(4, 'Cookie', 50, 5, 'images/cookie.jpg', 0),
(4, 'Brownie', 90, 9, 'images/brownie.jpg', 0),
(4, 'Donut', 50, 5, 'images/donut.jpg', 0),
(4, 'Bagel', 85, 9, 'images/bagel.jpg', 0);

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
GROUP BY p.id
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
GROUP BY c.id
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