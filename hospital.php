
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config.php';
$pageTitle = "Our Hospitals";

$isParent = (!empty($_SESSION['role']) && $_SESSION['role'] === 'parent');

// Load hospital availability map from DB
$hospAvailability = [];
try {
    $hRes = $conn->query("SELECT id, hospital_name, availability FROM hospitals");
    if ($hRes) {
        while ($row = $hRes->fetch_assoc()) {
            $hospAvailability[$row['id']] = $row['availability'] ?? 'Available';
            $hospAvailability[strtolower(trim($row['hospital_name']))] = $row['availability'] ?? 'Available';
        }
    }
} catch (Throwable $t) {}

function renderHospitalBookBtn($hospitalId, $hospitalName, $isParent, $hospAvailability) {
    $avail = $hospAvailability[$hospitalId] ?? $hospAvailability[strtolower(trim($hospitalName))] ?? 'Available';
    $isAvailable = (strcasecmp($avail, 'Unavailable') !== 0);

    if (!$isAvailable) {
        return '<span class="btn-book-disabled" title="This hospital is currently unavailable for booking"><i class="fa-solid fa-ban"></i> Unavailable</span>';
    }
    if ($isParent) {
        $link = 'parent/appointments.php?hospital_id=' . (int)$hospitalId . '&book=1';
    } else {
        $link = 'login.php?redirect=' . urlencode('parent/appointments.php?hospital_id=' . (int)$hospitalId . '&book=1');
    }
    return '<a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '">Book Now</a>';
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

* {
    margin:0;
    padding:0;
    box-sizing:border-box;
    scroll-behavior:smooth;
}

body {
    font-family:'Poppins',sans-serif;
    color:var(--dark);
}

a {
    text-decoration:none;
    color:inherit;
}

/* TOPBAR */

.topbar {
    background:#076b79;
    color:white;
    padding:10px 7%;
    display:flex;
    justify-content:space-between;
    font-size:13px;
}

.top-info {
    display:flex;
    gap:25px;
}

.topbar i {
    margin-right:7px;
}

/* NAVBAR */

.navbar {
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:17px 7%;
    background:white;
    position:sticky;
    top:0;
    z-index:1000;
    box-shadow:0 3px 15px rgba(0,0,0,.06);
}

.logo {
    display:flex;
    align-items:center;
    gap:12px;
}

.logo-icon {
    width:53px;
    height:53px;
    border:3px solid var(--primary);
    color:var(--primary);
    border-radius:15px;
    display:grid;
    place-items:center;
    font-size:25px;
}

.logo-text {
    font-family:'Playfair Display',serif;
    font-size:19px;
    font-weight:700;
    line-height:1.2;
}

.nav-links {
    display:flex;
    align-items:center;
    gap:25px;
}

.nav-links a {
    font-size:14px;
    font-weight:500;
    transition:.3s;
}

.nav-links a:hover,
.nav-links a.active {
    color:var(--primary);
}

.login-btn {
    background:var(--primary);
    color:white!important;
    padding:12px 23px;
    border-radius:30px;
}

.menu-toggle {
    display:none;
    background:none;
    border:none;
    font-size:25px;
    color:var(--primary);
    cursor:pointer;
}

/* HERO */

.hero {
    min-height:450px;
    display:flex;
    align-items:center;
    padding:80px 7%;
    color:white;
    background:
    linear-gradient(90deg,rgba(7,72,88,.96),rgba(7,107,121,.72)),
    url('https://images.unsplash.com/photo-1586773860418-d37222d8fce3?w=1600') center/cover;
}

.hero-content {
    max-width:700px;
}

.hero-tag {
    display:inline-block;
    padding:9px 18px;
    background:rgba(255,255,255,.15);
    border:1px solid rgba(255,255,255,.3);
    border-radius:30px;
    font-size:12px;
    letter-spacing:1px;
    margin-bottom:22px;
}

.hero h1 {
    font-family:'Playfair Display',serif;
    font-size:55px;
    line-height:1.2;
    margin-bottom:20px;
}

.hero h1 span {
    color:#8ff0e8;
}

.hero p {
    color:#e1f5f5;
    font-size:14px;
    line-height:1.9;
    max-width:590px;
    margin-bottom:28px;
}

.hero-btn {
    display:inline-block;
    padding:14px 28px;
    background:white;
    color:var(--primary);
    border-radius:30px;
    font-weight:600;
    font-size:14px;
    transition:.3s;
}

.hero-btn:hover {
    background:#d7ffff;
    transform:translateY(-4px);
}

/* STATS */

.stats {
    padding:35px 7%;
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:20px;
    background:white;
    box-shadow:0 5px 20px rgba(0,0,0,.04);
}

.stat {
    display:flex;
    align-items:center;
    justify-content:center;
    gap:15px;
    border-right:1px solid #e5eeee;
}

.stat:last-child {
    border:none;
}

.stat i {
    width:52px;
    height:52px;
    background:var(--light);
    color:var(--primary);
    display:grid;
    place-items:center;
    border-radius:14px;
    font-size:23px;
}

.stat h3 {
    font-size:22px;
}

.stat p {
    color:var(--text);
    font-size:12px;
}

/* SECTION HEADING */

.section {
    padding:80px 7%;
}

.section-heading {
    text-align:center;
    margin-bottom:40px;
}

.section-heading span {
    color:var(--primary);
    font-size:12px;
    font-weight:700;
    letter-spacing:2px;
}

.section-heading h2 {
    font-family:'Playfair Display',serif;
    font-size:36px;
    margin:12px 0;
}

.section-heading p {
    color:var(--text);
    font-size:13px;
    line-height:1.8;
}

/* SEARCH */

.search-section {
    padding:55px 7% 20px;
    background:#f4fafa;
}

.search-wrap {
    max-width:850px;
    margin:auto;
    position:relative;
}

.search-box {
    background:white;
    padding:10px 12px;
    border-radius:15px;
    display:flex;
    align-items:center;
    gap:10px;
    box-shadow:0 8px 25px rgba(0,0,0,.06);
    border:2px solid transparent;
    transition:border-color .3s, box-shadow .3s;
}

.search-box:focus-within {
    border-color:var(--primary);
    box-shadow:0 8px 30px rgba(8,126,139,.18);
}

.search-box i.search-icon {
    color:var(--primary);
    font-size:20px;
    margin-left:12px;
}

.search-box input {
    flex:1;
    border:none;
    outline:none;
    padding:12px;
    font-family:inherit;
    font-size:14px;
    min-width:0;
    color:var(--dark);
}

.clear-btn {
    background:none;
    border:none;
    color:#8fa1b3;
    font-size:16px;
    cursor:pointer;
    padding:8px 10px;
    border-radius:50%;
    display:none;
    align-items:center;
    justify-content:center;
    transition:.2s;
}

.clear-btn:hover {
    color:var(--primary);
    background:#eaf8f8;
}

.search-box button.search-submit {
    background:var(--primary);
    color:white;
    border:none;
    padding:14px 25px;
    border-radius:10px;
    font-family:inherit;
    cursor:pointer;
    transition:.3s;
    font-weight:600;
    white-space:nowrap;
}

.search-box button.search-submit:hover {
    background:var(--dark);
}

.search-suggestions {
    position:absolute;
    top:calc(100% + 8px);
    left:0;
    right:0;
    background:white;
    border-radius:14px;
    box-shadow:0 15px 40px rgba(0,0,0,.12);
    border:1px solid #e2edee;
    max-height:320px;
    overflow-y:auto;
    z-index:999;
}

.suggestion-item {
    padding:13px 18px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    cursor:pointer;
    border-bottom:1px solid #f2f7f7;
    transition:background .2s;
}

.suggestion-item:last-child {
    border-bottom:none;
}

.suggestion-item:hover {
    background:#f2fafb;
}

.suggestion-main {
    display:flex;
    align-items:center;
    gap:12px;
}

.suggestion-icon {
    width:34px;
    height:34px;
    border-radius:8px;
    background:var(--light);
    color:var(--primary);
    display:grid;
    place-items:center;
    font-size:15px;
}

.suggestion-name {
    font-weight:600;
    font-size:13px;
    color:var(--dark);
}

.suggestion-area {
    font-size:11px;
    color:var(--text);
}

.suggestion-badge {
    font-size:10px;
    background:var(--light);
    color:var(--primary);
    padding:4px 8px;
    border-radius:6px;
    font-weight:500;
}

.filter-pills {
    max-width:850px;
    margin:18px auto 0;
    display:flex;
    flex-wrap:wrap;
    gap:8px;
    justify-content:center;
}

.pill {
    background:white;
    border:1px solid #dbe7e7;
    color:var(--dark);
    padding:7px 15px;
    border-radius:20px;
    font-size:12px;
    font-family:inherit;
    font-weight:500;
    cursor:pointer;
    transition:.25s;
}

.pill:hover,
.pill.active {
    background:var(--primary);
    color:white;
    border-color:var(--primary);
}

.search-note {
    text-align:center;
    color:var(--text);
    font-size:12px;
    margin-top:17px;
}

.hospital-card.highlight {
    animation:pulseBorder 1.5s ease-out;
}

@keyframes pulseBorder {
    0% { transform:scale(1.02); box-shadow:0 0 0 4px rgba(8,126,139,.5); }
    100% { transform:scale(1); box-shadow:0 15px 35px rgba(8,126,139,.12); }
}

/* HOSPITAL CARDS */

.hospitals-section {
    background:#f4fafa;
    padding:35px 7% 85px;
}

.hospital-grid {
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:25px;
}

.hospital-card {
    background:white;
    border-radius:18px;
    overflow:hidden;
    border:1px solid #e6eeee;
    transition:.35s;
    display:flex;
    flex-direction:column;
    height:100%;
}

.hospital-card:hover {
    transform:translateY(-9px);
    box-shadow:0 15px 35px rgba(8,126,139,.12);
}

/* Scroll Animation */
.scroll-animate {
    opacity: 0;
    transform: translateY(50px);
    transition: opacity 0.85s cubic-bezier(0.16, 1, 0.3, 1), transform 0.85s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.3s ease;
    will-change: opacity, transform;
}
.scroll-animate.is-revealed {
    opacity: 1;
    transform: translateY(0);
}
.hospital-card.scroll-animate.is-revealed:hover {
    transform: translateY(-9px) !important;
    box-shadow: 0 15px 35px rgba(8,126,139,.12);
}
.facility-card.scroll-animate.is-revealed:hover {
    transform: translateY(-8px) !important;
}

.hospital-image {
    height:210px;
    position:relative;
    overflow:hidden;
}

.hospital-image img {
    width:100%;
    height:100%;
    object-fit:cover;
    transition:.5s;
}

.hospital-card:hover .hospital-image img {
    transform:scale(1.08);
}

.hospital-label {
    position:absolute;
    top:15px;
    left:15px;
    background:#087e8b;
    color:white;
    padding:8px 13px;
    border-radius:20px;
    font-size:10px;
    font-weight:600;
}

.hospital-content {
    padding:24px;
    display:flex;
    flex-direction:column;
    flex:1;
}

.hospital-content h3 {
    font-size:17px;
    margin-bottom:12px;
}

.hospital-location {
    display:flex;
    align-items:flex-start;
    gap:9px;
    color:var(--text);
    font-size:12px;
    line-height:1.8;
    margin-bottom:16px;
}

.hospital-location i {
    color:var(--primary);
    margin-top:4px;
}

.hospital-features {
    display:flex;
    flex-wrap:wrap;
    gap:8px;
    margin-bottom:20px;
}

.hospital-features span {
    background:var(--light);
    color:var(--primary);
    padding:7px 10px;
    border-radius:7px;
    font-size:10px;
    font-weight:500;
}

.hospital-footer {
    border-top:1px solid #edf1f1;
    padding-top:17px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-top:auto;
}

.hospital-footer small {
    color:var(--text);
    font-size:11px;
}

.hospital-footer a {
    background:var(--primary);
    color:white;
    padding:10px 14px;
    border-radius:8px;
    font-size:11px;
    font-weight:600;
    transition:.3s;
}

.hospital-footer a:hover {
    background:var(--dark);
}

.hospital-footer .btn-book-disabled {
    background:#e2e8f0;
    color:#94a3b8;
    padding:10px 14px;
    border-radius:8px;
    font-size:11px;
    font-weight:600;
    cursor:not-allowed;
    display:inline-flex;
    align-items:center;
    gap:5px;
    border:1px solid #cbd5e1;
}

.no-result {
    display:none;
    text-align:center;
    grid-column:1/-1;
    padding:50px;
    background:white;
    border-radius:15px;
    color:var(--text);
}

/* FACILITIES */

.facility-section {
    padding:80px 7%;
}

.facility-grid {
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:22px;
}

.facility-card {
    text-align:center;
    padding:30px 18px;
    border:1px solid #e6eeee;
    border-radius:16px;
    transition:.3s;
}

.facility-card:hover {
    background:#f0fafa;
    transform:translateY(-7px);
}

.facility-card i {
    color:var(--primary);
    font-size:30px;
    margin-bottom:17px;
}

.facility-card h3 {
    font-size:15px;
    margin-bottom:10px;
}

.facility-card p {
    color:var(--text);
    font-size:12px;
    line-height:1.8;
}

/* HOSPITAL REGISTRATION */

.register-section {
    padding:75px 7%;
    background:#f4fafa;
}

.register-box {
    background:linear-gradient(110deg,#087e8b,#13a6a6);
    color:white;
    padding:55px;
    border-radius:22px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:30px;
}

.register-box h2 {
    font-family:'Playfair Display',serif;
    font-size:32px;
    margin-bottom:13px;
}

.register-box p {
    color:#e2ffff;
    font-size:13px;
    line-height:1.9;
    max-width:600px;
}

.register-box a {
    background:white;
    color:var(--primary);
    padding:14px 25px;
    border-radius:30px;
    font-size:13px;
    font-weight:600;
    white-space:nowrap;
    transition:.3s;
}

.register-box a:hover {
    background:#d7ffff;
    transform:translateY(-3px);
}

/* FOOTER */

.footer {
    background:#092d42;
    color:white;
    padding:55px 7% 20px;
}

.footer-grid {
    display:grid;
    grid-template-columns:1.5fr 1fr 1fr 1fr;
    gap:35px;
    padding-bottom:35px;
}

.footer-logo {
    font-family:'Playfair Display',serif;
    font-size:20px;
    font-weight:700;
    margin-bottom:16px;
}

.footer p {
    color:#c3d4df;
    font-size:12px;
    line-height:1.9;
}

.footer h3 {
    font-size:16px;
    margin-bottom:20px;
}

.footer-links {
    display:flex;
    flex-direction:column;
    gap:12px;
}

.footer-links a {
    color:#c3d4df;
    font-size:13px;
    transition:.3s;
}

.footer-links a:hover {
    color:#38d1d1;
    padding-left:5px;
}

.footer-contact p {
    margin-bottom:12px;
}

.footer-contact i {
    color:#38d1d1;
    margin-right:8px;
}

.socials {
    display:flex;
    gap:10px;
    margin-top:18px;
}

.socials a {
    width:35px;
    height:35px;
    border-radius:50%;
    background:#16475d;
    display:grid;
    place-items:center;
}

.socials a:hover {
    background:var(--secondary);
}

.footer-bottom {
    border-top:1px solid #345368;
    padding-top:20px;
    display:flex;
    justify-content:space-between;
    gap:15px;
    font-size:12px;
}

/* RESPONSIVE */

@media(max-width:1050px) {
    .nav-links {
        gap:13px;
    }

    .hospital-grid {
        grid-template-columns:repeat(2,1fr);
    }

    .facility-grid {
        grid-template-columns:repeat(2,1fr);
    }

    .footer-grid {
        grid-template-columns:repeat(2,1fr);
    }
}

@media(max-width:768px) {
    .topbar {
        padding:10px 5%;
    }

    .navbar {
        padding:15px 5%;
    }

    .menu-toggle {
        display:block;
    }

    .nav-links {
        display:none;
        position:absolute;
        top:100%;
        left:0;
        width:100%;
        background:white;
        padding:22px 7%;
        flex-direction:column;
        align-items:flex-start;
    }

    .nav-links.show {
        display:flex;
    }

    .hero {
        padding:65px 6%;
    }

    .hero h1 {
        font-size:42px;
    }

    .stats {
        padding:30px 5%;
    }

    .stat {
        flex-direction:column;
        text-align:center;
    }

    .register-box {
        flex-direction:column;
        align-items:flex-start;
        padding:35px;
    }
}

@media(max-width:500px) {
    .top-info span:nth-child(2) {
        display:none;
    }

    .logo-text {
        font-size:15px;
    }

    .logo-icon {
        width:43px;
        height:43px;
    }

    .hero h1 {
        font-size:34px;
    }

    .hero {
        min-height:400px;
    }

    .stats {
        grid-template-columns:1fr;
        gap:25px;
    }

    .stat {
        flex-direction:row;
        justify-content:flex-start;
        text-align:left;
        border-right:none;
    }

    .section,
    .facility-section {
        padding:55px 6%;
    }

    .hospital-grid,
    .facility-grid,
    .footer-grid {
        grid-template-columns:1fr;
    }

    .search-section {
        padding:40px 6% 15px;
    }

    .search-box {
        flex-wrap:wrap;
    }

    .search-box input {
        width:70%;
        flex:auto;
    }

    .search-box button {
        width:100%;
    }

    .section-heading h2 {
        font-size:29px;
    }

    .register-box h2 {
        font-size:27px;
    }

    .register-box {
        padding:30px 22px;
    }

    .footer-bottom {
        flex-direction:column;
    }
}
</style>
</head>

<body>

<!-- HEADER & NAVBAR -->
<?php include 'components/header.php'; ?>
<!-- HERO -->

<section class="hero">

    <div class="hero-content">

        <span class="hero-tag">
            <i class="fa-solid fa-hospital"></i>
            OUR HEALTHCARE NETWORK
        </span>

        <h1>
            Find The Right
            <span>Hospital For Your Child</span>
        </h1>

        <p>
            Explore hospitals available on our vaccination
            management platform. Find hospital information,
            check available services and request vaccination
            appointments with ease.
        </p>

        <a href="#hospital-list" class="hero-btn">
            Explore Hospitals
            <i class="fa-solid fa-arrow-right"></i>
        </a>

    </div>

</section>

<!-- STATS -->

<section class="stats">

    <div class="stat">
        <i class="fa-solid fa-hospital"></i>
        <div>
            <h3>Registered</h3>
            <p>Hospital Network</p>
        </div>
    </div>

    <div class="stat">
        <i class="fa-solid fa-syringe"></i>
        <div>
            <h3>Vaccination</h3>
            <p>Service Information</p>
        </div>
    </div>

    <div class="stat">
        <i class="fa-solid fa-calendar-check"></i>
        <div>
            <h3>Easy Booking</h3>
            <p>Appointment Requests</p>
        </div>
    </div>

</section>

<!-- SEARCH -->

<section class="search-section" id="hospital-list">

    <div class="section-heading">
        <span>FIND HEALTHCARE IN KARACHI</span>
        <h2>Hospitals &amp; Vaccination Centers in Karachi</h2>
        <p>Explore recognized vaccination centers and child healthcare hospitals across Karachi.</p>
    </div>

    <div class="search-wrap">
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass search-icon"></i>

            <input
                type="text"
                id="hospitalSearch"
                placeholder="Search hospital name or area (e.g. Aga Khan, Indus, Gulshan, Clifton)..."
                autocomplete="off"
            >

            <button type="button" id="clearSearch" class="clear-btn" title="Clear search">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <button type="button" class="search-submit" onclick="searchHospitals(true)">
                Search Hospitals
            </button>
        </div>

        <!-- AUTOCOMPLETE DROPDOWN -->
        <div class="search-suggestions" id="searchSuggestions" style="display:none;"></div>
    </div>

    <!-- QUICK FILTER PILLS -->
    <div class="filter-pills" id="filterPills">
        <button type="button" class="pill active" data-filter="">All Hospitals</button>
        <button type="button" class="pill" data-filter="Aga Khan">Aga Khan</button>
        <button type="button" class="pill" data-filter="Indus">Indus Hospital</button>
        <button type="button" class="pill" data-filter="NICH">NICH</button>
        <button type="button" class="pill" data-filter="Civil">Civil Hospital</button>
        <button type="button" class="pill" data-filter="Ziauddin">Ziauddin</button>
        <button type="button" class="pill" data-filter="Gulshan">Gulshan-e-Iqbal</button>
        <button type="button" class="pill" data-filter="Clifton">Clifton</button>
        <button type="button" class="pill" data-filter="Saddar">Saddar</button>
    </div>

    <p class="search-note" id="searchCountNote">
        <i class="fa-solid fa-circle-info"></i>
        <span id="resultsCount">Showing all 12 hospitals in Karachi, Sindh.</span>
    </p>

</section>

<!-- HOSPITAL LIST -->

<section class="hospitals-section">

    <div class="hospital-grid" id="hospitalGrid">

        <!-- HOSPITAL 1 -->
        <div class="hospital-card" data-search="aga khan university hospital akuh stadium road karachi premier pediatric vaccination neonatal icu">
            <div class="hospital-image">
                <img src="https://images.unsplash.com/photo-1586773860418-d37222d8fce3?w=800" alt="Aga Khan University Hospital">
                <span class="hospital-label">Premier Healthcare</span>
            </div>
            <div class="hospital-content">
                <h3>Aga Khan University Hospital (AKUH)</h3>
                <div class="hospital-location">
                    <i class="fa-solid fa-location-dot"></i>
                    <span>Stadium Road, Karachi, Sindh</span>
                </div>
                <div class="hospital-features">
                    <span>Pediatric Vaccination</span>
                    <span>Neonatal ICU</span>
                    <span>24/7 Emergency</span>
                </div>
                <div class="hospital-footer">
                    <small><i class="fa-solid fa-clock"></i> 24/7 Vaccination Desk</small>
                    <?= renderHospitalBookBtn(2, 'Aga Khan University Hospital (AKUH)', $isParent, $hospAvailability) ?>
                </div>
            </div>
        </div>

        <!-- HOSPITAL 2 -->
        <div class="hospital-card" data-search="national institute of child health nich rafiqui shaheed road karachi specialized child care free vaccines pediatric icu">
            <div class="hospital-image">
                <img src="https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=800" alt="National Institute of Child Health">
                <span class="hospital-label">Specialized Child Care</span>
            </div>
            <div class="hospital-content">
                <h3>National Institute of Child Health (NICH)</h3>
                <div class="hospital-location">
                    <i class="fa-solid fa-location-dot"></i>
                    <span>Rafiqui Shaheed Road, Karachi</span>
                </div>
                <div class="hospital-features">
                    <span>Free Child Vaccines</span>
                    <span>Pediatric ICU</span>
                    <span>Infant Immunization</span>
                </div>
                <div class="hospital-footer">
                    <small><i class="fa-solid fa-clock"></i> Mon - Sat: 8:00 AM - 3:00 PM</small>
                    <?= renderHospitalBookBtn(3, 'National Institute of Child Health (NICH)', $isParent, $hospAvailability) ?>
                </div>
            </div>
        </div>

        <!-- HOSPITAL 3 -->
        <div class="hospital-card" data-search="the indus hospital korangi sector 39 karachi free healthcare child immunization pediatric ward">
            <div class="hospital-image">
                <img src="https://images.unsplash.com/photo-1516549655169-df83a0774514?w=800" alt="The Indus Hospital">
                <span class="hospital-label">100% Free Care</span>
            </div>
            <div class="hospital-content">
                <h3>The Indus Hospital &amp; Health Network</h3>
                <div class="hospital-location">
                    <i class="fa-solid fa-location-dot"></i>
                    <span>Korangi Sector 39, Karachi</span>
                </div>
                <div class="hospital-features">
                    <span>Child Immunization</span>
                    <span>Pediatric Ward</span>
                    <span>Free Vaccination</span>
                </div>
                <div class="hospital-footer">
                    <small><i class="fa-solid fa-clock"></i> Mon - Sat: 8:30 AM - 3:30 PM</small>
                    <?= renderHospitalBookBtn(4, 'The Indus Hospital & Health Network', $isParent, $hospAvailability) ?>
                </div>
            </div>
        </div>

        <!-- HOSPITAL 4 -->
        <div class="hospital-card" data-search="dr ruth pfau civil hospital baba e urdu road saddar karachi government tertiary care epi vaccine">
            <div class="hospital-image">
                <img src="https://images.unsplash.com/photo-1538108149393-fbbd81895907?w=800" alt="Civil Hospital Karachi">
                <span class="hospital-label">Government Hospital</span>
            </div>
            <div class="hospital-content">
                <h3>Dr. Ruth K. M. Pfau Civil Hospital</h3>
                <div class="hospital-location">
                    <i class="fa-solid fa-location-dot"></i>
                    <span>Baba-e-Urdu Road, Saddar, Karachi</span>
                </div>
                <div class="hospital-features">
                    <span>EPI Vaccine Center</span>
                    <span>Child Health Clinic</span>
                    <span>24/7 Emergency</span>
                </div>
                <div class="hospital-footer">
                    <small><i class="fa-solid fa-clock"></i> 24/7 Emergency &amp; OPD</small>
                    <?= renderHospitalBookBtn(5, 'Dr. Ruth K. M. Pfau Civil Hospital', $isParent, $hospAvailability) ?>
                </div>
            </div>
        </div>

        <!-- HOSPITAL 5 -->
        <div class="hospital-card" data-search="liaquat national hospital lnh national stadium road karachi multi specialty pediatric routine vaccines">
            <div class="hospital-image">
                <img src="https://images.unsplash.com/photo-1551076805-e1869033e561?w=800" alt="Liaquat National Hospital">
                <span class="hospital-label">Multi-Specialty</span>
            </div>
            <div class="hospital-content">
                <h3>Liaquat National Hospital (LNH)</h3>
                <div class="hospital-location">
                    <i class="fa-solid fa-location-dot"></i>
                    <span>National Stadium Road, Karachi</span>
                </div>
                <div class="hospital-features">
                    <span>Pediatric Center</span>
                    <span>Routine Vaccines</span>
                    <span>Child OPD</span>
                </div>
                <div class="hospital-footer">
                    <small><i class="fa-solid fa-clock"></i> Mon - Sat: 9:00 AM - 5:00 PM</small>
                    <?= renderHospitalBookBtn(6, 'Liaquat National Hospital (LNH)', $isParent, $hospAvailability) ?>
                </div>
            </div>
        </div>

        <!-- HOSPITAL 6 -->
        <div class="hospital-card" data-search="south city hospital block 3 clifton karachi private healthcare child wellness preventive vaccines">
            <div class="hospital-image">
                <img src="https://images.unsplash.com/photo-1587351021759-3e566b6af7cc?w=800" alt="South City Hospital">
                <span class="hospital-label">Private Healthcare</span>
            </div>
            <div class="hospital-content">
                <h3>South City Hospital</h3>
                <div class="hospital-location">
                    <i class="fa-solid fa-location-dot"></i>
                    <span>Block 3, Clifton, Karachi</span>
                </div>
                <div class="hospital-features">
                    <span>Child Wellness</span>
                    <span>Preventive Vaccines</span>
                    <span>Pediatric Specialists</span>
                </div>
                <div class="hospital-footer">
                    <small><i class="fa-solid fa-clock"></i> Mon - Sat: 9:00 AM - 8:00 PM</small>
                    <?= renderHospitalBookBtn(7, 'South City Hospital', $isParent, $hospAvailability) ?>
                </div>
            </div>
        </div>

        <!-- HOSPITAL 7 -->
        <div class="hospital-card" data-search="dr ziauddin hospital north nazimabad clifton block b karachi accredited hospital infant vaccines">
            <div class="hospital-image">
                <img src="https://images.unsplash.com/photo-1579684385127-1ef15d508118?w=800" alt="Dr. Ziauddin Hospital">
                <span class="hospital-label">Accredited Hospital</span>
            </div>
            <div class="hospital-content">
                <h3>Dr. Ziauddin Hospital</h3>
                <div class="hospital-location">
                    <i class="fa-solid fa-location-dot"></i>
                    <span>North Nazimabad &amp; Clifton, Karachi</span>
                </div>
                <div class="hospital-features">
                    <span>Infant Vaccines</span>
                    <span>Child Clinic</span>
                    <span>Immunization Center</span>
                </div>
                <div class="hospital-footer">
                    <small><i class="fa-solid fa-clock"></i> Mon - Sun: 8:00 AM - 10:00 PM</small>
                    <?= renderHospitalBookBtn(8, 'Dr. Ziauddin Hospital', $isParent, $hospAvailability) ?>
                </div>
            </div>
        </div>

        <!-- HOSPITAL 8 -->
        <div class="hospital-card" data-search="patel hospital block 4 gulshan e iqbal karachi community care child vaccination pediatric doctors">
            <div class="hospital-image">
                <img src="https://images.unsplash.com/photo-1505751172876-fa1923c5c528?w=800" alt="Patel Hospital">
                <span class="hospital-label">Community Care</span>
            </div>
            <div class="hospital-content">
                <h3>Patel Hospital</h3>
                <div class="hospital-location">
                    <i class="fa-solid fa-location-dot"></i>
                    <span>Block 4, Gulshan-e-Iqbal, Karachi</span>
                </div>
                <div class="hospital-features">
                    <span>Child Vaccination</span>
                    <span>Pediatric Doctors</span>
                    <span>General Care</span>
                </div>
                <div class="hospital-footer">
                    <small><i class="fa-solid fa-clock"></i> Mon - Sat: 9:00 AM - 6:00 PM</small>
                    <?= renderHospitalBookBtn(9, 'Patel Hospital', $isParent, $hospAvailability) ?>
                </div>
            </div>
        </div>

        <!-- HOSPITAL 9 -->
        <div class="hospital-card" data-search="al shifa children hospital main university road gulshan e iqbal karachi child care pediatric polio epi">
            <div class="hospital-image">
                <img src="https://images.unsplash.com/photo-1629909613654-28e377c37b09?w=800" alt="Al Shifa Children Hospital">
                <span class="hospital-label">Child Care</span>
            </div>
            <div class="hospital-content">
                <h3>Al-Shifa Children Hospital</h3>
                <div class="hospital-location">
                    <i class="fa-solid fa-location-dot"></i>
                    <span>Main University Road, Gulshan-e-Iqbal, Karachi</span>
                </div>
                <div class="hospital-features">
                    <span>Pediatric Care</span>
                    <span>Polio &amp; EPI Vaccines</span>
                    <span>Child Health</span>
                </div>
                <div class="hospital-footer">
                    <small><i class="fa-solid fa-clock"></i> Mon - Sat: 9:00 AM - 5:00 PM</small>
                    <?= renderHospitalBookBtn(10, 'Al-Shifa Children Hospital', $isParent, $hospAvailability) ?>
                </div>
            </div>
        </div>

        <!-- HOSPITAL 10 -->
        <div class="hospital-card" data-search="city care hospital pechs block 2 shahrah e faisal karachi polio routine epi child healthcare">
            <div class="hospital-image">
                <img src="https://images.unsplash.com/photo-1512678080530-7760d81faba6?w=800" alt="City Care Hospital">
                <span class="hospital-label">Featured Hospital</span>
            </div>
            <div class="hospital-content">
                <h3>City Care Hospital</h3>
                <div class="hospital-location">
                    <i class="fa-solid fa-location-dot"></i>
                    <span>PECHS Block 2, Shahrah-e-Faisal, Karachi</span>
                </div>
                <div class="hospital-features">
                    <span>Child Healthcare</span>
                    <span>Polio Vaccination</span>
                    <span>Routine EPI</span>
                </div>
                <div class="hospital-footer">
                    <small><i class="fa-solid fa-clock"></i> Mon - Sat: 9:00 AM - 7:00 PM</small>
                    <?= renderHospitalBookBtn(11, 'City Care Hospital', $isParent, $hospAvailability) ?>
                </div>
            </div>
        </div>

        <!-- HOSPITAL 11 -->
        <div class="hospital-card" data-search="al mustafa medical centre block 13-c gulshan e iqbal karachi welfare medical low cost vaccines routine epi">
            <div class="hospital-image">
                <img src="https://images.unsplash.com/photo-1584515979956-d9f6e5d09982?w=800" alt="Al-Mustafa Medical Centre">
                <span class="hospital-label">Welfare Medical Center</span>
            </div>
            <div class="hospital-content">
                <h3>Al-Mustafa Medical Centre</h3>
                <div class="hospital-location">
                    <i class="fa-solid fa-location-dot"></i>
                    <span>Block 13-C, Gulshan-e-Iqbal, Karachi</span>
                </div>
                <div class="hospital-features">
                    <span>Low-Cost Vaccines</span>
                    <span>Child Health</span>
                    <span>Routine EPI Shots</span>
                </div>
                <div class="hospital-footer">
                    <small><i class="fa-solid fa-clock"></i> Mon - Sun: 8:00 AM - 9:00 PM</small>
                    <?= renderHospitalBookBtn(12, 'Al-Mustafa Medical Centre', $isParent, $hospAvailability) ?>
                </div>
            </div>
        </div>

        <!-- HOSPITAL 12 -->
        <div class="hospital-card" data-search="omi hospital depot lines saddar karachi specialized center pediatric nursery vaccinations">
            <div class="hospital-image">
                <img src="https://images.unsplash.com/photo-1532938911079-1b06ac7ceec7?w=800" alt="OMI Hospital">
                <span class="hospital-label">Specialized Center</span>
            </div>
            <div class="hospital-content">
                <h3>OMI Hospital</h3>
                <div class="hospital-location">
                    <i class="fa-solid fa-location-dot"></i>
                    <span>89/1 Depot Lines, Saddar, Karachi</span>
                </div>
                <div class="hospital-features">
                    <span>Pediatric Care</span>
                    <span>Child Vaccinations</span>
                    <span>Nursery &amp; NICU</span>
                </div>
                <div class="hospital-footer">
                    <small><i class="fa-solid fa-clock"></i> Mon - Sat: 9:00 AM - 6:00 PM</small>
                    <?= renderHospitalBookBtn(13, 'OMI Hospital', $isParent, $hospAvailability) ?>
                </div>
            </div>
        </div>

        <div class="no-result" id="noResult">
            <i class="fa-solid fa-hospital" style="font-size:35px;color:#087e8b"></i>
            <h3 style="margin:15px 0">No Hospital Found</h3>
            <p>Try searching with another hospital name or city.</p>
        </div>

    </div>

</section>

<!-- FACILITIES -->

<section class="facility-section">

    <div class="section-heading">
        <span>HOSPITAL FACILITIES</span>
        <h2>Healthcare Services</h2>
        <p>Important services and facilities that parents can explore.</p>
    </div>

    <div class="facility-grid">

        <div class="facility-card">
            <i class="fa-solid fa-baby"></i>
            <h3>Pediatric Care</h3>
            <p>Healthcare services focused on infants and children.</p>
        </div>

        <div class="facility-card">
            <i class="fa-solid fa-syringe"></i>
            <h3>Vaccination Center</h3>
            <p>Information about vaccination services and availability.</p>
        </div>

        <div class="facility-card">
            <i class="fa-solid fa-user-doctor"></i>
            <h3>Medical Assistance</h3>
            <p>Access information about available healthcare services.</p>
        </div>

        <div class="facility-card">
            <i class="fa-solid fa-calendar-check"></i>
            <h3>Appointment Support</h3>
            <p>Manage vaccination appointment requests conveniently.</p>
        </div>

    </div>

</section>

<!-- HOSPITAL REGISTRATION CTA -->

<section class="register-section">

    <div class="register-box">

        <div>
            <h2>Are You A Hospital Administrator?</h2>

            <p>
                Join our vaccination management platform.
                Register your hospital, maintain vaccine
                availability information and manage
                vaccination appointments.
            </p>
        </div>

        <a href="register.php?role=hospital">
            Register Your Hospital
            <i class="fa-solid fa-arrow-right"></i>
        </a>

    </div>

</section>

<!-- FOOTER -->
<?php include 'components/footer.php'; ?>

<!-- JAVASCRIPT -->

<script>

// MOBILE NAVBAR

const menuToggle = document.getElementById("menuToggle");
const navLinks = document.getElementById("navLinks");

menuToggle.addEventListener("click", function() {

    navLinks.classList.toggle("show");

    const icon = menuToggle.querySelector("i");

    if(navLinks.classList.contains("show")) {
        icon.classList.replace("fa-bars","fa-xmark");
    } else {
        icon.classList.replace("fa-xmark","fa-bars");
    }

});

// HOSPITAL SEARCH & LIVE FILTER
const searchInput = document.getElementById("hospitalSearch");
const clearBtn = document.getElementById("clearSearch");
const hospitalCards = document.querySelectorAll(".hospital-card");
const noResult = document.getElementById("noResult");
const suggestionsBox = document.getElementById("searchSuggestions");
const resultsCount = document.getElementById("resultsCount");
const filterPills = document.querySelectorAll(".filter-pills .pill");

function searchHospitals(shouldScroll = false) {
    const searchValue = searchInput ? searchInput.value.toLowerCase().trim() : "";

    // Toggle clear button
    if (clearBtn) {
        clearBtn.style.display = searchValue.length > 0 ? "flex" : "none";
    }

    let found = 0;

    hospitalCards.forEach(function(card) {
        const title = card.querySelector("h3") ? card.querySelector("h3").textContent.toLowerCase() : "";
        const location = card.querySelector(".hospital-location") ? card.querySelector(".hospital-location").textContent.toLowerCase() : "";
        const features = card.querySelector(".hospital-features") ? card.querySelector(".hospital-features").textContent.toLowerCase() : "";
        const searchAttr = (card.dataset.search || "").toLowerCase();

        const combined = `${title} ${location} ${features} ${searchAttr}`;

        if (!searchValue || combined.includes(searchValue)) {
            card.style.display = ""; // restores CSS display: flex
            found++;
        } else {
            card.style.display = "none";
        }
    });

    if (noResult) {
        noResult.style.display = found === 0 ? "block" : "none";
    }

    if (resultsCount) {
        if (!searchValue) {
            resultsCount.textContent = `Showing all ${found} hospitals in Karachi, Sindh.`;
        } else {
            resultsCount.textContent = `Found ${found} hospital${found === 1 ? '' : 's'} matching "${searchValue}".`;
        }
    }

    // Update active pill
    filterPills.forEach(function(p) {
        if ((!searchValue && p.dataset.filter === "") || (searchValue && p.dataset.filter.toLowerCase() === searchValue)) {
            p.classList.add("active");
        } else {
            p.classList.remove("active");
        }
    });

    // Populate auto suggestions
    renderSuggestions(searchValue);

    if (shouldScroll) {
        const gridSection = document.getElementById("hospitalGrid");
        if (gridSection) {
            gridSection.scrollIntoView({ behavior: "smooth", block: "start" });
        }
        if (suggestionsBox) suggestionsBox.style.display = "none";
    }
}

function renderSuggestions(searchValue) {
    if (!suggestionsBox) return;
    if (!searchValue || searchValue.length < 1) {
        suggestionsBox.style.display = "none";
        suggestionsBox.innerHTML = "";
        return;
    }

    let matches = [];
    hospitalCards.forEach(function(card) {
        const title = card.querySelector("h3") ? card.querySelector("h3").textContent : "";
        const location = card.querySelector(".hospital-location span") ? card.querySelector(".hospital-location span").textContent : "";
        const label = card.querySelector(".hospital-label") ? card.querySelector(".hospital-label").textContent : "";
        const searchAttr = (card.dataset.search || "").toLowerCase();
        const combined = `${title} ${location} ${searchAttr}`.toLowerCase();

        if (combined.includes(searchValue)) {
            matches.push({ title, location, label, card });
        }
    });

    if (matches.length === 0) {
        suggestionsBox.style.display = "none";
        suggestionsBox.innerHTML = "";
        return;
    }

    suggestionsBox.innerHTML = matches.slice(0, 6).map(function(m) {
        const safeTitle = m.title.replace(/"/g, '&quot;');
        return `
            <div class="suggestion-item" data-title="${safeTitle}">
                <div class="suggestion-main">
                    <div class="suggestion-icon"><i class="fa-solid fa-hospital"></i></div>
                    <div>
                        <div class="suggestion-name">${m.title}</div>
                        <div class="suggestion-area"><i class="fa-solid fa-location-dot" style="margin-right:4px;"></i>${m.location}</div>
                    </div>
                </div>
                <span class="suggestion-badge">${m.label}</span>
            </div>
        `;
    }).join("");

    suggestionsBox.style.display = "block";

    // Bind suggestion clicks
    suggestionsBox.querySelectorAll(".suggestion-item").forEach(function(item) {
        item.addEventListener("click", function() {
            const chosenTitle = this.getAttribute("data-title");
            searchInput.value = chosenTitle;
            suggestionsBox.style.display = "none";
            searchHospitals(true);

            // Highlight the chosen card
            hospitalCards.forEach(function(card) {
                const cardTitle = card.querySelector("h3") ? card.querySelector("h3").textContent : "";
                if (cardTitle === chosenTitle) {
                    card.classList.add("highlight");
                    setTimeout(() => card.classList.remove("highlight"), 2000);
                }
            });
        });
    });
}

// Event Listeners
if (searchInput) {
    searchInput.addEventListener("input", function() { searchHospitals(false); });
    searchInput.addEventListener("keydown", function(e) {
        if (e.key === "Enter") {
            e.preventDefault();
            searchHospitals(true);
            if (suggestionsBox) suggestionsBox.style.display = "none";
        }
    });
}

if (clearBtn) {
    clearBtn.addEventListener("click", function() {
        searchInput.value = "";
        searchHospitals(false);
        searchInput.focus();
    });
}

// Pill clicks
filterPills.forEach(function(pill) {
    pill.addEventListener("click", function() {
        const filterVal = this.dataset.filter;
        searchInput.value = filterVal;
        searchHospitals(filterVal !== "");
    });
});

// Close suggestions on outside click
document.addEventListener("click", function(e) {
    if (suggestionsBox && !e.target.closest(".search-wrap")) {
        suggestionsBox.style.display = "none";
    }
});

// Scroll Animation
document.addEventListener("DOMContentLoaded", function () {
    const cardsToAnimate = document.querySelectorAll(".hospital-card, .facility-card");
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
            { threshold: 0.12, rootMargin: "0px 0px -40px 0px" }
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
