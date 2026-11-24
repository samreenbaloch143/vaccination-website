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
$msg = '';
$err = '';

// Active Tab Filter (Parents / Hospitals / Admins)
$roleTab = $_GET['tab'] ?? 'Parents';
if (!in_array($roleTab, ['Parents', 'Hospitals', 'Admins'], true)) {
    $roleTab = 'Parents';
}

// Handle Add / Delete User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_user') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = trim($_POST['role'] ?? 'Parent');
        $phone = trim($_POST['phone'] ?? '+92 300 0000000');
        $city = trim($_POST['city'] ?? 'Karachi');
        $pass = trim($_POST['password'] ?? '123456');

        if ($name === '' || $email === '') {
            $err = 'Name and Email are required.';
        } else {
            $hashed = password_hash($pass, PASSWORD_DEFAULT);
            if ($role === 'Parent') {
                $stmt = $conn->prepare("INSERT INTO parents (name, email, phone, city, password) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $name, $email, $phone, $city, $hashed);
            } elseif ($role === 'Hospital') {
                $stmt = $conn->prepare("INSERT INTO hospitals (hospital_name, admin_name, email, phone, city, address, password, status) VALUES (?, ?, ?, ?, ?, 'Central Ward', ?, 'approved')");
                $stmt->bind_param("ssssss", $name, $name, $email, $phone, $city, $hashed);
            } else {
                $stmt = $conn->prepare("INSERT INTO admins (name, email, password) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $name, $email, $hashed);
            }

            if ($stmt && $stmt->execute()) {
                $msg = "{$role} account created successfully!";
            } else {
                $err = 'Error creating user: ' . ($conn->error ?: 'Duplicate email.');
            }
            if ($stmt) $stmt->close();
        }
    } elseif ($_POST['action'] === 'delete_user') {
        $id = (int)($_POST['id'] ?? 0);
        $role = $_POST['role'] ?? 'Parent';
        if ($id > 0) {
            $table = ($role === 'Hospital') ? 'hospitals' : (($role === 'Admin') ? 'admins' : 'parents');
            $stmt = $conn->prepare("DELETE FROM {$table} WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $msg = 'User account deleted.';
            } else {
                $err = 'Failed to delete user.';
            }
            $stmt->close();
        }
    }
}

// Fetch Users according to Tab
$userList = [];
if ($roleTab === 'Parents') {
    $q = $conn->query("SELECT id, name, email, 'Parent' AS role FROM parents ORDER BY id DESC");
    if ($q) { while ($r = $q->fetch_assoc()) $userList[] = $r; }
} elseif ($roleTab === 'Hospitals') {
    $q = $conn->query("SELECT id, hospital_name AS name, email, 'Hospital' AS role FROM hospitals ORDER BY id DESC");
    if ($q) { while ($r = $q->fetch_assoc()) $userList[] = $r; }
} else {
    $q = $conn->query("SELECT id, name, email, 'Admin' AS role FROM admins ORDER BY id DESC");
    if ($q) { while ($r = $q->fetch_assoc()) $userList[] = $r; }
}

// Demo fallback matching screenshot if DB is empty
$demoUsers = [
    ['id' => 'd1', 'name' => 'Ahmed Khan', 'email' => 'ahmed@email.com', 'role' => 'Parent'],
    ['id' => 'd2', 'name' => 'Fatima Ali', 'email' => 'fatima@email.com', 'role' => 'Parent'],
    ['id' => 'd3', 'name' => 'Usman Raza', 'email' => 'usman@email.com', 'role' => 'Parent'],
];

$displayList = !empty($userList) ? $userList : $demoUsers;

// Pending count for sidebar badge
$pendingRequestsCount = 3;
$qBadge = $conn->query("SELECT COUNT(*) AS total FROM appointments WHERE status = 'Pending'");
if ($qBadge && $bRow = $qBadge->fetch_assoc()) {
    $pendingRequestsCount = (int)$bRow['total'] > 0 ? (int)$bRow['total'] : 3;
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
<title>User Management | Vaccination Management System</title>

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

.banner-right {
    display: flex;
    align-items: center;
    gap: 18px;
}

.illustration-badge {
    background: #eaf8f8;
    border: 1px solid #cce8e8;
    border-radius: 25px;
    padding: 6px 16px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13px;
    color: var(--primary);
    font-weight: 600;
}

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
}
.btn-primary-action:hover {
    background: var(--primary-hover);
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(8, 126, 139, 0.45);
}

/* ROLE TABS */
.tabs-nav {
    display: flex;
    align-items: center;
    gap: 10px;
}

