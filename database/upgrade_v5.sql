-- v5: category images + a custom homepage hero slider (independent of
-- "featured" products), plus admin management for both.

ALTER TABLE categories ADD COLUMN IF NOT EXISTS image VARCHAR(255) NULL AFTER accent_color;

CREATE TABLE IF NOT EXISTS hero_slides (
  id INT AUTO_INCREMENT PRIMARY KEY,
  image VARCHAR(255) NOT NULL,
  headline VARCHAR(150) NULL,
  subtext VARCHAR(255) NULL,
  link_url VARCHAR(255) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
