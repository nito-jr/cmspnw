-- ============================================================
-- CMSP Full Database Setup
-- Run this ONCE in phpMyAdmin > SQL tab
-- ============================================================

-- Database is pre-created on InfinityFree, no need to CREATE or USE here.

CREATE TABLE IF NOT EXISTS users (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    name                 VARCHAR(100) NOT NULL,
    email                VARCHAR(100) UNIQUE NOT NULL,
    phone                VARCHAR(20),
    address              TEXT,
    password             VARCHAR(255) NOT NULL,
    role                 ENUM('admin','operator','member') DEFAULT 'member',
    profession           VARCHAR(50),
    school               VARCHAR(100),
    year_graduation      INT,
    year_registration    INT,
    license_number       VARCHAR(50),
    photo                VARCHAR(255) DEFAULT 'default.jpg',
    status               ENUM('pending','approved','rejected') DEFAULT 'pending',
    balance_due          DECIMAL(10,2) DEFAULT 0.00,
    platform_charge      DECIMAL(10,2) DEFAULT 4000.00,
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS payments (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    amount       DECIMAL(10,2) NOT NULL,
    payment_type ENUM('application','dues','platform_charge') NOT NULL DEFAULT 'dues',
    proof_image  VARCHAR(255),
    status       ENUM('pending','approved','rejected') DEFAULT 'pending',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- ============================================================
-- If upgrading an EXISTING database, run these instead:
-- ============================================================
-- ALTER TABLE users ADD COLUMN IF NOT EXISTS school VARCHAR(100) AFTER profession;
-- ALTER TABLE users ADD COLUMN IF NOT EXISTS platform_charge DECIMAL(10,2) DEFAULT 4000.00;
-- ALTER TABLE payments MODIFY COLUMN payment_type ENUM('application','dues','platform_charge') NOT NULL DEFAULT 'dues';
-- ============================================================