.tab-btn {
    padding: 10px 20px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
    background: #ffffff;
    color: var(--text-muted);
    border: 1px solid var(--border-color);
}
.tab-btn:hover {
    color: var(--primary);
    border-color: var(--primary);
}
.tab-btn.active {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: #ffffff;
    border-color: transparent;
    box-shadow: 0 4px 12px rgba(8, 126, 139, 0.35);
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
.badge-active { background: #e6f8f0; color: #0b6b43; }

.actions-cell {
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-icon-action {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    display: grid;
    place-items: center;
    font-size: 13px;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
}
.btn-icon-edit { background: #eff6ff; color: #2563eb; }
.btn-icon-edit:hover { background: #2563eb; color: #ffffff; }

.btn-icon-delete { background: #fef2f2; color: #dc2626; }
.btn-icon-delete:hover { background: #dc2626; color: #ffffff; }

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
    max-width: 480px;
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
.modal-body { padding: 22px 24px; display: flex; flex-direction: column; gap: 14px; }
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
    .menu-toggle-btn { display: flex; }
    .sidebar {
        position: fixed;
        left: -265px;
        top: 0;
        bottom: 0;
    }
    .sidebar.open {
        left: 0;
    }
    .top-header { padding: 0 16px; }
    .management-container { padding: 16px; }
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
        <a href="reports.php" data-tooltip="Vaccination Reports">
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
        <a href="users.php" class="active" data-tooltip="User Management">
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
                <h1 style="font-size: 20px; font-weight: 700; color: var(--dark);">User Management</h1>
                <p style="font-size: 12px; color: var(--text-muted);">Manage parents, hospital representatives and admin accounts</p>
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

        <?php if ($msg): ?>
            <div class="alert-box alert-success"><i class="fa-solid fa-circle-check"></i> <?= e($msg) ?></div>
        <?php endif; ?>
        <?php if ($err): ?>
            <div class="alert-box alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= e($err) ?></div>
        <?php endif; ?>

        <!-- Banner Card -->
        <section class="banner-card">
            <div class="banner-title-block">
                <h2>User Management</h2>
                <div class="breadcrumb-trail">
                    <a href="dashboard.php">Dashboard</a> &gt; <span>Users</span>
                </div>
            </div>

            <div class="banner-right">
                <div class="illustration-badge">
                    <i class="fa-solid fa-users-gear"></i>
                    <span>Access Control</span>
                </div>

                <button class="btn-primary-action" onclick="openAddUserModal()">
                    <i class="fa-solid fa-plus"></i> Add User
                </button>
            </div>
        </section>

        <!-- Role Filter Tabs -->
        <div class="tabs-nav">
            <a href="users.php?tab=Parents" class="tab-btn <?= $roleTab === 'Parents' ? 'active' : '' ?>">
                <i class="fa-solid fa-users"></i> Parents
            </a>
            <a href="users.php?tab=Hospitals" class="tab-btn <?= $roleTab === 'Hospitals' ? 'active' : '' ?>">
                <i class="fa-solid fa-hospital"></i> Hospitals
            </a>
            <a href="users.php?tab=Admins" class="tab-btn <?= $roleTab === 'Admins' ? 'active' : '' ?>">
                <i class="fa-solid fa-user-shield"></i> Admins
            </a>
        </div>

        <!-- Content Card with Table -->
        <section class="content-card">
            
            <div class="table-responsive">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($displayList as $idx => $u): ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td><strong><?= e($u['name']) ?></strong></td>
                                <td><?= e($u['email']) ?></td>
                                <td><?= e($u['role']) ?></td>
                                <td><span class="badge-status badge-active">Active</span></td>
                                <td>
                                    <div class="actions-cell">
                                        <button class="btn-icon-action btn-icon-edit" title="Edit User" onclick="alert('Editing user: <?= e($u['name']) ?>')">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <?php if (is_numeric($u['id'])): ?>
                                            <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                                <input type="hidden" name="role" value="<?= e($u['role']) ?>">
                                                <button type="submit" class="btn-icon-action btn-icon-delete" title="Delete">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn-icon-action btn-icon-delete" title="Delete" onclick="alert('Sample record cannot be deleted.')">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </section>

    </main>

</div>

<!-- ADD USER MODAL -->
<div class="modal-overlay" id="addUserModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fa-solid fa-user-plus" style="color:var(--primary);margin-right:8px;"></i> Add New User</h3>
            <button class="modal-close-btn" onclick="closeAddUserModal()">&times;</button>
        </div>
        <form method="post">
            <input type="hidden" name="action" value="add_user">
            <div class="modal-body">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" placeholder="User Name" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="user@email.com" required>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role">
                        <option value="Parent" <?= $roleTab === 'Parents' ? 'selected' : '' ?>>Parent</option>
                        <option value="Hospital" <?= $roleTab === 'Hospitals' ? 'selected' : '' ?>>Hospital</option>
                        <option value="Admin" <?= $roleTab === 'Admins' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Set password" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeAddUserModal()">Cancel</button>
                <button type="submit" class="btn-primary-action"><i class="fa-solid fa-check"></i> Save User</button>
            </div>
        </form>
    </div>
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

function openAddUserModal() {
    document.getElementById('addUserModal').classList.add('open');
}
function closeAddUserModal() {
    document.getElementById('addUserModal').classList.remove('open');
}
</script>

</body>
</html>
