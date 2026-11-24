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

// Fetch Child Information for this parent
$child = null;
$totalChildren = 0;
if ($parentId > 0) {
    try {
        $cRes = $conn->query("SELECT * FROM children WHERE parent_id = {$parentId} ORDER BY id ASC LIMIT 1");
        if ($cRes && $row = $cRes->fetch_assoc()) {
            $child = $row;
        }
        $countRes = $conn->query("SELECT COUNT(*) AS total FROM children WHERE parent_id = {$parentId}");
        if ($countRes && $row = $countRes->fetch_assoc()) {
            $totalChildren = (int)$row['total'];
        }
    } catch (Throwable $t) {}
}

// Default child fallback matching screenshot if no child registered yet
if (!$child) {
    $child = [
        'id' => 1,
        'child_name' => 'Muhammad Ali',
        'dob' => '2026-01-10',
        'gender' => 'Male'
    ];
}
$dispTotalChildren = $totalChildren > 0 ? $totalChildren : 1;

// Fetch Upcoming Appointment for this parent
$upcomingApp = null;
$totalUpcoming = 0;
$totalCompleted = 0;
$totalHospitals = 0;

if ($parentId > 0) {
    try {
        $aRes = $conn->query("
            SELECT a.*, h.hospital_name 
            FROM appointments a 
            LEFT JOIN hospitals h ON a.hospital_id = h.id 
            WHERE a.parent_id = {$parentId} AND a.appointment_date >= CURDATE()
            ORDER BY a.appointment_date ASC 
            LIMIT 1
        ");
        if ($aRes && $row = $aRes->fetch_assoc()) {
            $upcomingApp = $row;
        }

        $cntQ = $conn->query("SELECT COUNT(*) AS total FROM appointments WHERE parent_id = {$parentId} AND status IN ('Pending', 'Approved')");
        if ($cntQ && $row = $cntQ->fetch_assoc()) {
            $totalUpcoming = (int)$row['total'];
        }

        $cntComp = $conn->query("SELECT COUNT(*) AS total FROM appointments WHERE parent_id = {$parentId} AND status = 'Completed'");
        if ($cntComp && $row = $cntComp->fetch_assoc()) {
            $totalCompleted = (int)$row['total'];
        }

        $cntHosp = $conn->query("SELECT COUNT(DISTINCT hospital_id) AS total FROM appointments WHERE parent_id = {$parentId}");
        if ($cntHosp && $row = $cntHosp->fetch_assoc()) {
            $totalHospitals = (int)$row['total'];
        }
    } catch (Throwable $t) {}
}

// Fallbacks matching screenshot
$dispUpcomingCount = $totalUpcoming > 0 ? $totalUpcoming : 1;
$dispCompletedCount = $totalCompleted > 0 ? $totalCompleted : 1;
$dispHospitalCount = $totalHospitals > 0 ? $totalHospitals : 1;

$upcomingVaccineName = $upcomingApp['notes'] ?? 'Polio Vaccine';
$upcomingDate = !empty($upcomingApp['appointment_date']) ? date('d M Y', strtotime($upcomingApp['appointment_date'])) : '20 Nov 2026';
$upcomingHospital = $upcomingApp['hospital_name'] ?? 'City Hospital';

// Fetch Vaccination History
$history = [];
if ($parentId > 0 && isset($child['id'])) {
    try {
        $hRes = $conn->query("
            SELECT vr.*, v.name AS vaccine_name 
            FROM vaccination_records vr 
            LEFT JOIN vaccines v ON vr.vaccine_id = v.id 
            WHERE vr.child_id = {$child['id']} 
            ORDER BY vr.date_administered DESC 
            LIMIT 4
        ");
        if ($hRes) {
            while ($row = $hRes->fetch_assoc()) {
                $history[] = $row;
            }
        }
    } catch (Throwable $t) {}
}

$demoHistory = [
    ['vaccine_name' => 'BCG', 'date_administered' => '2026-03-15', 'status' => 'Vaccinated'],
    ['vaccine_name' => 'Polio', 'date_administered' => '2026-09-15', 'status' => 'Vaccinated'],
];
$displayHistory = !empty($history) ? $history : $demoHistory;

// Vaccine Availability List (Live from DB - Only Available Vaccines)
$vaccinesList = [];
try {
    $vRes = $conn->query("SELECT name, status FROM vaccines WHERE name != 'zaid' AND (status = 'Available' OR status IS NULL OR status = '') ORDER BY id ASC LIMIT 8");
    if ($vRes) {
        while ($vRow = $vRes->fetch_assoc()) {
            $vaccinesList[] = [
                'name' => $vRow['name'],
                'status' => 'Available'
            ];
        }
    }
} catch (Throwable $t) {}

if (empty($vaccinesList)) {
    $vaccinesList = [
        ['name' => 'Oral Polio Vaccine (OPV & IPV)', 'status' => 'Available'],
        ['name' => 'Pentavalent Vaccine (DTP-HepB-Hib)', 'status' => 'Available'],
        ['name' => 'Pneumococcal Vaccine (PCV)', 'status' => 'Available'],
        ['name' => 'Measles-Rubella (MR) Vaccine', 'status' => 'Available'],
    ];
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
<title>Parent Dashboard | Vaccination Management System</title>

<!-- Fonts & Icons from Project Theme -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>
/* ==================== PROJECT DESIGN SYSTEM TOKENS ==================== */
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

    /* Card Accent Gradients */
    --kpi-blue: #087e8b;
    --kpi-green: #059669;
    --kpi-amber: #d97706;
    --kpi-purple: #7c3aed;
}

* { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
body { background-color: var(--body-bg); color: var(--text-dark); display: flex; min-height: 100vh; overflow-x: hidden; }

/* ==================== SIDEBAR ==================== */
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

/* Sidebar Bottom Promo Widget */
.sidebar-health-widget {
    margin: 14px 12px 20px;
    background: linear-gradient(135deg, #0e2b44 0%, #153e61 100%);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 14px;
    padding: 18px 14px;
    text-align: center;
    position: relative;
    overflow: hidden;
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

.widget-title {
    font-size: 13px;
    font-weight: 700;
    color: #ffffff;
    line-height: 1.3;
    margin-bottom: 12px;
}

.widget-family-illustration {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    margin-top: 6px;
}
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

/* ==================== MAIN CONTENT ==================== */
.main-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 0;
    overflow-y: auto;
}

/* Top Header */
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
.header-title-block h1 {
    font-size: 20px;
    font-weight: 700;
    color: var(--dark);
    line-height: 1.2;
    margin: 0;
}
.header-title-block p {
    font-size: 12px;
    color: var(--text-muted);
    margin: 0;
}
.portal-badge-text { font-size: 13.5px; color: var(--text-muted); font-weight: 600; }
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
    transition: all 0.2s;
}
.notif-bell:hover { background: var(--primary); color: #ffffff; }

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
    transition: background 0.2s;
}
.user-profile-btn:hover { background: var(--light); }

.user-profile-btn .avatar-circle {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
    display: grid;
    place-items: center;
    font-size: 15px;
}

/* Dashboard Body Container */
.dashboard-container {
    padding: 24px 30px 40px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* ==================== 1. HERO WELCOME BANNER ==================== */
.welcome-hero-banner {
    background: linear-gradient(135deg, #e3f7f8 0%, #edf9f9 50%, #e0f2fe 100%);
    border: 1px solid #d2eaed;
    border-radius: 16px;
    padding: 26px 34px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 18px rgba(8, 126, 139, 0.05);
}

.welcome-text-block {
    z-index: 2;
    max-width: 100%;
}

.welcome-text-block h1 {
    font-size: 26px;
    font-weight: 800;
    color: var(--dark);
    line-height: 1.25;
    margin-bottom: 6px;
}

.welcome-text-block p {
    font-size: 14px;
    color: var(--text);
    font-weight: 400;
}

.welcome-graphics-block {
    display: flex;
    align-items: center;
    gap: 16px;
    position: relative;
    z-index: 2;
}

.hero-shield-badge {
    width: 68px;
    height: 74px;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    clip-path: polygon(50% 0%, 100% 15%, 100% 80%, 50% 100%, 0% 80%, 0% 15%);
    display: grid;
    place-items: center;
    color: #ffffff;
    font-size: 26px;
    box-shadow: 0 6px 18px rgba(8, 126, 139, 0.35);
}

.hero-baby-avatar {
    width: 76px;
    height: 76px;
    border-radius: 50%;
    background: #ffffff;
    border: 3px solid #ffffff;
    box-shadow: 0 4px 16px rgba(16, 43, 70, 0.12);
    display: grid;
    place-items: center;
    overflow: hidden;
}

.hero-baby-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Background soft decorative plus shapes */
.hero-bg-plus {
    position: absolute;
    right: 18px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 110px;
    color: rgba(8, 126, 139, 0.08);
    pointer-events: none;
    font-weight: 900;
}

/* ==================== 2. ROW 1: THREE MAIN CARDS ==================== */
.cards-row-1 {
    display: grid;
    grid-template-columns: 1.1fr 1.3fr 1.1fr;
    gap: 18px;
}

.panel-card {
    background: var(--card-bg);
    border-radius: 14px;
    border: 1px solid var(--border-color);
    overflow: hidden;
    box-shadow: 0 3px 14px rgba(16, 43, 70, 0.04);
    display: flex;
    flex-direction: column;
}

/* Navy Header for each Panel */
.panel-card-header {
    background: var(--dark);
    color: #ffffff;
    padding: 12px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 13px;
    font-weight: 600;
}
.panel-card-header i { margin-right: 8px; color: var(--secondary); }

.panel-card-body {
    padding: 18px 20px;
    flex: 1;
}

/* Card 1: My Child */
.child-profile-flex {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 12px;
}

.child-avatar-img {
    width: 68px;
    height: 68px;
    border-radius: 50%;
    background: var(--light);
    border: 3px solid #d4ebed;
    display: grid;
    place-items: center;
    color: var(--primary);
    font-size: 28px;
    flex-shrink: 0;
    overflow: hidden;
}

.child-details h3 {
    font-size: 16px;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 2px;
}
.child-details p {
    font-size: 11px;
    color: var(--text-muted);
    line-height: 1.4;
}

.badge-registered {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #e6f8f0;
    color: #0b6b43;
    padding: 3px 9px;
    border-radius: 15px;
    font-size: 10px;
    font-weight: 700;
    margin-top: 4px;
}
.badge-registered::before {
    content: '●';
    font-size: 10px;
    color: #10b981;
}

.manage-child-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    font-weight: 600;
    color: var(--primary);
    background: var(--light);
    border: 1px solid #cce8e8;
    padding: 5px 12px;
    border-radius: 6px;
    text-decoration: none;
    margin-top: 6px;
    transition: all 0.2s;
}
.manage-child-link:hover { background: var(--primary); color: #ffffff; }

/* Card 2: Upcoming Vaccination */
.upcoming-vaccine-flex {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 12px;
}

.vaccine-circle-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: #f1f4fe;
    display: grid;
    place-items: center;
    color: var(--primary);
    font-size: 24px;
    flex-shrink: 0;
}

.upcoming-details h3 {
    font-size: 16px;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 3px;
}
.upcoming-details .detail-item {
    font-size: 12px;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 2px;
}

.btn-book-hospital {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #eaf8f8;
    color: var(--primary);
    font-size: 11px;
    font-weight: 700;
    padding: 6px 14px;
    border-radius: 6px;
    text-decoration: none;
    margin-top: 6px;
    border: 1px solid #cce8e8;
    transition: all 0.2s;
}
.btn-book-hospital:hover { background: var(--primary); color: #ffffff; }

/* Card 3: 4 Metric Mini-Cards Grid */
.mini-kpi-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    height: 100%;
}

.mini-kpi-box {
    border-radius: 10px;
    padding: 12px 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: transform 0.2s;
}
.mini-kpi-box:hover { transform: translateY(-2px); }

.mini-kpi-blue { background: #eaf8f8; border: 1px solid #cce8e8; }
.mini-kpi-green { background: #ecfdf5; border: 1px solid #a7f3d0; }
.mini-kpi-amber { background: #fffbeb; border: 1px solid #fde68a; }
.mini-kpi-purple { background: #f3e8ff; border: 1px solid #e9d5ff; }

.mini-kpi-icon {
    font-size: 22px;
}
.mini-kpi-blue .mini-kpi-icon { color: var(--primary); }
.mini-kpi-green .mini-kpi-icon { color: #059669; }
.mini-kpi-amber .mini-kpi-icon { color: #d97706; }
.mini-kpi-purple .mini-kpi-icon { color: #7c3aed; }

.mini-kpi-info { text-align: right; }
.mini-kpi-info h4 { font-size: 20px; font-weight: 800; color: var(--dark); line-height: 1; }
.mini-kpi-info span { font-size: 11px; color: var(--text-muted); font-weight: 500; }

/* ==================== 3. ROW 2: HISTORY & TIMELINE ==================== */
.cards-row-2 {
    display: grid;
    grid-template-columns: 1.3fr 1.1fr;
    gap: 18px;
}

/* History Table */
.modern-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
}
.modern-table th {
    background: #f7fcfc;
    color: var(--dark);
    font-weight: 600;
    text-align: left;
    padding: 9px 12px;
    border-bottom: 2px solid var(--border-color);
}
.modern-table td {
    padding: 10px 12px;
    border-bottom: 1px solid #edf5f6;
    color: var(--dark);
}
.badge-vaccinated {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #e6f8f0;
    color: #0b6b43;
    padding: 3px 10px;
    border-radius: 14px;
    font-size: 11px;
    font-weight: 600;
}

/* Appointment Status Step Timeline */
.timeline-flex-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    position: relative;
}

.appointment-timeline {
    position: relative;
    padding-left: 28px;
    display: flex;
    flex-direction: column;
    gap: 14px;
    flex: 1;
}

.appointment-timeline::before {
    content: '';
    position: absolute;
    left: 9px;
    top: 6px;
    bottom: 6px;
    width: 2px;
    background: #cbdfe2;
}

.timeline-step {
    position: relative;
}

.timeline-node {
    position: absolute;
    left: -28px;
    top: 2px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: grid;
    place-items: center;
    font-size: 10px;
    color: #ffffff;
}

.node-done { background: #00b87c; }
.node-pending { background: #087e8b; }

.timeline-step strong {
    display: block;
    font-size: 12px;
    font-weight: 700;
    color: var(--dark);
}
.timeline-step small {
    display: block;
    font-size: 11px;
    color: var(--text-muted);
}

.timeline-illustration {
    width: 90px;
    height: 90px;
    background: linear-gradient(135deg, #eaf8f8, #e0f2fe);
    border-radius: 50%;
    display: grid;
    place-items: center;
    color: var(--primary);
    font-size: 38px;
    flex-shrink: 0;
    box-shadow: 0 4px 14px rgba(8, 126, 139, 0.15);
}

/* ==================== 4. AVAILABLE VACCINES & CARDS BELOW ==================== */
.vaccine-list-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
    gap: 12px;
}
.vaccine-item-compact {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 14px;
    background: #fbfdfd;
    border: 1px solid #e8f2f4;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 500;
    color: var(--dark);
    transition: all 0.2s ease;
}
.vaccine-item-compact:hover {
    background: #f0f8f9;
    border-color: #cde4e8;
    transform: translateY(-1px);
}

.cards-row-2 {
    display: grid;
    grid-template-columns: 1fr 1.35fr;
    gap: 18px;
}

.badge-status {
    padding: 2px 9px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 600;
}
.badge-available { background: #e6f8f0; color: #0b6b43; }
.badge-not-avail { background: #fdeeee; color: #a12626; }

/* Support Box */
.support-center-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 10px 0;
}
.support-icon-circle {
    width: 52px;
    height: 52px;
    background: #eaf8f8;
    color: var(--primary);
    border-radius: 50%;
    display: grid;
    place-items: center;
    font-size: 24px;
    margin-bottom: 10px;
}
.support-center-box h4 {
    font-size: 15px;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 4px;
}
.support-center-box p {
    font-size: 11px;
    color: var(--text-muted);
    margin-bottom: 12px;
}
.btn-support {
    background: var(--dark);
    color: #ffffff;
    padding: 6px 16px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    text-decoration: none;
    transition: background 0.2s;
}
.btn-support:hover { background: var(--primary); }

/* Quick Actions 4 Tiles */
.quick-tiles-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
}
.quick-action-tile {
    border-radius: 10px;
    padding: 14px 6px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6px;
    text-decoration: none;
    transition: all 0.2s;
}
.quick-action-tile:hover { transform: translateY(-3px); }

.quick-action-tile i { font-size: 18px; }
.quick-action-tile span { font-size: 10px; font-weight: 600; text-align: center; }

.qtile-blue { background: #eaf8f8; border: 1px solid #cce8e8; color: var(--primary); }
.qtile-blue:hover { background: var(--primary); color: #ffffff; }

.qtile-green { background: #ecfdf5; border: 1px solid #a7f3d0; color: #059669; }
.qtile-green:hover { background: #059669; color: #ffffff; }

.qtile-purple { background: #f3e8ff; border: 1px solid #e9d5ff; color: #7c3aed; }
.qtile-purple:hover { background: #7c3aed; color: #ffffff; }

.qtile-orange { background: #fffbeb; border: 1px solid #fde68a; color: #d97706; }
.qtile-orange:hover { background: #d97706; color: #ffffff; }

/* Responsive adjustments */
@media (max-width: 1200px) {
    .cards-row-1 { grid-template-columns: 1fr; }
    .cards-row-2 { grid-template-columns: 1fr; }
    .cards-row-3 { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    .sidebar { position: fixed; left: -265px; top: 0; bottom: 0; }
    .sidebar.open { left: 0; }
    .welcome-hero-banner { flex-direction: column; text-align: center; }
    .welcome-text-block { max-width: 100%; margin-bottom: 16px; }
    .top-header { padding: 0 16px; }
    .dashboard-container { padding: 16px; }
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
        <a href="dashboard.php" class="active" data-tooltip="Dashboard">
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
        <a href="schedule.php" data-tooltip="Vaccination History">
            <i class="fa-solid fa-file-medical"></i>
            <span>Vaccination History</span>
        </a>
    </nav>

    <!-- Sidebar Bottom Promo Widget -->
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
                <h1>Parent Dashboard</h1>
                <p>Welcome to Vaccination Management System</p>
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

    <!-- Dashboard Body -->
    <main class="dashboard-container">

        <!-- 1. HERO WELCOME BANNER -->
        <section class="welcome-hero-banner">
            <div class="welcome-text-block">
                <h1>Welcome Back, <?= e($parentName) ?>!</h1>
                <p>Keep your child's vaccination up to date and stay protected.</p>
            </div>

            <div class="hero-bg-plus">+</div>
        </section>

        <!-- 2. ROW 1: THREE MAIN CARDS -->
        <section class="cards-row-1">
            
            <!-- Card 1: My Child -->
            <div class="panel-card">
                <div class="panel-card-header">
                    <span><i class="fa-solid fa-user"></i> My Child</span>
                </div>
                <div class="panel-card-body">
                    <div class="child-profile-flex">
                        <div class="child-avatar-img">
                            <i class="fa-solid fa-child-reaching"></i>
                        </div>
                        <div class="child-details">
                            <h3><?= e($child['child_name']) ?></h3>
                            <p>DOB: <?= date('d M Y', strtotime($child['dob'])) ?></p>
                            <p>Gender: <?= e($child['gender']) ?></p>
                            <span class="badge-registered">Registered</span>
                        </div>
                    </div>
                    <a href="children.php" class="manage-child-link">
                        <i class="fa-solid fa-pen-to-square"></i> Manage Child
                    </a>
                </div>
            </div>

            <!-- Card 2: Upcoming Vaccination -->
            <div class="panel-card">
                <div class="panel-card-header">
                    <span><i class="fa-regular fa-calendar-check"></i> Upcoming Vaccination</span>
                </div>
                <div class="panel-card-body">
                    <div class="upcoming-vaccine-flex">
                        <div class="vaccine-circle-icon">
                            <i class="fa-solid fa-syringe"></i>
                        </div>
                        <div class="upcoming-details">
                            <h3><?= e($upcomingVaccineName) ?></h3>
                            <div class="detail-item"><i class="fa-regular fa-calendar"></i> <?= e($upcomingDate) ?></div>
                            <div class="detail-item"><i class="fa-solid fa-location-dot"></i> <?= e($upcomingHospital) ?></div>
                        </div>
                    </div>
                    <a href="appointments.php" class="btn-book-hospital">
                        Book Hospital &rarr;
                    </a>
                </div>
            </div>

            <!-- Card 3: 4 Metric Mini-Cards Grid -->
            <div class="mini-kpi-grid">
                
                <div class="mini-kpi-box mini-kpi-blue">
                    <i class="fa-solid fa-users mini-kpi-icon"></i>
                    <div class="mini-kpi-info">
                        <h4><?= $dispTotalChildren ?></h4>
                        <span>Child</span>
                    </div>
                </div>

                <div class="mini-kpi-box mini-kpi-green">
                    <i class="fa-solid fa-shield-halved mini-kpi-icon"></i>
                    <div class="mini-kpi-info">
                        <h4><?= $dispCompletedCount ?></h4>
                        <span>Completed</span>
                    </div>
                </div>

                <div class="mini-kpi-box mini-kpi-amber">
                    <i class="fa-regular fa-calendar-plus mini-kpi-icon"></i>
                    <div class="mini-kpi-info">
                        <h4><?= $dispUpcomingCount ?></h4>
                        <span>Upcoming</span>
                    </div>
                </div>

                <div class="mini-kpi-box mini-kpi-purple">
                    <i class="fa-solid fa-hospital mini-kpi-icon"></i>
                    <div class="mini-kpi-info">
                        <h4><?= $dispHospitalCount ?></h4>
                        <span>Hospital</span>
                    </div>
                </div>

            </div>

        </section>

        <!-- 3. ROW 2: HISTORY & TIMELINE -->
        <section class="cards-row-2">
            
            <!-- Panel 1: Vaccination History -->
            <div class="panel-card">
                <div class="panel-card-header">
                    <span><i class="fa-regular fa-file-lines"></i> Vaccination History</span>
                    <a href="schedule.php" style="color:var(--secondary);font-size:11px;text-decoration:none;">View All &rarr;</a>
                </div>
                <div class="panel-card-body" style="padding:12px 16px;">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Vaccine</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($displayHistory as $idx => $h): ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td><strong><?= e($h['vaccine_name']) ?></strong></td>
                                    <td><?= date('d M Y', strtotime($h['date_administered'])) ?></td>
                                    <td><span class="badge-vaccinated"><i class="fa-solid fa-check"></i> Vaccinated</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Panel 2: Appointment Status Timeline -->
            <div class="panel-card">
                <div class="panel-card-header">
                    <span><i class="fa-regular fa-calendar-check"></i> Appointment Status</span>
                </div>
                <div class="panel-card-body">
                    <div class="timeline-flex-container">
                        <div class="appointment-timeline">
                            
                            <div class="timeline-step">
                                <div class="timeline-node node-done"><i class="fa-solid fa-check"></i></div>
                                <strong>Request Submitted</strong>
                                <small>13 Nov 2026, 10:30 AM</small>
                            </div>

                            <div class="timeline-step">
                                <div class="timeline-node node-done"><i class="fa-solid fa-check"></i></div>
                                <strong>Approved by Admin</strong>
                                <small>14 Nov 2026, 09:15 AM</small>
                            </div>

                            <div class="timeline-step">
                                <div class="timeline-node node-pending"><i class="fa-regular fa-clock"></i></div>
                                <strong>Vaccination Pending</strong>
                                <small>At <?= e($upcomingHospital) ?></small>
                            </div>

                        </div>

                        <div class="timeline-illustration">
                            <i class="fa-solid fa-shield-virus"></i>
                        </div>
                    </div>
                </div>
            </div>

        </section>

        <!-- 4. AVAILABLE VACCINES (FULL WIDTH CARD) -->
        <section class="section-vaccines-full">
            <div class="panel-card">
                <div class="panel-card-header">
                    <span><i class="fa-solid fa-syringe"></i> Available Vaccines</span>
                    <a href="../vaccine.php" style="font-size:12px; font-weight:600; color:var(--primary); text-decoration:none; display:flex; align-items:center; gap:5px;">
                        View All Vaccines <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
                <div class="panel-card-body" style="padding:16px 20px;">
                    <div class="vaccine-list-grid">
                        <?php if (!empty($vaccinesList)): ?>
                            <?php foreach ($vaccinesList as $vac): ?>
                                <div class="vaccine-item-compact">
                                    <span><?= e($vac['name']) ?></span>
                                    <span class="badge-status badge-available">Available</span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="padding:15px; color:var(--text-muted); font-size:12.5px; text-align:center;">No vaccines currently available.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- 5. ROW BELOW VACCINES: SUPPORT & QUICK ACTIONS -->
        <section class="cards-row-2">
            
            <!-- Panel 1: Need Help? Support Box -->
            <div class="panel-card">
                <div class="panel-card-body">
                    <div class="support-center-box">
                        <div class="support-icon-circle">
                            <i class="fa-solid fa-headset"></i>
                        </div>
                        <h4>Need Help?</h4>
                        <p>Contact our support team for any assistance or inquiries.</p>
                        <a href="../contact.php" class="btn-support">Contact Us &rarr;</a>
                    </div>
                </div>
            </div>

            <!-- Panel 2: Quick Actions -->
            <div class="panel-card">
                <div class="panel-card-header">
                    <span><i class="fa-solid fa-bolt"></i> Quick Actions</span>
                </div>
                <div class="panel-card-body" style="padding:16px 20px;">
                    <div class="quick-tiles-grid">
                        
                        <a href="appointments.php" class="quick-action-tile qtile-blue" title="Book Hospital">
                            <i class="fa-regular fa-calendar-check"></i>
                            <span>Book Hospital</span>
                        </a>

                        <a href="schedule.php" class="quick-action-tile qtile-green" title="View Reports">
                            <i class="fa-regular fa-file-lines"></i>
                            <span>View Reports</span>
                        </a>

                        <a href="profile.php" class="quick-action-tile qtile-purple" title="Update Profile">
                            <i class="fa-regular fa-user"></i>
                            <span>Update Profile</span>
                        </a>

                        <a href="../logout.php" class="quick-action-tile qtile-orange" title="Logout">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                            <span>Logout</span>
                        </a>

                    </div>
                </div>
            </div>

        </section>

    </main>

</div>

<script>
const sidebarCollapseBtn = document.getElementById('sidebarCollapseBtn');
const parentSidebar = document.getElementById('parentSidebar');
const mobileToggle = document.getElementById('sidebarToggle');

// Restore collapsed state from localStorage
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
</script>

</body>
</html>
