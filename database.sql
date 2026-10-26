-- Lawyer Website and Appointment Management System Database
-- Create database
CREATE DATABASE IF NOT EXISTS lawyer_system;
USE lawyer_system;

-- Users table (clients)
-- Email is now optional (may be NULL); phone must be unique.
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) DEFAULT NULL,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    is_blocked TINYINT(1) DEFAULT 0,
    current_session_id VARCHAR(255) NULL DEFAULT NULL,
    last_login DATETIME NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_phone (phone)
);

-- Admin users table
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    current_session_id VARCHAR(255) NULL DEFAULT NULL,
    last_login DATETIME NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Appointments table
CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    case_type VARCHAR(100) NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    message TEXT,
    notes TEXT,
    status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Documents table
CREATE TABLE IF NOT EXISTS documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    appointment_id INT,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL
);

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    status ENUM('unread', 'read') DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Payments table
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT,
    checkout_request_id VARCHAR(255) UNIQUE,
    merchant_request_id VARCHAR(255),
    amount DECIMAL(10,2) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    mpesa_receipt_number VARCHAR(100),
    status ENUM('PENDING', 'COMPLETED', 'FAILED') DEFAULT 'PENDING',
    transaction_time VARCHAR(50),
    result_code INT,
    result_desc TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL
);

-- Migration: Add M-Pesa callback columns to payments (for existing databases)
-- ALTER TABLE payments ADD COLUMN transaction_time VARCHAR(50) DEFAULT NULL AFTER status;
-- ALTER TABLE payments ADD COLUMN result_code INT DEFAULT NULL AFTER transaction_time;
-- ALTER TABLE payments ADD COLUMN result_desc TEXT DEFAULT NULL AFTER result_code;
-- ALTER TABLE payments ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- Insert admin user (username: Dennis Njenga, email: admin@justicelawfirm.com, password: 2060)
INSERT INTO admin_users (username, email, password) VALUES 
('Dennis Njenga', 'admin@justicelawfirm.com', '$2y$10$ZdhjGMEAX33SLq7LiIMUiO.5YqojNnHPE2Z3xgWxdDJGjqBgPJUwS');

-- Migration: Add email to admin_users (if missing)
-- ALTER TABLE admin_users ADD COLUMN IF NOT EXISTS email VARCHAR(100) AFTER username;
-- UPDATE admin_users SET email = 'admin@justicelawfirm.com' WHERE email IS NULL OR email = '';

-- Migration: Add notes to appointments (if missing)
-- ALTER TABLE appointments ADD COLUMN IF NOT EXISTS notes TEXT AFTER message;

-- Migration: Make email optional and enforce unique phone (for existing databases)
-- ALTER TABLE users MODIFY COLUMN email VARCHAR(100) DEFAULT NULL;
-- ALTER TABLE users DROP INDEX IF EXISTS email;
-- ALTER TABLE users ADD UNIQUE KEY IF NOT EXISTS unique_phone (phone);

-- SMS logs table for OpenSMS integration
CREATE TABLE IF NOT EXISTS sms_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_phone VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('sent', 'failed', 'pending') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Migration: single active session support (one device at a time)
-- ALTER TABLE users ADD COLUMN IF NOT EXISTS current_session_id VARCHAR(255) NULL DEFAULT NULL AFTER is_blocked;
-- ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login DATETIME NULL DEFAULT NULL AFTER current_session_id;
-- ALTER TABLE admin_users ADD COLUMN IF NOT EXISTS current_session_id VARCHAR(255) NULL DEFAULT NULL AFTER password;
-- ALTER TABLE admin_users ADD COLUMN IF NOT EXISTS last_login DATETIME NULL DEFAULT NULL AFTER current_session_id;
