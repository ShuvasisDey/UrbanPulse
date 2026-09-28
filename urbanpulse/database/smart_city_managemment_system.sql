-- ============================================================
-- SMART CITY MANAGEMENT SYSTEM - FULL SCHEMA (with Login/Users)
-- Import this whole file in phpMyAdmin (XAMPP) to set everything up.
-- ============================================================

CREATE DATABASE IF NOT EXISTS Smart_City_Management_System;
USE Smart_City_Management_System;

-- ============== CENTRAL ADMIN MODULE ==============
CREATE TABLE Central_Admin (
    admin_id INT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(120)
);

INSERT INTO Central_Admin (admin_id, name, email) VALUES
(1, 'Md. Al Amin', 'alamin@smartcity.com'),
(2, 'Tasfia Rahman', 'tasfia@smartcity.com'),
(3, 'Sabbir Hossain', 'sabbir@smartcity.com'),
(4, 'Fatema Akter', 'fatema@smartcity.com'),
(5, 'Raihan Chowdhury', 'raihan@smartcity.com'),
(6, 'Rumana Chowdhury', 'rumana@smartcity.com'),
(7, 'Imran Ahmed', 'imran@smartcity.com'),
(8, 'Lamia Sultana', 'lamia@smartcity.com'),
(9, 'Mahfuz Hasan', 'mahfuz@smartcity.com'),
(10, 'Sadia Islam', 'sadia@smartcity.com');

CREATE TABLE Central_Notifications (
    notification_id INT PRIMARY KEY,
    message TEXT,
    date_sent DATE,
    admin_id INT,
    FOREIGN KEY (admin_id) REFERENCES Central_Admin(admin_id)
);

INSERT INTO Central_Notifications (notification_id, message, date_sent, admin_id) VALUES
(1, 'Traffic update: Mirpur Road closed', '2025-11-01', 1),
(2, 'Energy maintenance scheduled', '2025-11-02', 2),
(3, 'Hospital blood donation drive', '2025-11-03', 3),
(4, 'New citizen portal launched', '2025-11-04', 4),
(5, 'City safety alert', '2025-11-05', 5),
(6, 'Water supply maintenance notice', '2025-11-06', 1),
(7, 'Public transport delay update', '2025-11-07', 2),
(8, 'City street cleaning schedule', '2025-11-08', 3),
(9, 'Emergency drill announcement', '2025-11-09', 4),
(10, 'Environmental awareness campaign', '2025-11-10', 5);

CREATE TABLE Central_Reports (
    report_id INT PRIMARY KEY,
    module VARCHAR(100),
    report_type VARCHAR(100),
    admin_id INT,
    FOREIGN KEY (admin_id) REFERENCES Central_Admin(admin_id)
);

INSERT INTO Central_Reports (report_id, module, report_type, admin_id) VALUES
(1, 'Traffic', 'Monthly', 1),
(2, 'Energy', 'Weekly', 2),
(3, 'Hospital', 'Quarterly', 3),
(4, 'Citizen', 'Monthly', 4),
(5, 'Energy', 'Monthly', 5),
(6, 'Traffic', 'Weekly', 1),
(7, 'Citizen', 'Annual', 2),
(8, 'Hospital', 'Monthly', 3),
(9, 'Energy', 'Quarterly', 4),
(10, 'Traffic', 'Special Report', 5);

-- ============== CITIZEN MODULE ==============
CREATE TABLE Citizen (
    citizen_id INT PRIMARY KEY,
    name VARCHAR(100),
    age INT,
    gender VARCHAR(20),
    address VARCHAR(255),
    contact_no VARCHAR(20),
    email VARCHAR(120)
);

