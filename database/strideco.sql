-- StrideCo shoe store database
-- Import via phpMyAdmin, or: mysql -u root < strideco.sql

CREATE DATABASE IF NOT EXISTS strideco CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE strideco;

-- ---------------------------------------------------------------
-- Structure
-- ---------------------------------------------------------------

CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(100) NOT NULL UNIQUE,
  accent_color VARCHAR(7) NOT NULL DEFAULT '#FF6A1A'
) ENGINE=InnoDB;

CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NOT NULL,
  name VARCHAR(150) NOT NULL,
  slug VARCHAR(160) NOT NULL UNIQUE,
  description TEXT,
  features TEXT,
  price DECIMAL(10,2) NOT NULL,
  color VARCHAR(50) DEFAULT NULL,
  image VARCHAR(255) DEFAULT NULL,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id),
  INDEX idx_category (category_id),
  INDEX idx_status (status)
) ENGINE=InnoDB;

CREATE TABLE product_sizes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  size VARCHAR(10) NOT NULL,
  stock INT NOT NULL DEFAULT 0,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  UNIQUE KEY uniq_product_size (product_id, size)
) ENGINE=InnoDB;

-- Color variants (additive, backward-compatible): a product with no rows
-- here behaves exactly as a single-color product (products.color text field,
-- stock from product_sizes above). A product WITH rows here shows color
-- swatches on its product page, each with its own optional photo and its
-- own per-size stock in variant_sizes.
CREATE TABLE product_variants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  color_name VARCHAR(60) NOT NULL,
  color_hex VARCHAR(7) DEFAULT NULL,
  image VARCHAR(255) DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  INDEX idx_product (product_id)
) ENGINE=InnoDB;

CREATE TABLE variant_sizes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  variant_id INT NOT NULL,
  size VARCHAR(10) NOT NULL,
  stock INT NOT NULL DEFAULT 0,
  FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE,
  UNIQUE KEY uniq_variant_size (variant_id, size)
) ENGINE=InnoDB;

