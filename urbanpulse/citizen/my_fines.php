<?php
require_once __DIR__ . '/../includes/auth.php';
require_citizen();
require_once __DIR__ . '/../includes/icons.php';
$citizen_id = $_SESSION['ref_id'];

$sql = "SELECT tf.fine_id, tf.amount, tf.payment_status, tv.type AS violation_type,
               veh.registration_no, veh.type AS vehicle_type
        FROM Traffic_Fines tf
        JOIN Traffic_Violations tv ON tf.violation_id = tv.violation_id
        JOIN Traffic_Vehicles veh ON tv.vehicle_id = veh.vehicle_id
        WHERE veh.citizen_id = ?
        ORDER BY tf.fine_id DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $citizen_id);
$stmt->execute();
$res = $stmt->get_result();

$finesList = [];
$totalFines = 0;
$unpaidTotal = 0;
while ($row = $res->fetch_assoc()) {
    $finesList[] = $row;
    $totalFines += (float)$row['amount'];
    if (strtolower($row['payment_status']) !== 'paid') {
        $unpaidTotal += (float)$row['amount'];
    }
}

function status_badge($status) {
    $s = strtolower($status);
    $cls = $s === 'paid' ? 'badge-paid' : ($s === 'unpaid' ? 'badge-unpaid' : 'badge-progress');
    return "<span class='badge $cls'>" . htmlspecialchars($status) . "</span>";
}

$page_title = "My Traffic Fines";
include __DIR__ . '/../includes/header.php';

page_banner('traffic', 'My Traffic Violations & Fines', 'Review registered vehicle violations, penalty amounts, and settlement status.');
?>

<!-- Fines KPI Summary -->
<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-header">
      <span class="stat-icon" style="color:var(--sky);"><i class="fa-solid fa-receipt"></i></span>
      <span class="stat-trend">Total</span>
    </div>
    <div class="stat-body">
      <div class="num"><?php echo count($finesList); ?></div>
      <div class="label">Total Violations Issued</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-header">
      <span class="stat-icon" style="color:var(--rose);"><i class="fa-solid fa-triangle-exclamation"></i></span>
      <span class="stat-trend" style="background:rgba(244,63,94,0.15);color:#fb7185;border-color:rgba(244,63,94,0.3);">Outstanding</span>
    </div>
    <div class="stat-body">
      <div class="num" style="color:var(--rose);">৳<?php echo number_format($unpaidTotal, 2); ?></div>
      <div class="label">Unpaid Fines Due</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-header">
      <span class="stat-icon" style="color:var(--emerald);"><i class="fa-solid fa-circle-check"></i></span>
      <span class="stat-trend" style="background:rgba(16,185,129,0.15);color:#34d399;">Settled</span>
    </div>
    <div class="stat-body">
      <div class="num" style="color:var(--emerald);">৳<?php echo number_format($totalFines - $unpaidTotal, 2); ?></div>
      <div class="label">Total Paid Violations</div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header" style="border:none; padding:0; margin-bottom:16px;">
    <h3><i class="fa-solid fa-car-burst" style="color:var(--sky);margin-right:8px;"></i> Violation Log</h3>
  </div>
  <div class="table-wrap" style="margin-bottom:0;">
    <table>
      <thead>
        <tr>
          <th>Fine ID</th>
          <th>Vehicle Registration</th>
          <th>Vehicle Type</th>
          <th>Violation Recorded</th>
          <th>Fine Amount</th>
          <th>Payment Status</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($finesList)): ?>
        <tr><td colspan="6" style="text-align:center;color:var(--text-muted);">No traffic fines on record. Excellent safe driving!</td></tr>
      <?php else: ?>
        <?php foreach ($finesList as $row): ?>
          <tr>
            <td>#<?php echo (int)$row['fine_id']; ?></td>
            <td><strong style="color:var(--text-main); font-weight:700;"><?php echo e($row['registration_no']); ?></strong></td>
            <td><span class="badge badge-info"><?php echo e($row['vehicle_type']); ?></span></td>
            <td><?php echo e($row['violation_type']); ?></td>
            <td><strong>৳<?php echo number_format($row['amount'], 2); ?></strong></td>
            <td><?php echo status_badge($row['payment_status']); ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
