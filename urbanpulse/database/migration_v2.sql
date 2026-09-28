-- ============================================================
-- UrbanPulse — Migration v2
-- ============================================================
-- Adds:
--   1. Separate login roles for Traffic / Energy / Hospital admins
--   2. A citizen self-registration approval queue
--   3. A hospital appointment booking table
--
-- HOW TO RUN:
-- Import this AFTER database/smart_city_full.sql (or
-- database/add_users_table.sql) has already been imported.
-- In phpMyAdmin: select your database -> SQL tab -> paste -> Go.
-- Safe to run once on an existing database; it does not touch
-- your existing rows.
-- ============================================================

-- 1. Allow module-specific admin roles
ALTER TABLE Users
  MODIFY role ENUM('admin','traffic_admin','energy_admin','hospital_admin','citizen') NOT NULL;

-- 2. Citizen sign-ups now land here first; a Citizen + Users row is
--    only created once a Central Admin approves the request.
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

-- 3. Citizen hospital appointment bookings (doctor + time slot)
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

-- After running this, use database/seed_users.php again (it now
-- also creates one demo login per existing Traffic_Admin,
-- Energy_Admin, and Hospital_Admin row) or create module-admin
-- logins from the Central Admin panel: Admin > Traffic Admins /
-- Energy Admins / Hospital Admins > + Add New (a username/password
-- is created for them automatically).
