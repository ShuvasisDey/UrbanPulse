<?php
require_once __DIR__ . '/../includes/auth.php';
require_citizen();
require_once __DIR__ . '/../includes/table_config.php';
require_once __DIR__ . '/../includes/icons.php';

$citizen_id = (int) $_SESSION['ref_id'];
$message = '';
$error = '';

// Handle quick complaint submission from dashboard
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['quick_complaint'])) {
    $desc = trim($_POST['description'] ?? '');
    if ($desc !== '') {
        $maxRes = $conn->query("SELECT COALESCE(MAX(complaint_id),0)+1 AS next_id FROM Citizen_Complaints");
        $next_id = $maxRes->fetch_assoc()['next_id'];
        $stmt = $conn->prepare("INSERT INTO Citizen_Complaints (complaint_id, description, status, citizen_id) VALUES (?, ?, 'Pending', ?)");
        $stmt->bind_param('isi', $next_id, $desc, $citizen_id);
        $stmt->execute();
        $message = "Complaint filed successfully (#$next_id).";
    } else {
        $error = "Please enter a complaint description.";
    }
}

// Handle appointment cancellation from dashboard
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['cancel_appt_id'])) {
    $apptId = (int)$_POST['cancel_appt_id'];
    $stmt = $conn->prepare("UPDATE Hospital_Appointments SET status = 'Cancelled' WHERE appointment_id = ? AND citizen_id = ? AND status IN ('Pending','Confirmed')");
    $stmt->bind_param('ii', $apptId, $citizen_id);
    $stmt->execute();
    $message = "Appointment #$apptId cancelled.";
}

// 1. Fetch Citizen Profile
$stmt = $conn->prepare("SELECT * FROM Citizen WHERE citizen_id = ?");
$stmt->bind_param('i', $citizen_id);
$stmt->execute();
$citizen = $stmt->get_result()->fetch_assoc() ?: [];

// 2. Fetch Traffic Fines & Unpaid Total
$finesSql = "SELECT tf.fine_id, tf.amount, tf.payment_status, tv.type AS violation_type, veh.registration_no, veh.type AS vehicle_type
             FROM Traffic_Fines tf
             JOIN Traffic_Violations tv ON tf.violation_id = tv.violation_id
             JOIN Traffic_Vehicles veh ON tv.vehicle_id = veh.vehicle_id
             WHERE veh.citizen_id = ?
             ORDER BY tf.fine_id DESC";
$fStmt = $conn->prepare($finesSql);
$fStmt->bind_param('i', $citizen_id);
$fStmt->execute();
$finesRes = $fStmt->get_result();
$fines = [];
$totalFineAmount = 0;
$unpaidFineAmount = 0;
$unpaidFinesCount = 0;
while ($fRow = $finesRes->fetch_assoc()) {
    $fines[] = $fRow;
    $totalFineAmount += (float)$fRow['amount'];
    if (strtolower($fRow['payment_status']) !== 'paid') {
        $unpaidFineAmount += (float)$fRow['amount'];
        $unpaidFinesCount++;
    }
}

// 3. Fetch Energy Bills & Dues
$billsSql = "SELECT eb.bill_id, eb.amount, eb.payment_status, eu.units, eu.month, ea.meter_no, ea.connection_type
             FROM Energy_Bill eb
             JOIN Energy_Usage eu ON eb.usage_id = eu.usage_id
             JOIN Energy_Account ea ON eu.account_id = ea.account_id
             WHERE ea.citizen_id = ?
             ORDER BY eb.bill_id DESC";
$bStmt = $conn->prepare($billsSql);
$bStmt->bind_param('i', $citizen_id);
$bStmt->execute();
$billsRes = $bStmt->get_result();
$bills = [];
$totalBillAmount = 0;
$unpaidBillAmount = 0;
$unpaidBillsCount = 0;
while ($bRow = $billsRes->fetch_assoc()) {
    $bills[] = $bRow;
    $totalBillAmount += (float)$bRow['amount'];
    if (strtolower($bRow['payment_status']) !== 'paid') {
        $unpaidBillAmount += (float)$bRow['amount'];
        $unpaidBillsCount++;
    }
}

// 4. Fetch Appointments
$apptsSql = "SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.status,
                    d.name AS doctor_name, d.specialization, h.name AS hospital
             FROM Hospital_Appointments a
             JOIN Doctors d ON a.doctor_id = d.doctor_id
             JOIN Hospitals h ON d.hospital_id = h.hospital_id
             WHERE a.citizen_id = ?
             ORDER BY a.appointment_date DESC, a.appointment_id DESC";
