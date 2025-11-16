-- ========================================
-- CRAFTED COMMUNE CAFÉ - FRESH START DATABASE
-- Clean slate with all products linked to ingredients
-- ========================================

-- Use the database
USE crafted_commune;

-- ========================================
-- STEP 1: CLEAN SLATE - Remove old data
-- ========================================

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- Clear all transactional data
TRUNCATE TABLE loyalty_transactions;
TRUNCATE TABLE loyalty_members;
TRUNCATE TABLE order_items;
TRUNCATE TABLE orders;
TRUNCATE TABLE product_ratings;
TRUNCATE TABLE rating_links;
TRUNCATE TABLE milestone_achievements;
TRUNCATE TABLE activity_log;

-- Clear product-ingredient links (we'll rebuild them)
TRUNCATE TABLE product_ingredients;

-- Reset inventory to full stock
UPDATE product_inventory SET stock = CASE name
    WHEN '20oz Cup' THEN 500
    WHEN 'Espresso Shot' THEN 200
    WHEN 'Milk' THEN 5000
    WHEN 'Matcha Powder' THEN 200
    WHEN 'Chocolate Syrup' THEN 1000
    WHEN 'Whipped Cream' THEN 500
    WHEN 'Ice' THEN 1000
    WHEN 'Sugar' THEN 5000
    WHEN 'Vanilla Syrup' THEN 800
    WHEN 'Caramel Syrup' THEN 800
    WHEN 'Eggs' THEN 150
    WHEN 'Bread' THEN 100
    WHEN 'Hotdog' THEN 150
    WHEN 'Coffee Beans' THEN 2000
    ELSE stock
END;

-- Reset all products to in_stock
UPDATE products SET stock_status = 'in_stock';

SET FOREIGN_KEY_CHECKS = 1;

-- ========================================
-- STEP 2: LINK ALL PRODUCTS TO INGREDIENTS
-- ========================================

-- COFFEE CATEGORY (18 products)
-- Most coffee drinks use: Cup, Espresso, Milk/Ice

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

-- NON-COFFEE CATEGORY (15 products)

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

-- BREAKFAST CATEGORY (4 products)

-- 34. Plain Waffle = Eggs + Milk + Sugar
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Plain Waffle'), (SELECT id FROM product_inventory WHERE name = 'Eggs'), 1),
((SELECT id FROM products WHERE name = 'Plain Waffle'), (SELECT id FROM product_inventory WHERE name = 'Milk'), 50),
((SELECT id FROM products WHERE name = 'Plain Waffle'), (SELECT id FROM product_inventory WHERE name = 'Sugar'), 20);

-- 35. Croffle = Eggs + Milk + Sugar
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Croffle'), (SELECT id FROM product_inventory WHERE name = 'Eggs'), 1),
((SELECT id FROM products WHERE name = 'Croffle'), (SELECT id FROM product_inventory WHERE name = 'Milk'), 50),
((SELECT id FROM products WHERE name = 'Croffle'), (SELECT id FROM product_inventory WHERE name = 'Sugar'), 20);

-- 36. French's Toast = Bread + Eggs + Milk + Sugar
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'French\'s Toast'), (SELECT id FROM product_inventory WHERE name = 'Bread'), 2),
((SELECT id FROM products WHERE name = 'French\'s Toast'), (SELECT id FROM product_inventory WHERE name = 'Eggs'), 1),
((SELECT id FROM products WHERE name = 'French\'s Toast'), (SELECT id FROM product_inventory WHERE name = 'Milk'), 30),
((SELECT id FROM products WHERE name = 'French\'s Toast'), (SELECT id FROM product_inventory WHERE name = 'Sugar'), 10);

-- 37. Big Breakfast = Eggs + Bread + Hotdog
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Big Breakfast'), (SELECT id FROM product_inventory WHERE name = 'Eggs'), 2),
((SELECT id FROM products WHERE name = 'Big Breakfast'), (SELECT id FROM product_inventory WHERE name = 'Bread'), 2),
((SELECT id FROM products WHERE name = 'Big Breakfast'), (SELECT id FROM product_inventory WHERE name = 'Hotdog'), 1);