INSERT INTO Citizen (citizen_id, name, age, gender, address, contact_no, email) VALUES
(1, 'Md. Rafiq', 30, 'Male', '123 Agrabad, Chittagong', '01710000001', 'rafiq@gmail.com'),
(2, 'Nusrat Jahan', 28, 'Female', '456 Halishahar, Chittagong', '01710000002', 'nusrat@gmail.com'),
(3, 'Shakib Al Hasan', 35, 'Male', '789 Panchlaish, Chittagong', '01710000003', 'shakib@gmail.com'),
(4, 'Tahmina Akter', 32, 'Female', '321 GEC, Chittagong', '01710000004', 'tahmina@gmail.com'),
(5, 'Rasel Hossain', 40, 'Male', '654 Nasirabad, Chittagong', '01710000005', 'rasel@gmail.com'),
(6, 'Samiul Haque', 29, 'Male', 'Pahartali, Chittagong', '01710000006', 'samiul@gmail.com'),
(7, 'Nasim Uddin', 45, 'Male', 'Chawkbazar, Chittagong', '01710000007', 'nasim@gmail.com'),
(8, 'Sumaiya Akter', 27, 'Female', 'Bayezid, Chittagong', '01710000008', 'sumaiya@gmail.com'),
(9, 'Kamrul Hasan', 33, 'Male', 'Patenga, Chittagong', '01710000009', 'kamrul@gmail.com'),
(10, 'Farzana Islam', 31, 'Female', 'Kotwali, Chittagong', '01710000010', 'farzana@gmail.com');

CREATE TABLE Citizen_Complaints (
    complaint_id INT PRIMARY KEY,
    description TEXT,
    status VARCHAR(50),
    citizen_id INT,
    FOREIGN KEY (citizen_id) REFERENCES Citizen(citizen_id)
);

INSERT INTO Citizen_Complaints (complaint_id, description, status, citizen_id) VALUES
(1, 'Street light not working', 'Pending', 1),
(2, 'Garbage collection delay', 'Resolved', 2),
(3, 'Water leakage in pipeline', 'In Progress', 3),
(4, 'Traffic signal malfunction', 'Pending', 4),
(5, 'Noisy construction at night', 'Resolved', 5),
(6, 'Road potholes near market', 'Pending', 6),
(7, 'Street flooding issue', 'Resolved', 7),
(8, 'Garbage bin overflow', 'Pending', 8),
(9, 'Illegal parking in neighborhood', 'In Progress', 9),
(10, 'Broken footpath', 'Resolved', 10);

CREATE TABLE Citizen_Feedback (
    feedback_id INT PRIMARY KEY,
    message TEXT,
    citizen_id INT,
    FOREIGN KEY (citizen_id) REFERENCES Citizen(citizen_id)
);

INSERT INTO Citizen_Feedback (feedback_id, message, citizen_id) VALUES
(1, 'Great service at city hospital', 1),
(2, 'Energy bill payment is easy', 2),
(3, 'Traffic fines are reasonable', 3),
(4, 'Portal is user-friendly', 4),
(5, 'Notification system is helpful', 5),
(6, 'Hospital service improved', 6),
(7, 'Need more street lights', 7),
(8, 'Water supply stable now', 8),
(9, 'Bus service quality poor', 9),
(10, 'City mobile app very helpful', 10);

-- ============== TRAFFIC MANAGEMENT MODULE ==============
CREATE TABLE Traffic_Admin (
    admin_id INT PRIMARY KEY,
    name VARCHAR(100),
    central_admin_id INT,
    FOREIGN KEY (central_admin_id) REFERENCES Central_Admin(admin_id)
);

INSERT INTO Traffic_Admin (admin_id, name, central_admin_id) VALUES
(1, 'Rafiul Islam', 1),
(2, 'Shathi Karim', 2),
(3, 'Fahim Ahmed', 3),
(4, 'Rina Sultana', 4),
(5, 'Sabbir Rahman', 5),
(6, 'Jannat Ara', 1),
(7, 'Mahadi Hasan', 2),
(8, 'Rasel Uddin', 3),
(9, 'Anamika Rahman', 4),
(10, 'Shawon Ahmed', 5);

