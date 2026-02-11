CREATE DATABASE IF NOT EXISTS tea_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tea_shop;

-- Users (admin / staff)
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  full_name VARCHAR(150),
  role ENUM('admin','staff') NOT NULL DEFAULT 'staff',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Inventory
CREATE TABLE inventory (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  description TEXT,
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  stock_qty INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
);

-- Orders
CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  customer_name VARCHAR(150),
  total_amount DECIMAL(10,2) NOT NULL,
  status ENUM('pending','completed','cancelled') NOT NULL DEFAULT 'completed',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
ALTER TABLE orders 
ADD COLUMN order_type ENUM('Dine-in', 'Take-out') DEFAULT 'Dine-in' AFTER customer_name,
ADD COLUMN takeout_fee DECIMAL(10,2) DEFAULT 0 AFTER order_type,
ADD COLUMN amount_paid DECIMAL(10,2) DEFAULT 0 AFTER total_amount,
ADD COLUMN change_due DECIMAL(10,2) DEFAULT 0 AFTER amount_paid;


-- Order items
CREATE TABLE order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  inventory_id INT NOT NULL,
  qty INT NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  line_total DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (inventory_id) REFERENCES inventory(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  message TEXT NOT NULL,
  is_read BOOLEAN DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Optional: Default admin account (username: admin / password: admin123)
INSERT INTO users (username, password, full_name, role)
VALUES (
  'admin',
  '$2y$10$vSP0jgTYTN5OQUwiuZiMIeitObhl6SIdf5PPPheRDZOSpaGEojNZW', -- hash for 'admin123'
  'Administrator',
  'admin'
);

-- Sample inventory items
INSERT INTO inventory (name, description, price, stock_qty) VALUES
('Classic Milk Tea', 'Black tea + milk', 99.00, 30),
('Taro Milk Tea', 'Sweet taro flavor', 110.00, 20),
('Matcha Latte', 'Green tea with milk', 130.00, 15);
