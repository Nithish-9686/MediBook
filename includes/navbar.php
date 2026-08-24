<?php
// Determine active page for nav highlighting
$current = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar">
    <div class="nav-inner">
        <a href="/medibook/index.php" class="logo">
            <div class="logo-mark">M</div>
            MediBook
        </a>

        <ul class="nav-links">
            <li><a href="/medibook/index.php"          <?= $current === 'index.php'   ? 'class="active"' : '' ?>>Home</a></li>
            <li><a href="/medibook/doctors.php"        <?= $current === 'doctors.php' ? 'class="active"' : '' ?>>Find Doctors</a></li>
            <li><a href="/medibook/subscribe.php"      <?= $current === 'subscribe.php' ? 'class="active"' : '' ?>>Pricing</a></li>
            <?php if (isset($_SESSION['user_id'])): ?>
            <li><a href="/medibook/dashboard.php"      <?= $current === 'dashboard.php' ? 'class="active"' : '' ?>>My Appointments</a></li>
            <?php endif; ?>
        </ul>

        <div class="nav-actions">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span class="nav-user">Hi, <?= htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]) ?></span>
                <a href="/medibook/logout.php" class="btn btn-ghost btn-sm">Log out</a>
            <?php else: ?>
                <a href="/medibook/login.php"  class="btn btn-ghost btn-sm">Log in</a>
                <a href="/medibook/signup.php" class="btn btn-primary btn-sm">Sign up</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
