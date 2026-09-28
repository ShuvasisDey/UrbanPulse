<?php
require_once __DIR__ . '/../includes/auth.php';

$step = 1;
$error = '';
$username = '';
$question = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['find_user'])) {
        $username = trim($_POST['username']);
        $stmt = $conn->prepare("SELECT security_question FROM Users WHERE username = ?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 1) {
            $question = $res->fetch_assoc()['security_question'];
            $step = 2;
        } else {
            $error = "No account found with that username.";
        }
    } elseif (isset($_POST['reset_password'])) {
        $username = trim($_POST['username']);
        $answer = strtolower(trim($_POST['security_answer']));
        $new_password = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];

        $stmt = $conn->prepare("SELECT * FROM Users WHERE username = ?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 1) {
            $user = $res->fetch_assoc();
            $question = $user['security_question'];
            if (!password_verify($answer, $user['security_answer'])) {
                $error = "Security answer is incorrect.";
                $step = 2;
            } elseif ($new_password !== $confirm) {
                $error = "New passwords do not match.";
                $step = 2;
            } elseif (strlen($new_password) < 4) {
                $error = "Password must be at least 4 characters.";
                $step = 2;
            } else {
                $hash = password_hash($new_password, PASSWORD_DEFAULT);
                $upd = $conn->prepare("UPDATE Users SET password = ? WHERE username = ?");
                $upd->bind_param('ss', $hash, $username);
                $upd->execute();
                header("Location: login.php?reset=1");
                exit;
            }
        } else {
            $error = "No account found with that username.";
        }
    }
}

$page_title = "Forgot Password";
include __DIR__ . '/../includes/header.php';
?>
<div class="auth-wrap">
  <div class="card">
    <h2>Reset Password</h2>
    <?php if ($error): ?><div class="alert alert-error"><?php echo e($error); ?></div><?php endif; ?>

    <?php if ($step === 1): ?>
      <form method="post">
        <div class="form-group">
          <label>Username</label>
          <input type="text" name="username" required autofocus>
        </div>
        <button type="submit" name="find_user" value="1" class="btn" style="width:100%;">Continue</button>
      </form>
    <?php else: ?>
      <form method="post">
        <input type="hidden" name="username" value="<?php echo e($username); ?>">
        <div class="form-group">
          <label><?php echo e($question); ?></label>
          <input type="text" name="security_answer" required autofocus>
        </div>
        <div class="form-group"><label>New Password</label><input type="password" name="new_password" required></div>
        <div class="form-group"><label>Confirm New Password</label><input type="password" name="confirm_password" required></div>
        <button type="submit" name="reset_password" value="1" class="btn" style="width:100%;">Reset Password</button>
      </form>
    <?php endif; ?>
    <p class="auth-links"><a href="login.php">Back to login</a></p>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
