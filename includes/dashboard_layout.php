<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../config.php';

function dashboard_require_role($role)
{
    if (empty($_SESSION['role']) || $_SESSION['role'] !== $role || empty($_SESSION['user_id'])) {
        header('Location: ../login.php');
        exit;
    }

    if (empty($_SESSION['dashboard_csrf'])) {
        $_SESSION['dashboard_csrf'] = bin2hex(random_bytes(32));
    }
}

function dashboard_e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function dashboard_csrf_token()
{
    return dashboard_e($_SESSION['dashboard_csrf'] ?? '');
}

function dashboard_valid_csrf()
{
    return isset($_POST['csrf_token'], $_SESSION['dashboard_csrf'])
        && hash_equals($_SESSION['dashboard_csrf'], $_POST['csrf_token']);
}

function dashboard_notice($message = '', $error = '')
{
    if ($message !== '') {
        echo '<div class="notice success">' . dashboard_e($message) . '</div>';
    }
    if ($error !== '') {
        echo '<div class="notice error">' . dashboard_e($error) . '</div>';
    }
}

function dashboard_start($title)
{
    $role = $_SESSION['role'];
    $currentPage = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $links = $role === 'admin'
        ? [
            'Dashboard' => 'dashboard.php',
            'Parents' => 'parents.php',
            'Hospitals' => 'hospitals.php',
            'Vaccines' => 'vaccines.php',
            'Appointments' => 'appointments.php',
            'Records' => 'records.php',
            'Reports' => 'reports.php',
        ]
        : [
            'Dashboard' => 'dashboard.php',
            'Patients' => 'patients.php',
            'Appointments' => 'appointments.php',
            'Records' => 'records.php',
            'Profile' => 'profile.php',
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
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= dashboard_e($title) ?> | Vaccination Management System</title>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <style>
            *{box-sizing:border-box}
            body{margin:0;background:#f0f7f8;color:#536477;font:15px 'Poppins',sans-serif}
            header{background:#fff;padding:16px 5%;display:flex;justify-content:space-between;align-items:center;gap:18px;box-shadow:0 3px 15px #102b4610}
            .brand{display:flex;align-items:center;color:#087e8b;font-size:23px;font-weight:bold;text-decoration:none}
            .brand i{margin-right:8px}
            nav{display:flex;flex-wrap:wrap;gap:8px}
            nav a,.button{display:inline-block;background:#087e8b;color:#fff;padding:10px 13px;border:0;border-radius:7px;text-decoration:none;font:inherit;cursor:pointer}
            nav a{background:#eaf8f8;color:#087e8b}
            nav a.active{background:#087e8b;color:#fff}
            main{max-width:1180px;margin:32px auto;padding:0 20px}
            h1,h2{color:#102b46}
            .panel,.card{background:#fff;padding:22px;border-radius:12px;box-shadow:0 5px 20px #102b4610;margin:18px 0}
            .table-wrap{overflow-x:auto}
            table{width:100%;border-collapse:collapse;background:#fff}
            th,td{text-align:left;padding:12px;border-bottom:1px solid #e3eeee;vertical-align:top}
            th{color:#102b46;background:#f5fbfb}
            form.inline{display:inline-flex;gap:8px;align-items:center}
            label{display:block;font-weight:bold;margin:12px 0 6px}
            input,select,textarea{width:100%;max-width:560px;padding:10px;border:1px solid #cbdada;border-radius:6px;font:inherit}
            textarea{min-height:90px}
            .form-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px}
            .form-grid input,.form-grid select,.form-grid textarea{max-width:none}
            .notice{padding:12px 15px;margin:14px 0;border-radius:7px}
            .success{background:#e6f8f0;color:#0b6b43}
            .error{background:#fdeeee;color:#a12626}
            .cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px}
            .card{margin:0}
            .card strong{display:block;font-size:26px;color:#087e8b;margin-top:8px}
            footer{text-align:center;padding:24px;color:#647487}
            @media(max-width:760px){header{align-items:flex-start;flex-direction:column}nav{gap:6px}nav a{padding:8px}}
        </style>
    </head>
    <body>
    <header>
        <a class="brand" href="dashboard.php"><i class="fa-solid fa-shield-heart" aria-hidden="true"></i> Vaccination Management</a>
        <nav>
            <?php foreach ($links as $label => $href): ?>
                <a href="<?= dashboard_e($href) ?>" class="<?= $currentPage === $href ? 'active' : '' ?>"<?= $currentPage === $href ? ' aria-current="page"' : '' ?>><?= dashboard_e($label) ?></a>
            <?php endforeach; ?>
            <a href="../logout.php">Logout</a>
        </nav>
    </header>
    <main>
        <h1><?= dashboard_e($title) ?></h1>
    <?php
}

function dashboard_end()
{
    ?>
    </main>
    <footer>&copy; <?= date('Y') ?> Vaccination Management System</footer>
    </body>
    </html>
    <?php
}
