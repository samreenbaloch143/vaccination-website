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
    $stmt = $conn->prepare("SELECT id, admin_name, hospital_name, email, phone, city, address, status FROM hospitals WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $hospitalId);
        $stmt->execute();
        $hospital = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

$hospitalName = $hospital['hospital_name'] ?? $_SESSION['hospital_name'] ?? 'City Hospital';
$adminName = $hospital['admin_name'] ?? $_SESSION['name'] ?? 'Admin';

// Handle quick appointment status updates
$statusMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $appointmentId = (int)($_POST['appointment_id'] ?? 0);
    $newStatus = trim($_POST['status'] ?? '');
    $allowed = ['Pending', 'Approved', 'Rejected', 'Completed'];
    if ($appointmentId > 0 && in_array($newStatus, $allowed, true)) {
        $uStmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ? AND hospital_id = ?");
        if ($uStmt) {
            $uStmt->bind_param("sii", $newStatus, $appointmentId, $hospitalId);
            $uStmt->execute();
            $uStmt->close();
            $statusMsg = "Appointment #$appointmentId status changed to $newStatus.";
        }
    }
}

// KPI Counts
$totalPatients = 0;
$pendingAppointments = 0;
$approvedAppointments = 0;
$completedRecords = 0;

if ($hospitalId > 0) {
    // Total distinct children/patients
    $pQuery = $conn->query("SELECT COUNT(DISTINCT child_id) AS total FROM appointments WHERE hospital_id = $hospitalId");
    if ($pQuery && $row = $pQuery->fetch_assoc()) {
        $totalPatients = (int)$row['total'];
    }

    // Pending appointments
    $pAppt = $conn->query("SELECT COUNT(*) AS total FROM appointments WHERE hospital_id = $hospitalId AND status = 'Pending'");
    if ($pAppt && $row = $pAppt->fetch_assoc()) {
        $pendingAppointments = (int)$row['total'];
    }

    // Approved appointments
    $aAppt = $conn->query("SELECT COUNT(*) AS total FROM appointments WHERE hospital_id = $hospitalId AND status = 'Approved'");
    if ($aAppt && $row = $aAppt->fetch_assoc()) {
        $approvedAppointments = (int)$row['total'];
    }

    // Completed vaccination records
    $cRec = $conn->query("SELECT COUNT(*) AS total FROM vaccination_records WHERE hospital_id = $hospitalId");
    if ($cRec && $row = $cRec->fetch_assoc()) {
        $completedRecords = (int)$row['total'];
    }
}

