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

// Fetch Children for Dropdown
$children = [];
if ($parentId > 0) {
    $stmt = $conn->prepare("SELECT id, child_name, dob, gender FROM children WHERE parent_id = ? ORDER BY child_name ASC");
    $stmt->bind_param("i", $parentId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $children[] = $row;
    }
    $stmt->close();
}

$childId = (int)($_GET['child_id'] ?? 0);
$selectedChild = null;

if (!empty($children)) {
    if ($childId > 0) {
        foreach ($children as $c) {
            if ((int)$c['id'] === $childId) {
                $selectedChild = $c;
                break;
            }
        }
    }
    if (!$selectedChild) {
        $selectedChild = $children[0];
    }
} else {
    $selectedChild = [
        'id' => 1,
        'child_name' => 'Muhammad Ali',
        'dob' => '2026-01-10',
        'gender' => 'Male'
    ];
}

// Fetch Administered Vaccination Records from DB
$records = [];
if ($parentId > 0 && isset($selectedChild['id']) && is_numeric($selectedChild['id'])) {
    $rRes = $conn->query("
        SELECT vr.*, v.name AS vaccine_name, h.hospital_name
        FROM vaccination_records vr
        LEFT JOIN vaccines v ON vr.vaccine_id = v.id
        LEFT JOIN hospitals h ON vr.hospital_id = h.id
        WHERE vr.child_id = {$selectedChild['id']}
        ORDER BY vr.date_administered DESC
    ");
    if ($rRes) {
        while ($row = $rRes->fetch_assoc()) {
            $records[] = $row;
        }
    }
}

// Fallback demo records
$demoRecords = [
    ['vaccine_name' => 'BCG', 'dose' => 'Dose 1', 'hospital_name' => 'City Hospital', 'date_administered' => '2026-03-15', 'notes' => 'Administered safely'],
    ['vaccine_name' => 'Polio (OPV-0)', 'dose' => 'Dose 1', 'hospital_name' => 'City Hospital', 'date_administered' => '2026-09-15', 'notes' => 'Oral drops given'],
];
$displayRecords = !empty($records) ? $records : $demoRecords;

// Standard EPI Recommended Schedule
$epiSchedule = [
    ['age' => 'At Birth', 'offset' => '+0 days', 'vaccines' => 'BCG, Hepatitis B, OPV-0', 'status' => 'Completed'],
    ['age' => '6 Weeks', 'offset' => '+6 weeks', 'vaccines' => 'OPV-1, Pentavalent-1, PCV-1, Rotavirus-1', 'status' => 'Completed'],
    ['age' => '10 Weeks', 'offset' => '+10 weeks', 'vaccines' => 'OPV-2, Pentavalent-2, PCV-2, Rotavirus-2', 'status' => 'Upcoming'],
    ['age' => '14 Weeks', 'offset' => '+14 weeks', 'vaccines' => 'OPV-3, Pentavalent-3, PCV-3, IPV-1', 'status' => 'Due'],
    ['age' => '9 Months', 'offset' => '+9 months', 'vaccines' => 'Measles-Rubella-1, IPV-2, Typhoid (TCV)', 'status' => 'Due'],
    ['age' => '15 Months', 'offset' => '+15 months', 'vaccines' => 'Measles-Rubella-2, DPT Booster', 'status' => 'Due'],
];
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
<title>Vaccination History & Schedule | Vaccination Management System</title>

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

.child-select-wrap {
    display: flex;
    align-items: center;
    gap: 10px;
}
.child-select-wrap select {
    padding: 9px 16px;
    border-radius: 8px;
    border: 1px solid #d4e8ea;
    font-size: 13px;
    font-weight: 600;
    color: var(--dark);
    background: var(--light);
    outline: none;
    cursor: pointer;
}

/* SECTION TABS */
.schedule-tabs-nav {
    display: flex;
    align-items: center;
    gap: 10px;
}
.s-tab-btn {
    padding: 10px 20px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    background: #ffffff;
    color: var(--text-muted);
    border: 1px solid var(--border-color);
    transition: all 0.2s;
}
.s-tab-btn.active {
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
.badge-completed { background: #e6f8f0; color: #0b6b43; }
.badge-upcoming { background: #fef3c7; color: #b45309; }
.badge-due { background: #e0f2fe; color: #0369a1; }

.btn-book-shortcut {
    background: var(--light);
    color: var(--primary);
    border: 1px solid #cce8e8;
    padding: 5px 12px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}
.btn-book-shortcut:hover { background: var(--primary); color: #ffffff; }

@media (max-width: 768px) {
    .mobile-only-toggle { display: flex; }
    .sidebar { position: fixed; left: -265px; top: 0; bottom: 0; }
    .sidebar.open { left: 0; }
    .top-header { padding: 0 16px; }
    .page-container { padding: 16px; }
    .banner-card { flex-direction: column; align-items: flex-start; gap: 14px; }
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
        <a href="appointments.php" data-tooltip="Book Hospital">
            <i class="fa-solid fa-calendar-days"></i>
            <span>Book Hospital</span>
        </a>
        <a href="schedule.php" class="active" data-tooltip="Vaccination History">
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
                <h1 style="font-size: 20px; font-weight: 700; color: var(--dark);">Vaccination History & Schedule</h1>
                <p style="font-size: 12px; color: var(--text-muted);">Track administered vaccinations and view upcoming doses</p>
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

        <!-- Banner Card with Child Selector -->
        <section class="banner-card">
            <div class="banner-title-block">
                <h2>Child: <?= e($selectedChild['child_name']) ?></h2>
                <div class="breadcrumb-trail">
                    <a href="dashboard.php">Dashboard</a> &gt; <span>Vaccination History</span>
                </div>
            </div>

            <div class="child-select-wrap">
                <label style="font-size:12px;font-weight:600;color:var(--dark);">Select Child:</label>
                <select onchange="location.href='schedule.php?child_id=' + this.value;">
                    <?php if (!empty($children)): ?>
                        <?php foreach ($children as $c): ?>
                            <option value="<?= (int)$c['id'] ?>" <?= ((int)$c['id'] === (int)$selectedChild['id']) ? 'selected' : '' ?>>
                                <?= e($c['child_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="1">Muhammad Ali (Demo)</option>
                    <?php endif; ?>
                </select>
            </div>
        </section>

        <!-- Toggle Tabs -->
        <div class="schedule-tabs-nav">
            <button class="s-tab-btn active" id="tabBtnHistory" onclick="showTab('history')">
                <i class="fa-solid fa-clock-rotate-left"></i> Administered Vaccines
            </button>
            <button class="s-tab-btn" id="tabBtnSchedule" onclick="showTab('schedule')">
                <i class="fa-regular fa-calendar-days"></i> Recommended EPI Schedule
            </button>
        </div>

        <!-- Tab 1: Administered Vaccines Table -->
        <section class="content-card" id="tabHistory">
            <h3 style="font-size: 16px; font-weight: 700; color: var(--dark); margin-bottom: 16px;">
                Vaccines Received by <?= e($selectedChild['child_name']) ?>
            </h3>
            <div class="table-responsive">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Vaccine</th>
                            <th>Dose</th>
                            <th>Hospital Center</th>
                            <th>Date Administered</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($displayRecords as $idx => $r): ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td><strong><?= e($r['vaccine_name']) ?></strong></td>
                                <td><?= e($r['dose'] ?? 'Dose 1') ?></td>
                                <td><?= e($r['hospital_name'] ?? 'City Hospital') ?></td>
                                <td><?= date('d M Y', strtotime($r['date_administered'])) ?></td>
                                <td><span class="badge-status badge-completed"><i class="fa-solid fa-check"></i> Vaccinated</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Tab 2: EPI Schedule Table -->
        <section class="content-card" id="tabSchedule" style="display:none;">
            <h3 style="font-size: 16px; font-weight: 700; color: var(--dark); margin-bottom: 16px;">
                National EPI Vaccination Timetable
            </h3>
            <div class="table-responsive">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Target Age</th>
                            <th>Recommended Vaccines</th>
                            <th>Calculated Target Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $childDob = new DateTime($selectedChild['dob']);
                        foreach ($epiSchedule as $item): 
                            $dueDate = clone $childDob;
                            $dueDate->modify($item['offset']);
                        ?>
                            <tr>
                                <td><strong><?= e($item['age']) ?></strong></td>
                                <td><?= e($item['vaccines']) ?></td>
                                <td><?= $dueDate->format('d M Y') ?></td>
                                <td>
                                    <?php if ($item['status'] === 'Completed'): ?>
                                        <span class="badge-status badge-completed"><i class="fa-solid fa-check"></i> Completed</span>
                                    <?php elseif ($item['status'] === 'Upcoming'): ?>
                                        <span class="badge-status badge-upcoming"><i class="fa-regular fa-clock"></i> Upcoming</span>
                                    <?php else: ?>
                                        <span class="badge-status badge-due">Due Soon</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="appointments.php?child_id=<?= (int)$selectedChild['id'] ?>" class="btn-book-shortcut">
                                        Book Now &rarr;
                                    </a>
                                </td>
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

function showTab(tabName) {
    const tabHistory = document.getElementById('tabHistory');
    const tabSchedule = document.getElementById('tabSchedule');
    const btnHistory = document.getElementById('tabBtnHistory');
    const btnSchedule = document.getElementById('tabBtnSchedule');

    if (tabName === 'history') {
        tabHistory.style.display = 'block';
        tabSchedule.style.display = 'none';
        btnHistory.classList.add('active');
        btnSchedule.classList.remove('active');
    } else {
        tabHistory.style.display = 'none';
        tabSchedule.style.display = 'block';
        btnHistory.classList.remove('active');
        btnSchedule.classList.add('active');
    }
}
</script>

</body>
</html>
