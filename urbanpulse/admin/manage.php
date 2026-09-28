<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../includes/table_config.php';
require_once __DIR__ . '/../includes/icons.php';

$table = $_GET['table'] ?? '';
if (!isset($TABLES[$table])) {
    die("Unknown table. <a href='dashboard.php'>Back to dashboard</a>");
}
$config = $TABLES[$table];

if (!can_manage_table($config)) {
    die("You don't have access to this module. <a href='dashboard.php'>Back to dashboard</a>");
}

$pk = $config['pk'];
$fields = $config['fields'];
$autoPk = !empty($config['auto_pk']);
$createsLogin = !empty($config['creates_login']);
$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

// Fetch dropdown options for fk fields
function fk_options($conn, $field) {
    $rows = [];
    $res = $conn->query("SELECT `{$field['ref_pk']}` AS id, `{$field['ref_display']}` AS label FROM `{$field['ref_table']}` ORDER BY `{$field['ref_display']}`");
    while ($r = $res->fetch_assoc()) { $rows[] = $r; }
    return $rows;
}

// ---------- Handle Delete (POST only) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = (int)$_POST['delete_id'];
    $stmt = $conn->prepare("DELETE FROM `$table` WHERE `$pk` = ?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        $message = "Record deleted.";
    } else {
        $error = "Could not delete: this record is referenced by other data.";
    }
    $action = 'list';
}

// ---------- Handle Add / Edit Save ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $cols = [];
    $placeholders = [];
    $types = '';
    $values = [];

    foreach ($fields as $col => $def) {
        $val = trim($_POST[$col] ?? '');
        $cols[] = "`$col`";
        $placeholders[] = '?';
        $types .= 's';
        $values[] = $val;
    }

    $loginError = '';
    if ($createsLogin && $_POST['mode'] === 'add') {
        $loginUsername = trim($_POST['login_username'] ?? '');
        $loginPassword = $_POST['login_password'] ?? '';
        $loginQuestion = trim($_POST['login_security_question'] ?? '');
        $loginAnswer = strtolower(trim($_POST['login_security_answer'] ?? ''));
        if ($loginUsername === '' || $loginPassword === '' || $loginQuestion === '' || $loginAnswer === '') {
            $loginError = "Please fill in all login fields (username, password, security question & answer) for this manager.";
        } else {
            $chk = $conn->prepare("SELECT user_id FROM Users WHERE username = ?");
            $chk->bind_param('s', $loginUsername);
            $chk->execute();
            if ($chk->get_result()->num_rows > 0) {
                $loginError = "That login username is already taken.";
            }
        }
    }

    if ($loginError) {
        $error = $loginError;
    } elseif ($_POST['mode'] === 'add') {
        if ($autoPk) {
            $sql = "INSERT INTO `$table` (" . implode(',', $cols) . ") VALUES (" . implode(',', $placeholders) . ")";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$values);
            $ok = $stmt->execute();
            $newId = $ok ? $conn->insert_id : null;
        } else {
            $maxRes = $conn->query("SELECT COALESCE(MAX(`$pk`),0)+1 AS next_id FROM `$table`");
            $newId = $maxRes->fetch_assoc()['next_id'];
            $sql = "INSERT INTO `$table` (`$pk`, " . implode(',', $cols) . ") VALUES (?, " . implode(',', $placeholders) . ")";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i' . $types, $newId, ...$values);
            $ok = $stmt->execute();
        }

        if ($ok) {
            if ($createsLogin) {
                $hash = password_hash($loginPassword, PASSWORD_DEFAULT);
                $answerHash = password_hash($loginAnswer, PASSWORD_DEFAULT);
                $u = $conn->prepare("INSERT INTO Users (username, password, role, ref_id, security_question, security_answer) VALUES (?, ?, ?, ?, ?, ?)");
                $u->bind_param('sssiss', $loginUsername, $hash, $config['login_role'], $newId, $loginQuestion, $answerHash);
                $u->execute();
            }
            header("Location: manage.php?table=" . urlencode($table) . "&msg=added");
            exit;
        } else {
            $error = "Insert failed: " . $conn->error;
        }
    } else {
        $id = (int)$_POST['pk_value'];
        $setSql = implode(', ', array_map(fn($c) => "$c = ?", $cols));
        $sql = "UPDATE `$table` SET $setSql WHERE `$pk` = ?";
        $stmt = $conn->prepare($sql);
        $updateValues = $values;
        $updateValues[] = $id;
        $stmt->bind_param($types . 'i', ...$updateValues);
        if ($stmt->execute()) {
            header("Location: manage.php?table=" . urlencode($table) . "&msg=updated");
            exit;
        } else {
            $error = "Update failed: " . $conn->error;
        }
    }
}

