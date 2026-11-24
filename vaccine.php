<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config.php';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$isParent = (!empty($_SESSION['role']) && $_SESSION['role'] === 'parent');

// Load live vaccines from DB
$dbVaccines = [];
try {
    $res = $conn->query("SELECT id, name, recommended_age, description, status FROM vaccines WHERE name != 'zaid' ORDER BY id ASC");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            if (empty($r['status'])) {
                $r['status'] = 'Available';
            }
            $dbVaccines[] = $r;
        }
    }
} catch (Throwable $t) {}

// Curated metadata dictionary by keywords
$curatedMeta = [
    'bcg' => [
        'img' => 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?auto=format&fit=crop&w=800&q=80',
        'category' => 'EPI Routine Vaccine',
        'protection' => 'Childhood TB',
        'admin' => 'Single Dose (Intradermal)',
    ],
    'polio' => [
        'img' => 'https://images.unsplash.com/photo-1603398938378-e54eab446dde?auto=format&fit=crop&w=800&q=80',
        'category' => 'Essential Protection',
        'protection' => 'Poliovirus 1, 2, 3',
        'admin' => 'Oral Drops & Injectable',
    ],
    'hepatitis b' => [
        'img' => 'https://images.unsplash.com/photo-1631815589968-fdb09a223b1e?auto=format&fit=crop&w=800&q=80',
        'category' => 'EPI Routine Vaccine',
        'protection' => 'Hepatitis B Virus',
        'admin' => 'Intramuscular',
    ],
    'pentavalent' => [
        'img' => 'https://images.unsplash.com/photo-1615461066841-6116e61058f4?auto=format&fit=crop&w=800&q=80',
        'category' => 'Combined Core Vaccine',
        'protection' => '5 Core Diseases',
        'admin' => 'Intramuscular',
    ],
    'pneumococcal' => [
        'img' => 'https://images.unsplash.com/photo-1584982751601-97dcc096659c?auto=format&fit=crop&w=800&q=80',
        'category' => 'EPI Routine Vaccine',
        'protection' => 'Pneumonia & Meningitis',
        'admin' => 'Intramuscular',
    ],
    'pcv' => [
        'img' => 'https://images.unsplash.com/photo-1584982751601-97dcc096659c?auto=format&fit=crop&w=800&q=80',
        'category' => 'EPI Routine Vaccine',
        'protection' => 'Pneumonia & Meningitis',
        'admin' => 'Intramuscular',
    ],
    'rotavirus' => [
        'img' => 'https://images.unsplash.com/photo-1584515933487-779824d29309?auto=format&fit=crop&w=800&q=80',
        'category' => 'Essential Infant Vaccine',
        'protection' => 'Rotaviral Diarrhea',
        'admin' => 'Oral Drops',
    ],
    'measles' => [
        'img' => 'https://images.unsplash.com/photo-1576091160550-2173dba999ef?auto=format&fit=crop&w=800&q=80',
        'category' => 'Critical Protection',
        'protection' => 'Measles & Rubella',
        'admin' => 'Subcutaneous Injection',
    ],
    'typhoid' => [
        'img' => 'https://images.unsplash.com/photo-1584515979956-d9f6e5d09982?auto=format&fit=crop&w=800&q=80',
        'category' => 'Pakistan EPI Standard',
        'protection' => 'Typhoid Fever',
        'admin' => 'Intramuscular',
    ],
    'dtp' => [
        'img' => 'https://images.unsplash.com/photo-1584017911766-d451b3d0e843?auto=format&fit=crop&w=800&q=80',
        'category' => 'Immunity Booster',
        'protection' => 'Diphtheria, Tetanus, Pertussis',
        'admin' => 'Intramuscular',
    ],
    'varicella' => [
        'img' => 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=800&q=80',
        'category' => 'Pediatric Recommended',
        'protection' => 'Chickenpox Virus',
        'admin' => 'Subcutaneous',
    ],
    'chickenpox' => [
        'img' => 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=800&q=80',
        'category' => 'Pediatric Recommended',
        'protection' => 'Chickenpox Virus',
        'admin' => 'Subcutaneous',
    ],
    'hepatitis a' => [
        'img' => 'https://images.unsplash.com/photo-1579684385127-1ef15d508118?auto=format&fit=crop&w=800&q=80',
        'category' => 'Preventive Child Vaccine',
        'protection' => 'Hepatitis A Virus',
        'admin' => '2-Dose Series (Intramuscular)',
    ],
    'influenza' => [
        'img' => 'https://images.unsplash.com/photo-1527613426441-4da17471b66d?auto=format&fit=crop&w=800&q=80',
        'category' => 'Annual Pediatric Shot',
        'protection' => 'Influenza A & B Strains',
        'admin' => 'Single Annual Dose',
    ],
    'flu' => [
        'img' => 'https://images.unsplash.com/photo-1527613426441-4da17471b66d?auto=format&fit=crop&w=800&q=80',
        'category' => 'Annual Pediatric Shot',
        'protection' => 'Influenza A & B Strains',
        'admin' => 'Single Annual Dose',
    ]
];

