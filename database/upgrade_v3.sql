-- StrideCo v3 schema upgrade — product color variants, shipping zones/tax,
-- refunds, gift cards, and abandoned-cart capture.
-- Safe to re-run: uses IF NOT EXISTS / ADD COLUMN IF NOT EXISTS throughout.

USE strideco;

-- Product color variants (additive, backward-compatible): a product with no
-- rows here behaves exactly as before (single color via products.color text
-- field, sizes/stock from product_sizes). A product WITH rows here shows
-- color swatches on the product page, each with its own optional photo and
-- its own per-size stock in variant_sizes.
CREATE TABLE IF NOT EXISTS product_variants (
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

CREATE TABLE IF NOT EXISTS variant_sizes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  variant_id INT NOT NULL,
  size VARCHAR(10) NOT NULL,
  stock INT NOT NULL DEFAULT 0,
  FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE,
  UNIQUE KEY uniq_variant_size (variant_id, size)
) ENGINE=InnoDB;

ALTER TABLE order_items ADD COLUMN IF NOT EXISTS variant_id INT DEFAULT NULL AFTER product_id;
ALTER TABLE order_items ADD COLUMN IF NOT EXISTS variant_color VARCHAR(60) DEFAULT NULL AFTER variant_id;

-- Shipping zones: a delivery fee per region instead of one flat SHIPPING_FEE.
-- `states` is a comma-separated list matched (case-insensitively) against the
-- order's city/state text; the zone with is_default=1 is the catch-all used
-- when nothing matches (or when no zones are configured at all, in which
-- case the app falls back to the SHIPPING_FEE constant — see
-- resolve_shipping_fee() in includes/functions.php).
CREATE TABLE IF NOT EXISTS shipping_zones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  states TEXT DEFAULT NULL,
  fee DECIMAL(10,2) NOT NULL DEFAULT 0,
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO shipping_zones (name, states, fee, is_default, sort_order)
SELECT 'Standard Delivery', NULL, 2500.00, 1, 0
WHERE NOT EXISTS (SELECT 1 FROM shipping_zones);

ALTER TABLE orders ADD COLUMN IF NOT EXISTS tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER discount_amount;

INSERT INTO settings (setting_key, setting_value)
SELECT 'tax_rate_percent', '0'
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE setting_key = 'tax_rate_percent');

-- Refunds: extends payment_status with 'refunded' and records what was
-- refunded, when, and (if processed through Paystack) their refund
-- reference. The event itself is also logged to order_status_history like
-- every other status change, so it shows in the customer-facing timeline.
ALTER TABLE orders MODIFY COLUMN payment_status ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending';
ALTER TABLE orders ADD COLUMN IF NOT EXISTS refund_amount DECIMAL(10,2) DEFAULT NULL AFTER tax_amount;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS refund_reference VARCHAR(100) DEFAULT NULL AFTER refund_amount;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS refunded_at TIMESTAMP NULL DEFAULT NULL AFTER refund_reference;

-- Gift cards: admin-issued store-credit codes (e.g. for promotions or
-- goodwill credit), redeemable at checkout against the order total. Not
-- (yet) purchasable by customers as a product — see README.
CREATE TABLE IF NOT EXISTS gift_cards (
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

CREATE TABLE IF NOT EXISTS gift_card_redemptions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  gift_card_id INT NOT NULL,
  order_id INT NOT NULL,
  amount_used DECIMAL(10,2) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (gift_card_id) REFERENCES gift_cards(id) ON DELETE CASCADE,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

ALTER TABLE orders ADD COLUMN IF NOT EXISTS gift_card_code VARCHAR(20) DEFAULT NULL AFTER coupon_code;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS gift_card_amount DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER gift_card_code;

-- Abandoned-cart capture: once a checkout email is known (customer typed it
-- and moved on, but never finished placing the order), a snapshot of their
-- cart is saved here so a reminder email can be sent later with a one-click
-- link back to restore it. One row per email — a fresh abandonment
-- overwrites the previous snapshot and resets reminder/recovered state.
CREATE TABLE IF NOT EXISTS abandoned_carts (
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

-- Site maintenance mode (Admin → Theme & Content → Site maintenance): shows
-- a closed splash on every storefront page. See includes/header.php for the
-- enforcement — the admin panel and Paystack webhook/callback are unaffected.
INSERT INTO settings (setting_key, setting_value)
SELECT 'maintenance_mode', '0'
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE setting_key = 'maintenance_mode');

INSERT INTO settings (setting_key, setting_value)
SELECT 'maintenance_message', ''
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE setting_key = 'maintenance_message');

INSERT INTO settings (setting_key, setting_value)
SELECT 'maintenance_reopen_at', ''
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE setting_key = 'maintenance_reopen_at');
