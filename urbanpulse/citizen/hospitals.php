<?php
require_once __DIR__ . '/../includes/auth.php';
require_citizen();
require_once __DIR__ . '/../includes/icons.php';

// "Available seats" = capacity - number of patients currently NOT discharged, per hospital.
$sql = "SELECT h.hospital_id, h.name, h.location, h.capacity,
        (SELECT COUNT(*) FROM Patients p
         JOIN Doctors d ON p.doctor_id = d.doctor_id
         WHERE d.hospital_id = h.hospital_id AND p.status <> 'Discharged') AS occupied
        FROM Hospitals h
        ORDER BY h.name";
$res = $conn->query($sql);

$hospitals = [];
$totalCap = 0;
$totalOcc = 0;
while ($row = $res->fetch_assoc()) {
    $hospitals[] = $row;
    $totalCap += (int)$row['capacity'];
    $totalOcc += (int)$row['occupied'];
}
$totalAvail = max(0, $totalCap - $totalOcc);

$page_title = "Hospitals & Bed Capacity";
include __DIR__ . '/../includes/header.php';

$actionBtn = '<a href="book_appointment.php" class="btn"><i class="fa-solid fa-calendar-plus"></i> Book Doctor Appointment</a>';
page_banner('hospital', 'Hospitals & Live Bed Availability', 'Real-time ICU and ward bed availability tracking across all municipal medical centers.', $actionBtn); 
?>

<!-- Healthcare Key Stats -->
<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-header">
      <span class="stat-icon"><i class="fa-solid fa-hospital-user"></i></span>
      <span class="stat-trend">Connected</span>
    </div>
    <div class="stat-body">
      <div class="num"><?php echo count($hospitals); ?></div>
      <div class="label">Medical Centers</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-header">
      <span class="stat-icon" style="color:var(--emerald);"><i class="fa-solid fa-bed-pulse"></i></span>
      <span class="stat-trend" style="background:rgba(16,185,129,0.15);color:#34d399;border-color:rgba(16,185,129,0.3);">Live Open</span>
    </div>
    <div class="stat-body">
      <div class="num" style="color:var(--emerald);"><?php echo $totalAvail; ?></div>
      <div class="label">Total Available Beds</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-header">
      <span class="stat-icon" style="color:var(--sky);"><i class="fa-solid fa-hospital"></i></span>
      <span class="stat-trend">City Capacity</span>
    </div>
    <div class="stat-body">
      <div class="num"><?php echo $totalCap; ?></div>
      <div class="label">Total Hospital Beds</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-header">
      <span class="stat-icon" style="color:var(--amber);"><i class="fa-solid fa-bed"></i></span>
      <span class="stat-trend">In-Patient</span>
    </div>
    <div class="stat-body">
      <div class="num"><?php echo $totalOcc; ?></div>
      <div class="label">Currently Occupied</div>
    </div>
  </div>
</div>

<!-- Hospital Capacity Table -->
<div class="card">
  <div class="card-header" style="border:none; padding:0; margin-bottom:16px;">
    <h3><i class="fa-solid fa-square-h" style="color:var(--emerald);margin-right:8px;"></i> Hospital Centers & Capacity Status</h3>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Hospital Name</th>
          <th>Location</th>
          <th>Total Capacity</th>
          <th>Occupied</th>
          <th>Available Seats</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($hospitals)): ?>
        <tr><td colspan="6" style="text-align:center;color:var(--text-muted);">No hospital centers on record.</td></tr>
      <?php else: ?>
        <?php foreach ($hospitals as $row):
            $available = max(0, (int)$row['capacity'] - (int)$row['occupied']);
        ?>
          <tr>
            <td><strong style="color:var(--text-main); font-weight:700;"><?php echo e($row['name']); ?></strong></td>
            <td><i class="fa-solid fa-location-dot" style="color:var(--sky);font-size:11px;margin-right:5px;"></i> <?php echo e($row['location']); ?></td>
            <td><?php echo (int)$row['capacity']; ?> Beds</td>
            <td><?php echo (int)$row['occupied']; ?> Patients</td>
            <td>
              <span class="badge <?php echo $available > 0 ? 'badge-paid' : 'badge-unpaid'; ?>">
                <i class="fa-solid <?php echo $available > 0 ? 'fa-check' : 'fa-xmark'; ?>"></i>
                <?php echo $available; ?> Seats Free
              </span>
            </td>
            <td>
              <a href="book_appointment.php" class="btn btn-sm btn-secondary">
                <i class="fa-solid fa-calendar-check"></i> Book Doctor
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Doctors Directory -->
<div class="card">
  <div class="card-header" style="border:none; padding:0; margin-bottom:16px;">
    <h3><i class="fa-solid fa-user-doctor" style="color:var(--sky);margin-right:8px;"></i> Certified Medical Specialists & Doctors</h3>
  </div>
  <div class="table-wrap" style="margin-bottom:0;">
    <table>
      <thead>
        <tr>
          <th>Doctor Name</th>
          <th>Medical Specialization</th>
          <th>Affiliated Hospital</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php
      $docs = $conn->query("SELECT d.doctor_id, d.name, d.specialization, h.name AS hospital
                             FROM Doctors d JOIN Hospitals h ON d.hospital_id = h.hospital_id
                             ORDER BY h.name, d.name");
      if (!$docs || $docs->num_rows === 0):
      ?>
        <tr><td colspan="4" style="text-align:center;color:var(--text-muted);">No doctor profiles found.</td></tr>
      <?php else: ?>
        <?php while ($dRow = $docs->fetch_assoc()): ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:34px;height:34px;border-radius:50%;background:rgba(56,189,248,0.15);border:1px solid rgba(56,189,248,0.3);display:flex;align-items:center;justify-content:center;color:var(--sky);">
                  <i class="fa-solid fa-stethoscope" style="font-size:13px;"></i>
                </div>
                <strong style="color:var(--text-main); font-weight:700;"><?php echo e($dRow['name']); ?></strong>
              </div>
            </td>
            <td><span class="badge badge-info"><?php echo e($dRow['specialization']); ?></span></td>
            <td><i class="fa-solid fa-hospital" style="color:var(--emerald);font-size:11px;margin-right:5px;"></i> <?php echo e($dRow['hospital']); ?></td>
            <td>
              <a href="book_appointment.php?doctor_id=<?php echo (int)$dRow['doctor_id']; ?>" class="btn btn-sm">
                <i class="fa-solid fa-clock"></i> Book Slot
              </a>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
