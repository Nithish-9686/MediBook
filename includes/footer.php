<footer>
    <div class="footer-inner">
        <div class="footer-top">
            <div>
                <a href="/medibook/index.php" class="logo">
                    <div class="logo-mark">M</div>
                    MediBook
                </a>
                <p class="footer-desc">India's trusted platform for finding and booking top-rated doctors by expertise, experience, and verified patient reviews.</p>
            </div>

            <div class="footer-col">
                <p class="footer-col-title">Platform</p>
                <ul>
                    <li><a href="/medibook/doctors.php">Find Doctors</a></li>
                    <li><a href="/medibook/subscribe.php">Pricing</a></li>
                    <li><a href="/medibook/index.php#how">How It Works</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <p class="footer-col-title">Account</p>
                <ul>
                    <li><a href="/medibook/login.php">Log In</a></li>
                    <li><a href="/medibook/signup.php">Sign Up</a></li>
                    <li><a href="/medibook/dashboard.php">My Appointments</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <p class="footer-col-title">Legal</p>
                <ul>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms of Service</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p class="footer-copy">© <?= date('Y') ?> MediBook. All rights reserved.</p>
            <div class="footer-legal">
                <a href="#">Privacy</a>
                <a href="#">Terms</a>
                <a href="#">Support</a>
            </div>
        </div>
    </div>
</footer>

<!-- Scroll reveal -->
<script>
    const io = new IntersectionObserver(entries => {
        entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('in'); });
    }, { threshold: 0.08 });
    document.querySelectorAll('.fade').forEach(el => io.observe(el));
</script>
