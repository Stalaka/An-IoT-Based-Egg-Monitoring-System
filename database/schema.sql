-- Egg Storage Monitoring System - database schema
-- Matches proposal section 3.4: Users, Stock registers, Sales logs, Telemetry logs.
CREATE DATABASE IF NOT EXISTS egg_monitor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE egg_monitor;

CREATE TABLE IF NOT EXISTS users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  full_name     VARCHAR(100) NOT NULL,
  email         VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,           -- a hash, never the plain password
  role          ENUM('admin','vendor','supplier') NOT NULL DEFAULT 'vendor',
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS stock_registers (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  batch_ref       VARCHAR(50) NOT NULL,
  crates_in_stock INT NOT NULL DEFAULT 0,
  price_per_crate DECIMAL(10,2) NOT NULL,
  low_stock_level INT NOT NULL DEFAULT 5,        -- alert when crates <= this number
  received_date   DATE NOT NULL,
  updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sales_logs (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  stock_id     INT NOT NULL,
  user_id      INT NOT NULL,
  crates_sold  INT NOT NULL,
  unit_price   DECIMAL(10,2) NOT NULL,
  total        DECIMAL(12,2) NOT NULL,
  sold_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sales_stock FOREIGN KEY (stock_id) REFERENCES stock_registers(id) ON DELETE RESTRICT,
  CONSTRAINT fk_sales_user  FOREIGN KEY (user_id)  REFERENCES users(id)           ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS telemetry_logs (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  device_id   VARCHAR(50) NOT NULL,
  temperature DECIMAL(5,2) NOT NULL,
  humidity    DECIMAL(5,2) NOT NULL,
  air_quality INT NOT NULL,                      -- raw MQ-135 value (0-1023)
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- Starter data so the demo is not empty.
-- Default admin login (CHANGE THE PASSWORD after the demo):
--   email: admin@egg.local     password: Admin@123
INSERT INTO users (full_name, email, password_hash, role) VALUES
('System Admin', 'admin@egg.local', '$2y$10$K/pA8ItcfJLsDZVHB6Un.emXJDgygJ/HrI.Zfc8/miSV3RekkVhcu', 'admin');

INSERT INTO stock_registers (batch_ref, crates_in_stock, price_per_crate, low_stock_level, received_date) VALUES
('BATCH-001', 20, 450.00, 5, CURDATE()),
('BATCH-002', 6, 470.00, 5, CURDATE());
