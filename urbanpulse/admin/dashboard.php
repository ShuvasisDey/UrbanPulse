<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../includes/table_config.php';
require_once __DIR__ . '/../includes/icons.php';

function count_rows($conn, $table) {
    $r = $conn->query("SELECT COUNT(*) c FROM `$table`");
    return $r ? (int)$r->fetch_assoc()['c'] : 0;
}

$module = admin_module(); // null for central manager = show everything

$allStats = [
    'central'  => [
        'Citizens' => ['table' => 'Citizen', 'icon' => 'fa-solid fa-users', 'trend' => '+12%'],
        'Complaints' => ['table' => 'Citizen_Complaints', 'icon' => 'fa-solid fa-comments', 'trend' => 'Active'],
        'Feedback' => ['table' => 'Citizen_Feedback', 'icon' => 'fa-solid fa-comment-dots', 'trend' => '+5%']
    ],
    'traffic'  => [
        'Vehicles' => ['table' => 'Traffic_Vehicles', 'icon' => 'fa-solid fa-car', 'trend' => '+8%'],
        'Violations' => ['table' => 'Traffic_Violations', 'icon' => 'fa-solid fa-triangle-exclamation', 'trend' => 'Monitored'],
        'Fines' => ['table' => 'Traffic_Fines', 'icon' => 'fa-solid fa-receipt', 'trend' => 'Settling']
    ],
    'energy'   => [
        'Energy Accounts' => ['table' => 'Energy_Account', 'icon' => 'fa-solid fa-bolt', 'trend' => '+4%'],
        'Usage Records' => ['table' => 'Energy_Usage', 'icon' => 'fa-solid fa-chart-line', 'trend' => 'Monthly'],
        'Utility Bills' => ['table' => 'Energy_Bill', 'icon' => 'fa-solid fa-file-invoice-dollar', 'trend' => 'Active']
    ],
    'hospital' => [
        'Hospitals' => ['table' => 'Hospitals', 'icon' => 'fa-solid fa-hospital', 'trend' => 'Active'],
        'Doctors' => ['table' => 'Doctors', 'icon' => 'fa-solid fa-user-doctor', 'trend' => 'Licensed'],
        'Patients' => ['table' => 'Patients', 'icon' => 'fa-solid fa-bed-pulse', 'trend' => 'In-care'],
        'Appointments' => ['table' => 'Hospital_Appointments', 'icon' => 'fa-solid fa-calendar-check', 'trend' => '+6%']
    ],
];

$stats = [];
foreach ($allStats as $mod => $rows) {
    if ($module !== null && $module !== $mod) continue;
    foreach ($rows as $label => $data) {
        $stats[$label] = [
            'count' => count_rows($conn, $data['table']),
            'icon'  => $data['icon'],
            'trend' => $data['trend']
        ];
    }
}

$pendingCount = 0;
if (is_central_admin()) {
    $pendingRes = $conn->query("SELECT COUNT(*) c FROM Citizen_Registration_Requests WHERE status = 'Pending'");
    if ($pendingRes) {
        $pendingCount = (int)$pendingRes->fetch_assoc()['c'];
        $stats['Pending Signups'] = [
            'count' => $pendingCount,
            'icon'  => 'fa-solid fa-user-clock',
            'trend' => 'Action Needed'
        ];
    }
}

$bannerIcon = ['central' => 'admin', 'traffic' => 'traffic', 'energy' => 'energy', 'hospital' => 'hospital'][$module ?? 'central'] ?? 'admin';
$bannerTitle = is_central_admin() ? 'Central Manager Dashboard' : role_label(current_role()) . ' Dashboard';
$bannerSubtitle = is_central_admin() ? 'Full system control, department coordination, and data management' : 'Scoped to the ' . ucfirst(e($module)) . ' department';

$page_title = "Manager Dashboard";
include __DIR__ . '/../includes/header.php';

$actionBtns = '';
if (is_central_admin()) {
    $actionBtns = '<a href="citizen_requests.php" class="btn ' . ($pendingCount > 0 ? '' : 'btn-secondary') . '"><i class="fa-solid fa-user-check"></i> Sign-up Requests (' . $pendingCount . ')</a>' .
                  '<a href="manage.php?table=Central_Notifications&action=add" class="btn btn-secondary"><i class="fa-solid fa-bullhorn"></i> New Bulletin</a>';
}
page_banner($bannerIcon, $bannerTitle, $bannerSubtitle, $actionBtns);
?>

<!-- Pending Citizen Sign-up Alert for Central Manager -->
<?php if (is_central_admin() && $pendingCount > 0): ?>
  <div class="alert alert-error" style="background: rgba(245, 158, 11, 0.15); border-color: rgba(245, 158, 11, 0.4); color: #fbbf24; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
    <div style="display: flex; align-items: center; gap: 10px;">
      <i class="fa-solid fa-bell" style="font-size: 1.2rem;"></i>
      <span><strong>Attention Manager:</strong> You have <strong><?php echo $pendingCount; ?></strong> pending citizen registration request(s) awaiting approval.</span>
    </div>
    <a href="citizen_requests.php" class="btn btn-sm" style="background: #f59e0b; color: #000 !important; font-weight: 800;">
      Review Requests Now
    </a>
  </div>
