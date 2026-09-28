<?php
require_once __DIR__ . '/../includes/auth.php';
require_doctor();
require_once __DIR__ . '/../includes/icons.php';

$doctor_id = (int)$_SESSION['ref_id'];

// Fetch Doctor Profile & Hospital Details
$docSql = "SELECT d.*, h.name AS hospital_name, h.location AS hospital_location
           FROM Doctors d
           JOIN Hospitals h ON d.hospital_id = h.hospital_id
           WHERE d.doctor_id = ?";
$dStmt = $conn->prepare($docSql);
$dStmt->bind_param('i', $doctor_id);
$dStmt->execute();
$doctor = $dStmt->get_result()->fetch_assoc() ?: [];

// Fetch completed appointments count for incentive calculations
$cStmt = $conn->prepare("SELECT COUNT(*) AS total FROM Hospital_Appointments WHERE doctor_id = ? AND status = 'Completed'");
$cStmt->bind_param('i', $doctor_id);
$cStmt->execute();
$completedCount = (int)$cStmt->get_result()->fetch_assoc()['total'];

$baseSalary = (float)($doctor['salary'] ?? 80000.00);
$medicalAllowance = $baseSalary * 0.10; // 10%
$housingAllowance = $baseSalary * 0.15; // 15%
$consultationIncentive = $completedCount * 500.00; // ৳500 per completed patient
$grossPay = $baseSalary + $medicalAllowance + $housingAllowance + $consultationIncentive;
$taxDeduction = $grossPay * 0.05; // 5%
$netPay = $grossPay - $taxDeduction;

$billingMonth = date('F Y');
$disbursementRef = "PAY-" . date('Ym') . "-DOC" . str_pad($doctor_id, 4, '0', STR_PAD_LEFT);

$page_title = "Doctor Compensation & Salary";
include __DIR__ . '/../includes/header.php';

$actBtns = '<button onclick="window.print()" class="btn btn-secondary"><i class="fa-solid fa-print"></i> Print Payslip</button>' .
           '<a href="dashboard.php" class="btn"><i class="fa-solid fa-gauge"></i> Dashboard</a>';
page_banner('doctor', 'Physician Compensation & Salary Slip', 'Official payroll record authorized and managed by Hospital Management.', $actBtns);
?>

<!-- Salary Summary Cards -->
<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-header">
      <span class="stat-icon" style="color:var(--amber);"><i class="fa-solid fa-building-columns"></i></span>
      <span class="stat-trend">Base Pay</span>
    </div>
    <div class="stat-body">
      <div class="num">৳<?php echo number_format($baseSalary, 2); ?></div>
      <div class="label">Monthly Base Salary</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-header">
      <span class="stat-icon" style="color:var(--sky);"><i class="fa-solid fa-hand-holding-dollar"></i></span>
      <span class="stat-trend"><?php echo $completedCount; ?> Consultations</span>
    </div>
    <div class="stat-body">
      <div class="num" style="color:var(--sky);">৳<?php echo number_format($consultationIncentive, 2); ?></div>
      <div class="label">Consultation Bonuses</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-header">
      <span class="stat-icon" style="color:var(--emerald);"><i class="fa-solid fa-receipt"></i></span>
      <span class="stat-trend" style="background:rgba(16,185,129,0.15);color:#34d399;">Disbursed</span>
    </div>
    <div class="stat-body">
      <div class="num" style="color:var(--emerald);">৳<?php echo number_format($netPay, 2); ?></div>
      <div class="label">Net Take-Home Compensation</div>
    </div>
  </div>
</div>

