<?php
require_once __DIR__ . '/../includes/auth.php';
require_citizen();
require_once __DIR__ . '/../includes/icons.php';
$citizen_id = $_SESSION['ref_id'];

$sql = "SELECT eb.bill_id, eb.amount, eb.payment_status, eu.units, eu.month, ea.meter_no, ea.connection_type
        FROM Energy_Bill eb
        JOIN Energy_Usage eu ON eb.usage_id = eu.usage_id
        JOIN Energy_Account ea ON eu.account_id = ea.account_id
        WHERE ea.citizen_id = ?
        ORDER BY eb.bill_id DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $citizen_id);
$stmt->execute();
$res = $stmt->get_result();

$billsList = [];
$totalBilled = 0;
$unpaidBilled = 0;
$totalUnits = 0;
while ($row = $res->fetch_assoc()) {
    $billsList[] = $row;
    $totalBilled += (float)$row['amount'];
    $totalUnits += (int)$row['units'];
    if (strtolower($row['payment_status']) !== 'paid') {
        $unpaidBilled += (float)$row['amount'];
    }
}

function status_badge($status) {
    $s = strtolower($status);
    $cls = $s === 'paid' ? 'badge-paid' : ($s === 'unpaid' ? 'badge-unpaid' : 'badge-progress');
    return "<span class='badge $cls'>" . htmlspecialchars($status) . "</span>";
}

$page_title = "My Energy Bills";
include __DIR__ . '/../includes/header.php';

page_banner('energy', 'My Smart Energy & Utility Bills', 'Track monthly electricity meter readings, consumption units, and payment statements.');
?>

<!-- Bills KPI Summary -->
<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-header">
      <span class="stat-icon" style="color:var(--amber);"><i class="fa-solid fa-file-invoice-dollar"></i></span>
      <span class="stat-trend">Statements</span>
    </div>
    <div class="stat-body">
      <div class="num"><?php echo count($billsList); ?></div>
      <div class="label">Total Bills Issued</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-header">
      <span class="stat-icon" style="color:var(--rose);"><i class="fa-solid fa-hourglass-half"></i></span>
      <span class="stat-trend" style="background:rgba(244,63,94,0.15);color:#fb7185;border-color:rgba(244,63,94,0.3);">Due Now</span>
    </div>
    <div class="stat-body">
      <div class="num" style="color:var(--rose);">৳<?php echo number_format($unpaidBilled, 2); ?></div>
      <div class="label">Outstanding Bills Due</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-header">
      <span class="stat-icon" style="color:var(--sky);"><i class="fa-solid fa-chart-line"></i></span>
      <span class="stat-trend">Consumption</span>
    </div>
    <div class="stat-body">
      <div class="num"><?php echo number_format($totalUnits); ?></div>
      <div class="label">Total Kilowatt-Hours (kWh)</div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header" style="border:none; padding:0; margin-bottom:16px;">
    <h3><i class="fa-solid fa-receipt" style="color:var(--amber);margin-right:8px;"></i> Energy Consumption & Billing History</h3>
  </div>
  <div class="table-wrap" style="margin-bottom:0;">
    <table>
      <thead>
        <tr>
          <th>Bill ID</th>
          <th>Meter Number</th>
          <th>Connection Type</th>
          <th>Billing Month</th>
          <th>Units Used</th>
          <th>Amount Due</th>
          <th>Payment Status</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($billsList)): ?>
        <tr><td colspan="7" style="text-align:center;color:var(--text-muted);">No utility bills found on record.</td></tr>
      <?php else: ?>
        <?php foreach ($billsList as $row): ?>
          <tr>
            <td>#<?php echo (int)$row['bill_id']; ?></td>
            <td><strong style="color:var(--text-main); font-weight:700;"><?php echo e($row['meter_no']); ?></strong></td>
            <td><span class="badge badge-info"><?php echo e($row['connection_type']); ?></span></td>
            <td><?php echo e($row['month']); ?></td>
            <td><?php echo e($row['units']); ?> kWh</td>
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
