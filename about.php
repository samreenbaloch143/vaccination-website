
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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

<title>About Us | Vaccination Management System</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>
:root {
    --primary: #087e8b;
    --secondary: #13a6a6;
    --dark: #102b46;
    --light: #eaf8f8;
    --text: #536477;
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
    overflow-x: hidden;
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
    gap: 27px;
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

/* PAGE BANNER */

.page-banner {
    background:
    linear-gradient(90deg, rgba(7,107,121,.94), rgba(16,43,70,.85)),
    url('https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=1500') center/cover;
    padding: 95px 7%;
    color: white;
    text-align: center;
}

.page-banner h1 {
    font-family: 'Playfair Display', serif;
    font-size: 48px;
    margin-bottom: 15px;
}

.page-banner p {
    font-size: 14px;
    color: #e1fafa;
}

.breadcrumb {
    margin-top: 20px;
    font-size: 13px;
}

.breadcrumb a {
    color: #9cf0e8;
}

/* COMMON SECTION */

.section {
    padding: 80px 7%;
}

.section-heading {
    text-align: center;
    margin-bottom: 45px;
}

.section-heading span {
    color: var(--primary);
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 2px;
}

.section-heading h2 {
    font-family: 'Playfair Display', serif;
    font-size: 36px;
    margin: 12px 0;
}

.section-heading p {
    color: var(--text);
    font-size: 14px;
}

/* ==================== SCROLL ANIMATIONS ==================== */
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
.mission-card.scroll-animate.is-revealed:hover,
.value-card.scroll-animate.is-revealed:hover,
.process-card.scroll-animate.is-revealed:hover {
    transform: translateY(-8px) !important;
}

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

/* ABOUT INTRO */

.about-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 55px;
    align-items: center;
}

.about-image {
    position: relative;
}

.about-image img {
    width: 100%;
    height: 390px;
    object-fit: cover;
    border-radius: 20px;
}

.image-label {
    position: absolute;
    bottom: 20px;
    right: -15px;
    background: var(--primary);
    color: white;
    padding: 17px 24px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 14px;
}

.about-content span {
    color: var(--primary);
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 1px;
}

.about-content h2 {
    font-family: 'Playfair Display', serif;
    font-size: 36px;
    margin: 15px 0;
}

.about-content h2 em {
    color: var(--primary);
    font-style: normal;
}

.about-content p {
    color: var(--text);
    font-size: 14px;
    line-height: 1.9;
    margin-bottom: 15px;
}

.check-list {
    margin: 20px 0;
}

.check-list div {
    margin: 12px 0;
    font-size: 14px;
    font-weight: 500;
}

.check-list i {
    color: var(--secondary);
    margin-right: 10px;
}

/* MISSION AND VISION */

.mission-section {
    background: #f4fafa;
}

.mission-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 25px;
}

.mission-card {
    background: white;
    padding: 35px;
    border-radius: 18px;
    border: 1px solid #e7f0f0;
    transition: .3s;
}

.mission-card:hover {
    transform: translateY(-7px);
    box-shadow: 0 10px 25px rgba(8,126,139,.1);
}

.mission-icon {
    width: 65px;
    height: 65px;
    display: grid;
    place-items: center;
    border-radius: 15px;
    background: var(--light);
    color: var(--primary);
    font-size: 28px;
    margin-bottom: 20px;
}

.mission-card h3 {
    font-size: 21px;
    margin-bottom: 13px;
}

.mission-card p {
    color: var(--text);
    font-size: 14px;
    line-height: 1.9;
}

/* VALUES */

.values-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 25px;
}

.value-card {
    text-align: center;
    padding: 35px 25px;
    border-radius: 18px;
    background: white;
    border: 1px solid #eaf0f0;
    transition: .3s;
}

.value-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 10px 30px rgba(0,0,0,.07);
}

.value-card i {
    font-size: 32px;
    color: var(--primary);
    background: var(--light);
    width: 75px;
    height: 75px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    margin: 0 auto 20px;
}

.value-card h3 {
    font-size: 18px;
    margin-bottom: 12px;
}

.value-card p {
    color: var(--text);
    font-size: 13px;
    line-height: 1.8;
}

/* HOW IT WORKS */

.process-section {
    background: #f4fafa;
}