function getVaccineMeta($name, $desc, $curatedMeta) {
    $lower = strtolower($name . ' ' . $desc);
    foreach ($curatedMeta as $key => $meta) {
        if (strpos($lower, $key) !== false) {
            return $meta;
        }
    }
    return [
        'img' => 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?auto=format&fit=crop&w=800&q=80',
        'category' => 'Approved Vaccine',
        'protection' => 'Childhood Immunization',
        'admin' => 'Standard Clinical Dose',
    ];
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
<title>Vaccines | E-Vaccination Management System</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Poppins',sans-serif;
}

body{
    background:#f5f9fc;
    color:#26364a;
}

html{scroll-behavior:smooth;}

nav{
    width:100%;
    padding:18px 7%;
    background:#fff;
    display:flex;
    justify-content:space-between;
    align-items:center;
    box-shadow:0 3px 15px rgba(0,0,0,.06);
    position:sticky;
    top:0;
    z-index:1000;
}

.logo{
    font-size:24px;
    font-weight:800;
    color:#087e8b;
}

.logo i{margin-right:7px;}

nav ul{
    display:flex;
    list-style:none;
    gap:25px;
    align-items:center;
}

nav ul a{
    text-decoration:none;
    color:#26364a;
    font-size:14px;
    font-weight:500;
    transition:.3s;
}

nav ul a:hover,
nav ul a.active{color:#0796a5;}

.nav-btn{
    background:#078c99;
    color:white!important;
    padding:11px 20px;
    border-radius:7px;
}

.menu{
    display:none;
    font-size:24px;
    color:#087e8b;
    cursor:pointer;
}

/* HERO */

.hero{
    background:linear-gradient(120deg,rgba(5,35,65,.94),rgba(5,126,139,.88)),
    url('https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?auto=format&fit=crop&w=1600&q=80');
    background-size:cover;
    background-position:center;
    padding:100px 7%;
    color:white;
}

.hero-content{max-width:720px;}

.hero h1{
    font-size:48px;
    line-height:1.3;
    margin-bottom:18px;
}

.hero p{
    font-size:16px;
    line-height:1.8;
    color:#e2f5f7;
    max-width:620px;
}

.hero-btn{
    display:inline-block;
    margin-top:25px;
    padding:13px 25px;
    background:#fff;
    color:#087e8b;
    border-radius:7px;
    text-decoration:none;
    font-weight:600;
}

/* INTRO */

.section{
    padding:75px 7%;
}

.section-heading{
    text-align:center;
    margin-bottom:40px;
}

.section-heading span{
    color:#0796a5;
    font-weight:600;
    font-size:14px;
    text-transform:uppercase;
    letter-spacing:2px;
}

.section-heading h2{
    font-size:34px;
    color:#102d4b;
    margin:10px 0;
}

.section-heading p{
    color:#718096;
    font-size:14px;
}

/* INFO CARDS */

.info-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:22px;
    margin-bottom:65px;
}

