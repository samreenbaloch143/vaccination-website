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

// Search filter
$search = trim($_GET['search'] ?? '');

$patients = [];
if ($hospitalId > 0) {
    $query = "
        SELECT c.id, c.child_name, c.dob, c.gender, p.name AS parent_name, p.email AS parent_email, p.phone AS parent_phone,
               COUNT(a.id) AS appointment_count,
               (SELECT COUNT(*) FROM vaccination_records vr WHERE vr.child_id = c.id AND vr.hospital_id = ?) AS record_count
        FROM appointments a
        INNER JOIN children c ON c.id = a.child_id
        INNER JOIN parents p ON p.id = a.parent_id
        WHERE a.hospital_id = ?
    ";

    if (!empty($search)) {
        $query .= " AND (c.child_name LIKE ? OR p.name LIKE ? OR p.email LIKE ? OR p.phone LIKE ?)";
    }

    $query .= " GROUP BY c.id, c.child_name, c.dob, c.gender, p.name, p.email, p.phone ORDER BY c.child_name ASC";

    $stmt = $conn->prepare($query);
    if ($stmt) {
        if (!empty($search)) {
            $like = "%$search%";
            $stmt->bind_param("iissss", $hospitalId, $hospitalId, $like, $like, $like, $like);
        } else {
            $stmt->bind_param("ii", $hospitalId, $hospitalId);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $patients[] = $row;
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
    <title>Patients Directory - Hospital Portal</title>
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
            flex-wrap: wrap;
            gap: 16px;
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

        /* SEARCH BAR */
        .search-form {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f8fafc;
            border: 1px solid #d4e8ea;
            border-radius: 10px;
            padding: 6px 12px;
            width: 340px;
            max-width: 100%;
        }

        .search-form i {
            color: var(--primary);
            font-size: 14px;
        }

        .search-form input {
            border: none;
            background: transparent;
            outline: none;
            font-size: 13px;
            width: 100%;
            color: var(--dark);
        }

        .search-btn {
            background: var(--primary);
            color: #ffffff;
            border: none;
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .search-btn:hover { background: var(--primary-hover); }

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

        .patient-avatar-badge {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--mint-bg);
            color: var(--primary);
            display: grid;
            place-items: center;
            font-size: 14px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .patient-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11.5px;
            font-weight: 600;
        }
        .badge-count {
            background: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }
        .badge-doses {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }

        .btn-action-small {
            background: var(--mint-bg);
            color: var(--primary);
            border: 1px solid #c2ecee;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .btn-action-small:hover {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
        }

        @media (max-width: 768px) {
            .menu-toggle-btn { display: flex; }
            .sidebar { position: fixed; left: -265px; top: 0; bottom: 0; }
            .sidebar.open { left: 0; }
            .top-header { padding: 0 16px; }
            .page-container { padding: 16px; }
            .search-form { width: 100%; }
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
        <a href="patients.php" class="active" data-tooltip="Patients">
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
                <span>Authorized Vaccination Center &bull; Patients Directory</span>
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
                <h2>Registered Patient Directory</h2>
                <div class="breadcrumb-trail">
                    <a href="dashboard.php">Dashboard</a> &gt; <span>Patients</span>
                </div>
            </div>

            <!-- Search Form -->
            <form method="GET" action="patients.php" class="search-form">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="search" placeholder="Search child, parent, or email..." value="<?= e($search) ?>">
                <button type="submit" class="search-btn">Search</button>
                <?php if (!empty($search)): ?>
                    <a href="patients.php" style="color: var(--text-muted); font-size: 13px;" title="Clear Search"><i class="fa-solid fa-xmark"></i></a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Patients Table Card -->
        <div class="panel-card">
            <div class="table-responsive">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Child Patient</th>
                            <th>Date of Birth</th>
                            <th>Gender</th>
                            <th>Parent Details</th>
                            <th>Appointments</th>
                            <th>Doses Recorded</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($patients)): ?>
                            <?php foreach ($patients as $idx => $patient): ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td>
                                        <div class="patient-cell">
                                            <div class="patient-avatar-badge">
                                                <i class="fa-solid fa-child"></i>
                                            </div>
                                            <div>
                                                <strong><?= e($patient['child_name']) ?></strong>
                                                <div style="font-size: 11px; color: var(--text-muted);">ID #<?= (int)$patient['id'] ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div><?= date('d M Y', strtotime($patient['dob'])) ?></div>
                                        <?php
                                            $birthDate = new DateTime($patient['dob']);
                                            $today = new DateTime();
                                            $age = $today->diff($birthDate);
                                            $ageStr = $age->y > 0 ? "{$age->y} yrs" : "{$age->m} mos";
                                        ?>
                                        <div style="font-size: 11px; color: var(--text-muted);"><?= $ageStr ?> old</div>
                                    </td>
                                    <td>
                                        <span style="font-weight: 500;"><?= e($patient['gender']) ?></span>
                                    </td>
                                    <td>
                                        <strong><?= e($patient['parent_name']) ?></strong>
                                        <div style="font-size: 11px; color: var(--text-muted);"><?= e($patient['parent_email']) ?></div>
                                        <?php if (!empty($patient['parent_phone'])): ?>
                                            <div style="font-size: 11px; color: var(--text-muted);"><i class="fa-solid fa-phone" style="font-size: 9px;"></i> <?= e($patient['parent_phone']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge-status badge-count">
                                            <i class="fa-regular fa-calendar-check"></i> <?= (int)$patient['appointment_count'] ?> visits
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge-status badge-doses">
                                            <i class="fa-solid fa-syringe"></i> <?= (int)$patient['record_count'] ?> doses
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        <a href="records.php?child_id=<?= (int)$patient['id'] ?>" class="btn-action-small">
                                            <i class="fa-solid fa-plus"></i> Add Record
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 36px; color: var(--text-muted);">
                                    <i class="fa-solid fa-user-xmark" style="font-size: 32px; color: #cbd5e1; margin-bottom: 10px; display: block;"></i>
                                    No patients found matching your search.
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