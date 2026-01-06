USE electrohub_db;

-- ========================================
-- Seed base vendors (IDs match existing project)
-- Passwords here are plaintext demo values; replace with hashes in production.
INSERT INTO vendors (id, name, email, password, city, category, status) VALUES
('V001', 'TechWorld', 'techworld@electrohub.demo', 'vendor123', 'Mumbai', 'laptop', 'active'),
('V002', 'Prime Electronics', 'prime@electrohub.demo', 'vendor123', 'Delhi', 'laptop', 'active'),
('V003', 'Bangalore Systems', 'bangalore@electrohub.demo', 'vendor123', 'Bangalore', 'laptop', 'active'),
('V004', 'Mumbai Computers', 'mumbaicomputers@electrohub.demo', 'vendor123', 'Mumbai', 'laptop', 'active'),
('V005', 'MobileMart', 'mobilemart@electrohub.demo', 'vendor123', 'Hyderabad', 'phone', 'active'),
('V006', 'Ahmedabad Mobiles', 'ahm@electrohub.demo', 'vendor123', 'Ahmedabad', 'phone', 'active'),
('V007', 'Chennai Digital', 'chennai@electrohub.demo', 'vendor123', 'Chennai', 'phone', 'active'),
('V008', 'Accessory Zone', 'accessory@electrohub.demo', 'vendor123', 'Pune', 'accessory', 'active'),
('V009', 'SoundLab', 'soundlab@electrohub.demo', 'vendor123', 'Kolkata', 'accessory', 'active')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- ========================================
-- Seed base products (IDs match original arrays)
INSERT INTO products (id, name, category, brand, price, vendor_id, rating, image, price_3m, price_6m, price_12m) VALUES
(1, 'Dell XPS 15', 'laptop', 'Dell', 185000, 'V001', 4.70, 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=900&q=80', 179000, 189000, 195000),
(2, 'MacBook Air M2', 'laptop', 'Apple', 135000, 'V002', 4.90, 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=900&q=80', 132000, 138000, 145000),
(3, 'HP Pavilion 14', 'laptop', 'HP', 72000, 'V003', 4.30, 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=900&q=80', 69999, 74999, 78000),
(4, 'Lenovo IdeaPad Slim 5', 'laptop', 'Lenovo', 68000, 'V004', 4.40, 'https://images.unsplash.com/photo-1511385348-a52b4a160dc2?auto=format&fit=crop&w=900&q=80', 65999, 69999, 73000),
(5, 'Samsung Galaxy S24', 'phone', 'Samsung', 89999, 'V005', 4.80, 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=900&q=80', 87999, 91999, 94999),
(6, 'iPhone 15 Pro', 'phone', 'Apple', 129999, 'V002', 4.90, 'https://images.unsplash.com/photo-1510557880182-3d4d3cba35a5?auto=format&fit=crop&w=900&q=80', 127999, 134999, 139999),
(7, 'OnePlus 12R 5G', 'phone', 'OnePlus', 42999, 'V006', 4.50, 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=900&q=80', 41999, 44999, 45999),
(8, 'Redmi Note 13 Pro', 'phone', 'Xiaomi', 27999, 'V007', 4.20, 'https://images.unsplash.com/photo-1480694313141-fce5e697ee25?auto=format&fit=crop&w=900&q=80', 26999, 28999, 30999),
(9, 'Logitech MX Master 3S Mouse', 'accessory', 'Logitech', 7999, 'V008', 4.60, 'https://images.unsplash.com/photo-1527814050087-3793815479db?auto=format&fit=crop&w=900&q=80', 7599, 8499, 8999),
(10, 'Sony WH-1000XM5 Headphones', 'accessory', 'Sony', 29999, 'V009', 4.80, 'https://images.unsplash.com/photo-1519666213635-f953892fa780?auto=format&fit=crop&w=900&q=80', 28999, 30999, 32999)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- ========================================
-- Seed static coupons from project copy
INSERT INTO coupons (code, discount_rate, is_active) VALUES
('SASTANASHA', 0.15, 1),
('JALDIWALAAAYA', 0.25, 1),
('UTHA LE RE', 0.50, 1)
ON DUPLICATE KEY UPDATE discount_rate = VALUES(discount_rate);

-- ========================================
-- Demo customer & admin viewing credentials
INSERT INTO customers (id, name, email, password, phone, status) VALUES
('C1001', 'Demo Customer', 'demo@customer.com', '$2y$10$5J6F84QEZAa5t2S1YQ/Rm.TdzSiv9bfm6cy6M4u0sHpRCbh6inZL2', '+91-90000-00000', 'active')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- NOTE: password above is bcrypt for \"demo123\". Use PHP password_hash for new entries.

-- ========================================
-- Demo notifications
INSERT INTO notifications (customer_id, message) VALUES
('C1001', '🎉 Welcome to ElectroHub! Enjoy exclusive launch discounts.'),
('C1001', 'Order ORD1001 has been shipped (demo message).')
ON DUPLICATE KEY UPDATE message = VALUES(message);

-- ========================================
-- Demo flash sale (optional)
INSERT INTO flash_sales (category, discount_rate, is_active) VALUES
('laptop', 0.10, 1)
ON DUPLICATE KEY UPDATE discount_rate = VALUES(discount_rate), is_active = VALUES(is_active);