CREATE TABLE Traffic_Vehicles (
    vehicle_id INT PRIMARY KEY,
    registration_no VARCHAR(50),
    type VARCHAR(50),
    citizen_id INT,
    admin_id INT,
    FOREIGN KEY (citizen_id) REFERENCES Citizen(citizen_id),
    FOREIGN KEY (admin_id) REFERENCES Traffic_Admin(admin_id)
);

INSERT INTO Traffic_Vehicles (vehicle_id, registration_no, type, citizen_id, admin_id) VALUES
(1, 'CTG-1001', 'Car', 1, 1),
(2, 'CTG-1002', 'Motorbike', 2, 2),
(3, 'CTG-1003', 'Car', 3, 3),
(4, 'CTG-1004', 'Truck', 4, 4),
(5, 'CTG-1005', 'Car', 5, 5),
(6, 'CTG-1006', 'Car', 6, 6),
(7, 'CTG-1007', 'Motorbike', 7, 7),
(8, 'CTG-1008', 'Car', 8, 8),
(9, 'CTG-1009', 'Truck', 9, 9),
(10,'CTG-1010', 'Motorbike', 10, 10);

CREATE TABLE Traffic_Violations (
    violation_id INT PRIMARY KEY,
    type VARCHAR(100),
    fine_amount DECIMAL(10,2),
    vehicle_id INT,
    FOREIGN KEY (vehicle_id) REFERENCES Traffic_Vehicles(vehicle_id)
);

INSERT INTO Traffic_Violations (violation_id, type, fine_amount, vehicle_id) VALUES
(1, 'Speeding', 1000, 1),
(2, 'Red Light Jump', 1500, 2),
(3, 'Illegal Parking', 500, 3),
(4, 'No Seatbelt', 300, 4),
(5, 'Wrong Lane', 700, 5),
(6, 'Overspeeding', 1200, 6),
(7, 'No Helmet', 500, 7),
(8, 'Blocking Road', 900, 8),
(9, 'Overtaking Dangerously', 1100, 9),
(10, 'Driving Without License', 2000, 10);

CREATE TABLE Traffic_Fines (
    fine_id INT PRIMARY KEY,
    amount DECIMAL(10,2),
    payment_status VARCHAR(50),
    violation_id INT,
    FOREIGN KEY (violation_id) REFERENCES Traffic_Violations(violation_id)
);

INSERT INTO Traffic_Fines (fine_id, amount, payment_status, violation_id) VALUES
(1, 1000, 'Paid', 1),
(2, 1500, 'Unpaid', 2),
(3, 500, 'Paid', 3),
(4, 300, 'Pending', 4),
(5, 700, 'Paid', 5),
(6, 1200, 'Paid', 6),
(7, 500, 'Pending', 7),
(8, 900, 'Paid', 8),
(9, 1100, 'Unpaid', 9),
(10, 2000, 'Paid', 10);

-- ============== ENERGY MANAGEMENT MODULE ==============
CREATE TABLE Energy_Admin (
    admin_id INT PRIMARY KEY,
    name VARCHAR(100),
    central_admin_id INT,
    FOREIGN KEY (central_admin_id) REFERENCES Central_Admin(admin_id)
);

INSERT INTO Energy_Admin (admin_id, name, central_admin_id) VALUES
(1, 'Jahid Hasan', 1),
(2, 'Anika Rahman', 2),
(3, 'Shafiqur Rahman', 3),
(4, 'Nabila Akter', 4),
(5, 'Tushar Karim', 5),
(6, 'Kamal Hasan', 1),
(7, 'Sharmin Akter', 2),
(8, 'Sabbir Chowdhury', 3),
(9, 'Lamia Hoque', 4),
(10, 'Shahriar Amin', 5);