<!-- Official Printable Payslip Container -->
<div class="card" style="max-width:850px; margin: 0 auto; background: var(--bg-card); border: 1.5px solid var(--border-light); padding: 32px; border-radius: 20px;">
  <!-- Slip Header -->
  <div style="display:flex; justify-content:space-between; align-items:flex-start; border-bottom: 2px solid var(--border-light); padding-bottom: 20px; margin-bottom: 24px; flex-wrap:wrap; gap:16px;">
    <div>
      <div style="font-size:1.4rem; font-weight:800; color:var(--text-main); display:flex; align-items:center; gap:8px;">
        <span class="brand-dot"></span> UrbanPulse Health Authority
      </div>
      <div style="color:var(--text-muted); font-size:0.875rem; margin-top:4px;">
        Hospital: <strong><?php echo e($doctor['hospital_name']); ?></strong> (<?php echo e($doctor['hospital_location']); ?>)
      </div>
      <div class="hint">Payroll Voucher: <?php echo $disbursementRef; ?></div>
    </div>
    <div style="text-align:right;">
      <span class="badge badge-paid" style="font-size:0.85rem; padding:6px 16px;">
        <i class="fa-solid fa-circle-check"></i> DISBURSED & SETTLED
      </span>
      <div style="color:var(--text-muted); font-size:0.85rem; margin-top:8px;">
        Billing Cycle: <strong style="color:var(--text-main);"><?php echo $billingMonth; ?></strong>
      </div>
    </div>
  </div>

  <!-- Physician Details Grid -->
  <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:16px; background:rgba(255,255,255,0.7); border:1px solid rgba(203,213,225,0.7); border-radius:14px; padding:16px; margin-bottom:24px; font-size:0.875rem;">
    <div>
      <div style="color:var(--text-muted); font-size:0.75rem;">PHYSICIAN NAME</div>
      <strong style="color:var(--text-main); font-size:1rem; font-weight:700;"><?php echo e($doctor['name']); ?></strong>
    </div>
    <div>
      <div style="color:var(--text-muted); font-size:0.75rem;">SPECIALIZATION</div>
      <strong style="color:var(--sky);"><?php echo e($doctor['specialization']); ?></strong>
    </div>
    <div>
      <div style="color:var(--text-muted); font-size:0.75rem;">STAFF ID</div>
      <strong style="color:var(--text-main); font-weight:700;">#DOC-<?php echo $doctor_id; ?></strong>
    </div>
    <div>
      <div style="color:var(--text-muted); font-size:0.75rem;">AUTHORIZING DESK</div>
      <strong style="color:var(--emerald);">Hospital Management</strong>
    </div>
  </div>

  <!-- Earnings & Deductions Breakdown Table -->
  <h4 style="color:var(--text-main); margin-bottom: 12px; font-size:0.95rem; font-weight:700;">Compensation & Allowance Ledger</h4>
  <div class="table-wrap" style="margin-bottom:20px;">
    <table>
      <thead>
        <tr>
          <th>Earnings Component</th>
          <th>Type</th>
          <th style="text-align:right;">Amount (৳)</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><strong>Base Medical Retainer</strong></td>
          <td>Fixed Monthly Salary (Set by Hospital Manager)</td>
          <td style="text-align:right; font-weight:700;">৳<?php echo number_format($baseSalary, 2); ?></td>
        </tr>
        <tr>
          <td>Medical & Clinical Equipment Allowance</td>
          <td>Standard Allowance (10%)</td>
          <td style="text-align:right;">৳<?php echo number_format($medicalAllowance, 2); ?></td>
        </tr>
        <tr>
          <td>Specialist Housing & Transit Allowance</td>
          <td>Benefit Allowance (15%)</td>
          <td style="text-align:right;">৳<?php echo number_format($housingAllowance, 2); ?></td>
        </tr>
        <tr>
          <td>Consultation Visit Incentives (<?php echo $completedCount; ?> verified visits)</td>
          <td>Performance Bonus (@ ৳500 / visit)</td>
          <td style="text-align:right; color:var(--sky);">+ ৳<?php echo number_format($consultationIncentive, 2); ?></td>
        </tr>
        <tr style="background:rgba(255,255,255,0.6); font-weight:700;">
          <td colspan="2">Gross Earnings</td>
          <td style="text-align:right; color:var(--emerald);">৳<?php echo number_format($grossPay, 2); ?></td>
        </tr>
        <tr>
          <td>Municipal Health Service Tax Withholding</td>
          <td>Statutory Deduction (5%)</td>
          <td style="text-align:right; color:var(--rose);">- ৳<?php echo number_format($taxDeduction, 2); ?></td>
        </tr>
        <tr style="background:rgba(16,185,129,0.12); border-top:2px solid var(--emerald); font-size:1.05rem; font-weight:800;">
          <td colspan="2" style="color:var(--text-main); font-weight:800;">Net Take-Home Salary Disbursed</td>
          <td style="text-align:right; color:var(--emerald);">৳<?php echo number_format($netPay, 2); ?></td>
        </tr>
      </tbody>
    </table>
  </div>

  <!-- Signoff / Verification footer -->
  <div style="display:flex; justify-content:space-between; align-items:flex-end; padding-top:20px; border-top:1px solid var(--border-light); font-size:0.8rem; color:var(--text-muted); flex-wrap:wrap; gap:16px;">
    <div>
      <div>Verified by: <strong>Hospital Financial Controller</strong></div>
      <div>City Healthcare Management System &bull; UrbanPulse</div>
    </div>
    <div style="text-align:right;">
      <div style="color:var(--emerald); font-weight:700;"><i class="fa-solid fa-stamp"></i> Digitally Signed & Approved</div>
      <div>Date: <?php echo date('d M Y'); ?></div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

