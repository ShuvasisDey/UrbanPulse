<?php
require_once __DIR__ . '/../includes/auth.php';
require_citizen();
require_once __DIR__ . '/../includes/icons.php';
$citizen_id = $_SESSION['ref_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_complaint'])) {
    $description = trim($_POST['description'] ?? '');
    if ($description !== '') {
        $maxRes = $conn->query("SELECT COALESCE(MAX(complaint_id),0)+1 AS next_id FROM Citizen_Complaints");
        $next_id = $maxRes->fetch_assoc()['next_id'];
        $status = 'Pending';
        $stmt = $conn->prepare("INSERT INTO Citizen_Complaints (complaint_id, description, status, citizen_id) VALUES (?,?,?,?)");
        $stmt->bind_param('issi', $next_id, $description, $status, $citizen_id);
        $stmt->execute();
        $message = "Complaint #$next_id submitted successfully. Municipal managers will investigate.";
    } else {
        $error = "Please provide details for your complaint.";
    }
}

$stmt = $conn->prepare("SELECT * FROM Citizen_Complaints WHERE citizen_id = ? ORDER BY complaint_id DESC");
$stmt->bind_param('i', $citizen_id);
$stmt->execute();
$res = $stmt->get_result();

$complaints = [];
$pendingCount = 0;
while ($c = $res->fetch_assoc()) {
    $complaints[] = $c;
    if (strtolower($c['status']) !== 'resolved') {
        $pendingCount++;
    }
}

function status_badge($status) {
    $s = strtolower($status);
    $cls = $s === 'resolved' ? 'badge-resolved' : ($s === 'pending' ? 'badge-pending' : 'badge-progress');
    return "<span class='badge $cls'>" . htmlspecialchars($status) . "</span>";
}

$page_title = "Citizen Complaints Desk";
include __DIR__ . '/../includes/header.php';

page_banner('complaint', 'Public Grievances & Complaints Desk', 'Report municipal infrastructure defects, water leakages, streetlight failures, or civic disruptions.');
?>

<?php if ($message): ?><div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo e($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo e($error); ?></div><?php endif; ?>

<!-- Submission Form & Guidance -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; margin-bottom: 24px;">
  <div class="card" style="margin-bottom:0;">
    <div class="card-header" style="border:none; padding:0; margin-bottom:14px;">
      <h3><i class="fa-solid fa-pen-to-square" style="color:var(--emerald);margin-right:8px;"></i> File a New Complaint</h3>
    </div>
    <form method="post">
      <div class="form-group">
        <label>Issue Description & Location *</label>
        <textarea name="description" required placeholder="Describe the problem, exact street/neighborhood, and urgency level..." style="min-height:120px;"></textarea>
      </div>
      <button type="submit" name="submit_complaint" value="1" class="btn" style="width:100%;">
        <i class="fa-solid fa-paper-plane"></i> Submit Grievance
      </button>
    </form>
  </div>

  <div class="card" style="margin-bottom:0;">
    <div class="card-header" style="border:none; padding:0; margin-bottom:14px;">
      <h3><i class="fa-solid fa-circle-info" style="color:var(--sky);margin-right:8px;"></i> Complaint Process & SLA</h3>
    </div>
    <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:12px; font-size:0.875rem; color:var(--text-muted);">
      <li style="display:flex;gap:10px;align-items:flex-start;">
        <span class="badge badge-pending" style="flex-shrink:0;">1. Pending</span>
        <span>Ticket logged and forwarded to the concerned municipal department manager.</span>
      </li>
      <li style="display:flex;gap:10px;align-items:flex-start;">
        <span class="badge badge-info" style="flex-shrink:0;">2. In Progress</span>
        <span>Inspection crew dispatched on-site for evaluation and physical repair.</span>
      </li>
      <li style="display:flex;gap:10px;align-items:flex-start;">
        <span class="badge badge-paid" style="flex-shrink:0;">3. Resolved</span>
        <span>Issue resolved and verified by central city management desk.</span>
      </li>
    </ul>
    <div class="hint" style="margin-top:16px;">For life-threatening hazards or gas leaks, dial <strong>999</strong> immediately.</div>
  </div>
</div>

<!-- History Table -->
<div class="card">
  <div class="card-header" style="border:none; padding:0; margin-bottom:16px;">
    <h3><i class="fa-solid fa-clock-rotate-left" style="color:var(--emerald);margin-right:8px;"></i> My Complaint History</h3>
    <span class="hint"><?php echo count($complaints); ?> total records (<?php echo $pendingCount; ?> active)</span>
  </div>
  <div class="table-wrap" style="margin-bottom:0;">
    <table>
      <thead>
        <tr>
          <th style="width:80px;">Ticket ID</th>
          <th>Description of Grievance</th>
          <th style="width:150px;">Resolution Status</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($complaints)): ?>
        <tr><td colspan="3" style="text-align:center;color:var(--text-muted);">No complaints filed yet.</td></tr>
      <?php else: ?>
        <?php foreach ($complaints as $row): ?>
          <tr>
            <td><strong>#<?php echo (int)$row['complaint_id']; ?></strong></td>
            <td><?php echo e($row['description']); ?></td>
            <td><?php echo status_badge($row['status']); ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
