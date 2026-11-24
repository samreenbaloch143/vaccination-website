<?php
session_start();
$pageTitle = "Register";

// Agar page khali (blank) aaye to neeche ki 2 lines uncomment karke error dekhein:
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

$success = false;
$errors  = [];

$roleParam = $_GET['role'] ?? 'parent';
if (!in_array($roleParam, ['parent', 'hospital'], true)) {
    $roleParam = 'parent';
}

$old = [
    'role' => $roleParam,
    'name' => '', 'email' => '', 'phone' => '', 'city' => '',
    'hospital_name' => '', 'address' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    foreach ($old as $key => $v) {
        $old[$key] = trim($_POST[$key] ?? '');
    }
    if (!in_array($old['role'], ['parent', 'hospital'], true)) { $old['role'] = 'parent'; }

    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (strlen($old['name']) < 3) {
        $errors[] = "Please enter your full name.";
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }
    if (!preg_match('/^[0-9+\-\s]{10,16}$/', $old['phone'])) {
        $errors[] = "Please enter a valid phone number.";
    }
    if ($old['city'] === '') {
        $errors[] = "Please enter your city.";
    }
    if ($old['role'] === 'hospital') {
        if (strlen($old['hospital_name']) < 3) { $errors[] = "Please enter the hospital name."; }
        if (strlen($old['address']) < 5)       { $errors[] = "Please enter the hospital address."; }
    }
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters.";
    }
    if ($password !== $confirm) {
        $errors[] = "Passwords do not match.";
    }
    if (empty($_POST['terms'])) {
        $errors[] = "Please accept the terms and conditions.";
    }

    if (empty($errors)) {

        $hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            require_once __DIR__ . "/db.php";

            if ($old['role'] === 'parent') {
                $table = 'parents';
            } else {
                $table = 'hospitals';
            }

            $check = $conn->prepare("SELECT id FROM $table WHERE email = ? LIMIT 1");
            $check->bind_param("s", $old['email']);
            $check->execute();

            if ($check->get_result()->num_rows > 0) {
                $errors[] = "This email is already registered.";
            } else {
                if ($old['role'] === 'parent') {
                    $stmt = $conn->prepare("INSERT INTO parents (name,email,phone,city,password) VALUES (?,?,?,?,?)");
                    $stmt->bind_param("sssss", $old['name'], $old['email'], $old['phone'], $old['city'], $hash);
                } else {
                    $stmt = $conn->prepare("INSERT INTO hospitals (admin_name,hospital_name,email,phone,city,address,password,status) VALUES (?,?,?,?,?,?,?,'pending')");
                    $stmt->bind_param("sssssss", $old['name'], $old['hospital_name'], $old['email'], $old['phone'], $old['city'], $old['address'], $hash);
                }
                $stmt->execute();
                $success = true;
                $old = ['role' => $old['role'], 'name' => '', 'email' => '', 'phone' => '', 'city' => '', 'hospital_name' => '', 'address' => ''];
                header("Location: login.php?registered=1");
                exit;
            }
        } catch (Throwable $ex) {
            $errors[] = "Database error: " . $ex->getMessage();
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

/* REGISTER LAYOUT */
.register-section {
    min-height:calc(100vh - 160px);
    padding:60px 7%;
    background:
    linear-gradient(90deg,rgba(7,72,88,.96),rgba(7,107,121,.80)),
    url('https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=1600') center/cover;
    display:flex;
    align-items:center;
    justify-content:center;
}

.register-wrap {
    width:100%;
    max-width:1020px;
    display:grid;
    grid-template-columns:.85fr 1.15fr;
    background:white;
    border-radius:24px;
    overflow:hidden;
    box-shadow:0 25px 60px rgba(0,0,0,.25);
}

/* LEFT PANEL */
.register-info { padding:50px 38px; color:white; background:linear-gradient(150deg,#087e8b,#13a6a6); display:flex; flex-direction:column; justify-content:center; }
.hero-tag { align-self:flex-start; padding:8px 16px; background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.3); border-radius:30px; font-size:11px; letter-spacing:1px; margin-bottom:22px; }
.register-info h2 { font-family:'Playfair Display',serif; font-size:32px; line-height:1.25; margin-bottom:16px; }
.register-info > p { color:#e2ffff; font-size:13px; line-height:1.9; margin-bottom:28px; }
.register-info ul { list-style:none; display:flex; flex-direction:column; gap:14px; }
.register-info li { display:flex; align-items:center; gap:12px; font-size:13px; }
.register-info li i { width:34px; height:34px; border-radius:10px; background:rgba(255,255,255,.18); display:grid; place-items:center; font-size:14px; flex-shrink:0; }

/* RIGHT PANEL */
.register-form { padding:42px 40px; }
.register-form h1 { font-family:'Playfair Display',serif; font-size:29px; margin-bottom:6px; }
.register-form > p { color:var(--text); font-size:12px; margin-bottom:22px; }

.role-tabs {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 14px;
    margin-bottom: 22px;
}
.role-tabs input { display: none; }
.role-tabs label {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 14px 8px;
    border: 1px solid #dbe6e6;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 500;
    color: var(--text);
    cursor: pointer;
    background: #ffffff;
    transition: all 0.25s ease;
    gap: 6px;
}
.role-tabs label i {
    font-size: 20px;
    color: #475569;
    transition: all 0.25s ease;
}
.role-tabs label:hover {
    border-color: var(--primary);
    background: #f7fdfd;
}
.role-tabs input:checked + label {
    background: var(--light);
    border-color: var(--primary);
    color: var(--primary);
    font-weight: 600;
}
.role-tabs input:checked + label i {
    color: var(--primary);
}
.role-tabs input:focus-visible + label {
    box-shadow: 0 0 0 4px rgba(8, 126, 139, 0.15);
}

.form-row { display:grid; grid-template-columns:1fr 1fr; gap:15px; }
.form-group { margin-bottom:15px; }
.form-group label { display:block; font-size:12px; font-weight:600; margin-bottom:7px; }
.form-group input,
.form-group textarea {
    width:100%; padding:12px 14px; border:1px solid #dbe6e6; border-radius:10px;
    font-family:inherit; font-size:13px; color:var(--dark); outline:none; background:#fbfefe; transition:.3s;
}
.form-group input:focus,
.form-group textarea:focus { border-color:var(--primary); box-shadow:0 0 0 4px rgba(8,126,139,.1); }
.form-group textarea { resize:vertical; min-height:80px; }

.hospital-fields { display:none; }
.hospital-fields.show { display:block; }

.pass-wrap { position:relative; }
.pass-wrap input { padding-right:44px; }
.toggle-pass { position:absolute; right:6px; top:50%; transform:translateY(-50%); background:none; border:none; color:var(--text); cursor:pointer; padding:9px 11px; font-size:14px; }

.terms { display:flex; align-items:flex-start; gap:9px; font-size:12px; color:var(--text); line-height:1.7; margin:4px 0 20px; cursor:pointer; }
.terms input { accent-color:var(--primary); margin-top:4px; }
.terms a { color:var(--primary); font-weight:600; }

.submit-btn { width:100%; background:var(--primary); color:white; border:none; padding:15px; border-radius:30px; font-family:inherit; font-size:14px; font-weight:600; cursor:pointer; transition:.3s; }
.submit-btn:hover { background:var(--dark); transform:translateY(-3px); }

.alert { padding:14px 17px; border-radius:10px; font-size:12px; line-height:1.7; margin-bottom:20px; }
.alert i { margin-right:7px; }
.alert-success { background:#e6f8f0; color:#0b6b43; border:1px solid #b6e8d1; }
.alert-error { background:#fdeeee; color:#a12626; border:1px solid #f4c4c4; }
.alert-error ul { margin:6px 0 0 20px; }

.form-bottom { text-align:center; font-size:12px; color:var(--text); margin-top:20px; }
.form-bottom a { color:var(--primary); font-weight:600; }

/* FOOTER (slim) */
.footer { background:#092d42; color:white; padding:22px 7%; }
.footer-bottom { display:flex; justify-content:space-between; gap:15px; font-size:12px; color:#c3d4df; }

/* RESPONSIVE */
@media(max-width:1050px) { .nav-links { gap:13px; } }

@media(max-width:900px) {
    .register-wrap { grid-template-columns:1fr; max-width:580px; }
    .register-info { display:none; }
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
    .register-section { padding:35px 5%; }
    .register-form { padding:30px 20px; }
    .register-form h1 { font-size:25px; }
    .form-row { grid-template-columns:1fr; gap:0; }
    .footer-bottom { flex-direction:column; }
}
</style>
</head>

<body>

<!-- HEADER & NAVBAR -->
<?php include 'components/header.php'; ?>

<!-- REGISTER -->
<section class="register-section">

    <div class="register-wrap">

        <!-- LEFT -->
        <div class="register-info">
            <span class="hero-tag"><i class="fa-solid fa-user-plus"></i> JOIN US</span>

            <h2>Create Your Free Account</h2>

            <p>
                Register as a parent to book vaccinations for your child,
                or as a hospital to manage vaccine availability and appointments.
            </p>

            <ul>
                <li><i class="fa-solid fa-baby"></i> Add and manage child profiles</li>
                <li><i class="fa-solid fa-calendar-check"></i> Request vaccination appointments</li>
                <li><i class="fa-solid fa-hospital"></i> Hospitals manage vaccine stock</li>
            </ul>
        </div>

        <!-- RIGHT -->
        <div class="register-form">

            <h1>Register</h1>
            <p>Choose your account type and fill in your details.</p>

            <?php if ($success): ?>
                <div class="alert alert-success" role="status">
                    <i class="fa-solid fa-circle-check"></i>
                    Account created successfully!
                    <?php echo $old['role'] === 'hospital' ? 'Your hospital will be reviewed before activation.' : ''; ?>
                    <a href="login.php" style="font-weight:600;text-decoration:underline;">Login now</a>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error" role="alert">
                    <i class="fa-solid fa-circle-exclamation"></i> Please fix the following:
                    <ul>
                        <?php foreach ($errors as $err): ?>
                            <li><?php echo e($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php" novalidate>

                <div class="role-tabs">
                    <input type="radio" name="role" id="role-parent" value="parent" <?php echo $old['role'] === 'parent' ? 'checked' : ''; ?>>
                    <label for="role-parent"><i class="fa-solid fa-people-roof"></i>Parent</label>

                    <input type="radio" name="role" id="role-hospital" value="hospital" <?php echo $old['role'] === 'hospital' ? 'checked' : ''; ?>>
                    <label for="role-hospital"><i class="fa-solid fa-hospital"></i>Hospital</label>
                </div>

                <!-- Sirf Hospital ke liye -->
                <div class="hospital-fields <?php echo $old['role'] === 'hospital' ? 'show' : ''; ?>" id="hospitalFields">
                    <div class="form-group">
                        <label for="hospital_name">Hospital Name *</label>
                        <input type="text" id="hospital_name" name="hospital_name" placeholder="e.g. City Care Hospital" value="<?php echo e($old['hospital_name']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="address">Hospital Address *</label>
                        <textarea id="address" name="address" placeholder="Street, area, city"><?php echo e($old['address']); ?></textarea>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="name"><span id="nameLabel">Full Name</span> *</label>
                        <input type="text" id="name" name="name" placeholder="Your name" value="<?php echo e($old['name']); ?>" autocomplete="name" required>
                    </div>
                    <div class="form-group">
                        <label for="city">City *</label>
                        <input type="text" id="city" name="city" placeholder="e.g. Karachi" value="<?php echo e($old['city']); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" placeholder="you@example.com" value="<?php echo e($old['email']); ?>" autocomplete="email" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number *</label>
                        <input type="tel" id="phone" name="phone" placeholder="+92 3XX XXXXXXX" value="<?php echo e($old['phone']); ?>" autocomplete="tel" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <div class="pass-wrap">
                            <input type="password" id="password" name="password" placeholder="At least 8 characters" autocomplete="new-password" required>
                            <button type="button" class="toggle-pass" id="togglePass" aria-label="Show password"><i class="fa-solid fa-eye"></i></button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat password" autocomplete="new-password" required>
                    </div>
                </div>

                <label class="terms">
                    <input type="checkbox" name="terms">
                    <span>I agree to the <a href="#">Terms &amp; Conditions</a> and <a href="#">Privacy Policy</a>.</span>
                </label>

                <button type="submit" class="submit-btn">
                    <i class="fa-solid fa-user-plus"></i> Create Account
                </button>

            </form>

            <div class="form-bottom">
                Already have an account? <a href="login.php">Login here</a>
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

// PARENT / HOSPITAL TOGGLE
const hospitalFields = document.getElementById("hospitalFields");
const nameLabel = document.getElementById("nameLabel");

function updateRole() {
    const isHospital = document.getElementById("role-hospital") && document.getElementById("role-hospital").checked;
    hospitalFields.classList.toggle("show", isHospital);
    if (isHospital) {
        nameLabel.textContent = "Administrator Name";
    } else {
        nameLabel.textContent = "Full Name";
    }
}

document.querySelectorAll('input[name="role"]').forEach(function(r) {
    r.addEventListener("change", updateRole);
});
updateRole();

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