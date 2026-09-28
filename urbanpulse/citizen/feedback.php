<?php
require_once __DIR__ . '/../includes/auth.php';
require_citizen();
require_once __DIR__ . '/../includes/icons.php';
$citizen_id = $_SESSION['ref_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $msg = trim($_POST['message'] ?? '');
    if ($msg !== '') {
        $maxRes = $conn->query("SELECT COALESCE(MAX(feedback_id),0)+1 AS next_id FROM Citizen_Feedback");
        $next_id = $maxRes->fetch_assoc()['next_id'];
        $stmt = $conn->prepare("INSERT INTO Citizen_Feedback (feedback_id, message, citizen_id) VALUES (?,?,?)");
        $stmt->bind_param('isi', $next_id, $msg, $citizen_id);
        $stmt->execute();
        $message = "Thank you! Your feedback (#$next_id) has been delivered to municipal managers.";
    } else {
        $error = "Please enter your feedback message.";
    }
}

$stmt = $conn->prepare("SELECT * FROM Citizen_Feedback WHERE citizen_id = ? ORDER BY feedback_id DESC");
$stmt->bind_param('i', $citizen_id);
$stmt->execute();
$res = $stmt->get_result();

$feedbackList = [];
while ($row = $res->fetch_assoc()) {
    $feedbackList[] = $row;
}

$page_title = "Citizen Feedback & Ideas";
include __DIR__ . '/../includes/header.php';

page_banner('feedback', 'Citizen Voice & City Feedback', 'Share innovative ideas, constructive suggestions, and reviews on municipal services.');
?>

<?php if ($message): ?><div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo e($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo e($error); ?></div><?php endif; ?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; margin-bottom: 24px;">
  <!-- Feedback Submission Form -->
  <div class="card" style="margin-bottom:0;">
    <div class="card-header" style="border:none; padding:0; margin-bottom:14px;">
      <h3><i class="fa-solid fa-lightbulb" style="color:var(--amber);margin-right:8px;"></i> Share Your Idea or Feedback</h3>
    </div>
    <form method="post">
      <div class="form-group">
        <label>Feedback Message *</label>
        <textarea name="message" required placeholder="What city service can be improved? How was your recent experience with traffic, hospitals, or energy utilities?" style="min-height:120px;"></textarea>
      </div>
      <button type="submit" name="submit_feedback" value="1" class="btn" style="width:100%;">
        <i class="fa-solid fa-paper-plane"></i> Send Feedback to Management
      </button>
    </form>
  </div>

  <div class="card" style="margin-bottom:0;">
    <div class="card-header" style="border:none; padding:0; margin-bottom:14px;">
      <h3><i class="fa-solid fa-heart" style="color:var(--rose);margin-right:8px;"></i> Why Your Voice Matters</h3>
    </div>
    <p>UrbanPulse uses citizen feedback to optimize bus and traffic schedules, identify hospital bottlenecks, upgrade energy grids, and prioritize infrastructure investments.</p>
    <div class="hint" style="margin-top:16px;">
      All submissions are reviewed directly by the Central Management Operations Unit.
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header" style="border:none; padding:0; margin-bottom:16px;">
    <h3><i class="fa-solid fa-comments" style="color:var(--emerald);margin-right:8px;"></i> My Previous Feedback Submissions</h3>
  </div>
  <div class="table-wrap" style="margin-bottom:0;">
    <table>
      <thead>
        <tr>
          <th style="width:100px;">Submission ID</th>
          <th>Feedback Content</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($feedbackList)): ?>
        <tr><td colspan="2" style="text-align:center;color:var(--text-muted);">No feedback logged yet. We would love to hear your thoughts!</td></tr>
      <?php else: ?>
        <?php foreach ($feedbackList as $fb): ?>
          <tr>
            <td><strong>#<?php echo (int)$fb['feedback_id']; ?></strong></td>
            <td><?php echo e($fb['message']); ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
