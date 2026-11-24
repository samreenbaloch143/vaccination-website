<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
$isLoggedIn   = !empty($_SESSION['role']);
$userRole     = $_SESSION['role'] ?? '';
$userName     = $_SESSION['name'] ?? '';

// Dashboard destination link
$dashLink = 'parent/dashboard.php';
if ($userRole === 'hospital') {
    $dashLink = 'hospital/dashboard.php';
} elseif ($userRole === 'admin') {
    $dashLink = 'admin/dashboard.php';
}
?>
<!-- FONTS & ICONS (Guaranteed for all pages) -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<!-- HEADER & TOPBAR STYLES -->
<style>
.topbar {
    background: #076b79;
    color: #ffffff;
    padding: 10px 7%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
    font-family: 'Poppins', sans-serif;
    position: relative;
    z-index: 1001;
}

.top-info {
    display: flex;
    gap: 25px;
    align-items: center;
}

.top-info span {
    display: inline-flex;
    align-items: center;
}

.top-info i {
    margin-right: 7px;
}

.topbar a {
    color: #ffffff;
    text-decoration: none;
    transition: color 0.3s;
}

.topbar a:hover {
    color: #b8ffff;
}

.navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 17px 7%;
    background: #ffffff;
    position: sticky;
    top: 0;
    z-index: 1000;
    box-shadow: 0 3px 15px rgba(0,0,0,0.06);
    font-family: 'Poppins', sans-serif;
}

.navbar .logo {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
}

.navbar .logo-icon {
    width: 53px;
    height: 53px;
    border: 3px solid #087e8b;
    color: #087e8b;
    border-radius: 16px;
    display: grid;
    place-items: center;
    font-size: 26px;
    flex-shrink: 0;
}

.navbar .logo-text {
    font-family: 'Playfair Display', serif;
    font-size: 20px;
    line-height: 1.15;
    font-weight: 700;
    color: #102b46;
}

.navbar .nav-links {
    display: flex;
    align-items: center;
    gap: 29px;
}

.navbar .nav-links a {
    font-size: 14px;
    font-weight: 500;
    color: #26364a;
    text-decoration: none;
    transition: .3s;
}

.navbar .nav-links a:hover,
.navbar .nav-links a.active {
    color: #087e8b;
}

.navbar .login-btn {
    background: #087e8b;
    color: #ffffff !important;
    padding: 11px 22px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    text-decoration: none;
    transition: .3s;
}

.navbar .login-btn:hover {
    background: #066570;
}

.navbar .logout-link {
    color: #ef4444 !important;
    font-weight: 600;
    font-size: 13.5px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
    text-decoration: none;
}
.navbar .logout-link:hover {
    color: #dc2626 !important;
    transform: translateY(-1px);
}

.navbar .menu-toggle {
    display: none;
    border: none;
    background: none;
    font-size: 24px;
    color: #102b46;
    cursor: pointer;
}

@media(max-width: 768px) {
    .topbar {
        padding: 10px 5%;
        font-size: 11px;
    }

    .top-info {
        gap: 10px;
        flex-wrap: wrap;
    }

    .navbar {
        padding: 15px 5%;
    }

    .navbar .menu-toggle {
        display: block;
    }

    .navbar .nav-links {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        width: 100%;
        background: #ffffff;
        padding: 20px 7%;
        flex-direction: column;
        align-items: flex-start;
        box-shadow: 0 8px 15px rgba(0,0,0,.08);
        gap: 16px;
    }

    .navbar .nav-links.show {
        display: flex;
    }
}
</style>

<!-- TOP BAR -->
<div class="topbar">
    <div class="top-info">
        <span><i class="fa-solid fa-phone"></i> +92 321 1234567</span>
        <span><i class="fa-solid fa-envelope"></i> info@vaccination.com</span>
    </div>

    <div>
        <?php if ($isLoggedIn): ?>
            <span><i class="fa-solid fa-circle-user"></i> Welcome, <?= htmlspecialchars($userName ?: 'User') ?></span> |
            <a href="<?= $dashLink ?>"><i class="fa-solid fa-gauge-high"></i> Dashboard</a> |
            <a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a> |
            <a href="register.php">Register</a>
        <?php endif; ?>
    </div>
</div>

<!-- NAVBAR -->
<nav class="navbar">

    <a href="index.php" class="logo">
        <div class="logo-icon">
            <i class="fa-solid fa-shield-heart"></i>
        </div>

        <div class="logo-text">
            Vaccination<br>Management System
        </div>
    </a>

    <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">
        <i class="fa-solid fa-bars"></i>
    </button>

    <div class="nav-links" id="navLinks">
        <a href="index.php" class="<?= ($current_page == 'index.php') ? 'active' : '' ?>">Home</a>
        <a href="about.php" class="<?= ($current_page == 'about.php') ? 'active' : '' ?>">About Us</a>
        <a href="services.php" class="<?= ($current_page == 'services.php') ? 'active' : '' ?>">Services</a>
        <a href="hospital.php" class="<?= ($current_page == 'hospital.php') ? 'active' : '' ?>">Hospitals</a>
        <a href="vaccine.php" class="<?= ($current_page == 'vaccine.php') ? 'active' : '' ?>">Vaccines</a>
        <a href="contact.php" class="<?= ($current_page == 'contact.php') ? 'active' : '' ?>">Contact Us</a>
        <?php if ($isLoggedIn): ?>
            <a href="<?= $dashLink ?>" class="login-btn">
                <i class="fa-solid fa-gauge-high"></i> Dashboard
            </a>
            <a href="logout.php" class="logout-link" title="Logout">
                <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
            </a>
        <?php else: ?>
            <a href="login.php" class="login-btn">
                <i class="fa-solid fa-user"></i> Login
            </a>
        <?php endif; ?>
    </div>

</nav>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const menuToggle = document.getElementById("menuToggle");
    const navLinks = document.getElementById("navLinks");
    if (menuToggle && navLinks) {
        menuToggle.addEventListener("click", function () {
            navLinks.classList.toggle("show");
            const icon = menuToggle.querySelector("i");
            if (icon) {
                if (navLinks.classList.contains("show")) {
                    icon.classList.remove("fa-bars");
                    icon.classList.add("fa-xmark");
                } else {
                    icon.classList.remove("fa-xmark");
                    icon.classList.add("fa-bars");
                }
            }
        });
    }
});
</script>