$aStmt = $conn->prepare($apptsSql);
$aStmt->bind_param('i', $citizen_id);
$aStmt->execute();
$apptsRes = $aStmt->get_result();
$appointments = [];
$activeAppointmentsCount = 0;
while ($aRow = $apptsRes->fetch_assoc()) {
    $appointments[] = $aRow;
    if (in_array($aRow['status'], ['Pending', 'Confirmed'], true)) {
        $activeAppointmentsCount++;
    }
}

// 5. Fetch Complaints
$compSql = "SELECT * FROM Citizen_Complaints WHERE citizen_id = ? ORDER BY complaint_id DESC";
$cStmt = $conn->prepare($compSql);
$cStmt->bind_param('i', $citizen_id);
$cStmt->execute();
$compRes = $cStmt->get_result();
$complaints = [];
$pendingComplaintsCount = 0;
while ($cRow = $compRes->fetch_assoc()) {
    $complaints[] = $cRow;
    if (strtolower($cRow['status']) !== 'resolved') {
        $pendingComplaintsCount++;
    }
}

// 6. Fetch live hospital capacity summary
$hospCapSql = "SELECT h.name, h.location, h.capacity,
              (SELECT COUNT(*) FROM Patients p JOIN Doctors d ON p.doctor_id = d.doctor_id WHERE d.hospital_id = h.hospital_id AND p.status <> 'Discharged') AS occupied
              FROM Hospitals h ORDER BY h.name LIMIT 4";
$hospCapRes = $conn->query($hospCapSql);
$hospitalCapacities = [];
if ($hospCapRes) {
    while ($hcRow = $hospCapRes->fetch_assoc()) {
        $hospitalCapacities[] = $hcRow;
    }
}

function status_badge($status) {
    $s = strtolower($status);
    $cls = ($s === 'paid' || $s === 'resolved' || $s === 'confirmed') ? 'badge-paid' :
           (($s === 'unpaid' || $s === 'cancelled') ? 'badge-unpaid' :
           (($s === 'pending') ? 'badge-pending' : 'badge-progress'));
    return "<span class='badge $cls'>" . htmlspecialchars($status) . "</span>";
}

$page_title = "Citizen Portal";
include __DIR__ . '/../includes/header.php';

$citBadge = '<span class="badge badge-paid" style="font-size:0.825rem;padding:6px 14px;"><i class="fa-solid fa-circle-check"></i> Account Verified</span>';
$citSub = 'Citizen ID: #' . $citizen_id . ' • Email: ' . ($citizen['email'] ?? 'Not set') . ' • Contact: ' . ($citizen['contact_no'] ?? 'Not set');
page_banner('citizen', 'Welcome, ' . ($citizen['name'] ?? $_SESSION['username']), $citSub, $citBadge);
?>