// Recent Appointments
$recentAppointments = [];
if ($hospitalId > 0) {
    $stmt = $conn->prepare("
        SELECT a.id, a.appointment_date, a.appointment_time, a.status, a.notes,
               c.child_name, c.dob, c.gender, p.name AS parent_name, p.phone AS parent_phone
        FROM appointments a
        INNER JOIN children c ON c.id = a.child_id
        INNER JOIN parents p ON p.id = a.parent_id
        WHERE a.hospital_id = ?
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
        LIMIT 6
    ");
    if ($stmt) {
        $stmt->bind_param("i", $hospitalId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $recentAppointments[] = $row;
        }
        $stmt->close();
    }
}

// Recent Vaccination Records
$recentRecords = [];
if ($hospitalId > 0) {
    $stmt = $conn->prepare("
        SELECT r.id, r.date_administered, r.dose, r.notes,
               c.child_name, v.name AS vaccine_name
        FROM vaccination_records r
        INNER JOIN children c ON c.id = r.child_id
        INNER JOIN vaccines v ON v.id = r.vaccine_id
        WHERE r.hospital_id = ?
        ORDER BY r.date_administered DESC, r.id DESC
        LIMIT 5
    ");
    if ($stmt) {
        $stmt->bind_param("i", $hospitalId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $recentRecords[] = $row;
        }
        $stmt->close();
    }
}

// Available vaccines list for widget
$vaccinesList = [];
$vRes = $conn->query("SELECT name, description FROM vaccines ORDER BY name ASC LIMIT 4");
if ($vRes) {
    while ($row = $vRes->fetch_assoc()) {
        $vaccinesList[] = $row;
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
    <title>Hospital Dashboard | Vaccination Management System</title>
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
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
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

        /* User profile block in sidebar */
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

        .header-hospital-title h1,
        .header-hospital-title h2 {
            font-size: 20px;
            font-weight: 700;
            color: var(--dark);
            margin: 0;
            line-height: 1.2;
        }

        .header-hospital-title p,
        .header-hospital-title span {
            font-size: 12px;
            color: var(--text-muted);
            display: block;
            margin-top: 2px;
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

        .header-hospital-title {
            display: flex;
            flex-direction: column;
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

        .notif-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background: #ef4444;
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            border: 2px solid #ffffff;
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
            gap: 22px;
        }

        /* HERO BANNER */
        .hero-banner {
            background: linear-gradient(135deg, #102b46 0%, #0d3856 50%, #087e8b 100%);
            border-radius: 16px;
            padding: 28px 34px;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 8px 24px rgba(16, 43, 70, 0.12);
            position: relative;
            overflow: hidden;
        }

        .hero-banner::after {
            content: '';
            position: absolute;
            right: -30px;
            top: -30px;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(19, 166, 166, 0.25) 0%, rgba(255,255,255,0) 70%);
            pointer-events: none;
        }

        .hero-text h1 {
            font-family: 'Playfair Display', serif;
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 6px;
            letter-spacing: 0.3px;
        }

        .hero-text p {
            font-size: 13px;
            color: #e2f1f3;
            max-width: 580px;
            line-height: 1.5;
        }

        .hero-actions {
            display: flex;
            gap: 12px;
            z-index: 2;
        }

        .btn-hero-primary {
            background: #ffffff;
            color: var(--primary);
            padding: 11px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
            transition: all 0.2s;
        }

        .btn-hero-primary:hover {
            background: #eaf8f8;
            transform: translateY(-2px);
        }

        .btn-hero-outline {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 11px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .btn-hero-outline:hover {
            background: rgba(255, 255, 255, 0.22);
        }

        /* 4 KPI CARDS GRID */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
        }

        .kpi-card {
            background: var(--card-bg);
            border-radius: 14px;
            border: 1px solid var(--border-color);
            padding: 20px 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 18px rgba(16, 43, 70, 0.04);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .kpi-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(16, 43, 70, 0.08);
        }

        .kpi-info-block span {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .kpi-info-block h3 {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark);
            margin-top: 4px;
            line-height: 1.1;
        }

        .kpi-icon-pill {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            font-size: 22px;
        }

        .kpi-icon-blue   { background: #e0f2fe; color: #0284c7; }
        .kpi-icon-amber  { background: #fef3c7; color: #d97706; }
        .kpi-icon-teal   { background: #ccfbf1; color: #0f766e; }
        .kpi-icon-purple { background: #ede9fe; color: #7c3aed; }

        /* TWO COLUMN MAIN CONTENT */
        .content-split-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 22px;
        }

        .panel-card {
            background: var(--card-bg);
            border-radius: 14px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 18px rgba(16, 43, 70, 0.04);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .panel-header {
            padding: 18px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border-color);
            background: #ffffff;
        }

        .panel-header h3 {
            font-size: 16px;
            font-weight: 700;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .panel-header-action {
            font-size: 12.5px;
            font-weight: 600;
            color: var(--primary);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .panel-header-action:hover {
            color: var(--secondary);
        }

        .panel-body {
            padding: 20px 24px;
            flex: 1;
        }

        /* TABLES */
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
            padding: 12px 14px;
            border-bottom: 1px solid var(--border-color);
            white-space: nowrap;
        }

        .modern-table td {
            padding: 13px 14px;
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

        /* ACTION BUTTONS IN TABLE */
        .action-btn-group {
            display: inline-flex;
            gap: 6px;
        }

        .btn-table-action {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            border: none;
            display: grid;
            place-items: center;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-approve { background: #dcfce7; color: #166534; }
        .btn-approve:hover { background: #16a34a; color: #ffffff; }

        .btn-reject { background: #fee2e2; color: #991b1b; }
        .btn-reject:hover { background: #dc2626; color: #ffffff; }

        .btn-complete { background: #e0f2fe; color: #0369a1; }
        .btn-complete:hover { background: #0284c7; color: #ffffff; }

        /* HOSPITAL PROFILE SUMMARY CARD */
        .hospital-info-box {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .hosp-badge-header {
            display: flex;
            align-items: center;
            gap: 14px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border-color);
        }

        .hosp-avatar-icon {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #ffffff;
            display: grid;
            place-items: center;
            font-size: 24px;
        }

        .hosp-header-details h4 {
            font-size: 16px;
            font-weight: 700;
            color: var(--dark);
        }

        .hosp-header-details span {
            font-size: 11.5px;
            color: #059669;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .hosp-data-list {
            display: flex;
            flex-direction: column;
            gap: 11px;
            font-size: 12.5px;
        }

        .hosp-data-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #334155;
        }

        .hosp-data-item i {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            background: var(--mint-bg);
            color: var(--primary);
            display: grid;
            place-items: center;
            font-size: 12px;
            flex-shrink: 0;
        }

        /* QUICK ACTIONS BAR */
        .quick-actions-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
        }

        .quick-action-tile {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 14px;
            text-decoration: none;
            color: var(--dark);
            box-shadow: 0 4px 14px rgba(16, 43, 70, 0.03);
            transition: all 0.2s;
        }

        .quick-action-tile:hover {
            transform: translateY(-2px);
            border-color: var(--primary);
            box-shadow: 0 6px 20px rgba(8, 126, 139, 0.12);
        }

        .qa-icon-wrap {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: grid;
            place-items: center;
            font-size: 18px;
            color: #ffffff;
            flex-shrink: 0;
        }

        .qa-blue   { background: linear-gradient(135deg, #0284c7, #38bdf8); }
        .qa-teal   { background: linear-gradient(135deg, #087e8b, #13a6a6); }
        .qa-purple { background: linear-gradient(135deg, #7c3aed, #a855f7); }
        .qa-amber  { background: linear-gradient(135deg, #d97706, #fbbf24); }

        .qa-text-wrap strong {
            font-size: 13px;
            display: block;
            color: var(--dark);
        }

        .qa-text-wrap small {
            font-size: 11px;
            color: var(--text-muted);
        }

        .alert-box {
            padding: 12px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
        }
        .alert-success { background: #e6f8f0; color: #0b6b43; border: 1px solid #a7f3d0; }

        @media (max-width: 1024px) {
            .kpi-grid { grid-template-columns: repeat(2, 1fr); }
            .content-split-grid { grid-template-columns: 1fr; }
            .quick-actions-row { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 768px) {
            .menu-toggle-btn { display: flex; }
            .sidebar { position: fixed; left: -265px; top: 0; bottom: 0; }
            .sidebar.open { left: 0; }
            .top-header { padding: 0 16px; }
            .page-container { padding: 16px; }
            .kpi-grid { grid-template-columns: 1fr; }
            .hero-banner { flex-direction: column; align-items: flex-start; gap: 16px; }
            .quick-actions-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar" id="hospitalSidebar">
    
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

    <!-- Hospital User Profile in Sidebar -->
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

    <!-- Navigation Menu -->
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="active" data-tooltip="Dashboard">
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

    <!-- Sidebar Bottom Promo Widget -->
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

    <!-- Top Header -->
    <header class="top-header">
        <div class="header-left">
            <button class="menu-toggle-btn" id="menuToggle" aria-label="Toggle Menu">
                <i class="fa-solid fa-bars"></i>
            </button>
            <div class="header-hospital-title">
                <h1>Hospital Dashboard</h1>
                <p>Welcome to Vaccination Management System</p>
            </div>
        </div>
        <div class="header-right">
            <a href="appointments.php" class="notif-bell" title="Appointments">
                <i class="fa-regular fa-bell"></i>
                <?php if ($pendingAppointments > 0): ?>
                    <span class="notif-badge"><?= $pendingAppointments ?></span>
                <?php endif; ?>
            </a>
            <?php include __DIR__ . '/../components/dashboard_user_dropdown.php'; ?>
        </div>
    </header>

    <!-- Page Body -->
    <div class="page-container">

        <?php if (!empty($statusMsg)): ?>
            <div class="alert-box alert-success">
                <i class="fa-solid fa-circle-check"></i> <?= e($statusMsg) ?>
            </div>
        <?php endif; ?>

        <!-- 1. HERO WELCOME BANNER -->
        <section class="hero-banner">
            <div class="hero-text">
                <h1>Welcome Back, <?= e($adminName) ?>!</h1>
                <p>Welcome to <?= e($hospitalName) ?>'s vaccination operations portal. Monitor patient visits, review appointment bookings, and record vaccine doses efficiently.</p>
            </div>
            <div class="hero-actions">
                <a href="appointments.php" class="btn-hero-primary">
                    <i class="fa-regular fa-calendar-check"></i> Manage Appointments
                </a>
                <a href="records.php" class="btn-hero-outline">
                    <i class="fa-solid fa-plus"></i> Add Record
                </a>
            </div>
        </section>

        <!-- 2. KPI METRICS CARDS -->
        <section class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-info-block">
                    <span>Total Patients</span>
                    <h3><?= $totalPatients ?></h3>
                </div>
                <div class="kpi-icon-pill kpi-icon-blue">
                    <i class="fa-solid fa-children"></i>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-info-block">
                    <span>Pending Requests</span>
                    <h3><?= $pendingAppointments ?></h3>
                </div>
                <div class="kpi-icon-pill kpi-icon-amber">
                    <i class="fa-regular fa-clock"></i>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-info-block">
                    <span>Approved Sessions</span>
                    <h3><?= $approvedAppointments ?></h3>
                </div>
                <div class="kpi-icon-pill kpi-icon-teal">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-info-block">
                    <span>Vaccines Administered</span>
                    <h3><?= $completedRecords ?></h3>
                </div>
                <div class="kpi-icon-pill kpi-icon-purple">
                    <i class="fa-solid fa-syringe"></i>
                </div>
            </div>
        </section>

        <!-- 3. SPLIT SECTION: RECENT APPOINTMENTS & HOSPITAL INFO -->
        <section class="content-split-grid">

            <!-- Panel: Recent Appointments -->
            <div class="panel-card">
                <div class="panel-header">
                    <h3><i class="fa-regular fa-calendar-days" style="color: var(--primary);"></i> Recent Appointment Bookings</h3>
                    <a href="appointments.php" class="panel-header-action">View All <i class="fa-solid fa-arrow-right"></i></a>
                </div>
                <div class="panel-body" style="padding: 0;">
                    <?php if (!empty($recentAppointments)): ?>
                        <div class="table-responsive">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>Child Patient</th>
                                        <th>Parent Contact</th>
                                        <th>Date &amp; Time</th>
                                        <th>Status</th>
                                        <th style="text-align: right;">Quick Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentAppointments as $appt): ?>
                                        <tr>
                                            <td>
                                                <strong><?= e($appt['child_name']) ?></strong>
                                                <div style="font-size: 11px; color: var(--text-muted);"><?= e($appt['gender']) ?>, DOB: <?= date('d M Y', strtotime($appt['dob'])) ?></div>
                                            </td>
                                            <td>
                                                <?= e($appt['parent_name']) ?>
                                                <div style="font-size: 11px; color: var(--text-muted);"><?= e($appt['parent_phone']) ?></div>
                                            </td>
                                            <td>
                                                <div><strong><?= date('d M Y', strtotime($appt['appointment_date'])) ?></strong></div>
                                                <div style="font-size: 11px; color: var(--text-muted);"><?= date('h:i A', strtotime($appt['appointment_time'])) ?></div>
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
                                                <div class="action-btn-group">
                                                    <?php if ($appt['status'] === 'Pending'): ?>
                                                        <form method="POST" action="dashboard.php" style="display:inline;">
                                                            <input type="hidden" name="action" value="update_status">
                                                            <input type="hidden" name="appointment_id" value="<?= (int)$appt['id'] ?>">
                                                            <input type="hidden" name="status" value="Approved">
                                                            <button type="submit" class="btn-table-action btn-approve" title="Approve Request">
                                                                <i class="fa-solid fa-check"></i>
                                                            </button>
                                                        </form>
                                                        <form method="POST" action="dashboard.php" style="display:inline;">
                                                            <input type="hidden" name="action" value="update_status">
                                                            <input type="hidden" name="appointment_id" value="<?= (int)$appt['id'] ?>">
                                                            <input type="hidden" name="status" value="Rejected">
                                                            <button type="submit" class="btn-table-action btn-reject" title="Reject Request">
                                                                <i class="fa-solid fa-xmark"></i>
                                                            </button>
                                                        </form>
                                                    <?php elseif ($appt['status'] === 'Approved'): ?>
                                                        <form method="POST" action="dashboard.php" style="display:inline;">
                                                            <input type="hidden" name="action" value="update_status">
                                                            <input type="hidden" name="appointment_id" value="<?= (int)$appt['id'] ?>">
                                                            <input type="hidden" name="status" value="Completed">
                                                            <button type="submit" class="btn-table-action btn-complete" title="Mark as Completed">
                                                                <i class="fa-solid fa-syringe"></i>
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span style="font-size: 11px; color: var(--text-muted);">-</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div style="padding: 30px; text-align: center; color: var(--text-muted);">
                            <i class="fa-regular fa-calendar-xmark" style="font-size: 32px; color: #cbd5e1; margin-bottom: 10px;"></i>
                            <p>No appointment requests registered yet for this hospital.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Panel: Hospital Center Info -->
            <div class="panel-card">
                <div class="panel-header">
                    <h3><i class="fa-solid fa-hospital" style="color: var(--primary);"></i> Center Information</h3>
                    <a href="profile.php" class="panel-header-action">Edit Profile <i class="fa-solid fa-pen"></i></a>
                </div>
                <div class="panel-body">
                    <div class="hospital-info-box">
                        <div class="hosp-badge-header">
                            <div class="hosp-avatar-icon">
                                <i class="fa-solid fa-hospital"></i>
                            </div>
                            <div class="hosp-header-details">
                                <h4><?= e($hospitalName) ?></h4>
                                <span><i class="fa-solid fa-circle-check"></i> Government Authorized</span>
                            </div>
                        </div>

                        <div class="hosp-data-list">
                            <div class="hosp-data-item">
                                <i class="fa-solid fa-user-shield"></i>
                                <div><strong>Admin:</strong> <?= e($adminName) ?></div>
                            </div>
                            <div class="hosp-data-item">
                                <i class="fa-solid fa-envelope"></i>
                                <div><strong>Email:</strong> <?= e($hospital['email'] ?? 'N/A') ?></div>
                            </div>
                            <div class="hosp-data-item">
                                <i class="fa-solid fa-phone"></i>
                                <div><strong>Phone:</strong> <?= e($hospital['phone'] ?? 'N/A') ?></div>
                            </div>
                            <div class="hosp-data-item">
                                <i class="fa-solid fa-location-dot"></i>
                                <div><strong>City:</strong> <?= e($hospital['city'] ?? 'Karachi') ?></div>
                            </div>
                            <div class="hosp-data-item">
                                <i class="fa-solid fa-map-location-dot"></i>
                                <div><strong>Address:</strong> <?= e($hospital['address'] ?? 'N/A') ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </section>

        <!-- 4. QUICK SHORTCUT TILES -->
        <section class="quick-actions-row">
            <a href="patients.php" class="quick-action-tile">
                <div class="qa-icon-wrap qa-blue">
                    <i class="fa-solid fa-children"></i>
                </div>
                <div class="qa-text-wrap">
                    <strong>Patient Directory</strong>
                    <small>View registered children</small>
                </div>
            </a>

            <a href="appointments.php" class="quick-action-tile">
                <div class="qa-icon-wrap qa-amber">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
                <div class="qa-text-wrap">
                    <strong>Appointments</strong>
                    <small>Review &amp; approve sessions</small>
                </div>
            </a>

            <a href="records.php" class="quick-action-tile">
                <div class="qa-icon-wrap qa-teal">
                    <i class="fa-solid fa-syringe"></i>
                </div>
                <div class="qa-text-wrap">
                    <strong>Add Vaccination Record</strong>
                    <small>Log administered doses</small>
                </div>
            </a>

            <a href="profile.php" class="quick-action-tile">
                <div class="qa-icon-wrap qa-purple">
                    <i class="fa-solid fa-hospital-user"></i>
                </div>
                <div class="qa-text-wrap">
                    <strong>Hospital Profile</strong>
                    <small>Manage center info &amp; timings</small>
                </div>
            </a>
        </section>

        <!-- 5. RECENT VACCINATION RECORDS SECTION -->
        <section class="panel-card">
            <div class="panel-header">
                <h3><i class="fa-solid fa-syringe" style="color: var(--primary);"></i> Recently Administered Vaccinations</h3>
                <a href="records.php" class="panel-header-action">View All Records <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <div class="panel-body" style="padding: 0;">
                <?php if (!empty($recentRecords)): ?>
                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Patient (Child)</th>
                                    <th>Vaccine</th>
                                    <th>Dose</th>
                                    <th>Date Administered</th>
                                    <th>Notes / Remarks</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentRecords as $idx => $rec): ?>
                                    <tr>
                                        <td><?= $idx + 1 ?></td>
                                        <td><strong><?= e($rec['child_name']) ?></strong></td>
                                        <td><?= e($rec['vaccine_name']) ?></td>
                                        <td><span class="badge-status badge-approved"><?= e($rec['dose'] ?: 'Dose 1') ?></span></td>
                                        <td><?= date('d M Y', strtotime($rec['date_administered'])) ?></td>
                                        <td><?= e($rec['notes'] ?: 'Immunization completed successfully.') ?></td>
                                        <td><span class="badge-status badge-completed"><i class="fa-solid fa-check"></i> Recorded</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div style="padding: 28px; text-align: center; color: var(--text-muted);">
                        <i class="fa-solid fa-file-medical" style="font-size: 30px; color: #cbd5e1; margin-bottom: 8px;"></i>
                        <p>No vaccination records logged yet for this hospital.</p>
                        <a href="records.php" style="color: var(--primary); font-weight: 600; text-decoration: none; font-size: 13px; margin-top: 6px; display: inline-block;">+ Log First Vaccination Record</a>
                    </div>
                <?php endif; ?>
            </div>
        </section>

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