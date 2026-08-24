<?php
session_start();
include 'config/db.php';

// Fetch top 3 rated doctors for homepage
$doctors = mysqli_query($conn, "
    SELECT d.*, h.name AS hospital_name, h.city
    FROM doctors d
    LEFT JOIN hospitals h ON d.hospital_id = h.id
    ORDER BY d.rating DESC
    LIMIT 3
");

// Fetch specialities for booking dropdown
$specs = mysqli_query($conn, "SELECT DISTINCT speciality FROM doctors ORDER BY speciality ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediBook — Find Top Doctors</title>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/medibook/assets/css/style.css">
    <style>

        /* ══════════════════════════════════════
           HERO — APPOINTMENT FIRST
        ══════════════════════════════════════ */
        .hero {
            border-bottom: 1px solid var(--border);
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        .hero-left {
            padding: 72px 52px 64px;
            border-right: 1px solid var(--border);
        }

        .hero-tag {
            font-size: 11.5px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--blue);
            margin-bottom: 18px;
        }

        .hero-title {
            font-size: 44px;
            font-weight: 700;
            letter-spacing: -0.02em;
            line-height: 1.1;
            color: var(--navy);
            font-family: var(--font-heading);
        }

        .hero-title span { color: var(--blue); }

        .hero-desc {
            margin-top: 16px;
            font-size: 15px;
            color: var(--body);
            line-height: 1.7;
            max-width: 400px;
        }

        .hero-steps {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-top: 36px;
        }

        .hero-step {
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }

        .hero-step-num {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--blue-bg);
            border: 1px solid var(--blue-bd);
            display: grid;
            place-items: center;
            font-size: 12px;
            font-weight: 700;
            color: var(--blue);
            flex-shrink: 0;
            margin-top: 1px;
        }

        .hero-step-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--navy);
        }

        .hero-step-sub {
            font-size: 12.5px;
            color: var(--muted);
            margin-top: 2px;
            line-height: 1.5;
        }

        /* Hero right */
        .hero-right {
            padding: 48px 52px;
            background: var(--bg);
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 16px;
        }

        .book-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 28px;
        }

        .book-card-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 20px;
            letter-spacing: -0.01em;
        }

        .book-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--navy);
            margin-bottom: 6px;
            display: block;
        }

        .book-field {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 11px 14px;
            font-size: 13.5px;
            font-family: var(--font);
            color: var(--navy);
            background: white;
            outline: none;
            transition: border-color 0.2s;
            margin-bottom: 14px;
        }

        .book-field:focus { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(37,99,235,0.08); }

        .book-field-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 14px;
        }

        .book-field-row .book-field { margin-bottom: 0; }

        .book-submit {
            width: 100%;
            padding: 13px;
            background: var(--blue);
            color: white;
            border: none;
            border-radius: var(--radius);
            font-size: 14px;
            font-weight: 700;
            font-family: var(--font);
            cursor: pointer;
            transition: opacity 0.15s;
        }

        .book-submit:hover { opacity: 0.88; }

        .trust-row {
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        .trust-item {
            font-size: 12px;
            color: var(--muted);
            display: flex;
            align-items: center;
            gap: 5px;
            font-weight: 500;
        }

        .trust-check { color: var(--green); }

        /* ══════════════════════════════════════
           STATS BAR
        ══════════════════════════════════════ */
        .stats-bar { background: var(--navy); padding: 48px 24px; }

        .stats-inner {
            max-width: 1160px;
            margin: auto;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 14px;
            overflow: hidden;
        }

        .stat-cell { background: var(--navy); padding: 32px 24px; text-align: center; }

        .stat-num {
            font-size: 38px;
            font-weight: 700;
            letter-spacing: -0.04em;
            color: white;
        }

        .stat-lbl { font-size: 13px; color: rgba(255,255,255,0.4); margin-top: 6px; }

        /* ══════════════════════════════════════
           SPECIALITIES
        ══════════════════════════════════════ */
        .spec-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 12px;
        }

        .spec-card {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px 12px;
            text-align: center;
            cursor: pointer;
            background: white;
            transition: all 0.18s;
            text-decoration: none;
            display: block;
        }

        .spec-card:hover {
            border-color: var(--blue-bd);
            background: var(--blue-bg);
            transform: translateY(-2px);
        }

        .spec-icon { font-size: 28px; display: block; margin-bottom: 10px; }
        .spec-name { font-size: 12.5px; font-weight: 600; color: var(--navy); line-height: 1.3; }
        .spec-count { font-size: 11px; color: var(--muted); margin-top: 3px; }

        /* ══════════════════════════════════════
           DOCTOR CARDS — FIXED
        ══════════════════════════════════════ */
        .docs-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .doc-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            overflow: hidden;
            transition: all 0.2s;
            display: flex;
            flex-direction: column;
        }

        .doc-card:hover {
            border-color: var(--blue-bd);
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(0,0,0,0.07);
        }

        .doc-card-img {
            width: 100%;
            height: 260px;
            object-fit: cover;
            object-position: top center;
            display: block;
            background: var(--blue-bg);
        }

        .doc-card-img-placeholder {
            width: 100%;
            height: 260px;
            background: var(--blue-bg);
            display: grid;
            place-items: center;
            font-size: 64px;
        }

        .doc-card-badge {
            position: absolute;
            top: 12px;
            right: 12px;
        }

        .doc-card-img-wrap {
            position: relative;
            border-bottom: 1px solid var(--border);
        }

        .doc-card-body {
            padding: 18px 20px;
            flex: 1;
        }

        .doc-card-name { font-size: 15px; font-weight: 700; color: var(--navy); }
        .doc-card-spec { font-size: 13px; color: var(--blue); font-weight: 500; margin-top: 3px; }

        .doc-card-meta {
            display: flex;
            flex-wrap: nowrap;
            gap: 10px;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid var(--border);
            font-size: 12px;
            color: var(--muted);
            overflow: hidden;
        }

        .doc-card-meta span {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .doc-card-meta span strong { color: var(--navy); font-weight: 600; }

        .doc-card-footer {
            padding: 0 20px 20px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        /* ══════════════════════════════════════
           STEPS
        ══════════════════════════════════════ */
        .steps-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }

        .step {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 28px 22px;
            transition: border-color 0.2s;
        }

        .step:hover { border-color: var(--blue-bd); }
        .step-index { font-size: 11px; font-weight: 700; letter-spacing: 0.08em; color: var(--blue); margin-bottom: 16px; text-transform: uppercase; }
        .step-icon { font-size: 26px; margin-bottom: 14px; display: block; }
        .step-title { font-size: 15px; font-weight: 700; color: var(--navy); }
        .step-desc { font-size: 13.5px; color: var(--muted); margin-top: 6px; line-height: 1.6; }

        /* ══════════════════════════════════════
           TESTIMONIALS
        ══════════════════════════════════════ */
        .testi-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        .testi-card {
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 24px;
            background: white;
            transition: border-color 0.2s;
        }

        .testi-card:hover { border-color: var(--blue-bd); }
        .testi-stars { font-size: 13px; color: #F59E0B; letter-spacing: 2px; margin-bottom: 14px; }
        .testi-text { font-size: 14.5px; color: var(--body); line-height: 1.7; }

        .testi-author {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 18px;
            padding-top: 18px;
            border-top: 1px solid var(--border);
        }

        .testi-av {
            width: 36px;
            height: 36px;
            background: var(--blue-bg);
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-size: 14px;
            font-weight: 700;
            color: var(--blue);
            flex-shrink: 0;
        }

        .testi-name { font-size: 13.5px; font-weight: 600; color: var(--navy); }
        .testi-loc { font-size: 12px; color: var(--muted); }

        /* ══════════════════════════════════════
           CTA
        ══════════════════════════════════════ */
        .cta-box {
            background: var(--navy);
            border-radius: 16px;
            padding: 60px 56px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 40px;
        }

        .cta-title { font-size: 28px; font-weight: 700; letter-spacing: -0.03em; color: white; line-height: 1.25; }
        .cta-sub { margin-top: 10px; font-size: 15px; color: rgba(255,255,255,0.45); line-height: 1.6; max-width: 460px; }
        .cta-btns { display: flex; gap: 10px; flex-shrink: 0; }

        .btn-cta-white {
            padding: 12px 24px; background: white; border: none; border-radius: 9px;
            font-size: 14px; font-weight: 600; font-family: var(--font); color: var(--navy);
            cursor: pointer; white-space: nowrap; transition: opacity 0.15s;
        }
        .btn-cta-white:hover { opacity: 0.9; }

        .btn-cta-ghost {
            padding: 12px 24px; background: transparent; border: 1px solid rgba(255,255,255,0.15);
            border-radius: 9px; font-size: 14px; font-weight: 500; font-family: var(--font);
            color: rgba(255,255,255,0.65); cursor: pointer; white-space: nowrap;
            transition: border-color 0.15s, color 0.15s;
        }
        .btn-cta-ghost:hover { border-color: rgba(255,255,255,0.35); color: white; }

        /* ══════════════════════════════════════
           SECTION SPACING
        ══════════════════════════════════════ */
        .section { max-width: 1160px; margin: auto; padding: 80px 24px; }
        .section-bg { background: var(--bg); }

        /* ══════════════════════════════════════
           RESPONSIVE
        ══════════════════════════════════════ */
        @media (max-width: 1024px) {
            .hero { grid-template-columns: 1fr; }
            .hero-left { border-right: none; border-bottom: 1px solid var(--border); padding: 48px 32px; }
            .hero-right { padding: 40px 32px; }
            .hero-title { font-size: 36px; }
            .spec-grid { grid-template-columns: repeat(4, 1fr); }
            .docs-grid { grid-template-columns: repeat(2, 1fr); }
            .stats-inner { grid-template-columns: repeat(2, 1fr); }
            .steps-grid { grid-template-columns: repeat(2, 1fr); }
            .testi-grid { grid-template-columns: repeat(2, 1fr); }
            .cta-box { flex-direction: column; align-items: flex-start; padding: 40px 32px; }
        }

        @media (max-width: 640px) {
            .hero-title { font-size: 30px; }
            .spec-grid { grid-template-columns: repeat(3, 1fr); }
            .docs-grid { grid-template-columns: 1fr; }
            .testi-grid { grid-template-columns: 1fr; }
            .steps-grid { grid-template-columns: 1fr; }
            .cta-btns { flex-direction: column; width: 100%; }
            .book-field-row { grid-template-columns: 1fr; }
            .trust-row { flex-wrap: wrap; justify-content: flex-start; gap: 12px; }
        }

    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<main>

<!-- ══════════════════════════════════════
     HERO — APPOINTMENT FIRST
══════════════════════════════════════ -->
<div class="hero">

    <!-- LEFT — Value prop + steps -->
    <div class="hero-left">
        <div class="hero-tag">Book in under 4 minutes</div>

        <h1 class="hero-title">
            The fastest way<br>to <span>book a doctor</span>
        </h1>

        <p class="hero-desc">
            Search verified specialists, compare ratings, and confirm your appointment — all in one place. No phone calls, no waiting.
        </p>

        <div class="hero-steps">
            <div class="hero-step">
                <div class="hero-step-num">1</div>
                <div>
                    <div class="hero-step-title">Search by speciality</div>
                    <div class="hero-step-sub">Filter by location, rating, and years of experience</div>
                </div>
            </div>
            <div class="hero-step">
                <div class="hero-step-num">2</div>
                <div>
                    <div class="hero-step-title">Subscribe to unlock</div>
                    <div class="hero-step-sub">Access doctor contacts, hospital info, and booking</div>
                </div>
            </div>
            <div class="hero-step">
                <div class="hero-step-num">3</div>
                <div>
                    <div class="hero-step-title">Confirm your appointment</div>
                    <div class="hero-step-sub">Instant confirmation — no back and forth</div>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT — Booking widget -->
    <div class="hero-right">
        <div class="book-card">
            <div class="book-card-title">Find available doctors</div>

            <form action="/medibook/doctors.php" method="GET">
                <label class="book-label">Speciality or symptom</label>
                <select name="search" class="book-field">
                    <option value="">All specialities</option>
                    <?php while ($s = mysqli_fetch_assoc($specs)): ?>
                    <option value="<?= htmlspecialchars($s['speciality']) ?>">
                        <?= htmlspecialchars($s['speciality']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>

                <div class="book-field-row">
                    <div>
                        <label class="book-label">Preferred date</label>
                        <input type="date" name="date" class="book-field" min="<?= date('Y-m-d') ?>">
                    </div>
                    <div>
                        <label class="book-label">Preferred time</label>
                        <select name="time" class="book-field">
                            <option value="">Any time</option>
                            <option>Morning (9am–12pm)</option>
                            <option>Afternoon (12pm–5pm)</option>
                            <option>Evening (5pm–8pm)</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="book-submit">Find available doctors →</button>
            </form>
        </div>

        <div class="trust-row">
            <div class="trust-item"><span class="trust-check">✓</span> Verified doctors</div>
            <div class="trust-item"><span class="trust-check">✓</span> Instant booking</div>
            <div class="trust-item"><span class="trust-check">✓</span> Real ratings</div>
        </div>
    </div>

</div>

<!-- ══════════════════════════════════════
     STATS BAR
══════════════════════════════════════ -->
<div class="stats-bar">
    <div class="stats-inner">
        <div class="stat-cell fade"><div class="stat-num count" data-to="10000">0</div><div class="stat-lbl">Verified Doctors</div></div>
        <div class="stat-cell fade" style="transition-delay:.08s"><div class="stat-num count" data-to="50000">0</div><div class="stat-lbl">Happy Patients</div></div>
        <div class="stat-cell fade" style="transition-delay:.16s"><div class="stat-num count" data-to="25">0</div><div class="stat-lbl">Specialities</div></div>
        <div class="stat-cell fade" style="transition-delay:.24s"><div class="stat-num count" data-to="98">0</div><div class="stat-lbl">Satisfaction Rate %</div></div>
    </div>
</div>

<!-- ══════════════════════════════════════
     SPECIALITIES
══════════════════════════════════════ -->
<div class="section">
    <div class="section-head fade">
        <div>
            <p class="section-label">Browse by Category</p>
            <h2 class="section-title">Find by speciality</h2>
        </div>
        <a href="doctors.php" class="section-more">View all →</a>
    </div>
    <div class="spec-grid">
        <a href="doctors.php?search=Cardiologist"    class="spec-card fade" style="transition-delay:.04s"><span class="spec-icon">❤️</span><div class="spec-name">Cardiologist</div><div class="spec-count">124 doctors</div></a>
        <a href="doctors.php?search=Neurologist"     class="spec-card fade" style="transition-delay:.08s"><span class="spec-icon">🧠</span><div class="spec-name">Neurologist</div><div class="spec-count">89 doctors</div></a>
        <a href="doctors.php?search=Orthopedic"      class="spec-card fade" style="transition-delay:.12s"><span class="spec-icon">🦴</span><div class="spec-name">Orthopedic</div><div class="spec-count">102 doctors</div></a>
        <a href="doctors.php?search=Pediatrician"    class="spec-card fade" style="transition-delay:.16s"><span class="spec-icon">👶</span><div class="spec-name">Pediatrician</div><div class="spec-count">76 doctors</div></a>
        <a href="doctors.php?search=General"         class="spec-card fade" style="transition-delay:.20s"><span class="spec-icon">🩺</span><div class="spec-name">General Physician</div><div class="spec-count">210 doctors</div></a>
        <a href="doctors.php?search=Ophthalmologist" class="spec-card fade" style="transition-delay:.24s"><span class="spec-icon">👁️</span><div class="spec-name">Ophthalmologist</div><div class="spec-count">58 doctors</div></a>
        <a href="doctors.php?search=Dentist"         class="spec-card fade" style="transition-delay:.28s"><span class="spec-icon">🦷</span><div class="spec-name">Dentist</div><div class="spec-count">143 doctors</div></a>
        <a href="doctors.php?search=Gynecologist"    class="spec-card fade" style="transition-delay:.32s"><span class="spec-icon">🌸</span><div class="spec-name">Gynecologist</div><div class="spec-count">97 doctors</div></a>
        <a href="doctors.php?search=Dermatologist"   class="spec-card fade" style="transition-delay:.36s"><span class="spec-icon">🧬</span><div class="spec-name">Dermatologist</div><div class="spec-count">88 doctors</div></a>
        <a href="doctors.php?search=Pulmonologist"   class="spec-card fade" style="transition-delay:.40s"><span class="spec-icon">🫁</span><div class="spec-name">Pulmonologist</div><div class="spec-count">64 doctors</div></a>
        <a href="doctors.php?search=ENT"             class="spec-card fade" style="transition-delay:.44s"><span class="spec-icon">👂</span><div class="spec-name">ENT Specialist</div><div class="spec-count">72 doctors</div></a>
        <a href="doctors.php"                        class="spec-card fade" style="transition-delay:.48s"><span class="spec-icon">➕</span><div class="spec-name">View All</div><div class="spec-count">25+ specialities</div></a>
    </div>
</div>

<!-- ══════════════════════════════════════
     TOP DOCTORS
══════════════════════════════════════ -->
<div class="section-bg">
    <div class="section">
        <div class="section-head fade">
            <div>
                <p class="section-label">Featured Specialists</p>
                <h2 class="section-title">Top rated doctors</h2>
            </div>
            <a href="doctors.php" class="section-more">View all →</a>
        </div>
        <div class="docs-grid">
            <?php
            $i = 0;
            while ($doc = mysqli_fetch_assoc($doctors)):
                $delay = ($i + 1) * 0.06;
            ?>
            <div class="doc-card fade" style="transition-delay:<?= $delay ?>s">
                <div class="doc-card-img-wrap">
                    <?php if (!empty($doc['image'])): ?>
                    <img
                        class="doc-card-img"
                        src="/medibook/assets/images/<?= htmlspecialchars($doc['image']) ?>"
                        alt="<?= htmlspecialchars($doc['name']) ?>"
                    >
                    <?php else: ?>
                    <div class="doc-card-img-placeholder">👨‍⚕️</div>
                    <?php endif; ?>
                    <?php if ($doc['available']): ?>
                    <span class="doc-card-badge badge badge-green">Available</span>
                    <?php endif; ?>
                </div>
                <div class="doc-card-body">
                    <div class="doc-card-name"><?= htmlspecialchars($doc['name']) ?></div>
                    <div class="doc-card-spec"><?= htmlspecialchars($doc['speciality']) ?></div>
                    <div class="doc-card-meta">
                        <span>⭐ <strong><?= $doc['rating'] ?></strong> · <?= number_format($doc['review_count']) ?> reviews</span>
                        <span>💼 <strong><?= $doc['experience'] ?> yrs</strong></span>
                        <span>🏥 <?= htmlspecialchars($doc['hospital_name'] ?? '—') ?></span>
                    </div>
                </div>
                <div class="doc-card-footer">
                    <a href="doctor-profile.php?id=<?= $doc['id'] ?>" class="btn btn-ghost btn-sm" style="text-align:center;">View Profile</a>
                    <a href="doctor-profile.php?id=<?= $doc['id'] ?>" class="btn btn-blue btn-sm" style="text-align:center;">Book Now</a>
                </div>
            </div>
            <?php $i++; endwhile; ?>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════
     HOW IT WORKS
══════════════════════════════════════ -->
<div class="section" id="how">
    <div class="section-head fade">
        <div>
            <p class="section-label">Simple Process</p>
            <h2 class="section-title">How it works</h2>
        </div>
    </div>
    <div class="steps-grid">
        <div class="step fade" style="transition-delay:.06s"><div class="step-index">Step 01</div><span class="step-icon">🔍</span><div class="step-title">Search</div><div class="step-desc">Find doctors by speciality, location, or symptom using our smart search.</div></div>
        <div class="step fade" style="transition-delay:.12s"><div class="step-index">Step 02</div><span class="step-icon">📋</span><div class="step-title">Compare</div><div class="step-desc">Review profiles, credentials, ratings, and hospital affiliations side by side.</div></div>
        <div class="step fade" style="transition-delay:.18s"><div class="step-index">Step 03</div><span class="step-icon">💳</span><div class="step-title">Subscribe</div><div class="step-desc">Unlock contact details, hospital info, and appointment booking with one plan.</div></div>
        <div class="step fade" style="transition-delay:.24s"><div class="step-index">Step 04</div><span class="step-icon">📅</span><div class="step-title">Book</div><div class="step-desc">Confirm your appointment and receive reminders before your visit.</div></div>
    </div>
</div>

<!-- ══════════════════════════════════════
     TESTIMONIALS
══════════════════════════════════════ -->
<div class="section-bg">
    <div class="section">
        <div class="section-head fade">
            <div>
                <p class="section-label">Patient Stories</p>
                <h2 class="section-title">What patients say</h2>
            </div>
        </div>
        <div class="testi-grid">
            <div class="testi-card fade" style="transition-delay:.06s">
                <div class="testi-stars">★★★★★</div>
                <p class="testi-text">Found a top cardiologist within minutes. The profile details were thorough and I felt confident before even walking in.</p>
                <div class="testi-author"><div class="testi-av">R</div><div><div class="testi-name">Ramesh Iyer</div><div class="testi-loc">Chennai</div></div></div>
            </div>
            <div class="testi-card fade" style="transition-delay:.12s">
                <div class="testi-stars">★★★★★</div>
                <p class="testi-text">The subscription was worth every rupee. Got verified contact details and booked same day. Completely seamless.</p>
                <div class="testi-author"><div class="testi-av">P</div><div><div class="testi-name">Priya Nair</div><div class="testi-loc">Bengaluru</div></div></div>
            </div>
            <div class="testi-card fade" style="transition-delay:.18s">
                <div class="testi-stars">★★★★☆</div>
                <p class="testi-text">Finally a platform with real ratings. Booked a pediatrician for my daughter — smooth from start to finish.</p>
                <div class="testi-author"><div class="testi-av">A</div><div><div class="testi-name">Anitha Reddy</div><div class="testi-loc">Hyderabad</div></div></div>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════
     CTA
══════════════════════════════════════ -->
<div class="section">
    <div class="cta-box fade">
        <div>
            <h2 class="cta-title">Your health deserves<br>the right doctor.</h2>
            <p class="cta-sub">Join thousands of patients who found the right specialist through MediBook. Fast, trusted, and transparent.</p>
        </div>
        <div class="cta-btns">
            <button class="btn-cta-white" onclick="location.href='/medibook/signup.php'">Get started free</button>
            <button class="btn-cta-ghost" onclick="location.href='/medibook/doctors.php'">Browse doctors</button>
        </div>
    </div>
</div>

</main>

<?php include 'includes/footer.php'; ?>

<script>
    // Count-up
    function runCount(el) {
        const target = +el.dataset.to;
        const inc = target / (1600 / 16);
        let cur = 0;
        const t = setInterval(() => {
            cur = Math.min(cur + inc, target);
            el.textContent = Math.floor(cur).toLocaleString('en-IN');
            if (cur >= target) clearInterval(t);
        }, 16);
    }

    const statsBar = document.querySelector('.stats-bar');
    if (statsBar) {
        new IntersectionObserver(entries => {
            if (entries[0].isIntersecting) document.querySelectorAll('.count').forEach(runCount);
        }, { threshold: 0.4 }).observe(statsBar);
    }
</script>

</body>
</html>