CREATE TABLE Energy_Account (
    account_id INT PRIMARY KEY,
    meter_no VARCHAR(100),
    connection_type VARCHAR(50),
    citizen_id INT,
    admin_id INT,
    FOREIGN KEY (citizen_id) REFERENCES Citizen(citizen_id),
    FOREIGN KEY (admin_id) REFERENCES Energy_Admin(admin_id)
);

INSERT INTO Energy_Account (account_id, meter_no, connection_type, citizen_id, admin_id) VALUES
(1, 'MTR-1001', 'Residential', 1, 1),
(2, 'MTR-1002', 'Commercial', 2, 2),
(3, 'MTR-1003', 'Residential', 3, 3),
(4, 'MTR-1004', 'Commercial', 4, 4),
(5, 'MTR-1005', 'Residential', 5, 5),
(6, 'MTR-1006', 'Industrial', 6, 6),
(7, 'MTR-1007', 'Residential', 7, 7),
(8, 'MTR-1008', 'Commercial', 8, 8),
(9, 'MTR-1009', 'Residential', 9, 9),
(10,'MTR-1010', 'Industrial', 10, 10);

CREATE TABLE Energy_Usage (
    usage_id INT PRIMARY KEY,
    units INT,
    month VARCHAR(20),
    account_id INT,
    FOREIGN KEY (account_id) REFERENCES Energy_Account(account_id)
);

INSERT INTO Energy_Usage (usage_id, units, month, account_id) VALUES
(1, 150, 'November', 1),
(2, 200, 'November', 2),
(3, 120, 'November', 3),
(4, 250, 'November', 4),
(5, 180, 'November', 5),
(6, 300, 'November', 6),
(7, 160, 'November', 7),
(8, 420, 'November', 8),
(9, 130, 'November', 9),
(10,230, 'November', 10);

CREATE TABLE Energy_Bill (
    bill_id INT PRIMARY KEY,
    amount DECIMAL(10,2),
    payment_status VARCHAR(50),
    usage_id INT,
    FOREIGN KEY (usage_id) REFERENCES Energy_Usage(usage_id)
);

INSERT INTO Energy_Bill (bill_id, amount, payment_status, usage_id) VALUES
(1, 1800, 'Paid', 1),
(2, 2400, 'Unpaid', 2),
(3, 1440, 'Paid', 3),
(4, 3000, 'Pending', 4),
(5, 2160, 'Paid', 5),
(6, 3600, 'Paid', 6),
(7, 1920, 'Unpaid', 7),
(8, 5040, 'Pending', 8),
(9, 1560, 'Paid', 9),
(10,2760, 'Unpaid', 10);

-- ============== HOSPITAL MANAGEMENT MODULE ==============
CREATE TABLE Hospital_Admin (
    admin_id INT PRIMARY KEY,
    name VARCHAR(100),
    central_admin_id INT,
    FOREIGN KEY (central_admin_id) REFERENCES Central_Admin(admin_id)
);

INSERT INTO Hospital_Admin (admin_id, name, central_admin_id) VALUES
(1, 'Dr. Karim', 1),
(2, 'Dr. Jahan', 2),
(3, 'Dr. Rahman', 3),
(4, 'Dr. Akter', 4),
(5, 'Dr. Hossain', 5),
(6, 'Dr. Nesa', 1),
(7, 'Dr. Amin', 2),
(8, 'Dr. Latif', 3),
(9, 'Dr. Rupa', 4),
(10, 'Dr. Tanim', 5);

CREATE TABLE Hospitals (
    hospital_id INT PRIMARY KEY,
    name VARCHAR(100),
    location VARCHAR(255),
    capacity INT
);

