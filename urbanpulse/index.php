<?php
session_start();
require_once __DIR__ . '/includes/db_connect.php';

if (!function_exists('base_url')) {
    function base_url($path = '') {
        return '/urbanpulse/' . ltrim($path, '/');
    }
}
if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        return isset($_SESSION['user_id']);
    }
}
if (!function_exists('is_admin_role')) {
    function is_admin_role() {
        return isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'traffic_admin', 'energy_admin', 'hospital_admin'], true);
    }
}

// Fetch live database statistics for city metrics
$citizenCount = 0;
$cRes = $conn->query("SELECT COUNT(*) AS total FROM Citizen");
if ($cRes) { $citizenCount = (int)$cRes->fetch_assoc()['total']; }

$hospitalCount = 0;
$hRes = $conn->query("SELECT COUNT(*) AS total FROM Hospitals");
if ($hRes) { $hospitalCount = (int)$hRes->fetch_assoc()['total']; }

$doctorCount = 0;
$dRes = $conn->query("SELECT COUNT(*) AS total FROM Doctors");
if ($dRes) { $doctorCount = (int)$dRes->fetch_assoc()['total']; }

$patientCount = 0;
$pRes = $conn->query("SELECT COUNT(*) AS total FROM Patients WHERE status <> 'Discharged'");
if ($pRes) { $patientCount = (int)$pRes->fetch_assoc()['total']; }

$totalAvailableBeds = 0;
$totalBedsCapacity = 0;
$bedSql = "SELECT h.capacity,
          (SELECT COUNT(*) FROM Patients p JOIN Doctors d ON p.doctor_id = d.doctor_id WHERE d.hospital_id = h.hospital_id AND p.status <> 'Discharged') AS occupied
          FROM Hospitals h";
$bedRes = $conn->query($bedSql);
if ($bedRes) {
    while ($bRow = $bedRes->fetch_assoc()) {
        $cap = (int)$bRow['capacity'];
        $occ = (int)$bRow['occupied'];
        $totalBedsCapacity += $cap;
        $totalAvailableBeds += max(0, $cap - $occ);
    }
}

$vehicleCount = 0;
$vRes = $conn->query("SELECT COUNT(*) AS total FROM Traffic_Vehicles");
if ($vRes) { $vehicleCount = (int)$vRes->fetch_assoc()['total']; }

$violationsCount = 0;
$vlRes = $conn->query("SELECT COUNT(*) AS total FROM Traffic_Violations");
if ($vlRes) { $violationsCount = (int)$vlRes->fetch_assoc()['total']; }

$energyCount = 0;
$eRes = $conn->query("SELECT COUNT(*) AS total FROM Energy_Account");
if ($eRes) { $energyCount = (int)$eRes->fetch_assoc()['total']; }

$complaintsCount = 0;
$cmpRes = $conn->query("SELECT COUNT(*) AS total FROM Citizen_Complaints");
if ($cmpRes) { $complaintsCount = (int)$cmpRes->fetch_assoc()['total']; }

// Fetch live announcements from Central_Notifications
$notifications = [];
$notifSql = "SELECT n.message, n.date_sent, a.name AS sender_name 
             FROM Central_Notifications n 
             LEFT JOIN Central_Admin a ON n.admin_id = a.admin_id 
             ORDER BY n.notification_id DESC LIMIT 4";
$notifRes = $conn->query($notifSql);
if ($notifRes) {
    while ($nRow = $notifRes->fetch_assoc()) {
        $notifications[] = $nRow;
    }
}

// Fetch live hospital snapshot for healthcare pillar
$liveHospitals = [];
$hospListSql = "SELECT h.hospital_id, h.name, h.location, h.capacity,
                (SELECT COUNT(*) FROM Patients p JOIN Doctors d ON p.doctor_id = d.doctor_id WHERE d.hospital_id = h.hospital_id AND p.status <> 'Discharged') AS occupied
                FROM Hospitals h ORDER BY h.hospital_id ASC LIMIT 5";
