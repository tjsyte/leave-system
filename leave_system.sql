-- Leave Filing and Approval System Database
-- Create Database
CREATE DATABASE IF NOT EXISTS leave_system;
USE leave_system;

-- Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('employee', 'manager') NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(100) NOT NULL,
    department VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Leave Requests Table
CREATE TABLE leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    leave_type VARCHAR(100) NOT NULL,
    date_from DATE NOT NULL,
    date_to DATE NOT NULL,
    days_requested INT NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    filed_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    manager_remarks TEXT,
    approved_by INT,
    approved_date TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Leave Balances Table
CREATE TABLE leave_balances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    leave_type VARCHAR(100) NOT NULL,
    total_earned INT DEFAULT 15,
    used INT DEFAULT 0,
    balance INT DEFAULT 15,
    year YEAR DEFAULT (YEAR(CURDATE())),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_leave_year (user_id, leave_type, year)
);

-- Insert Sample Users
INSERT INTO users (username, password, role, full_name, email, department) VALUES
('manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', 'John Manager', 'manager@company.com', 'Human Resources'),
('employee', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', 'Jane Employee', 'employee@company.com', 'IT Department'),
('employee2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', 'Bob Smith', 'bob@company.com', 'Marketing'),
('employee3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', 'Alice Johnson', 'alice@company.com', 'Finance');

-- Leave Types Table
CREATE TABLE leave_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    leave_type VARCHAR(100) NOT NULL UNIQUE,
    default_balance INT DEFAULT 15,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert Default Leave Types
INSERT INTO leave_types (leave_type, default_balance, description) VALUES
('Vacation Leave', 15, 'Annual vacation leave for rest and recreation'),
('Sick Leave', 10, 'Medical leave for illness or health-related issues'),
('Personal Leave', 5, 'Personal time off for personal matters'),
('Emergency Leave', 3, 'Emergency leave for urgent personal situations'),
('Maternity Leave', 90, 'Maternity leave for new mothers'),
('Paternity Leave', 7, 'Paternity leave for new fathers'),
('Bereavement Leave', 5, 'Leave for mourning the loss of family members'),
('Study Leave', 10, 'Educational leave for professional development');

-- Insert Leave Balances for all users
INSERT INTO leave_balances (user_id, leave_type, total_earned, used, balance) VALUES
-- Manager balances
(1, 'Vacation Leave', 15, 2, 13),
(1, 'Sick Leave', 10, 1, 9),
(1, 'Personal Leave', 5, 0, 5),
(1, 'Emergency Leave', 3, 0, 3),
-- Employee 1 balances
(2, 'Vacation Leave', 15, 3, 12),
(2, 'Sick Leave', 10, 2, 8),
(2, 'Personal Leave', 5, 1, 4),
(2, 'Emergency Leave', 3, 0, 3),
-- Employee 2 balances
(3, 'Vacation Leave', 15, 1, 14),
(3, 'Sick Leave', 10, 0, 10),
(3, 'Personal Leave', 5, 0, 5),
(3, 'Emergency Leave', 3, 1, 2),
-- Employee 3 balances
(4, 'Vacation Leave', 15, 4, 11),
(4, 'Sick Leave', 10, 1, 9),
(4, 'Personal Leave', 5, 2, 3),
(4, 'Emergency Leave', 3, 0, 3);

-- Insert Sample Leave Requests (Enhanced with more 2024 data for better chart visualization)
INSERT INTO leave_requests (user_id, leave_type, date_from, date_to, days_requested, reason, status, manager_remarks, approved_by, filed_date, approved_date) VALUES
-- January 2024
(2, 'Vacation Leave', '2024-01-15', '2024-01-17', 3, 'Family vacation', 'approved', 'Approved for family time', 1, '2024-01-10 09:00:00', '2024-01-11 14:30:00'),
(3, 'Sick Leave', '2024-01-22', '2024-01-23', 2, 'Flu symptoms', 'approved', 'Medical leave approved', 1, '2024-01-20 08:15:00', '2024-01-20 16:45:00'),
(4, 'Personal Leave', '2024-01-29', '2024-01-29', 1, 'Personal appointment', 'approved', 'Approved', 1, '2024-01-25 10:30:00', '2024-01-26 09:15:00'),

-- February 2024
(2, 'Sick Leave', '2024-02-10', '2024-02-11', 2, 'Medical appointment', 'approved', 'Medical leave approved', 1, '2024-02-08 07:45:00', '2024-02-08 15:20:00'),
(4, 'Sick Leave', '2024-02-20', '2024-02-20', 1, 'Flu symptoms', 'rejected', 'Need medical certificate', 1, '2024-02-18 11:00:00', '2024-02-19 13:30:00'),
(3, 'Vacation Leave', '2024-02-26', '2024-02-28', 3, 'Long weekend trip', 'approved', 'Enjoy your trip', 1, '2024-02-20 14:20:00', '2024-02-21 10:45:00'),

-- March 2024
(3, 'Vacation Leave', '2024-03-01', '2024-03-01', 1, 'Personal matters', 'pending', NULL, NULL, '2024-02-28 16:30:00', NULL),
(2, 'Personal Leave', '2024-03-15', '2024-03-15', 1, 'Family event', 'approved', 'Approved for family event', 1, '2024-03-10 09:20:00', '2024-03-11 11:15:00'),
(4, 'Emergency Leave', '2024-03-22', '2024-03-22', 1, 'Family emergency', 'approved', 'Emergency approved', 1, '2024-03-22 06:30:00', '2024-03-22 08:00:00'),

-- April 2024
(2, 'Vacation Leave', '2024-04-08', '2024-04-12', 5, 'Spring vacation', 'approved', 'Enjoy your vacation', 1, '2024-04-01 10:00:00', '2024-04-02 14:20:00'),
(3, 'Sick Leave', '2024-04-18', '2024-04-19', 2, 'Medical treatment', 'approved', 'Get well soon', 1, '2024-04-16 08:45:00', '2024-04-16 16:30:00'),
(4, 'Personal Leave', '2024-04-25', '2024-04-26', 2, 'Personal business', 'pending', NULL, NULL, '2024-04-20 13:15:00', NULL),

-- May 2024
(3, 'Vacation Leave', '2024-05-06', '2024-05-10', 5, 'Family vacation', 'approved', 'Have a great time', 1, '2024-04-28 11:30:00', '2024-04-29 09:45:00'),
(2, 'Sick Leave', '2024-05-15', '2024-05-16', 2, 'Doctor appointment', 'approved', 'Medical leave approved', 1, '2024-05-13 07:20:00', '2024-05-13 15:10:00'),
(4, 'Vacation Leave', '2024-05-27', '2024-05-31', 5, 'Memorial Day weekend', 'approved', 'Approved for long weekend', 1, '2024-05-20 14:45:00', '2024-05-21 10:30:00'),

-- June 2024
(2, 'Personal Leave', '2024-06-03', '2024-06-03', 1, 'Personal appointment', 'approved', 'Approved', 1, '2024-05-30 09:15:00', '2024-05-30 16:20:00'),
(3, 'Sick Leave', '2024-06-12', '2024-06-13', 2, 'Illness', 'approved', 'Rest and recover', 1, '2024-06-10 06:45:00', '2024-06-10 14:15:00'),
(4, 'Emergency Leave', '2024-06-20', '2024-06-21', 2, 'Family emergency', 'approved', 'Emergency leave granted', 1, '2024-06-19 17:30:00', '2024-06-19 18:00:00'),

-- July 2024
(2, 'Vacation Leave', '2024-07-01', '2024-07-05', 5, 'Summer vacation', 'approved', 'Enjoy summer break', 1, '2024-06-25 10:20:00', '2024-06-26 11:45:00'),
(3, 'Personal Leave', '2024-07-15', '2024-07-15', 1, 'Personal matters', 'approved', 'Approved', 1, '2024-07-10 13:30:00', '2024-07-11 09:20:00'),
(4, 'Sick Leave', '2024-07-22', '2024-07-24', 3, 'Medical procedure', 'pending', NULL, NULL, '2024-07-18 08:15:00', NULL),

-- August 2024
(3, 'Vacation Leave', '2024-08-05', '2024-08-09', 5, 'Summer holiday', 'approved', 'Have fun', 1, '2024-07-30 14:00:00', '2024-07-31 10:15:00'),
(2, 'Sick Leave', '2024-08-14', '2024-08-15', 2, 'Health checkup', 'approved', 'Take care of your health', 1, '2024-08-12 07:30:00', '2024-08-12 15:45:00'),
(4, 'Personal Leave', '2024-08-26', '2024-08-26', 1, 'Personal business', 'approved', 'Approved', 1, '2024-08-22 11:45:00', '2024-08-23 09:30:00'),

-- September 2024
(2, 'Personal Leave', '2024-09-02', '2024-09-02', 1, 'Labor Day', 'approved', 'Holiday approved', 1, '2024-08-28 16:20:00', '2024-08-29 10:00:00'),
(3, 'Sick Leave', '2024-09-16', '2024-09-17', 2, 'Medical appointment', 'approved', 'Medical leave granted', 1, '2024-09-13 08:00:00', '2024-09-13 16:30:00'),
(4, 'Vacation Leave', '2024-09-23', '2024-09-27', 5, 'Fall vacation', 'pending', NULL, NULL, '2024-09-18 12:15:00', NULL),

-- October 2024
(2, 'Vacation Leave', '2024-10-07', '2024-10-11', 5, 'Fall break', 'approved', 'Enjoy autumn', 1, '2024-10-01 09:45:00', '2024-10-02 14:20:00'),
(3, 'Personal Leave', '2024-10-18', '2024-10-18', 1, 'Personal appointment', 'approved', 'Approved', 1, '2024-10-15 13:30:00', '2024-10-15 17:15:00'),
(4, 'Sick Leave', '2024-10-25', '2024-10-25', 1, 'Flu symptoms', 'rejected', 'Please provide medical certificate', 1, '2024-10-23 07:45:00', '2024-10-24 11:30:00'),

-- November 2024
(3, 'Vacation Leave', '2024-11-04', '2024-11-08', 5, 'Thanksgiving week', 'approved', 'Happy Thanksgiving', 1, '2024-10-28 10:30:00', '2024-10-29 15:45:00'),
(2, 'Sick Leave', '2024-11-15', '2024-11-15', 1, 'Doctor visit', 'approved', 'Take care', 1, '2024-11-13 08:20:00', '2024-11-13 16:10:00'),
(4, 'Personal Leave', '2024-11-22', '2024-11-22', 1, 'Personal matters', 'pending', NULL, NULL, '2024-11-18 14:45:00', NULL),

-- December 2024
(2, 'Vacation Leave', '2024-12-23', '2024-12-31', 7, 'Christmas and New Year', 'pending', NULL, NULL, '2024-12-01 11:00:00', NULL),
(3, 'Personal Leave', '2024-12-16', '2024-12-16', 1, 'Holiday shopping', 'approved', 'Enjoy shopping', 1, '2024-12-10 15:30:00', '2024-12-11 09:45:00'),
(4, 'Sick Leave', '2024-12-20', '2024-12-20', 1, 'Cold symptoms', 'approved', 'Rest well', 1, '2024-12-18 06:15:00', '2024-12-18 14:30:00');
