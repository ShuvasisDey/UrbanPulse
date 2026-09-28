<?php
require_once __DIR__ . '/../includes/auth.php';
require_citizen();
require_once __DIR__ . '/../includes/table_config.php';
require_once __DIR__ . '/../includes/icons.php';

$citizen_id = $_SESSION['ref_id'];
$message = '';
$error = '';

// ---------- Handle booking ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book'])) {
    $doctor_id = (int)($_POST['doctor_id'] ?? 0);
    $date = $_POST['appointment_date'] ?? '';
    $slot = $_POST['appointment_time'] ?? '';

    if (!$doctor_id || !$date || !in_array($slot, $APPOINTMENT_SLOTS, true)) {
        $error = "Please choose a doctor, date, and a valid time slot.";
    } elseif ($date < date('Y-m-d')) {
        $error = "Please choose today or a future date.";
    } else {
        // Re-check the slot is still free right before inserting.
        $chk = $conn->prepare("SELECT appointment_id FROM Hospital_Appointments
                                WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ?
                                AND status IN ('Pending','Confirmed')");
        $chk->bind_param('iss', $doctor_id, $date, $slot);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $error = "Sorry, that time slot was just taken by another patient. Please choose another.";
        } else {
            $ins = $conn->prepare("INSERT INTO Hospital_Appointments (citizen_id, doctor_id, appointment_date, appointment_time, status)
                                    VALUES (?, ?, ?, ?, 'Pending')");
            $ins->bind_param('iiss', $citizen_id, $doctor_id, $date, $slot);
            $ins->execute();
            $message = "Appointment request submitted for $date at $slot. Hospital management will review and confirm it.";
        }
    }
}

// ---------- Handle cancel ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_id'])) {
    $apptId = (int)$_POST['cancel_id'];
    $upd = $conn->prepare("UPDATE Hospital_Appointments SET status = 'Cancelled'
                            WHERE appointment_id = ? AND citizen_id = ? AND status IN ('Pending','Confirmed')");
    $upd->bind_param('ii', $apptId, $citizen_id);
    $upd->execute();
    $message = "Appointment cancelled.";
}

