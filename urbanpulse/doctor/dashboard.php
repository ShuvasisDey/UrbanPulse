<?php
require_once __DIR__ . '/../includes/auth.php';
require_doctor();
require_once __DIR__ . '/../includes/icons.php';

$doctor_id = (int)$_SESSION['ref_id'];
$message = '';
$error = '';

// Handle quick appointment status update
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['update_appt_status'])) {
    $apptId = (int)$_POST['appt_id'];
    $newStatus = trim($_POST['status'] ?? '');
    if (in_array($newStatus, ['Pending', 'Confirmed', 'Completed', 'Cancelled'], true)) {
        $stmt = $conn->prepare("UPDATE Hospital_Appointments SET status = ? WHERE appointment_id = ? AND doctor_id = ?");
        $stmt->bind_param('sii', $newStatus, $apptId, $doctor_id);
        $stmt->execute();
        $message = "Appointment #$apptId status updated to '$newStatus'.";
    }
}

// Handle patient status update
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['update_patient_status'])) {
    $patientId = (int)$_POST['patient_id'];
    $newStatus = trim($_POST['patient_status'] ?? '');
    if (in_array($newStatus, ['Stable', 'Recovering', 'Critical', 'Under Treatment', 'Discharged'], true)) {
        $stmt = $conn->prepare("UPDATE Patients SET status = ? WHERE patient_id = ? AND doctor_id = ?");
        $stmt->bind_param('sii', $newStatus, $patientId, $doctor_id);
        $stmt->execute();
        $message = "Patient #$patientId condition updated to '$newStatus'.";
    }
}

// 1. Fetch Doctor Profile & Hospital Details
$docSql = "SELECT d.*, h.name AS hospital_name, h.location AS hospital_location, h.capacity AS hospital_capacity
           FROM Doctors d
           JOIN Hospitals h ON d.hospital_id = h.hospital_id
           WHERE d.doctor_id = ?";
$dStmt = $conn->prepare($docSql);
$dStmt->bind_param('i', $doctor_id);
$dStmt->execute();
$doctor = $dStmt->get_result()->fetch_assoc() ?: [];

// 2. Fetch Appointments for this Doctor
$apptSql = "SELECT a.*, c.name AS patient_name, c.contact_no, c.email, c.age, c.gender
            FROM Hospital_Appointments a
            JOIN Citizen c ON a.citizen_id = c.citizen_id
            WHERE a.doctor_id = ?
            ORDER BY a.appointment_date DESC, a.appointment_time ASC";
$aStmt = $conn->prepare($apptSql);
$aStmt->bind_param('i', $doctor_id);
$aStmt->execute();
$aRes = $aStmt->get_result();
$appointments = [];
$pendingCount = 0;
$confirmedCount = 0;
$completedCount = 0;
while ($row = $aRes->fetch_assoc()) {
    $appointments[] = $row;
    if ($row['status'] === 'Pending') $pendingCount++;
    elseif ($row['status'] === 'Confirmed') $confirmedCount++;
    elseif ($row['status'] === 'Completed') $completedCount++;
}

// 3. Fetch In-Patients Assigned to this Doctor
$patSql = "SELECT p.*, c.name AS citizen_name, c.contact_no, c.age, c.gender
           FROM Patients p
           JOIN Citizen c ON p.citizen_id = c.citizen_id
           WHERE p.doctor_id = ?
           ORDER BY p.patient_id DESC";
$pStmt = $conn->prepare($patSql);
$pStmt->bind_param('i', $doctor_id);
$pStmt->execute();
$pRes = $pStmt->get_result();
$patients = [];
$activePatients = 0;
while ($row = $pRes->fetch_assoc()) {
    $patients[] = $row;
    if ($row['status'] !== 'Discharged') $activePatients++;
}

// Salary Calculations
$baseSalary = (float)($doctor['salary'] ?? 80000.00);
$consultationBonus = $completedCount * 500.00; // ৳500 per completed appointment
$totalEarnings = $baseSalary + $consultationBonus;

