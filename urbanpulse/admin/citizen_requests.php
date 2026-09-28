<?php
require_once __DIR__ . '/../includes/auth.php';
require_central_admin();
require_once __DIR__ . '/../includes/icons.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestId = (int)($_POST['request_id'] ?? 0);
    $stmt = $conn->prepare("SELECT * FROM Citizen_Registration_Requests WHERE request_id = ? AND status = 'Pending'");
    $stmt->bind_param('i', $requestId);
    $stmt->execute();
    $req = $stmt->get_result()->fetch_assoc();

    if (!$req) {
        $error = "That request no longer exists or was already processed.";
    } elseif (isset($_POST['approve'])) {
        // Re-check the username is still free (it may have been taken since the request was filed).
        $chk = $conn->prepare("SELECT user_id FROM Users WHERE username = ?");
        $chk->bind_param('s', $req['username']);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $error = "Cannot approve: the username '" . $req['username'] . "' was taken by another account in the meantime.";
        } else {
            $maxRes = $conn->query("SELECT COALESCE(MAX(citizen_id),0)+1 AS next_id FROM Citizen");
            $nextId = $maxRes->fetch_assoc()['next_id'];

            $ins = $conn->prepare("INSERT INTO Citizen (citizen_id, name, age, gender, address, contact_no, email) VALUES (?,?,?,?,?,?,?)");
            $ins->bind_param('isissss', $nextId, $req['name'], $req['age'], $req['gender'], $req['address'], $req['contact_no'], $req['email']);
            $ins->execute();

            $u = $conn->prepare("INSERT INTO Users (username, password, role, ref_id, security_question, security_answer) VALUES (?, ?, 'citizen', ?, ?, ?)");
            $u->bind_param('ssiss', $req['username'], $req['password'], $nextId, $req['security_question'], $req['security_answer']);
            $u->execute();

            $upd = $conn->prepare("UPDATE Citizen_Registration_Requests SET status = 'Approved' WHERE request_id = ?");
            $upd->bind_param('i', $requestId);
            $upd->execute();

            $message = "Approved successfully — " . e($req['name']) . " can now log in as '" . e($req['username']) . "'.";
        }
    } elseif (isset($_POST['reject'])) {
        $upd = $conn->prepare("UPDATE Citizen_Registration_Requests SET status = 'Rejected' WHERE request_id = ?");
        $upd->bind_param('i', $requestId);
        $upd->execute();
        $message = "Request for " . e($req['name']) . " was rejected.";
    }
}

$pending = $conn->query("SELECT * FROM Citizen_Registration_Requests WHERE status = 'Pending' ORDER BY requested_at DESC");
$recent = $conn->query("SELECT * FROM Citizen_Registration_Requests WHERE status <> 'Pending' ORDER BY requested_at DESC LIMIT 10");

$page_title = "Citizen Sign-up Requests";
include __DIR__ . '/../includes/header.php';

$backBtn = '<a href="dashboard.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Manager Dashboard</a>';
page_banner('citizen', 'Citizen Sign-up Approval Queue', 'Verify applicant identities and approve or decline access to the citizen portal.', $backBtn);
?>

<?php if ($message): ?><div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo e($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo e($error); ?></div><?php endif; ?>

<h3 style="color:var(--text-main); margin-bottom: 16px; display:flex; align-items:center; gap:8px;">
  <i class="fa-solid fa-hourglass-half" style="color:var(--amber);"></i>
  Pending Applications (<?php echo $pending->num_rows; ?>)
</h3>

<?php if ($pending->num_rows === 0): ?>
  <div class="card" style="text-align:center; padding: 40px; color:var(--text-muted);">
    <i class="fa-solid fa-circle-check" style="font-size: 2.5rem; color: var(--emerald); margin-bottom: 12px; display:block;"></i>
    All clear! There are no pending citizen sign-up requests awaiting approval.
  </div>
<?php else: ?>
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 20px; margin-bottom: 32px;">
    <?php while ($r = $pending->fetch_assoc()): ?>
      <div class="card request-card" style="margin-bottom:0; display:flex; flex-direction:column; justify-content:space-between;">
        <div>
          <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px;">
            <div>
              <h3 style="margin:0; font-size: 1.15rem; color:var(--text-main); font-weight:800;"><?php echo e($r['name']); ?></h3>
              <span class="hint" style="color:var(--sky); font-weight:600;">@<?php echo e($r['username']); ?></span>
            </div>
            <span class="badge badge-pending"><i class="fa-solid fa-clock"></i> Pending Review</span>
          </div>

          <div style="background: rgba(255,255,255,0.7); border: 1px solid rgba(203,213,225,0.6); border-radius: 12px; padding: 12px 14px; margin-bottom: 16px; font-size: 0.85rem; line-height: 1.6; color: var(--text-main);">
            <div><strong>Age:</strong> <?php echo e($r['age'] ?: 'N/A'); ?> &bull; <strong>Gender:</strong> <?php echo e($r['gender'] ?: 'N/A'); ?></div>
            <div><strong>Contact:</strong> <?php echo e($r['contact_no'] ?: 'N/A'); ?></div>
            <div><strong>Email:</strong> <?php echo e($r['email'] ?: 'N/A'); ?></div>
            <div><strong>Address:</strong> <?php echo e($r['address'] ?: 'N/A'); ?></div>
          </div>
        </div>

        <form method="post" style="display:flex; gap:10px;">
          <input type="hidden" name="request_id" value="<?php echo (int)$r['request_id']; ?>">
          <button type="submit" name="approve" value="1" class="btn" style="flex:1;">
            <i class="fa-solid fa-check"></i> Approve
          </button>
          <button type="submit" name="reject" value="1" class="btn btn-danger" onclick="return confirm('Are you sure you want to reject this citizen application?');" style="flex:1;">
            <i class="fa-solid fa-xmark"></i> Reject
          </button>
        </form>
      </div>
    <?php endwhile; ?>
  </div>
<?php endif; ?>

<?php if ($recent->num_rows > 0): ?>
  <div class="card">
    <div class="card-header" style="border:none; padding:0; margin-bottom:16px;">
      <h3><i class="fa-solid fa-history" style="color:var(--sky);margin-right:8px;"></i> Recently Processed Requests</h3>
    </div>
    <div class="table-wrap" style="margin-bottom:0;">
      <table>
        <thead>
          <tr>
            <th>Applicant Name</th>
            <th>Requested Username</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Final Decision</th>
          </tr>
        </thead>
        <tbody>
        <?php while ($r = $recent->fetch_assoc()): ?>
          <tr>
            <td><strong style="color:var(--text-main); font-weight:700;"><?php echo e($r['name']); ?></strong></td>
            <td style="color:var(--text-main);"><?php echo e($r['username']); ?></td>
            <td style="color:var(--text-main);"><?php echo e($r['email'] ?: '—'); ?></td>
            <td style="color:var(--text-main);"><?php echo e($r['contact_no'] ?: '—'); ?></td>
            <td>
              <?php echo $r['status'] === 'Approved' ? 
                '<span class="badge badge-paid"><i class="fa-solid fa-check"></i> Approved</span>' : 
                '<span class="badge badge-unpaid"><i class="fa-solid fa-xmark"></i> Rejected</span>'; ?>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
