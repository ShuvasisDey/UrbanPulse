-- ============================================================
-- UrbanPulse — Add-on script for an ALREADY EXISTING database
-- ============================================================
-- Use this instead of smart_city_full.sql if you already created
-- your database and inserted your data yourself in phpMyAdmin.
--
-- HOW TO RUN:
-- 1. In phpMyAdmin, click on YOUR existing database in the left sidebar
--    (so it is the selected/active database).
-- 2. Go to the "SQL" tab and paste everything below, then click Go.
--
-- This does NOT drop, recreate, or modify any of your existing tables
-- or rows. It only adds one new table that the login system needs.
-- ============================================================

CREATE TABLE IF NOT EXISTS Users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','traffic_admin','energy_admin','hospital_admin','citizen') NOT NULL,
    ref_id INT NOT NULL COMMENT 'Central_Admin/Traffic_Admin/Energy_Admin/Hospital_Admin.admin_id, or Citizen.citizen_id, depending on role',
    security_question VARCHAR(255) NOT NULL,
    security_answer VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS Citizen_Registration_Requests (
    request_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    age INT,
    gender VARCHAR(20),
    address VARCHAR(255),
    contact_no VARCHAR(30),
    email VARCHAR(100),
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    security_question VARCHAR(255) NOT NULL,
    security_answer VARCHAR(255) NOT NULL,
    status ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
    requested_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS Hospital_Appointments (
    appointment_id INT PRIMARY KEY AUTO_INCREMENT,
    citizen_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time VARCHAR(20) NOT NULL,
    status ENUM('Pending','Confirmed','Cancelled','Completed') NOT NULL DEFAULT 'Pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (citizen_id) REFERENCES Citizen(citizen_id),
    FOREIGN KEY (doctor_id) REFERENCES Doctors(doctor_id)
);

-- After running this, open includes/db_connect.php and make sure
-- $DB_NAME exactly matches the name of YOUR existing database.
-- Then visit database/seed_users.php once in your browser to generate
-- a demo login for every Central_Admin, Traffic_Admin, Energy_Admin,
-- Hospital_Admin, and Citizen row you already have.