<?php if ($message): ?><div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo e($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo e($error); ?></div><?php endif; ?>

<!-- 4 Key Financial & Service KPI Cards -->
<div class="stat-grid">
  <!-- Traffic Fines -->
  <a class="stat-card" href="my_fines.php">
    <div class="stat-header">
      <span class="stat-icon" style="color:var(--sky);"><i class="fa-solid fa-traffic-light"></i></span>
      <span class="stat-trend" style="<?php echo $unpaidFinesCount > 0 ? 'background:rgba(244,63,94,0.15);color:#fb7185;border-color:rgba(244,63,94,0.3);' : ''; ?>">
        <?php echo $unpaidFinesCount > 0 ? $unpaidFinesCount . ' Unpaid' : 'All Clear'; ?>
      </span>
    </div>
    <div class="stat-body">
      <div class="num"><?php echo count($fines); ?></div>
      <div class="label">Traffic Violations & Fines</div>
      <div class="hint" style="margin-top:6px; color: <?php echo $unpaidFineAmount > 0 ? '#fb7185' : 'var(--emerald)'; ?>;">
        Due: ৳<?php echo number_format($unpaidFineAmount, 2); ?>
      </div>
    </div>
  </a>

  <!-- Energy Bills -->
  <a class="stat-card" href="my_bills.php">
    <div class="stat-header">
      <span class="stat-icon" style="color:var(--amber);"><i class="fa-solid fa-bolt"></i></span>
      <span class="stat-trend" style="<?php echo $unpaidBillsCount > 0 ? 'background:rgba(245,158,11,0.15);color:#fbbf24;border-color:rgba(245,158,11,0.3);' : ''; ?>">
        <?php echo $unpaidBillsCount > 0 ? $unpaidBillsCount . ' Pending Bill' : 'Paid Up'; ?>
      </span>
    </div>
    <div class="stat-body">
      <div class="num"><?php echo count($bills); ?></div>
      <div class="label">Utility & Energy Bills</div>
      <div class="hint" style="margin-top:6px; color: <?php echo $unpaidBillAmount > 0 ? '#fbbf24' : 'var(--emerald)'; ?>;">
        Due: ৳<?php echo number_format($unpaidBillAmount, 2); ?>
      </div>
    </div>
  </a>

  <!-- Medical Appointments -->
  <a class="stat-card" href="book_appointment.php">
    <div class="stat-header">
      <span class="stat-icon" style="color:var(--emerald);"><i class="fa-solid fa-calendar-check"></i></span>
      <span class="stat-trend">
        <?php echo $activeAppointmentsCount; ?> Active
      </span>
    </div>
    <div class="stat-body">
      <div class="num"><?php echo count($appointments); ?></div>
      <div class="label">Hospital Appointments</div>
      <div class="hint" style="margin-top:6px; color: var(--sky);">
        <?php echo $activeAppointmentsCount > 0 ? 'Upcoming Scheduled' : 'No upcoming visits'; ?>
      </div>
    </div>
  </a>

  <!-- Complaints & Grievance -->
  <a class="stat-card" href="complaints.php">
    <div class="stat-header">
      <span class="stat-icon" style="color:var(--rose);"><i class="fa-solid fa-circle-exclamation"></i></span>
      <span class="stat-trend" style="<?php echo $pendingComplaintsCount > 0 ? 'background:rgba(245,158,11,0.15);color:#fbbf24;' : ''; ?>">
        <?php echo $pendingComplaintsCount; ?> In Review
      </span>
    </div>
    <div class="stat-body">
      <div class="num"><?php echo count($complaints); ?></div>
      <div class="label">Civic Complaints</div>
      <div class="hint" style="margin-top:6px; color: var(--text-muted);">
        <?php echo (count($complaints) - $pendingComplaintsCount); ?> Resolved
      </div>
    </div>
  </a>
</div>

<!-- SECTION 1: HEALTHCARE & HOSPITAL APPOINTMENTS -->
<div class="card">
  <div class="card-header" style="border:none; padding:0; margin-bottom:18px;">
    <h3><i class="fa-solid fa-hospital text-emerald" style="color:var(--emerald);margin-right:8px;"></i> Healthcare & Doctor Appointments</h3>
    <div style="display:flex;gap:10px;">
      <a href="book_appointment.php" class="btn btn-sm"><i class="fa-solid fa-plus"></i> New Appointment</a>
      <a href="hospitals.php" class="btn btn-secondary btn-sm"><i class="fa-solid fa-bed"></i> Bed Availability</a>
    </div>
  </div>

  <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px; margin-bottom: 20px;">
    <?php foreach ($hospitalCapacities as $hc): 
      $freeBeds = max(0, (int)$hc['capacity'] - (int)$hc['occupied']);
    ?>
      <div style="background: rgba(255,255,255,0.7); border: 1px solid rgba(203,213,225,0.7); padding: 14px 16px; border-radius: 14px;">
        <div style="display:flex;justify-content:space-between;align-items:center;">
          <strong style="color:var(--text-main); font-weight:700;"><?php echo e($hc['name']); ?></strong>
          <span class="badge <?php echo $freeBeds > 0 ? 'badge-paid' : 'badge-unpaid'; ?>"><?php echo $freeBeds; ?> Beds Free</span>
        </div>
        <div class="hint" style="margin-top:4px;"><i class="fa-solid fa-location-dot" style="font-size:10px;"></i> <?php echo e($hc['location']); ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <h4 style="color:var(--text-main); font-size: 0.95rem; margin-bottom: 12px; font-weight:700;">My Booked Appointments</h4>
  <div class="table-wrap" style="margin-bottom:0;">
    <table>
      <thead>
        <tr>
          <th>Doctor</th>
          <th>Hospital</th>
          <th>Date</th>
          <th>Time Slot</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($appointments)): ?>
          <tr><td colspan="6" style="text-align:center;color:var(--text-muted);">No appointments booked yet. Click "New Appointment" to book with a specialist.</td></tr>
        <?php else: ?>
          <?php foreach (array_slice($appointments, 0, 5) as $appt): ?>
            <tr>
              <td><strong><?php echo e($appt['doctor_name']); ?></strong> <span class="hint">(<?php echo e($appt['specialization']); ?>)</span></td>
              <td><?php echo e($appt['hospital']); ?></td>
              <td><?php echo e($appt['appointment_date']); ?></td>
              <td><?php echo e($appt['appointment_time']); ?></td>
              <td><?php echo status_badge($appt['status']); ?></td>
              <td>
                <?php if (in_array($appt['status'], ['Pending', 'Confirmed'], true)): ?>
                  <form method="post" style="display:inline" onsubmit="return confirm('Cancel this appointment?');">
                    <input type="hidden" name="cancel_appt_id" value="<?php echo (int)$appt['appointment_id']; ?>">
                    <button type="submit" class="btn btn-sm btn-danger">Cancel</button>
                  </form>
                <?php else: ?>
                  <span class="hint">—</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- SECTION 2: UTILITIES & TRAFFIC DUAL OVERVIEW -->
