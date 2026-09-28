
<?php
require_once __DIR__ . '/session.php';

// Roles: admin (Central Admin, full access), traffic_admin, energy_admin,
// hospital_admin (each scoped to their own module), citizen.
const ADMIN_ROLES = ['admin', 'traffic_admin', 'energy_admin', 'hospital_admin'];

// Maps a login role to which table_config.php "module" it may manage.
// Central 'admin' is not in this map because it can access every module.
const ROLE_MODULE_MAP = [
    'traffic_admin'  => 'traffic',
    'energy_admin'   => 'energy',
    'hospital_admin' => 'hospital',
];

// Maps a login role to the data table its ref_id points into.
const ROLE_REF_TABLE = [
    'admin'          => 'Central_Admin',
    'traffic_admin'  => 'Traffic_Admin',
    'energy_admin'   => 'Energy_Admin',
    'hospital_admin' => 'Hospital_Admin',
    'citizen'        => 'Citizen',
    'doctor'         => 'Doctors',
];

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function current_role() {
    return $_SESSION['role'] ?? null;
}

function is_admin_role($role = null) {
    $role = $role ?? current_role();
    return in_array($role, ADMIN_ROLES, true);
}

function is_central_admin() {
    return current_role() === 'admin';
}

// Which table_config.php "module" the current admin may manage.
// Central admin gets null, meaning "all modules" (checked separately).
function admin_module() {
    return ROLE_MODULE_MAP[current_role()] ?? null;
}

function can_manage_table($tableConfig) {
    if (is_central_admin()) return true;
    if (!is_admin_role()) return false;
    return admin_module() !== null && admin_module() === ($tableConfig['module'] ?? null);
}

function require_login() {
    if (!is_logged_in()) {
        header("Location: " . base_url("auth/login.php"));
        exit;
    }
}

// Any admin-type role (central or module-scoped) may pass this gate;
// individual pages/tables still need can_manage_table() checks.
function require_admin() {
    require_login();
    if (!is_admin_role()) {
        header("Location: " . base_url("citizen/dashboard.php"));
        exit;
    }
}

function require_central_admin() {
    require_login();
    if (!is_central_admin()) {
        header("Location: " . base_url("admin/dashboard.php"));
        exit;
    }
}

function require_citizen() {
    require_login();
    if (current_role() !== 'citizen') {
        header("Location: " . base_url(is_doctor() ? "doctor/dashboard.php" : "admin/dashboard.php"));
        exit;
    }
}

function is_doctor() {
    return current_role() === 'doctor';
}

function require_doctor() {
    require_login();
    if (!is_doctor()) {
        header("Location: " . base_url(is_admin_role() ? "admin/dashboard.php" : "citizen/dashboard.php"));
        exit;
    }
}

// Computes a path relative to the project root regardless of which
// subfolder (auth/, admin/, citizen/) the current script lives in.
function base_url($path = '') {
    return "/urbanpulse/" . ltrim($path, '/');
}

function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function role_label($role) {
    $labels = [
        'admin' => 'Central Manager',
        'traffic_admin' => 'Traffic Manager',
        'energy_admin' => 'Energy Manager',
        'hospital_admin' => 'Hospital Manager',
        'citizen' => 'Citizen',
        'doctor' => 'Medical Doctor',
    ];
    return $labels[$role] ?? $role;
}
