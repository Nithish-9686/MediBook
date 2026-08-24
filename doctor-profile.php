<?php
session_start();
include 'config/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /medibook/doctors.php");
    exit;
}

$doctor_id = (int) $_GET['id'];

$result = mysqli_query($conn, "
    SELECT d.*, h.name AS hospital_name, h.city, h.address
    FROM doctors d
    LEFT JOIN hospitals h ON d.hospital_id = h.id
    WHERE d.id = $doctor_id
");

if (mysqli_num_rows($result) === 0) {
    header("Location: /medibook/doctors.php");
    exit;
}

$doc = mysqli_fetch_assoc($result);

$logged_in     = isset($_SESSION['user_id']);
$is_subscribed = $logged_in && $_SESSION['is_subscribed'] == 1;
$user_id       = $logged_in ? (int)$_SESSION['user_id'] : null;

// Handle booking
$booking_success = false;
$booking_error   = '';

if ($is_subscribed && isset($_POST['book'])) {
    $date = $_POST['date'];
    $time = $_POST['time'];

    if (empty($date) || empty($time)) {
        $booking_error = 'Please select both a date and time.';
    } elseif (strtotime($date) < strtotime('today')) {
        $booking_error = 'Please select a future date.';
    } else {
        $check = mysqli_query($conn, "
            SELECT id FROM appointments
            WHERE user_id = $user_id AND doctor_id = $doctor_id
            AND appt_date = '$date' AND status != 'cancelled'
        ");

        if (mysqli_num_rows($check) > 0) {
            $booking_error = 'You already have an appointment with this doctor on that date.';
        } else {
            mysqli_query($conn, "
                INSERT INTO appointments (user_id, doctor_id, appt_date, appt_time, status)
                VALUES ($user_id, $doctor_id, '$date', '$time', 'confirmed')
            ");
            $booking_success = true;
        }
    }
}

$expertise_tags = array_map('trim', explode(',', $doc['expertise'] ?? ''));
$education_list = array_map('trim', explode('|', $doc['education'] ?? ''));
$languages_list = array_map('trim', explode(',', $doc['languages'] ?? ''));
$avatars        = ['👨‍⚕️','👩‍⚕️','🧑‍⚕️','👨‍⚕️','👩‍⚕️'];
$avatar         = $avatars[$doc['id'] % count($avatars)];
$today          = date('Y-m-d');

// Success rate color
$sr = (float)($doc['success_rate'] ?? 0);
$sr_color = $sr >= 97 ? '#059669' : ($sr >= 95 ? '#2563EB' : '#D97706');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($doc['name']) ?> — MediBook</title>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/medibook/assets/css/style.css">
    <style>
        /* ── LAYOUT ── */
        .profile-layout {
            max-width: 1160px;
            margin: 48px auto;
            padding: 0 24px;
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 28px;
            align-items: start;
        }

        /* ── LEFT SIDEBAR ── */
        .profile-left {
            position: sticky;
            top: 84px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .profile-card-main {
            background: white;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            overflow: hidden;
        }

        .profile-img {
            width: 100%;
            height: 280px;
            object-fit: cover;
            object-position: top center;
            display: block;
        }

        .profile-img-placeholder {
            width: 100%;
            height: 280px;
            background: var(--blue-bg);
            display: grid;
            place-items: center;
            font-size: 80px;
        }

        .profile-info { padding: 20px; }

        .profile-name {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--navy);
        }

        .profile-spec {
            font-size: 14px;
            color: var(--blue);
            font-weight: 500;
            margin-top: 4px;
        }

        /* Stats grid */
        .profile-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid var(--border);
        }

        .pstat {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 12px;
            text-align: center;
        }

        .pstat-num {
            font-size: 18px;
            font-weight: 700;
            color: var(--navy);
            letter-spacing: -0.02em;
        }

        .pstat-lbl { font-size: 11px; color: var(--muted); margin-top: 2px; }

        /* Success rate bar */
        .sr-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 18px 20px;
        }

        .sr-label {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 10px;
        }

        .sr-value {
            font-size: 32px;
            font-weight: 700;
            letter-spacing: -0.03em;
        }

        .sr-sublabel { font-size: 12px; color: var(--muted); margin-top: 3px; }

        .sr-bar-bg {
            height: 6px;
            background: var(--border);
            border-radius: 10px;
            margin-top: 12px;
            overflow: hidden;
        }

        .sr-bar-fill {
            height: 100%;
            border-radius: 10px;
            transition: width 1s ease;
        }

        /* Languages */
        .lang-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            margin-top: 10px;
        }

        .lang-chip {
            font-size: 12px;
            color: var(--body);
            background: var(--bg);
            border: 1px solid var(--border);
            padding: 4px 12px;
            border-radius: 6px;
        }

        /* Upgrade card */
        .upgrade-card {
            background: var(--navy);
            border-radius: var(--radius-lg);
            padding: 22px 20px;
        }

        .upgrade-card-title { font-size: 15px; font-weight: 700; color: white; margin-bottom: 6px; }
        .upgrade-card-desc  { font-size: 13px; color: rgba(255,255,255,0.45); line-height: 1.6; margin-bottom: 16px; }

        /* ── RIGHT CONTENT ── */
        .profile-right { display: flex; flex-direction: column; gap: 20px; }

        .pcard {
            background: white;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 24px 28px;
        }

        .pcard-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .about-text {
            font-size: 15px;
            color: var(--body);
            line-height: 1.8;
        }

        /* Expertise expanded */
        .expertise-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        .exp-item {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 14px 16px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .exp-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--blue);
            flex-shrink: 0;
            margin-top: 5px;
        }

        .exp-name { font-size: 13.5px; font-weight: 600; color: var(--navy); }
        .exp-desc { font-size: 12px; color: var(--muted); margin-top: 3px; line-height: 1.5; }

        /* Education */
        .education-list { display: flex; flex-direction: column; gap: 12px; }

        .edu-item {
            display: flex;
            gap: 14px;
            align-items: flex-start;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border);
        }

        .edu-item:last-child { border-bottom: none; padding-bottom: 0; }

        .edu-icon {
            width: 36px;
            height: 36px;
            background: var(--blue-bg);
            border-radius: 9px;
            display: grid;
            place-items: center;
            font-size: 16px;
            flex-shrink: 0;
        }

        .edu-degree { font-size: 14px; font-weight: 600; color: var(--navy); }
        .edu-inst   { font-size: 13px; color: var(--muted); margin-top: 2px; }

        /* Contact row */
        .contact-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid var(--border);
            font-size: 14px;
            color: var(--body);
        }

        .contact-row:last-child { border-bottom: none; padding-bottom: 0; }

        .contact-icon {
            width: 36px;
            height: 36px;
            background: var(--blue-bg);
            border-radius: 9px;
            display: grid;
            place-items: center;
            font-size: 16px;
            flex-shrink: 0;
        }

        .contact-label { font-size: 12px; color: var(--muted); }
        .contact-value { font-weight: 600; color: var(--navy); font-size: 14px; }

        /* Lock overlay */
        .locked-wrap { position: relative; }
        .locked-blur { filter: blur(5px); pointer-events: none; user-select: none; }

        .locked-overlay {
            position: absolute;
            inset: 0;
            background: rgba(255,255,255,0.9);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-lg);
            text-align: center;
            padding: 28px;
            gap: 10px;
        }

        .locked-overlay h3 { font-size: 16px; font-weight: 700; color: var(--navy); }
        .locked-overlay p  { font-size: 13.5px; color: var(--muted); max-width: 260px; line-height: 1.6; }

        /* Booking */
        .booking-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 16px;
        }

        .time-slots {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-top: 8px;
        }

        .time-slot {
            padding: 9px;
            border: 1px solid var(--border);
            border-radius: 8px;
            text-align: center;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.15s;
            background: white;
            font-family: var(--font);
        }

        .time-slot:hover { border-color: var(--blue-bd); background: var(--blue-bg); color: var(--blue); }
        .time-slot.selected { background: var(--blue); color: white; border-color: var(--blue); }

        /* ── RESPONSIVE ── */
        @media (max-width: 900px) {
            .profile-layout { grid-template-columns: 1fr; }
            .profile-left { position: static; }
            .expertise-grid { grid-template-columns: 1fr; }
            .time-slots { grid-template-columns: repeat(3, 1fr); }
        }

        @media (max-width: 480px) {
            .booking-grid { grid-template-columns: 1fr; }
            .profile-stats { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<main>

<!-- Breadcrumb -->
<div style="background:var(--bg);border-bottom:1px solid var(--border);padding:12px 24px;">
    <div style="max-width:1160px;margin:auto;font-size:13px;color:var(--muted);">
        <a href="/medibook/index.php" style="color:var(--muted);">Home</a>
        <span style="margin:0 8px;">›</span>
        <a href="/medibook/doctors.php" style="color:var(--muted);">Doctors</a>
        <span style="margin:0 8px;">›</span>
        <span style="color:var(--navy);font-weight:500;"><?= htmlspecialchars($doc['name']) ?></span>
    </div>
</div>

<div class="profile-layout">

    <!-- ── LEFT ── -->
    <div class="profile-left">

        <div class="profile-card-main">
            <?php if (!empty($doc['image'])): ?>
            <img class="profile-img"
                 src="/medibook/assets/images/<?= htmlspecialchars($doc['image']) ?>"
                 alt="<?= htmlspecialchars($doc['name']) ?>">
            <?php else: ?>
            <div class="profile-img-placeholder"><?= $avatar ?></div>
            <?php endif; ?>

            <div class="profile-info">
                <div class="profile-name"><?= htmlspecialchars($doc['name']) ?></div>
                <div class="profile-spec"><?= htmlspecialchars($doc['speciality']) ?></div>

                <?php if ($doc['available']): ?>
                <div style="margin-top:10px;"><span class="badge badge-green">● Available Today</span></div>
                <?php else: ?>
                <div style="margin-top:10px;"><span class="badge badge-muted">Unavailable</span></div>
                <?php endif; ?>

                <div class="profile-stats">
                    <div class="pstat">
                        <div class="pstat-num"><?= $doc['experience'] ?>+</div>
                        <div class="pstat-lbl">Years Exp</div>
                    </div>
                    <div class="pstat">
                        <div class="pstat-num"><?= number_format($doc['review_count']) ?></div>
                        <div class="pstat-lbl">Reviews</div>
                    </div>
                    <div class="pstat">
                        <div class="pstat-num">★ <?= $doc['rating'] ?></div>
                        <div class="pstat-lbl">Rating</div>
                    </div>
                    <div class="pstat">
                        <div class="pstat-num"><?= number_format($doc['consultations'] ?? 0) ?>+</div>
                        <div class="pstat-lbl">Consultations</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success Rate -->
        <?php if ($doc['success_rate']): ?>
        <div class="sr-card">
            <div class="sr-label">Success Rate</div>
            <div class="sr-value" style="color:<?= $sr_color ?>;"><?= $doc['success_rate'] ?>%</div>
            <div class="sr-sublabel">Based on <?= number_format($doc['consultations'] ?? 0) ?>+ procedures</div>
            <div class="sr-bar-bg">
                <div class="sr-bar-fill" style="width:<?= $doc['success_rate'] ?>%;background:<?= $sr_color ?>;"></div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Languages -->
        <?php if (!empty($doc['languages'])): ?>
        <div class="pcard" style="padding:18px 20px;">
            <div class="pcard-title" style="margin-bottom:10px;">🌐 Languages</div>
            <div class="lang-chips">
                <?php foreach ($languages_list as $lang): ?>
                <span class="lang-chip"><?= htmlspecialchars($lang) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Subscribe CTA for non-subscribers -->
        <?php if (!$is_subscribed): ?>
        <div class="upgrade-card">
            <div class="upgrade-card-title">🔒 Unlock Full Access</div>
            <div class="upgrade-card-desc">Subscribe to view contact details, hospital info, and book appointments.</div>
            <a href="/medibook/subscribe.php" style="display:block;text-align:center;background:white;color:var(--navy);border:none;padding:10px;border-radius:8px;font-size:13.5px;font-weight:700;font-family:var(--font);text-decoration:none;">
                View Plans →
            </a>
        </div>
        <?php endif; ?>

    </div>

    <!-- ── RIGHT ── -->
    <div class="profile-right">

        <?php if ($booking_success): ?>
        <div class="alert alert-success">✅ Appointment confirmed! View it in your <a href="/medibook/dashboard.php" style="color:var(--green);font-weight:600;">dashboard</a>.</div>
        <?php endif; ?>

        <?php if ($booking_error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($booking_error) ?></div>
        <?php endif; ?>

        <!-- About -->
        <div class="pcard">
            <div class="pcard-title">👤 About</div>
            <p class="about-text"><?= htmlspecialchars($doc['about'] ?? 'No information available.') ?></p>
        </div>

        <!-- Expertise — Expanded -->
        <div class="pcard">
            <div class="pcard-title">🎯 Areas of Expertise</div>
            <div class="expertise-grid">
                <?php
                // Expanded expertise descriptions per tag
                $exp_descriptions = [
                    'Heart Surgery'          => 'Open heart, bypass, and valve replacement procedures with advanced surgical techniques.',
                    'Angioplasty'            => 'Minimally invasive balloon angioplasty and stenting to restore blood flow.',
                    'ECG & Stress Test'      => 'Comprehensive cardiac electrical activity analysis and exercise stress testing.',
                    'Acne Treatment'         => 'Evidence-based treatments including topical, oral, and laser therapies.',
                    'Skin Laser'             => 'Advanced laser procedures for pigmentation, scars, and skin rejuvenation.',
                    'Hair Loss'              => 'Diagnosis and treatment of alopecia, thinning, and scalp conditions.',
                    'Joint Replacement'      => 'Total and partial knee, hip, and shoulder replacement surgeries.',
                    'Sports Injury'          => 'Diagnosis and rehabilitation of ligament tears, fractures, and overuse injuries.',
                    'Spine'                  => 'Minimally invasive spine surgeries for disc herniation and spinal stenosis.',
                    'Child Nutrition'        => 'Personalised nutritional plans for healthy growth and development.',
                    'Vaccinations'           => 'Complete immunisation schedules and travel vaccination advisory.',
                    'Asthma'                 => 'Management of childhood asthma including inhaler therapy and prevention.',
                    'Epilepsy'               => 'Anti-epileptic drug management and surgical evaluation for drug-resistant epilepsy.',
                    'Migraine'               => 'Prophylactic and abortive treatment strategies for chronic migraines.',
                    'Stroke Management'      => 'Acute stroke intervention and long-term rehabilitation planning.',
                    'Diabetes'               => 'Type 1 & 2 diabetes management with continuous glucose monitoring.',
                    'Hypertension'           => 'Lifestyle and pharmacological management of high blood pressure.',
                    'Fever'                  => 'Diagnosis and treatment of acute and recurrent febrile illnesses.',
                    'Pregnancy'              => 'Antenatal care, high-risk pregnancy management, and delivery planning.',
                    'PCOS'                   => 'Hormonal and metabolic management of polycystic ovary syndrome.',
                    'Laparoscopy'            => 'Minimally invasive diagnostic and therapeutic laparoscopic procedures.',
                    'Sinusitis'              => 'Medical and surgical management of chronic and acute sinusitis.',
                    'Hearing Loss'           => 'Audiological evaluation and hearing aid fitting for sensorineural loss.',
                    'Tonsils'                => 'Tonsillectomy and adenoidectomy for recurrent infection and sleep apnea.',
                    'ECG'                    => 'Electrocardiogram interpretation for arrhythmias and heart disease.',
                    'Angioplasty'            => 'Coronary angioplasty and stent placement to treat blocked arteries.',
                ];

                foreach ($expertise_tags as $tag):
                    $desc = $exp_descriptions[$tag] ?? 'Specialised treatment and diagnosis in this area.';
                ?>
                <div class="exp-item">
                    <div class="exp-dot"></div>
                    <div>
                        <div class="exp-name"><?= htmlspecialchars($tag) ?></div>
                        <div class="exp-desc"><?= htmlspecialchars($desc) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Education & Credentials -->
        <?php if (!empty($doc['education'])): ?>
        <div class="pcard">
            <div class="pcard-title">🎓 Education & Credentials</div>
            <div class="education-list">
                <?php foreach ($education_list as $edu):
                    // Split "Degree – Institution"
                    $parts = explode('–', $edu, 2);
                    $degree = trim($parts[0] ?? $edu);
                    $inst   = trim($parts[1] ?? '');
                ?>
                <div class="edu-item">
                    <div class="edu-icon">🎓</div>
                    <div>
                        <div class="edu-degree"><?= htmlspecialchars($degree) ?></div>
                        <?php if ($inst): ?>
                        <div class="edu-inst"><?= htmlspecialchars($inst) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Hospital & Contact — LOCKED -->
        <div class="locked-wrap">
            <div class="pcard <?= !$is_subscribed ? 'locked-blur' : '' ?>">
                <div class="pcard-title">🏥 Hospital & Contact</div>

                <div class="contact-row">
                    <div class="contact-icon">🏥</div>
                    <div>
                        <div class="contact-label">Hospital</div>
                        <div class="contact-value"><?= htmlspecialchars($doc['hospital_name'] ?? '—') ?></div>
                    </div>
                </div>
                <div class="contact-row">
                    <div class="contact-icon">📍</div>
                    <div>
                        <div class="contact-label">Location</div>
                        <div class="contact-value"><?= htmlspecialchars(($doc['city'] ?? '') . ($doc['address'] ? ', ' . $doc['address'] : '')) ?></div>
                    </div>
                </div>
                <div class="contact-row">
                    <div class="contact-icon">📞</div>
                    <div>
                        <div class="contact-label">Phone</div>
                        <div class="contact-value"><?= htmlspecialchars($doc['phone'] ?? '—') ?></div>
                    </div>
                </div>
                <div class="contact-row">
                    <div class="contact-icon">📧</div>
                    <div>
                        <div class="contact-label">Email</div>
                        <div class="contact-value"><?= htmlspecialchars($doc['email'] ?? '—') ?></div>
                    </div>
                </div>
            </div>

            <?php if (!$is_subscribed): ?>
            <div class="locked-overlay">
                <div style="font-size:28px;">🔒</div>
                <h3>Premium Content</h3>
                <p>Subscribe to view hospital details and contact information.</p>
                <a href="/medibook/subscribe.php" class="btn btn-primary btn-sm" style="margin-top:4px;">Subscribe Now</a>
                <?php if (!$logged_in): ?>
                <a href="/medibook/login.php" style="font-size:13px;color:var(--blue);margin-top:4px;">Already subscribed? Log in</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Book Appointment — LOCKED -->
        <div class="locked-wrap">
            <div class="pcard <?= !$is_subscribed ? 'locked-blur' : '' ?>">
                <div class="pcard-title">📅 Book Appointment</div>

                <?php if ($is_subscribed): ?>
                <form method="POST">
                    <div class="booking-grid">
                        <div class="form-group" style="margin:0;">
                            <label class="form-label">Select Date</label>
                            <input type="date" name="date" class="form-input" min="<?= $today ?>" required>
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label">Select Time</label>
                            <input type="time" name="time" id="time-input" class="form-input" required>
                        </div>
                    </div>

                    <div class="form-label" style="margin-bottom:8px;">Quick Select</div>
                    <div class="time-slots">
                        <button type="button" class="time-slot" onclick="setTime('09:00')">9:00 AM</button>
                        <button type="button" class="time-slot" onclick="setTime('10:00')">10:00 AM</button>
                        <button type="button" class="time-slot" onclick="setTime('11:00')">11:00 AM</button>
                        <button type="button" class="time-slot" onclick="setTime('12:00')">12:00 PM</button>
                        <button type="button" class="time-slot" onclick="setTime('14:00')">2:00 PM</button>
                        <button type="button" class="time-slot" onclick="setTime('15:00')">3:00 PM</button>
                        <button type="button" class="time-slot" onclick="setTime('16:00')">4:00 PM</button>
                        <button type="button" class="time-slot" onclick="setTime('17:00')">5:00 PM</button>
                    </div>

                    <button type="submit" name="book" class="btn btn-blue btn-full" style="margin-top:20px;">
                        Confirm Appointment
                    </button>
                </form>

                <?php else: ?>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px;">
                    <div style="height:44px;background:var(--bg);border-radius:8px;"></div>
                    <div style="height:44px;background:var(--bg);border-radius:8px;"></div>
                </div>
                <div style="height:44px;background:var(--blue);border-radius:8px;opacity:0.3;"></div>
                <?php endif; ?>
            </div>

            <?php if (!$is_subscribed): ?>
            <div class="locked-overlay">
                <div style="font-size:28px;">📅</div>
                <h3>Book Appointments</h3>
                <p>Subscribe to book appointments with this doctor instantly.</p>
                <a href="/medibook/subscribe.php" class="btn btn-primary btn-sm" style="margin-top:4px;">Unlock Booking</a>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>
</main>

<?php include 'includes/footer.php'; ?>

<script>
    function setTime(val) {
        document.getElementById('time-input').value = val;
        document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));
        event.target.classList.add('selected');
    }
</script>

</body>
</html>
