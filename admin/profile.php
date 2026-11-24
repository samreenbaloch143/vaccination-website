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

$adminId = (int)($_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? 1);
$adminName = $_SESSION['name'] ?? 'Admin';
$adminEmail = $_SESSION['email'] ?? 'admin@system.com';
$msg = '';
$err = '';

// Fetch fresh admin details
$stmt = $conn->prepare("SELECT name, email FROM admins WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("i", $adminId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $adminName = $row['name'];
        $adminEmail = $row['email'];
        $_SESSION['name'] = $adminName;
        $_SESSION['email'] = $adminEmail;
    }
    $stmt->close();
}

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $newPass = trim($_POST['password'] ?? '');

        if ($name === '' || $email === '') {
            $err = 'Name and Email are required.';
        } else {
            // Check email uniqueness
            $chk = $conn->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
            $chk->bind_param("si", $email, $adminId);
            $chk->execute();
            if ($chk->get_result()->num_rows > 0) {
                $err = 'This email is already in use by another admin.';
            } else {
                if ($newPass !== '') {
                    $hashed = password_hash($newPass, PASSWORD_DEFAULT);
                    $upd = $conn->prepare("UPDATE admins SET name = ?, email = ?, password = ? WHERE id = ?");
                    $upd->bind_param("sssi", $name, $email, $hashed, $adminId);
                } else {
                    $upd = $conn->prepare("UPDATE admins SET name = ?, email = ? WHERE id = ?");
                    $upd->bind_param("ssi", $name, $email, $adminId);
                }

                if ($upd && $upd->execute()) {
                    $msg = 'Profile updated successfully!';
                    $adminName = $name;
                    $adminEmail = $email;
                    $_SESSION['name'] = $name;
                    $_SESSION['email'] = $email;
                } else {
                    $err = 'Failed to update profile. Please try again.';
                }
                if ($upd) $upd->close();
            }
            $chk->close();
        }
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
<title>Admin Profile | Vaccination Management System</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
:root {
    --primary: #087e8b;
    --primary-hover: #066570;
    --secondary: #13a6a6;
    --dark: #102b46;
    --light: #eaf8f8;
    --light-gray: #f4f8fa;
    --text: #637487;
    --text-muted: #8fa1b3;
    --border-color: #e1e9ee;
    --white: #ffffff;
    --danger: #dc2626;
    --success: #16a34a;
    --sidebar-width: 260px;
    --sidebar-collapsed-width: 80px;
}
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Poppins',sans-serif; color:var(--dark); background:var(--light-gray); overflow-x:hidden; display:flex; min-height:100vh; }
aside.sidebar { width:var(--sidebar-width); background:var(--dark); color:white; display:flex; flex-direction:column; position:fixed; top:0; left:0; bottom:0; z-index:100; transition:width 0.3s ease; }
aside.sidebar.collapsed { width:var(--sidebar-collapsed-width); }
.sidebar-header { padding:22px 20px 18px; display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid rgba(255,255,255,0.08); }
.brand-wrapper { display:flex; align-items:center; gap:12px; text-decoration:none; color:white; overflow:hidden; }
.brand-icon { width:42px; height:42px; border-radius:12px; background:linear-gradient(135deg,var(--primary),var(--secondary)); display:grid; place-items:center; font-size:20px; flex-shrink:0; }
.brand-text h2 { font-family:'Playfair Display',serif; font-size:16px; color:white; margin:0; }
.brand-text span { font-size:10px; letter-spacing:1.5px; color:var(--secondary); text-transform:uppercase; font-weight:700; }
.sidebar.collapsed .brand-text { display:none; }
.sidebar-profile { padding:18px 20px; display:flex; align-items:center; gap:12px; background:rgba(255,255,255,0.04); border-bottom:1px solid rgba(255,255,255,0.08); }
.profile-avatar { width:44px; height:44px; border-radius:50%; background:linear-gradient(135deg,var(--secondary),var(--primary)); display:grid; place-items:center; font-size:18px; flex-shrink:0; }
.profile-info { overflow:hidden; }
.profile-info h4 { font-size:13.5px; font-weight:600; color:white; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.profile-status { font-size:11px; color:#4ade80; display:flex; align-items:center; gap:5px; }
.profile-status::before { content:''; width:7px; height:7px; background:#4ade80; border-radius:50%; display:inline-block; }
.sidebar.collapsed .profile-info { display:none; }
.sidebar-nav { padding:18px 12px; display:flex; flex-direction:column; gap:4px; flex-grow:1; overflow-y:auto; }
.sidebar-nav a { display:flex; align-items:center; gap:14px; padding:12px 14px; border-radius:10px; color:rgba(255,255,255,0.7); text-decoration:none; font-size:13px; font-weight:500; transition:all 0.2s ease; white-space:nowrap; }
.sidebar-nav a:hover, .sidebar-nav a.active { background:rgba(8,126,139,0.25); color:white; border-left:3px solid var(--secondary); }
.sidebar-nav a i { font-size:16px; width:20px; text-align:center; }
.sidebar.collapsed .sidebar-nav a span { display:none; }
.main-content { margin-left:var(--sidebar-width); flex-grow:1; display:flex; flex-direction:column; min-width:0; transition:margin-left 0.3s ease; }
aside.sidebar.collapsed + .main-content { margin-left:var(--sidebar-collapsed-width); }
.top-header { height:72px; background:var(--white); border-bottom:1px solid var(--border-color); padding:0 30px; display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; z-index:90; }
.header-left { display:flex; align-items:center; gap:16px; }
.menu-toggle-btn { background:none; border:none; color:var(--dark); font-size:19px; cursor:pointer; width:38px; height:38px; border-radius:8px; display:grid; place-items:center; }
.menu-toggle-btn:hover { background:var(--light); color:var(--primary); }
.header-right { display:flex; align-items:center; gap:16px; }
.page-container { padding:28px 32px 50px; display:flex; flex-direction:column; gap:24px; }
.alert-box { padding:14px 18px; border-radius:12px; font-size:13.5px; display:flex; align-items:center; gap:10px; }
.alert-success { background:#e8f8ed; color:var(--success); border:1px solid #b7ebd1; }
.alert-error { background:#fef2f2; color:var(--danger); border:1px solid #fecaca; }
.profile-card { background:white; border-radius:16px; border:1px solid var(--border-color); padding:32px; box-shadow:0 3px 15px rgba(0,0,0,0.03); max-width:750px; }
.profile-header-meta { display:flex; align-items:center; gap:20px; margin-bottom:28px; padding-bottom:22px; border-bottom:1px solid #edf2f5; }
.profile-header-avatar { width:68px; height:68px; border-radius:50%; background:linear-gradient(135deg,var(--primary),var(--secondary)); color:white; display:grid; place-items:center; font-size:26px; }
.form-group { margin-bottom:20px; }
.form-group label { display:block; font-size:13px; font-weight:600; margin-bottom:7px; color:var(--dark); }
.form-control { width:100%; padding:11px 15px; border-radius:10px; border:1px solid var(--border-color); font-size:13.5px; font-family:inherit; outline:none; transition:border-color 0.2s; }
.form-control:focus { border-color:var(--primary); }
.btn-submit { background:var(--primary); color:white; border:none; padding:12px 24px; border-radius:10px; font-weight:600; font-size:14px; cursor:pointer; transition:background 0.2s; }
.btn-submit:hover { background:var(--primary-hover); }
</style>
</head>
<body>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="brand-wrapper">
            <div class="brand-icon"><i class="fa-solid fa-shield-virus"></i></div>
            <div class="brand-text">
                <h2>VACCINATION</h2>
                <span>ADMIN PANEL</span>
            </div>
        </a>
    </div>

    <div class="sidebar-profile">
        <div class="profile-avatar"><i class="fa-solid fa-user-shield"></i></div>
        <div class="profile-info">
            <h4><?= e($adminName) ?></h4>
            <span class="profile-status">Online</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <a href="dashboard.php"><i class="fa-solid fa-chart-pie"></i><span>Dashboard</span></a>
        <a href="vaccines.php"><i class="fa-solid fa-syringe"></i><span>Vaccines</span></a>
        <a href="children.php"><i class="fa-solid fa-children"></i><span>Children</span></a>
        <a href="appointments.php"><i class="fa-regular fa-calendar-check"></i><span>Appointments</span></a>
        <a href="hospitals.php"><i class="fa-solid fa-hospital-user"></i><span>Manage Hospitals</span></a>
        <a href="users.php"><i class="fa-solid fa-user-gear"></i><span>User Management</span></a>
        <a href="reports.php"><i class="fa-solid fa-file-waveform"></i><span>Reports</span></a>
    </nav>
</aside>

<div class="main-content">
    <header class="top-header">
        <div class="header-left">
            <button class="menu-toggle-btn" id="sidebarToggle" title="Toggle Sidebar">
                <i class="fa-solid fa-bars"></i>
            </button>
            <div>
                <h1 style="font-size: 20px; font-weight: 700; color: var(--dark);">Admin Profile</h1>
                <p style="font-size: 12px; color: var(--text-muted);">Manage your account credentials</p>
            </div>
        </div>

        <div class="header-right">
            <?php include __DIR__ . '/../components/dashboard_user_dropdown.php'; ?>
        </div>
    </header>

    <main class="page-container">
        <?php if ($msg): ?>
            <div class="alert-box alert-success"><i class="fa-solid fa-circle-check"></i> <?= e($msg) ?></div>
        <?php endif; ?>
        <?php if ($err): ?>
            <div class="alert-box alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= e($err) ?></div>
        <?php endif; ?>

        <div class="profile-card">
            <div class="profile-header-meta">
                <div class="profile-header-avatar"><i class="fa-solid fa-user-shield"></i></div>
                <div>
                    <h2 style="font-size: 20px; font-weight: 700;"><?= e($adminName) ?></h2>
                    <p style="color: var(--text-muted); font-size: 13px;">System Administrator</p>
                </div>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="action" value="update_profile">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" class="form-control" value="<?= e($adminName) ?>" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control" value="<?= e($adminEmail) ?>" required>
                </div>
                <div class="form-group">
                    <label>New Password (leave empty to keep current)</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••">
                </div>
                <button type="submit" class="btn-submit"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
            </form>
        </div>
    </main>
</div>

<script>
const sidebar = document.getElementById('sidebar');
const toggleBtn = document.getElementById('sidebarToggle');
if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', function() {
        sidebar.classList.toggle('collapsed');
    });
}
</script>
</body>
</html>