// ---------- Doctor list ----------
$doctors = $conn->query("SELECT d.doctor_id, d.name, d.specialization, h.name AS hospital
                          FROM Doctors d JOIN Hospitals h ON d.hospital_id = h.hospital_id
                          ORDER BY h.name, d.name");

// ---------- Selected doctor/date -> show slot availability ----------
$selDoctor = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 0;
$selDate = $_GET['date'] ?? date('Y-m-d');
$takenSlots = [];
if ($selDoctor) {
    $t = $conn->prepare("SELECT appointment_time FROM Hospital_Appointments
                          WHERE doctor_id = ? AND appointment_date = ? AND status IN ('Pending','Confirmed')");
    $t->bind_param('is', $selDoctor, $selDate);
    $t->execute();
    $tr = $t->get_result();
    while ($row = $tr->fetch_assoc()) { $takenSlots[] = $row['appointment_time']; }
}

// ---------- My appointments ----------
$mine = $conn->prepare("SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.status,
                                d.name AS doctor_name, d.specialization, h.name AS hospital
                         FROM Hospital_Appointments a
                         JOIN Doctors d ON a.doctor_id = d.doctor_id
                         JOIN Hospitals h ON d.hospital_id = h.hospital_id
                         WHERE a.citizen_id = ?
                         ORDER BY a.appointment_date DESC, a.appointment_id DESC");
$mine->bind_param('i', $citizen_id);
$mine->execute();
$myAppts = $mine->get_result();

function appt_badge($status) {
    $cls = ['Confirmed' => 'badge-paid', 'Pending' => 'badge-pending', 'Cancelled' => 'badge-unpaid', 'Completed' => 'badge-resolved'][$status] ?? 'badge-progress';
    return "<span class='badge $cls'>" . htmlspecialchars($status) . "</span>";
}

$page_title = "Book Doctor Appointment";
include __DIR__ . '/../includes/header.php';

$backBtn = '<a href="hospitals.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> View Hospitals</a>';
page_banner('appointment', 'Book a Doctor Appointment', 'Select your preferred doctor, date, and available time slot for verified medical consultation.', $backBtn); 
?>

<?php if ($message): ?><div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo e($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo e($error); ?></div><?php endif; ?>

<!-- Step 1 & Step 2 Booking Form -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 24px; margin-bottom: 28px;">
  <!-- Doctor & Date Picker -->
  <div class="card" style="margin-bottom:0;">
    <div class="card-header" style="border:none; padding:0; margin-bottom:14px;">
      <h3><i class="fa-solid fa-1" style="color:var(--sky);margin-right:8px;"></i> Select Doctor & Date</h3>
    </div>
    <form method="get">
      <div class="form-group">
        <label>Medical Doctor *</label>
        <select name="doctor_id" required>
          <option value="">-- Choose a doctor --</option>
          <?php $doctors->data_seek(0); while ($d = $doctors->fetch_assoc()): ?>
            <option value="<?php echo (int)$d['doctor_id']; ?>" <?php echo $selDoctor === (int)$d['doctor_id'] ? 'selected' : ''; ?>>
              <?php echo e($d['name']); ?> — <?php echo e($d['specialization']); ?> (<?php echo e($d['hospital']); ?>)
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <div class="form-group">
        <label>Appointment Date *</label>
        <input type="date" name="date" value="<?php echo e($selDate); ?>" min="<?php echo date('Y-m-d'); ?>" required>
      </div>

      <button type="submit" class="btn" style="width:100%;">
        <i class="fa-solid fa-magnifying-glass"></i> Check Time Slots
      </button>
    </form>
  </div>

  <!-- Available Time Slots Picker -->
  <div class="card" style="margin-bottom:0;">
    <div class="card-header" style="border:none; padding:0; margin-bottom:14px;">
      <h3><i class="fa-solid fa-2" style="color:var(--emerald);margin-right:8px;"></i> Choose Open Time Slot</h3>
    </div>
    <?php if (!$selDoctor): ?>
      <p style="color:var(--text-muted); padding-top: 10px;">
        Please select a doctor and date on the left to see live slot availability.
      </p>
    <?php else: ?>
      <p class="hint">Slots marked in grey with strikethrough are already reserved.</p>
      <div class="slot-grid">
        <?php foreach ($APPOINTMENT_SLOTS as $slot): ?>
          <?php if (in_array($slot, $takenSlots, true)): ?>
            <span class="slot-btn taken" title="Already booked"><?php echo e($slot); ?></span>
          <?php else: ?>
            <form method="post" style="margin:0;">
              <input type="hidden" name="doctor_id" value="<?php echo (int)$selDoctor; ?>">
              <input type="hidden" name="appointment_date" value="<?php echo e($selDate); ?>">
              <input type="hidden" name="appointment_time" value="<?php echo e($slot); ?>">
              <button type="submit" name="book" value="1" class="slot-btn" style="width:100%;">
                <i class="fa-solid fa-clock" style="font-size:11px;margin-right:5px;"></i> <?php echo e($slot); ?>
              </button>
            </form>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- My Booked Appointments History -->
<div class="card">
  <div class="card-header" style="border:none; padding:0; margin-bottom:16px;">
    <h3><i class="fa-solid fa-clipboard-list" style="color:var(--emerald);margin-right:8px;"></i> My Appointment Schedule</h3>
  </div>
  <div class="table-wrap" style="margin-bottom:0;">
    <table>
      <thead>
        <tr>
          <th>Doctor & Specialization</th>
          <th>Hospital Center</th>
          <th>Date</th>
          <th>Time Slot</th>
          <th>Current Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if ($myAppts->num_rows === 0): ?>
        <tr><td colspan="6" style="text-align:center;color:var(--text-muted);">No appointments booked yet. Choose a doctor and slot above to book.</td></tr>
      <?php else: ?>
        <?php while ($a = $myAppts->fetch_assoc()): ?>
          <tr>
            <td>
              <strong style="color:var(--text-main); font-weight:700;"><?php echo e($a['doctor_name']); ?></strong><br>
              <span class="hint"><?php echo e($a['specialization']); ?></span>
            </td>
            <td><i class="fa-solid fa-hospital" style="color:var(--emerald);font-size:11px;margin-right:4px;"></i> <?php echo e($a['hospital']); ?></td>
            <td><?php echo e($a['appointment_date']); ?></td>
            <td><strong><?php echo e($a['appointment_time']); ?></strong></td>
            <td><?php echo appt_badge($a['status']); ?></td>
            <td>
              <?php if (in_array($a['status'], ['Pending', 'Confirmed'], true)): ?>
                <form method="post" style="display:inline" onsubmit="return confirm('Are you sure you want to cancel this appointment?');">
                  <input type="hidden" name="cancel_id" value="<?php echo (int)$a['appointment_id']; ?>">
                  <button type="submit" class="btn btn-sm btn-danger">
                    <i class="fa-solid fa-xmark"></i> Cancel
                  </button>
                </form>
              <?php else: ?>
                <span class="hint">—</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
