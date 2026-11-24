<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$siteName = "Vaccination Management System";
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

<title><?php echo $siteName; ?></title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>
:root {
    --primary: #087e8b;
    --secondary: #13a6a6;
    --dark: #102b46;
    --light: #eaf8f8;
    --white: #ffffff;
    --text: #536477;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    scroll-behavior: smooth;
}

body {
    font-family: 'Poppins', sans-serif;
    color: var(--dark);
    background: #ffffff;
    overflow-x: hidden;
}

a {
    text-decoration: none;
    color: inherit;
}

.topbar {
    background: #076b79;
    color: white;
    padding: 10px 7%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
}

.top-info {
    display: flex;
    gap: 25px;
}

.top-info i {
    margin-right: 7px;
}

.topbar a:hover {
    color: #b8ffff;
}

.navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 17px 7%;
    background: white;
    position: sticky;
    top: 0;
    z-index: 1000;
    box-shadow: 0 3px 15px rgba(0,0,0,0.06);
}

.logo {
    display: flex;
    align-items: center;
    gap: 12px;
}

.logo-icon {
    width: 53px;
    height: 53px;
    border: 3px solid var(--primary);
    color: var(--primary);
    border-radius: 16px;
    display: grid;
    place-items: center;
    font-size: 26px;
}

.logo-text {
    font-family: 'Playfair Display', serif;
    font-size: 20px;
    line-height: 1.15;
    font-weight: 700;
    color: var(--dark);
}

.nav-links {
    display: flex;
    align-items: center;
    gap: 29px;
}

.nav-links a {
    font-size: 14px;
    font-weight: 500;
    transition: .3s;
}

.nav-links a:hover,
.nav-links a.active {
    color: var(--primary);
}

.login-btn,
.primary-btn {
    background: var(--primary);
    color: white !important;
    padding: 12px 23px;
    border-radius: 30px;
    transition: .3s;
    display: inline-block;
}

.login-btn:hover,
.primary-btn:hover {
    background: var(--dark);
    transform: translateY(-3px);
}

.menu-toggle {
    display: none;
    border: none;
    background: none;
    font-size: 25px;
    color: var(--primary);
    cursor: pointer;
}

/* HERO SECTION */

.hero {
    min-height: 500px;
    display: flex;
    align-items: center;
    padding: 70px 7%;
    background:
    linear-gradient(90deg, rgba(234,248,248,.99) 0%, rgba(234,248,248,.95) 43%, rgba(234,248,248,.10) 100%),
    url('https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=1600') center/cover;
}

.hero-content {
    max-width: 610px;
}

.hero-tag {
    display: inline-block;
    color: var(--primary);
    background: #d6f3f1;
    padding: 9px 18px;
    border-radius: 30px;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 22px;
}

.hero h1 {
    font-family: 'Playfair Display', serif;
    font-size: clamp(38px, 5vw, 62px);
    line-height: 1.17;
    margin-bottom: 17px;
}

.hero h1 span {
    color: var(--primary);
}

.hero p {
    color: var(--text);
    font-size: 16px;
    line-height: 1.9;
    margin-bottom: 28px;
    max-width: 520px;
}

.hero-buttons {
    display: flex;
    gap: 14px;
    flex-wrap: wrap;
}

.secondary-btn {
    border: 2px solid var(--primary);
    color: var(--primary);
    padding: 11px 24px;
    border-radius: 30px;
    font-weight: 600;
    transition: .3s;
}

.secondary-btn:hover {
    background: var(--primary);
    color: white;
}

/* FEATURES */

.features {
    padding: 55px 7%;
    background: #f8fcfc;
}

.feature-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

.feature-card {
    background: white;
    padding: 27px 20px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    gap: 17px;
    box-shadow: 0 5px 22px rgba(0,0,0,.06);
    transition: .3s;
    border: 1px solid #edf3f3;
}

.feature-card:hover {
    transform: translateY(-7px);
    box-shadow: 0 10px 25px rgba(8,126,139,.13);
}

