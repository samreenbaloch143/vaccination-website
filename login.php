<?php
session_start();
$pageTitle = "Login";

// Check optional redirect parameter (e.g. from "Book Now")
$rawRedirect = trim($_GET['redirect'] ?? $_POST['redirect'] ?? '');
$redirectUrl = '';
if (!empty($rawRedirect) && !preg_match('#^https?://#i', $rawRedirect)) {
    $redirectUrl = $rawRedirect;
}

// Admin has its own dedicated portal login at admin/login.php
if ((isset($_GET['role']) && $_GET['role'] === 'admin') || (isset($_POST['role']) && $_POST['role'] === 'admin')) {
    header("Location: admin/login.php");
    exit;
}

// Pehle se login ho to: parent ko website ya requested page par rakho, baqi roles ko unke dashboard par
$dashboards = [
    'parent'   => !empty($redirectUrl) ? $redirectUrl : 'index.php',
    'hospital' => 'hospital/dashboard.php',
];
if (!empty($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
        exit;
    }
    if (isset($dashboards[$_SESSION['role']])) {
        header("Location: " . $dashboards[$_SESSION['role']]);
        exit;
    }
}

$error = "";
$email = "";
$role  = $_POST['role'] ?? 'parent';
if ($role !== 'hospital') { $role = 'parent'; }
$registered = isset($_GET['registered']) && $_GET['registered'] == '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {

        try {
            require_once __DIR__ . "/db.php";

            if ($role === 'hospital') {
                $sql = "SELECT id, admin_name AS name, password, status FROM hospitals WHERE email = ? LIMIT 1";
            } else {
                $sql = "SELECT id, name, password, 'approved' AS status FROM parents WHERE email = ? LIMIT 1";
            }

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            if (!$user || !password_verify($password, $user['password'])) {
                $error = "Invalid email or password.";
            } elseif ($user['status'] !== 'approved') {
                $error = "Your hospital account is waiting for admin approval.";
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name']    = $user['name'];
                $_SESSION['role']    = $role;
                if ($role === 'parent') {
                    $targetDest = !empty($redirectUrl) ? $redirectUrl : "index.php";
                    header("Location: " . $targetDest);
                } else {
                    header("Location: " . $dashboards[$role]);
                }
                exit;
            }
        } catch (Throwable $ex) {
            $error = "Database error: " . $ex->getMessage();
        }
    }
}