-- SNACKS CATEGORY (4 products)

-- 38. Nachos = Basic snack (no specific ingredients tracked)
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Nachos'), (SELECT id FROM product_inventory WHERE name = '20oz Cup'), 1);

-- 39. Fries = Basic snack
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Fries'), (SELECT id FROM product_inventory WHERE name = '20oz Cup'), 1);

-- 40. Hungarian Sausage = Hotdog + Bread
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Hungarian Sausage'), (SELECT id FROM product_inventory WHERE name = 'Hotdog'), 1),
((SELECT id FROM products WHERE name = 'Hungarian Sausage'), (SELECT id FROM product_inventory WHERE name = 'Bread'), 1);

-- 41. Sriracha Egg Sammie = Bread + Eggs
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Sriracha Egg Sammie'), (SELECT id FROM product_inventory WHERE name = 'Bread'), 2),
((SELECT id FROM products WHERE name = 'Sriracha Egg Sammie'), (SELECT id FROM product_inventory WHERE name = 'Eggs'), 1);

-- LUNCH CATEGORY (1 product)

-- 42. Lunch Bowl = Basic lunch
INSERT IGNORE INTO product_ingredients (product_id, ingredient_id, quantity_needed) VALUES
((SELECT id FROM products WHERE name = 'Lunch Bowl'), (SELECT id FROM product_inventory WHERE name = '20oz Cup'), 1);

-- ========================================
-- STEP 3: UPDATE ALL STOCK STATUSES
-- ========================================

CALL update_all_product_stock_statuses();

-- ========================================
-- VERIFICATION REPORT
-- ========================================

SELECT '========================================' as '';
SELECT 'FRESH START DATABASE - SETUP COMPLETE!' as 'STATUS';
SELECT '========================================' as '';

SELECT 
    'Total Products' as 'METRIC',
    COUNT(*) as 'VALUE'
FROM products;

SELECT 
    'Products Linked to Ingredients' as 'METRIC',
    COUNT(DISTINCT product_id) as 'VALUE'
FROM product_ingredients;

SELECT 
    'Total Ingredient Mappings' as 'METRIC',
    COUNT(*) as 'VALUE'
FROM product_ingredients;

SELECT 
    'Products In Stock' as 'METRIC',
    COUNT(*) as 'VALUE'
FROM products
WHERE stock_status = 'in_stock';

SELECT 
    'Products Low Stock' as 'METRIC',
    COUNT(*) as 'VALUE'
FROM products
WHERE stock_status = 'low_stock';

SELECT 
    'Products Out of Stock' as 'METRIC',
    COUNT(*) as 'VALUE'
FROM products
WHERE stock_status = 'out_of_stock';

SELECT 
    'Total Ingredients Available' as 'METRIC',
    COUNT(*) as 'VALUE'
FROM product_inventory;

SELECT 
    'Loyalty Members' as 'METRIC',
    COUNT(*) as 'VALUE'
FROM loyalty_members;

SELECT 
    'Total Orders' as 'METRIC',
    COUNT(*) as 'VALUE'
FROM orders;

SELECT '========================================' as '';
SELECT '✅ Database is ready for use!' as 'RESULT';
SELECT '========================================' as '';

-- Show sample of linked products
SELECT 
    p.name as 'Product',
    GROUP_CONCAT(
        CONCAT(inv.name, ' (', pi.quantity_needed, ' ', inv.unit, ')')
        SEPARATOR ', '
    ) as 'Ingredients'
FROM products p
LEFT JOIN product_ingredients pi ON p.id = pi.product_id
LEFT JOIN product_inventory inv ON pi.ingredient_id = inv.id
WHERE p.id <= 5
GROUP BY p.id, p.name;