/* ==================== SCROLL ANIMATION (BOTTOM TO TOP) ==================== */
.scroll-animate {
    opacity: 0;
    transform: translateY(60px);
    transition: opacity 0.85s cubic-bezier(0.16, 1, 0.3, 1), transform 0.85s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.3s ease;
    will-change: opacity, transform;
}

.scroll-animate.is-revealed {
    opacity: 1;
    transform: translateY(0);
}

/* ==================== SCROLL ANIMATION (SIDEWAYS SLIDE) ==================== */
.scroll-slide-right {
    opacity: 0;
    transform: translateX(80px);
    transition: opacity 0.95s cubic-bezier(0.16, 1, 0.3, 1), transform 0.95s cubic-bezier(0.16, 1, 0.3, 1);
    will-change: opacity, transform;
}

.scroll-slide-right.is-revealed {
    opacity: 1;
    transform: translateX(0);
}

.scroll-slide-left {
    opacity: 0;
    transform: translateX(-80px);
    transition: opacity 0.95s cubic-bezier(0.16, 1, 0.3, 1), transform 0.95s cubic-bezier(0.16, 1, 0.3, 1);
    will-change: opacity, transform;
}

.scroll-slide-left.is-revealed {
    opacity: 1;
    transform: translateX(0);
}

/* Maintain hover effect when revealed */
.feature-card.scroll-animate.is-revealed:hover {
    transform: translateY(-8px) !important;
    box-shadow: 0 12px 28px rgba(8,126,139,.16);
}

.service-card.scroll-animate.is-revealed:hover {
    transform: translateY(-8px) !important;
    box-shadow: 0 14px 32px rgba(0,0,0,.1);
}

/* Stagger delay for cards appearing one after another */
.feature-grid .feature-card:nth-child(1) { transition-delay: 0.05s; }
.feature-grid .feature-card:nth-child(2) { transition-delay: 0.16s; }
.feature-grid .feature-card:nth-child(3) { transition-delay: 0.27s; }
.feature-grid .feature-card:nth-child(4) { transition-delay: 0.38s; }

.service-grid .service-card:nth-child(1) { transition-delay: 0.06s; }
.service-grid .service-card:nth-child(2) { transition-delay: 0.14s; }
.service-grid .service-card:nth-child(3) { transition-delay: 0.22s; }
.service-grid .service-card:nth-child(4) { transition-delay: 0.30s; }
.service-grid .service-card:nth-child(5) { transition-delay: 0.38s; }
.service-grid .service-card:nth-child(6) { transition-delay: 0.46s; }

.feature-icon {
    min-width: 55px;
    height: 55px;
    background: var(--light);
    color: var(--primary);
    display: grid;
    place-items: center;
    border-radius: 13px;
    font-size: 25px;
}

.feature-card h3 {
    font-size: 15px;
    margin-bottom: 5px;
}

.feature-card p {
    color: var(--text);
    font-size: 12px;
    line-height: 1.7;
}

/* SECTION COMMON */

.section {
    padding: 85px 7%;
}

.section-heading {
    text-align: center;
    margin-bottom: 45px;
}

.section-heading span {
    color: var(--primary);
    font-size: 13px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 2px;
}

.section-heading h2 {
    font-family: 'Playfair Display', serif;
    font-size: 37px;
    margin: 10px 0;
}

.section-heading p {
    color: var(--text);
    font-size: 14px;
}

/* ABOUT */

.about-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    align-items: center;
    gap: 55px;
}

.about-image {
    position: relative;
}

.about-image img {
    width: 100%;
    height: 370px;
    object-fit: cover;
    border-radius: 20px;
}

.about-badge {
    position: absolute;
    right: -20px;
    bottom: 25px;
    background: var(--primary);
    color: white;
    padding: 18px 23px;
    border-radius: 14px;
    font-weight: 600;
}

