
<?php
session_start();
require_once __DIR__ . '/../config.php';

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'parent') {
    header("Location: ../login.php");
    exit;
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$hospitals = [];

$result = $conn->query(
    "SELECT id, hospital_name, city, address, phone, availability
     FROM hospitals
     WHERE LOWER(status) = 'approved'
     ORDER BY hospital_name ASC"
);

while ($row = $result->fetch_assoc()) {
    if (empty($row['availability'])) {
        $row['availability'] = 'Available';
    }
    $hospitals[] = $row;
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

<title>Find Hospitals | Vaccination Management System</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>
*{margin:0;padding:0;box-sizing:border-box}

body{
    font-family:Poppins,sans-serif;
    background:#eaf8f8;
    color:#536477;
}

header{
    background:white;
    padding:18px 6%;
    display:flex;
    justify-content:space-between;
    align-items:center;
    box-shadow:0 3px 15px #102b4610;
}

.logo{
    font-family:'Playfair Display',serif;
    font-size:22px;
    color:#087e8b;
    font-weight:bold;
}

.logo i{margin-right:8px}

.header-links{
    display:flex;
    gap:12px;
}

.header-links a{
    background:#087e8b;
    color:white;
    text-decoration:none;
    padding:10px 16px;
    border-radius:7px;
    font-size:13px;
}

.container{
    width:88%;
    max-width:1200px;
    margin:40px auto;
}

.heading{
    margin-bottom:25px;
}

.heading h1{
    color:#102b46;
    font-size:28px;
}

.heading p{margin-top:7px}

.search-box{
    background:white;
    padding:20px;
    border-radius:12px;
    box-shadow:0 5px 20px #102b4610;
    margin-bottom:28px;
}

.search-box input{
    width:100%;
    padding:15px 18px;
    border:1px solid #dce6e9;
    border-radius:8px;
    outline:none;
    font-family:inherit;
    font-size:14px;
}

.search-box input:focus{
    border-color:#087e8b;
}

.hospital-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(280px,1fr));
    gap:22px;
}

.hospital-card{
    background:white;
    border-radius:14px;
    padding:25px;
    box-shadow:0 5px 20px #102b4610;
    transition:.3s;
}

.hospital-card:hover{
    transform:translateY(-5px);
    box-shadow:0 10px 25px #102b4620;
}

.hospital-icon{
    width:58px;
    height:58px;
    display:flex;
    justify-content:center;
    align-items:center;
    border-radius:12px;
    background:#eaf8f8;
    color:#087e8b;
    font-size:26px;
    margin-bottom:18px;
}

.hospital-card h2{
    color:#102b46;
    font-size:19px;
    margin-bottom:13px;
}

.info{
    display:flex;
    gap:10px;
    align-items:flex-start;
    font-size:13px;
    margin:12px 0;
    line-height:1.7;
}

.info i{
    color:#087e8b;
    margin-top:4px;
    min-width:15px;
}

.status{
    display:inline-block;
    background:#d8f5e5;
    color:#176b40;
    padding:6px 12px;
    border-radius:20px;
    font-size:11px;
    font-weight:600;
    margin-top:8px;
}

.book-btn{
    display:block;
    text-align:center;
    text-decoration:none;
    background:#087e8b;
    color:white;
    padding:12px;
    border-radius:7px;
    margin-top:20px;
    font-size:13px;
    font-weight:600;
    transition:.3s;
}

