<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$pageTitle = "Contact Us";

$success = false;
$errors  = [];
$old     = ['name' => '', 'email' => '', 'phone' => '', 'subject' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    foreach ($old as $key => $v) {
        $old[$key] = trim($_POST[$key] ?? '');
    }

    if (strlen($old['name']) < 3) {
        $errors[] = "Please enter your full name.";
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }
    if ($old['phone'] !== '' && !preg_match('/^[0-9+\-\s]{7,16}$/', $old['phone'])) {
        $errors[] = "Please enter a valid phone number.";
    }
    if ($old['subject'] === '') {
        $errors[] = "Please select a subject.";
    }
    if (strlen($old['message']) < 10) {
        $errors[] = "Your message should be at least 10 characters.";
    }

    if (empty($errors)) {

        // ---- OPTION 1: Email bhejna (server par mail configure ho to uncomment karein) ----
        // $to      = "info@vaccination.com";
        // $subject = "Contact Form: " . $old['subject'];
        // $body    = "Name: {$old['name']}\nEmail: {$old['email']}\nPhone: {$old['phone']}\n\n{$old['message']}";
        // mail($to, $subject, $body, "From: " . $old['email']);

        // ---- OPTION 2: Database mein save karna (aapka $conn / PDO yahan use karein) ----
        // $stmt = $conn->prepare("INSERT INTO contact_messages (name,email,phone,subject,message) VALUES (?,?,?,?,?)");
        // $stmt->bind_param("sssss", $old['name'], $old['email'], $old['phone'], $old['subject'], $old['message']);
        // $stmt->execute();

        $success = true;
        $old = ['name' => '', 'email' => '', 'phone' => '', 'subject' => '', 'message' => ''];
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
.menu-toggle { display:none; background:none; border:none; font-size:25px; color:var(--primary); cursor:pointer; }

/* HERO */
.hero {
    min-height:380px; display:flex; align-items:center; padding:80px 7%; color:white;
    background:
    linear-gradient(90deg,rgba(7,72,88,.96),rgba(7,107,121,.72)),
    url('https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=1600') center/cover;
}
.hero-content { max-width:700px; }
.hero-tag { display:inline-block; padding:9px 18px; background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.3); border-radius:30px; font-size:12px; letter-spacing:1px; margin-bottom:22px; }
.hero h1 { font-family:'Playfair Display',serif; font-size:55px; line-height:1.2; margin-bottom:20px; }
.hero h1 span { color:#8ff0e8; }
.hero p { color:#e1f5f5; font-size:14px; line-height:1.9; max-width:590px; margin-bottom:28px; }
.hero-btn { display:inline-block; padding:14px 28px; background:white; color:var(--primary); border-radius:30px; font-weight:600; font-size:14px; transition:.3s; }
.hero-btn:hover { background:#d7ffff; transform:translateY(-4px); }

/* SECTION HEADING */
.section-heading { text-align:center; margin-bottom:40px; }
.section-heading span { color:var(--primary); font-size:12px; font-weight:700; letter-spacing:2px; }
.section-heading h2 { font-family:'Playfair Display',serif; font-size:36px; margin:12px 0; }
.section-heading p { color:var(--text); font-size:13px; line-height:1.8; }

/* CONTACT INFO CARDS */
.info-section { padding:80px 7% 40px; }
.info-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:22px; }
.info-card { text-align:center; padding:32px 20px; border:1px solid #e6eeee; border-radius:16px; transition:.3s; background:white; }
.info-card:hover { background:#f0fafa; transform:translateY(-7px); }
.info-card .icon { width:62px; height:62px; margin:0 auto 18px; background:var(--light); color:var(--primary); display:grid; place-items:center; border-radius:18px; font-size:25px; }
.info-card h3 { font-size:15px; margin-bottom:10px; }
.info-card p { color:var(--text); font-size:12px; line-height:1.8; }

/* FORM + MAP */
.contact-section { padding:40px 7% 85px; background:#f4fafa; }
.contact-wrap { display:grid; grid-template-columns:1.2fr 1fr; gap:30px; margin-top:20px; }

.form-box { background:white; padding:38px; border-radius:20px; border:1px solid #e6eeee; box-shadow:0 8px 25px rgba(0,0,0,.05); }
.form-box h3 { font-family:'Playfair Display',serif; font-size:26px; margin-bottom:6px; }
.form-box > p { color:var(--text); font-size:12px; margin-bottom:25px; }

.form-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.form-group { margin-bottom:16px; }
.form-group label { display:block; font-size:12px; font-weight:600; margin-bottom:7px; }

.form-group input,
.form-group select,
.form-group textarea {
    width:100%; padding:13px 15px; border:1px solid #dbe6e6; border-radius:10px;
    font-family:inherit; font-size:13px; color:var(--dark); outline:none; background:#fbfefe; transition:.3s;
}
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus { border-color:var(--primary); box-shadow:0 0 0 4px rgba(8,126,139,.1); }
.form-group textarea { resize:vertical; min-height:130px; }

.submit-btn {
    background:var(--primary); color:white; border:none; padding:15px 30px; border-radius:30px;
    font-family:inherit; font-size:14px; font-weight:600; cursor:pointer; transition:.3s;
}
.submit-btn:hover { background:var(--dark); transform:translateY(-3px); }

.alert { padding:15px 18px; border-radius:10px; font-size:13px; line-height:1.7; margin-bottom:22px; }
.alert i { margin-right:8px; }
.alert-success { background:#e6f8f0; color:#0b6b43; border:1px solid #b6e8d1; }
.alert-error { background:#fdeeee; color:#a12626; border:1px solid #f4c4c4; }
.alert-error ul { margin:6px 0 0 20px; }

.side-box { display:flex; flex-direction:column; gap:20px; }
.map-box { border-radius:20px; overflow:hidden; border:1px solid #e6eeee; min-height:300px; flex:1; }
.map-box iframe { width:100%; height:100%; min-height:300px; border:0; display:block; }

.hours-box { background:linear-gradient(110deg,#087e8b,#13a6a6); color:white; padding:28px; border-radius:20px; }
.hours-box h3 { font-family:'Playfair Display',serif; font-size:21px; margin-bottom:16px; }
.hours-box li { list-style:none; display:flex; justify-content:space-between; font-size:13px; padding:9px 0; border-bottom:1px solid rgba(255,255,255,.2); }
.hours-box li:last-child { border:none; }

/* FAQ */
.faq-section { padding:80px 7%; }
.faq-list { max-width:850px; margin:auto; }
.faq-item { border:1px solid #e6eeee; border-radius:14px; margin-bottom:14px; overflow:hidden; }
.faq-q {
    width:100%; background:white; border:none; padding:19px 22px; display:flex; justify-content:space-between; align-items:center;
    font-family:inherit; font-size:14px; font-weight:600; color:var(--dark); cursor:pointer; text-align:left; gap:15px;
}
.faq-q i { color:var(--primary); transition:.3s; }
.faq-a { max-height:0; overflow:hidden; transition:max-height .35s ease; }
.faq-a p { padding:0 22px 20px; color:var(--text); font-size:13px; line-height:1.9; }
.faq-item.open .faq-a { max-height:200px; }
.faq-item.open .faq-q i { transform:rotate(45deg); }

/* FOOTER */
.footer { background:#092d42; color:white; padding:55px 7% 20px; }
.footer-grid { display:grid; grid-template-columns:1.5fr 1fr 1fr 1fr; gap:35px; padding-bottom:35px; }
.footer-logo { font-family:'Playfair Display',serif; font-size:20px; font-weight:700; margin-bottom:16px; }
.footer p { color:#c3d4df; font-size:12px; line-height:1.9; }
.footer h3 { font-size:16px; margin-bottom:20px; }
.footer-links { display:flex; flex-direction:column; gap:12px; }
.footer-links a { color:#c3d4df; font-size:13px; transition:.3s; }
.footer-links a:hover { color:#38d1d1; padding-left:5px; }
.footer-contact p { margin-bottom:12px; }
.footer-contact i { color:#38d1d1; margin-right:8px; }
.socials { display:flex; gap:10px; margin-top:18px; }
.socials a { width:35px; height:35px; border-radius:50%; background:#16475d; display:grid; place-items:center; }
.socials a:hover { background:var(--secondary); }
.footer-bottom { border-top:1px solid #345368; padding-top:20px; display:flex; justify-content:space-between; gap:15px; font-size:12px; }

/* RESPONSIVE */
@media(max-width:1050px) {
    .nav-links { gap:13px; }
    .info-grid { grid-template-columns:repeat(2,1fr); }
    .contact-wrap { grid-template-columns:1fr; }
    .footer-grid { grid-template-columns:repeat(2,1fr); }
}

@media(max-width:768px) {
    .topbar { padding:10px 5%; }
    .navbar { padding:15px 5%; }
    .menu-toggle { display:block; }
    .nav-links { display:none; position:absolute; top:100%; left:0; width:100%; background:white; padding:22px 7%; flex-direction:column; align-items:flex-start; }
    .nav-links.show { display:flex; }
    .hero { padding:65px 6%; }
    .hero h1 { font-size:42px; }
}

@media(max-width:500px) {
    .top-info span:nth-child(2) { display:none; }
    .logo-text { font-size:15px; }
    .logo-icon { width:43px; height:43px; }
    .hero h1 { font-size:34px; }
    .hero { min-height:340px; }
    .info-section, .faq-section { padding:55px 6% 30px; }
    .contact-section { padding:30px 6% 60px; }
    .info-grid, .footer-grid, .form-row { grid-template-columns:1fr; gap:16px; }
    .form-box { padding:26px 20px; }
    .section-heading h2 { font-size:29px; }
    .footer-bottom { flex-direction:column; }
}
</style>
</head>

<body>

<!-- HEADER & NAVBAR -->
<?php include 'components/header.php'; ?>

<!-- HERO -->
<section class="hero">
    <div class="hero-content">
        <span class="hero-tag"><i class="fa-solid fa-headset"></i> GET IN TOUCH</span>

        <h1>We're Here <span>To Help You</span></h1>

        <p>
            Have a question about vaccination schedules, hospital registration
            or your account? Send us a message and our team will get back to you.
        </p>

        <a href="#contact-form" class="hero-btn">
            Send A Message
            <i class="fa-solid fa-arrow-right"></i>
        </a>
    </div>
</section>

<!-- CONTACT INFO -->
<section class="info-section">

    <div class="section-heading">
        <span>CONTACT INFORMATION</span>
        <h2>Reach Us Anytime</h2>
        <p>Choose the way that is most convenient for you.</p>
    </div>

    <div class="info-grid">

        <div class="info-card">
            <div class="icon"><i class="fa-solid fa-phone"></i></div>
            <h3>Call Us</h3>
            <p>+92 321 1234567<br>+92 21 1234567</p>
        </div>

        <div class="info-card">
            <div class="icon"><i class="fa-solid fa-envelope"></i></div>
            <h3>Email Us</h3>
            <p>info@vaccination.com<br>support@vaccination.com</p>
        </div>

        <div class="info-card">
            <div class="icon"><i class="fa-solid fa-location-dot"></i></div>
            <h3>Visit Us</h3>
            <p>Main Office<br>Karachi, Sindh, Pakistan</p>
        </div>

        <div class="info-card">
            <div class="icon"><i class="fa-solid fa-clock"></i></div>
            <h3>Working Hours</h3>
            <p>Mon - Sat: 9:00 AM - 6:00 PM<br>Sunday: Closed</p>
        </div>

    </div>

</section>

<!-- FORM + MAP -->
<section class="contact-section" id="contact-form">

    <div class="contact-wrap">

        <!-- FORM -->
        <div class="form-box">

            <h3>Send Us A Message</h3>
            <p>Fill in the form below and we will reply as soon as possible.</p>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-circle-check"></i>
                    Thank you! Your message has been sent. We will contact you soon.
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-circle-exclamation"></i> Please fix the following:
                    <ul>
                        <?php foreach ($errors as $err): ?>
                            <li><?php echo e($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="contact.php#contact-form" novalidate>

                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" placeholder="Your name" value="<?php echo e($old['name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" placeholder="you@example.com" value="<?php echo e($old['email']); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" placeholder="+92 3XX XXXXXXX" value="<?php echo e($old['phone']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="subject">Subject *</label>
                        <select id="subject" name="subject" required>
                            <option value="">Select a subject</option>
                            <?php
                            $subjects = ["Vaccination Appointment", "Vaccine Information", "Hospital Registration", "Account Problem", "Other"];
                            foreach ($subjects as $s) {
                                $sel = ($old['subject'] === $s) ? 'selected' : '';
                                echo "<option value=\"" . e($s) . "\" $sel>" . e($s) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="message">Message *</label>
                    <textarea id="message" name="message" placeholder="Write your message here..." required><?php echo e($old['message']); ?></textarea>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fa-solid fa-paper-plane"></i> Send Message
                </button>

            </form>

        </div>

        <!-- MAP + HOURS -->
        <div class="side-box">

            <div class="map-box">
                <iframe
                    src="https://www.google.com/maps?q=Karachi,Pakistan&output=embed"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    title="Our location on map"></iframe>
            </div>

            <div class="hours-box">
                <h3>Office Hours</h3>
                <ul>
                    <li><span>Monday - Friday</span><span>9:00 AM - 6:00 PM</span></li>
                    <li><span>Saturday</span><span>9:00 AM - 2:00 PM</span></li>
                    <li><span>Sunday</span><span>Closed</span></li>
                </ul>
            </div>

        </div>

    </div>

</section>

<!-- FAQ -->
<section class="faq-section">

    <div class="section-heading">
        <span>FAQ</span>
        <h2>Common Questions</h2>
        <p>Quick answers before you contact us.</p>
    </div>

    <div class="faq-list">

        <div class="faq-item">
            <button class="faq-q">How do I book a vaccination appointment? <i class="fa-solid fa-plus"></i></button>
            <div class="faq-a"><p>Register or log in as a parent, add your child's profile, choose a hospital and send an appointment request from your dashboard.</p></div>
        </div>

        <div class="faq-item">
            <button class="faq-q">How can my hospital join the platform? <i class="fa-solid fa-plus"></i></button>
            <div class="faq-a"><p>Hospital administrators can use the "Register Your Hospital" option on the Hospitals page. Our team reviews each request before approval.</p></div>
        </div>

        <div class="faq-item">
            <button class="faq-q">I forgot my password. What should I do? <i class="fa-solid fa-plus"></i></button>
            <div class="faq-a"><p>Use the forgot password option on the login page, or send us a message with the subject "Account Problem" and we will help you recover access.</p></div>
        </div>

        <div class="faq-item">
            <button class="faq-q">How long does it take to get a reply? <i class="fa-solid fa-plus"></i></button>
            <div class="faq-a"><p>We usually respond within one working day.</p></div>
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

// FAQ ACCORDION
document.querySelectorAll(".faq-q").forEach(function(btn) {
    btn.addEventListener("click", function() {
        btn.parentElement.classList.toggle("open");
    });
});

</script>

</body>
</html>