<?php endif; ?>

<!-- Stats Overview Grid -->
<div class="stat-grid">
  <?php foreach ($stats as $label => $info): ?>
    <div class="stat-card">
      <div class="stat-header">
        <span class="stat-icon"><i class="<?php echo $info['icon']; ?>"></i></span>
        <span class="stat-trend" style="<?php echo $label === 'Pending Signups' && $info['count'] > 0 ? 'background:rgba(244,63,94,0.15);color:#fb7185;border-color:rgba(244,63,94,0.3);' : ''; ?>">
          <?php echo $info['trend']; ?>
        </span>
      </div>
      <div class="stat-body">
        <div class="num"><?php echo (int)$info['count']; ?></div>
        <div class="label"><?php echo e($label); ?></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<h2 class="section-title">Department Management Portals</h2>

<!-- Categorized Module Grid -->
<div class="module-grid">

  <?php if ($module === null): ?>
  <!-- City & Core Administration -->
  <div class="module-card">
    <div class="card-header">
      <h3><i class="fa-solid fa-building-columns" style="color:var(--sky);"></i> City Governance</h3>
      <span class="badge badge-info">Core</span>
    </div>
    <ul>
      <li><a href="citizen_requests.php">Citizen Sign-up Queue <?php echo $pendingCount > 0 ? "<span class='badge badge-unpaid' style='padding:2px 8px;font-size:10px;'>$pendingCount New</span>" : ""; ?></a></li>
      <li><a href="manage.php?table=Central_Admin">Central Managers</a></li>
      <li><a href="manage.php?table=Central_Notifications">Public Bulletins & Notices</a></li>
      <li><a href="manage.php?table=Central_Reports">Department Reports</a></li>
    </ul>
  </div>

  <!-- Citizens & Grievance -->
  <div class="module-card">
    <div class="card-header">
      <h3><i class="fa-solid fa-users" style="color:var(--emerald);"></i> Citizens & Grievances</h3>
      <span class="badge badge-paid">Public</span>
    </div>
    <ul>
      <li><a href="manage.php?table=Citizen">Citizen Directory</a></li>
      <li><a href="manage.php?table=Citizen_Complaints">Public Complaints Log</a></li>
      <li><a href="manage.php?table=Citizen_Feedback">Citizen Feedback & Ideas</a></li>
    </ul>
  </div>
  <?php endif; ?>

  <?php if ($module === null || $module === 'traffic'): ?>
  <!-- Traffic & Transport -->
  <div class="module-card">
    <div class="card-header">
      <h3><i class="fa-solid fa-traffic-light" style="color:var(--sky);"></i> Traffic Department</h3>
      <span class="badge badge-info">Transport</span>
    </div>
    <ul>
      <?php if ($module === null): ?><li><a href="manage.php?table=Traffic_Admin">Traffic Managers</a></li><?php endif; ?>
      <li><a href="manage.php?table=Traffic_Vehicles">Vehicle Registrations</a></li>
      <li><a href="manage.php?table=Traffic_Violations">Traffic Violations</a></li>
      <li><a href="manage.php?table=Traffic_Fines">Fine Payments & Status</a></li>
    </ul>
  </div>
  <?php endif; ?>

  <?php if ($module === null || $module === 'energy'): ?>
  <!-- Energy & Utilities -->
  <div class="module-card">
    <div class="card-header">
      <h3><i class="fa-solid fa-bolt" style="color:var(--amber);"></i> Energy Department</h3>
      <span class="badge badge-warning">Utilities</span>
    </div>
    <ul>
      <?php if ($module === null): ?><li><a href="manage.php?table=Energy_Admin">Energy Managers</a></li><?php endif; ?>
      <li><a href="manage.php?table=Energy_Account">Meter Accounts</a></li>
      <li><a href="manage.php?table=Energy_Usage">Monthly Meter Readings</a></li>
      <li><a href="manage.php?table=Energy_Bill">Utility Billing Ledger</a></li>
    </ul>
  </div>
  <?php endif; ?>

  <?php if ($module === null || $module === 'hospital'): ?>
  <!-- Healthcare & Medical Network -->
  <div class="module-card">
    <div class="card-header">
      <h3><i class="fa-solid fa-hospital" style="color:var(--emerald);"></i> Healthcare Logistics</h3>
      <span class="badge badge-paid">Medical</span>
    </div>
    <ul>
      <?php if ($module === null): ?><li><a href="manage.php?table=Hospital_Admin">Hospital Managers</a></li><?php endif; ?>
      <li><a href="manage.php?table=Hospitals">Hospital Centers & Beds</a></li>
      <li><a href="manage.php?table=Doctors">Medical Doctors Directory</a></li>
      <li><a href="manage.php?table=Patients">Admitted Patients</a></li>
      <li><a href="manage.php?table=Hospital_Appointments">Patient Appointments</a></li>
    </ul>
  </div>
  <?php endif; ?>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>