.info-card{
    background:white;
    padding:28px;
    border-radius:13px;
    box-shadow:0 5px 25px rgba(0,0,0,.05);
    border:1px solid #edf1f5;
}

.info-card i{
    width:55px;
    height:55px;
    background:#e4f6f6;
    color:#078c99;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:12px;
    font-size:23px;
    margin-bottom:18px;
}

.info-card h3{
    font-size:18px;
    color:#102d4b;
    margin-bottom:10px;
}

.info-card p{
    color:#718096;
    font-size:13px;
    line-height:1.8;
}

/* VACCINE CARDS */

.vaccine-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:25px;
}

.vaccine-card{
    background:white;
    border-radius:14px;
    overflow:hidden;
    box-shadow:0 5px 25px rgba(0,0,0,.06);
    transition:.3s;
    border:1px solid #edf1f5;
    display:flex;
    flex-direction:column;
    height:100%;
    position:relative;
}

.vaccine-card:hover{
    transform:translateY(-7px);
    box-shadow:0 12px 30px rgba(0,0,0,.1);
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
.vaccine-card.scroll-animate.is-revealed:hover {
    transform: translateY(-7px) !important;
    box-shadow: 0 12px 30px rgba(0,0,0,.1);
}
.info-card.scroll-animate.is-revealed:hover {
    transform: translateY(-6px) !important;
}

.vaccine-card.card-unavailable {
    border-color: #fecaca;
    background: #fdfefe;
}

.vaccine-img{
    height:190px;
    overflow:hidden;
    position:relative;
}

.vaccine-img img{
    width:100%;
    height:100%;
    object-fit:cover;
}

.img-avail-tag {
    position: absolute;
    top: 12px;
    right: 12px;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.18);
    backdrop-filter: blur(4px);
    z-index: 2;
}
.img-avail-tag.tag-available {
    background: rgba(230, 248, 240, 0.95);
    color: #0b6b43;
    border: 1px solid #86efac;
}
.img-avail-tag.tag-unavailable {
    background: rgba(254, 242, 242, 0.95);
    color: #991b1b;
    border: 1px solid #fca5a5;
}

.vaccine-content{
    padding:23px;
    display:flex;
    flex-direction:column;
    flex:1;
}

.vaccine-top-badges {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 12px;
}

.avail-badge-indicator {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
}
.badge-indicator-yes {
    background: #e6f8f0;
    color: #0b6b43;
}
.badge-indicator-no {
    background: #fdeeee;
    color: #a12626;
}

.vaccine-content h3{
    font-size:19px;
    color:#102d4b;
    margin-bottom:10px;
    font-weight: 700;
}

.vaccine-content p{
    font-size:13px;
    color:#718096;
    line-height:1.7;
    margin-bottom:15px;
}

.vaccine-details{
    border-top:1px solid #edf1f5;
    padding-top:15px;
    margin-top:10px;
}

.vaccine-details div{
    display:flex;
    justify-content:space-between;
    gap:10px;
    margin-bottom:10px;
    font-size:12px;
}