.about-content span {
    color: var(--primary);
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 1px;
}

.about-content h2 {
    font-family: 'Playfair Display', serif;
    font-size: 37px;
    margin: 12px 0 18px;
}

.about-content p {
    color: var(--text);
    font-size: 14px;
    line-height: 1.9;
    margin-bottom: 15px;
}

.about-points {
    margin: 22px 0;
}

.about-points p {
    color: var(--dark);
    font-weight: 500;
}

.about-points i {
    color: var(--secondary);
    margin-right: 10px;
}

/* SERVICES */

.services {
    background: #f5fafa;
}

.service-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 25px;
}

.service-card {
    background: white;
    padding: 32px 25px;
    border-radius: 17px;
    border: 1px solid #e8f1f1;
    transition: .3s;
}

.service-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 10px 30px rgba(0,0,0,.08);
}

.service-icon {
    width: 62px;
    height: 62px;
    display: grid;
    place-items: center;
    background: var(--light);
    color: var(--primary);
    border-radius: 15px;
    font-size: 26px;
    margin-bottom: 20px;
}

.service-card h3 {
    font-size: 17px;
    margin-bottom: 12px;
}

.service-card p {
    color: var(--text);
    font-size: 13px;
    line-height: 1.8;
}

.service-card a {
    display: inline-block;
    margin-top: 20px;
    color: var(--primary);
    font-size: 13px;
    font-weight: 700;
}

/* STATS */

