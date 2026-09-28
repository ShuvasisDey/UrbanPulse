<?php
// Expects $page_title to be set before including this file.
require_once __DIR__ . '/auth.php';
$page_title = $page_title ?? 'UrbanPulse';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo e($page_title); ?> | UrbanPulse - Smart City Portal</title>
<!-- Font Awesome 6 -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<!-- Main Stylesheet -->
<link rel="stylesheet" href="<?php echo base_url('assets/css/style.css'); ?>?v=3.2">
</head>
<body>
<header class="topbar">
  <div class="brand">
    <a href="<?php echo base_url('index.php'); ?>">
      <span class="brand-dot"></span>
      <span>Urban<span style="color:var(--emerald);">Pulse</span></span>
    </a>
  </div>
  <?php if (is_logged_in()): ?>
  <nav class="topnav">
    <?php $role = current_role(); ?>
    <a href="<?php echo base_url($role === 'citizen' ? 'citizen/dashboard.php' : ($role === 'doctor' ? 'doctor/dashboard.php' : 'admin/dashboard.php')); ?>" class="<?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
      <i class="fa-solid fa-gauge"></i> Dashboard
    </a>

    <?php if ($role === 'doctor'): ?>
      <a href="<?php echo base_url('doctor/appointments.php'); ?>" class="<?php echo $current_page === 'appointments.php' ? 'active' : ''; ?>">
        <i class="fa-solid fa-calendar-check"></i> My Appointments
      </a>
      <a href="<?php echo base_url('doctor/patients.php'); ?>" class="<?php echo $current_page === 'patients.php' ? 'active' : ''; ?>">
        <i class="fa-solid fa-bed-pulse"></i> In-Patients
      </a>
      <a href="<?php echo base_url('doctor/salary.php'); ?>" class="<?php echo $current_page === 'salary.php' ? 'active' : ''; ?>">
        <i class="fa-solid fa-money-check-dollar"></i> Salary & Pay
      </a>
    <?php elseif ($role === 'admin'): ?>
      <a href="<?php echo base_url('admin/citizen_requests.php'); ?>" class="<?php echo $current_page === 'citizen_requests.php' ? 'active' : ''; ?>">
        <i class="fa-solid fa-user-clock"></i> Citizen Requests
      </a>
      <a href="<?php echo base_url('admin/manage.php?table=Citizen'); ?>" class="<?php echo ($current_page === 'manage.php' && isset($_GET['table']) && $_GET['table'] === 'Citizen') ? 'active' : ''; ?>">
        <i class="fa-solid fa-users"></i> Citizens
      </a>
      <a href="<?php echo base_url('admin/manage.php?table=Citizen_Complaints'); ?>" class="<?php echo ($current_page === 'manage.php' && isset($_GET['table']) && $_GET['table'] === 'Citizen_Complaints') ? 'active' : ''; ?>">
        <i class="fa-solid fa-comments"></i> Complaints
      </a>
      <a href="<?php echo base_url('admin/notifications.php'); ?>">
        <i class="fa-solid fa-bullhorn"></i> Bulletins
      </a>
    <?php elseif ($role === 'traffic_admin'): ?>
      <a href="<?php echo base_url('admin/manage.php?table=Traffic_Vehicles'); ?>"><i class="fa-solid fa-car"></i> Vehicles</a>
      <a href="<?php echo base_url('admin/manage.php?table=Traffic_Violations'); ?>"><i class="fa-solid fa-triangle-exclamation"></i> Violations</a>
      <a href="<?php echo base_url('admin/manage.php?table=Traffic_Fines'); ?>"><i class="fa-solid fa-receipt"></i> Fines</a>
    <?php elseif ($role === 'energy_admin'): ?>
      <a href="<?php echo base_url('admin/manage.php?table=Energy_Account'); ?>"><i class="fa-solid fa-bolt"></i> Accounts</a>
      <a href="<?php echo base_url('admin/manage.php?table=Energy_Usage'); ?>"><i class="fa-solid fa-chart-line"></i> Usage</a>
      <a href="<?php echo base_url('admin/manage.php?table=Energy_Bill'); ?>"><i class="fa-solid fa-file-invoice-dollar"></i> Bills</a>
    <?php elseif ($role === 'hospital_admin'): ?>
      <a href="<?php echo base_url('admin/manage.php?table=Hospitals'); ?>"><i class="fa-solid fa-hospital"></i> Hospitals</a>
      <a href="<?php echo base_url('admin/manage.php?table=Doctors'); ?>"><i class="fa-solid fa-user-doctor"></i> Doctors</a>
      <a href="<?php echo base_url('admin/manage.php?table=Hospital_Appointments'); ?>"><i class="fa-solid fa-calendar-check"></i> Appointments</a>
    <?php else: ?>
      <a href="<?php echo base_url('citizen/hospitals.php'); ?>" class="<?php echo $current_page === 'hospitals.php' ? 'active' : ''; ?>">
        <i class="fa-solid fa-hospital"></i> Hospitals & Beds
      </a>
      <a href="<?php echo base_url('citizen/book_appointment.php'); ?>" class="<?php echo $current_page === 'book_appointment.php' ? 'active' : ''; ?>">
        <i class="fa-solid fa-calendar-plus"></i> Appointments
      </a>
      <a href="<?php echo base_url('citizen/my_fines.php'); ?>" class="<?php echo $current_page === 'my_fines.php' ? 'active' : ''; ?>">
        <i class="fa-solid fa-traffic-light"></i> Traffic Fines
      </a>
      <a href="<?php echo base_url('citizen/my_bills.php'); ?>" class="<?php echo $current_page === 'my_bills.php' ? 'active' : ''; ?>">
        <i class="fa-solid fa-bolt"></i> Energy Bills
      </a>
      <a href="<?php echo base_url('citizen/complaints.php'); ?>" class="<?php echo $current_page === 'complaints.php' ? 'active' : ''; ?>">
        <i class="fa-solid fa-circle-exclamation"></i> Grievance
      </a>
    <?php endif; ?>

    <span class="who">
      <i class="fa-solid fa-user-circle" style="color:var(--sky);"></i>
      <?php echo e($_SESSION['name'] ?? $_SESSION['username']); ?>
      <strong>(<?php echo e(role_label($role)); ?>)</strong>
    </span>
    <a href="<?php echo base_url('auth/logout.php'); ?>" class="logout-link">
      <i class="fa-solid fa-right-from-bracket"></i> Logout
    </a>
  </nav>
  <?php else: ?>
  <div style="display:flex;align-items:center;gap:12px;">
    <a href="<?php echo base_url('auth/login.php'); ?>" class="btn btn-secondary btn-sm">Login</a>
    <a href="<?php echo base_url('auth/register.php'); ?>" class="btn btn-sm">Register</a>
  </div>
  <?php endif; ?>
</header>
<main class="container">