-- Gift cards: admin-issued store-credit codes (e.g. for promotions or
-- goodwill credit), redeemable at checkout against the order total. Not
-- (yet) purchasable by customers as a product — see README.
CREATE TABLE gift_cards (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(20) NOT NULL UNIQUE,
  initial_balance DECIMAL(10,2) NOT NULL,
  balance DECIMAL(10,2) NOT NULL,
  status ENUM('active','disabled') NOT NULL DEFAULT 'active',
  expires_at DATE DEFAULT NULL,
  recipient_email VARCHAR(150) DEFAULT NULL,
  note VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  phone VARCHAR(30) DEFAULT NULL,
  password_hash VARCHAR(255) NOT NULL,
  address TEXT DEFAULT NULL,
  city VARCHAR(100) DEFAULT NULL,
  failed_attempts INT NOT NULL DEFAULT 0,
  locked_until DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT DEFAULT NULL,
  order_ref VARCHAR(50) NOT NULL UNIQUE,
  customer_name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  address TEXT NOT NULL,
  city VARCHAR(100) DEFAULT NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  shipping_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
  coupon_code VARCHAR(40) DEFAULT NULL,
  discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  gift_card_code VARCHAR(20) DEFAULT NULL,
  gift_card_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  refund_amount DECIMAL(10,2) DEFAULT NULL,
  refund_reference VARCHAR(100) DEFAULT NULL,
  refunded_at TIMESTAMP NULL DEFAULT NULL,
  total DECIMAL(10,2) NOT NULL,
  payment_status ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  order_status ENUM('processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'processing',
  payment_reference VARCHAR(100) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
  INDEX idx_payment_status (payment_status),
  INDEX idx_order_ref (order_ref)
) ENGINE=InnoDB;

CREATE TABLE order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT DEFAULT NULL,
  variant_id INT DEFAULT NULL,
  variant_color VARCHAR(60) DEFAULT NULL,
  product_name VARCHAR(150) NOT NULL,
  size VARCHAR(10) DEFAULT NULL,
  quantity INT NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE gift_card_redemptions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  gift_card_id INT NOT NULL,
  order_id INT NOT NULL,
  amount_used DECIMAL(10,2) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (gift_card_id) REFERENCES gift_cards(id) ON DELETE CASCADE,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('super_admin','staff') NOT NULL DEFAULT 'staff',
  failed_attempts INT NOT NULL DEFAULT 0,
  locked_until DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE product_images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  image VARCHAR(255) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE reviews (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  customer_id INT DEFAULT NULL,
  customer_name VARCHAR(150) NOT NULL,
  rating TINYINT NOT NULL,
  comment TEXT NOT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
  INDEX idx_product_status (product_id, status)
) ENGINE=InnoDB;

CREATE TABLE wishlists (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  product_id INT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  UNIQUE KEY uniq_customer_product (customer_id, product_id)
) ENGINE=InnoDB;

CREATE TABLE coupons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(40) NOT NULL UNIQUE,
  type ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
  value DECIMAL(10,2) NOT NULL,
  min_subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
  max_uses INT DEFAULT NULL,
  used_count INT NOT NULL DEFAULT 0,
  expires_at DATE DEFAULT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE newsletter_subscribers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(150) NOT NULL UNIQUE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Abandoned-cart capture: once a checkout email is known (customer typed it
-- and moved on, but never finished placing the order), a snapshot of their
-- cart is saved here so a reminder email can be sent later with a one-click
-- link back to restore it. One row per email — a fresh abandonment
-- overwrites the previous snapshot and resets reminder/recovered state.
CREATE TABLE abandoned_carts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(150) NOT NULL,
  token VARCHAR(64) NOT NULL,
  cart_snapshot TEXT NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  reminder_sent_at TIMESTAMP NULL DEFAULT NULL,
  recovered_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_email (email),
  UNIQUE KEY uniq_token (token)
) ENGINE=InnoDB;

CREATE TABLE contact_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL,
  subject VARCHAR(200) DEFAULT NULL,
  message TEXT NOT NULL,
  status ENUM('new','read') NOT NULL DEFAULT 'new',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE order_status_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  status VARCHAR(30) NOT NULL,
  note VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  INDEX idx_order (order_id)
) ENGINE=InnoDB;

CREATE TABLE settings (
  setting_key VARCHAR(60) PRIMARY KEY,
  setting_value TEXT
) ENGINE=InnoDB;

-- Shipping zones: a delivery fee per region instead of one flat SHIPPING_FEE
-- constant. `states` is a comma-separated list matched (case-insensitively)
-- against the order's city; the zone with is_default=1 is the catch-all used
-- when nothing matches (or when this table is empty, in which case the app
-- falls back to the SHIPPING_FEE constant — see resolve_shipping_fee() in
-- includes/functions.php).
CREATE TABLE shipping_zones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  states TEXT DEFAULT NULL,
  fee DECIMAL(10,2) NOT NULL DEFAULT 0,
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- Seed data
-- ---------------------------------------------------------------

INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'StrideCo'),
('accent_color', '#FF5A1F'),
('hero_eyebrow', 'New season drop'),
('hero_headline_line1', 'Every step deserves'),
('hero_headline_highlight', 'the right shoe'),
('hero_subtext', 'StrideCo brings you sneakers, formal, sport and casual shoes built for comfort and made to last — with fast delivery across Nigeria.'),
('hero_cta_primary_label', 'Shop Now'),
('hero_cta_secondary_label', 'Browse Sneakers'),
('contact_email', 'hello@strideco.example'),
('contact_phone', '+234 800 000 0000'),
('contact_address', 'Lagos, Nigeria'),
('social_instagram', ''),
('social_twitter', ''),
('social_tiktok', ''),
('tax_rate_percent', '0');

INSERT INTO shipping_zones (name, states, fee, is_default, sort_order) VALUES
('Standard Delivery', NULL, 2500.00, 1, 0);