.stats {
    background: linear-gradient(110deg, #076b79, #10a4a4);
    padding: 55px 7%;
    color: white;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    text-align: center;
}

.stat-item i {
    font-size: 30px;
    margin-bottom: 15px;
}

.stat-item h2 {
    font-size: 34px;
    margin-bottom: 6px;
}

.stat-item p {
    font-size: 13px;
    opacity: .9;
}

/* CTA */

.cta {
    padding: 75px 7%;
    text-align: center;
}

.cta-box {
    background: #e7f7f7;
    border-radius: 23px;
    padding: 55px 20px;
}

.cta-box h2 {
    font-family: 'Playfair Display', serif;
    font-size: 35px;
    margin-bottom: 15px;
}

.cta-box p {
    color: var(--text);
    font-size: 14px;
    margin-bottom: 25px;
    line-height: 1.8;
}

/* FOOTER */

.footer {
    background: #092d42;
    color: white;
    padding: 60px 7% 20px;
}

.footer-grid {
    display: grid;
    grid-template-columns: 1.5fr 1fr 1fr 1fr;
    gap: 35px;
    padding-bottom: 40px;
}

.footer-logo {
    font-family: 'Playfair Display', serif;
    font-size: 22px;
    font-weight: 700;
    margin-bottom: 17px;
}

.footer h3 {
    font-size: 16px;
    margin-bottom: 20px;
}

.footer p {
    color: #c3d4df;
    font-size: 12px;
    line-height: 1.9;
}

.footer-links {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.footer-links a {
    color: #c3d4df;
    font-size: 13px;
    transition: .3s;
}

.footer-links a:hover {
    color: #38d1d1;
    padding-left: 5px;
}

.footer-contact p {
    margin-bottom: 12px;
}

.footer-contact i {
    margin-right: 8px;
    color: #31c1c1;
}

.socials {
    display: flex;
    gap: 10px;
    margin-top: 18px;
}

.socials a {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    display: grid;
    place-items: center;
    background: #16475d;
    transition: .3s;
}

.socials a:hover {
    background: var(--secondary);
}

.footer-bottom {
    border-top: 1px solid #345368;
    padding-top: 20px;
    display: flex;
    justify-content: space-between;
    gap: 15px;
    color: #c3d4df;
    font-size: 12px;
}

/* RESPONSIVE */

@media(max-width: 1000px) {
    .nav-links {
        gap: 15px;
    }

    .feature-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .footer-grid {
        grid-template-columns: repeat(2, 1fr);
    }
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

    .menu-toggle {
        display: block;
    }

    .nav-links {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        width: 100%;
        background: white;
        padding: 20px 7%;
        flex-direction: column;
        align-items: flex-start;
        box-shadow: 0 8px 15px rgba(0,0,0,.08);
    }

    .nav-links.show {
        display: flex;
    }

    .hero {
        padding: 65px 6%;
        background:
        linear-gradient(rgba(234,248,248,.92),rgba(234,248,248,.92)),
        url('https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=1000') center/cover;
    }

    .about-grid {
        grid-template-columns: 1fr;
    }

    .service-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        row-gap: 35px;
    }
}

@media(max-width: 500px) {
    .top-info span:nth-child(2) {
        display: none;
    }

    .logo-text {
        font-size: 16px;
    }

    .logo-icon {
        width: 44px;
        height: 44px;
        font-size: 21px;
    }

    .hero h1 {
        font-size: 37px;
    }

    .hero p {
        font-size: 13px;
    }

    .feature-grid,
    .service-grid,
    .footer-grid {
        grid-template-columns: 1fr;
    }

    .feature-card {
        padding: 20px;
    }

    .section {
        padding: 60px 6%;
    }

    .section-heading h2,
    .about-content h2 {
        font-size: 29px;
    }

    .about-image img {
        height: 270px;
    }

    .about-badge {
        right: 8px;
    }

    .cta-box h2 {
        font-size: 27px;
    }

    .footer-bottom {
        flex-direction: column;
    }
}
</style>
</head>

<body>

<!-- HEADER & NAVBAR -->
<?php include 'components/header.php'; ?>

<!-- HERO SECTION -->
<section class="hero" id="home">

    <div class="hero-content">

        <span class="hero-tag">
            <i class="fa-solid fa-heart-pulse"></i>
            PROTECTING LITTLE LIVES
        </span>

        <h1>
            Vaccination <br>
            <span>Management System</span>
        </h1>

        <p>
            Healthy children create a healthy future.
            Manage your child's vaccination schedule,
            book hospital appointments and keep every
            vaccination record in one place.
        </p>

        <div class="hero-buttons">
            <a href="login.php" class="primary-btn">
                <i class="fa-solid fa-calendar-check"></i>
                Book Appointment
                <i class="fa-solid fa-arrow-right"></i>
            </a>

            <a href="about.php" class="secondary-btn">
                Learn More
            </a>
        </div>

    </div>

</section>

<!-- FEATURES -->
<section class="features">

    <div class="feature-grid">

        <div class="feature-card">
            <div class="feature-icon">
                <i class="fa-solid fa-calendar-days"></i>
            </div>
            <div>
                <h3>Easy Booking</h3>
                <p>Book vaccination appointments easily.</p>
            </div>
        </div>

        <div class="feature-card">
            <div class="feature-icon">
                <i class="fa-solid fa-shield-heart"></i>
            </div>
            <div>
                <h3>Track Records</h3>
                <p>Manage your child's vaccination history.</p>
            </div>
        </div>

        <div class="feature-card">
            <div class="feature-icon">
                <i class="fa-solid fa-hospital"></i>
            </div>
            <div>
                <h3>Find Hospitals</h3>
                <p>Explore available vaccination centers.</p>
            </div>
        </div>

        <div class="feature-card">
            <div class="feature-icon">
                <i class="fa-solid fa-bell"></i>
            </div>
            <div>
                <h3>Get Reminders</h3>
                <p>Stay informed about upcoming vaccines.</p>
            </div>
        </div>

    </div>

</section>

<!-- ABOUT SECTION -->
<section class="section" id="about">

    <div class="about-grid">

        <div class="about-image scroll-slide-right">
            <img
                src="https://images.unsplash.com/photo-1584515933487-779824d29309?w=900"
                alt="Healthcare professional caring for a child">

            <div class="about-badge">
                <i class="fa-solid fa-heart"></i>
                Care For Every Child
            </div>
        </div>

        <div class="about-content scroll-slide-left">

            <span>ABOUT OUR SYSTEM</span>

            <h2>
                Building A Healthier
                <span style="color:#087e8b;">Tomorrow</span>
            </h2>

            <p>
                The Vaccination Management System is designed
                to make the vaccination process simple,
                organized and accessible for every family.
            </p>

            <p>
                Our platform connects parents, hospitals and
                administrators through a centralized system
                that helps manage child vaccination records,
                appointments and vaccine availability.
            </p>

            <div class="about-points">
                <p><i class="fa-solid fa-circle-check"></i> Easy child registration</p>
                <p><i class="fa-solid fa-circle-check"></i> Organized vaccination schedules</p>
                <p><i class="fa-solid fa-circle-check"></i> Hospital appointment management</p>
                <p><i class="fa-solid fa-circle-check"></i> Digital vaccination records</p>
            </div>

            <a href="about.php" class="primary-btn">
                Read More <i class="fa-solid fa-arrow-right"></i>
            </a>

        </div>

    </div>

</section>

<!-- SERVICES -->
<section class="section services">

    <div class="section-heading">
        <span>WHAT WE OFFER</span>
        <h2>Our Main Services</h2>
        <p>Simple digital solutions for better vaccination management.</p>
    </div>

    <?php
    $isParentLoggedIn = (!empty($_SESSION['role']) && $_SESSION['role'] === 'parent');
    $bookHospitalLink = $isParentLoggedIn ? 'parent/appointments.php' : 'login.php?redirect=parent/appointments.php';
    $childRegLink     = $isParentLoggedIn ? 'parent/children.php' : 'register.php';
    $scheduleLink     = $isParentLoggedIn ? 'parent/schedule.php' : 'login.php';
    $recordsLink      = $isParentLoggedIn ? 'parent/schedule.php' : 'login.php';
    $remindersLink    = $isParentLoggedIn ? 'parent/dashboard.php' : 'login.php';
    $reportsLink      = $isParentLoggedIn ? 'parent/schedule.php' : 'login.php';
    ?>

    <div class="service-grid">

        <div class="service-card">
            <div class="service-icon">
                <i class="fa-solid fa-baby"></i>
            </div>
            <h3><a href="<?php echo $childRegLink; ?>" style="color:inherit;text-decoration:none;">Child Registration</a></h3>
            <p>
                Parents can register their children and
                maintain their personal vaccination details.
            </p>
            <a href="<?php echo $childRegLink; ?>">Get Started <i class="fa-solid fa-arrow-right"></i></a>
        </div>

        <div class="service-card">
            <div class="service-icon">
                <i class="fa-solid fa-calendar-check"></i>
            </div>
            <h3><a href="<?php echo $bookHospitalLink; ?>" style="color:inherit;text-decoration:none;">Appointment Booking</a></h3>
            <p>
                Search for hospitals and request vaccination
                appointments through the online platform.
            </p>
            <a href="<?php echo $bookHospitalLink; ?>">Book Now <i class="fa-solid fa-arrow-right"></i></a>
        </div>

        <div class="service-card">
            <div class="service-icon">
                <i class="fa-solid fa-syringe"></i>
            </div>
            <h3><a href="<?php echo $recordsLink; ?>" style="color:inherit;text-decoration:none;">Vaccination Records</a></h3>
            <p>
                Keep previous vaccination records organized
                and accessible from your account.
            </p>
            <a href="<?php echo $recordsLink; ?>">View Records <i class="fa-solid fa-arrow-right"></i></a>
        </div>

        <div class="service-card">
            <div class="service-icon">
                <i class="fa-solid fa-hospital-user"></i>
            </div>
            <h3><a href="hospital.php" style="color:inherit;text-decoration:none;">Hospital Management</a></h3>
            <p>
                Explore hospital information and vaccination
                center details in one place.
            </p>
            <a href="hospital.php">Explore Hospitals <i class="fa-solid fa-arrow-right"></i></a>
        </div>

        <div class="service-card">
            <div class="service-icon">
                <i class="fa-solid fa-bell"></i>
            </div>
            <h3><a href="<?php echo $remindersLink; ?>" style="color:inherit;text-decoration:none;">Vaccination Reminders</a></h3>
            <p>
                Stay aware of upcoming vaccination dates
                and never miss a scheduled appointment.
            </p>
            <a href="<?php echo $remindersLink; ?>">Learn More <i class="fa-solid fa-arrow-right"></i></a>
        </div>

        <div class="service-card">
            <div class="service-icon">
                <i class="fa-solid fa-chart-line"></i>
            </div>
            <h3><a href="<?php echo $reportsLink; ?>" style="color:inherit;text-decoration:none;">Vaccination Reports</a></h3>
            <p>
                Access vaccination history and view
                completed vaccination records.
            </p>
            <a href="<?php echo $reportsLink; ?>">View Details <i class="fa-solid fa-arrow-right"></i></a>
        </div>

    </div>

</section>

<!-- STATISTICS -->
<section class="stats">

    <div class="stats-grid">

        <div class="stat-item">
            <i class="fa-solid fa-hospital"></i>
            <h2>Hospitals</h2>
            <p>Connected Healthcare Centers</p>
        </div>

        <div class="stat-item">
            <i class="fa-solid fa-user-group"></i>
            <h2>Families</h2>
            <p>Parent Access Platform</p>
        </div>

        <div class="stat-item">
            <i class="fa-solid fa-syringe"></i>
            <h2>Vaccines</h2>
            <p>Vaccine Information</p>
        </div>

        <div class="stat-item">
            <i class="fa-solid fa-heart-pulse"></i>
            <h2>24/7</h2>
            <p>Digital Platform Access</p>
        </div>

    </div>

</section>

<!-- CALL TO ACTION -->
<section class="cta">

    <div class="cta-box">

        <h2>Give Your Child A Healthier Future</h2>

        <p>
            Create your parent account today and take the
            first step towards organized vaccination care.
            Keep your child's vaccination journey on track.
        </p>

        <a href="register.php" class="primary-btn">
            Register Now <i class="fa-solid fa-arrow-right"></i>
        </a>

    </div>

</section>

<!-- FOOTER -->
<?php include 'components/footer.php'; ?>

<!-- JAVASCRIPT FOR MOBILE MENU & SCROLL ANIMATION -->
<script>
const menuToggle = document.getElementById("menuToggle");
const navLinks = document.getElementById("navLinks");

menuToggle.addEventListener("click", function () {
    navLinks.classList.toggle("show");

    const icon = menuToggle.querySelector("i");

    if (navLinks.classList.contains("show")) {
        icon.classList.replace("fa-bars", "fa-xmark");
    } else {
        icon.classList.replace("fa-xmark", "fa-bars");
    }
});

// Scroll Animation: Reveal cards from bottom to top & sideways elements
document.addEventListener("DOMContentLoaded", function () {
    const cardsToAnimate = document.querySelectorAll(".feature-card, .service-card");
    const sideElements = document.querySelectorAll(".scroll-slide-right, .scroll-slide-left");

    cardsToAnimate.forEach(function (card) {
        card.classList.add("scroll-animate");
    });

    const allAnimatedElements = [...cardsToAnimate, ...sideElements];

    if ("IntersectionObserver" in window) {
        const observer = new IntersectionObserver(
            function (entries, obs) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add("is-revealed");
                        obs.unobserve(entry.target);
                    }
                });
            },
            {
                threshold: 0.12,
                rootMargin: "0px 0px -40px 0px"
            }
        );

        allAnimatedElements.forEach(function (el) {
            observer.observe(el);
        });
    } else {
        allAnimatedElements.forEach(function (el) {
            el.classList.add("is-revealed");
        });
    }
});
</script>

</body>
</html>
