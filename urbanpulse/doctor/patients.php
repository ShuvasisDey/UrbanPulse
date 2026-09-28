<?php
require_once __DIR__ . '/../includes/auth.php';
require_doctor();
require_once __DIR__ . '/../includes/icons.php';

$doctor_id = (int)$_SESSION['ref_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $patientId = (int)$_POST['patient_id'];
    $newStatus = trim($_POST['status'] ?? '');
    if (in_array($newStatus, ['Stable', 'Recovering', 'Critical', 'Under Treatment', 'Discharged'], true)) {
        $stmt = $conn->prepare("UPDATE Patients SET status = ? WHERE patient_id = ? AND doctor_id = ?");
        $stmt->bind_param('sii', $newStatus, $patientId, $doctor_id);
        $stmt->execute();
        $message = "Patient #$patientId condition updated to '$newStatus'.";
    }
}

$sql = "SELECT p.*, c.name AS patient_name, c.contact_no, c.email, c.age, c.gender, c.address
        FROM Patients p
        JOIN Citizen c ON p.citizen_id = c.citizen_id
        WHERE p.doctor_id = ?
        ORDER BY p.patient_id DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $doctor_id);
$stmt->execute();
$res = $stmt->get_result();

function patient_badge($status) {
    $cls = ['Stable' => 'badge-paid', 'Recovering' => 'badge-info', 'Under Treatment' => 'badge-pending', 'Critical' => 'badge-unpaid', 'Discharged' => 'badge-resolved'][$status] ?? 'badge-progress';
    return "<span class='badge $cls'>" . htmlspecialchars($status) . "</span>";
}

$page_title = "Assigned In-Patients";
include __DIR__ . '/../includes/header.php';

$backBtn = '<a href="dashboard.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Doctor Dashboard</a>';
page_banner('hospital', 'Assigned In-Patients Clinical Tracker', 'Monitor patient health recovery progress, active treatment plans, and discharge status.', $backBtn);
?>

<?php if ($message): ?><div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo e($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo e($error); ?></div><?php endif; ?>

<div class="card">
  <div class="table-wrap" style="margin-bottom:0;">
    <table>
      <thead>
        <tr>
          <th>#Patient ID</th>
          <th>Patient Name</th>
          <th>Demographics</th>
          <th>Contact & Address</th>
          <th>Diagnosed Disease</th>
          <th>Current Status</th>
          <th style="text-align:right;">Update Condition</th>
        </tr>
      </thead>
      <tbody>
      <?php if ($res->num_rows === 0): ?>
        <tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:30px;">No patients currently assigned to your care.</td></tr>
      <?php else: ?>
        <?php while ($p = $res->fetch_assoc()): ?>
          <tr>
            <td><strong>#PT-<?php echo (int)$p['patient_id']; ?></strong></td>
            <td><strong style="color:var(--text-main); font-weight:700;"><?php echo e($p['patient_name']); ?></strong></td>
            <td><?php echo e($p['age'] ?: '—'); ?> yrs &bull; <?php echo e($p['gender'] ?: '—'); ?></td>
            <td>
              <div><i class="fa-solid fa-phone" style="color:var(--sky);font-size:11px;margin-right:4px;"></i> <?php echo e($p['contact_no'] ?: '—'); ?></div>
              <div class="hint"><?php echo e($p['address'] ?: ''); ?></div>
            </td>
            <td><span style="color:var(--text-main); font-weight:600;"><?php echo e($p['disease']); ?></span></td>
            <td><?php echo patient_badge($p['status']); ?></td>
            <td style="text-align:right; white-space:nowrap;">
              <form method="post" style="display:inline-flex; gap:4px; align-items:center;">
                <input type="hidden" name="patient_id" value="<?php echo (int)$p['patient_id']; ?>">
                <select name="status" style="padding:4px 8px;font-size:0.775rem;width:auto;border-radius:6px;">
                  <option value="Stable" <?php echo $p['status'] === 'Stable' ? 'selected' : ''; ?>>Stable</option>
                  <option value="Recovering" <?php echo $p['status'] === 'Recovering' ? 'selected' : ''; ?>>Recovering</option>
                  <option value="Critical" <?php echo $p['status'] === 'Critical' ? 'selected' : ''; ?>>Critical</option>
                  <option value="Under Treatment" <?php echo $p['status'] === 'Under Treatment' ? 'selected' : ''; ?>>Under Treatment</option>
                  <option value="Discharged" <?php echo $p['status'] === 'Discharged' ? 'selected' : ''; ?>>Discharged</option>
                </select>
                <button type="submit" name="update_status" value="1" class="btn btn-sm">
                  Update
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