.book-btn:hover{background:#102b46}

.empty{
    background:white;
    padding:50px 20px;
    border-radius:14px;
    text-align:center;
    grid-column:1/-1;
}

.empty i{
    color:#13a6a6;
    font-size:45px;
    margin-bottom:15px;
}

.empty h2{
    color:#102b46;
    margin-bottom:8px;
}

.no-result{
    display:none;
    background:white;
    padding:30px;
    text-align:center;
    border-radius:12px;
    grid-column:1/-1;
}

footer{
    background:#092d42;
    color:white;
    text-align:center;
    padding:22px;
    margin-top:50px;
}

@media(max-width:650px){
    .container{width:92%}
    header{padding:15px 4%}
    .logo{font-size:16px}
    .header-links a{font-size:11px;padding:9px}
    .heading h1{font-size:23px}
}
</style>
</head>

<body>

<header>
    <div class="logo">
        <i class="fa-solid fa-shield-heart"></i>
        Vaccination Management
    </div>

    <div class="header-links">
        <a href="dashboard.php">
            <i class="fa-solid fa-gauge"></i> Dashboard
        </a>

        <a href="../logout.php">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
    </div>
</header>

<main class="container">

    <div class="heading">
        <h1><i class="fa-solid fa-hospital"></i> Find Hospitals</h1>
        <p>Find approved hospitals for your child's vaccination services.</p>
    </div>

    <div class="search-box">
        <input
            type="text"
            id="searchHospital"
            placeholder="Search by hospital name or city..."
            onkeyup="searchHospitals()"
        >
    </div>

    <div class="hospital-grid" id="hospitalGrid">

        <?php if (!empty($hospitals)): ?>

            <?php foreach ($hospitals as $hospital): ?>

                <div class="hospital-card">

                    <div class="hospital-icon">
                        <i class="fa-solid fa-hospital"></i>
                    </div>

                    <h2><?= e($hospital['hospital_name']) ?></h2>

                    <div class="info">
                        <i class="fa-solid fa-location-dot"></i>
                        <span><?= e($hospital['city']) ?></span>
                    </div>

                    <div class="info">
                        <i class="fa-solid fa-map-location-dot"></i>
                        <span><?= e($hospital['address'] ?: 'Address not provided') ?></span>
                    </div>

                    <div class="info">
                        <i class="fa-solid fa-phone"></i>
                        <span><?= e($hospital['phone'] ?: 'Phone not provided') ?></span>
                    </div>

                    <?php if (($hospital['availability'] ?? 'Available') === 'Available'): ?>
                        <span class="status">
                            <i class="fa-solid fa-circle-check"></i> Available
                        </span>
                        <a href="appointments.php" class="book-btn">
                            <i class="fa-solid fa-calendar-check"></i>
                            Book Appointment
                        </a>
                    <?php else: ?>
                        <span class="status" style="background:#fee2e2;color:#991b1b;">
                            <i class="fa-solid fa-circle-xmark"></i> Unavailable
                        </span>
                        <button type="button" class="book-btn" disabled style="background:#cbd5e1;color:#64748b;cursor:not-allowed;pointer-events:none;border:none;">
                            <i class="fa-solid fa-ban"></i> Booking Off
                        </button>
                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

            <div class="no-result" id="noResult">
                <i class="fa-solid fa-magnifying-glass"></i>
                <h3>No Hospital Found</h3>
                <p>Try searching with another hospital name or city.</p>
            </div>

        <?php else: ?>

            <div class="empty">
                <i class="fa-solid fa-hospital"></i>
                <h2>No Approved Hospitals Available</h2>
                <p>Approved hospitals will appear here when available.</p>
            </div>

        <?php endif; ?>

    </div>

</main>

<footer>
    © <?= date('Y') ?> Vaccination Management System. All Rights Reserved.
</footer>

<script>
function searchHospitals(){

    let input = document.getElementById("searchHospital").value.toLowerCase();

    let cards = document.querySelectorAll(".hospital-card");
    let noResult = document.getElementById("noResult");

    let visible = 0;

    cards.forEach(function(card){

        let text = card.innerText.toLowerCase();

        if(text.includes(input)){
            card.style.display = "block";
            visible++;
        }else{
            card.style.display = "none";
        }

    });

    if(noResult){
        noResult.style.display = visible === 0 ? "block" : "none";
    }
}
</script>

</body>
</html>
