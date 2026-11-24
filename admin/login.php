<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config.php';

// If already logged in as admin, go straight to dashboard
if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: dashboard.php");
    exit;
}

$error = "";
$email = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid administrator email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        try {
            $stmt = $conn->prepare("SELECT id, name, email, password FROM admins WHERE email = ? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $admin = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$admin || !password_verify($password, $admin['password'])) {
                $error = "Invalid administrator credentials. Access denied.";
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $admin['id'];
                $_SESSION['name']    = $admin['name'];
                $_SESSION['role']    = 'admin';
                header("Location: dashboard.php");
                exit;
            }
        } catch (Throwable $ex) {
            $error = "Database authentication error. Please try again.";
        }
    }
}

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
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
<title>Admin Portal Login | Vaccination Management System</title>

<!-- Fonts & Font Awesome -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>
:root {
    --primary: #087e8b;
    --primary-hover: #06646f;
    --secondary: #13a6a6;
    --dark: #102b46;
    --navy-deep: #09253b;
    --light: #eaf8f8;
    --text: #647487;
    --white: #ffffff;
    --danger: #dc2626;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Poppins', sans-serif;
    min-height: 100vh;
    background: linear-gradient(135deg, #072e42 0%, #0d3852 50%, #064e5b 100%);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 24px 16px;
    position: relative;
    overflow-x: hidden;
}

/* Subtle background glowing rings */
body::before, body::after {
    content: '';
    position: absolute;
    border-radius: 50%;
    pointer-events: none;
    filter: blur(80px);
    opacity: 0.22;
}

body::before {
    width: 360px;
    height: 360px;
    background: #13a6a6;
    top: -80px;
    left: -80px;
}

body::after {
    width: 420px;
    height: 420px;
    background: #087e8b;
    bottom: -100px;
    right: -100px;
}

.admin-card {
    background: #ffffff;
    width: 100%;
    max-width: 460px;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.35);
    padding: 44px 38px 40px;
    position: relative;
    z-index: 10;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.brand-header {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    margin-bottom: 28px;
}

.brand-icon-box {
    width: 62px;
    height: 62px;
    border: 3px solid var(--primary);
    background: var(--light);
    color: var(--primary);
    border-radius: 18px;
    display: grid;
    place-items: center;
    font-size: 30px;
    margin-bottom: 14px;
    box-shadow: 0 8px 20px rgba(8, 126, 139, 0.18);
}

.portal-tag {
    display: inline-block;
    padding: 4px 12px;
    background: #eef8f8;
    color: var(--primary);
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1.2px;
    text-transform: uppercase;
    margin-bottom: 8px;
}

.brand-header h1 {
    font-family: 'Playfair Display', serif;
    font-size: 24px;
    color: var(--dark);
    font-weight: 700;
    margin-bottom: 4px;
}

.brand-header p {
    color: var(--text);
    font-size: 12.5px;
    line-height: 1.6;
}

.alert-error {
    background: #fdeeee;
    color: #a12626;
    border: 1px solid #f8caca;
    padding: 12px 16px;
    border-radius: 10px;
    font-size: 12px;
    line-height: 1.6;
    margin-bottom: 22px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-group {
    margin-bottom: 18px;
}

.form-group label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 7px;
}

.input-wrap {
    position: relative;
}

.input-wrap > i.field-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--primary);
    font-size: 14px;
}

.input-wrap input {
    width: 100%;
    padding: 13px 44px 13px 42px;
    border: 1px solid #d8e5e7;
    border-radius: 10px;
    font-family: inherit;
    font-size: 13.5px;
    color: var(--dark);
    outline: none;
    background: #fbfdfd;
    transition: all 0.25s ease;
}

.input-wrap input:focus {
    border-color: var(--primary);
    background: #ffffff;
    box-shadow: 0 0 0 4px rgba(8, 126, 139, 0.12);
}

.toggle-pass {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--text);
    cursor: pointer;
    padding: 8px 10px;
    font-size: 14px;
}

.toggle-pass:hover {
    color: var(--dark);
}

.btn-submit {
    width: 100%;
    background: var(--primary);
    color: #ffffff;
    border: none;
    padding: 14px 20px;
    border-radius: 12px;
    font-family: inherit;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.25s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    margin-top: 10px;
    box-shadow: 0 6px 18px rgba(8, 126, 139, 0.25);
}

.btn-submit:hover {
    background: var(--dark);
    transform: translateY(-2px);
    box-shadow: 0 10px 22px rgba(16, 43, 70, 0.3);
}

.bottom-meta {
    text-align: center;
    margin-top: 24px;
    padding-top: 18px;
    border-top: 1px solid #edf3f5;
}

.bottom-meta a {
    color: var(--primary);
    text-decoration: none;
    font-size: 12.5px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: color 0.2s;
}

.bottom-meta a:hover {
    color: var(--dark);
    text-decoration: underline;
}

.footer-copy {
    margin-top: 22px;
    color: #a4c2d4;
    font-size: 11.5px;
    text-align: center;
}
</style>
</head>
<body>

<div class="admin-card">

    <div class="brand-header">
        <div class="brand-icon-box">
            <i class="fa-solid fa-shield-heart"></i>
        </div>
        <span class="portal-tag"><i class="fa-solid fa-user-shield"></i> Secure Admin Access</span>
        <h1>Admin Portal Login</h1>
        <p>Please enter your administrative credentials to manage the vaccination system.</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert-error" role="alert">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php" novalidate>
        <div class="form-group">
            <label for="email">Admin Email Address</label>
            <div class="input-wrap">
                <i class="fa-solid fa-envelope field-icon"></i>
                <input type="email" id="email" name="email" value="<?= e($email ?: 'admin@vaccination.com') ?>" placeholder="admin@vaccination.com" autocomplete="email" required autofocus>
            </div>
        </div>

        <div class="form-group">
            <label for="password">Admin Password</label>
            <div class="input-wrap">
                <i class="fa-solid fa-lock field-icon"></i>
                <input type="password" id="password" name="password" placeholder="Enter administrative password" autocomplete="current-password" required>
                <button type="button" class="toggle-pass" id="togglePass" aria-label="Toggle password visibility">
                    <i class="fa-solid fa-eye"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn-submit">
            <i class="fa-solid fa-right-to-bracket"></i> Login to Dashboard
        </button>
    </form>

    <div class="bottom-meta">
        <a href="../index.php">
            <i class="fa-solid fa-arrow-left"></i> Return to Main Website
        </a>
    </div>

</div>

<p class="footer-copy">
    &copy; <?= date('Y') ?> Vaccination Management System &bull; Admin Portal
</p>

<script>
const togglePass = document.getElementById("togglePass");
const passField  = document.getElementById("password");

if (togglePass && passField) {
    togglePass.addEventListener("click", function () {
        const type = passField.getAttribute("type") === "password" ? "text" : "password";
        passField.setAttribute("type", type);
        const icon = this.querySelector("i");
        icon.classList.toggle("fa-eye");
        icon.classList.toggle("fa-eye-slash");
    });
}
</script>

</body>
</html>
