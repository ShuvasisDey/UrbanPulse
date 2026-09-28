<?php


$TABLES = [

  'Central_Admin' => [
    'label' => 'Central Managers',
    'pk' => 'admin_id',
    'module' => 'central',
    'fields' => [
      'name'  => ['label' => 'Name', 'type' => 'text', 'required' => true],
      'email' => ['label' => 'Email', 'type' => 'text', 'required' => true],
    ],
  ],

  'Central_Notifications' => [
    'label' => 'Notifications',
    'pk' => 'notification_id',
    'module' => 'central',
    'fields' => [
      'message'    => ['label' => 'Message', 'type' => 'textarea', 'required' => true],
      'date_sent'  => ['label' => 'Date Sent', 'type' => 'date', 'required' => true],
      'admin_id'   => ['label' => 'Manager', 'type' => 'fk', 'ref_table' => 'Central_Admin', 'ref_pk' => 'admin_id', 'ref_display' => 'name'],
    ],
  ],

  'Central_Reports' => [
    'label' => 'Reports',
    'pk' => 'report_id',
    'module' => 'central',
    'fields' => [
      'module'      => ['label' => 'Module', 'type' => 'select', 'options' => ['Traffic','Energy','Hospital','Citizen']],
      'report_type' => ['label' => 'Report Type', 'type' => 'text'],
      'admin_id'    => ['label' => 'Manager', 'type' => 'fk', 'ref_table' => 'Central_Admin', 'ref_pk' => 'admin_id', 'ref_display' => 'name'],
    ],
  ],

  'Citizen' => [
    'label' => 'Citizens',
    'pk' => 'citizen_id',
    'module' => 'central',
    'fields' => [
      'name'       => ['label' => 'Name', 'type' => 'text', 'required' => true],
      'age'        => ['label' => 'Age', 'type' => 'number'],
      'gender'     => ['label' => 'Gender', 'type' => 'select', 'options' => ['Male','Female','Other']],
      'address'    => ['label' => 'Address', 'type' => 'text'],
      'contact_no' => ['label' => 'Contact No', 'type' => 'text'],
      'email'      => ['label' => 'Email', 'type' => 'text'],
    ],
  ],

  'Citizen_Complaints' => [
    'label' => 'Complaints',
    'pk' => 'complaint_id',
    'module' => 'central',
    'fields' => [
      'description' => ['label' => 'Description', 'type' => 'textarea', 'required' => true],
      'status'      => ['label' => 'Status', 'type' => 'select', 'options' => ['Pending','In Progress','Resolved']],
      'citizen_id'  => ['label' => 'Citizen', 'type' => 'fk', 'ref_table' => 'Citizen', 'ref_pk' => 'citizen_id', 'ref_display' => 'name'],
    ],
  ],

  'Citizen_Feedback' => [
    'label' => 'Feedback',
    'pk' => 'feedback_id',
    'module' => 'central',
    'fields' => [
      'message'    => ['label' => 'Message', 'type' => 'textarea', 'required' => true],
      'citizen_id' => ['label' => 'Citizen', 'type' => 'fk', 'ref_table' => 'Citizen', 'ref_pk' => 'citizen_id', 'ref_display' => 'name'],
    ],
  ],

  'Traffic_Admin' => [
    'label' => 'Traffic Managers',
    'pk' => 'admin_id',
    'module' => 'traffic',
    'creates_login' => true,
    'login_role' => 'traffic_admin',
    'fields' => [
      'name'             => ['label' => 'Name', 'type' => 'text', 'required' => true],
      'central_admin_id' => ['label' => 'Central Manager', 'type' => 'fk', 'ref_table' => 'Central_Admin', 'ref_pk' => 'admin_id', 'ref_display' => 'name'],
    ],
  ],

  'Traffic_Vehicles' => [
    'label' => 'Vehicles',
    'pk' => 'vehicle_id',
    'module' => 'traffic',
    'fields' => [
      'registration_no' => ['label' => 'Registration No', 'type' => 'text', 'required' => true],
      'type'             => ['label' => 'Type', 'type' => 'select', 'options' => ['Car','Motorbike','Truck','Bus','Other']],
      'citizen_id'       => ['label' => 'Owner (Citizen)', 'type' => 'fk', 'ref_table' => 'Citizen', 'ref_pk' => 'citizen_id', 'ref_display' => 'name'],
      'admin_id'         => ['label' => 'Traffic Manager', 'type' => 'fk', 'ref_table' => 'Traffic_Admin', 'ref_pk' => 'admin_id', 'ref_display' => 'name'],
    ],
  ],

  'Traffic_Violations' => [
    'label' => 'Violations',
    'pk' => 'violation_id',
    'module' => 'traffic',
    'fields' => [
      'type'        => ['label' => 'Violation Type', 'type' => 'text', 'required' => true],
      'fine_amount' => ['label' => 'Fine Amount', 'type' => 'decimal'],
      'vehicle_id'  => ['label' => 'Vehicle', 'type' => 'fk', 'ref_table' => 'Traffic_Vehicles', 'ref_pk' => 'vehicle_id', 'ref_display' => 'registration_no'],
    ],
  ],

  'Traffic_Fines' => [
    'label' => 'Traffic Fines',
    'pk' => 'fine_id',
    'module' => 'traffic',
    'fields' => [
      'amount'         => ['label' => 'Amount', 'type' => 'decimal', 'required' => true],
      'payment_status' => ['label' => 'Payment Status', 'type' => 'select', 'options' => ['Paid','Unpaid','Pending']],
      'violation_id'   => ['label' => 'Violation', 'type' => 'fk', 'ref_table' => 'Traffic_Violations', 'ref_pk' => 'violation_id', 'ref_display' => 'type'],
    ],
  ],

  'Energy_Admin' => [
    'label' => 'Energy Managers',
    'pk' => 'admin_id',
    'module' => 'energy',
    'creates_login' => true,
    'login_role' => 'energy_admin',
    'fields' => [
      'name'             => ['label' => 'Name', 'type' => 'text', 'required' => true],
      'central_admin_id' => ['label' => 'Central Manager', 'type' => 'fk', 'ref_table' => 'Central_Admin', 'ref_pk' => 'admin_id', 'ref_display' => 'name'],
    ],
  ],

  'Energy_Account' => [
    'label' => 'Energy Accounts',
    'pk' => 'account_id',
    'module' => 'energy',
    'fields' => [
      'meter_no'        => ['label' => 'Meter No', 'type' => 'text', 'required' => true],
      'connection_type' => ['label' => 'Connection Type', 'type' => 'select', 'options' => ['Residential','Commercial','Industrial']],
      'citizen_id'      => ['label' => 'Citizen', 'type' => 'fk', 'ref_table' => 'Citizen', 'ref_pk' => 'citizen_id', 'ref_display' => 'name'],
      'admin_id'        => ['label' => 'Energy Manager', 'type' => 'fk', 'ref_table' => 'Energy_Admin', 'ref_pk' => 'admin_id', 'ref_display' => 'name'],
    ],
  ],

  'Energy_Usage' => [
    'label' => 'Energy Usage',
    'pk' => 'usage_id',
    'module' => 'energy',
    'fields' => [
      'units'      => ['label' => 'Units', 'type' => 'number', 'required' => true],
      'month'      => ['label' => 'Month', 'type' => 'text'],
      'account_id' => ['label' => 'Account', 'type' => 'fk', 'ref_table' => 'Energy_Account', 'ref_pk' => 'account_id', 'ref_display' => 'meter_no'],
    ],
  ],

  'Energy_Bill' => [
    'label' => 'Energy Bills',
    'pk' => 'bill_id',
    'module' => 'energy',
    'fields' => [
      'amount'         => ['label' => 'Amount', 'type' => 'decimal', 'required' => true],
      'payment_status' => ['label' => 'Payment Status', 'type' => 'select', 'options' => ['Paid','Unpaid','Pending']],
      'usage_id'       => ['label' => 'Usage Record', 'type' => 'fk', 'ref_table' => 'Energy_Usage', 'ref_pk' => 'usage_id', 'ref_display' => 'month'],
    ],
  ],

  'Hospital_Admin' => [
    'label' => 'Hospital Managers',
    'pk' => 'admin_id',
    'module' => 'hospital',
    'creates_login' => true,
    'login_role' => 'hospital_admin',
    'fields' => [
      'name'             => ['label' => 'Name', 'type' => 'text', 'required' => true],
      'central_admin_id' => ['label' => 'Central Manager', 'type' => 'fk', 'ref_table' => 'Central_Admin', 'ref_pk' => 'admin_id', 'ref_display' => 'name'],
    ],
  ],

  'Hospitals' => [
    'label' => 'Hospitals',
    'pk' => 'hospital_id',
    'module' => 'hospital',
    'fields' => [
      'name'     => ['label' => 'Name', 'type' => 'text', 'required' => true],
      'location' => ['label' => 'Location', 'type' => 'text'],
      'capacity' => ['label' => 'Capacity', 'type' => 'number'],
    ],
  ],

  'Doctors' => [
    'label' => 'Doctors',
    'pk' => 'doctor_id',
    'module' => 'hospital',
    'creates_login' => true,
    'login_role' => 'doctor',
    'fields' => [
      'name'           => ['label' => 'Doctor Name', 'type' => 'text', 'required' => true],
      'specialization' => ['label' => 'Specialization', 'type' => 'text', 'required' => true],
      'hospital_id'    => ['label' => 'Affiliated Hospital', 'type' => 'fk', 'ref_table' => 'Hospitals', 'ref_pk' => 'hospital_id', 'ref_display' => 'name'],
      'salary'         => ['label' => 'Monthly Salary (৳)', 'type' => 'decimal', 'required' => true],
      'contact_no'     => ['label' => 'Contact Number', 'type' => 'text'],
      'email'          => ['label' => 'Official Email', 'type' => 'text'],
    ],
  ],

  'Patients' => [
    'label' => 'Patients',
    'pk' => 'patient_id',
    'module' => 'hospital',
    'fields' => [
      'disease'    => ['label' => 'Disease', 'type' => 'text', 'required' => true],
      'status'     => ['label' => 'Status', 'type' => 'select', 'options' => ['Stable','Recovering','Critical','Under Treatment','Discharged']],
      'citizen_id' => ['label' => 'Citizen', 'type' => 'fk', 'ref_table' => 'Citizen', 'ref_pk' => 'citizen_id', 'ref_display' => 'name'],
      'doctor_id'  => ['label' => 'Doctor', 'type' => 'fk', 'ref_table' => 'Doctors', 'ref_pk' => 'doctor_id', 'ref_display' => 'name'],
      'admin_id'   => ['label' => 'Hospital Manager', 'type' => 'fk', 'ref_table' => 'Hospital_Admin', 'ref_pk' => 'admin_id', 'ref_display' => 'name'],
    ],
  ],

  'Hospital_Appointments' => [
    'label' => 'Appointments',
    'pk' => 'appointment_id',
    'module' => 'hospital',
    'auto_pk' => true,
    'fields' => [
      'citizen_id'       => ['label' => 'Citizen', 'type' => 'fk', 'ref_table' => 'Citizen', 'ref_pk' => 'citizen_id', 'ref_display' => 'name'],
      'doctor_id'        => ['label' => 'Doctor', 'type' => 'fk', 'ref_table' => 'Doctors', 'ref_pk' => 'doctor_id', 'ref_display' => 'name'],
      'appointment_date' => ['label' => 'Date', 'type' => 'date', 'required' => true],
      'appointment_time' => ['label' => 'Time Slot', 'type' => 'text', 'required' => true],
      'status'           => ['label' => 'Status', 'type' => 'select', 'options' => ['Pending','Confirmed','Cancelled','Completed']],
    ],
  ],

];

// Fixed daily appointment slots offered to citizens when booking.
$APPOINTMENT_SLOTS = ['09:00 AM','10:00 AM','11:00 AM','12:00 PM','02:00 PM','03:00 PM','04:00 PM','05:00 PM'];
