<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$pageTitle = "Our Services";
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
    --primary: #087e8b;
    --secondary: #13a6a6;
    --dark: #102b46;
    --light: #eaf8f8;
    --text: #637487;
    --white: #ffffff;
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
    background: white;
}

a {
    text-decoration: none;
    color: inherit;
}

/* TOP BAR */

.topbar {
    background: #076b79;
    color: white;
    padding: 10px 7%;
    display: flex;
    justify-content: space-between;
    font-size: 13px;
}

.top-info {
    display: flex;
    gap: 25px;
}

.topbar i {
    margin-right: 7px;
}

/* NAVBAR */

.navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 17px 7%;
    background: white;
    position: sticky;
    top: 0;
    z-index: 1000;
    box-shadow: 0 3px 15px rgba(0,0,0,.06);
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
    border-radius: 15px;
    display: grid;
    place-items: center;
    font-size: 25px;
}

.logo-text {
    font-family: 'Playfair Display', serif;
    font-size: 19px;
    font-weight: 700;
    line-height: 1.2;
}

.nav-links {
    display: flex;
    align-items: center;
    gap: 25px;
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
    display: inline-block;
    transition: .3s;
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

/* HERO */

.services-hero {
    min-height: 420px;
    padding: 80px 7%;
    display: flex;
    align-items: center;
    background:
    linear-gradient(90deg, rgba(7,107,121,.96), rgba(16,43,70,.78)),
    url('https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=1600') center/cover;
    color: white;
}

.hero-content {
    max-width: 720px;
}

.hero-label {
    display: inline-block;
    background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.3);
    padding: 9px 18px;
    border-radius: 30px;
    font-size: 12px;
    letter-spacing: 1px;
    margin-bottom: 22px;
}

.services-hero h1 {
    font-family: 'Playfair Display', serif;
    font-size: clamp(40px, 5vw, 59px);
    line-height: 1.2;
    margin-bottom: 20px;
}

.services-hero h1 span {
    color: #8ff0e8;
}

.services-hero p {
    color: #e0f5f5;
    font-size: 15px;
    line-height: 1.9;
    max-width: 600px;
    margin-bottom: 27px;
}

.hero-btn {
    display: inline-block;
    background: white;
    color: var(--primary);
    padding: 14px 27px;
    border-radius: 30px;
    font-size: 14px;
    font-weight: 600;
    transition: .3s;
}

.hero-btn:hover {
    background: #bff7f0;
    transform: translateY(-3px);
}

/* INTRODUCTION */

.intro {
    padding: 75px 7%;
    text-align: center;
}

.section-tag {
    color: var(--primary);
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 2px;
}

.intro h2,
.section-heading h2 {
    font-family: 'Playfair Display', serif;
    font-size: 36px;
    margin: 13px 0;
}

.intro h2 span {
    color: var(--primary);
}

.intro p {
    max-width: 750px;
    margin: auto;
    color: var(--text);
    font-size: 14px;
    line-height: 1.9;
}

/* SERVICES */

.services-section {
    padding: 30px 7% 85px;
    background: #f5fafa;
}

.section-heading {
    text-align: center;
    margin-bottom: 45px;
}

.section-heading p {
    color: var(--text);
    font-size: 13px;
    margin-top: 10px;
}

.service-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 25px;
}

.service-card {
    background: white;
    border: 1px solid #e7eeee;
    border-radius: 18px;
    padding: 32px 27px;
    position: relative;
    overflow: hidden;
    transition: .35s;
}

.service-card::before {
    content: "";
    position: absolute;
    width: 4px;
    height: 0;
    background: var(--primary);
    top: 0;
    left: 0;
    transition: .35s;
}

.service-card:hover::before {
    height: 100%;
}