if (isset($_GET['msg'])) {
    $message = $_GET['msg'] === 'added' ? 'Record added successfully.' : 'Record updated successfully.';
}

// ---------- Data for Add/Edit form ----------
$editRow = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM `$table` WHERE `$pk` = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $editRow = $stmt->get_result()->fetch_assoc();
    if (!$editRow) { $action = 'list'; }
}

$bannerIcon = ['central' => 'admin', 'traffic' => 'traffic', 'energy' => 'energy', 'hospital' => 'hospital'][$config['module'] ?? ''] ?? 'admin';

$page_title = $config['label'];
include __DIR__ . '/../includes/header.php';
?>

<?php page_banner($bannerIcon, $config['label'], is_central_admin() ? 'Central Manager management' : role_label(current_role()) . ' domain data'); ?>

<div class="page-title">
  <div></div>
  <div>
    <?php if ($action === 'list'): ?>
      <a href="manage.php?table=<?php echo urlencode($table); ?>&action=add" class="btn">
        <i class="fa-solid fa-plus"></i> Add New Record
      </a>
    <?php else: ?>
      <a href="manage.php?table=<?php echo urlencode($table); ?>" class="btn btn-secondary">
        <i class="fa-solid fa-arrow-left"></i> Back to List
      </a>
    <?php endif; ?>
  </div>
</div>

