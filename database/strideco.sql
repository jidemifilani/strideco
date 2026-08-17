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
  is_preorder TINYINT(1) NOT NULL DEFAULT 0,
  preorder_available_at DATE DEFAULT NULL,
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
  is_preorder TINYINT(1) NOT NULL DEFAULT 0,
  preorder_available_at DATE DEFAULT NULL,
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
('site_name', 'Expandable Collection'),
('accent_color', '#FF5A1F'),
('hero_eyebrow', 'New season drop'),
('hero_headline_line1', 'Every step deserves'),
('hero_headline_highlight', 'the right shoe'),
('hero_subtext', 'Expandable Collection brings you sneakers, formal, sport and casual shoes built for comfort and made to last — with fast delivery across Nigeria.'),
('hero_cta_primary_label', 'Shop Now'),
('hero_cta_secondary_label', 'Browse Sneakers'),
('contact_email', 'hello@strideco.example'),
('contact_phone', '+234 800 000 0000'),
('contact_address', 'Lagos, Nigeria'),
('social_instagram', ''),
('social_twitter', ''),
('social_tiktok', ''),
('tax_rate_percent', '0'),
('maintenance_mode', '0'),
('maintenance_message', ''),
('maintenance_reopen_at', '');

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

INSERT INTO products (category_id, name, slug, description, features, price, color, image, is_featured, status) VALUES
(3, 'Marathon Elite', 'marathon-elite', 'A featherlight racing shoe built for tempo runs and race day, with a breathable knit upper and responsive foam cushioning.', 'Lightweight breathable knit upper\nResponsive foam midsole cushioning\nReinforced heel for a secure lockdown\nFlexible outsole for a natural running motion\nBuilt for tempo runs and race-day pace', 34000.00, 'Crimson Red', '88415609c27e636f.jpg', 1, 'active'),
(3, 'PowerLift Trainer', 'powerlift-trainer', 'A stability-focused training shoe with a wide platform base built to handle heavy lifts, sprints, and everything in between.', 'Wide stable base for heavy lifting\nDurable mesh and synthetic overlay upper\nCushioned heel with firm forefoot support\nReinforced toe cap for durability\nVersatile for gym, HIIT, and cross-training', 36500.00, 'Volt Green / Black', 'f4968a6bd1486535.jpg', 0, 'active'),
(1, 'Retro Wave 90', 'retro-wave-90', 'A chunky retro-inspired sneaker with contrast paneling and a bold silhouette that stands out in any lineup.', 'Chunky retro-inspired silhouette\nMulti-panel design in a bold colorway\nCushioned midsole for all-day comfort\nPadded collar for a snug fit\nStatement style for streetwear looks', 33500.00, 'Multicolor Pastel', '91c7c23ecb566cbe.jpg', 1, 'active'),
(4, 'Suede Drift Boot', 'suede-drift-boot', 'A classic suede desert boot with a relaxed silhouette and crepe-style sole, built for everyday wear from weekday to weekend.', 'Genuine suede leather upper\nTwo-eyelet lace-up desert boot design\nCushioned footbed for all-day comfort\nDurable crepe-style outsole\nPairs easily with jeans or chinos', 30000.00, 'Olive Suede', 'b14c86ff6a02e627.jpg', 0, 'active'),
(2, 'Chelsea Noir Boot', 'chelsea-noir-boot', 'A sleek black leather ankle boot with a side-zip closure, dressy enough for the office and rugged enough for the commute.', 'Genuine leather upper with a matte black finish\nSmooth side-zip closure for easy on/off\nStacked block heel\nCushioned insole for comfort\nPairs well with suits or smart-casual outfits', 41000.00, 'Black', '8d612b426eba8285.jpg', 0, 'active'),
(1, 'Crimson Pulse High-Top', 'crimson-pulse-high-top', 'A bold all-red high-top with a padded collar and premium textured upper, made to be the loudest pair in the room.', 'Premium textured synthetic upper\nHigh-top silhouette with padded collar for ankle support\nRubber traction outsole\nReinforced lace loops for a snug fit\nBold monochrome colorway', 39500.00, 'Red', 'd104d27f506daa48.jpg', 1, 'active'),
(1, 'Black Governors Crocodile Belgian Sneakers', 'black-governors-crocodile-belgian-sneakers', 'Every trendy man''s dream — the Black Governors pairs a bold crocodile-embossed finish with sharp Belgian-style tailoring. Built from high-quality materials designed to go the distance, it is the kind of pair that only gets better with wear.', 'Crocodile-embossed leather upper\nHandcrafted Belgian-style silhouette\nBuilt for durability and everyday wear\nEasy to clean and low maintenance\nRetains its shine and finish for years\nSmart-casual versatility — dress up or down', 28000.00, 'Black', '41589f082b258237.jpg', 1, 'active');