$hospListRes = $conn->query($hospListSql);
if ($hospListRes) {
    while ($hlRow = $hospListRes->fetch_assoc()) {
        $liveHospitals[] = $hlRow;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UrbanPulse — Smart City Integrated Management System</title>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        darkBg: '#0b1120',
                        cardBg: '#0f172a',
                        darkerBg: '#060a12',
                        emeraldGreen: '#10b981',
                        skyBlue: '#38bdf8',
                        mintGlow: '#34d399',
                        ashGap: '#1e293b',
                        ashBorder: '#334155'
                    }
                }
            }
        }
    </script>
    <style>
        * { scroll-behavior: smooth; }
        body {
            background-color: #0b1120;
            color: #e2e8f0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(16, 185, 129, 0.08) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(56, 189, 248, 0.08) 0%, transparent 40%);
        }
        
        .section-gap-ash {
            background-color: #1e293b;
            height: 1px;
            width: 100%;
        }

        @keyframes pureFadeIn {
            0% { opacity: 0; transform: translateY(8px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        .text-fade-motion { animation: pureFadeIn 1s ease-in-out forwards; }
        .text-fade-motion-delayed { animation: pureFadeIn 1.3s ease-in-out 0.2s forwards; opacity: 0; }

        @keyframes pulse-ring {
            0% { transform: scale(0.95); opacity: 0.8; box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1.1); opacity: 0; box-shadow: 0 0 0 15px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); opacity: 0; box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }
        .advanced-float-wrapper {
            position: fixed;
            bottom: 25px;
            right: 25px;
            z-index: 999;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .adv-float-btn {
            position: relative;
            width: 52px;
            height: 52px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 22px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            text-decoration: none;
        }
        .adv-float-btn:hover { transform: scale(1.15) translateY(-3px); }
        .adv-whatsapp { background: linear-gradient(135deg, #25D366, #128C7E); }
        .adv-whatsapp::after {
            content: '';
            position: absolute;
            top: -4px; left: -4px; right: -4px; bottom: -4px;
            border-radius: 50%;
            border: 2px solid #25D366;
            animation: pulse-ring 2s infinite;
        }
        .adv-phone { background: linear-gradient(135deg, #0284c7, #0369a1); }
        .adv-phone::after {
            content: '';
            position: absolute;
            top: -4px; left: -4px; right: -4px; bottom: -4px;
            border-radius: 50%;
            border: 2px solid #38bdf8;
            animation: pulse-ring 2s infinite 1s;
        }

        @keyframes morph {
            0% { border-radius: 38% 62% 63% 37% / 41% 44% 56% 59%; }
            50% { border-radius: 54% 46% 38% 62% / 49% 60% 40% 51%; }
            100% { border-radius: 38% 62% 63% 37% / 41% 44% 56% 59%; }
        }
        .wavy-image-outer {
            position: relative;
            width: 100%;
            max-width: 480px;
            height: 420px;
            background: linear-gradient(135deg, #0284c7, #10b981);
            border-radius: 38% 62% 63% 37% / 41% 44% 56% 59%;
            padding: 12px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.7), 0 0 30px rgba(16, 185, 129, 0.3);
            animation: morph 8s ease-in-out infinite;
        }
        .wavy-image-inner {
            width: 100%;
            height: 100%;
            background: #ffffff;
            border-radius: 35% 65% 60% 40% / 40% 35% 65% 60%;
            padding: 6px;
            overflow: hidden;
        }
        .wavy-image-inner img.main-ai-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 32% 68% 58% 42% / 38% 32% 68% 62%;
            transition: transform 0.7s ease;
        }
        .wavy-image-outer:hover img.main-ai-img { transform: scale(1.05); }

        .circles-wrapper {
            position: absolute;
            bottom: -15px;
            right: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 10;
        }
        .circle-badge {
            border-radius: 50%;
            border: 3px solid #0b1120;
            box-shadow: 0 8px 25px rgba(0,0,0,0.6);
            overflow: hidden;
            background: #fff;
            transition: transform 0.3s ease;
        }
        .circle-badge:hover { transform: scale(1.15) translateY(-5px); }
        .circle-badge.c1 { width: 75px; height: 75px; }
        .circle-badge.c2 { width: 65px; height: 65px; }
        .circle-badge.c3 { width: 80px; height: 80px; }
        .circle-badge img { width: 100%; height: 100%; object-fit: cover; }

        .glass-card {
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(52, 211, 153, 0.25);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .glass-card:hover {
            border-color: rgba(52, 211, 153, 0.6);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.5), 0 0 25px rgba(52, 211, 153, 0.2);
            transform: translateY(-4px);
        }

        .hover-card-effect {
            transition: all 0.3s ease-in-out;
        }
        .hover-card-effect:hover {
            transform: translateY(-5px);
            border-color: #38bdf8;
            box-shadow: 0 15px 35px rgba(56, 189, 248, 0.18);
        }

        /* Dropdown Styling for Navbar */
        .dropdown-container { position: relative; }
        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background-color: #0f172a;
            min-width: 250px;
            box-shadow: 0px 15px 35px rgba(0,0,0,0.7);
            border: 1px solid #334155;
            border-radius: 14px;
            z-index: 100;
            padding: 8px 0;
            list-style: none;
        }
        .dropdown-menu li a {
            display: flex;
            align-items: center;
            padding: 10px 18px;
            color: #cbd5e1;
            font-size: 13px;
            text-decoration: none;
            white-space: nowrap;
            transition: all 0.2s ease;
        }
        .dropdown-menu li a:hover {
            background-color: #1e293b;
            color: #10b981;
            padding-left: 22px;
        }
        .dropdown-container:hover .dropdown-menu { display: block; }

        .stat-pill {
            background: rgba(15, 23, 42, 0.65);
            border: 1px solid rgba(52, 211, 153, 0.25);
            backdrop-filter: blur(12px);
            border-radius: 16px;
            padding: 16px 20px;
            transition: all 0.3s ease;
        }
        .stat-pill:hover {
            border-color: #34d399;
            box-shadow: 0 0 20px rgba(52, 211, 153, 0.25);
            transform: translateY(-2px);
        }

        .topic-img-card {
            position: relative;
            height: 140px;
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 16px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .topic-img-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s ease;
        }
        .topic-img-card:hover img {
            transform: scale(1.08);
        }
        .topic-img-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, transparent 30%, rgba(15, 23, 42, 0.9) 100%);
        }
        .topic-img-badge {
            position: absolute;
            bottom: 10px;
            left: 12px;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 6px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.8);
        }
    </style>
</head>
<body class="antialiased text-slate-200 bg-darkBg relative">

    <!-- FLOATING CONTACT WIDGET -->
    <div class="advanced-float-wrapper">
        <a href="https://wa.me/8801700000000" target="_blank" title="Chat on WhatsApp" class="adv-float-btn adv-whatsapp">
            <i class="fa-brands fa-whatsapp"></i>
        </a>
        <a href="tel:999" title="Call Emergency Hotline" class="adv-float-btn adv-phone">
            <i class="fa-solid fa-phone"></i>
        </a>
    </div>

    <!-- 1. TOP INFO BAR -->
    <div class="bg-darkerBg text-slate-400 px-4 md:px-[6%] py-2.5 text-xs md:text-sm flex flex-col sm:flex-row justify-between items-center border-b border-slate-800 gap-2">
        <div class="flex items-center gap-4 flex-wrap justify-center sm:justify-start">
            <span><i class="fa-solid fa-city text-emeraldGreen mr-1.5"></i> Smart City Integrated Platform</span>
            <span class="hidden md:inline">|</span>
            <span><i class="fa-solid fa-hospital-user text-skyBlue mr-1.5"></i> Connected Hospitals: <strong class="text-white"><?php echo $hospitalCount; ?></strong></span>
            <span class="hidden md:inline">|</span>
            <span><i class="fa-solid fa-bed text-emeraldGreen mr-1.5"></i> Live Beds Open: <strong class="text-emeraldGreen"><?php echo $totalAvailableBeds; ?></strong></span>
            <span class="hidden md:inline">|</span>
            <span><i class="fa-solid fa-user-doctor text-teal-400 mr-1.5"></i> Doctors on Duty: <strong class="text-teal-300"><?php echo $doctorCount; ?></strong></span>
        </div>
        <div class="font-medium text-slate-300 flex items-center gap-2">
            <span>Emergency Dispatch:</span>
            <a href="tel:999" class="text-emeraldGreen font-bold bg-emerald-950/80 px-2.5 py-1 rounded-md border border-emerald-800/60 hover:bg-emerald-900 transition-colors">
                <i class="fa-solid fa-phone-volume mr-1"></i> 999
            </a>
            <a href="tel:106" class="text-skyBlue font-bold bg-sky-950/80 px-2.5 py-1 rounded-md border border-sky-800/60 hover:bg-sky-900 transition-colors">
                <i class="fa-solid fa-shield-halved mr-1"></i> 106
            </a>
        </div>
    </div>

    <!-- NAVBAR -->
    <nav class="sticky top-0 z-50 bg-darkBg/95 backdrop-blur-md px-4 md:px-[6%] py-4 border-b border-slate-800 shadow-xl flex justify-between items-center">
        <a href="#home" class="text-2xl font-black text-white flex items-center gap-2.5 tracking-wide">
            <img src="assets/images/logo1.png" alt="UrbanPulse Logo" class="w-10 h-10 rounded-xl object-cover shadow-lg bg-slate-800 p-1 border border-slate-700">
            <span>Urban<span class="text-emeraldGreen">Pulse</span></span>
        </a>

        <!-- Desktop Navigation Menu -->
        <ul class="hidden lg:flex items-center gap-5 text-sm font-medium text-slate-300">
            <li><a href="#home" class="hover:text-emeraldGreen transition-colors">Home</a></li>
            <li><a href="#overview" class="hover:text-emeraldGreen transition-colors">About System</a></li>
            <li><a href="#workflow" class="hover:text-emeraldGreen transition-colors">How It Works</a></li>
            
            <!-- City Services Dropdown -->
            <li class="dropdown-container py-2">
                <a href="#city-services" class="hover:text-emeraldGreen transition-colors flex items-center gap-1.5">
                    City Services <i class="fa-solid fa-angle-down text-xs"></i>
                </a>
                <ul class="dropdown-menu">
                    <li><a href="#healthcare"><i class="fa-solid fa-hospital text-emeraldGreen mr-2.5"></i> Healthcare & Hospitals</a></li>
                    <li><a href="#doctor-portal"><i class="fa-solid fa-user-doctor text-teal-400 mr-2.5"></i> Doctor Clinical Portal</a></li>
                    <li><a href="#traffic"><i class="fa-solid fa-traffic-light text-skyBlue mr-2.5"></i> Traffic & Transport</a></li>
                    <li><a href="#energy"><i class="fa-solid fa-bolt text-amber-400 mr-2.5"></i> Smart Energy & Utilities</a></li>
                    <li><a href="#central-gov"><i class="fa-solid fa-building-columns text-emeraldGreen mr-2.5"></i> Central City Governance</a></li>
                    <li><a href="#grievance"><i class="fa-solid fa-comments text-skyBlue mr-2.5"></i> Grievances & Citizen Voice</a></li>
                </ul>
            </li>

            <!-- Citizen Portal Access -->
            <li><a href="<?php echo is_logged_in() && ($_SESSION['role'] ?? '') === 'citizen' ? base_url('citizen/dashboard.php') : base_url('auth/login.php'); ?>" class="hover:text-emeraldGreen transition-colors flex items-center gap-1">
                <i class="fa-solid fa-user-circle text-skyBlue"></i> Citizen Portal
            </a></li>

            <!-- Doctor Portal Access -->
            <li><a href="<?php echo is_logged_in() && ($_SESSION['role'] ?? '') === 'doctor' ? base_url('doctor/dashboard.php') : base_url('auth/login.php'); ?>" class="hover:text-emeraldGreen transition-colors flex items-center gap-1">
                <i class="fa-solid fa-user-doctor text-teal-400"></i> Doctor Portal
            </a></li>

            <!-- Manager Portal Dropdown -->
            <li class="dropdown-container py-2">
                <a href="#" class="hover:text-emeraldGreen transition-colors flex items-center gap-1.5">
                    Manager Portal <i class="fa-solid fa-angle-down text-xs"></i>
                </a>
                <ul class="dropdown-menu">
                    <li><a href="<?php echo base_url('auth/login.php?role=central_admin'); ?>"><i class="fa-solid fa-user-shield mr-2.5 text-skyBlue"></i> Central Manager</a></li>
                    <li><a href="<?php echo base_url('auth/login.php?role=traffic_admin'); ?>"><i class="fa-solid fa-car mr-2.5 text-skyBlue"></i> Traffic Manager</a></li>
                    <li><a href="<?php echo base_url('auth/login.php?role=hospital_admin'); ?>"><i class="fa-solid fa-hospital mr-2.5 text-emeraldGreen"></i> Hospital Manager</a></li>
                    <li><a href="<?php echo base_url('auth/login.php?role=energy_admin'); ?>"><i class="fa-solid fa-bolt mr-2.5 text-amber-400"></i> Energy Manager</a></li>
                </ul>
            </li>

            <li><a href="#bulletins" class="hover:text-emeraldGreen transition-colors">Bulletins</a></li>
            <li><a href="#faq" class="hover:text-emeraldGreen transition-colors">FAQ</a></li>
            <li><a href="#contact" class="hover:text-emeraldGreen transition-colors">Contact</a></li>
        </ul>

        <div class="flex items-center gap-3">
            <?php if (is_logged_in()): ?>
                <?php 
                $dashboard_url = 'citizen/dashboard.php';
                if (is_admin_role()) { $dashboard_url = 'admin/dashboard.php'; }
                elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'doctor') { $dashboard_url = 'doctor/dashboard.php'; }
                ?>
                <a href="<?php echo base_url($dashboard_url); ?>" class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold text-white bg-slate-800 hover:bg-slate-700 border border-slate-700 rounded-lg transition-all shadow-md">
                    <i class="fa-solid fa-gauge text-skyBlue"></i> Dashboard
                </a>
                <a href="<?php echo base_url('auth/logout.php'); ?>" class="px-4 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-500 rounded-lg shadow-md transition-all">
                    Logout
                </a>
            <?php else: ?>
                <a href="<?php echo base_url('auth/login.php'); ?>" class="px-4 py-2 text-xs font-bold text-slate-300 hover:text-white transition-colors">
                    Login
                </a>
                <a href="<?php echo base_url('auth/register.php'); ?>" class="px-4 py-2 text-xs font-bold text-white bg-emeraldGreen hover:bg-emerald-600 rounded-lg shadow-md transition-all flex items-center gap-1.5">
                    <i class="fa-solid fa-user-plus"></i> Register
                </a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- 2. HERO BANNER SECTION -->
    <section id="home" class="min-h-[620px] bg-darkBg flex items-center justify-between px-4 md:px-[6%] py-12 lg:py-20 relative overflow-hidden">
        <div class="absolute top-1/4 left-10 w-72 h-72 bg-blue-600/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute bottom-10 right-10 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="grid lg:grid-cols-12 gap-12 items-center w-full relative z-10">
            <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
                <div class="inline-flex items-center gap-2 bg-emerald-950/70 border border-emerald-500/30 text-emeraldGreen px-4 py-1.5 rounded-full text-xs font-bold tracking-wide text-fade-motion">
                    <i class="fa-solid fa-shield-halved"></i> URBANPULSE SMART METROPOLIS PLATFORM
                </div>
                
                <h1 class="text-3xl sm:text-5xl lg:text-6xl font-extrabold text-white leading-tight tracking-tight text-fade-motion">
                    UrbanPulse: Building <span class="text-transparent bg-clip-text bg-gradient-to-r from-skyBlue via-mintGlow to-emeraldGreen">Smarter, Safer, & Connected</span> Cities.
                </h1>
                
                <p class="text-slate-300 text-base sm:text-lg leading-relaxed max-w-2xl mx-auto lg:mx-0 text-fade-motion-delayed">
                    The complete municipal operating system unifying public healthcare bed availability, automated traffic violations, smart energy grid meters, clinical physician portals, and direct citizen grievance redressal.
                </p>
                
                <div class="pt-2 flex flex-wrap gap-4 justify-center lg:justify-start">
                    <a href="#city-services" class="px-7 py-3.5 rounded-xl bg-gradient-to-r from-skyBlue to-emeraldGreen text-white font-bold shadow-lg shadow-sky-500/20 hover:shadow-sky-500/40 transition-all duration-300 transform hover:-translate-y-0.5 flex items-center gap-2 text-sm">
                        <span>Explore All Services</span>
                        <i class="fa-solid fa-arrow-down"></i>
                    </a>
                    <a href="<?php echo is_logged_in() ? base_url('citizen/dashboard.php') : base_url('auth/login.php'); ?>" class="px-7 py-3.5 rounded-xl bg-cardBg border border-slate-700 hover:border-emeraldGreen text-slate-200 font-bold hover:text-white transition-all duration-300 flex items-center gap-2 text-sm">
                        <i class="fa-solid fa-user-circle text-emeraldGreen"></i>
                        <span>Citizen Hub</span>
                    </a>
                    <a href="<?php echo base_url('auth/login.php'); ?>" class="px-6 py-3.5 rounded-xl bg-slate-800/80 border border-slate-700 hover:border-teal-400 text-teal-300 font-bold hover:text-white transition-all text-sm flex items-center gap-2">
                        <i class="fa-solid fa-user-doctor text-teal-400"></i>
                        <span>Doctor Login</span>
                    </a>
                </div>

                <!-- Live Dynamic City Metrics -->
                <div class="pt-8 grid grid-cols-2 sm:grid-cols-3 gap-4 border-t border-slate-800/80 max-w-xl mx-auto lg:mx-0 text-left">
                    <div class="stat-pill">
                        <div class="text-2xl font-black text-emeraldGreen"><?php echo $citizenCount; ?>+</div>
                        <div class="text-xs text-slate-400 mt-0.5 font-medium">Citizens Registered</div>
                    </div>
                    <div class="stat-pill">
                        <div class="text-2xl font-black text-skyBlue"><?php echo $hospitalCount; ?></div>
                        <div class="text-xs text-slate-400 mt-0.5 font-medium">Connected Hospitals</div>
                    </div>
                    <div class="stat-pill">
                        <div class="text-2xl font-black text-teal-400"><?php echo $doctorCount; ?></div>
                        <div class="text-xs text-slate-400 mt-0.5 font-medium">Licensed Doctors</div>
                    </div>
                    <div class="stat-pill">
                        <div class="text-2xl font-black text-emeraldGreen"><?php echo $totalAvailableBeds; ?></div>
                        <div class="text-xs text-slate-400 mt-0.5 font-medium">Live ICU / Beds Open</div>
                    </div>
                    <div class="stat-pill">
                        <div class="text-2xl font-black text-amber-400"><?php echo $energyCount; ?></div>
                        <div class="text-xs text-slate-400 mt-0.5 font-medium">Smart Energy Meters</div>
                    </div>
                    <div class="stat-pill">
                        <div class="text-2xl font-black text-rose-400"><?php echo $violationsCount; ?></div>
                        <div class="text-xs text-slate-400 mt-0.5 font-medium">Tracked Violations</div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-5 flex justify-center lg:justify-end relative">
                <div class="relative w-full max-w-[480px] h-[420px] flex items-center justify-center">
                    <div class="wavy-image-outer">
                        <div class="wavy-image-inner">
                            <img class="main-ai-img" src="assets/images/ai.jpg" alt="Smart City Command Center">
                        </div>
                    </div>
                    <div class="circles-wrapper">
                        <div class="circle-badge c1" title="Real-time Traffic Corridor"><img src="assets/images/traffic.jpg" alt="Traffic"></div>
                        <div class="circle-badge c2" title="Central Command"><img src="assets/images/gun.jpg" alt="Command"></div>
                        <div class="circle-badge c3" title="Healthcare Hub"><img src="assets/images/video.jpg" alt="Hospital"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- EMERGENCY RAPID ACTION BAR -->
    <section id="emergency" class="bg-gradient-to-r from-red-950/40 via-darkerBg to-sky-950/40 border-y border-slate-800 py-6 px-4 md:px-[6%]">
        <div class="max-w-6xl mx-auto flex flex-col md:flex-row items-center justify-between gap-6">
            <div>
                <div class="text-xs font-bold text-rose-400 uppercase tracking-wider flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-ping inline-block"></span>
                    24/7 Rapid Emergency Dispatch Hotline
                </div>
                <h3 class="text-lg font-bold text-white mt-1">Instant Municipal Rapid Action Network</h3>
            </div>
            <div class="flex items-center gap-3 flex-wrap justify-center">
                <a href="tel:999" class="bg-rose-950/80 border border-rose-800 text-rose-300 hover:bg-rose-900 hover:text-white px-4 py-2.5 rounded-xl font-bold text-xs flex items-center gap-2 transition-all">
                    <i class="fa-solid fa-truck-medical text-sm"></i> Ambulance / Police: 999
                </a>
                <a href="tel:199" class="bg-amber-950/80 border border-amber-800 text-amber-300 hover:bg-amber-900 hover:text-white px-4 py-2.5 rounded-xl font-bold text-xs flex items-center gap-2 transition-all">
                    <i class="fa-solid fa-fire-extinguisher text-sm"></i> Fire & Rescue: 199
                </a>
                <a href="tel:106" class="bg-sky-950/80 border border-sky-800 text-sky-300 hover:bg-sky-900 hover:text-white px-4 py-2.5 rounded-xl font-bold text-xs flex items-center gap-2 transition-all">
                    <i class="fa-solid fa-headset text-sm"></i> City Control: 106
                </a>
            </div>
        </div>
    </section>

    <!-- 3. COMPREHENSIVE OVERVIEW: WHAT IS URBANPULSE -->
    <section id="overview" class="px-4 md:px-[6%] py-20 bg-darkerBg">
        <div class="max-w-6xl mx-auto space-y-14">
            <div class="text-center space-y-4 max-w-3xl mx-auto">
                <span class="text-xs font-bold text-emeraldGreen uppercase tracking-widest bg-emerald-950/60 px-3 py-1 rounded-full border border-emerald-800/40">Complete Platform Architecture</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-white">What is UrbanPulse?</h2>
                <p class="text-slate-300 text-sm sm:text-base leading-relaxed">
                    UrbanPulse is an integrated Smart City Operating System designed to replace fragmented municipal paperwork with a synchronized, real-time digital command grid. It brings together citizens, medical doctors, and department managers to create an efficient, transparent, and resilient urban community.
                </p>
            </div>

            <!-- 3 Core Pillars Cards -->
            <div class="grid md:grid-cols-3 gap-8">
                <div class="glass-card rounded-3xl p-8 space-y-4">
                    <div class="w-14 h-14 rounded-2xl bg-sky-950/80 border border-sky-800/50 flex items-center justify-center text-skyBlue text-2xl">
                        <i class="fa-solid fa-network-wired"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white">Unified City Infrastructure</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Seamlessly bridges Healthcare, Traffic, Energy Utilities, and Civic Grievance management under a centralized MySQL relational database, eliminating data silos and administrative friction.
                    </p>
                    <ul class="text-xs text-slate-300 space-y-2 pt-2 border-t border-slate-800">
                        <li><i class="fa-solid fa-check text-skyBlue mr-2"></i> Real-time multi-department sync</li>
                        <li><i class="fa-solid fa-check text-skyBlue mr-2"></i> Role-based access control & audits</li>
                    </ul>
                </div>

                <div class="glass-card rounded-3xl p-8 space-y-4">
                    <div class="w-14 h-14 rounded-2xl bg-emerald-950/80 border border-emerald-800/50 flex items-center justify-center text-emeraldGreen text-2xl">
                        <i class="fa-solid fa-user-check"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white">Citizen Empowerment</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Citizens enjoy 1-click self-service: live hospital bed discovery, verified doctor appointments, traffic fine payment tracking, monthly electricity ledger audits, and public grievance tracking.
                    </p>
                    <ul class="text-xs text-slate-300 space-y-2 pt-2 border-t border-slate-800">
                        <li><i class="fa-solid fa-check text-emeraldGreen mr-2"></i> Verified citizen digital identity</li>
                        <li><i class="fa-solid fa-check text-emeraldGreen mr-2"></i> Transparent SLA ticket resolution</li>
                    </ul>
                </div>

                <div class="glass-card rounded-3xl p-8 space-y-4">
                    <div class="w-14 h-14 rounded-2xl bg-teal-950/80 border border-teal-800/50 flex items-center justify-center text-teal-400 text-2xl">
                        <i class="fa-solid fa-user-doctor"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white">Clinical & Operational Precision</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Dedicated portals for Medical Doctors and Department Managers. Doctors manage patient consultations and clinical wards; Managers coordinate departmental resources and payroll.
                    </p>
                    <ul class="text-xs text-slate-300 space-y-2 pt-2 border-t border-slate-800">
                        <li><i class="fa-solid fa-check text-teal-400 mr-2"></i> Dedicated Doctor Portal & payslips</li>
                        <li><i class="fa-solid fa-check text-teal-400 mr-2"></i> Scoped Manager Command Desks</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <div class="section-gap-ash"></div>

    <!-- 4. SYSTEM WORKFLOW: HOW URBANPULSE WORKS -->
    <section id="workflow" class="px-4 md:px-[6%] py-20 bg-darkBg">
        <div class="max-w-6xl mx-auto space-y-14">
            <div class="text-center space-y-4 max-w-2xl mx-auto">
                <span class="text-xs font-bold text-skyBlue uppercase tracking-widest bg-sky-950/60 px-3 py-1 rounded-full border border-sky-800/40">Operational Lifecycle</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-white">How UrbanPulse Operates</h2>
                <p class="text-slate-400 text-sm">Step-by-step workflow connecting citizen requests, automated IoT streams, and municipal resolution.</p>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Step 1 -->
                <div class="glass-card rounded-2xl p-6 relative space-y-3">
                    <div class="w-10 h-10 rounded-xl bg-sky-500/20 border border-sky-400/40 text-skyBlue font-extrabold flex items-center justify-center text-lg">
                        1
                    </div>
                    <h4 class="text-base font-bold text-white">Identity & Verification</h4>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Citizens register with identity details and security credentials. Central Managers review and approve requests, preventing spam and identity fraud.
                    </p>
                </div>

                <!-- Step 2 -->
                <div class="glass-card rounded-2xl p-6 relative space-y-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/20 border border-emerald-400/40 text-emeraldGreen font-extrabold flex items-center justify-center text-lg">
                        2
                    </div>
                    <h4 class="text-base font-bold text-white">IoT & Live Data Streams</h4>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Municipal hospitals report bed occupancy, smart meters log kilowatt-hour power usage, and speed cameras record road violations in real-time.
                    </p>
                </div>

                <!-- Step 3 -->
                <div class="glass-card rounded-2xl p-6 relative space-y-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/20 border border-amber-400/40 text-amber-400 font-extrabold flex items-center justify-center text-lg">
                        3
                    </div>
                    <h4 class="text-base font-bold text-white">Citizen Action & Access</h4>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Citizens book doctor consultations, check fine settlements, review utility bills, and file geo-located complaints for streetlights and water leaks.
                    </p>
                </div>

                <!-- Step 4 -->
                <div class="glass-card rounded-2xl p-6 relative space-y-3">
                    <div class="w-10 h-10 rounded-xl bg-teal-500/20 border border-teal-400/40 text-teal-400 font-extrabold flex items-center justify-center text-lg">
                        4
                    </div>
                    <h4 class="text-base font-bold text-white">Resolution & Broadcast</h4>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Doctors confirm appointments; department managers dispatch repair crews and broadcast live municipal notices to the citizen bulletin board.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <div class="section-gap-ash"></div>

    <!-- 5. LIVE MUNICIPAL BULLETINS & NOTICES -->
    <section id="bulletins" class="px-4 md:px-[6%] py-16 bg-darkerBg">
        <div class="max-w-6xl mx-auto space-y-8">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div>
                    <span class="text-xs font-bold text-emeraldGreen uppercase tracking-widest">Official City Feed</span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-white mt-1">Live Municipal Bulletins & Notices</h2>
                </div>
                <div class="text-xs text-slate-400">
                    Broadcasted directly by Central Managers & Municipal Desks
                </div>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php if (empty($notifications)): ?>
                    <div class="col-span-full bg-cardBg border border-slate-800 p-8 rounded-2xl text-center text-slate-400 text-sm">
                        No active announcements posted at this moment.
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $notif): ?>
                        <div class="bg-cardBg border border-slate-800 rounded-2xl p-5 hover-card-effect flex flex-col justify-between space-y-4">
                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-[11px] text-slate-400">
                                    <span class="bg-sky-950/70 text-skyBlue px-2 py-0.5 rounded border border-sky-800/40 font-semibold">Notice</span>
                                    <span><?php echo htmlspecialchars($notif['date_sent']); ?></span>
                                </div>
                                <p class="text-sm text-slate-200 leading-relaxed font-medium">
                                    <?php echo htmlspecialchars($notif['message']); ?>
                                </p>
                            </div>
                            <div class="text-[11px] text-slate-400 border-t border-slate-800/80 pt-2 flex items-center gap-1.5">
                                <i class="fa-solid fa-user-shield text-emeraldGreen text-xs"></i>
                                <span><?php echo htmlspecialchars($notif['sender_name'] ?? 'City Manager'); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <div class="section-gap-ash"></div>

    <!-- 6. CORE CITY SERVICES DIRECTORY (6 IN-DEPTH MODULES WITH IMAGES) -->
    <section id="city-services" class="px-4 md:px-[6%] py-20 bg-darkBg">
        <div class="max-w-6xl mx-auto space-y-16">
            <div class="text-center space-y-3">
                <span class="text-xs font-bold text-emeraldGreen uppercase tracking-widest">Public Service Portals</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-white">Essential Smart City Services & Portals</h2>
                <p class="text-slate-400 text-sm max-w-2xl mx-auto">Explore the specialized operational wings engineered to run a modern, responsive metropolis.</p>
            </div>

            <!-- PILLAR 1: HEALTHCARE & LIVE BEDS -->
            <div id="healthcare" class="glass-card rounded-3xl p-6 sm:p-10 shadow-2xl">
                <div class="grid lg:grid-cols-12 gap-8 items-center pb-6 border-b border-slate-800">
                    <div class="lg:col-span-8 flex items-start gap-4">
                        <div class="w-16 h-16 rounded-2xl bg-emerald-950/80 border border-emerald-800/60 flex items-center justify-center text-emeraldGreen text-3xl flex-shrink-0">
                            <i class="fa-solid fa-hospital"></i>
                        </div>
                        <div>
                            <span class="text-xs font-bold text-emeraldGreen uppercase tracking-wider">Healthcare Infrastructure & Bed Management</span>
                            <h3 class="text-xl sm:text-2xl font-bold text-white mt-1">Live Hospital Network & Critical Care Bed Tracker</h3>
                            <p class="text-slate-300 text-xs sm:text-sm mt-2 leading-relaxed">
                                Emergency medical care without guesswork. Real-time ICU and ward bed occupancy counts across all municipal hospitals, with transparent patient admission counters.
                            </p>
                        </div>
                    </div>
                    <div class="lg:col-span-4 flex items-center justify-start lg:justify-end gap-3 flex-wrap">
                        <a href="<?php echo base_url('citizen/book_appointment.php'); ?>" class="px-5 py-2.5 rounded-xl bg-emeraldGreen hover:bg-emerald-600 text-white font-bold text-xs transition-all flex items-center gap-2 shadow-lg shadow-emerald-500/20">
                            <i class="fa-solid fa-calendar-plus"></i> Book Doctor Appointment
                        </a>
                        <a href="<?php echo base_url('citizen/hospitals.php'); ?>" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-bold text-xs transition-all">
                            View All Hospitals
                        </a>
                    </div>
                </div>

                <!-- Live Hospital Table Snapshot -->
                <div class="mt-6 overflow-x-auto">
                    <table class="w-full text-left text-xs sm:text-sm">
                        <thead class="bg-darkerBg text-slate-400 uppercase text-[11px] border-b border-slate-800">
                            <tr>
                                <th class="p-3">Hospital Center</th>
                                <th class="p-3">Location</th>
                                <th class="p-3">Total Capacity</th>
                                <th class="p-3">In-Patients</th>
                                <th class="p-3">Live Available Beds</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/80">
                            <?php if (empty($liveHospitals)): ?>
                                <tr><td colspan="5" class="p-4 text-center text-slate-400">No hospital data found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($liveHospitals as $hosp): 
                                    $openBeds = max(0, (int)$hosp['capacity'] - (int)$hosp['occupied']);
                                ?>
                                    <tr class="hover:bg-slate-800/40 transition-colors">
                                        <td class="p-3 font-semibold text-white"><?php echo htmlspecialchars($hosp['name']); ?></td>
                                        <td class="p-3 text-slate-300"><?php echo htmlspecialchars($hosp['location']); ?></td>
                                        <td class="p-3 text-slate-400"><?php echo (int)$hosp['capacity']; ?> Beds</td>
                                        <td class="p-3 text-slate-400"><?php echo (int)$hosp['occupied']; ?> Patients</td>
                                        <td class="p-3">
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold <?php echo $openBeds > 0 ? 'bg-emerald-950/80 text-emeraldGreen border border-emerald-800/50' : 'bg-rose-950/80 text-rose-400 border border-rose-800/50'; ?>">
                                                <span class="w-1.5 h-1.5 rounded-full <?php echo $openBeds > 0 ? 'bg-emeraldGreen animate-pulse' : 'bg-rose-500'; ?>"></span>
                                                <?php echo $openBeds; ?> Open Beds
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- PILLAR 2 TO 6: SPECIALIZED DEPARTMENT TILES WITH THEMED IMAGES -->
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">

                <!-- 1. Doctor Clinical Portal -->
                <div id="doctor-portal" class="glass-card rounded-3xl p-6 flex flex-col justify-between hover-card-effect">
                    <div>
                        <div class="topic-img-card">
                            <img src="assets/images/video.jpg" alt="Medical Doctors">
                            <div class="topic-img-overlay"></div>
                            <span class="topic-img-badge"><i class="fa-solid fa-user-doctor text-teal-400"></i> Physician Clinical Portal</span>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-2">Physician Clinical Portal</h3>
                        <p class="text-slate-400 text-xs sm:text-sm leading-relaxed mb-4">
                            Licensed medical doctors access their dedicated dashboard to review scheduled appointments, track admitted in-patients, and view monthly official salary statements with itemized pay slips.
                        </p>
                        <div class="space-y-1.5 text-xs text-slate-300 border-t border-slate-800/80 pt-3">
                            <div><i class="fa-solid fa-calendar-check text-teal-400 mr-2"></i> Confirm / complete patient bookings</div>
                            <div><i class="fa-solid fa-bed-pulse text-teal-400 mr-2"></i> Update in-patient recovery status</div>
                            <div><i class="fa-solid fa-file-invoice-dollar text-teal-400 mr-2"></i> Official compensation slip with print support</div>
                        </div>
                    </div>
                    <div class="pt-5 border-t border-slate-800 flex items-center justify-between mt-4">
                        <a href="<?php echo is_logged_in() && ($_SESSION['role'] ?? '') === 'doctor' ? base_url('doctor/dashboard.php') : base_url('auth/login.php'); ?>" class="text-xs font-bold text-teal-400 hover:text-white flex items-center gap-1.5">
                            <span>Open Doctor Portal</span>
                            <i class="fa-solid fa-arrow-right text-[10px]"></i>
                        </a>
                        <span class="text-[11px] text-slate-500 font-mono"><?php echo $doctorCount; ?> Doctors Active</span>
                    </div>
                </div>

                <!-- 2. Traffic & Transportation -->
                <div id="traffic" class="glass-card rounded-3xl p-6 flex flex-col justify-between hover-card-effect">
                    <div>
                        <div class="topic-img-card">
                            <img src="assets/images/traffic.jpg" alt="Traffic and Transportation">
                            <div class="topic-img-overlay"></div>
                            <span class="topic-img-badge"><i class="fa-solid fa-traffic-light text-skyBlue"></i> Traffic & Transport Management</span>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-2">Traffic & Transportation</h3>
                        <p class="text-slate-400 text-xs sm:text-sm leading-relaxed mb-4">
                            Intelligent speed corridor radars and intersection monitoring. Automated violation issuance, vehicle registrations, and instant online fine verification and settlement.
                        </p>
                        <div class="space-y-1.5 text-xs text-slate-300 border-t border-slate-800/80 pt-3">
                            <div><i class="fa-solid fa-car text-skyBlue mr-2"></i> <?php echo $vehicleCount; ?> Registered Municipal Vehicles</div>
                            <div><i class="fa-solid fa-triangle-exclamation text-skyBlue mr-2"></i> Automated camera violation logging</div>
                            <div><i class="fa-solid fa-receipt text-skyBlue mr-2"></i> Online penalty payment tracking</div>
                        </div>
                    </div>
                    <div class="pt-5 border-t border-slate-800 flex items-center justify-between mt-4">
                        <a href="<?php echo base_url('citizen/my_fines.php'); ?>" class="text-xs font-bold text-skyBlue hover:text-white flex items-center gap-1.5">
                            <span>Check Traffic Fines</span>
                            <i class="fa-solid fa-arrow-right text-[10px]"></i>
                        </a>
                        <span class="text-[11px] text-slate-500 font-mono">Radar Grid Active</span>
                    </div>
                </div>

                <!-- 3. Smart Energy & Power Utilities -->
                <div id="energy" class="glass-card rounded-3xl p-6 flex flex-col justify-between hover-card-effect">
                    <div>
                        <div class="topic-img-card">
                            <img src="assets/images/ai.jpg" alt="Smart Energy Grid">
                            <div class="topic-img-overlay"></div>
                            <span class="topic-img-badge"><i class="fa-solid fa-bolt text-amber-400"></i> Smart Energy & Utilities</span>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-2">Smart Energy & Utilities</h3>
                        <p class="text-slate-400 text-xs sm:text-sm leading-relaxed mb-4">
                            Next-generation automated digital power grid. Real-time smart meter electricity readings, transparent consumption ledgers, and automated monthly billing statements.
                        </p>
                        <div class="space-y-1.5 text-xs text-slate-300 border-t border-slate-800/80 pt-3">
                            <div><i class="fa-solid fa-bolt text-amber-400 mr-2"></i> <?php echo $energyCount; ?> Active Smart Meter Accounts</div>
                            <div><i class="fa-solid fa-chart-line text-amber-400 mr-2"></i> Monthly kWh usage calculation</div>
                            <div><i class="fa-solid fa-file-invoice-dollar text-amber-400 mr-2"></i> Automated tariff invoice settlements</div>
                        </div>
                    </div>
                    <div class="pt-5 border-t border-slate-800 flex items-center justify-between mt-4">
                        <a href="<?php echo base_url('citizen/my_bills.php'); ?>" class="text-xs font-bold text-amber-400 hover:text-white flex items-center gap-1.5">
                            <span>View Energy Bills</span>
                            <i class="fa-solid fa-arrow-right text-[10px]"></i>
                        </a>
                        <span class="text-[11px] text-slate-500 font-mono">Grid 99.8% Uptime</span>
                    </div>
                </div>

                <!-- 4. Central City Governance & Command -->
                <div id="central-gov" class="glass-card rounded-3xl p-6 flex flex-col justify-between hover-card-effect">
                    <div>
                        <div class="topic-img-card">
                            <img src="assets/images/gun.jpg" alt="City Governance">
                            <div class="topic-img-overlay"></div>
                            <span class="topic-img-badge"><i class="fa-solid fa-building-columns text-emeraldGreen"></i> Central City Governance</span>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-2">City Command & Governance</h3>
                        <p class="text-slate-400 text-xs sm:text-sm leading-relaxed mb-4">
                            Central Managers oversee all municipal departments, review citizen sign-up requests, manage global security credentials, and broadcast emergency public bulletins.
                        </p>
                        <div class="space-y-1.5 text-xs text-slate-300 border-t border-slate-800/80 pt-3">
                            <div><i class="fa-solid fa-user-clock text-emeraldGreen mr-2"></i> Citizen sign-up approval queue</div>
                            <div><i class="fa-solid fa-bullhorn text-emeraldGreen mr-2"></i> City-wide bulletin broadcaster</div>
                            <div><i class="fa-solid fa-shield text-emeraldGreen mr-2"></i> Cross-department administrative audits</div>
                        </div>
                    </div>
                    <div class="pt-5 border-t border-slate-800 flex items-center justify-between mt-4">
                        <a href="<?php echo base_url('auth/login.php?role=central_admin'); ?>" class="text-xs font-bold text-emeraldGreen hover:text-white flex items-center gap-1.5">
                            <span>Central Manager Portal</span>
                            <i class="fa-solid fa-arrow-right text-[10px]"></i>
                        </a>
                        <span class="text-[11px] text-slate-500 font-mono">Secured Level 1</span>
                    </div>
                </div>

                <!-- 5. Grievances & Citizen Voice -->
                <div id="grievance" class="glass-card rounded-3xl p-6 flex flex-col justify-between hover-card-effect">
                    <div>
                        <div class="topic-img-card">
                            <img src="assets/images/download (1).jpg" alt="Citizen Grievances">
                            <div class="topic-img-overlay"></div>
                            <span class="topic-img-badge"><i class="fa-solid fa-comments text-skyBlue"></i> Civic Grievances & Feedback</span>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-2">Public Grievances & Feedback</h3>
                        <p class="text-slate-400 text-xs sm:text-sm leading-relaxed mb-4">
                            Empowering citizens to report broken streetlights, water pipeline bursts, and road damage. File complaints with SLA resolution tracking and share city improvement suggestions.
                        </p>
                        <div class="space-y-1.5 text-xs text-slate-300 border-t border-slate-800/80 pt-3">
                            <div><i class="fa-solid fa-triangle-exclamation text-skyBlue mr-2"></i> <?php echo $complaintsCount; ?> Logged Civic Complaints</div>
                            <div><i class="fa-solid fa-circle-check text-skyBlue mr-2"></i> 3-stage SLA: Pending &rarr; In Progress &rarr; Resolved</div>
                            <div><i class="fa-solid fa-comment-dots text-skyBlue mr-2"></i> Direct citizen feedback to municipal desks</div>
                        </div>
                    </div>
                    <div class="pt-5 border-t border-slate-800 flex items-center justify-between mt-4">
                        <a href="<?php echo base_url('citizen/complaints.php'); ?>" class="text-xs font-bold text-skyBlue hover:text-white flex items-center gap-1.5">
                            <span>File a Complaint</span>
                            <i class="fa-solid fa-arrow-right text-[10px]"></i>
                        </a>
                        <a href="<?php echo base_url('citizen/feedback.php'); ?>" class="text-xs text-slate-400 hover:text-white">Give Feedback</a>
                    </div>
                </div>

                <!-- 6. Hospital Management Portal -->
                <div id="hospital-mgmt" class="glass-card rounded-3xl p-6 flex flex-col justify-between hover-card-effect">
                    <div>
                        <div class="topic-img-card">
                            <img src="assets/images/video.jpg" alt="Hospital Management">
                            <div class="topic-img-overlay"></div>
                            <span class="topic-img-badge"><i class="fa-solid fa-hospital text-emeraldGreen"></i> Hospital Department Management</span>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-2">Hospital Management</h3>
                        <p class="text-slate-400 text-xs sm:text-sm leading-relaxed mb-4">
                            Hospital Managers oversee medical infrastructure, manage total hospital bed capacities, license medical doctors, set up doctor login accounts, and manage in-patient rosters.
                        </p>
                        <div class="space-y-1.5 text-xs text-slate-300 border-t border-slate-800/80 pt-3">
                            <div><i class="fa-solid fa-hospital-user text-emeraldGreen mr-2"></i> Total Bed Capacity: <?php echo $totalBedsCapacity; ?> Beds</div>
                            <div><i class="fa-solid fa-key text-emeraldGreen mr-2"></i> Set up doctor logins & salary packages</div>
                            <div><i class="fa-solid fa-bed text-emeraldGreen mr-2"></i> Manage patient admissions & discharges</div>
                        </div>
                    </div>
                    <div class="pt-5 border-t border-slate-800 flex items-center justify-between mt-4">
                        <a href="<?php echo base_url('auth/login.php?role=hospital_admin'); ?>" class="text-xs font-bold text-emeraldGreen hover:text-white flex items-center gap-1.5">
                            <span>Hospital Manager Portal</span>
                            <i class="fa-solid fa-arrow-right text-[10px]"></i>
                        </a>
                        <span class="text-[11px] text-slate-500 font-mono"><?php echo $hospitalCount; ?> Centers</span>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <div class="section-gap-ash"></div>

    <!-- 7. ROLE MATRIX: WHO USES URBANPULSE -->
    <section class="px-4 md:px-[6%] py-20 bg-darkerBg">
        <div class="max-w-6xl mx-auto space-y-12">
            <div class="text-center space-y-3">
                <span class="text-xs font-bold text-teal-400 uppercase tracking-widest bg-teal-950/60 px-3 py-1 rounded-full border border-teal-800/40">Role Matrix</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-white">Who Uses UrbanPulse?</h2>
                <p class="text-slate-400 text-sm max-w-xl mx-auto">A purpose-built ecosystem tailored to citizens, clinical medical staff, and municipal department managers.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Citizen Role -->
                <div class="glass-card rounded-3xl p-8 border-t-4 border-sky-400 space-y-5">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-sky-950/80 text-skyBlue flex items-center justify-center text-xl">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">Citizens</h3>
                            <span class="text-xs text-skyBlue font-medium">Public Community</span>
                        </div>
                    </div>
                    <ul class="text-xs text-slate-300 space-y-2.5">
                        <li class="flex items-start gap-2"><i class="fa-solid fa-check text-skyBlue mt-0.5"></i> <span>Search live hospital ICU and ward beds</span></li>
                        <li class="flex items-start gap-2"><i class="fa-solid fa-check text-skyBlue mt-0.5"></i> <span>Book doctor appointments with instant slots</span></li>
                        <li class="flex items-start gap-2"><i class="fa-solid fa-check text-skyBlue mt-0.5"></i> <span>View vehicle violations and settle fines</span></li>
                        <li class="flex items-start gap-2"><i class="fa-solid fa-check text-skyBlue mt-0.5"></i> <span>Track monthly kWh electricity billing statements</span></li>
                        <li class="flex items-start gap-2"><i class="fa-solid fa-check text-skyBlue mt-0.5"></i> <span>File civic grievances with SLA resolution</span></li>
                    </ul>
                    <a href="<?php echo base_url('auth/login.php'); ?>" class="block text-center py-2.5 rounded-xl bg-sky-600/30 border border-sky-500/40 text-sky-200 text-xs font-bold hover:bg-sky-600 hover:text-white transition-all">
                        Citizen Sign In &rarr;
                    </a>
                </div>

                <!-- Doctor Role -->
                <div class="glass-card rounded-3xl p-8 border-t-4 border-teal-400 space-y-5">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-teal-950/80 text-teal-400 flex items-center justify-center text-xl">
                            <i class="fa-solid fa-user-doctor"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">Medical Doctors</h3>
                            <span class="text-xs text-teal-400 font-medium">Clinical Practitioners</span>
                        </div>
                    </div>
                    <ul class="text-xs text-slate-300 space-y-2.5">
                        <li class="flex items-start gap-2"><i class="fa-solid fa-check text-teal-400 mt-0.5"></i> <span>Managed and licensed by Hospital Management</span></li>
                        <li class="flex items-start gap-2"><i class="fa-solid fa-check text-teal-400 mt-0.5"></i> <span>Dedicated Doctor Portal with secure login</span></li>
                        <li class="flex items-start gap-2"><i class="fa-solid fa-check text-teal-400 mt-0.5"></i> <span>Review and confirm citizen appointment bookings</span></li>
                        <li class="flex items-start gap-2"><i class="fa-solid fa-check text-teal-400 mt-0.5"></i> <span>Track admitted in-patients and update condition</span></li>
                        <li class="flex items-start gap-2"><i class="fa-solid fa-check text-teal-400 mt-0.5"></i> <span>View itemized official payslip with print support</span></li>
                    </ul>
                    <a href="<?php echo base_url('auth/login.php'); ?>" class="block text-center py-2.5 rounded-xl bg-teal-600/30 border border-teal-500/40 text-teal-200 text-xs font-bold hover:bg-teal-600 hover:text-white transition-all">
                        Doctor Sign In &rarr;
                    </a>
                </div>

                <!-- Managers Role -->
                <div class="glass-card rounded-3xl p-8 border-t-4 border-emerald-400 space-y-5">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-emerald-950/80 text-emeraldGreen flex items-center justify-center text-xl">
                            <i class="fa-solid fa-user-shield"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">Department Managers</h3>
                            <span class="text-xs text-emeraldGreen font-medium">Central & Domain Admins</span>
                        </div>
                    </div>
                    <ul class="text-xs text-slate-300 space-y-2.5">
                        <li class="flex items-start gap-2"><i class="fa-solid fa-check text-emeraldGreen mt-0.5"></i> <span><strong>Central Manager:</strong> Approvals & bulletins</span></li>
                        <li class="flex items-start gap-2"><i class="fa-solid fa-check text-emeraldGreen mt-0.5"></i> <span><strong>Hospital Manager:</strong> Beds, doctors, payroll</span></li>
                        <li class="flex items-start gap-2"><i class="fa-solid fa-check text-emeraldGreen mt-0.5"></i> <span><strong>Traffic Manager:</strong> Radars, vehicles, fines</span></li>
                        <li class="flex items-start gap-2"><i class="fa-solid fa-check text-emeraldGreen mt-0.5"></i> <span><strong>Energy Manager:</strong> Meters, kWh usage, tariffs</span></li>
                        <li class="flex items-start gap-2"><i class="fa-solid fa-check text-emeraldGreen mt-0.5"></i> <span>Full CRUD management across city tables</span></li>
                    </ul>
                    <a href="<?php echo base_url('auth/login.php?role=central_admin'); ?>" class="block text-center py-2.5 rounded-xl bg-emerald-600/30 border border-emerald-500/40 text-emerald-200 text-xs font-bold hover:bg-emerald-600 hover:text-white transition-all">
                        Manager Sign In &rarr;
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="section-gap-ash"></div>

    <!-- 8. FREQUENTLY ASKED QUESTIONS (FAQ) -->
    <section id="faq" class="px-4 md:px-[6%] py-20 bg-darkBg">
        <div class="max-w-5xl mx-auto space-y-12">
            <div class="text-center space-y-3">
                <span class="text-xs font-bold text-skyBlue uppercase tracking-widest bg-sky-950/60 px-3 py-1 rounded-full border border-sky-800/40">Knowledge Base</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-white">Frequently Asked Questions</h2>
                <p class="text-slate-400 text-sm">Everything you need to know about the UrbanPulse Smart City Management System.</p>
            </div>

            <div class="space-y-4">
                <div class="glass-card rounded-2xl p-6 space-y-2">
                    <h4 class="text-base font-bold text-white flex items-center gap-3">
                        <i class="fa-solid fa-circle-question text-emeraldGreen"></i>
                        How do citizens register and access the portal?
                    </h4>
                    <p class="text-xs sm:text-sm text-slate-300 leading-relaxed pl-7">
                        Citizens can submit their registration request at the <a href="auth/register.php" class="text-emeraldGreen underline font-semibold">Sign Up Page</a>. To maintain identity authenticity and eliminate fake accounts, registration requests enter a verification queue and are promptly approved by a Central Manager.
                    </p>
                </div>

                <div class="glass-card rounded-2xl p-6 space-y-2">
                    <h4 class="text-base font-bold text-white flex items-center gap-3">
                        <i class="fa-solid fa-circle-question text-teal-400"></i>
                        How are doctors managed, and how do they receive their login accounts?
                    </h4>
                    <p class="text-xs sm:text-sm text-slate-300 leading-relaxed pl-7">
                        Doctors are licensed clinical healthcare professionals managed by Hospital Management (not managers themselves). Hospital Managers configure each doctor's hospital center, monthly base salary, and login credentials in the Hospital Manager portal. Doctors then log in with their credentials to access the Doctor Portal.
                    </p>
                </div>

                <div class="glass-card rounded-2xl p-6 space-y-2">
                    <h4 class="text-base font-bold text-white flex items-center gap-3">
                        <i class="fa-solid fa-circle-question text-skyBlue"></i>
                        How does real-time hospital bed availability work?
                    </h4>
                    <p class="text-xs sm:text-sm text-slate-300 leading-relaxed pl-7">
                        The live bed tracker continuously queries total registered hospital capacity minus all active admitted in-patients (excluding discharged patients). This provides citizens and emergency ambulances with an instant, accurate count of open ICU and ward beds before dispatch.
                    </p>
                </div>

                <div class="glass-card rounded-2xl p-6 space-y-2">
                    <h4 class="text-base font-bold text-white flex items-center gap-3">
                        <i class="fa-solid fa-circle-question text-amber-400"></i>
                        How are traffic violations and energy utility bills handled?
                    </h4>
                    <p class="text-xs sm:text-sm text-slate-300 leading-relaxed pl-7">
                        Traffic cameras and patrol officers record violations linked to registered vehicle license plates. Citizens can check their violation history, penalty amounts, and payment status in their portal. Similarly, smart meter monthly kWh readings generate digital billing statements with automated tariff calculations.
                    </p>
                </div>

                <div class="glass-card rounded-2xl p-6 space-y-2">
                    <h4 class="text-base font-bold text-white flex items-center gap-3">
                        <i class="fa-solid fa-circle-question text-rose-400"></i>
                        What happens when a citizen files a complaint?
                    </h4>
                    <p class="text-xs sm:text-sm text-slate-300 leading-relaxed pl-7">
                        Submitted grievances (streetlights, water leakage, road hazards) are routed to the Central Manager and relevant department. The complaint tracks through three transparent SLA stages: <em>Pending</em> &rarr; <em>In Progress</em> &rarr; <em>Resolved</em>, ensuring full municipal accountability.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <div class="section-gap-ash"></div>

    <!-- 9. CONTACT US SECTION -->
    <section id="contact" class="px-4 md:px-[6%] py-20 bg-darkerBg">
        <div class="max-w-6xl mx-auto space-y-12">
            <div class="text-center space-y-3">
                <span class="text-xs font-bold text-emeraldGreen uppercase tracking-widest bg-emerald-950/60 px-3 py-1 rounded-full border border-emerald-800/40">Reach Out To Us</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-white">Municipal Headquarters & Support</h2>
                <p class="text-slate-400 text-sm max-w-xl mx-auto">Have questions regarding municipal services, fine inquiries, or emergency support? Contact our team anytime.</p>
            </div>
            
            <div class="grid md:grid-cols-3 gap-6">
                <!-- Location Card -->
                <div class="glass-card p-8 rounded-3xl text-center hover-card-effect space-y-4">
                    <div class="w-14 h-14 bg-sky-950/80 rounded-2xl flex items-center justify-center text-skyBlue text-2xl mx-auto">
                        <i class="fa-solid fa-location-dot"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white">Central Operations Tower</h3>
                    <p class="text-slate-400 text-xs sm:text-sm leading-relaxed">
                        UrbanPulse Command Center, Level 12, Smart City Avenue, Central Metro District
                    </p>
                </div>

                <!-- Emergency Contact Card -->
                <div class="glass-card p-8 rounded-3xl text-center hover-card-effect space-y-4">
                    <div class="w-14 h-14 bg-emerald-950/80 rounded-2xl flex items-center justify-center text-emeraldGreen text-2xl mx-auto">
                        <i class="fa-solid fa-phone-volume"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white">Emergency Hotline</h3>
                    <p class="text-slate-400 text-xs sm:text-sm leading-relaxed">
                        Hotline: 999 / 106<br>+880 1700-000000 (24/7 Dispatch)
                    </p>
                </div>

                <!-- Email Card -->
                <div class="glass-card p-8 rounded-3xl text-center hover-card-effect space-y-4">
                    <div class="w-14 h-14 bg-amber-950/80 rounded-2xl flex items-center justify-center text-amber-400 text-2xl mx-auto">
                        <i class="fa-solid fa-envelope-open-text"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white">Digital Helpdesk</h3>
                    <p class="text-slate-400 text-xs sm:text-sm leading-relaxed">
                        support@urbanpulse.gov<br>control@urbanpulse.org
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER SECTION -->
    <footer class="bg-darkerBg text-slate-400 pt-16 pb-8 border-t border-slate-800 relative overflow-hidden">
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-3/4 h-[1px] bg-gradient-to-r from-transparent via-emeraldGreen to-transparent"></div>
        
        <div class="max-w-6xl mx-auto px-4 md:px-[6%] grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10 mb-12">
            <!-- Brand Column -->
            <div class="space-y-4">
                <a href="#home" class="text-2xl font-black text-white flex items-center gap-2.5">
                    <img src="assets/images/logo1.png" alt="UrbanPulse Logo" class="w-9 h-9 rounded-lg object-cover bg-slate-800 p-1 border border-slate-700">
                    <span>Urban<span class="text-emeraldGreen">Pulse</span></span>
                </a>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Transforming standard metropolitans into smart, connected, and sustainable eco-systems using next-generation data grids and citizen collaboration.
                </p>
                <div class="flex items-center gap-3 pt-2">
                    <a href="#" class="w-9 h-9 rounded-lg bg-cardBg border border-slate-800 flex items-center justify-center text-slate-300 hover:text-white hover:bg-emeraldGreen transition-all"><i class="fa-brands fa-facebook-f text-xs"></i></a>
                    <a href="#" class="w-9 h-9 rounded-lg bg-cardBg border border-slate-800 flex items-center justify-center text-slate-300 hover:text-white hover:bg-skyBlue transition-all"><i class="fa-brands fa-twitter text-xs"></i></a>
                    <a href="#" class="w-9 h-9 rounded-lg bg-cardBg border border-slate-800 flex items-center justify-center text-slate-300 hover:text-white hover:bg-emeraldGreen transition-all"><i class="fa-brands fa-linkedin-in text-xs"></i></a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="space-y-4">
                <h4 class="text-white text-sm font-bold uppercase tracking-wider border-l-2 border-emeraldGreen pl-3">Quick Navigation</h4>
                <ul class="space-y-2 text-xs">
                    <li><a href="#home" class="hover:text-emeraldGreen transition-colors flex items-center gap-2"><i class="fa-solid fa-angle-right text-[10px]"></i> Home Page</a></li>
                    <li><a href="#overview" class="hover:text-emeraldGreen transition-colors flex items-center gap-2"><i class="fa-solid fa-angle-right text-[10px]"></i> About System</a></li>
                    <li><a href="#workflow" class="hover:text-emeraldGreen transition-colors flex items-center gap-2"><i class="fa-solid fa-angle-right text-[10px]"></i> How It Works</a></li>
                    <li><a href="#city-services" class="hover:text-emeraldGreen transition-colors flex items-center gap-2"><i class="fa-solid fa-angle-right text-[10px]"></i> City Services</a></li>
                    <li><a href="#bulletins" class="hover:text-emeraldGreen transition-colors flex items-center gap-2"><i class="fa-solid fa-angle-right text-[10px]"></i> Public Bulletins</a></li>
                    <li><a href="#faq" class="hover:text-emeraldGreen transition-colors flex items-center gap-2"><i class="fa-solid fa-angle-right text-[10px]"></i> FAQ Knowledge Base</a></li>
                </ul>
            </div>

            <!-- Essential Services -->
            <div class="space-y-4">
                <h4 class="text-white text-sm font-bold uppercase tracking-wider border-l-2 border-skyBlue pl-3">Service Portals</h4>
                <ul class="space-y-2 text-xs">
                    <li><a href="<?php echo base_url('citizen/hospitals.php'); ?>" class="hover:text-skyBlue transition-colors flex items-center gap-2"><i class="fa-solid fa-angle-right text-[10px]"></i> Hospitals & ICU Beds</a></li>
                    <li><a href="<?php echo base_url('citizen/book_appointment.php'); ?>" class="hover:text-skyBlue transition-colors flex items-center gap-2"><i class="fa-solid fa-angle-right text-[10px]"></i> Book Medical Appointment</a></li>
                    <li><a href="<?php echo base_url('doctor/dashboard.php'); ?>" class="hover:text-skyBlue transition-colors flex items-center gap-2"><i class="fa-solid fa-angle-right text-[10px]"></i> Doctor Clinical Portal</a></li>
                    <li><a href="<?php echo base_url('citizen/my_fines.php'); ?>" class="hover:text-skyBlue transition-colors flex items-center gap-2"><i class="fa-solid fa-angle-right text-[10px]"></i> Traffic Fines Verification</a></li>
                    <li><a href="<?php echo base_url('citizen/my_bills.php'); ?>" class="hover:text-skyBlue transition-colors flex items-center gap-2"><i class="fa-solid fa-angle-right text-[10px]"></i> Energy & Utility Bills</a></li>
                    <li><a href="<?php echo base_url('citizen/complaints.php'); ?>" class="hover:text-skyBlue transition-colors flex items-center gap-2"><i class="fa-solid fa-angle-right text-[10px]"></i> File Civic Complaint</a></li>
                </ul>
            </div>

            <!-- Manager Portals -->
            <div class="space-y-4">
                <h4 class="text-white text-sm font-bold uppercase tracking-wider border-l-2 border-amber-400 pl-3">Manager Portals</h4>
                <ul class="space-y-2 text-xs">
                    <li><a href="<?php echo base_url('auth/login.php?role=central_admin'); ?>" class="hover:text-amber-400 transition-colors flex items-center gap-2"><i class="fa-solid fa-angle-right text-[10px]"></i> Central Manager Portal</a></li>
                    <li><a href="<?php echo base_url('auth/login.php?role=traffic_admin'); ?>" class="hover:text-amber-400 transition-colors flex items-center gap-2"><i class="fa-solid fa-angle-right text-[10px]"></i> Traffic Manager Portal</a></li>
                    <li><a href="<?php echo base_url('auth/login.php?role=hospital_admin'); ?>" class="hover:text-amber-400 transition-colors flex items-center gap-2"><i class="fa-solid fa-angle-right text-[10px]"></i> Hospital Manager Portal</a></li>
                    <li><a href="<?php echo base_url('auth/login.php?role=energy_admin'); ?>" class="hover:text-amber-400 transition-colors flex items-center gap-2"><i class="fa-solid fa-angle-right text-[10px]"></i> Energy Manager Portal</a></li>
                    <li><a href="<?php echo base_url('auth/login.php'); ?>" class="hover:text-amber-400 transition-colors flex items-center gap-2"><i class="fa-solid fa-angle-right text-[10px]"></i> Citizen Access Login</a></li>
                </ul>
            </div>
        </div>

        <div class="max-w-6xl mx-auto px-4 md:px-[6%] pt-8 border-t border-slate-800/80 flex flex-col sm:flex-row justify-between items-center text-xs text-slate-500 gap-4">
            <p>© 2026 UrbanPulse Smart City Management System. All rights reserved.</p>
            <div class="flex gap-6">
                <a href="#" class="hover:text-slate-300 transition-colors">Privacy Policy</a>
                <a href="#" class="hover:text-slate-300 transition-colors">Terms of Service</a>
                <a href="#" class="hover:text-slate-300 transition-colors">Emergency Protocol</a>
            </div>
        </div>
    </footer>

</body>
</html>