function appt_badge($status) {
    $cls = ['Confirmed' => 'badge-paid', 'Pending' => 'badge-pending', 'Cancelled' => 'badge-unpaid', 'Completed' => 'badge-resolved'][$status] ?? 'badge-progress';
    return "<span class='badge $cls'>" . htmlspecialchars($status) . "</span>";
}

function patient_badge($status) {
    $cls = ['Stable' => 'badge-paid', 'Recovering' => 'badge-info', 'Under Treatment' => 'badge-pending', 'Critical' => 'badge-unpaid', 'Discharged' => 'badge-resolved'][$status] ?? 'badge-progress';
    return "<span class='badge $cls'>" . htmlspecialchars($status) . "</span>";
}

$page_title = "Doctor Portal";
include __DIR__ . '/../includes/header.php';

$docBadge = '<span class="badge badge-paid" style="font-size:0.825rem;padding:6px 14px;"><i class="fa-solid fa-certificate"></i> Certified Medical Practitioner</span>';
$docSub = 'Specialization: ' . e($doctor['specialization']) . ' • ' . e($doctor['hospital_name']) . ' (' . e($doctor['hospital_location']) . ') • ID: #DOC-' . $doctor_id;
page_banner('doctor', $doctor['name'], $docSub, $docBadge);
?>