-- 30 additional products across all categories, added 2026-08-17
INSERT INTO products (category_id, name, slug, description, features, price, color, image, is_featured, status) VALUES
(1, 'Arctic White Classic', 'arctic-white-classic', 'A crisp, all-white high-top that pairs with absolutely everything in your closet.', 'Premium leather upper\nCushioned collar for all-day comfort\nDurable rubber outsole\nClassic high-top silhouette\nEasy to keep clean', 27000.00, 'White', 'e58afce6be3efc76.jpg', 0, 'active'),
(1, 'Cloud Nine Court Classic', 'cloud-nine-court-classic', 'Minimalist white court sneaker with a clean, versatile silhouette.', 'Premium leather upper\nPerforated side detailing\nCushioned footbed\nClean minimalist design\nGrippy rubber sole', 26500.00, 'White', '00ababbcf93aa839.jpg', 0, 'active'),
(1, 'Volt Flash Runner', 'volt-flash-runner', 'Bold multicolor runner that turns heads from the first step.', 'Vibrant multicolor upper\nResponsive cushioned midsole\nBreathable mesh panels\nReinforced toe cap\nLightweight everyday build', 29500.00, 'Red/Yellow', 'd7e335daa7ad69a9.jpg', 0, 'active'),
(1, 'Sunset Pop High-Top', 'sunset-pop-high-top', 'Playful pastel high-top with standout color-blocking.', 'Eye-catching color-block upper\nPadded ankle collar\nDurable canvas-leather mix\nFlexible sole for natural movement\nStands out in any crowd', 28500.00, 'Pink/Yellow', 'cc1b2f64d9ed23b6.jpg', 0, 'active'),
(1, 'Storm Grey High-Top', 'storm-grey-high-top', 'Sleek monochrome high-top with a sharp, understated edge.', 'Two-tone leather upper\nPadded high-top collar\nShock-absorbing sole\nReinforced stitching throughout\nVersatile everyday styling', 31500.00, 'Black/White', '96eab0b62baf9dc2.jpg', 0, 'active'),
(1, 'Teal Canvas High', 'teal-canvas-high', 'Bold teal canvas high-top for a laid-back, street-ready look.', 'Durable canvas upper\nBold teal colorway\nVulcanized rubber sole\nClassic lace-up closure\nEasy to pair with denim', 24500.00, 'Teal', '930259a4783f169e.jpg', 0, 'active'),
(1, 'Golden Hour Sneaker', 'golden-hour-sneaker', 'Warm-toned sneaker with a standout sunset color pairing.', 'Genuine leather upper\nContrast-stitched detailing\nCushioned insole\nDurable rubber outsole\nStatement colorway', 30000.00, 'Brown/Yellow', '9a6343c455bb242d.jpg', 0, 'active'),
(1, 'Shadow Tech Runner', 'shadow-tech-runner', 'All-black technical runner built for low-key performance.', 'Sleek all-black upper\nLightweight technical build\nShock-absorbing sole\nReflective detailing\nBuilt for daily mileage', 33500.00, 'Black', 'b56a5a72962dc1d4.jpg', 0, 'active'),
(2, 'Classic Wingtip Oxford', 'classic-wingtip-oxford', 'Traditional wingtip brogue detailing on a timeless oxford silhouette.', 'Full-grain leather upper\nHand-finished brogue detailing\nLeather-lined interior\nStacked leather heel\nClassic lace-up closure', 46000.00, 'Brown', 'db17418c4b03f5b2.jpg', 0, 'active'),
(2, 'Brogue Heritage', 'brogue-heritage', 'Heritage-inspired brogue oxford, built for boardrooms and beyond.', 'Premium leather construction\nPerforated brogue detailing\nCushioned leather insole\nDurable leather sole\nTimeless formal silhouette', 43000.00, 'Brown', '8418ad350cf4eb26.jpg', 0, 'active'),
(2, 'Midnight Oxford Tuxedo', 'midnight-oxford-tuxedo', 'Polished black oxford for black-tie occasions.', 'Smooth polished leather upper\nSleek formal silhouette\nLeather-lined comfort\nSturdy leather sole\nBuilt for special occasions', 44500.00, 'Black', '8fe919183280c417.jpg', 0, 'active'),
(2, 'Executive Cap-Toe', 'executive-cap-toe', 'Sharp cap-toe oxford designed for the corner office.', 'Structured cap-toe design\nGenuine leather upper\nCushioned arch support\nDurable formal outsole\nBoardroom-ready polish', 47500.00, 'Black', 'a0a9d51e219db3b4.jpg', 0, 'active'),
(2, 'Suede Horsebit Loafer', 'suede-horsebit-loafer', 'Suede loafer finished with a statement gold horsebit.', 'Soft suede upper\nGold-tone horsebit hardware\nSlip-on convenience\nCushioned footbed\nSmart-casual versatility', 39500.00, 'Tan', '8dcc61a7700ff0b3.jpg', 0, 'active'),
(2, 'Chestnut Double Monk Strap', 'chestnut-double-monk-strap', 'Chestnut-toned double monk strap with a clean, classic finish.', 'Genuine leather upper\nDouble buckle monk strap closure\nComfort-cushioned insole\nDurable leather sole\nSharp formal silhouette', 41500.00, 'Brown', '527f947430d8fe84.jpg', 0, 'active'),
(2, 'Noir Studded Loafer', 'noir-studded-loafer', 'Statement studded velvet loafer built for black-tie occasions.', 'Velvet upper with allover stud detailing\nSlip-on comfort\nCushioned footbed\nDurable sole construction\nEye-catching evening wear', 42000.00, 'Black', '1ee0e7606b5de219.jpg', 0, 'active'),
(3, 'Thunder Grip Trainer', 'thunder-grip-trainer', 'High-energy training shoe with bold multicolor accents.', 'Breathable mesh upper\nResponsive cushioned sole\nMulti-directional traction\nSecure lace-up fit\nBuilt for high-intensity training', 34500.00, 'Multicolor', '581506e3a0ddeb94.jpg', 0, 'active'),
(3, 'SpeedForm Elite', 'speedform-elite', 'Streamlined racing silhouette with a pop of orange.', 'Lightweight racing build\nOrange accent striping\nBreathable knit-mesh upper\nResponsive foam midsole\nBuilt for speed sessions', 35500.00, 'White/Orange', 'ef22562f9ea60a03.jpg', 0, 'active'),
(3, 'CourtSmash Pro', 'courtsmash-pro', 'Classic court silhouette built for game day.', 'High-top ankle support\nImpact-absorbing midsole\nDurable rubber traction\nClassic court styling\nBuilt for hardwood performance', 38000.00, 'Black/Red', '8dc0fbae6c928a69.jpg', 0, 'active'),
(3, 'TrailRush Hiker', 'trailrush-hiker', 'Rugged trail runner ready for uneven terrain.', 'Aggressive traction outsole\nWater-resistant upper\nReinforced toe protection\nCushioned trail-ready midsole\nBuilt for off-road mileage', 36000.00, 'Grey', '8fc9c9d86dbf7842.jpg', 0, 'active'),
(3, 'AeroFlex Running', 'aeroflex-running', 'Ultra-light everyday trainer for easy miles.', 'Ultra-lightweight build\nBreathable mesh upper\nFlexible responsive sole\nCushioned comfort fit\nBuilt for daily runs', 32000.00, 'White', '9cc28cab8cc8fe88.jpg', 0, 'active'),
(3, 'PulseFit Cross-Trainer', 'pulsefit-cross-trainer', 'Stable cross-trainer built for the gym floor.', 'Stable flat sole for lifting\nBreathable mesh panels\nSecure lockdown fit\nDurable toe protection\nVersatile gym-ready build', 33000.00, 'Green/Black', 'dbfb1f7de05635df.jpg', 0, 'active'),
(3, 'PowerGrip High-Top Trainer', 'powergrip-high-top-trainer', 'High-top training shoe built for stability under heavy load.', 'High-top ankle support\nHigh-grip rubber outsole\nSecure lace-up lockdown\nDurable reinforced build\nBuilt for gym and court training', 31500.00, 'Black/Orange', '9064a4f27316683f.jpg', 0, 'active'),
(4, 'Rugged Desert Boot', 'rugged-desert-boot', 'Classic suede desert boot for rugged everyday wear.', 'Genuine suede upper\nCrepe-style rubber sole\nClassic desert boot silhouette\nComfortable break-in feel\nVersatile with denim or chinos', 28000.00, 'Dark Brown', '384320315bf7c89b.jpg', 0, 'active'),
(4, 'Boat Deck Loafer', 'boat-deck-loafer', 'Classic leather deck shoe built for weekend wear.', 'Genuine leather upper\nSiped non-marking outsole\nRawhide lace detailing\nMoisture-friendly build\nClassic deck-shoe styling', 23500.00, 'Brown', '3dfa7b5591fb1761.jpg', 0, 'active'),
(4, 'Canvas Summer Slip-On', 'canvas-summer-slip-on', 'Breezy canvas slip-on built for warm-weather days.', 'Lightweight canvas upper\nEasy slip-on wear\nFlexible rubber sole\nBreathable everyday comfort\nEffortless summer styling', 19500.00, 'Grey', '887f22082e785f3b.jpg', 0, 'active'),
(4, 'Chukka Suede Classic', 'chukka-suede-classic', 'Suede chukka boot with a clean, versatile silhouette.', 'Soft suede upper\nTwo-eyelet lace-up closure\nCushioned comfort insole\nDurable rubber sole\nClassic chukka silhouette', 29000.00, 'Dark Brown', '8b2645b80e85a4a5.jpg', 0, 'active'),
(4, 'Brown Leather Casual Derby', 'brown-leather-casual-derby', 'Clean round-toe leather derby built for everyday comfort.', 'Genuine leather upper\nClassic derby lace-up\nCushioned comfort insole\nDurable outsole\nVersatile everyday styling', 27500.00, 'Brown', '8024c4f135d34133.jpg', 0, 'active'),
(4, 'Ash Grey Court Sneaker', 'ash-grey-court-sneaker', 'Clean grey canvas sneaker with a low-profile court silhouette.', 'Durable canvas upper\nContrast cream laces\nLow-profile court silhouette\nFlexible rubber sole\nBreathable everyday comfort', 22000.00, 'Grey', '71cd6de9706d7b10.jpg', 0, 'active'),
(4, 'Vintage Canvas High', 'vintage-canvas-high', 'Retro-inspired high-top with classic color-blocking.', 'Retro-inspired canvas upper\nClassic high-top silhouette\nVulcanized rubber sole\nColor-blocked detailing\nTimeless everyday style', 25000.00, 'Blue/White', 'c56326e5a49bbc76.jpg', 0, 'active'),
(4, 'Weekend Brown Lace-Up', 'weekend-brown-lace-up', 'Rugged lace-up casual shoe for easy weekend wear.', 'Durable leather upper\nRugged lace-up closure\nComfort-cushioned insole\nDurable outsole traction\nBuilt for weekend adventures', 24000.00, 'Brown', '8cbb5a94a21e15b2.jpg', 0, 'active');

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