.service-card:hover {
    transform: translateY(-9px);
    box-shadow: 0 15px 35px rgba(8,126,139,.12);
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

.service-card.scroll-animate.is-revealed:hover {
    transform: translateY(-9px) !important;
    box-shadow: 0 15px 35px rgba(8,126,139,.12);
}

.process-card.scroll-animate.is-revealed:hover {
    transform: translateY(-9px) !important;
    box-shadow: 0 15px 35px rgba(8,126,139,.12);
}

.service-grid .service-card:nth-child(1) { transition-delay: 0.05s; }
.service-grid .service-card:nth-child(2) { transition-delay: 0.15s; }
.service-grid .service-card:nth-child(3) { transition-delay: 0.25s; }
.service-grid .service-card:nth-child(4) { transition-delay: 0.05s; }
.service-grid .service-card:nth-child(5) { transition-delay: 0.15s; }
.service-grid .service-card:nth-child(6) { transition-delay: 0.25s; }
.service-grid .service-card:nth-child(7) { transition-delay: 0.05s; }
.service-grid .service-card:nth-child(8) { transition-delay: 0.15s; }
.service-grid .service-card:nth-child(9) { transition-delay: 0.25s; }

.process-grid .process-card:nth-child(1) { transition-delay: 0.05s; }
.process-grid .process-card:nth-child(2) { transition-delay: 0.15s; }
.process-grid .process-card:nth-child(3) { transition-delay: 0.25s; }
.process-grid .process-card:nth-child(4) { transition-delay: 0.35s; }

.service-icon {
    width: 68px;
    height: 68px;
    border-radius: 17px;
    background: var(--light);
    color: var(--primary);
    display: grid;
    place-items: center;
    font-size: 29px;
    margin-bottom: 23px;
    transition: .3s;
}

.service-card:hover .service-icon {
    background: var(--primary);
    color: white;
    transform: rotate(-6deg);
}

.service-card h3 {
    font-size: 17px;
    margin-bottom: 13px;
}

.service-card p {
    color: var(--text);
    font-size: 13px;
    line-height: 1.9;
    min-height: 74px;
}

.service-card a {
    display: inline-block;
    color: var(--primary);
    font-weight: 600;
    font-size: 13px;
    margin-top: 20px;
}

.service-card a i {
    margin-left: 7px;
    transition: .3s;
}

.service-card a:hover i {
    margin-left: 13px;
}

/* HIGHLIGHT SECTION */

.highlight {
    padding: 85px 7%;
}

.highlight-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 55px;
    align-items: center;
}

.highlight-image img {
    width: 100%;
    height: 400px;
    object-fit: cover;
    border-radius: 20px;
}

.highlight-content h2 {
    font-family: 'Playfair Display', serif;
    font-size: 36px;
    margin: 15px 0;
}

.highlight-content h2 span {
    color: var(--primary);
}

.highlight-content p {
    color: var(--text);
    font-size: 14px;
    line-height: 1.9;
    margin-bottom: 20px;
}

.benefit {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    margin-bottom: 20px;
}

.benefit i {
    background: var(--light);
    color: var(--primary);
    width: 42px;
    height: 42px;
    min-width: 42px;
    display: grid;
    place-items: center;
    border-radius: 12px;
}

.benefit h4 {
    font-size: 14px;
    margin-bottom: 5px;
}

.benefit p {
    font-size: 12px;
    margin: 0;
}

/* PROCESS */

.process-section {
    padding: 80px 7%;
    background: #f5fafa;
}

.process-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 22px;
}

.process-card {
    background: white;
    border-radius: 17px;
    padding: 28px 18px;
    text-align: center;
    transition: .3s;
}

.process-card:hover {
    transform: translateY(-7px);
    box-shadow: 0 10px 25px rgba(0,0,0,.07);
}

.process-number {
    width: 43px;
    height: 43px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background: var(--primary);
    color: white;
    font-weight: 700;
    margin: 0 auto 20px;
}

.process-card i {
    color: var(--primary);
    font-size: 27px;
    margin-bottom: 15px;
}

.process-card h3 {
    font-size: 15px;
    margin-bottom: 10px;
}

.process-card p {
    font-size: 12px;
    color: var(--text);
    line-height: 1.8;
}

/* FAQ */

.faq-section {
    padding: 80px 7%;
}

.faq-container {
    max-width: 850px;
    margin: auto;
}

