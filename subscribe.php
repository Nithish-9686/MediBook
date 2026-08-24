<?php
session_start();
include 'config/db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /medibook/login.php?redirect=/medibook/subscribe.php");
    exit;
}

$user_id       = $_SESSION['user_id'];
$is_subscribed = $_SESSION['is_subscribed'] == 1;

// Fetch plans from DB
$plans = mysqli_query($conn, "SELECT * FROM plans ORDER BY price ASC");

$success = '';
$error   = '';

// Handle subscription
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['plan_id'])) {
    $plan_id = (int) $_POST['plan_id'];

    // Verify plan exists
    $plan_check = mysqli_query($conn, "SELECT * FROM plans WHERE id = $plan_id");

    if (mysqli_num_rows($plan_check) === 1) {
        // Update user subscription
        $now = date('Y-m-d H:i:s');
        mysqli_query($conn, "
            UPDATE users
            SET is_subscribed = 1, subscribed_at = '$now'
            WHERE id = $user_id
        ");

        // Update session
        $_SESSION['is_subscribed'] = 1;
        $is_subscribed = true;

        $success = 'Subscription activated! You now have full access to book appointments.';
    } else {
        $error = 'Invalid plan selected. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pricing — MediBook</title>
    <link rel="stylesheet" href="/medibook/assets/css/style.css">
    <style>
        /* ── PAGE HEADER ── */
        .pricing-header {
            text-align: center;
            padding: 72px 24px 56px;
            max-width: 600px;
            margin: auto;
        }

        .pricing-header .section-label { margin-bottom: 10px; }

        .pricing-title {
            font-size: 40px;
            font-weight: 700;
            letter-spacing: -0.03em;
            color: var(--navy);
            line-height: 1.15;
        }

        .pricing-sub {
            margin-top: 14px;
            font-size: 16px;
            color: var(--muted);
            line-height: 1.7;
        }

        /* ── ALREADY SUBSCRIBED ── */
        .subscribed-banner {
            max-width: 600px;
            margin: 0 auto 40px;
            padding: 0 24px;
        }

        /* ── PLANS GRID ── */
        .plans-wrap {
            max-width: 820px;
            margin: 0 auto 80px;
            padding: 0 24px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            align-items: start;
        }

        .plan-card {
            background: white;
            border: 1.5px solid var(--border);
            border-radius: 16px;
            padding: 32px 28px;
            position: relative;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .plan-card:hover {
            border-color: var(--blue-bd);
            box-shadow: 0 12px 32px rgba(0,0,0,0.06);
        }

        .plan-card.featured {
            border-color: var(--blue);
            box-shadow: 0 0 0 4px rgba(37,99,235,0.08);
        }

        .plan-tag {
            position: absolute;
            top: -13px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--blue);
            color: white;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            padding: 4px 14px;
            border-radius: 20px;
            white-space: nowrap;
        }

        .plan-name {
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 12px;
        }

        .plan-price {
            font-size: 44px;
            font-weight: 700;
            letter-spacing: -0.04em;
            color: var(--navy);
            line-height: 1;
        }

        .plan-price sup {
            font-size: 20px;
            font-weight: 600;
            vertical-align: super;
            letter-spacing: 0;
        }

        .plan-period {
            font-size: 13px;
            color: var(--muted);
            margin-top: 4px;
        }

        .plan-divider { height: 1px; background: var(--border); margin: 20px 0; }

        .plan-features { display: flex; flex-direction: column; gap: 10px; margin-bottom: 24px; }

        .plan-feature {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 14px;
            color: var(--body);
        }

        .feat-check {
            color: var(--green);
            font-size: 15px;
            flex-shrink: 0;
            margin-top: 1px;
        }

        /* ── FEATURES TABLE ── */
        .features-section {
            max-width: 820px;
            margin: 0 auto 80px;
            padding: 0 24px;
        }

        .features-title {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--navy);
            margin-bottom: 24px;
            text-align: center;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        .feature-item {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 20px;
        }

        .feature-icon { font-size: 24px; margin-bottom: 10px; }

        .feature-name {
            font-size: 14px;
            font-weight: 700;
            color: var(--navy);
        }

        .feature-desc { font-size: 13px; color: var(--muted); margin-top: 4px; line-height: 1.5; }

        /* ── FAQ ── */
        .faq-section {
            max-width: 640px;
            margin: 0 auto 80px;
            padding: 0 24px;
        }

        .faq-title {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--navy);
            margin-bottom: 20px;
            text-align: center;
        }

        .faq-item {
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            margin-bottom: 10px;
            overflow: hidden;
        }

        .faq-q {
            padding: 16px 20px;
            font-size: 14.5px;
            font-weight: 600;
            color: var(--navy);
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            user-select: none;
            background: white;
            transition: background 0.15s;
        }

        .faq-q:hover { background: var(--bg); }

        .faq-arrow {
            font-size: 12px;
            color: var(--muted);
            transition: transform 0.2s;
        }

        .faq-item.open .faq-arrow { transform: rotate(180deg); }

        .faq-a {
            display: none;
            padding: 0 20px 16px;
            font-size: 14px;
            color: var(--body);
            line-height: 1.7;
            background: white;
        }

        .faq-item.open .faq-a { display: block; }

        /* ── RESPONSIVE ── */
        @media (max-width: 640px) {
            .plans-wrap { grid-template-columns: 1fr; }
            .features-grid { grid-template-columns: 1fr 1fr; }
            .pricing-title { font-size: 30px; }
        }

        @media (max-width: 480px) {
            .features-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<main>

<!-- ── HEADER ── -->
<div class="pricing-header fade">
    <p class="section-label">Simple Pricing</p>
    <h1 class="pricing-title">One plan, full access</h1>
    <p class="pricing-sub">Subscribe once and unlock doctor contacts, hospital details, and instant appointment booking.</p>
</div>

<!-- Already subscribed banner -->
<?php if ($is_subscribed): ?>
<div class="subscribed-banner">
    <div class="alert alert-success" style="text-align: center; font-size: 14.5px;">
        ✅ You have an active subscription. <a href="/medibook/doctors.php" style="color: var(--green); font-weight: 700;">Browse doctors →</a>
    </div>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="subscribed-banner">
    <div class="alert alert-success" style="text-align: center; font-size: 14.5px;">
        🎉 <?= htmlspecialchars($success) ?> <a href="/medibook/doctors.php" style="color: var(--green); font-weight: 700;">Find a doctor →</a>
    </div>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="subscribed-banner">
    <div class="alert alert-error" style="text-align: center;"><?= htmlspecialchars($error) ?></div>
</div>
<?php endif; ?>

<!-- ── PLANS ── -->
<div class="plans-wrap">
    <?php
    $plan_index = 0;
    mysqli_data_seek($plans, 0);
    while ($plan = mysqli_fetch_assoc($plans)):
        $is_featured  = $plan_index === 1; // Yearly = featured
        $features_arr = array_map('trim', explode(',', $plan['features']));
        $plan_index++;
    ?>
    <div class="plan-card <?= $is_featured ? 'featured' : '' ?> fade" style="transition-delay: <?= $plan_index * 0.08 ?>s">

        <?php if ($is_featured): ?>
        <div class="plan-tag">Best Value</div>
        <?php endif; ?>

        <div class="plan-name"><?= htmlspecialchars($plan['name']) ?></div>

        <div class="plan-price">
            <sup>₹</sup><?= number_format($plan['price'], 0) ?>
        </div>
        <div class="plan-period">
            per <?= $plan['duration'] >= 365 ? 'year' : 'month' ?>
            <?php if ($plan['duration'] >= 365): ?>
            <span style="color: var(--green); font-weight: 600; margin-left: 6px;">Save 58%</span>
            <?php endif; ?>
        </div>

        <div class="plan-divider"></div>

        <div class="plan-features">
            <?php foreach ($features_arr as $feat): ?>
            <div class="plan-feature">
                <span class="feat-check">✓</span>
                <span><?= htmlspecialchars($feat) ?></span>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (!$is_subscribed): ?>
        <form method="POST">
            <input type="hidden" name="plan_id" value="<?= $plan['id'] ?>">
            <button type="submit" class="btn <?= $is_featured ? 'btn-blue' : 'btn-primary' ?> btn-full btn-lg">
                <?= $is_featured ? 'Get Best Value' : 'Get Started' ?>
            </button>
        </form>
        <?php else: ?>
        <button class="btn btn-ghost btn-full btn-lg" disabled style="opacity: 0.5; cursor: not-allowed;">
            Current Plan
        </button>
        <?php endif; ?>

    </div>
    <?php endwhile; ?>
</div>

<!-- ── WHAT'S INCLUDED ── -->
<div class="features-section">
    <h2 class="features-title fade">Everything included</h2>
    <div class="features-grid">
        <div class="feature-item fade" style="transition-delay:.04s">
            <div class="feature-icon">🔍</div>
            <div class="feature-name">Smart Search</div>
            <div class="feature-desc">Find doctors by speciality, symptom, or location with advanced filters.</div>
        </div>
        <div class="feature-item fade" style="transition-delay:.08s">
            <div class="feature-icon">📞</div>
            <div class="feature-name">Doctor Contacts</div>
            <div class="feature-desc">Access verified phone numbers and email addresses for every doctor.</div>
        </div>
        <div class="feature-item fade" style="transition-delay:.12s">
            <div class="feature-icon">🏥</div>
            <div class="feature-name">Hospital Details</div>
            <div class="feature-desc">View hospital names, locations, and addresses for all doctors.</div>
        </div>
        <div class="feature-item fade" style="transition-delay:.16s">
            <div class="feature-icon">📅</div>
            <div class="feature-name">Instant Booking</div>
            <div class="feature-desc">Book appointments directly through MediBook with instant confirmation.</div>
        </div>
        <div class="feature-item fade" style="transition-delay:.20s">
            <div class="feature-icon">📋</div>
            <div class="feature-name">Appointment History</div>
            <div class="feature-desc">Track all your past and upcoming appointments in one place.</div>
        </div>
        <div class="feature-item fade" style="transition-delay:.24s">
            <div class="feature-icon">⭐</div>
            <div class="feature-name">Verified Ratings</div>
            <div class="feature-desc">See real patient ratings and reviews for every specialist.</div>
        </div>
    </div>
</div>

<!-- ── FAQ ── -->
<div class="faq-section">
    <h2 class="faq-title fade">Frequently asked questions</h2>

    <div class="faq-item fade" style="transition-delay:.04s">
        <div class="faq-q" onclick="toggleFaq(this)">
            Can I cancel my subscription anytime?
            <span class="faq-arrow">▼</span>
        </div>
        <div class="faq-a">Yes, you can cancel at any time. Your access continues until the end of your billing period.</div>
    </div>

    <div class="faq-item fade" style="transition-delay:.08s">
        <div class="faq-q" onclick="toggleFaq(this)">
            Is my payment information secure?
            <span class="faq-arrow">▼</span>
        </div>
        <div class="faq-a">Absolutely. All payments are processed securely. We do not store your card details.</div>
    </div>

    <div class="faq-item fade" style="transition-delay:.12s">
        <div class="faq-q" onclick="toggleFaq(this)">
            How many appointments can I book?
            <span class="faq-arrow">▼</span>
        </div>
        <div class="faq-a">There's no limit. With an active subscription you can book as many appointments as you need.</div>
    </div>

    <div class="faq-item fade" style="transition-delay:.16s">
        <div class="faq-q" onclick="toggleFaq(this)">
            What's the difference between Monthly and Yearly?
            <span class="faq-arrow">▼</span>
        </div>
        <div class="faq-a">Both plans include exactly the same features. The Yearly plan saves you 58% compared to paying monthly for 12 months.</div>
    </div>

</div>

</main>

<?php include 'includes/footer.php'; ?>

<script>
    function toggleFaq(el) {
        const item = el.parentElement;
        item.classList.toggle('open');
    }
</script>

</body>
</html>
