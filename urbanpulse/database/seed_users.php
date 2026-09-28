<?php
// Run this in your browser (http://localhost/urbanpulse/database/seed_users.php)
// AFTER importing smart_city_full.sql (or add_users_table.sql + migration_v2.sql).
// It creates one login account per existing Central_Admin, Traffic_Admin,
// Energy_Admin, Hospital_Admin (password: admin123) and per existing
// Citizen (password: citizen123), with a default security question for
// password resets. Safe to re-run — existing usernames are skipped.
//
// IMPORTANT: delete this file (or move it out of the web root) once you're
// done seeding demo data — anyone who can reach this URL can see every
// generated username and the default password.
require_once __DIR__ . '/../includes/db_connect.php';

$created = [];
$skipped = [];

function seed_login($conn, $username, $password, $role, $refId, $question, $answer, &$created, &$skipped) {
    $check = $conn->prepare("SELECT user_id FROM Users WHERE username = ?");
    $check->bind_param('s', $username);
    $check->execute();
    if ($check->get_result()->num_rows > 0) { $skipped[] = $username; return; }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $answerHash = password_hash($answer, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO Users (username, password, role, ref_id, security_question, security_answer) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('sssiss', $username, $hash, $role, $refId, $question, $answerHash);
    $stmt->execute();
    $created[] = "$username ($role, password: $password)";
}

// Central admins (username from email)
$res = $conn->query("SELECT admin_id, name, email FROM Central_Admin");
while ($row = $res->fetch_assoc()) {
    $username = strtolower(explode('@', $row['email'])[0]);
    seed_login($conn, $username, 'admin123', 'admin', $row['admin_id'], "What city do you work in?", 'chittagong', $created, $skipped);
}

// Module admins (no email column -> derive username from name + role)
$moduleTables = [
    'Traffic_Admin'  => 'traffic_admin',
    'Energy_Admin'   => 'energy_admin',
    'Hospital_Admin' => 'hospital_admin',
];
foreach ($moduleTables as $table => $role) {
    $res = $conn->query("SELECT admin_id, name FROM `$table`");
    while ($row = $res->fetch_assoc()) {
        $slug = strtolower(preg_replace('/[^a-z0-9]/i', '', $row['name']));
        $username = $slug . '.' . str_replace('_admin', '', $role);
        seed_login($conn, $username, 'admin123', $role, $row['admin_id'], "What city do you work in?", 'chittagong', $created, $skipped);
    }
}

// Citizens (username from email)
$res = $conn->query("SELECT citizen_id, name, email FROM Citizen");
while ($row = $res->fetch_assoc()) {
    $username = strtolower(explode('@', $row['email'])[0]);
    seed_login($conn, $username, 'citizen123', 'citizen', $row['citizen_id'], "What is your favorite color?", 'blue', $created, $skipped);
}
?>
<!DOCTYPE html>
<html><head><title>Seed Users</title>
<link rel="stylesheet" href="../assets/css/style.css"></head>
<body>
<div class="container">
  <h2>Demo Accounts Seeded</h2>
  <p><strong>Created:</strong></p>
  <ul>
    <?php foreach ($created as $c) echo "<li>" . htmlspecialchars($c) . "</li>"; ?>
  </ul>
  <?php if ($skipped): ?>
    <p><strong>Already existed (skipped):</strong> <?php echo htmlspecialchars(implode(', ', $skipped)); ?></p>
  <?php endif; ?>
  <p>Security answers: admins/module admins &rarr; <em>chittagong</em>, citizens &rarr; <em>blue</em> (all lowercase, used for Forgot Password).</p>
  <p><strong>Please delete this file after seeding</strong> — it's a public page that reveals every username and the default password.</p>
  <a href="../auth/login.php">Go to Login</a>
</div>
</body></html>