function e($v) { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
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
<title><?php echo $pageTitle; ?> | Vaccination Management System</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>
:root {
    --primary:#087e8b;
    --secondary:#13a6a6;
    --dark:#102b46;
    --light:#eaf8f8;
    --text:#647487;
    --white:#fff;
}

* { margin:0; padding:0; box-sizing:border-box; scroll-behavior:smooth; }
body { font-family:'Poppins',sans-serif; color:var(--dark); }
a { text-decoration:none; color:inherit; }

/* TOPBAR */
.topbar { background:#076b79; color:white; padding:10px 7%; display:flex; justify-content:space-between; font-size:13px; }
.top-info { display:flex; gap:25px; }
.topbar i { margin-right:7px; }

/* NAVBAR */
.navbar { display:flex; justify-content:space-between; align-items:center; padding:17px 7%; background:white; position:sticky; top:0; z-index:1000; box-shadow:0 3px 15px rgba(0,0,0,.06); }
.logo { display:flex; align-items:center; gap:12px; }
.logo-icon { width:53px; height:53px; border:3px solid var(--primary); color:var(--primary); border-radius:15px; display:grid; place-items:center; font-size:25px; }
.logo-text { font-family:'Playfair Display',serif; font-size:19px; font-weight:700; line-height:1.2; }
.nav-links { display:flex; align-items:center; gap:25px; }
.nav-links a { font-size:14px; font-weight:500; transition:.3s; }
.nav-links a:hover, .nav-links a.active { color:var(--primary); }
.login-btn { background:var(--primary); color:white!important; padding:12px 23px; border-radius:30px; }
.nav-links a.login-btn:hover { color:white; background:var(--dark); }
.menu-toggle { display:none; background:none; border:none; font-size:25px; color:var(--primary); cursor:pointer; }

/* LOGIN LAYOUT */
.login-section {
    min-height:calc(100vh - 160px);
    padding:60px 7%;
    background:
    linear-gradient(90deg,rgba(7,72,88,.96),rgba(7,107,121,.80)),
    url('https://images.unsplash.com/photo-1586773860418-d37222d8fce3?w=1600') center/cover;
    display:flex;
    align-items:center;
    justify-content:center;
}

.login-wrap {
    width:100%;
    max-width:980px;
    display:grid;
    grid-template-columns:1fr 1fr;
    background:white;
    border-radius:24px;
    overflow:hidden;
    box-shadow:0 25px 60px rgba(0,0,0,.25);
}

/* LEFT PANEL */
.login-info {
    padding:50px 42px;
    color:white;
    background:linear-gradient(150deg,#087e8b,#13a6a6);
    display:flex;
    flex-direction:column;
    justify-content:center;
}
.hero-tag { align-self:flex-start; padding:8px 16px; background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.3); border-radius:30px; font-size:11px; letter-spacing:1px; margin-bottom:22px; }
.login-info h2 { font-family:'Playfair Display',serif; font-size:34px; line-height:1.25; margin-bottom:16px; }
.login-info > p { color:#e2ffff; font-size:13px; line-height:1.9; margin-bottom:28px; }
.login-info ul { list-style:none; display:flex; flex-direction:column; gap:14px; }
.login-info li { display:flex; align-items:center; gap:12px; font-size:13px; }
.login-info li i { width:34px; height:34px; border-radius:10px; background:rgba(255,255,255,.18); display:grid; place-items:center; font-size:14px; flex-shrink:0; }

/* RIGHT PANEL */
.login-form { padding:46px 42px; }
.login-form h1 { font-family:'Playfair Display',serif; font-size:30px; margin-bottom:6px; }
.login-form > p { color:var(--text); font-size:12px; margin-bottom:24px; }

.role-tabs { display:grid; grid-template-columns:repeat(2,1fr); gap:14px; margin-bottom:22px; }
.role-tabs input { display:none; }
.role-tabs label {
    text-align:center; padding:11px 6px; border:1px solid #dbe6e6; border-radius:10px;
    font-size:12px; font-weight:500; color:var(--text); cursor:pointer; transition:.3s;
}
.role-tabs label i { display:block; font-size:17px; margin-bottom:5px; }
.role-tabs input:checked + label { background:var(--light); border-color:var(--primary); color:var(--primary); font-weight:600; }
.role-tabs input:focus-visible + label { box-shadow:0 0 0 4px rgba(8,126,139,.15); }

.form-group { margin-bottom:17px; }
.form-group label { display:block; font-size:12px; font-weight:600; margin-bottom:7px; }
.input-wrap { position:relative; }
.input-wrap > i.field-icon { position:absolute; left:15px; top:50%; transform:translateY(-50%); color:var(--primary); font-size:14px; }
.input-wrap input {
    width:100%; padding:13px 44px 13px 42px; border:1px solid #dbe6e6; border-radius:10px;
    font-family:inherit; font-size:13px; color:var(--dark); outline:none; background:#fbfefe; transition:.3s;
}
.input-wrap input:focus { border-color:var(--primary); box-shadow:0 0 0 4px rgba(8,126,139,.1); }
.toggle-pass { position:absolute; right:6px; top:50%; transform:translateY(-50%); background:none; border:none; color:var(--text); cursor:pointer; padding:9px 11px; font-size:14px; }

.form-options { display:flex; justify-content:space-between; align-items:center; font-size:12px; margin-bottom:22px; gap:10px; }
.remember { display:flex; align-items:center; gap:7px; color:var(--text); cursor:pointer; }
.remember input { accent-color:var(--primary); }
.form-options a { color:var(--primary); font-weight:600; }

.submit-btn {
    width:100%; background:var(--primary); color:white; border:none; padding:15px; border-radius:30px;
    font-family:inherit; font-size:14px; font-weight:600; cursor:pointer; transition:.3s;
}
.submit-btn:hover { background:var(--dark); transform:translateY(-3px); }

.alert-error { background:#fdeeee; color:#a12626; border:1px solid #f4c4c4; padding:13px 16px; border-radius:10px; font-size:12px; line-height:1.7; margin-bottom:20px; }
.alert-error i { margin-right:7px; }

.form-bottom { text-align:center; font-size:12px; color:var(--text); margin-top:22px; line-height:2; }
.form-bottom a { color:var(--primary); font-weight:600; }

/* FOOTER (slim) */
.footer { background:#092d42; color:white; padding:22px 7%; }
.footer-bottom { display:flex; justify-content:space-between; gap:15px; font-size:12px; color:#c3d4df; }

/* RESPONSIVE */
@media(max-width:1050px) { .nav-links { gap:13px; } }

@media(max-width:860px) {
    .login-wrap { grid-template-columns:1fr; max-width:520px; }
    .login-info { display:none; }
}

@media(max-width:768px) {
    .topbar { padding:10px 5%; }
    .navbar { padding:15px 5%; }
    .menu-toggle { display:block; }
    .nav-links { display:none; position:absolute; top:100%; left:0; width:100%; background:white; padding:22px 7%; flex-direction:column; align-items:flex-start; }
    .nav-links.show { display:flex; }
}

@media(max-width:500px) {
    .top-info span:nth-child(2) { display:none; }
    .logo-text { font-size:15px; }
    .logo-icon { width:43px; height:43px; }
    .login-section { padding:35px 5%; }
    .login-form { padding:32px 22px; }
    .login-form h1 { font-size:26px; }
    .footer-bottom { flex-direction:column; }
}
</style>
</head>

<body>

<!-- HEADER & NAVBAR -->
<?php include 'components/header.php'; ?>

<!-- LOGIN -->
<section class="login-section">

    <div class="login-wrap">

        <!-- LEFT -->
        <div class="login-info">
            <span class="hero-tag"><i class="fa-solid fa-lock"></i> SECURE ACCESS</span>

            <h2>Welcome Back To Your Health Portal</h2>

            <p>
                Log in to manage your child's vaccination records,
                appointments and hospital information in one place.
            </p>

            <ul>
                <li><i class="fa-solid fa-syringe"></i> Track vaccination schedules</li>
                <li><i class="fa-solid fa-calendar-check"></i> Manage appointment requests</li>
                <li><i class="fa-solid fa-hospital"></i> Find registered hospitals</li>
            </ul>
        </div>

        <!-- RIGHT -->
        <div class="login-form">

            <h1>Login</h1>
            <p>Select your account type and enter your details.</p>

            <?php if ($registered): ?>
                <div class="alert-success" style="background:#e6f8f0;color:#0b6b43;border:1px solid #b6e8d1;padding:13px 16px;border-radius:10px;font-size:12px;line-height:1.7;margin-bottom:20px;">
                    <i class="fa-solid fa-circle-check"></i> Account created successfully. Please login below.
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert-error" role="alert">
                    <i class="fa-solid fa-circle-exclamation"></i> <?php echo e($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php<?= !empty($redirectUrl) ? '?redirect=' . urlencode($redirectUrl) : '' ?>" novalidate>
                <?php if (!empty($redirectUrl)): ?>
                    <input type="hidden" name="redirect" value="<?php echo e($redirectUrl); ?>">
                <?php endif; ?>

                <div class="role-tabs">
                    <input type="radio" name="role" id="role-parent" value="parent" <?php echo $role === 'parent' ? 'checked' : ''; ?>>
                    <label for="role-parent"><i class="fa-solid fa-people-roof"></i>Parent</label>

                    <input type="radio" name="role" id="role-hospital" value="hospital" <?php echo $role === 'hospital' ? 'checked' : ''; ?>>
                    <label for="role-hospital"><i class="fa-solid fa-hospital"></i>Hospital</label>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-envelope field-icon"></i>
                        <input type="email" id="email" name="email" placeholder="you@example.com" value="<?php echo e($email); ?>" autocomplete="email" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-lock field-icon"></i>
                        <input type="password" id="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
                        <button type="button" class="toggle-pass" id="togglePass" aria-label="Show password">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-options">
                    <label class="remember"><input type="checkbox" name="remember"> Remember me</label>
                    <a href="forgot-password.php">Forgot password?</a>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fa-solid fa-right-to-bracket"></i> Login
                </button>

            </form>

            <div class="form-bottom">
                Don't have an account? <a href="register.php">Register as a parent</a><br>
                Hospital administrator? <a href="register.php?role=hospital">Register your hospital</a>
            </div>

        </div>

    </div>

</section>

<!-- FOOTER -->
<?php include 'components/footer.php'; ?>

<script>

// MOBILE NAVBAR
const menuToggle = document.getElementById("menuToggle");
const navLinks = document.getElementById("navLinks");

menuToggle.addEventListener("click", function() {
    navLinks.classList.toggle("show");
    const icon = menuToggle.querySelector("i");
    if (navLinks.classList.contains("show")) {
        icon.classList.replace("fa-bars", "fa-xmark");
    } else {
        icon.classList.replace("fa-xmark", "fa-bars");
    }
});

// SHOW / HIDE PASSWORD
const togglePass = document.getElementById("togglePass");
const passInput = document.getElementById("password");

togglePass.addEventListener("click", function() {
    const show = passInput.type === "password";
    passInput.type = show ? "text" : "password";
    togglePass.querySelector("i").className = show ? "fa-solid fa-eye-slash" : "fa-solid fa-eye";
    togglePass.setAttribute("aria-label", show ? "Hide password" : "Show password");
});

</script>

</body>
</html>