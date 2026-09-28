<?php
require_once __DIR__ . '/../includes/auth.php';
require_doctor();
require_once __DIR__ . '/../includes/icons.php';

$doctor_id = (int)$_SESSION['ref_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $apptId = (int)$_POST['appt_id'];
    $newStatus = trim($_POST['status'] ?? '');
    if (in_array($newStatus, ['Pending', 'Confirmed', 'Completed', 'Cancelled'], true)) {
        $stmt = $conn->prepare("UPDATE Hospital_Appointments SET status = ? WHERE appointment_id = ? AND doctor_id = ?");
        $stmt->bind_param('sii', $newStatus, $apptId, $doctor_id);
        $stmt->execute();
        $message = "Appointment #$apptId status updated to '$newStatus'.";
    }
}

$statusFilter = $_GET['status'] ?? 'all';
$sql = "SELECT a.*, c.name AS patient_name, c.contact_no, c.email, c.age, c.gender, c.address
        FROM Hospital_Appointments a
        JOIN Citizen c ON a.citizen_id = c.citizen_id
        WHERE a.doctor_id = ?";

if ($statusFilter !== 'all' && in_array($statusFilter, ['Pending', 'Confirmed', 'Completed', 'Cancelled'], true)) {
    $sql .= " AND a.status = '" . $conn->real_escape_string($statusFilter) . "'";
}
$sql .= " ORDER BY a.appointment_date DESC, a.appointment_time ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $doctor_id);
$stmt->execute();
$res = $stmt->get_result();

$appts = [];
$counts = ['Pending' => 0, 'Confirmed' => 0, 'Completed' => 0, 'Cancelled' => 0];
$cStmt = $conn->prepare("SELECT status, COUNT(*) c FROM Hospital_Appointments WHERE doctor_id = ? GROUP BY status");
$cStmt->bind_param('i', $doctor_id);
$cStmt->execute();
$cRes = $cStmt->get_result();
while ($r = $cRes->fetch_assoc()) {
    if (isset($counts[$r['status']])) $counts[$r['status']] = (int)$r['c'];
}

function appt_badge($status) {
    $cls = ['Confirmed' => 'badge-paid', 'Pending' => 'badge-pending', 'Cancelled' => 'badge-unpaid', 'Completed' => 'badge-resolved'][$status] ?? 'badge-progress';
    return "<span class='badge $cls'>" . htmlspecialchars($status) . "</span>";
}

$page_title = "Doctor Appointments Manager";
include __DIR__ . '/../includes/header.php';

$backBtn = '<a href="dashboard.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Doctor Dashboard</a>';
page_banner('appointment', 'Patient Appointments Roster', 'Manage medical appointments, confirm bookings, and record completed consultations.', $backBtn);
?>

<?php if ($message): ?><div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo e($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo e($error); ?></div><?php endif; ?>

<!-- Quick Status Filter Pills -->
<div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:24px;">
  <a href="appointments.php?status=all" class="btn btn-sm <?php echo $statusFilter === 'all' ? '' : 'btn-secondary'; ?>">
    All (<?php echo array_sum($counts); ?>)
  </a>
  <a href="appointments.php?status=Pending" class="btn btn-sm <?php echo $statusFilter === 'Pending' ? '' : 'btn-secondary'; ?>">
    Pending (<?php echo $counts['Pending']; ?>)
  </a>
  <a href="appointments.php?status=Confirmed" class="btn btn-sm <?php echo $statusFilter === 'Confirmed' ? '' : 'btn-secondary'; ?>">
    Confirmed (<?php echo $counts['Confirmed']; ?>)
  </a>
  <a href="appointments.php?status=Completed" class="btn btn-sm <?php echo $statusFilter === 'Completed' ? '' : 'btn-secondary'; ?>">
    Completed (<?php echo $counts['Completed']; ?>)
  </a>
  <a href="appointments.php?status=Cancelled" class="btn btn-sm <?php echo $statusFilter === 'Cancelled' ? '' : 'btn-secondary'; ?>">
    Cancelled (<?php echo $counts['Cancelled']; ?>)
  </a>
</div>

<div class="card">
  <div class="table-wrap" style="margin-bottom:0;">
    <table>
      <thead>
        <tr>
          <th>#ID</th>
          <th>Patient Name</th>
          <th>Demographics</th>
          <th>Contact & Email</th>
          <th>Scheduled Date</th>
          <th>Time Slot</th>
          <th>Status</th>
          <th style="text-align:right;">Update Status</th>
        </tr>
      </thead>
      <tbody>
      <?php if ($res->num_rows === 0): ?>
        <tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:30px;">No appointments match this filter.</td></tr>
      <?php else: ?>
        <?php while ($a = $res->fetch_assoc()): ?>
          <tr>
            <td><strong>#<?php echo (int)$a['appointment_id']; ?></strong></td>
            <td><strong style="color:var(--text-main); font-weight:700;"><?php echo e($a['patient_name']); ?></strong></td>
            <td><?php echo e($a['age'] ?: '—'); ?> yrs &bull; <?php echo e($a['gender'] ?: '—'); ?></td>
            <td>
              <div><i class="fa-solid fa-phone" style="color:var(--sky);font-size:11px;margin-right:4px;"></i> <?php echo e($a['contact_no'] ?: '—'); ?></div>
              <div class="hint"><?php echo e($a['email'] ?: ''); ?></div>
            </td>
            <td><strong><?php echo e($a['appointment_date']); ?></strong></td>
            <td><?php echo e($a['appointment_time']); ?></td>
            <td><?php echo appt_badge($a['status']); ?></td>
            <td style="text-align:right; white-space:nowrap;">
              <form method="post" style="display:inline-flex; gap:4px; align-items:center;">
                <input type="hidden" name="appt_id" value="<?php echo (int)$a['appointment_id']; ?>">
                <select name="status" style="padding:4px 8px;font-size:0.775rem;width:auto;border-radius:6px;">
                  <option value="Pending" <?php echo $a['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                  <option value="Confirmed" <?php echo $a['status'] === 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                  <option value="Completed" <?php echo $a['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                  <option value="Cancelled" <?php echo $a['status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
                <button type="submit" name="update_status" value="1" class="btn btn-sm">
                  Save
                </button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

