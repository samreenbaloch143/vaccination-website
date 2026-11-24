<?php
// Shared Footer Component
$footerYear = date("Y");
?>
<!-- SHARED FOOTER STYLES (Guaranteed on all pages) -->
<style>
.footer {
    background: #092d42;
    color: #ffffff;
    padding: 60px 7% 20px;
    font-family: 'Poppins', sans-serif;
    margin-top: auto;
    width: 100%;
    box-sizing: border-box;
}

.footer-grid {
    display: grid;
    grid-template-columns: 1.5fr 1fr 1fr 1fr;
    gap: 35px;
    padding-bottom: 40px;
    max-width: 1300px;
    margin: 0 auto;
}

.footer-logo {
    display: inline-flex;
    align-items: center;
    gap: 14px;
    text-decoration: none;
    margin-bottom: 18px;
}

.footer-logo-icon {
    width: 48px;
    height: 48px;
    border: 2.5px solid #13a6a6;
    color: #13a6a6;
    background: rgba(19, 166, 166, 0.12);
    border-radius: 15px;
    display: grid;
    place-items: center;
    font-size: 24px;
    flex-shrink: 0;
    transition: all 0.3s ease;
}

.footer-logo:hover .footer-logo-icon {
    background: #13a6a6;
    color: #ffffff;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(19, 166, 166, 0.35);
}

.footer-logo-text {
    font-family: 'Playfair Display', serif;
    font-size: 19px;
    line-height: 1.2;
    font-weight: 700;
    color: #ffffff;
}

.footer h3 {
    font-size: 16px;
    margin-bottom: 20px;
    color: #ffffff;
    font-weight: 600;
}

.footer p {
    color: #c3d4df;
    font-size: 12.5px;
    line-height: 1.9;
    margin: 0;
}

.footer-links {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.footer-links a {
    color: #c3d4df;
    font-size: 13px;
    text-decoration: none;
    transition: all 0.25s ease;
    display: inline-block;
}

.footer-links a:hover {
    color: #38d1d1;
    padding-left: 5px;
}

.footer-contact p {
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 10px;
    color: #c3d4df;
    font-size: 12.5px;
}

.footer-contact p:last-child {
    margin-bottom: 0;
}

.footer-contact i {
    color: #31c1c1;
    font-size: 14px;
    width: 18px;
    text-align: center;
    flex-shrink: 0;
}

.socials {
    display: flex;
    gap: 10px;
    margin-top: 18px;
}

.socials a {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: grid;
    place-items: center;
    background: #16475d;
    color: #ffffff;
    text-decoration: none;
    font-size: 14px;
    transition: all 0.25s ease;
}

.socials a:hover {
    background: #13a6a6;
    color: #ffffff;
    transform: translateY(-2px);
}

.footer-bottom {
    border-top: 1px solid #345368;
    padding-top: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    color: #c3d4df;
    font-size: 12px;
    max-width: 1300px;
    margin: 0 auto;
}

.footer-bottom p {
    color: #c3d4df;
    font-size: 12px;
    margin: 0;
}

@media (max-width: 1000px) {
    .footer-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 30px;
    }
}

@media (max-width: 600px) {
    .footer {
        padding: 45px 5% 20px;
    }
    .footer-grid {
        grid-template-columns: 1fr;
        gap: 25px;
    }
    .footer-bottom {
        flex-direction: column;
        text-align: center;
        gap: 10px;
    }
}
</style>

<!-- FOOTER -->
<footer class="footer">

    <div class="footer-grid">

        <div>
            <a href="index.php" class="footer-logo">
                <div class="footer-logo-icon">
                    <i class="fa-solid fa-shield-heart"></i>
                </div>
                <div class="footer-logo-text">
                    Vaccination<br>Management System
                </div>
            </a>

            <p>
                Making vaccination management easier,
                safer and more accessible for parents,
                hospitals and healthcare administrators.
            </p>

            <div class="socials">
                <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                <a href="#" aria-label="Twitter"><i class="fa-brands fa-x-twitter"></i></a>
                <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                <a href="#" aria-label="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
            </div>
        </div>

        <div>
            <h3>Quick Links</h3>
            <div class="footer-links">
                <a href="index.php">Home</a>
                <a href="about.php">About Us</a>
                <a href="services.php">Services</a>
                <a href="hospital.php">Hospitals</a>
                <a href="vaccine.php">Vaccines</a>
                <a href="contact.php">Contact Us</a>
            </div>
        </div>

        <div>
            <h3>Account</h3>
            <div class="footer-links">
                <a href="login.php">Login</a>
                <a href="register.php">Parent Registration</a>
                <a href="parent/dashboard.php">Parent Dashboard</a>
                <a href="hospital/dashboard.php">Hospital Panel</a>
            </div>
        </div>

        <div class="footer-contact">
            <h3>Contact Us</h3>
            <p><i class="fa-solid fa-phone"></i> +92 321 1234567</p>
            <p><i class="fa-solid fa-envelope"></i> info@vaccination.com</p>
            <p><i class="fa-solid fa-location-dot"></i> Karachi, Pakistan</p>
        </div>

    </div>

    <div class="footer-bottom">
        <p>
            &copy; <?php echo $footerYear; ?> Vaccination Management System. All Rights Reserved.
        </p>

        <p>Healthy Child &bull; Healthy Nation</p>
    </div>

</footer>
