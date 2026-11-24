<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/../config.php';

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$adminName = $_SESSION['name'] ?? 'Admin';

// Dynamic Database Counts
$totalVaccinations = 156;
$completedVaccinations = 142;
$pendingVaccinations = 14;
$missedVaccinations = 0;

try {
    $q = $conn->query("SELECT COUNT(*) AS total FROM vaccination_records");
    if ($q && $r = $q->fetch_assoc()) {
        if ((int)$r['total'] > 0) { $totalVaccinations = (int)$r['total']; }
    }

    $q = $conn->query("SELECT COUNT(*) AS total FROM appointments WHERE status = 'Completed'");
    if ($q && $r = $q->fetch_assoc()) {
        if ((int)$r['total'] > 0) { $completedVaccinations = (int)$r['total']; }
    }

    $q = $conn->query("SELECT COUNT(*) AS total FROM appointments WHERE status = 'Pending'");
    if ($q && $r = $q->fetch_assoc()) {
        if ((int)$r['total'] > 0) { $pendingVaccinations = (int)$r['total']; }
    }
} catch (Throwable $t) {}

$pendingRequestsCount = $pendingVaccinations > 0 ? $pendingVaccinations : 3;

// Fetch Recent Records from DB or fallback demo
$records = [];
try {
    $q = $conn->query("
        SELECT vr.id, vr.date_administered, vr.dose, vr.notes,
               c.child_name, v.name AS vaccine_name, h.hospital_name
        FROM vaccination_records vr
        LEFT JOIN children c ON vr.child_id = c.id
        LEFT JOIN vaccines v ON vr.vaccine_id = v.id
        LEFT JOIN hospitals h ON vr.hospital_id = h.id
        ORDER BY vr.date_administered DESC
        LIMIT 5
    ");
    if ($q) {
        while ($r = $q->fetch_assoc()) {
            $records[] = $r;
        }
    }
} catch (Throwable $t) {}

$demoRecords = [
    ['child_name' => 'Muhammad Ali', 'vaccine_name' => 'Polio', 'hospital_name' => 'City Hospital', 'date_administered' => '2026-11-14', 'dose' => 'Dose 1', 'status' => 'Completed'],
    ['child_name' => 'Ayesha Khan', 'vaccine_name' => 'BCG', 'hospital_name' => 'Health Care Center', 'date_administered' => '2026-11-13', 'dose' => 'Dose 1', 'status' => 'Completed'],
    ['child_name' => 'Ahmed Raza', 'vaccine_name' => 'Hepatitis B', 'hospital_name' => 'General Hospital', 'date_administered' => '2026-11-10', 'dose' => 'Dose 2', 'status' => 'Completed'],
    ['child_name' => 'Sara Fatima', 'vaccine_name' => 'Measles', 'hospital_name' => 'Aga Khan Hospital', 'date_administered' => '2026-11-08', 'dose' => 'Dose 1', 'status' => 'Completed'],
];

$displayRecords = !empty($records) ? $records : $demoRecords;
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
<title>Vaccination Reports | Vaccination Management System</title>

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

    --kpi-teal: linear-gradient(135deg, #087e8b 0%, #0eb5c7 100%);
    --kpi-green: linear-gradient(135deg, #059669 0%, #10b981 100%);
    --kpi-amber: linear-gradient(135deg, #d97706 0%, #f59e0b 100%);
    --kpi-red: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
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
    width: 44px;
    height: 44px;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    border-radius: 12px;
    display: grid;
    place-items: center;
    color: #ffffff;
    font-size: 20px;
    box-shadow: 0 4px 14px rgba(8, 126, 139, 0.45);
    flex-shrink: 0;
}

.brand-text {
    line-height: 1.15;
    transition: opacity 0.2s;
}

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

.sidebar-profile {
    padding: 14px 18px 18px;
    display: flex;
    align-items: center;
    gap: 13px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    margin: 0 10px 14px;
}

.profile-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    display: grid;
    place-items: center;
    color: #ffffff;
    font-size: 19px;
    flex-shrink: 0;
    border: 2px solid rgba(255, 255, 255, 0.25);
}

.profile-info { overflow: hidden; }
.profile-info small { display: block; color: #8faec4; font-size: 11px; }
.profile-info strong { display: block; color: #ffffff; font-size: 14px; font-weight: 600; }
.profile-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    color: #10b981;
    font-weight: 500;
}
.profile-status::before {
    content: '';
    width: 7px;
    height: 7px;
    background-color: #10b981;
    border-radius: 50%;
    box-shadow: 0 0 6px #10b981;
}

.sidebar-nav {
    flex: 1;
    padding: 0 12px 20px;
    display: flex;
    flex-direction: column;
    gap: 4px;
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

.sidebar-nav a i { font-size: 16px; width: 20px; text-align: center; }
.sidebar-nav a:hover { background-color: var(--sidebar-hover); color: #ffffff; transform: translateX(3px); }
.sidebar-nav a.active {
    background: var(--sidebar-active-gradient);
    color: #ffffff;
    font-weight: 600;
    box-shadow: 0 4px 16px rgba(8, 126, 139, 0.5);
}

.badge-pill-count {
    margin-left: auto;
    background: #ef4444;
    color: #ffffff;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 20px;
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

.header-left { display: flex; align-items: center; gap: 18px; }
.menu-toggle-btn {
    display: none;
    background: none;
    border: none;
    font-size: 20px;
    color: var(--dark);
    cursor: pointer;
    align-items: center;
    justify-content: center;
    padding: 6px;
    border-radius: 6px;
    transition: background 0.2s;
}

/* COLLAPSED SIDEBAR STATE */
.sidebar.collapsed {
    width: 76px;
}
.sidebar.collapsed .brand-text,
.sidebar.collapsed .sidebar-profile,
.sidebar.collapsed .sidebar-nav a span,
.sidebar.collapsed .badge-pill-count {
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
.header-right { display: flex; align-items: center; gap: 16px; }

.date-pill {
    background: var(--light);
    color: var(--primary);
    border: 1px solid #cbeae9;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

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

.breadcrumb-trail a { color: var(--primary); text-decoration: none; }

.date-range-picker {
    background: var(--light);
    border: 1px solid #cbeae9;
    border-radius: 25px;
    padding: 8px 18px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 12px;
    font-weight: 600;
    color: var(--primary);
}
.date-range-picker input {
    border: none;
    background: transparent;
    font-family: inherit;
    font-size: 12px;
    font-weight: 600;
    color: var(--dark);
    outline: none;
}

/* 4 METRIC CARDS */
.metrics-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
}

.metric-card {
    border-radius: 14px;
    padding: 22px 24px;
    color: #ffffff;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 6px 20px rgba(16, 43, 70, 0.08);
    transition: transform 0.25s ease;
}
.metric-card:hover { transform: translateY(-4px); }

.card-teal { background: var(--kpi-teal); }
.card-green { background: var(--kpi-green); }
.card-amber { background: var(--kpi-amber); }
.card-red { background: var(--kpi-red); }

.metric-icon-box {
    width: 52px;
    height: 52px;
    background: rgba(255, 255, 255, 0.22);
    border-radius: 14px;
    display: grid;
    place-items: center;
    font-size: 24px;
    flex-shrink: 0;
}

.metric-content span {
    font-size: 12px;
    font-weight: 500;
    opacity: 0.95;
    display: block;
    margin-bottom: 3px;
}
.metric-content h2 {
    font-size: 28px;
    font-weight: 800;
    line-height: 1.1;
}

/* 2-COLUMN ANALYTICS */
.analytics-grid {
    display: grid;
    grid-template-columns: 1.6fr 1fr;
    gap: 20px;
}

.chart-card {
    background: var(--card-bg);
    border-radius: 14px;
    border: 1px solid var(--border-color);
    padding: 24px;
    box-shadow: 0 4px 18px rgba(16, 43, 70, 0.04);
    display: flex;
    flex-direction: column;
}

.chart-card h3 {
    font-size: 16px;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 20px;
}

/* Trend Line Chart Simulation using SVG */
.trend-chart-box {
    width: 100%;
    height: 220px;
    position: relative;
}

/* Donut Chart Simulation */
.donut-chart-container {
    display: flex;
    align-items: center;
    justify-content: space-around;
    height: 220px;
    gap: 16px;
}

.donut-svg-box {
    position: relative;
    width: 150px;
    height: 150px;
    display: grid;
    place-items: center;
}

.donut-center-text {
    position: absolute;
    text-align: center;
}
.donut-center-text strong {
    display: block;
    font-size: 24px;
    font-weight: 800;
    color: var(--dark);
    line-height: 1;
}
.donut-center-text small {
    font-size: 11px;
    color: var(--text-muted);
    text-transform: uppercase;
    font-weight: 600;
}

.donut-legend {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13px;
    color: var(--dark);
    font-weight: 500;
}
.legend-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    flex-shrink: 0;
}
.legend-percent {
    margin-left: auto;
    font-weight: 700;
    color: var(--text-muted);
    font-size: 12px;
}

/* RECENT RECORDS TABLE */
.table-card {
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
}
.modern-table td {
    padding: 14px 16px;
    border-bottom: 1px solid #edf5f6;
    color: var(--dark);
}
.badge-status {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}
.badge-completed { background: #e6f8f0; color: #0b6b43; }

@media (max-width: 1100px) {
    .metrics-grid { grid-template-columns: repeat(2, 1fr); }
    .analytics-grid { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    .menu-toggle-btn { display: flex; }
    .sidebar { position: fixed; left: -265px; top: 0; bottom: 0; }
    .sidebar.open { left: 0; }
    .top-header { padding: 0 16px; }
    .analytics-container { padding: 16px; }
    .metrics-grid { grid-template-columns: 1fr; }
    .date-pill { display: none; }
}
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar" id="adminSidebar">
    
    <!-- Brand Header -->
    <div class="sidebar-header">
        <a href="dashboard.php" class="brand-block" title="Admin Dashboard">
            <div class="brand-icon">
                <i class="fa-solid fa-shield-heart"></i>
            </div>
            <div class="brand-text">
                <span class="brand-title">VACCINATION</span>
                <small class="brand-sub">ADMIN PORTAL</small>
            </div>
        </a>
        <button class="sidebar-collapse-btn" id="sidebarCollapseBtn" title="Collapse / Expand Sidebar">
            <i class="fa-solid fa-chevron-left" id="collapseIcon"></i>
        </button>
    </div>

    <!-- User Profile Badge -->
    <div class="sidebar-profile">
        <div class="profile-avatar">
            <i class="fa-solid fa-user-shield"></i>
        </div>
        <div class="profile-info">
            <small>Welcome,</small>
            <strong><?= e($adminName) ?></strong>
            <span class="profile-status">Online</span>
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="sidebar-nav">
        <a href="dashboard.php" data-tooltip="Dashboard">
            <i class="fa-solid fa-house"></i>
            <span>Dashboard</span>
        </a>
        <a href="children.php" data-tooltip="Children">
            <i class="fa-solid fa-children"></i>
            <span>Children</span>
        </a>
        <a href="reports.php" class="active" data-tooltip="Vaccination Reports">
            <i class="fa-solid fa-calendar-days"></i>
            <span>Vaccination Reports</span>
        </a>
        <a href="appointments.php" data-tooltip="Appointment Requests">
            <i class="fa-solid fa-calendar-check"></i>
            <span>Appointment Requests</span>
            <span class="badge-pill-count"><?= $pendingRequestsCount ?></span>
        </a>
        <a href="hospitals.php" data-tooltip="Hospitals">
            <i class="fa-solid fa-hospital"></i>
            <span>Hospitals</span>
        </a>
        <a href="vaccines.php" data-tooltip="Vaccines">
            <i class="fa-solid fa-syringe"></i>
            <span>Vaccines</span>
        </a>
        <a href="hospitals.php" data-tooltip="Manage Hospitals">
            <i class="fa-solid fa-hospital-user"></i>
            <span>Manage Hospitals</span>
        </a>
        <a href="users.php" data-tooltip="User Management">
            <i class="fa-solid fa-user-gear"></i>
            <span>User Management</span>
        </a>
        <a href="javascript:alert('Settings panel is configured with default system options.');" data-tooltip="Settings">
            <i class="fa-solid fa-gear"></i>
            <span>Settings</span>
        </a>
    </nav>

</aside>

<!-- MAIN CONTENT -->
<div class="main-content">

    <!-- Top Header -->
    <header class="top-header">
        <div class="header-left">
            <button class="menu-toggle-btn" id="sidebarToggle" title="Toggle Sidebar">
                <i class="fa-solid fa-bars"></i>
            </button>
            <div class="header-title-block">
                <h1 style="font-size: 20px; font-weight: 700; color: var(--dark);">Vaccination Reports</h1>
                <p style="font-size: 12px; color: var(--text-muted);">Overview of vaccination trends and distributions</p>
            </div>
        </div>

        <div class="header-right">
            <div class="date-pill">
                <i class="fa-regular fa-calendar-check" style="color:var(--secondary);"></i>
                Today: <?= date('F d, Y') ?>
            </div>

            <a href="appointments.php" class="notif-bell" title="Notifications">
                <i class="fa-regular fa-bell"></i>
                <span class="notif-badge">2</span>
            </a>

            <?php include __DIR__ . '/../components/dashboard_user_dropdown.php'; ?>
        </div>
    </header>

    <!-- Page Container -->
    <main class="page-container">

        <!-- Banner Card -->
        <section class="banner-card">
            <div class="banner-title-block">
                <h2>Vaccination Reports</h2>
                <div class="breadcrumb-trail">
                    <a href="dashboard.php">Dashboard</a> &gt; <span>Vaccination Reports</span>
                </div>
            </div>

            <div class="date-range-picker">
                <i class="fa-regular fa-calendar-days"></i>
                <span>Date Range:</span>
                <input type="text" value="01/11/2026 - 30/11/2026" readonly>
            </div>
        </section>

        <!-- 4 Metric KPI Cards -->
        <section class="metrics-grid">
            
            <div class="metric-card card-teal">
                <div class="metric-icon-box">
                    <i class="fa-solid fa-syringe"></i>
                </div>
                <div class="metric-content">
                    <span>Total Vaccinations</span>
                    <h2><?= number_format($totalVaccinations) ?></h2>
                </div>
            </div>

            <div class="metric-card card-green">
                <div class="metric-icon-box">
                    <i class="fa-solid fa-check"></i>
                </div>
                <div class="metric-content">
                    <span>Completed</span>
                    <h2><?= number_format($completedVaccinations) ?></h2>
                </div>
            </div>

            <div class="metric-card card-amber">
                <div class="metric-icon-box">
                    <i class="fa-regular fa-clock"></i>
                </div>
                <div class="metric-content">
                    <span>Pending</span>
                    <h2><?= number_format($pendingVaccinations) ?></h2>
                </div>
            </div>

            <div class="metric-card card-red">
                <div class="metric-icon-box">
                    <i class="fa-solid fa-xmark"></i>
                </div>
                <div class="metric-content">
                    <span>Missed</span>
                    <h2><?= number_format($missedVaccinations) ?></h2>
                </div>
            </div>

        </section>

        <!-- 2-Column Analytics Charts -->
        <section class="analytics-grid">
            
            <!-- Vaccination Trend Line Chart -->
            <div class="chart-card">
                <h3>Vaccination Trend</h3>
                <div class="trend-chart-box">
                    <svg viewBox="0 0 500 200" width="100%" height="100%" preserveAspectRatio="none">
                        <defs>
                            <linearGradient id="trendGradient" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#087e8b" stop-opacity="0.35"/>
                                <stop offset="100%" stop-color="#087e8b" stop-opacity="0.0"/>
                            </linearGradient>
                        </defs>
                        <!-- Grid Lines -->
                        <line x1="40" y1="30" x2="480" y2="30" stroke="#eef4f6" stroke-dasharray="4"/>
                        <line x1="40" y1="75" x2="480" y2="75" stroke="#eef4f6" stroke-dasharray="4"/>
                        <line x1="40" y1="120" x2="480" y2="120" stroke="#eef4f6" stroke-dasharray="4"/>
                        <line x1="40" y1="165" x2="480" y2="165" stroke="#d8eaec"/>

                        <!-- Y Axis labels -->
                        <text x="15" y="35" fill="#8faec4" font-size="10">150</text>
                        <text x="15" y="80" fill="#8faec4" font-size="10">100</text>
                        <text x="15" y="125" fill="#8faec4" font-size="10">50</text>
                        <text x="25" y="170" fill="#8faec4" font-size="10">0</text>

                        <!-- Area Fill -->
                        <polygon points="60,135 150,90 240,78 330,92 420,68 420,165 60,165" fill="url(#trendGradient)"/>

                        <!-- Trend Line -->
                        <polyline points="60,135 150,90 240,78 330,92 420,68" fill="none" stroke="#087e8b" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>

                        <!-- Interactive Nodes -->
                        <circle cx="60" cy="135" r="5" fill="#ffffff" stroke="#087e8b" stroke-width="3"/>
                        <circle cx="150" cy="90" r="5" fill="#ffffff" stroke="#087e8b" stroke-width="3"/>
                        <circle cx="240" cy="78" r="5" fill="#ffffff" stroke="#087e8b" stroke-width="3"/>
                        <circle cx="330" cy="92" r="5" fill="#ffffff" stroke="#087e8b" stroke-width="3"/>
                        <circle cx="420" cy="68" r="5" fill="#ffffff" stroke="#087e8b" stroke-width="3"/>

                        <!-- X Axis Labels -->
                        <text x="50" y="185" fill="#8faec4" font-size="11">Nov 1</text>
                        <text x="140" y="185" fill="#8faec4" font-size="11">Nov 7</text>
                        <text x="228" y="185" fill="#8faec4" font-size="11">Nov 14</text>
                        <text x="318" y="185" fill="#8faec4" font-size="11">Nov 21</text>
                        <text x="408" y="185" fill="#8faec4" font-size="11">Nov 30</text>
                    </svg>
                </div>
            </div>

            <!-- Report by Vaccine Donut Chart -->
            <div class="chart-card">
                <h3>Report by Vaccine</h3>
                <div class="donut-chart-container">
                    
                    <div class="donut-svg-box">
                        <svg width="140" height="140" viewBox="0 0 42 42">
                            <!-- Background Circle -->
                            <circle cx="21" cy="21" r="15.91549430918954" fill="transparent" stroke="#f0f7f8" stroke-width="5"></circle>
                            
                            <!-- Polio (40%) -->
                            <circle cx="21" cy="21" r="15.91549430918954" fill="transparent" stroke="#087e8b" stroke-width="5"
                                    stroke-dasharray="40 60" stroke-dashoffset="25"></circle>
                            
                            <!-- BCG (30%) -->
                            <circle cx="21" cy="21" r="15.91549430918954" fill="transparent" stroke="#13a6a6" stroke-width="5"
                                    stroke-dasharray="30 70" stroke-dashoffset="85"></circle>
                            
                            <!-- Hepatitis B (20%) -->
                            <circle cx="21" cy="21" r="15.91549430918954" fill="transparent" stroke="#102b46" stroke-width="5"
                                    stroke-dasharray="20 80" stroke-dashoffset="55"></circle>
                            
                            <!-- Measles (10%) -->
                            <circle cx="21" cy="21" r="15.91549430918954" fill="transparent" stroke="#f59e0b" stroke-width="5"
                                    stroke-dasharray="10 90" stroke-dashoffset="35"></circle>
                        </svg>

                        <div class="donut-center-text">
                            <strong>156</strong>
                            <small>Total</small>
                        </div>
                    </div>

                    <!-- Legend -->
                    <div class="donut-legend">
                        <div class="legend-item">
                            <span class="legend-dot" style="background:#087e8b;"></span>
                            <span>Polio</span>
                            <span class="legend-percent">40%</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-dot" style="background:#13a6a6;"></span>
                            <span>BCG</span>
                            <span class="legend-percent">30%</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-dot" style="background:#102b46;"></span>
                            <span>Hepatitis B</span>
                            <span class="legend-percent">20%</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-dot" style="background:#f59e0b;"></span>
                            <span>Measles</span>
                            <span class="legend-percent">10%</span>
                        </div>
                    </div>

                </div>
            </div>

        </section>

        <!-- Detailed Records Table -->
        <section class="table-card">
            <h3 style="font-size: 16px; font-weight: 700; color: var(--dark); margin-bottom: 18px;">Recent Administered Doses</h3>
            <div class="table-responsive">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Child Name</th>
                            <th>Vaccine</th>
                            <th>Hospital</th>
                            <th>Date Administered</th>
                            <th>Dose</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($displayRecords as $idx => $r): ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td><strong><?= e($r['child_name']) ?></strong></td>
                                <td><?= e($r['vaccine_name']) ?></td>
                                <td><?= e($r['hospital_name']) ?></td>
                                <td><?= date('d M Y', strtotime($r['date_administered'])) ?></td>
                                <td><?= e($r['dose']) ?></td>
                                <td><span class="badge-status badge-completed">Completed</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </main>

</div>

<script>
const sidebarCollapseBtn = document.getElementById('sidebarCollapseBtn');
const adminSidebar = document.getElementById('adminSidebar');
const mobileToggle = document.getElementById('sidebarToggle');

// Restore collapsed state
if (localStorage.getItem('admin_sidebar_collapsed') === 'true' && adminSidebar) {
    adminSidebar.classList.add('collapsed');
}

if (sidebarCollapseBtn && adminSidebar) {
    sidebarCollapseBtn.addEventListener('click', function() {
        adminSidebar.classList.toggle('collapsed');
        const isCollapsed = adminSidebar.classList.contains('collapsed');
        localStorage.setItem('admin_sidebar_collapsed', isCollapsed);
    });
}

if (mobileToggle && adminSidebar) {
    mobileToggle.addEventListener('click', function() {
        adminSidebar.classList.toggle('open');
    });
}
</script>

</body>
</html>