INSERT INTO Hospitals (hospital_id, name, location, capacity) VALUES
(1, 'Chittagong Medical College', 'Chittagong', 500),
(2, 'Chittagong General Hospital', 'Chittagong', 200),
(3, 'Chittagong Sadar Hospital', 'Chittagong', 300),
(4, 'Chittagong Medical Hospital', 'Chittagong', 150),
(5, 'Chittagong City Hospital', 'Chittagong', 400),
(6, 'Chittagong Metropolitan Hospital', 'Chittagong', 350),
(7, 'Parkview Hospital', 'Chittagong', 300),
(8, 'Epic Healthcare', 'Chittagong', 280),
(9, 'CSCR Hospital', 'Chittagong', 250),
(10,'Delta Health Care', 'Chittagong', 220);

CREATE TABLE Doctors (
    doctor_id INT PRIMARY KEY,
    name VARCHAR(100),
    specialization VARCHAR(100),
    hospital_id INT,
    FOREIGN KEY (hospital_id) REFERENCES Hospitals(hospital_id)
);

INSERT INTO Doctors (doctor_id, name, specialization, hospital_id) VALUES
(1, 'Dr. Rafiq', 'Cardiology', 1),
(2, 'Dr. Nusrat', 'Neurology', 2),
(3, 'Dr. Shakib', 'Orthopedics', 3),
(4, 'Dr. Tahmina', 'Pediatrics', 4),
(5, 'Dr. Rasel', 'General Surgery', 5),
(6, 'Dr. Atik', 'Cardiology', 6),
(7, 'Dr. Lima', 'Dermatology', 7),
(8, 'Dr. Javed', 'ENT', 8),
(9, 'Dr. Sultana', 'Gynecology', 9),
(10,'Dr. Mahmud', 'Oncology', 10);

CREATE TABLE Patients (
    patient_id INT PRIMARY KEY,
    disease VARCHAR(150),
    status VARCHAR(50),
    citizen_id INT,
    doctor_id INT,
    admin_id INT,
    FOREIGN KEY (citizen_id) REFERENCES Citizen(citizen_id),
    FOREIGN KEY (doctor_id) REFERENCES Doctors(doctor_id),
    FOREIGN KEY (admin_id) REFERENCES Hospital_Admin(admin_id)
);

INSERT INTO Patients (patient_id, disease, status, citizen_id, doctor_id, admin_id) VALUES
(1, 'Flu', 'Recovering', 1, 1, 1),
(2, 'Migraine', 'Critical', 2, 2, 2),
(3, 'Fracture', 'Stable', 3, 3, 3),
(4, 'Fever', 'Recovering', 4, 4, 4),
(5, 'Allergy', 'Stable', 5, 5, 5),
(6, 'Heart Disease', 'Critical', 6, 6, 6),
(7, 'Skin Infection', 'Recovering', 7, 7, 7),
(8, 'Hearing Problem', 'Stable', 8, 8, 8),
(9, 'Pregnancy Case', 'Stable', 9, 9, 9),
(10,'Cancer Stage 1', 'Under Treatment', 10, 10, 10);

-- ============== NEW: USERS / LOGIN MODULE ==============
-- Added to power login, roles, and forgot-password (via security
-- question, since XAMPP has no email server configured by default).
CREATE TABLE Users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','traffic_admin','energy_admin','hospital_admin','citizen') NOT NULL,
    ref_id INT NOT NULL COMMENT 'Central_Admin/Traffic_Admin/Energy_Admin/Hospital_Admin.admin_id, or Citizen.citizen_id, depending on role',
    security_question VARCHAR(255) NOT NULL,
    security_answer VARCHAR(255) NOT NULL
);

-- Citizen sign-ups land here first; approving one (from the Central
-- Admin panel) creates the matching Citizen + Users rows.
CREATE TABLE Citizen_Registration_Requests (
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

-- Citizen hospital appointment bookings (doctor + time slot)
CREATE TABLE Hospital_Appointments (
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

-- After importing this file, run database/seed_users.php once
-- in your browser to create demo login accounts with hashed passwords
-- for every existing Central_Admin, Traffic_Admin, Energy_Admin,
-- Hospital_Admin, and Citizen row.