INSERT INTO categories (name, slug, accent_color) VALUES
('Sneakers', 'sneakers', '#FF6A1A'),
('Formal', 'formal', '#2B2420'),
('Sport', 'sport', '#1A73E8'),
('Casual', 'casual', '#3C8C5C');

-- Default admin login: username "admin", password "Admin@Stride2026"
INSERT INTO admins (username, password_hash, role) VALUES
('admin', '$2y$10$sBvDPLyybhx/vKpdS6LtcumsCLGJci3RBqx4eE58xTdNlX6r5U3vu', 'super_admin');

-- Sample discount code: 10% off orders over ₦20,000
INSERT INTO coupons (code, type, value, min_subtotal, status) VALUES
('WELCOME10', 'percent', 10.00, 20000.00, 'active');

INSERT INTO products (category_id, name, slug, description, price, color, is_featured, status) VALUES
(1, 'Air Stride Runner', 'air-stride-runner', 'A lightweight everyday runner with breathable mesh uppers and responsive cushioning built for all-day wear.', 32500.00, 'Orange / White', 1, 'active'),
(1, 'Urban Flex High-Top', 'urban-flex-high-top', 'A street-ready high-top with a padded collar and durable rubber outsole for city miles.', 28000.00, 'Black', 1, 'active'),
(1, 'Cloudwalk Knit', 'cloudwalk-knit', 'Sock-fit knit sneaker that moves with your foot, finished with a soft cloud-like midsole.', 35000.00, 'Grey', 0, 'active'),
(1, 'Retro Court Classic', 'retro-court-classic', 'A timeless low-top court silhouette with clean leather panels and a vintage rubber cup sole.', 27500.00, 'White / Red', 1, 'active'),
(2, 'Oxford Elite', 'oxford-elite', 'Hand-finished full-grain leather Oxfords for the boardroom and beyond.', 45000.00, 'Black', 1, 'active'),
(2, 'Derby Signature', 'derby-signature', 'A refined derby shoe in burnished leather with a cushioned leather sole.', 42000.00, 'Brown', 0, 'active'),
(2, 'Monk Strap Prestige', 'monk-strap-prestige', 'Double monk-strap dress shoe with a polished tan finish for standout occasions.', 48500.00, 'Tan', 0, 'active'),
(3, 'TrailBlaze Runner', 'trailblaze-runner', 'Rugged trail-ready trainer with an aggressive grip outsole and water-resistant upper.', 33000.00, 'Blue', 1, 'active'),
(3, 'SprintX Pro', 'sprintx-pro', 'Performance racing flat engineered for speed sessions and race day.', 37500.00, 'Black / Green', 0, 'active'),
(3, 'CourtDominate', 'courtdominate', 'High-traction court shoe built for quick cuts and explosive jumps.', 31000.00, 'White / Blue', 0, 'active'),
(4, 'Weekend Loafer', 'weekend-loafer', 'A relaxed slip-on loafer in soft suede for easy weekend wear.', 26000.00, 'Navy', 0, 'active'),
(4, 'Canvas Roam Low', 'canvas-roam-low', 'Classic canvas low-top with a durable vulcanized sole, built to be lived in.', 19500.00, 'Beige', 1, 'active');

-- Sizes 38-45 for every product, with varied stock levels
INSERT INTO product_sizes (product_id, size, stock)
SELECT p.id, s.size, FLOOR(3 + RAND() * 15)
FROM products p
CROSS JOIN (
  SELECT '38' AS size UNION SELECT '39' UNION SELECT '40' UNION SELECT '41'
  UNION SELECT '42' UNION SELECT '43' UNION SELECT '44' UNION SELECT '45'
) s;

-- Make one size on one product deliberately low/out of stock to demo stock states
UPDATE product_sizes SET stock = 0 WHERE product_id = 1 AND size = '38';
UPDATE product_sizes SET stock = 2 WHERE product_id = 1 AND size = '39';
