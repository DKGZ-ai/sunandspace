-- Sun and Space — fresh database schema and seed data
-- Import in phpMyAdmin or: mysql -u root < schema.sql

CREATE DATABASE IF NOT EXISTS sunandspace
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE sunandspace;

CREATE TABLE users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email VARCHAR(255) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  google_id VARCHAR(255) NULL DEFAULT NULL,
  name VARCHAR(255) NOT NULL DEFAULT '',
  phone VARCHAR(50) NOT NULL DEFAULT '',
  role ENUM('admin', 'customer') NOT NULL,
  billing_address TEXT NULL,
  billing_notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email_role (email, role),
  UNIQUE KEY uq_users_google_role (google_id, role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE products (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  category VARCHAR(100) NOT NULL DEFAULT '',
  price_cents INT UNSIGNED NOT NULL,
  sale_price_cents INT UNSIGNED NULL DEFAULT NULL,
  image_path VARCHAR(255) NOT NULL DEFAULT '',
  description TEXT NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  weight_kg DECIMAL(8,2) NOT NULL DEFAULT 5.00,
  availability_status ENUM('in_stock', 'pre_order') NOT NULL DEFAULT 'in_stock',
  estimated_arrival VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE carts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_carts_user (user_id),
  CONSTRAINT fk_carts_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cart_items (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  cart_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  qty INT UNSIGNED NOT NULL DEFAULT 1,
  unit_price_cents INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_cart_product (cart_id, product_id),
  CONSTRAINT fk_cart_items_cart FOREIGN KEY (cart_id) REFERENCES carts (id) ON DELETE CASCADE,
  CONSTRAINT fk_cart_items_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE orders (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  status ENUM('pending', 'approved', 'in_progress', 'delivered') NOT NULL DEFAULT 'pending',
  tracking_number VARCHAR(100) NOT NULL DEFAULT '',
  shipping_name VARCHAR(255) NOT NULL,
  shipping_email VARCHAR(255) NOT NULL,
  shipping_phone VARCHAR(50) NOT NULL DEFAULT '',
  shipping_address TEXT NOT NULL,
  shipping_notes TEXT NOT NULL,
  shipping_province VARCHAR(100) NOT NULL DEFAULT '',
  shipping_city VARCHAR(100) NOT NULL DEFAULT '',
  shipping_cents INT UNSIGNED NOT NULL DEFAULT 0,
  subtotal_cents INT UNSIGNED NOT NULL DEFAULT 0,
  delivery_method VARCHAR(30) NOT NULL DEFAULT 'jt_nationwide',
  payment_method VARCHAR(20) NOT NULL DEFAULT 'cod',
  payment_receipt_path VARCHAR(255) NULL DEFAULT NULL,
  total_cents INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_orders_user (user_id),
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_items (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  qty INT UNSIGNED NOT NULL,
  unit_price_cents INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  KEY idx_order_items_order (order_id),
  CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
  CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE store_settings (
  setting_key VARCHAR(100) NOT NULL,
  setting_value TEXT NOT NULL,
  PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default admin + linked customer (password: changeme)
INSERT INTO users (email, password_hash, name, phone, role) VALUES
(
  'admin@sunandspace.local',
  '$2y$10$ZDEg/PwVfwxe4n1VUZ8cJu9w4xIhhPq1cWyNUQ1k.wmGDc0m8BUgy',
  'Store Admin',
  '',
  'admin'
),
(
  'admin@sunandspace.local',
  '$2y$10$ZDEg/PwVfwxe4n1VUZ8cJu9w4xIhhPq1cWyNUQ1k.wmGDc0m8BUgy',
  'Store Admin',
  '',
  'customer'
);

INSERT INTO products (
  name, category, price_cents, sale_price_cents, image_path, description,
  active, sort_order, weight_kg, availability_status, estimated_arrival
) VALUES
(
  '650W/400Wh power station with free 100W Premium Foldable Solar Panel',
  'POWER STATIONS',
  2499900,
  NULL,
  'images/product-power-station-650w.jpg',
  'High-capacity portable power station bundled with a 100W foldable solar panel.',
  1, 1, 8.00, 'in_stock', NULL
),
(
  '300W/225W portable power station with free 60W/18V premium foldable solar panel',
  'POWER STATIONS',
  1299900,
  NULL,
  'images/product-power-station-300w.jpg',
  'Compact portable power station with a 60W premium foldable solar panel.',
  1, 2, 5.00, 'in_stock', NULL
),
(
  '100W Premium Foldable Solar Panel',
  'SOLAR PANELS',
  899900,
  NULL,
  'images/product-solar-panel-100w.jpg',
  'Lightweight foldable solar panel for charging power stations and devices.',
  1, 3, 5.00, 'pre_order', 'Estimated arrival in PH: July 2nd-3rd week'
),
(
  'Complete 600W Solar Kit',
  'SOLAR KITS',
  3499900,
  NULL,
  'images/product-solar-kit-600w.jpg',
  'All-in-one solar kit with panels, inverter, and mounting accessories.',
  1, 4, 8.00, 'pre_order', 'Estimated arrival in PH: July 2nd-3rd week'
);