.faq-item {
    border: 1px solid #e4eeee;
    border-radius: 12px;
    margin-bottom: 14px;
    overflow: hidden;
}

.faq-question {
    width: 100%;
    border: none;
    background: white;
    padding: 19px 22px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    text-align: left;
    font-family: inherit;
    font-size: 14px;
    font-weight: 600;
    color: var(--dark);
    cursor: pointer;
}

.faq-question i {
    color: var(--primary);
    transition: .3s;
}

.faq-answer {
    display: none;
    padding: 0 22px 20px;
    color: var(--text);
    font-size: 13px;
    line-height: 1.9;
}

.faq-item.open .faq-answer {
    display: block;
}

.faq-item.open .faq-question i {
    transform: rotate(180deg);
}

/* CTA */

.cta {
    padding: 25px 7% 80px;
}

.cta-box {
    background: linear-gradient(110deg, #076b79, #13a6a6);
    padding: 60px 25px;
    border-radius: 22px;
    text-align: center;
    color: white;
}

.cta-box h2 {
    font-family: 'Playfair Display', serif;
    font-size: 35px;
    margin-bottom: 15px;
}

.cta-box p {
    font-size: 14px;
    line-height: 1.9;
    color: #e2ffff;
    margin-bottom: 25px;
}

.cta-box a {
    display: inline-block;
    background: white;
    color: var(--primary);
    padding: 14px 27px;
    border-radius: 30px;
    font-weight: 600;
    transition: .3s;
}

.cta-box a:hover {
    background: #d7ffff;
    transform: translateY(-3px);
}

/* FOOTER */

.footer {
    background: #092d42;
    color: white;
    padding: 55px 7% 20px;
}

.footer-grid {
    display: grid;
    grid-template-columns: 1.5fr 1fr 1fr 1fr;
    gap: 35px;
    padding-bottom: 35px;
}

.footer-logo {
    font-family: 'Playfair Display', serif;
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 16px;
}

.footer p {
    color: #c3d4df;
    font-size: 12px;
    line-height: 1.9;
}

.footer h3 {
    font-size: 16px;
    margin-bottom: 20px;
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
    color: #38d1d1;
    margin-right: 8px;
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
    background: #16475d;
    display: grid;
    place-items: center;
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
    font-size: 12px;
}

/* RESPONSIVE */

@media(max-width: 1000px) {
    .nav-links {
        gap: 13px;
    }

    .service-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .footer-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .process-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media(max-width: 768px) {
    .topbar {
        padding: 10px 5%;
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
        padding: 22px 7%;
        flex-direction: column;
        align-items: flex-start;
    }

    .nav-links.show {
        display: flex;
    }

    .highlight-grid {
        grid-template-columns: 1fr;
    }

    .services-hero {
        padding: 65px 6%;
    }

    .services-hero h1 {
        font-size: 40px;
    }
}

@media(max-width: 500px) {
    .top-info span:nth-child(2) {
        display: none;
    }

    .logo-text {
        font-size: 15px;
    }

    .logo-icon {
        width: 43px;
        height: 43px;
    }

    .service-grid,
    .process-grid,
    .footer-grid {
        grid-template-columns: 1fr;
    }

    .services-hero h1 {
        font-size: 34px;
    }

    .intro h2,
    .section-heading h2,
    .highlight-content h2 {
        font-size: 29px;
    }

    .intro,
    .highlight,
    .faq-section {
        padding: 55px 6%;
    }

    .services-section,
    .process-section {
        padding: 55px 6%;
    }

    .highlight-image img {
        height: 270px;
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

<section class="services-hero">

    <div class="hero-content">

        <span class="hero-label">
            <i class="fa-solid fa-heart-pulse"></i>
            COMPLETE VACCINATION CARE
        </span>

        <h1>
            Smart Services For
            <span>Healthy Children</span>
        </h1>

        <p>
            Explore our digital vaccination management services.
            From child registration to hospital appointments
            and vaccination history, everything is organized
            in one convenient platform.
        </p>

        <a href="#all-services" class="hero-btn">
            Explore Our Services
            <i class="fa-solid fa-arrow-right"></i>
        </a>

    </div>

</section>

<!-- INTRODUCTION -->

<section class="intro">

    <span class="section-tag">OUR SERVICES</span>

    <h2>
        Complete Care In
        <span>One Platform</span>
    </h2>

    <p>
        Our Vaccination Management System provides digital
        solutions to simplify vaccination activities for
        parents, hospitals and administrators. We help
        organize appointments, child information, vaccine
        availability and vaccination records.
    </p>

</section>

<!-- SERVICES GRID -->

<section class="services-section" id="all-services">

    <div class="section-heading">

        <span class="section-tag">WHAT WE PROVIDE</span>

        <h2>Explore Our Services</h2>

        <p>
            Everything you need to manage your child's
            vaccination journey more efficiently.
        </p>

    </div>

    <?php
    $isParentLoggedIn = (!empty($_SESSION['role']) && $_SESSION['role'] === 'parent');
    $isHospitalLoggedIn = (!empty($_SESSION['role']) && $_SESSION['role'] === 'hospital');
    $bookHospitalLink = $isParentLoggedIn ? 'parent/appointments.php' : 'login.php?redirect=parent/appointments.php';
    $childRegLink     = $isParentLoggedIn ? 'parent/children.php' : 'register.php';
    $scheduleLink     = $isParentLoggedIn ? 'parent/schedule.php' : 'login.php';
    $recordsLink      = $isParentLoggedIn ? 'parent/schedule.php' : 'login.php';
    $remindersLink    = $isParentLoggedIn ? 'parent/dashboard.php' : 'login.php';
    $reportsLink      = $isParentLoggedIn ? 'parent/schedule.php' : 'login.php';
    $hospitalCoordLink = $isHospitalLoggedIn ? 'hospital/dashboard.php' : 'login.php';
    ?>

    <div class="service-grid">

        <div class="service-card">
            <div class="service-icon">
                <i class="fa-solid fa-baby"></i>
            </div>
            <h3><a href="<?php echo $childRegLink; ?>" style="color:inherit;text-decoration:none;">Child Registration</a></h3>
            <p>
                Parents can create child profiles by adding
                essential details such as name, date of birth,
                gender and other required information.
            </p>
            <a href="<?php echo $childRegLink; ?>">
                Register Now <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <div class="service-card">
            <div class="service-icon">
                <i class="fa-solid fa-calendar-check"></i>
            </div>
            <h3><a href="<?php echo $bookHospitalLink; ?>" style="color:inherit;text-decoration:none;">Appointment Booking</a></h3>
            <p>
                Request vaccination appointments by selecting
                a suitable hospital and preferred date.
                Appointments are managed through the system.
            </p>
            <a href="<?php echo $bookHospitalLink; ?>">
                Book Appointment <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <div class="service-card">
            <div class="service-icon">
                <i class="fa-solid fa-syringe"></i>
            </div>
            <h3><a href="<?php echo $scheduleLink; ?>" style="color:inherit;text-decoration:none;">Vaccination Schedule</a></h3>
            <p>
                View scheduled vaccination dates and
                maintain an organized timeline for
                your child's vaccination journey.
            </p>
            <a href="<?php echo $scheduleLink; ?>">
                View Schedule <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <div class="service-card">
            <div class="service-icon">
                <i class="fa-solid fa-hospital"></i>
            </div>
            <h3><a href="hospital.php" style="color:inherit;text-decoration:none;">Hospital Information</a></h3>
            <p>
                Explore registered hospitals and access
                their address, location and available
                vaccination service information.
            </p>
            <a href="hospital.php">
                Find Hospitals <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <div class="service-card">
            <div class="service-icon">
                <i class="fa-solid fa-file-medical"></i>
            </div>
            <h3><a href="<?php echo $recordsLink; ?>" style="color:inherit;text-decoration:none;">Digital Vaccination Records</a></h3>
            <p>
                Maintain vaccination history and view
                previously completed vaccinations
                through your parent account.
            </p>
            <a href="<?php echo $recordsLink; ?>">
                View Records <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <div class="service-card">
            <div class="service-icon">
                <i class="fa-solid fa-bell"></i>
            </div>
            <h3><a href="<?php echo $remindersLink; ?>" style="color:inherit;text-decoration:none;">Vaccination Reminders</a></h3>
            <p>
                Stay informed about upcoming vaccination
                dates through dashboard notifications
                and available reminder features.
            </p>
            <a href="<?php echo $remindersLink; ?>">
                Get Started <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <div class="service-card">
            <div class="service-icon">
                <i class="fa-solid fa-boxes-stacked"></i>
            </div>
            <h3><a href="vaccine.php" style="color:inherit;text-decoration:none;">Vaccine Availability</a></h3>
            <p>
                Check vaccine availability information
                maintained by registered hospitals
                and authorized administrators.
            </p>
            <a href="vaccine.php">
                Explore Vaccines <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <div class="service-card">
            <div class="service-icon">
                <i class="fa-solid fa-chart-column"></i>
            </div>
            <h3><a href="<?php echo $reportsLink; ?>" style="color:inherit;text-decoration:none;">Vaccination Reports</a></h3>
            <p>
                View vaccination status and completed
                vaccination records to keep track
                of your child's progress.
            </p>
            <a href="<?php echo $reportsLink; ?>">
                View Reports <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <div class="service-card">
            <div class="service-icon">
                <i class="fa-solid fa-user-doctor"></i>
            </div>
            <h3><a href="<?php echo $hospitalCoordLink; ?>" style="color:inherit;text-decoration:none;">Hospital Coordination</a></h3>
            <p>
                Hospitals can manage their vaccine
                information and update appointment
                and vaccination completion status.
            </p>
            <a href="<?php echo $hospitalCoordLink; ?>">
                Hospital Login <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

    </div>

</section>

<!-- HIGHLIGHT SECTION -->

<section class="highlight">

    <div class="highlight-grid">

        <div class="highlight-image">

            <img
                src="https://images.unsplash.com/photo-1584982751601-97dcc096659c?w=1000"
                alt="Healthcare professional with child">

        </div>

        <div class="highlight-content">

            <span class="section-tag">DIGITAL HEALTHCARE</span>

            <h2>
                Making Vaccination
                <span>Simple & Organized</span>
            </h2>

            <p>
                Our platform brings important vaccination
                management activities together, helping
                parents and healthcare centers manage
                information more conveniently.
            </p>

            <div class="benefit">
                <i class="fa-solid fa-user-check"></i>
                <div>
                    <h4>Easy Parent Access</h4>
                    <p>Manage child profiles and vaccination information.</p>
                </div>
            </div>

            <div class="benefit">
                <i class="fa-solid fa-calendar-check"></i>
                <div>
                    <h4>Organized Appointments</h4>
                    <p>Submit and track hospital appointment requests.</p>
                </div>
            </div>

            <div class="benefit">
                <i class="fa-solid fa-shield-heart"></i>
                <div>
                    <h4>Digital Record Management</h4>
                    <p>Keep previous vaccination information accessible.</p>
                </div>
            </div>

            <a href="register.php" class="primary-btn">
                Join Our Platform
                <i class="fa-solid fa-arrow-right"></i>
            </a>

        </div>

    </div>

</section>

<!-- HOW IT WORKS -->

<section class="process-section">

    <div class="section-heading">

        <span class="section-tag">SIMPLE PROCESS</span>

        <h2>How To Get Started</h2>

        <p>
            Start managing your child's vaccination
            information in a few simple steps.
        </p>

    </div>

    <div class="process-grid">

        <div class="process-card">
            <div class="process-number">01</div>
            <i class="fa-solid fa-user-plus"></i>
            <h3>Create Account</h3>
            <p>Register as a parent and create your account.</p>
        </div>

        <div class="process-card">
            <div class="process-number">02</div>
            <i class="fa-solid fa-child"></i>
            <h3>Add Child Details</h3>
            <p>Enter your child's required information.</p>
        </div>

        <div class="process-card">
            <div class="process-number">03</div>
            <i class="fa-solid fa-hospital"></i>
            <h3>Select Hospital</h3>
            <p>Choose a hospital and request an appointment.</p>
        </div>

        <div class="process-card">
            <div class="process-number">04</div>
            <i class="fa-solid fa-file-circle-check"></i>
            <h3>Track Vaccination</h3>
            <p>View vaccination status and previous records.</p>
        </div>

    </div>

</section>

<!-- FAQ SECTION -->

<section class="faq-section">

    <div class="section-heading">

        <span class="section-tag">HELP CENTER</span>

        <h2>Frequently Asked Questions</h2>

        <p>Find answers to common questions about our services.</p>

    </div>

    <div class="faq-container">

        <div class="faq-item">
            <button class="faq-question">
                What is the Vaccination Management System?
                <i class="fa-solid fa-chevron-down"></i>
            </button>
            <div class="faq-answer">
                It is an online platform designed to help parents
                manage child vaccination information, appointment
                requests and vaccination records.
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question">
                How can parents register their children?
                <i class="fa-solid fa-chevron-down"></i>
            </button>
            <div class="faq-answer">
                Parents can create an account and add their
                children's required details through the
                parent registration and dashboard pages.
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question">
                Can parents book hospital appointments?
                <i class="fa-solid fa-chevron-down"></i>
            </button>
            <div class="faq-answer">
                Yes. Parents can select a registered hospital
                and submit an appointment request. The request
                is then handled according to the system's
                approval process.
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question">
                Can parents view previous vaccination records?
                <i class="fa-solid fa-chevron-down"></i>
            </button>
            <div class="faq-answer">
                Yes. Parents can view the vaccination history
                recorded in their child's account.
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question">
                Who can update vaccine availability?
                <i class="fa-solid fa-chevron-down"></i>
            </button>
            <div class="faq-answer">
                Authorized hospital users can update vaccine
                availability information through their
                hospital dashboard.
            </div>
        </div>

    </div>

</section>

<!-- CALL TO ACTION -->

<section class="cta">

    <div class="cta-box">

        <h2>Start Your Child's Vaccination Journey Today</h2>

        <p>
            Create your parent account and make vaccination
            management easier, more organized and convenient.
        </p>

        <a href="register.php">
            Register Now
            <i class="fa-solid fa-arrow-right"></i>
        </a>

    </div>

</section>

<!-- FOOTER -->
<?php include 'components/footer.php'; ?>

<!-- JAVASCRIPT -->

<script>
const menuToggle = document.getElementById("menuToggle");
const navLinks = document.getElementById("navLinks");

menuToggle.addEventListener("click", function() {

    navLinks.classList.toggle("show");

    const icon = menuToggle.querySelector("i");

    if(navLinks.classList.contains("show")) {
        icon.classList.replace("fa-bars", "fa-xmark");
    } else {
        icon.classList.replace("fa-xmark", "fa-bars");
    }

});

// FAQ ACCORDION

const faqItems = document.querySelectorAll(".faq-item");

faqItems.forEach(function(item) {

    const question = item.querySelector(".faq-question");

    question.addEventListener("click", function() {

        faqItems.forEach(function(otherItem) {
            if(otherItem !== item) {
                otherItem.classList.remove("open");
            }
        });

        item.classList.toggle("open");

    });

});

// Scroll Animation: Reveal cards from bottom to top
document.addEventListener("DOMContentLoaded", function () {
    const cardsToAnimate = document.querySelectorAll(".service-card, .process-card");

    cardsToAnimate.forEach(function (card) {
        card.classList.add("scroll-animate");
    });

    if ("IntersectionObserver" in window) {
        const cardObserver = new IntersectionObserver(
            function (entries, observer) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add("is-revealed");
                        observer.unobserve(entry.target);
                    }
                });
            },
            {
                threshold: 0.12,
                rootMargin: "0px 0px -40px 0px"
            }
        );

        cardsToAnimate.forEach(function (card) {
            cardObserver.observe(card);
        });
    } else {
        cardsToAnimate.forEach(function (card) {
            card.classList.add("is-revealed");
        });
    }
});
</script>

</body>
</html>