<?php if ($message): ?><div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo e($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo e($error); ?></div><?php endif; ?>

<!-- 4 Key Doctor Metrics -->
<div class="stat-grid">
  <!-- Total Appointments -->
  <a class="stat-card" href="appointments.php">
    <div class="stat-header">
      <span class="stat-icon" style="color:var(--sky);"><i class="fa-solid fa-calendar-check"></i></span>
      <span class="stat-trend" style="<?php echo $pendingCount > 0 ? 'background:rgba(245,158,11,0.15);color:#fbbf24;border-color:rgba(245,158,11,0.3);' : ''; ?>">
        <?php echo $pendingCount > 0 ? "$pendingCount Pending Review" : "Up to date"; ?>
      </span>
    </div>
    <div class="stat-body">
      <div class="num"><?php echo count($appointments); ?></div>
      <div class="label">Total Appointments Logged</div>
      <div class="hint" style="margin-top:6px; color:var(--emerald);">
        <?php echo $confirmedCount; ?> Confirmed &bull; <?php echo $completedCount; ?> Completed
      </div>
    </div>
  </a>

  <!-- In-Patient Roster -->
  <a class="stat-card" href="patients.php">
    <div class="stat-header">
      <span class="stat-icon" style="color:var(--emerald);"><i class="fa-solid fa-bed-pulse"></i></span>
      <span class="stat-trend">Hospital Ward</span>
    </div>
    <div class="stat-body">
      <div class="num"><?php echo count($patients); ?></div>
      <div class="label">Assigned In-Patients</div>
      <div class="hint" style="margin-top:6px; color:var(--sky);">
        <?php echo $activePatients; ?> Currently Under Active Care
      </div>
    </div>
  </a>

  <!-- Monthly Salary -->
  <a class="stat-card" href="salary.php">
    <div class="stat-header">
      <span class="stat-icon" style="color:var(--amber);"><i class="fa-solid fa-money-check-dollar"></i></span>
      <span class="stat-trend" style="background:rgba(16,185,129,0.15);color:#34d399;">Disbursed by Hospital</span>
    </div>
    <div class="stat-body">
      <div class="num" style="color:var(--amber);">৳<?php echo number_format($baseSalary, 2); ?></div>
      <div class="label">Monthly Base Salary</div>
      <div class="hint" style="margin-top:6px; color:var(--text-muted);">
        Set by Hospital Management
      </div>
    </div>
  </a>

  <!-- Total Earnings & Bonuses -->
  <a class="stat-card" href="salary.php">
    <div class="stat-header">
      <span class="stat-icon" style="color:var(--emerald);"><i class="fa-solid fa-wallet"></i></span>
      <span class="stat-trend">Gross Total</span>
    </div>
    <div class="stat-body">
      <div class="num" style="color:var(--emerald);">৳<?php echo number_format($totalEarnings, 2); ?></div>
      <div class="label">Total Gross Earnings</div>
      <div class="hint" style="margin-top:6px; color:var(--sky);">
        Incl. ৳<?php echo number_format($consultationBonus, 2); ?> Consultation Bonus
      </div>
    </div>
  </a>
</div>

<!-- SECTION 1: APPOINTMENTS SCHEDULE & CLINICAL ACTIONS -->
<div class="card">
  <div class="card-header" style="border:none; padding:0; margin-bottom:16px;">
    <h3><i class="fa-solid fa-calendar-days" style="color:var(--sky);margin-right:8px;"></i> Scheduled Patient Appointments</h3>
    <a href="appointments.php" class="btn btn-secondary btn-sm">Full Appointments Log</a>
  </div>

  <div class="table-wrap" style="margin-bottom:0;">
    <table>
      <thead>
        <tr>
          <th>Patient Name</th>
          <th>Demographics</th>
          <th>Contact Phone</th>
          <th>Appointment Date</th>
          <th>Time Slot</th>
          <th>Status</th>
          <th style="text-align:right;">Doctor Action</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($appointments)): ?>
        <tr><td colspan="7" style="text-align:center;color:var(--text-muted);">No appointments booked for you yet.</td></tr>
      <?php else: ?>
        <?php foreach (array_slice($appointments, 0, 5) as $a): ?>
          <tr>
            <td><strong style="color:var(--text-main); font-weight:700;"><?php echo e($a['patient_name']); ?></strong></td>
            <td><?php echo e($a['age'] ?: '—'); ?> yrs / <?php echo e($a['gender'] ?: '—'); ?></td>
            <td><i class="fa-solid fa-phone" style="font-size:10px;color:var(--sky);margin-right:4px;"></i> <?php echo e($a['contact_no'] ?: '—'); ?></td>
            <td><?php echo e($a['appointment_date']); ?></td>
            <td><strong><?php echo e($a['appointment_time']); ?></strong></td>
            <td><?php echo appt_badge($a['status']); ?></td>
            <td style="text-align:right; white-space:nowrap;">
              <?php if ($a['status'] === 'Pending'): ?>
                <form method="post" style="display:inline;">
                  <input type="hidden" name="appt_id" value="<?php echo (int)$a['appointment_id']; ?>">
                  <input type="hidden" name="status" value="Confirmed">
                  <button type="submit" name="update_appt_status" value="1" class="btn btn-sm">
                    <i class="fa-solid fa-check"></i> Confirm
                  </button>
                </form>
              <?php elseif ($a['status'] === 'Confirmed'): ?>
                <form method="post" style="display:inline;">
                  <input type="hidden" name="appt_id" value="<?php echo (int)$a['appointment_id']; ?>">
                  <input type="hidden" name="status" value="Completed">
                  <button type="submit" name="update_appt_status" value="1" class="btn btn-sm" style="background:linear-gradient(135deg,#38bdf8,#0284c7);">
                    <i class="fa-solid fa-circle-check"></i> Mark Complete
                  </button>
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

