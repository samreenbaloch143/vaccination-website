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
$message = '';
$error = '';

// Handle Profile and Password Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_profile') {
        $adminName = trim($_POST['admin_name'] ?? '');
        $hospitalName = trim($_POST['hospital_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if ($hospitalId <= 0) {
            $error = 'Session expired. Please log in again.';
        } elseif (empty($adminName) || empty($hospitalName) || empty($phone) || empty($city) || empty($address)) {
            $error = 'Please fill out all required profile fields.';
        } else {
            $stmt = $conn->prepare("
                UPDATE hospitals 
                SET admin_name = ?, hospital_name = ?, phone = ?, city = ?, address = ? 
                WHERE id = ?
            ");
            if ($stmt) {
                $stmt->bind_param("sssssi", $adminName, $hospitalName, $phone, $city, $address, $hospitalId);
                if ($stmt->execute()) {
                    $_SESSION['name'] = $adminName;
                    $_SESSION['hospital_name'] = $hospitalName;
                    $message = 'Hospital profile details updated successfully!';
                } else {
                    $error = 'Failed to update profile: ' . $conn->error;
                }
                $stmt->close();
            } else {
                $error = 'Database error: ' . $conn->error;
            }
        }
    } elseif ($_POST['action'] === 'update_password') {
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        if (empty($newPass) || empty($confirmPass)) {
            $error = 'Both password fields are required.';
        } elseif (strlen($newPass) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } elseif ($newPass !== $confirmPass) {
            $error = 'New passwords do not match.';
        } else {
            $hashed = password_hash($newPass, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE hospitals SET password = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("si", $hashed, $hospitalId);
                if ($stmt->execute()) {
                    $message = 'Hospital login password changed successfully!';
                } else {
                    $error = 'Failed to update password: ' . $conn->error;
                }
                $stmt->close();
            }
        }
    }
}

