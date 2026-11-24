<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/../config.php';

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'hospital' || empty($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$hospitalId = (int)($_SESSION['hospital_id'] ?? $_SESSION['user_id'] ?? 0);

// Fetch hospital info
$hospital = null;
if ($hospitalId > 0) {
    $stmt = $conn->prepare("SELECT id, admin_name, hospital_name, email, phone, city, address FROM hospitals WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $hospitalId);
        $stmt->execute();
        $hospital = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

$hospitalName = $hospital['hospital_name'] ?? $_SESSION['hospital_name'] ?? 'City Hospital';
$adminName = $hospital['admin_name'] ?? $_SESSION['name'] ?? 'Admin';

$message = '';
$error = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $appointmentId = (int)($_POST['appointment_id'] ?? 0);
    $newStatus = trim($_POST['status'] ?? '');
    $allowed = ['Pending', 'Approved', 'Completed', 'Rejected'];

    if ($appointmentId <= 0 || !in_array($newStatus, $allowed, true)) {
        $error = 'Invalid appointment request or status value.';
    } else {
        $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ? AND hospital_id = ?");
        if ($stmt) {
            $stmt->bind_param("sii", $newStatus, $appointmentId, $hospitalId);
            if ($stmt->execute()) {
                $message = "Appointment #$appointmentId status has been updated to $newStatus.";
            } else {
                $error = "Failed to update appointment: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Filter Tab
$filter = trim($_GET['filter'] ?? 'all');
$validFilters = ['all', 'pending', 'approved', 'completed', 'rejected'];
if (!in_array(strtolower($filter), $validFilters, true)) {
    $filter = 'all';
}

// Counts for filter pills
$counts = ['all' => 0, 'pending' => 0, 'approved' => 0, 'completed' => 0, 'rejected' => 0];
if ($hospitalId > 0) {
    $cRes = $conn->query("
        SELECT status, COUNT(*) AS cnt 
        FROM appointments 
        WHERE hospital_id = $hospitalId 
        GROUP BY status
    ");
    if ($cRes) {
        while ($row = $cRes->fetch_assoc()) {
            $st = strtolower($row['status']);
            if (isset($counts[$st])) {
                $counts[$st] = (int)$row['cnt'];
            }
            $counts['all'] += (int)$row['cnt'];
        }
    }
}

// Fetch Appointments
$appointments = [];
if ($hospitalId > 0) {
    $sql = "
        SELECT a.id, a.appointment_date, a.appointment_time, a.status, a.notes,
               c.id AS child_id, c.child_name, c.dob, c.gender,
               p.name AS parent_name, p.email AS parent_email, p.phone AS parent_phone
        FROM appointments a
        INNER JOIN children c ON c.id = a.child_id
        INNER JOIN parents p ON p.id = a.parent_id
        WHERE a.hospital_id = ?
    ";

    if ($filter !== 'all') {
        $capStatus = ucfirst(strtolower($filter));
        $sql .= " AND a.status = '$capStatus'";
    }

    $sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $hospitalId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $appointments[] = $row;
        }
        $stmt->close();
    }
}
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
    <title>Appointments Management - Hospital Portal</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary: #087e8b;
            --primary-hover: #066570;
            --secondary: #13a6a6;
            --dark: #102b46;
            --dark-surface: #0a1e32;
            --light: #f4fafb;
            --mint-bg: #eaf8f8;
            --body-bg: #f0f7f8;
            --card-bg: #ffffff;
            --border-color: #e1edf0;
            --text-dark: #102b46;
            --text-muted: #64748b;
            --sidebar-width: 250px;
            --sidebar-active-gradient: linear-gradient(135deg, #087e8b 0%, #13a6a6 100%);
            --sidebar-hover: rgba(255, 255, 255, 0.08);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: var(--body-bg);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
        }

        /* SIDEBAR */
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--dark);
            color: #ffffff;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s ease;
            z-index: 1000;
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
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .brand-block {
            display: flex;
            align-items: center;
            gap: 12px;
            overflow: hidden;
            text-decoration: none;
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 10px;
            display: grid;
            place-items: center;
            font-size: 20px;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(8, 126, 139, 0.4);
            flex-shrink: 0;
        }

        .brand-text {
            display: flex;
            flex-direction: column;
            transition: opacity 0.2s;
        }

        .brand-title {
            font-family: 'Playfair Display', serif;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.5px;
            color: #ffffff;
            line-height: 1.1;
        }

        .brand-sub {
            font-size: 10px;
            color: #94a3b8;
            letter-spacing: 0.5px;
            text-transform: uppercase;
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

        .sidebar-user {
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(0, 0, 0, 0.1);
        }

        .sidebar-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #087e8b, #13a6a6);
            display: grid;
            place-items: center;
            color: #ffffff;
            font-size: 17px;
            font-weight: 600;
        }

        .sidebar-user-info {
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .sidebar-user-name {
            font-size: 13px;
            font-weight: 600;
            color: #ffffff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar-user-role {
            font-size: 11px;
            color: #2dd4bf;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .online-dot {
            width: 6px;
            height: 6px;
            background: #2dd4bf;
            border-radius: 50%;
            display: inline-block;
        }

        .sidebar-nav {
            padding: 18px 14px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            flex: 1;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 16px;
            color: #cbd5e1;
            text-decoration: none;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .sidebar-nav a i {
            font-size: 16px;
            width: 20px;
            text-align: center;
        }

        .sidebar-nav a:hover {
            background-color: var(--sidebar-hover);
            color: #ffffff;
            transform: translateX(3px);
        }

        .sidebar-nav a.active {
            background: var(--sidebar-active-gradient);
            color: #ffffff;
            font-weight: 600;
            box-shadow: 0 4px 16px rgba(8, 126, 139, 0.5);
        }

        .sidebar-health-widget {
            margin: 14px 12px 20px;
            background: linear-gradient(135deg, #0e2b44 0%, #153e61 100%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 14px;
            padding: 16px 14px;
            text-align: center;
        }

        .widget-heart-icon {
            width: 42px;
            height: 42px;
            background: rgba(8, 126, 139, 0.25);
            border-radius: 50%;
            display: grid;
            place-items: center;
            color: #13a6a6;
            font-size: 18px;
            margin: 0 auto 8px;
        }

        .widget-title {
            font-size: 12.5px;
            font-weight: 700;
            color: #ffffff;
            line-height: 1.3;
            margin-bottom: 6px;
        }

        .widget-subtitle {
            font-size: 11px;
            color: #94a3b8;
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

        .header-left {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .menu-toggle-btn {
            display: none;
            background: none;
            border: none;
            font-size: 20px;
            color: var(--dark);
            cursor: pointer;
        }

        /* COLLAPSED SIDEBAR STATE */
        .sidebar.collapsed {
            width: 76px;
        }
        .sidebar.collapsed .brand-text,
        .sidebar.collapsed .sidebar-user-info {
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
        .sidebar.collapsed .sidebar-user {
            padding: 12px 0;
            justify-content: center;
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
        .sidebar.collapsed .widget-subtitle {
            display: none;
        }
        .sidebar.collapsed .widget-heart-icon {
            margin: 0 auto;
            width: 36px;
            height: 36px;
            font-size: 16px;
        }

        .header-hospital-title h2 {
            font-size: 16px;
            font-weight: 700;
            color: var(--dark);
            line-height: 1.2;
        }
        .header-hospital-title span {
            font-size: 11.5px;
            color: var(--text-muted);
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .notif-bell {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f4fafb;
            border: 1px solid var(--border-color);
            display: grid;
            place-items: center;
            color: var(--dark);
            position: relative;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }

        .notif-bell:hover {
            background: #eaf8f8;
            color: var(--primary);
        }

        .user-chip {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 14px 6px 8px;
            background: #f4fafb;
            border: 1px solid var(--border-color);
            border-radius: 30px;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }

        .user-chip .user-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #ffffff;
            display: grid;
            place-items: center;
            font-size: 14px;
            font-weight: 600;
        }

        .user-chip .user-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--dark);
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

        .banner-title-block h2 {
            font-size: 22px;
            font-weight: 700;
            color: var(--dark);
            line-height: 1.2;
        }

        .breadcrumb-trail {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 4px;
        }
        .breadcrumb-trail a {
            color: var(--primary);
            text-decoration: none;
        }

        /* FILTER TABS */
        .tabs-nav-bar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .tab-filter-btn {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            padding: 10px 18px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            box-shadow: 0 2px 8px rgba(16, 43, 70, 0.02);
        }

        .tab-filter-btn:hover {
            color: var(--primary);
            border-color: var(--primary);
            transform: translateY(-1px);
        }

        .tab-filter-btn.active {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
            box-shadow: 0 4px 14px rgba(8, 126, 139, 0.35);
        }

        .tab-counter-pill {
            background: rgba(0, 0, 0, 0.06);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
        }

        .tab-filter-btn.active .tab-counter-pill {
            background: rgba(255, 255, 255, 0.25);
            color: #ffffff;
        }

        /* CONTENT PANEL */
        .panel-card {
            background: var(--card-bg);
            border-radius: 14px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 18px rgba(16, 43, 70, 0.04);
            overflow: hidden;
        }

        .table-responsive {
            overflow-x: auto;
            width: 100%;
        }

        .modern-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 13px;
        }

        .modern-table th {
            background: #f8fafb;
            color: #475569;
            font-weight: 600;
            text-align: left;
            padding: 14px 16px;
            border-bottom: 1px solid var(--border-color);
            white-space: nowrap;
        }

        .modern-table td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border-color);
            color: var(--dark);
            vertical-align: middle;
        }

        .modern-table tbody tr:hover {
            background: #fbfdfd;
        }

        /* STATUS BADGES */
        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11.5px;
            font-weight: 600;
        }

        .badge-pending   { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .badge-approved  { background: #e0f2fe; color: #075985; border: 1px solid #bae6fd; }
        .badge-completed { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .badge-rejected  { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        /* ACTION CONTROLS */
        .status-select-form {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .status-dropdown {
            padding: 6px 10px;
            border: 1px solid #cbdada;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            outline: none;
            background: #fbfdfd;
            cursor: pointer;
        }
        .status-dropdown:focus { border-color: var(--primary); }

        .btn-status-save {
            background: var(--primary);
            color: #ffffff;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-status-save:hover { background: var(--primary-hover); }

        .alert-box {
            padding: 12px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
        }
        .alert-success { background: #e6f8f0; color: #0b6b43; border: 1px solid #a7f3d0; }
        .alert-error { background: #fdeeee; color: #a12626; border: 1px solid #fecaca; }

        @media (max-width: 768px) {
            .menu-toggle-btn { display: flex; }
            .sidebar { position: fixed; left: -265px; top: 0; bottom: 0; }
            .sidebar.open { left: 0; }
            .top-header { padding: 0 16px; }
            .page-container { padding: 16px; }
            .tabs-nav-bar { flex-direction: column; }
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar" id="hospitalSidebar">
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

    <div class="sidebar-user">
        <div class="sidebar-avatar">
            <i class="fa-solid fa-hospital"></i>
        </div>
        <div class="sidebar-user-info">
            <div class="sidebar-user-name"><?= e($hospitalName) ?></div>
            <div class="sidebar-user-role">
                <span class="online-dot"></span> <?= e($adminName) ?> (Admin)
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <a href="dashboard.php" data-tooltip="Dashboard">
            <i class="fa-solid fa-chart-line"></i>
            <span>Dashboard</span>
        </a>
        <a href="patients.php" data-tooltip="Patients">
            <i class="fa-solid fa-children"></i>
            <span>Patients</span>
        </a>
        <a href="appointments.php" class="active" data-tooltip="Appointments">
            <i class="fa-solid fa-calendar-check"></i>
            <span>Appointments</span>
        </a>
        <a href="records.php" data-tooltip="Vaccination Records">
            <i class="fa-solid fa-syringe"></i>
            <span>Vaccination Records</span>
        </a>
    </nav>

    <div class="sidebar-health-widget">
        <div class="widget-heart-icon">
            <i class="fa-solid fa-stethoscope"></i>
        </div>
        <div class="widget-title">Hospital Care Portal</div>
        <div class="widget-subtitle">Child Immunization Center</div>
    </div>
</aside>

<!-- MAIN CONTENT -->
<main class="main-content">

    <header class="top-header">
        <div class="header-left">
            <button class="menu-toggle-btn" id="menuToggle" aria-label="Toggle Menu">
                <i class="fa-solid fa-bars"></i>
            </button>
            <div class="header-hospital-title">
                <h2><?= e($hospitalName) ?></h2>
                <span>Authorized Vaccination Center &bull; Appointment Desk</span>
            </div>
        </div>
        <div class="header-right">
            <a href="appointments.php" class="notif-bell" title="Appointments">
                <i class="fa-regular fa-bell"></i>
                <?php if ($counts['pending'] > 0): ?>
                    <span class="notif-badge"><?= $counts['pending'] ?></span>
                <?php endif; ?>
            </a>
            <?php include __DIR__ . '/../components/dashboard_user_dropdown.php'; ?>
        </div>
    </header>

    <div class="page-container">

        <!-- Banner Card -->
        <div class="banner-card">
            <div class="banner-title-block">
                <h2>Appointment Requests &amp; Schedule</h2>
                <div class="breadcrumb-trail">
                    <a href="dashboard.php">Dashboard</a> &gt; <span>Appointments</span>
                </div>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert-box alert-success">
                <i class="fa-solid fa-circle-check"></i> <?= e($message) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert-box alert-error">
                <i class="fa-solid fa-circle-exclamation"></i> <?= e($error) ?>
            </div>
        <?php endif; ?>

        <!-- Filter Tabs -->
        <div class="tabs-nav-bar">
            <a href="appointments.php?filter=all" class="tab-filter-btn <?= $filter === 'all' ? 'active' : '' ?>">
                <i class="fa-solid fa-list-ul"></i> All Appointments <span class="tab-counter-pill"><?= $counts['all'] ?></span>
            </a>
            <a href="appointments.php?filter=pending" class="tab-filter-btn <?= $filter === 'pending' ? 'active' : '' ?>">
                <i class="fa-regular fa-clock"></i> Pending <span class="tab-counter-pill"><?= $counts['pending'] ?></span>
            </a>
            <a href="appointments.php?filter=approved" class="tab-filter-btn <?= $filter === 'approved' ? 'active' : '' ?>">
                <i class="fa-solid fa-check"></i> Approved <span class="tab-counter-pill"><?= $counts['approved'] ?></span>
            </a>
            <a href="appointments.php?filter=completed" class="tab-filter-btn <?= $filter === 'completed' ? 'active' : '' ?>">
                <i class="fa-solid fa-syringe"></i> Completed <span class="tab-counter-pill"><?= $counts['completed'] ?></span>
            </a>
            <a href="appointments.php?filter=rejected" class="tab-filter-btn <?= $filter === 'rejected' ? 'active' : '' ?>">
                <i class="fa-solid fa-xmark"></i> Rejected <span class="tab-counter-pill"><?= $counts['rejected'] ?></span>
            </a>
        </div>

        <!-- Appointments Table Card -->
        <div class="panel-card">
            <div class="table-responsive">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Child Patient</th>
                            <th>Parent Details</th>
                            <th>Appointment Schedule</th>
                            <th>Vaccine / Notes</th>
                            <th>Current Status</th>
                            <th style="text-align: right;">Update Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($appointments)): ?>
                            <?php foreach ($appointments as $idx => $appt): ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td>
                                        <strong><?= e($appt['child_name']) ?></strong>
                                        <div style="font-size: 11px; color: var(--text-muted);"><?= e($appt['gender']) ?>, DOB: <?= date('d M Y', strtotime($appt['dob'])) ?></div>
                                    </td>
                                    <td>
                                        <strong><?= e($appt['parent_name']) ?></strong>
                                        <div style="font-size: 11px; color: var(--text-muted);"><?= e($appt['parent_email']) ?></div>
                                        <?php if (!empty($appt['parent_phone'])): ?>
                                            <div style="font-size: 11px; color: var(--text-muted);"><i class="fa-solid fa-phone" style="font-size: 9px;"></i> <?= e($appt['parent_phone']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div><strong><?= date('d M Y', strtotime($appt['appointment_date'])) ?></strong></div>
                                        <div style="font-size: 11px; color: var(--text-muted);"><?= date('h:i A', strtotime($appt['appointment_time'])) ?></div>
                                    </td>
                                    <td>
                                        <span style="font-weight: 500; color: var(--dark);"><?= e($appt['notes'] ?: 'Vaccination Session') ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $sClass = match($appt['status']) {
                                            'Approved' => 'badge-approved',
                                            'Completed' => 'badge-completed',
                                            'Rejected' => 'badge-rejected',
                                            default => 'badge-pending'
                                        };
                                        ?>
                                        <span class="badge-status <?= $sClass ?>">
                                            <?= e($appt['status']) ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        <form method="POST" action="appointments.php?filter=<?= e($filter) ?>" class="status-select-form">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="appointment_id" value="<?= (int)$appt['id'] ?>">
                                            <select name="status" class="status-dropdown">
                                                <option value="Pending" <?= $appt['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="Approved" <?= $appt['status'] === 'Approved' ? 'selected' : '' ?>>Approved</option>
                                                <option value="Completed" <?= $appt['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                                <option value="Rejected" <?= $appt['status'] === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                                            </select>
                                            <button type="submit" class="btn-status-save">Save</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 36px; color: var(--text-muted);">
                                    <i class="fa-regular fa-calendar-xmark" style="font-size: 32px; color: #cbd5e1; margin-bottom: 10px; display: block;"></i>
                                    No appointments found for the "<?= e(ucfirst($filter)) ?>" filter.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</main>

<script>
    const sidebarCollapseBtn = document.getElementById('sidebarCollapseBtn');
    const hospitalSidebar = document.getElementById('hospitalSidebar');
    const mobileToggle = document.getElementById('menuToggle');

    // Restore collapsed state
    if (localStorage.getItem('hospital_sidebar_collapsed') === 'true' && hospitalSidebar) {
        hospitalSidebar.classList.add('collapsed');
    }

    if (sidebarCollapseBtn && hospitalSidebar) {
        sidebarCollapseBtn.addEventListener('click', () => {
            hospitalSidebar.classList.toggle('collapsed');
            const isCollapsed = hospitalSidebar.classList.contains('collapsed');
            localStorage.setItem('hospital_sidebar_collapsed', isCollapsed);
        });
    }

    if (mobileToggle && hospitalSidebar) {
        mobileToggle.addEventListener('click', () => {
            hospitalSidebar.classList.toggle('open');
        });
    }
</script>
</body>
</html>