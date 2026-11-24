<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/../config.php';

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'parent') {
    header("Location: ../login.php");
    exit;
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$parentId = (int)($_SESSION['parent_id'] ?? $_SESSION['user_id'] ?? 0);
$parentName = $_SESSION['name'] ?? 'Ahmed Khan';
$selectedHospitalId = (int)($_GET['hospital_id'] ?? 0);
$message = "";
$error = "";

// Fetch parent's children
$children = [];
if ($parentId > 0) {
    $stmt = $conn->prepare("SELECT id, child_name FROM children WHERE parent_id = ? ORDER BY child_name ASC");
    $stmt->bind_param("i", $parentId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $children[] = $row;
    }
    $stmt->close();
}

// Fetch only Available and Approved hospitals
$hospitals = [];
$hRes = $conn->query("
    SELECT id, hospital_name, city, address 
    FROM hospitals 
    WHERE LOWER(status) = 'approved' 
      AND (availability = 'Available' OR availability IS NULL OR availability = '')
    ORDER BY hospital_name ASC
");
if ($hRes) {
    while ($row = $hRes->fetch_assoc()) {
        $hospitals[] = $row;
    }
}

// Book Appointment POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'book_appointment') {
    $childId = (int)($_POST['child_id'] ?? 0);
    $hospitalId = (int)($_POST['hospital_id'] ?? 0);
    $appointmentDate = $_POST['appointment_date'] ?? '';
    $appointmentTime = $_POST['appointment_time'] ?? '10:00:00';
    $notes = trim($_POST['notes'] ?? 'Polio Vaccine');

    if ($parentId <= 0) {
        $error = "Session expired. Please login again.";
    } elseif (!$childId || !$hospitalId || !$appointmentDate) {
        $error = "Please fill all required booking fields.";
    } elseif ($appointmentDate < date('Y-m-d')) {
        $error = "Please select today or a future date.";
    } else {
        // Verify chosen hospital is currently approved and available
        $checkH = $conn->prepare("SELECT id FROM hospitals WHERE id = ? AND LOWER(status) = 'approved' AND (availability = 'Available' OR availability IS NULL OR availability = '')");
        $checkH->bind_param("i", $hospitalId);
        $checkH->execute();
        $hAvailRes = $checkH->get_result();
        if ($hAvailRes->num_rows === 0) {
            $error = "The selected hospital is currently unavailable for appointments. Please choose an available hospital.";
            $checkH->close();
        } else {
            $checkH->close();
            $status = "Pending";
            $stmt = $conn->prepare("
                INSERT INTO appointments (parent_id, child_id, hospital_id, appointment_date, appointment_time, notes, status)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("iiissss", $parentId, $childId, $hospitalId, $appointmentDate, $appointmentTime, $notes, $status);
            if ($stmt->execute()) {
                $message = "Vaccination appointment booked successfully! Pending hospital confirmation.";
            } else {
                $error = "Failed to book appointment: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Cancel Appointment POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_appointment') {
    $appId = (int)($_POST['appointment_id'] ?? 0);
    if ($parentId > 0 && $appId > 0) {
        $stmt = $conn->prepare("DELETE FROM appointments WHERE id = ? AND parent_id = ? AND status = 'Pending'");
        $stmt->bind_param("ii", $appId, $parentId);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $message = "Appointment request cancelled.";
        } else {
            $error = "Unable to cancel appointment.";
        }
        $stmt->close();
    }
}

// Fetch Appointments for this Parent
$appointments = [];
if ($parentId > 0) {
    $aRes = $conn->query("
        SELECT a.*, c.child_name, h.hospital_name, h.city
        FROM appointments a
        LEFT JOIN children c ON a.child_id = c.id
        LEFT JOIN hospitals h ON a.hospital_id = h.id
        WHERE a.parent_id = {$parentId}
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
    ");
    if ($aRes) {
        while ($row = $aRes->fetch_assoc()) {
            $appointments[] = $row;
        }
    }
}

// Fallback demo row if empty
$demoAppointments = [
    ['id' => 'd1', 'child_name' => 'Muhammad Ali', 'notes' => 'Polio Vaccine', 'hospital_name' => 'City Hospital', 'city' => 'Karachi', 'appointment_date' => '2026-11-20', 'appointment_time' => '10:30:00', 'status' => 'Pending']
];
$displayAppointments = !empty($appointments) ? $appointments : $demoAppointments;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<!-- Favicon / Brand Logo -->
<link rel="icon" type="image/svg+xml" href="/vaccination/assets/images/favicon.svg">
<link rel="alternate icon" type="image/png" href="/vaccination/assets/images/favicon.png">
<link rel="shortcut icon" href="/vaccination/favicon.ico">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Book Hospital | Vaccination Management System</title>

<!-- Fonts & Icons from Project Theme -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>
:root {
    --primary: #087e8b;
    --primary-hover: #06646f;
    --secondary: #13a6a6;
    --dark: #102b46;
    --light: #eaf8f8;
    --text: #647487;
    --white: #ffffff;
    --topbar-bg: #076b79;

    --sidebar-bg: #102b46;
    --sidebar-hover: #163a5d;
    --sidebar-active-gradient: linear-gradient(135deg, #087e8b 0%, #13a6a6 100%);
    --sidebar-text: #95b0c7;
    --body-bg: #f0f7f8;
    --card-bg: #ffffff;
    --text-dark: #102b46;
    --text-muted: #647487;
    --border-color: #d8eaec;
}

* { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
body { background-color: var(--body-bg); color: var(--text-dark); display: flex; min-height: 100vh; overflow-x: hidden; }

/* SIDEBAR */
.sidebar {
    width: 265px;
    background-color: var(--sidebar-bg);
    color: var(--sidebar-text);
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
    transition: all 0.3s ease;
    z-index: 1000;
    border-right: 1px solid rgba(255, 255, 255, 0.05);
    position: sticky;
    top: 0;
    height: 100vh;
    overflow-y: auto;
}
.sidebar::-webkit-scrollbar {
    width: 4px;
}
.sidebar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.15);
    border-radius: 4px;
}

.sidebar-header {
    padding: 22px 16px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    position: relative;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.brand-block {
    display: flex;
    align-items: center;
    gap: 12px;
    overflow: hidden;
    text-decoration: none;
    cursor: pointer;
    transition: opacity 0.2s, transform 0.2s;
}
.brand-block:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}

.brand-icon {
    width: 42px;
    height: 42px;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    border-radius: 12px;
    display: grid;
    place-items: center;
    color: #ffffff;
    font-size: 20px;
    box-shadow: 0 4px 14px rgba(8, 126, 139, 0.45);
    flex-shrink: 0;
}

.brand-text { line-height: 1.15; transition: opacity 0.2s; }
.brand-title {
    font-family: 'Playfair Display', serif;
    font-size: 16px;
    font-weight: 700;
    color: #ffffff;
    letter-spacing: 0.5px;
    display: block;
}
.brand-sub {
    font-size: 10px;
    color: #8faec4;
    font-weight: 600;
    letter-spacing: 1px;
    text-transform: uppercase;
    display: block;
    margin-top: 2px;
}

.sidebar-collapse-btn {
    width: 28px;
    height: 28px;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 8px;
    color: #cbd5e1;
    display: grid;
    place-items: center;
    cursor: pointer;
    font-size: 12px;
    transition: all 0.25s ease;
    flex-shrink: 0;
}
.sidebar-collapse-btn:hover {
    background: var(--primary);
    color: #ffffff;
    border-color: var(--primary);
}

.sidebar-nav {
    flex: 1;
    padding: 12px 12px 14px;
    display: flex;
    flex-direction: column;
    gap: 5px;
    overflow-y: auto;
}

.sidebar-nav a {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 11px 16px;
    color: var(--sidebar-text);
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
    border-radius: 10px;
    transition: all 0.25s ease;
}

.sidebar-nav a i { font-size: 16px; width: 20px; text-align: center; flex-shrink: 0; }
.sidebar-nav a:hover { background-color: var(--sidebar-hover); color: #ffffff; transform: translateX(3px); }
.sidebar-nav a.active {
    background: var(--sidebar-active-gradient);
    color: #ffffff;
    font-weight: 600;
    box-shadow: 0 4px 16px rgba(8, 126, 139, 0.5);
}

/* COLLAPSED SIDEBAR STATE */
.sidebar.collapsed {
    width: 76px;
}
.sidebar.collapsed .brand-text {
    display: none;
}
.sidebar.collapsed .brand-block {
    justify-content: center;
    width: 100%;
}
.sidebar.collapsed .sidebar-header {
    padding: 18px 8px 14px;
    justify-content: center;
    flex-direction: column;
    gap: 10px;
}
.sidebar.collapsed .sidebar-collapse-btn #collapseIcon {
    transform: rotate(180deg);
}
.sidebar.collapsed .sidebar-nav {
    padding: 12px 8px 14px;
}
.sidebar.collapsed .sidebar-nav a {
    justify-content: center;
    padding: 12px 0;
    gap: 0;
    position: relative;
}
.sidebar.collapsed .sidebar-nav a span {
    display: none;
}
.sidebar.collapsed .sidebar-nav a:hover {
    transform: none;
}
.sidebar.collapsed .sidebar-nav a:hover::after {
    content: attr(data-tooltip);
    position: absolute;
    left: 80px;
    top: 50%;
    transform: translateY(-50%);
    background: var(--dark);
    color: #ffffff;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
    white-space: nowrap;
    z-index: 1001;
    box-shadow: 0 4px 14px rgba(0,0,0,0.25);
    border: 1px solid rgba(255, 255, 255, 0.1);
    pointer-events: none;
}
.sidebar.collapsed .sidebar-health-widget {
    padding: 12px 6px;
    margin: 10px 8px;
}
.sidebar.collapsed .widget-title,
.sidebar.collapsed .widget-family-illustration {
    display: none;
}
.sidebar.collapsed .widget-heart-icon {
    margin: 0 auto;
    width: 36px;
    height: 36px;
    font-size: 16px;
}

/* Sidebar Health Promo Widget */
.sidebar-health-widget {
    margin: 14px 12px 20px;
    background: linear-gradient(135deg, #0e2b44 0%, #153e61 100%);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 14px;
    padding: 18px 14px;
    text-align: center;
}
.widget-heart-icon {
    width: 44px;
    height: 44px;
    background: rgba(8, 126, 139, 0.25);
    border-radius: 50%;
    display: grid;
    place-items: center;
    color: #13a6a6;
    font-size: 20px;
    margin: 0 auto 10px;
}
.widget-title { font-size: 13px; font-weight: 700; color: #ffffff; line-height: 1.3; margin-bottom: 12px; }
.widget-family-illustration { display: flex; justify-content: center; gap: 8px; }
.widget-family-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #eaf8f8;
    color: var(--primary);
    display: grid;
    place-items: center;
    font-size: 14px;
    border: 2px solid #ffffff;
}

/* MAIN CONTENT */
.main-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 0;
    overflow-y: auto;
}

.top-header {
    height: 72px;
    background: #ffffff;
    border-bottom: 1px solid var(--border-color);
    padding: 0 32px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 99;
}
.header-left { display: flex; align-items: center; gap: 14px; }
.mobile-only-toggle {
    display: none;
    background: none;
    border: none;
    font-size: 20px;
    color: var(--dark);
    cursor: pointer;
}
.header-right { display: flex; align-items: center; gap: 16px; }

.notif-bell {
    position: relative;
    width: 38px;
    height: 38px;
    background: var(--light);
    border: 1px solid #d5ecec;
    border-radius: 50%;
    display: grid;
    place-items: center;
    color: var(--primary);
    font-size: 15px;
    text-decoration: none;
}
.notif-badge {
    position: absolute;
    top: 2px;
    right: 2px;
    background: #ef4444;
    color: #ffffff;
    font-size: 10px;
    font-weight: 700;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    display: grid;
    place-items: center;
    border: 2px solid #ffffff;
}

.user-profile-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--dark);
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    padding: 5px 10px 5px 5px;
    border-radius: 25px;
}
.user-profile-btn .avatar-circle {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
    display: grid;
    place-items: center;
}

/* PAGE CONTAINER */
.page-container {
    padding: 26px 32px 42px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* BANNER CARD */
.banner-card {
    background: var(--card-bg);
    border-radius: 14px;
    border: 1px solid var(--border-color);
    padding: 22px 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 4px 18px rgba(16, 43, 70, 0.04);
}
.banner-title-block h2 { font-size: 22px; font-weight: 700; color: var(--dark); line-height: 1.2; }
.breadcrumb-trail { font-size: 12px; color: var(--text-muted); margin-top: 4px; }
.breadcrumb-trail a { color: var(--primary); text-decoration: none; }

.btn-primary-action {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: #ffffff;
    border: none;
    padding: 11px 22px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 14px rgba(8, 126, 139, 0.35);
    transition: all 0.2s;
    text-decoration: none;
}
.btn-primary-action:hover {
    background: var(--primary-hover);
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(8, 126, 139, 0.45);
}

/* CONTENT CARD & TABLE */
.content-card {
    background: var(--card-bg);
    border-radius: 14px;
    border: 1px solid var(--border-color);
    padding: 24px;
    box-shadow: 0 4px 18px rgba(16, 43, 70, 0.04);
}

.table-responsive { overflow-x: auto; }
.modern-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.modern-table th {
    background-color: var(--light);
    color: var(--dark);
    font-weight: 600;
    text-align: left;
    padding: 12px 16px;
    border-bottom: 2px solid var(--border-color);
    font-size: 12px;
}
.modern-table td {
    padding: 14px 16px;
    border-bottom: 1px solid #edf5f6;
    color: var(--dark);
    vertical-align: middle;
}
.modern-table tbody tr:hover { background-color: #f7fcfc; }

.badge-status {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}
.badge-pending { background: #fef3c7; color: #b45309; }
.badge-approved { background: #e6f8f0; color: #0b6b43; }
.badge-completed { background: #e0f2fe; color: #0369a1; }
.badge-rejected { background: #fdeeee; color: #a12626; }

.btn-cancel-app {
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
    padding: 5px 12px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-cancel-app:hover { background: #dc2626; color: #ffffff; }

/* MODAL */
.modal-overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(16, 43, 70, 0.6);
    display: none;
    place-items: center;
    z-index: 2000;
}
.modal-overlay.open { display: grid; }
.modal-box {
    background: #ffffff;
    width: 90%;
    max-width: 520px;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 10px 35px rgba(0,0,0,0.2);
}
.modal-header {
    background: var(--light);
    padding: 18px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--border-color);
}
.modal-header h3 { font-size: 16px; color: var(--dark); font-weight: 700; }
.modal-close-btn { background: none; border: none; font-size: 18px; color: var(--text-muted); cursor: pointer; }
.modal-body { padding: 22px 24px; display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.form-group-full { grid-column: span 2; }
.form-group label { display: block; font-size: 12px; font-weight: 600; color: var(--dark); margin-bottom: 6px; }
.form-group input, .form-group select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d4e8ea;
    border-radius: 8px;
    font-size: 13px;
    outline: none;
    background: #f7fcfc;
}
.form-group input:focus, .form-group select:focus {
    border-color: var(--primary);
    background: #ffffff;
}
.modal-footer {
    padding: 16px 24px;
    background: #f8fafc;
    border-top: 1px solid var(--border-color);
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
.btn-secondary {
    background: #e2e8f0;
    color: var(--dark);
    padding: 9px 18px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    border: none;
    cursor: pointer;
}

.alert-box {
    padding: 12px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    margin-bottom: 16px;
}
.alert-success { background: #e6f8f0; color: #0b6b43; border: 1px solid #a7f3d0; }
.alert-error { background: #fdeeee; color: #a12626; border: 1px solid #fecaca; }

@media (max-width: 768px) {
    .mobile-only-toggle { display: flex; }
    .sidebar { position: fixed; left: -265px; top: 0; bottom: 0; }
    .sidebar.open { left: 0; }
    .top-header { padding: 0 16px; }
    .page-container { padding: 16px; }
    .modal-body { grid-template-columns: 1fr; }
    .form-group-full { grid-column: span 1; }
}
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar" id="parentSidebar">
    
    <!-- Brand Header -->
    <div class="sidebar-header">
        <a href="../index.php" class="brand-block" title="Back to Website">
            <div class="brand-icon">
                <i class="fa-solid fa-shield-heart"></i>
            </div>
            <div class="brand-text">
                <span class="brand-title">VACCINATION</span>
                <small class="brand-sub">Management System</small>
            </div>
        </a>
        <button class="sidebar-collapse-btn" id="sidebarCollapseBtn" title="Collapse / Expand Sidebar">
            <i class="fa-solid fa-chevron-left" id="collapseIcon"></i>
        </button>
    </div>

    <!-- Navigation Menu -->
    <nav class="sidebar-nav">
        <a href="dashboard.php" data-tooltip="Dashboard">
            <i class="fa-solid fa-house"></i>
            <span>Dashboard</span>
        </a>
        <a href="children.php" data-tooltip="My Children">
            <i class="fa-solid fa-children"></i>
            <span>My Children</span>
        </a>
        <a href="appointments.php" class="active" data-tooltip="Book Hospital">
            <i class="fa-solid fa-calendar-days"></i>
            <span>Book Hospital</span>
        </a>
        <a href="schedule.php" data-tooltip="Vaccination History">
            <i class="fa-solid fa-file-medical"></i>
            <span>Vaccination History</span>
        </a>
    </nav>

    <!-- Sidebar Health Promo Widget -->
    <div class="sidebar-health-widget">
        <div class="widget-heart-icon">
            <i class="fa-solid fa-heart-pulse"></i>
        </div>
        <div class="widget-title">
            Healthy Children<br>Healthy Tomorrow
        </div>
        <div class="widget-family-illustration">
            <div class="widget-family-avatar" title="Father"><i class="fa-solid fa-user-tie"></i></div>
            <div class="widget-family-avatar" title="Child" style="background:var(--primary);color:#fff;"><i class="fa-solid fa-child"></i></div>
            <div class="widget-family-avatar" title="Mother"><i class="fa-solid fa-person-dress"></i></div>
        </div>
    </div>

</aside>

<!-- MAIN CONTENT -->
<div class="main-content">

    <!-- Top Header -->
    <header class="top-header">
        <div class="header-left">
            <button class="mobile-only-toggle" id="sidebarToggle" title="Toggle Menu">
                <i class="fa-solid fa-bars"></i>
            </button>
            <div class="header-title-block">
                <h1 style="font-size: 20px; font-weight: 700; color: var(--dark);">Book Hospital Appointment</h1>
                <p style="font-size: 12px; color: var(--text-muted);">Schedule vaccination sessions at authorized hospitals</p>
            </div>
        </div>

        <div class="header-right">
            <a href="appointments.php" class="notif-bell" title="Notifications">
                <i class="fa-regular fa-bell"></i>
                <span class="notif-badge">1</span>
            </a>

            <?php include __DIR__ . '/../components/dashboard_user_dropdown.php'; ?>
        </div>
    </header>

    <!-- Page Container -->
    <main class="page-container">

        <?php if ($message): ?>
            <div class="alert-box alert-success"><i class="fa-solid fa-circle-check"></i> <?= e($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert-box alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= e($error) ?></div>
        <?php endif; ?>

        <!-- Banner Card -->
        <section class="banner-card">
            <div class="banner-title-block">
                <h2>Book Hospital Appointment</h2>
                <div class="breadcrumb-trail">
                    <a href="dashboard.php">Dashboard</a> &gt; <span>Book Hospital</span>
                </div>
            </div>

            <button class="btn-primary-action" onclick="openBookingModal()">
                <i class="fa-solid fa-calendar-plus"></i> New Booking
            </button>
        </section>

        <!-- Appointments Table -->
        <section class="content-card">
            <div class="table-responsive">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Child Name</th>
                            <th>Vaccine / Notes</th>
                            <th>Hospital</th>
                            <th>Date & Time</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($displayAppointments as $idx => $a): ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td><strong><?= e($a['child_name'] ?? 'Child') ?></strong></td>
                                <td><?= e($a['notes'] ?: 'Routine Vaccination') ?></td>
                                <td><?= e($a['hospital_name'] ?? 'City Hospital') ?> (<?= e($a['city'] ?? 'Karachi') ?>)</td>
                                <td><?= date('d M Y', strtotime($a['appointment_date'])) ?> &bull; <?= date('h:i A', strtotime($a['appointment_time'] ?? '10:00:00')) ?></td>
                                <td>
                                    <?php if ($a['status'] === 'Approved'): ?>
                                        <span class="badge-status badge-approved">Approved</span>
                                    <?php elseif ($a['status'] === 'Completed'): ?>
                                        <span class="badge-status badge-completed">Completed</span>
                                    <?php elseif ($a['status'] === 'Rejected'): ?>
                                        <span class="badge-status badge-rejected">Rejected</span>
                                    <?php else: ?>
                                        <span class="badge-status badge-pending">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($a['status'] === 'Pending' && is_numeric($a['id'])): ?>
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Cancel this appointment request?');">
                                            <input type="hidden" name="action" value="cancel_appointment">
                                            <input type="hidden" name="appointment_id" value="<?= (int)$a['id'] ?>">
                                            <button type="submit" class="btn-cancel-app">Cancel</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="font-size:12px;color:var(--text-muted);">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </main>

</div>

<!-- BOOK APPOINTMENT MODAL -->
<div class="modal-overlay" id="bookingModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fa-regular fa-calendar-check" style="color:var(--primary);margin-right:8px;"></i> Book Hospital Appointment</h3>
            <button class="modal-close-btn" onclick="closeBookingModal()">&times;</button>
        </div>
        <form method="post">
            <input type="hidden" name="action" value="book_appointment">
            <div class="modal-body">
                
                <div class="form-group form-group-full">
                    <label>Select Child</label>
                    <select name="child_id" required>
                        <?php if (!empty($children)): ?>
                            <?php foreach ($children as $c): ?>
                                <option value="<?= (int)$c['id'] ?>"><?= e($c['child_name']) ?></option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="1">Muhammad Ali (Demo)</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group form-group-full">
                    <label>Select Hospital</label>
                    <select name="hospital_id" required>
                        <option value="">-- Choose Hospital --</option>
                        <?php if (!empty($hospitals)): ?>
                            <?php foreach ($hospitals as $h): ?>
                                <option value="<?= (int)$h['id'] ?>" <?= ($selectedHospitalId === (int)$h['id']) ? 'selected' : '' ?>>
                                    <?= e($h['hospital_name']) ?> (<?= e($h['city']) ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="" disabled>No available hospitals found</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group form-group-full">
                    <label>Vaccine Name / Purpose</label>
                    <input type="text" name="notes" value="<?= e($_GET['vaccine'] ?? '') ?>" placeholder="e.g. Polio Vaccine, BCG Booster" required>
                </div>

                <div class="form-group">
                    <label>Appointment Date</label>
                    <input type="date" name="appointment_date" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="form-group">
                    <label>Preferred Time</label>
                    <input type="time" name="appointment_time" value="10:00" required>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeBookingModal()">Cancel</button>
                <button type="submit" class="btn-primary-action"><i class="fa-solid fa-check"></i> Confirm Booking</button>
            </div>
        </form>
    </div>
</div>

<script>
const sidebarCollapseBtn = document.getElementById('sidebarCollapseBtn');
const parentSidebar = document.getElementById('parentSidebar');
const mobileToggle = document.getElementById('sidebarToggle');

// Restore collapsed state
if (localStorage.getItem('parent_sidebar_collapsed') === 'true' && parentSidebar) {
    parentSidebar.classList.add('collapsed');
}

if (sidebarCollapseBtn && parentSidebar) {
    sidebarCollapseBtn.addEventListener('click', () => {
        parentSidebar.classList.toggle('collapsed');
        const isCollapsed = parentSidebar.classList.contains('collapsed');
        localStorage.setItem('parent_sidebar_collapsed', isCollapsed);
    });
}

if (mobileToggle && parentSidebar) {
    mobileToggle.addEventListener('click', () => {
        parentSidebar.classList.toggle('open');
    });
}

function openBookingModal() {
    document.getElementById('bookingModal').classList.add('open');
}
function closeBookingModal() {
    document.getElementById('bookingModal').classList.remove('open');
}

<?php if (!empty($_GET['vaccine']) || !empty($_GET['hospital_id']) || isset($_GET['book'])): ?>
window.addEventListener('DOMContentLoaded', () => {
    openBookingModal();
});
<?php endif; ?>
</script>

</body>
</html>