// Fetch Profile
$profile = null;
if ($hospitalId > 0) {
    $stmt = $conn->prepare("SELECT admin_name, hospital_name, email, phone, city, address, created_at FROM hospitals WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $hospitalId);
        $stmt->execute();
        $profile = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

if (!$profile) {
    header("Location: ../login.php");
    exit;
}

$hospitalName = $profile['hospital_name'] ?? 'City Hospital';
$adminName = $profile['admin_name'] ?? 'Admin';

// Fetch stats for profile badge
$totalPatients = 0;
$totalRecords = 0;
if ($hospitalId > 0) {
    $pQuery = $conn->query("SELECT COUNT(DISTINCT child_id) AS total FROM appointments WHERE hospital_id = $hospitalId");
    if ($pQuery && $row = $pQuery->fetch_assoc()) {
        $totalPatients = (int)$row['total'];
    }
    $rQuery = $conn->query("SELECT COUNT(*) AS total FROM vaccination_records WHERE hospital_id = $hospitalId");
    if ($rQuery && $row = $rQuery->fetch_assoc()) {
        $totalRecords = (int)$row['total'];
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
    <title>Hospital Profile - Hospital Portal</title>
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

        /* PROFILE LAYOUT GRID */
        .profile-layout-grid {
            display: grid;
            grid-template-columns: 330px 1fr;
            gap: 22px;
            align-items: start;
        }

        /* OVERVIEW CARD */
        .profile-overview-card {
            background: var(--card-bg);
            border-radius: 14px;
            border: 1px solid var(--border-color);
            padding: 26px 22px;
            box-shadow: 0 4px 18px rgba(16, 43, 70, 0.04);
            text-align: center;
        }

        .hospital-avatar-big {
            width: 86px;
            height: 86px;
            border-radius: 18px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #ffffff;
            display: grid;
            place-items: center;
            font-size: 38px;
            margin: 0 auto 14px;
            box-shadow: 0 6px 18px rgba(8, 126, 139, 0.35);
        }

        .hospital-name-big {
            font-size: 18px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 4px;
        }

        .hospital-role-badge {
            display: inline-block;
            background: var(--mint-bg);
            color: var(--primary);
            font-size: 12px;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 20px;
            margin-bottom: 20px;
            border: 1px solid #c2ecee;
        }

        .profile-stats-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            padding: 16px 0;
            border-top: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 18px;
        }

        .profile-stat-box h4 {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary);
        }

        .profile-stat-box small {
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .profile-detail-list {
            text-align: left;
            display: flex;
            flex-direction: column;
            gap: 12px;
            font-size: 13px;
        }

        .profile-detail-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            color: var(--text-dark);
        }

        .profile-detail-item i {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            background: #f4fafb;
            color: var(--primary);
            display: grid;
            place-items: center;
            font-size: 13px;
            flex-shrink: 0;
            margin-top: 2px;
        }

        /* FORMS COLUMN */
        .forms-column {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .card-panel {
            background: var(--card-bg);
            border-radius: 14px;
            border: 1px solid var(--border-color);
            padding: 24px 26px;
            box-shadow: 0 4px 18px rgba(16, 43, 70, 0.04);
        }

        .card-header-line {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border-color);
        }

        .card-header-line h3 {
            font-size: 16px;
            font-weight: 700;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 14px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 14px;
        }

        .form-group label {
            font-size: 12px;
            font-weight: 600;
            color: var(--dark);
        }

        .form-group input, .form-group textarea {
            width: 100%;
            padding: 11px 14px;
            border: 1px solid #d4e8ea;
            border-radius: 8px;
            font-size: 13px;
            outline: none;
            background: #fbfdfd;
            transition: all 0.2s;
        }

        .form-group input:focus, .form-group textarea:focus {
            border-color: var(--primary);
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(8, 126, 139, 0.12);
        }

        .form-group input:disabled {
            background: #f1f5f9;
            color: #64748b;
            cursor: not-allowed;
        }

        .form-action-row {
            margin-top: 10px;
            display: flex;
            justify-content: flex-end;
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #ffffff;
            border: none;
            padding: 11px 24px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 14px rgba(8, 126, 139, 0.35);
            transition: all 0.2s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(8, 126, 139, 0.45);
        }

        .alert-box {
            padding: 12px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 16px;
        }
        .alert-success { background: #e6f8f0; color: #0b6b43; border: 1px solid #a7f3d0; }
        .alert-error { background: #fdeeee; color: #a12626; border: 1px solid #fecaca; }

        @media (max-width: 900px) {
            .profile-layout-grid { grid-template-columns: 1fr; }
            .form-grid-2 { grid-template-columns: 1fr; }
        }

        @media (max-width: 768px) {
            .menu-toggle-btn { display: flex; }
            .sidebar { position: fixed; left: -265px; top: 0; bottom: 0; }
            .sidebar.open { left: 0; }
            .top-header { padding: 0 16px; }
            .page-container { padding: 16px; }
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
        <a href="appointments.php" data-tooltip="Appointments">
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
                <span>Authorized Vaccination Center &bull; Profile Management</span>
            </div>
        </div>
        <div class="header-right">
            <a href="appointments.php" class="notif-bell" title="Appointments">
                <i class="fa-regular fa-bell"></i>
            </a>
            <?php include __DIR__ . '/../components/dashboard_user_dropdown.php'; ?>
        </div>
    </header>

    <div class="page-container">

        <!-- Banner Card -->
        <div class="banner-card">
            <div class="banner-title-block">
                <h2>Hospital Center Profile</h2>
                <div class="breadcrumb-trail">
                    <a href="dashboard.php">Dashboard</a> &gt; <span>Hospital Profile</span>
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

        <!-- Profile Layout Grid -->
        <div class="profile-layout-grid">

            <!-- Hospital Overview Card -->
            <div class="profile-overview-card">
                <div class="hospital-avatar-big">
                    <i class="fa-solid fa-hospital"></i>
                </div>
                <div class="hospital-name-big"><?= e($hospitalName) ?></div>
                <span class="hospital-role-badge"><i class="fa-solid fa-shield-halved"></i> Authorized Center</span>

                <div class="profile-stats-row">
                    <div class="profile-stat-box">
                        <h4><?= $totalPatients ?></h4>
                        <small>Patients</small>
                    </div>
                    <div class="profile-stat-box">
                        <h4><?= $totalRecords ?></h4>
                        <small>Vaccines Given</small>
                    </div>
                </div>

                <div class="profile-detail-list">
                    <div class="profile-detail-item">
                        <i class="fa-solid fa-user-shield"></i>
                        <div>
                            <strong>Administrator</strong>
                            <div style="font-size: 11.5px; color: var(--text-muted);"><?= e($adminName) ?></div>
                        </div>
                    </div>
                    <div class="profile-detail-item">
                        <i class="fa-solid fa-envelope"></i>
                        <div>
                            <strong>Email Address</strong>
                            <div style="font-size: 11.5px; color: var(--text-muted);"><?= e($profile['email'] ?? 'N/A') ?></div>
                        </div>
                    </div>
                    <div class="profile-detail-item">
                        <i class="fa-solid fa-phone"></i>
                        <div>
                            <strong>Phone Number</strong>
                            <div style="font-size: 11.5px; color: var(--text-muted);"><?= e($profile['phone'] ?? 'N/A') ?></div>
                        </div>
                    </div>
                    <div class="profile-detail-item">
                        <i class="fa-solid fa-location-dot"></i>
                        <div>
                            <strong>City</strong>
                            <div style="font-size: 11.5px; color: var(--text-muted);"><?= e($profile['city'] ?? 'Karachi') ?></div>
                        </div>
                    </div>
                    <div class="profile-detail-item">
                        <i class="fa-solid fa-map-pin"></i>
                        <div>
                            <strong>Address</strong>
                            <div style="font-size: 11.5px; color: var(--text-muted);"><?= e($profile['address'] ?? 'N/A') ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Forms Column -->
            <div class="forms-column">

                <!-- Center Details Card -->
                <div class="card-panel">
                    <div class="card-header-line">
                        <h3><i class="fa-solid fa-hospital-user" style="color: var(--primary);"></i> Center &amp; Administrator Details</h3>
                    </div>
                    <form method="POST" action="profile.php">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="form-grid-2">
                            <div class="form-group" style="margin-bottom:0;">
                                <label for="hospital_name">Hospital Name *</label>
                                <input type="text" id="hospital_name" name="hospital_name" value="<?= e($profile['hospital_name']) ?>" required>
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label for="admin_name">Administrator Incharge *</label>
                                <input type="text" id="admin_name" name="admin_name" value="<?= e($profile['admin_name']) ?>" required>
                            </div>
                        </div>

                        <div class="form-grid-2">
                            <div class="form-group" style="margin-bottom:0;">
                                <label for="email">Login Email</label>
                                <input type="email" id="email" value="<?= e($profile['email']) ?>" disabled title="Email cannot be changed directly">
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label for="phone">Contact Phone *</label>
                                <input type="text" id="phone" name="phone" value="<?= e($profile['phone']) ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="city">City / District *</label>
                            <input type="text" id="city" name="city" value="<?= e($profile['city']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="address">Full Physical Address *</label>
                            <textarea id="address" name="address" rows="3" required style="resize:vertical;"><?= e($profile['address']) ?></textarea>
                        </div>

                        <div class="form-action-row">
                            <button type="submit" class="btn-submit">
                                <i class="fa-solid fa-floppy-disk"></i> Save Hospital Details
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Password Change Card -->
                <div class="card-panel">
                    <div class="card-header-line">
                        <h3><i class="fa-solid fa-lock" style="color: var(--primary);"></i> Change Hospital Login Password</h3>
                    </div>
                    <form method="POST" action="profile.php">
                        <input type="hidden" name="action" value="update_password">
                        <div class="form-grid-2">
                            <div class="form-group" style="margin-bottom:0;">
                                <label for="new_password">New Password *</label>
                                <input type="password" id="new_password" name="new_password" placeholder="At least 6 characters" required>
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label for="confirm_password">Confirm New Password *</label>
                                <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-type password" required>
                            </div>
                        </div>
                        <div class="form-action-row">
                            <button type="submit" class="btn-submit">
                                <i class="fa-solid fa-key"></i> Update Password
                            </button>
                        </div>
                    </form>
                </div>

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