.vaccine-details strong{color:#26364a;}
.vaccine-details span{color:#718096;text-align:right;}

.status{
    display:inline-block;
    padding:6px 12px;
    border-radius:20px;
    background:#e5f8ed;
    color:#16834c;
    font-size:11px;
    font-weight:600;
}

.vaccine-actions-wrap {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: auto;
    padding-top: 16px;
}

.btn-book-now {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    width: 100%;
    padding: 11px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    border: none;
    transition: all 0.25s ease;
    font-family: inherit;
}
.btn-book-now.active {
    background: linear-gradient(135deg, #087e8b, #13a6a6);
    color: #ffffff;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(8, 126, 139, 0.25);
}
.btn-book-now.active:hover {
    background: linear-gradient(135deg, #066974, #0f8b8b);
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(8, 126, 139, 0.35);
}
.btn-book-now.disabled {
    background: #e2e8f0;
    color: #94a3b8;
    cursor: not-allowed;
    opacity: 0.75;
    pointer-events: none;
    box-shadow: none;
    border: 1px dashed #cbd5e1;
}

.btn-schedule-pill {
    display: block;
    text-align: center;
    background: #eaf8f8;
    color: #087e8b;
    padding: 8px 12px;
    border-radius: 7px;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    transition: 0.2s;
}
.btn-schedule-pill:hover {
    background: #d4f0f0;
    color: #066974;
}

.learn-btn{
    display:block;
    text-align:center;
    background:#078c99;
    color:white;
    text-decoration:none;
    padding:11px;
    border-radius:7px;
    margin-top:auto;
    font-size:13px;
    font-weight:600;
    transition:.3s;
}

.learn-btn:hover{background:#056b77;}

/* SCHEDULE */

.schedule{
    background:#eaf6f8;
}

.schedule-table{
    background:white;
    border-radius:12px;
    overflow:hidden;
    box-shadow:0 5px 20px rgba(0,0,0,.05);
}

table{
    width:100%;
    border-collapse:collapse;
}

th,td{
    padding:17px 20px;
    text-align:left;
    border-bottom:1px solid #edf1f5;
    font-size:13px;
}

th{
    background:#087e8b;
    color:white;
    font-weight:600;
}

td{color:#526174;}

tr:hover td{background:#f5fbfc;}

/* NOTICE */

.notice{
    background:#102d4b;
    padding:55px 7%;
    color:white;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:25px;
    flex-wrap:wrap;
}

.notice h2{
    font-size:27px;
    margin-bottom:10px;
}

.notice p{
    color:#d2e2ed;
    font-size:13px;
    line-height:1.7;
}

.notice a{
    background:#fff;
    color:#087e8b;
    padding:13px 23px;
    text-decoration:none;
    border-radius:7px;
    font-weight:600;
    white-space:nowrap;
}

/* FOOTER */

footer{
    background:#071b30;
    color:white;
    padding:55px 7% 20px;
}

.footer-grid{
    display:grid;
    grid-template-columns:2fr 1fr 1fr 1fr;
    gap:35px;
    margin-bottom:35px;
}

.footer-col h3{
    font-size:17px;
    margin-bottom:18px;
}

.footer-col p{
    color:#b6c5d3;
    font-size:13px;
    line-height:1.8;
}

.footer-col a{
    display:block;
    color:#b6c5d3;
    text-decoration:none;
    font-size:13px;
    margin-bottom:11px;
}

.footer-col a:hover{color:#45c5cd;}

.copyright{
    border-top:1px solid #284056;
    text-align:center;
    padding-top:20px;
    color:#a9b9c8;
    font-size:12px;
}

/* RESPONSIVE */

@media(max-width:950px){
    nav ul{
        display:none;
        position:absolute;
        top:75px;
        left:0;
        width:100%;
        background:white;
        padding:25px 7%;
        flex-direction:column;
        align-items:flex-start;
        box-shadow:0 8px 15px rgba(0,0,0,.08);
    }

    nav ul.show{display:flex;}
    .menu{display:block;}
    .info-grid,.vaccine-grid{grid-template-columns:repeat(2,1fr);}
    .footer-grid{grid-template-columns:repeat(2,1fr);}
}

@media(max-width:600px){
    .hero{padding:75px 7%;}
    .hero h1{font-size:34px;}
    .section{padding:55px 6%;}
    .section-heading h2{font-size:27px;}
    .info-grid,.vaccine-grid,.footer-grid{grid-template-columns:1fr;}
    .schedule-table{overflow-x:auto;}
    table{min-width:600px;}
    .notice h2{font-size:23px;}
}
</style>
</head>

<body>

<!-- HEADER & NAVBAR -->
<?php include 'components/header.php'; ?>

<!-- HERO -->

<section class="hero">
    <div class="hero-content">
        <h1>Protect Your Child With Timely Vaccination</h1>
        <p>
            Explore essential childhood vaccines, understand their importance
            and stay informed about vaccination schedules. Give your child
            a healthier and safer future.
        </p>
        <a href="#vaccines" class="hero-btn">Explore Vaccines <i class="fas fa-arrow-right"></i></a>
    </div>
</section>

<!-- INTRO -->

<section class="section">

    <div class="section-heading">
        <span>Why Vaccination Matters</span>
        <h2>Healthy Children, Healthy Future</h2>
        <p>Vaccination helps protect children against serious preventable diseases.</p>
    </div>

    <div class="info-grid">

        <div class="info-card">
            <i class="fas fa-shield-virus"></i>
            <h3>Disease Protection</h3>
            <p>Vaccines help protect children from dangerous infectious diseases and their complications.</p>
        </div>

        <div class="info-card">
            <i class="fas fa-calendar-check"></i>
            <h3>Timely Vaccination</h3>
            <p>Following the recommended schedule helps children receive vaccines at the appropriate ages.</p>
        </div>

        <div class="info-card">
            <i class="fas fa-child"></i>
            <h3>Better Future</h3>
            <p>Childhood immunization supports healthier communities and protects future generations.</p>
        </div>

    </div>

    <!-- VACCINES -->

    <div class="section-heading" id="vaccines">
        <span>Our Vaccine Information</span>
        <h2>Explore Important Vaccines</h2>
        <p>Learn about common vaccines included in childhood immunization programs.</p>
    </div>

    <div class="vaccine-grid">

        <?php if (!empty($dbVaccines)): ?>
            <?php foreach ($dbVaccines as $v):
                $meta = getVaccineMeta($v['name'], $v['description'], $curatedMeta);
                $isAvailable = ($v['status'] === 'Available');
                if ($isParent) {
                    $bookUrl = "parent/appointments.php?vaccine=" . urlencode($v['name']);
                } else {
                    $bookUrl = "login.php?redirect=" . urlencode("parent/appointments.php?vaccine=" . urlencode($v['name']));
                }
            ?>
                <div class="vaccine-card <?= $isAvailable ? 'card-available' : 'card-unavailable' ?>">
                    <div class="vaccine-img">
                        <img src="<?= e($meta['img']) ?>" alt="<?= e($v['name']) ?>">
                        <span class="img-avail-tag <?= $isAvailable ? 'tag-available' : 'tag-unavailable' ?>">
                            <i class="fa-solid <?= $isAvailable ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i>
                            <?= $isAvailable ? 'Available' : 'Unavailable' ?>
                        </span>
                    </div>
                    <div class="vaccine-content">
                        <div class="vaccine-top-badges">
                            <span class="status"><?= e($meta['category']) ?></span>
                            <span class="avail-badge-indicator <?= $isAvailable ? 'badge-indicator-yes' : 'badge-indicator-no' ?>">
                                <i class="fa-solid <?= $isAvailable ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i>
                                <?= $isAvailable ? 'Available' : 'Unavailable' ?>
                            </span>
                        </div>
                        <h3><?= e($v['name']) ?></h3>
                        <p><?= e($v['description']) ?></p>
                        <div class="vaccine-details">
                            <div><strong>Protection</strong><span><?= e($meta['protection']) ?></span></div>
                            <div><strong>Recommended Age</strong><span><?= e($v['recommended_age'] ?: 'As Directed') ?></span></div>
                            <div><strong>Administration</strong><span><?= e($meta['admin']) ?></span></div>
                        </div>

                        <div class="vaccine-actions-wrap">
                            <?php if ($isAvailable): ?>
                                <a href="<?= e($bookUrl) ?>" class="btn-book-now active" title="Book appointment for <?= e($v['name']) ?>">
                                    <i class="fa-solid fa-calendar-check"></i> Book Now
                                </a>
                            <?php else: ?>
                                <button type="button" class="btn-book-now disabled" disabled title="This vaccine is currently unavailable">
                                    <i class="fa-solid fa-ban"></i> Book Now (Off)
                                </button>
                            <?php endif; ?>
                            <a href="#schedule" class="btn-schedule-pill">View Schedule</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>
</section>

<!-- SCHEDULE -->

<section class="section schedule" id="schedule">

    <div class="section-heading">
        <span>Child Immunization</span>
        <h2>Vaccination Schedule Overview</h2>
        <p>A general overview of childhood immunization milestones.</p>
    </div>

    <div class="schedule-table">
        <table>
            <thead>
                <tr>
                    <th>Child's Age</th>
                    <th>Vaccination Milestone</th>
                    <th>Purpose</th>
                </tr>
            </thead>

            <tbody>
                <tr>
                    <td>At Birth (within 24h)</td>
                    <td><strong>BCG, OPV-0, Hepatitis B</strong> (Birth Dose)</td>
                    <td>Early newborn protection against Childhood TB, Polio &amp; Hepatitis B virus</td>
                </tr>

                <tr>
                    <td>6 Weeks</td>
                    <td><strong>Pentavalent-1, PCV-1, Rotavirus-1, OPV-1</strong></td>
                    <td>First core infant series (DTP, Hep B, Hib, Pneumonia, Diarrhea, Polio)</td>
                </tr>

                <tr>
                    <td>10 Weeks</td>
                    <td><strong>Pentavalent-2, PCV-2, Rotavirus-2, OPV-2</strong></td>
                    <td>Second infant booster series for building active antibody protection</td>
                </tr>

                <tr>
                    <td>14 Weeks</td>
                    <td><strong>Pentavalent-3, PCV-3, IPV-1, OPV-3</strong></td>
                    <td>Completion of primary infant immunization series (Oral &amp; Injectable Polio)</td>
                </tr>

                <tr>
                    <td>9 Months</td>
                    <td><strong>Measles-Rubella (MR-1), Typhoid (TCV), IPV-2</strong></td>
                    <td>Protection against Measles rash, Typhoid (XDR) bacterial fever &amp; Polio</td>
                </tr>

                <tr>
                    <td>12 - 15 Months</td>
                    <td><strong>Measles-Rubella (MR-2), Chickenpox, Hepatitis A</strong></td>
                    <td>Second Measles dose, Varicella blister prevention &amp; Liver protection</td>
                </tr>

                <tr>
                    <td>18 - 24 Months</td>
                    <td><strong>DTP Booster-1, OPV Booster, Hep A Booster</strong></td>
                    <td>Toddler booster doses reinforcing immunity before preschool age</td>
                </tr>

                <tr>
                    <td>4 - 5 Years</td>
                    <td><strong>DTP Booster-2, School Entry Boosters, Annual Flu</strong></td>
                    <td>Pre-school &amp; kindergarten immunization for long-term childhood immunity</td>
                </tr>
            </tbody>
        </table>
    </div>

    <p style="font-size:12px;color:#718096;margin-top:15px;line-height:1.8;">
        Note: This is an illustrative overview, not a complete or personalized
        vaccination schedule. Vaccine schedules and doses can change.
        Always confirm the current Pakistan EPI schedule with an authorized
        healthcare professional or vaccination center.
    </p>

</section>

<!-- NOTICE -->

<section class="notice">
    <div>
        <h2>Don't Miss Your Child's Vaccination</h2>
        <p>Register your child and keep track of upcoming vaccination appointments.</p>
    </div>

    <a href="register.php">Register Your Child <i class="fas fa-arrow-right"></i></a>
</section>

<!-- FOOTER -->
<?php include 'components/footer.php'; ?>

<script>
function toggleMenu(){
    document.getElementById("navLinks").classList.toggle("show");
}

// Scroll Animation
document.addEventListener("DOMContentLoaded", function () {
    const cardsToAnimate = document.querySelectorAll(".vaccine-card, .info-card");
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