<!-- SECTION 2: DUAL CARDS — IN-PATIENTS & SALARY SUMMARY -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 24px;">
  <!-- In-Patients Care List -->
  <div class="card" style="margin-bottom:0;">
    <div class="card-header" style="border:none; padding:0; margin-bottom:14px;">
      <h3><i class="fa-solid fa-bed-pulse" style="color:var(--emerald);margin-right:8px;"></i> Assigned In-Patients</h3>
      <a href="patients.php" class="btn btn-secondary btn-sm">Manage Patients</a>
    </div>
    <div class="table-wrap" style="margin-bottom:0;">
      <table>
        <thead>
          <tr>
            <th>Patient</th>
            <th>Diagnosed Condition</th>
            <th>Status</th>
            <th>Update Condition</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($patients)): ?>
          <tr><td colspan="4" style="text-align:center;color:var(--text-muted);">No in-patients currently assigned.</td></tr>
        <?php else: ?>
          <?php foreach (array_slice($patients, 0, 4) as $p): ?>
            <tr>
              <td>
                <strong style="color:var(--text-main); font-weight:700;"><?php echo e($p['citizen_name']); ?></strong><br>
                <span class="hint"><?php echo e($p['age']); ?> yrs, <?php echo e($p['gender']); ?></span>
              </td>
              <td><span style="color:var(--text-main); font-weight:600;"><?php echo e($p['disease']); ?></span></td>
              <td><?php echo patient_badge($p['status']); ?></td>
              <td>
                <form method="post" style="display:flex;gap:4px;align-items:center;">
                  <input type="hidden" name="patient_id" value="<?php echo (int)$p['patient_id']; ?>">
                  <select name="patient_status" style="padding:4px 8px;font-size:0.75rem;width:auto;border-radius:6px;">
                    <option value="Stable" <?php echo $p['status'] === 'Stable' ? 'selected' : ''; ?>>Stable</option>
                    <option value="Recovering" <?php echo $p['status'] === 'Recovering' ? 'selected' : ''; ?>>Recovering</option>
                    <option value="Critical" <?php echo $p['status'] === 'Critical' ? 'selected' : ''; ?>>Critical</option>
                    <option value="Under Treatment" <?php echo $p['status'] === 'Under Treatment' ? 'selected' : ''; ?>>Treatment</option>
                    <option value="Discharged" <?php echo $p['status'] === 'Discharged' ? 'selected' : ''; ?>>Discharged</option>
                  </select>
                  <button type="submit" name="update_patient_status" value="1" class="btn btn-sm" style="padding:4px 8px;">
                    Save
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Salary & Compensation Slip Summary -->
  <div class="card" style="margin-bottom:0; display:flex; flex-direction:column; justify-content:space-between;">
    <div>
      <div class="card-header" style="border:none; padding:0; margin-bottom:14px;">
        <h3><i class="fa-solid fa-file-invoice-dollar" style="color:var(--amber);margin-right:8px;"></i> Monthly Salary Statement</h3>
        <a href="salary.php" class="btn btn-secondary btn-sm">Detailed Payslip</a>
      </div>
      <p>Monthly compensation record determined by Hospital Management based on your specialization and consultation visits.</p>

      <div style="background: rgba(255,255,255,0.75); border: 1px solid rgba(203,213,225,0.7); border-radius: 14px; padding: 16px; margin-bottom: 16px;">
        <div style="display:flex; justify-content:space-between; font-size:0.875rem; border-bottom:1px solid var(--border-color); padding-bottom:8px;">
          <span style="color:var(--text-muted);">Base Monthly Retainer:</span>
          <strong style="color:var(--text-main); font-weight:700;">৳<?php echo number_format($baseSalary, 2); ?></strong>
        </div>
        <div style="display:flex; justify-content:space-between; font-size:0.875rem; border-bottom:1px solid var(--border-color); padding-bottom:8px; padding-top:8px;">
          <span style="color:var(--text-muted);">Consultation Incentives (<?php echo $completedCount; ?> visits @ ৳500):</span>
          <strong style="color:var(--sky);">+ ৳<?php echo number_format($consultationBonus, 2); ?></strong>
        </div>
        <div style="display:flex; justify-content:space-between; font-size:0.95rem; padding-top:8px;">
          <span style="color:var(--text-main); font-weight:700;">Total Take-Home Pay:</span>
          <strong style="color:var(--emerald); font-size:1.1rem;">৳<?php echo number_format($totalEarnings, 2); ?></strong>
        </div>
      </div>
    </div>

    <div style="display:flex; justify-content:space-between; align-items:center; background:rgba(16,185,129,0.12); border:1px solid rgba(16,185,129,0.3); border-radius:12px; padding:12px 16px;">
      <div>
        <div style="font-size:0.75rem; color:var(--text-muted);">Disbursement Cycle: Monthly</div>
        <strong style="color:var(--text-main); font-size:0.875rem;">Status: Active / Verified by Hospital Manager</strong>
      </div>
      <span class="badge badge-paid"><i class="fa-solid fa-check-circle"></i> Enrolled</span>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

