<?php
// ---- Database connection settings for XAMPP ----
// Default XAMPP MySQL: host=localhost, user=root, password=""
//
// IMPORTANT: set $DB_NAME to the EXACT name of the database you already
// created in phpMyAdmin (the one your Citizen/Central_Admin/etc. tables
// live in). Check the left sidebar in phpMyAdmin if you're not sure.
$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "Smart_City_Management_System"; // <-- change this if your database has a different name

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error .
        "<br>Make sure XAMPP's Apache and MySQL are running, and that you " .
        "imported database/smart_city_full.sql.");
}
$conn->set_charset("utf8mb4");