<?php if ($message): ?><div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo e($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo e($error); ?></div><?php endif; ?>

<?php if ($action === 'list'): ?>

  <div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th><?php echo e($pk); ?></th>
        <?php foreach ($fields as $col => $def): ?>
          <th><?php echo e($def['label']); ?></th>
        <?php endforeach; ?>
        <th style="text-align:right;">Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php
    $res = $conn->query("SELECT * FROM `$table` ORDER BY `$pk`");
    while ($row = $res->fetch_assoc()):
    ?>
      <tr>
        <td><strong>#<?php echo e($row[$pk]); ?></strong></td>
        <?php foreach ($fields as $col => $def): ?>
          <td>
            <?php
            if ($def['type'] === 'fk') {
                $r = $conn->query("SELECT `{$def['ref_display']}` FROM `{$def['ref_table']}` WHERE `{$def['ref_pk']}` = " . (int)$row[$col]);
                $lbl = $r && $r->num_rows ? $r->fetch_assoc()[$def['ref_display']] : $row[$col];
                echo e($lbl);
            } else {
                echo e($row[$col]);
            }
            ?>
          </td>
        <?php endforeach; ?>
        <td style="text-align:right; white-space:nowrap;">
          <a href="manage.php?table=<?php echo urlencode($table); ?>&action=edit&id=<?php echo (int)$row[$pk]; ?>" class="btn btn-sm btn-secondary">
            <i class="fa-solid fa-pen-to-square"></i> Edit
          </a>
          <form method="post" style="display:inline" onsubmit="return confirm('Are you sure you want to delete this record?');">
            <input type="hidden" name="delete_id" value="<?php echo (int)$row[$pk]; ?>">
            <button type="submit" class="btn btn-sm btn-danger">
              <i class="fa-solid fa-trash"></i> Delete
            </button>
          </form>
        </td>
      </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
  </div>

<?php elseif ($action === 'add' || $action === 'edit'): ?>

  <div class="card" style="max-width:600px;">
    <form method="post">
      <input type="hidden" name="mode" value="<?php echo $action; ?>">
      <?php if ($action === 'edit'): ?>
        <input type="hidden" name="pk_value" value="<?php echo (int)$editRow[$pk]; ?>">
      <?php endif; ?>

      <?php foreach ($fields as $col => $def): ?>
        <div class="form-group">
          <label><?php echo e($def['label']); ?><?php echo !empty($def['required']) ? ' *' : ''; ?></label>
          <?php $current = $editRow[$col] ?? ''; ?>

          <?php if ($def['type'] === 'textarea'): ?>
            <textarea name="<?php echo $col; ?>" <?php echo !empty($def['required']) ? 'required' : ''; ?>><?php echo e($current); ?></textarea>

          <?php elseif ($def['type'] === 'select'): ?>
            <select name="<?php echo $col; ?>">
              <?php foreach ($def['options'] as $opt): ?>
                <option value="<?php echo e($opt); ?>" <?php echo ($current === $opt) ? 'selected' : ''; ?>><?php echo e($opt); ?></option>
              <?php endforeach; ?>
            </select>

          <?php elseif ($def['type'] === 'fk'): ?>
            <select name="<?php echo $col; ?>" required>
              <option value="">-- Select --</option>
              <?php foreach (fk_options($conn, $def) as $opt): ?>
                <option value="<?php echo (int)$opt['id']; ?>" <?php echo ((string)$current === (string)$opt['id']) ? 'selected' : ''; ?>>
                  <?php echo e($opt['label']); ?> (#<?php echo (int)$opt['id']; ?>)
                </option>
              <?php endforeach; ?>
            </select>

          <?php elseif ($def['type'] === 'date'): ?>
            <input type="date" name="<?php echo $col; ?>" value="<?php echo e($current); ?>" <?php echo !empty($def['required']) ? 'required' : ''; ?>>

          <?php elseif ($def['type'] === 'number'): ?>
            <input type="number" name="<?php echo $col; ?>" value="<?php echo e($current); ?>" <?php echo !empty($def['required']) ? 'required' : ''; ?>>

          <?php elseif ($def['type'] === 'decimal'): ?>
            <input type="number" step="0.01" name="<?php echo $col; ?>" value="<?php echo e($current); ?>" <?php echo !empty($def['required']) ? 'required' : ''; ?>>

          <?php else: ?>
            <input type="text" name="<?php echo $col; ?>" value="<?php echo e($current); ?>" <?php echo !empty($def['required']) ? 'required' : ''; ?>>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>

      <?php if ($createsLogin && $action === 'add'): ?>
        <hr style="border:none;border-top:1px solid var(--border);margin:18px 0;">
        <h3 style="margin-top:0;">Login Credentials Setup</h3>
        <p class="hint">
          <?php if (($config['login_role'] ?? '') === 'doctor'): ?>
            Hospital Manager sets up login credentials for this doctor so they can access their Doctor Portal, view appointments, managed patients, and review salary.
          <?php else: ?>
            Creates a <?php echo e(role_label($config['login_role'])); ?> account so they can log in and manage the <?php echo e($config['module']); ?> department.
          <?php endif; ?>
        </p>
        <div class="form-group"><label>Username *</label><input type="text" name="login_username" required></div>
        <div class="form-group"><label>Password *</label><input type="password" name="login_password" required></div>
        <div class="form-group"><label>Security Question * (for password reset)</label><input type="text" name="login_security_question" placeholder="e.g. What city do you work in?" required></div>
        <div class="form-group"><label>Answer *</label><input type="text" name="login_security_answer" required></div>
      <?php endif; ?>

      <button type="submit" name="save" value="1" class="btn"><?php echo $action === 'add' ? 'Add Record' : 'Save Changes'; ?></button>
    </form>
  </div>

<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