<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 24px; margin-bottom: 24px;">
  <!-- Energy Bills Snapshot -->
  <div class="card" style="margin-bottom:0;">
    <div class="card-header" style="border:none; padding:0; margin-bottom:16px;">
      <h3><i class="fa-solid fa-bolt" style="color:var(--amber);margin-right:8px;"></i> Energy & Utility Accounts</h3>
      <a href="my_bills.php" class="btn btn-secondary btn-sm">Full History</a>
    </div>
    <div class="table-wrap" style="margin-bottom:0;">
      <table>
        <thead>
          <tr>
            <th>Meter</th>
            <th>Month</th>
            <th>Units</th>
            <th>Amount</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($bills)): ?>
            <tr><td colspan="5" style="text-align:center;color:var(--text-muted);">No utility bills found.</td></tr>
          <?php else: ?>
            <?php foreach (array_slice($bills, 0, 4) as $bill): ?>
              <tr>
                <td><?php echo e($bill['meter_no']); ?></td>
                <td><?php echo e($bill['month']); ?></td>
                <td><?php echo e($bill['units']); ?> kWh</td>
                <td>৳<?php echo number_format($bill['amount'], 2); ?></td>
                <td><?php echo status_badge($bill['payment_status']); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Traffic Fines Snapshot -->
  <div class="card" style="margin-bottom:0;">
    <div class="card-header" style="border:none; padding:0; margin-bottom:16px;">
      <h3><i class="fa-solid fa-traffic-light" style="color:var(--sky);margin-right:8px;"></i> Traffic Violations & Fines</h3>
      <a href="my_fines.php" class="btn btn-secondary btn-sm">Full History</a>
    </div>
    <div class="table-wrap" style="margin-bottom:0;">
      <table>
        <thead>
          <tr>
            <th>Vehicle</th>
            <th>Violation</th>
            <th>Amount</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($fines)): ?>
            <tr><td colspan="4" style="text-align:center;color:var(--text-muted);">No violations on record. Safe driving!</td></tr>
          <?php else: ?>
            <?php foreach (array_slice($fines, 0, 4) as $fine): ?>
              <tr>
                <td><?php echo e($fine['registration_no']); ?></td>
                <td><?php echo e($fine['violation_type']); ?></td>
                <td>৳<?php echo number_format($fine['amount'], 2); ?></td>
                <td><?php echo status_badge($fine['payment_status']); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- SECTION 3: QUICK GRIEVANCE & CITIZEN FEEDBACK -->
<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 24px;">
  <!-- Fast Complaint Form -->
  <div class="card" style="margin-bottom:0;">
    <div class="card-header" style="border:none; padding:0; margin-bottom:14px;">
      <h3><i class="fa-solid fa-bullhorn" style="color:var(--rose);margin-right:8px;"></i> File a Quick Complaint</h3>
      <a href="complaints.php" class="btn btn-secondary btn-sm">All Complaints</a>
    </div>
    <p>Report damaged roads, broken streetlights, water pipeline issues, or public hazards to municipal management.</p>
    <form method="post">
      <div class="form-group">
        <textarea name="description" placeholder="Describe the municipal issue, location, and severity..." required style="min-height:90px;"></textarea>
      </div>
      <button type="submit" name="quick_complaint" value="1" class="btn" style="width:100%;">
        <i class="fa-solid fa-paper-plane"></i> Submit to City Desk
      </button>
    </form>
  </div>

  <!-- Recent Complaints List -->
  <div class="card" style="margin-bottom:0;">
    <div class="card-header" style="border:none; padding:0; margin-bottom:14px;">
      <h3><i class="fa-solid fa-list-check" style="color:var(--emerald);margin-right:8px;"></i> Track My Complaints</h3>
      <a href="feedback.php" class="btn btn-secondary btn-sm">Leave Feedback</a>
    </div>
    <div class="table-wrap" style="margin-bottom:0;">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Issue Description</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($complaints)): ?>
            <tr><td colspan="3" style="text-align:center;color:var(--text-muted);">No complaints filed yet.</td></tr>
          <?php else: ?>
            <?php foreach (array_slice($complaints, 0, 4) as $comp): ?>
              <tr>
                <td>#<?php echo (int)$comp['complaint_id']; ?></td>
                <td><?php echo e(mb_strimwidth($comp['description'], 0, 45, '...')); ?></td>
                <td><?php echo status_badge($comp['status']); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