.process-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

.process-card {
    background: white;
    padding: 28px 18px;
    border-radius: 15px;
    text-align: center;
    position: relative;
}

.process-number {
    width: 45px;
    height: 45px;
    background: var(--primary);
    color: white;
    border-radius: 50%;
    display: grid;
    place-items: center;
    margin: 0 auto 18px;
    font-weight: 700;
}

.process-card i {
    color: var(--primary);
    font-size: 28px;
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

/* CTA */

.cta {
    padding: 75px 7%;
    text-align: center;
}

.cta-box {
    background: linear-gradient(110deg, #087e8b, #13a6a6);
    color: white;
    padding: 55px 20px;
    border-radius: 22px;
}

.cta-box h2 {
    font-family: 'Playfair Display', serif;
    font-size: 35px;
    margin-bottom: 15px;
}

.cta-box p {
    color: #e5ffff;
    font-size: 14px;
    line-height: 1.8;
    margin-bottom: 25px;
}

.cta-box a {
    background: white;
    color: var(--primary);
    padding: 13px 27px;
    border-radius: 30px;
    display: inline-block;
    font-weight: 600;
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
    font-size: 21px;
    font-weight: 700;
    margin-bottom: 17px;
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
        gap: 14px;
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

    .about-grid {
        grid-template-columns: 1fr;
    }

    .values-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .page-banner h1 {
        font-size: 38px;
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

    .page-banner {
        padding: 65px 5%;
    }

    .page-banner h1 {
        font-size: 32px;
    }

    .section {
        padding: 55px 6%;
    }

    .section-heading h2,
    .about-content h2 {
        font-size: 29px;
    }

    .about-image img {
        height: 270px;
    }

    .image-label {
        right: 5px;
    }

    .mission-grid,
    .values-grid,
    .process-grid,
    .footer-grid {
        grid-template-columns: 1fr;
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

<!-- PAGE BANNER -->

<section class="page-banner">
    <h1>About Our System</h1>

    <p>
        Making Vaccination Easier, Smarter and More Accessible
    </p>

    <div class="breadcrumb">
        <a href="index.php">Home</a>
        &nbsp; / &nbsp; About Us
    </div>
</section>

<!-- ABOUT INTRODUCTION -->

<section class="section">

    <div class="about-grid">

        <div class="about-image scroll-slide-right">
            <img
                src="https://images.unsplash.com/photo-1584515933487-779824d29309?w=900"
                alt="Healthcare professional caring for a child">

            <div class="image-label">
                <i class="fa-solid fa-heart"></i>
                Caring For Every Child
            </div>
        </div>

        <div class="about-content scroll-slide-left">

            <span>WHO WE ARE</span>

            <h2>
                A Better Way To Manage
                <em>Child Vaccination</em>
            </h2>

            <p>
                The Vaccination Management System is an online
                platform designed to improve the way child
                vaccination services are organized and managed.
            </p>

            <p>
                Traditional vaccination processes can make it
                difficult for parents to remember vaccination
                dates, find hospitals and maintain previous
                vaccination records.
            </p>

            <p>
                Our system provides a centralized platform where
                parents can register their children, view
                vaccination schedules, request appointments
                and access vaccination history.
            </p>

            <div class="check-list">
                <div><i class="fa-solid fa-circle-check"></i> Simple online registration</div>
                <div><i class="fa-solid fa-circle-check"></i> Organized vaccination schedules</div>
                <div><i class="fa-solid fa-circle-check"></i> Hospital appointment management</div>
                <div><i class="fa-solid fa-circle-check"></i> Digital vaccination records</div>
            </div>

            <a href="register.php" class="primary-btn">
                Get Started <i class="fa-solid fa-arrow-right"></i>
            </a>

        </div>

    </div>

</section>

<!-- MISSION AND VISION -->

<section class="section mission-section">

    <div class="section-heading">
        <span>OUR PURPOSE</span>
        <h2>Mission & Vision</h2>
        <p>Our commitment towards a healthier and safer future.</p>
    </div>

    <div class="mission-grid">

        <div class="mission-card">
            <div class="mission-icon">
                <i class="fa-solid fa-bullseye"></i>
            </div>

            <h3>Our Mission</h3>

            <p>
                Our mission is to make child vaccination
                management simple, efficient and accessible
                by connecting parents, hospitals and
                administrators through a digital platform.
                We aim to help families keep track of
                vaccination appointments and records.
            </p>
        </div>

        <div class="mission-card">
            <div class="mission-icon">
                <i class="fa-solid fa-eye"></i>
            </div>

            <h3>Our Vision</h3>

            <p>
                Our vision is to create a reliable and
                organized vaccination management environment
                where every child can have better access
                to vaccination services and parents can
                manage their children's health records
                with greater convenience.
            </p>
        </div>

    </div>

</section>

<!-- CORE VALUES -->

<section class="section">

    <div class="section-heading">
        <span>WHAT MATTERS TO US</span>
        <h2>Our Core Values</h2>
        <p>The principles that guide our vaccination management platform.</p>
    </div>

    <div class="values-grid">

        <div class="value-card">
            <i class="fa-solid fa-shield-heart"></i>
            <h3>Child Safety</h3>
            <p>
                We focus on organized vaccination records
                and timely appointment management.
            </p>
        </div>

        <div class="value-card">
            <i class="fa-solid fa-hand-holding-heart"></i>
            <h3>Accessibility</h3>
            <p>
                We aim to make vaccination information
                and hospital services easier to access.
            </p>
        </div>

        <div class="value-card">
            <i class="fa-solid fa-clock"></i>
            <h3>Efficiency</h3>
            <p>
                We simplify manual processes through
                digital appointment and record management.
            </p>
        </div>

        <div class="value-card">
            <i class="fa-solid fa-lock"></i>
            <h3>Privacy</h3>
            <p>
                We aim to keep child and parent
                information protected and organized.
            </p>
        </div>

        <div class="value-card">
            <i class="fa-solid fa-users"></i>
            <h3>Cooperation</h3>
            <p>
                We connect parents, hospitals and
                administrators in one system.
            </p>
        </div>

        <div class="value-card">
            <i class="fa-solid fa-heart-pulse"></i>
            <h3>Healthy Future</h3>
            <p>
                We support better vaccination
                awareness for healthier communities.
            </p>
        </div>

    </div>

</section>

<!-- HOW IT WORKS -->

<section class="section process-section">

    <div class="section-heading">
        <span>OUR PROCESS</span>
        <h2>How Our System Works</h2>
        <p>Four simple steps to manage vaccination appointments.</p>
    </div>

    <div class="process-grid">

        <div class="process-card">
            <div class="process-number">01</div>
            <i class="fa-solid fa-user-plus"></i>
            <h3>Register Your Child</h3>
            <p>
                Parents create an account and
                add their child's information.
            </p>
        </div>

        <div class="process-card">
            <div class="process-number">02</div>
            <i class="fa-solid fa-calendar-days"></i>
            <h3>Check Schedule</h3>
            <p>
                Parents view vaccination dates
                and available appointment information.
            </p>
        </div>

        <div class="process-card">
            <div class="process-number">03</div>
            <i class="fa-solid fa-hospital"></i>
            <h3>Choose Hospital</h3>
            <p>
                Select a hospital and submit
                an appointment request.
            </p>
        </div>

        <div class="process-card">
            <div class="process-number">04</div>
            <i class="fa-solid fa-file-circle-check"></i>
            <h3>Track Records</h3>
            <p>
                View completed vaccination
                records through your account.
            </p>
        </div>

    </div>

</section>

<!-- CALL TO ACTION -->

<section class="cta">

    <div class="cta-box">

        <h2>Every Child Deserves A Healthy Future</h2>

        <p>
            Join our platform to manage your child's
            vaccination journey more easily.
            Register today and stay informed.
        </p>

        <a href="register.php">
            Create Parent Account
            <i class="fa-solid fa-arrow-right"></i>
        </a>

    </div>

</section>

<!-- FOOTER -->
<?php include 'components/footer.php'; ?>

<!-- MOBILE MENU -->

<script src="assets/script.js"></script>

<script>
// Scroll Animation: Reveal cards from bottom to top & sideways elements
document.addEventListener("DOMContentLoaded", function () {
    const cardsToAnimate = document.querySelectorAll(".mission-card, .value-card, .process-card");
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

<!-- <script>
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
</script> -->

</body>
</html>
