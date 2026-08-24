<?php
session_start();
include 'config/db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /medibook/login.php?redirect=/medibook/dashboard.php");
    exit;
}

$user_id       = $_SESSION['user_id'];
$is_subscribed = $_SESSION['is_subscribed'] == 1;

// Fetch user details
$user_result = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
$user        = mysqli_fetch_assoc($user_result);

// Handle cancel appointment
if (isset($_POST['cancel_id'])) {
    $cancel_id = (int) $_POST['cancel_id'];
    mysqli_query($conn, "
        UPDATE appointments
        SET status = 'cancelled'
        WHERE id = $cancel_id AND user_id = $user_id
    ");
    header("Location: /medibook/dashboard.php?cancelled=1");
    exit;
}

$cancelled = isset($_GET['cancelled']);

// Fetch appointments
$upcoming = mysqli_query($conn, "
    SELECT a.*, d.name AS doctor_name, d.speciality, d.rating,
           h.name AS hospital_name, h.city
    FROM appointments a
    JOIN doctors d  ON a.doctor_id  = d.id
    LEFT JOIN hospitals h ON d.hospital_id = h.id
    WHERE a.user_id = $user_id
    AND a.appt_date >= CURDATE()
    AND a.status != 'cancelled'
    ORDER BY a.appt_date ASC, a.appt_time ASC
");

$past = mysqli_query($conn, "
    SELECT a.*, d.name AS doctor_name, d.speciality, d.rating,
           h.name AS hospital_name, h.city
    FROM appointments a
    JOIN doctors d  ON a.doctor_id  = d.id
    LEFT JOIN hospitals h ON d.hospital_id = h.id
    WHERE a.user_id = $user_id
    AND (a.appt_date < CURDATE() OR a.status = 'cancelled')
    ORDER BY a.appt_date DESC, a.appt_time DESC
");

$upcoming_count = mysqli_num_rows($upcoming);
$past_count     = mysqli_num_rows($past);

// Avatar map
$avatars = ['👨‍⚕️','👩‍⚕️','🧑‍⚕️','👨‍⚕️','👩‍⚕️','🧑‍⚕️','👨‍⚕️','👩‍⚕️'];

function format_date($date) {
    return date('D, d M Y', strtotime($date));
}

function format_time($time) {
    return date('g:i A', strtotime($time));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard — MediBook</title>
    <link rel="stylesheet" href="/medibook/assets/css/style.css">
    <style>
        /* ── LAYOUT ── */
        .dash-layout {
            max-width: 1160px;
            margin: 48px auto;
            padding: 0 24px;
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 28px;
            align-items: start;
        }

        /* ── SIDEBAR ── */
        .dash-sidebar {
            position: sticky;
            top: 84px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .user-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 24px;
            text-align: center;
        }

        .user-avatar {
            width: 64px;
            height: 64px;
            background: var(--blue-bg);
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-size: 24px;
            font-weight: 700;
            color: var(--blue);
            margin: 0 auto 14px;
        }

        .user-name {
            font-size: 16px;
            font-weight: 700;
            color: var(--navy);
        }

        .user-email {
            font-size: 13px;
            color: var(--muted);
            margin-top: 3px;
            word-break: break-all;
        }

        .user-sub {
            margin-top: 14px;
            padding-top: 14px;
            border-top: 1px solid var(--border);
        }

        /* Stats */
        .dash-stats {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .dash-stat {
            background: white;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 18px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: grid;
            place-items: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .stat-icon.blue  { background: var(--blue-bg); }
        .stat-icon.green { background: var(--green-bg); }
        .stat-icon.amber { background: #FEF3C7; }

        .stat-num {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--navy);
        }

        .stat-lbl { font-size: 12px; color: var(--muted); margin-top: 1px; }

        /* Sidebar nav */
        .dash-nav {
            background: white;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            overflow: hidden;
        }

        .dash-nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 13px 18px;
            font-size: 14px;
            font-weight: 500;
            color: var(--body);
            border-bottom: 1px solid var(--border);
            cursor: pointer;
            transition: background 0.15s, color 0.15s;
            text-decoration: none;
        }

        .dash-nav-item:last-child { border-bottom: none; }

        .dash-nav-item:hover { background: var(--bg); color: var(--navy); }

        .dash-nav-item.active {
            background: var(--blue-bg);
            color: var(--blue);
            font-weight: 600;
        }

        /* ── MAIN CONTENT ── */
        .dash-main { display: flex; flex-direction: column; gap: 28px; }

        /* Tab bar */
        .tab-bar {
            display: flex;
            border-bottom: 1px solid var(--border);
            margin-bottom: 24px;
        }

        .tab {
            padding: 12px 20px;
            font-size: 14px;
            font-weight: 500;
            color: var(--muted);
            cursor: pointer;
            border-bottom: 2px solid transparent;
            margin-bottom: -1px;
            transition: color 0.15s, border-color 0.15s;
            user-select: none;
        }

        .tab:hover { color: var(--navy); }

        .tab.active {
            color: var(--blue);
            border-bottom-color: var(--blue);
            font-weight: 600;
        }

        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        /* Appointment card */
        .appt-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 20px;
            display: grid;
            grid-template-columns: 52px 1fr auto;
            gap: 16px;
            align-items: center;
            margin-bottom: 12px;
            transition: border-color 0.2s;
        }

        .appt-card:hover { border-color: var(--blue-bd); }

        .appt-avatar {
            width: 52px;
            height: 52px;
            background: var(--blue-bg);
            border-radius: 12px;
            display: grid;
            place-items: center;
            font-size: 22px;
            flex-shrink: 0;
        }

        .appt-doc-name {
            font-size: 15px;
            font-weight: 700;
            color: var(--navy);
        }

        .appt-doc-spec {
            font-size: 13px;
            color: var(--blue);
            font-weight: 500;
            margin-top: 2px;
        }

        .appt-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 10px;
            font-size: 13px;
            color: var(--muted);
        }

        .appt-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .appt-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex-shrink: 0;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 64px 24px;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            background: white;
        }

        .empty-icon { font-size: 40px; margin-bottom: 14px; }

        .empty-title {
            font-size: 17px;
            font-weight: 700;
            color: var(--navy);
        }

        .empty-desc {
            font-size: 14px;
            color: var(--muted);
            margin-top: 6px;
            line-height: 1.6;
        }

        /* Not subscribed card */
        .upgrade-card {
            background: var(--navy);
            border-radius: var(--radius-lg);
            padding: 32px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
        }

        .upgrade-title {
            font-size: 18px;
            font-weight: 700;
            color: white;
            margin-bottom: 6px;
        }

        .upgrade-desc { font-size: 14px; color: rgba(255,255,255,0.5); line-height: 1.6; }

        /* ── RESPONSIVE ── */
        @media (max-width: 900px) {
            .dash-layout { grid-template-columns: 1fr; }
            .dash-sidebar { position: static; }
        }

        @media (max-width: 600px) {
            .appt-card { grid-template-columns: 44px 1fr; }
            .appt-actions { grid-column: 1 / -1; flex-direction: row; }
            .upgrade-card { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<main>
<div class="dash-layout">

    <!-- ── SIDEBAR ── -->
    <div class="dash-sidebar">

        <!-- User card -->
        <div class="user-card">
            <div class="user-avatar">
                <?= strtoupper(substr($user['name'], 0, 1)) ?>
            </div>
            <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
            <div class="user-email"><?= htmlspecialchars($user['email']) ?></div>
            <div class="user-sub">
                <?php if ($is_subscribed): ?>
                <span class="badge badge-green">✓ Subscribed</span>
                <?php else: ?>
                <span class="badge badge-muted">Free Account</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Stats -->
        <div class="dash-stats">
            <div class="dash-stat">
                <div class="stat-icon blue">📅</div>
                <div>
                    <div class="stat-num"><?= $upcoming_count ?></div>
                    <div class="stat-lbl">Upcoming</div>
                </div>
            </div>
            <div class="dash-stat">
                <div class="stat-icon green">✅</div>
                <div>
                    <div class="stat-num"><?= $past_count ?></div>
                    <div class="stat-lbl">Past Appointments</div>
                </div>
            </div>
        </div>

        <!-- Nav -->
        <div class="dash-nav">
            <a href="/medibook/dashboard.php" class="dash-nav-item active">📋 My Appointments</a>
            <a href="/medibook/doctors.php"   class="dash-nav-item">🔍 Find Doctors</a>
            <a href="/medibook/subscribe.php" class="dash-nav-item">💳 Subscription</a>
            <a href="/medibook/logout.php"    class="dash-nav-item" style="color: var(--red);">↩ Log Out</a>
        </div>

    </div>

    <!-- ── MAIN ── -->
    <div class="dash-main">

        <!-- Cancelled alert -->
        <?php if ($cancelled): ?>
        <div class="alert alert-success">Appointment cancelled successfully.</div>
        <?php endif; ?>

        <!-- Not subscribed upgrade banner -->
        <?php if (!$is_subscribed): ?>
        <div class="upgrade-card fade">
            <div>
                <div class="upgrade-title">Unlock appointment booking</div>
                <div class="upgrade-desc">Subscribe to book appointments with top doctors and manage them here.</div>
            </div>
            <a href="/medibook/subscribe.php" style="background:white;color:var(--navy);border:none;padding:11px 22px;border-radius:9px;font-size:14px;font-weight:700;font-family:var(--font);cursor:pointer;white-space:nowrap;text-decoration:none;flex-shrink:0;">
                View Plans →
            </a>
        </div>
        <?php endif; ?>

        <!-- Tabs -->
        <div>
            <div class="tab-bar">
                <div class="tab active" onclick="switchTab('upcoming', this)">
                    Upcoming
                    <?php if ($upcoming_count > 0): ?>
                    <span class="badge badge-blue" style="margin-left: 6px;"><?= $upcoming_count ?></span>
                    <?php endif; ?>
                </div>
                <div class="tab" onclick="switchTab('past', this)">
                    Past & Cancelled
                    <?php if ($past_count > 0): ?>
                    <span class="badge badge-muted" style="margin-left: 6px;"><?= $past_count ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- UPCOMING -->
            <div class="tab-panel active" id="tab-upcoming">
                <?php if ($upcoming_count === 0): ?>
                <div class="empty-state">
                    <div class="empty-icon">📅</div>
                    <div class="empty-title">No upcoming appointments</div>
                    <div class="empty-desc">
                        <?php if ($is_subscribed): ?>
                        Browse doctors and book your first appointment.
                        <?php else: ?>
                        Subscribe to start booking appointments with top doctors.
                        <?php endif; ?>
                    </div>
                    <a href="<?= $is_subscribed ? '/medibook/doctors.php' : '/medibook/subscribe.php' ?>"
                       class="btn btn-primary btn-sm" style="margin-top: 16px; display: inline-flex;">
                        <?= $is_subscribed ? 'Find a Doctor' : 'Subscribe Now' ?>
                    </a>
                </div>

                <?php else:
                    $i = 0;
                    while ($appt = mysqli_fetch_assoc($upcoming)):
                ?>
                <div class="appt-card fade" style="transition-delay: <?= $i * 0.06 ?>s">
                    <div class="appt-avatar"><?= $avatars[$appt['doctor_id'] % count($avatars)] ?></div>

                    <div>
                        <div class="appt-doc-name"><?= htmlspecialchars($appt['doctor_name']) ?></div>
                        <div class="appt-doc-spec"><?= htmlspecialchars($appt['speciality']) ?></div>
                        <div class="appt-meta">
                            <span>📅 <?= format_date($appt['appt_date']) ?></span>
                            <span>🕐 <?= format_time($appt['appt_time']) ?></span>
                            <span>🏥 <?= htmlspecialchars($appt['hospital_name'] ?? '—') ?>, <?= htmlspecialchars($appt['city'] ?? '') ?></span>
                            <span class="badge badge-green">Confirmed</span>
                        </div>
                    </div>

                    <div class="appt-actions">
                        <a href="/medibook/doctor-profile.php?id=<?= $appt['doctor_id'] ?>"
                           class="btn btn-ghost btn-sm">View Doctor</a>
                        <form method="POST" onsubmit="return confirm('Cancel this appointment?')">
                            <input type="hidden" name="cancel_id" value="<?= $appt['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm btn-full">Cancel</button>
                        </form>
                    </div>
                </div>
                <?php $i++; endwhile; endif; ?>
            </div>

            <!-- PAST & CANCELLED -->
            <div class="tab-panel" id="tab-past">
                <?php if ($past_count === 0): ?>
                <div class="empty-state">
                    <div class="empty-icon">📋</div>
                    <div class="empty-title">No past appointments</div>
                    <div class="empty-desc">Your appointment history will appear here.</div>
                </div>

                <?php else:
                    $i = 0;
                    while ($appt = mysqli_fetch_assoc($past)):
                        $is_cancelled = $appt['status'] === 'cancelled';
                ?>
                <div class="appt-card fade" style="transition-delay: <?= $i * 0.06 ?>s; <?= $is_cancelled ? 'opacity: 0.6;' : '' ?>">
                    <div class="appt-avatar" style="<?= $is_cancelled ? 'filter: grayscale(1);' : '' ?>">
                        <?= $avatars[$appt['doctor_id'] % count($avatars)] ?>
                    </div>

                    <div>
                        <div class="appt-doc-name"><?= htmlspecialchars($appt['doctor_name']) ?></div>
                        <div class="appt-doc-spec"><?= htmlspecialchars($appt['speciality']) ?></div>
                        <div class="appt-meta">
                            <span>📅 <?= format_date($appt['appt_date']) ?></span>
                            <span>🕐 <?= format_time($appt['appt_time']) ?></span>
                            <span>🏥 <?= htmlspecialchars($appt['hospital_name'] ?? '—') ?></span>
                            <?php if ($is_cancelled): ?>
                            <span class="badge badge-red">Cancelled</span>
                            <?php else: ?>
                            <span class="badge badge-muted">Completed</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="appt-actions">
                        <a href="/medibook/doctor-profile.php?id=<?= $appt['doctor_id'] ?>"
                           class="btn btn-ghost btn-sm">Book Again</a>
                    </div>
                </div>
                <?php $i++; endwhile; endif; ?>
            </div>
        </div>

    </div>
</div>
</main>

<?php include 'includes/footer.php'; ?>

<script>
    function switchTab(name, el) {
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        el.classList.add('active');
        document.getElementById('tab-' + name).classList.add('active');
    }
</script>

</body>
</html>
