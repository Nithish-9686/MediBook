<?php
session_start();
include 'config/db.php';

$is_subscribed = isset($_SESSION['is_subscribed']) && $_SESSION['is_subscribed'] == 1;
$logged_in     = isset($_SESSION['user_id']);

// ── FILTERS ──────────────────────────────
$search     = isset($_GET['search'])     ? trim($_GET['search'])     : '';
$speciality = isset($_GET['speciality']) ? trim($_GET['speciality']) : '';
$sort       = isset($_GET['sort'])       ? trim($_GET['sort'])       : 'rating';

$where = "WHERE 1=1";
if ($search !== '') {
    $s      = mysqli_real_escape_string($conn, $search);
    $where .= " AND (d.name LIKE '%$s%' OR d.speciality LIKE '%$s%' OR d.expertise LIKE '%$s%')";
}
if ($speciality !== '') {
    $sp     = mysqli_real_escape_string($conn, $speciality);
    $where .= " AND d.speciality = '$sp'";
}

$order = "d.rating DESC";
if ($sort === 'experience') $order = "d.experience DESC";
if ($sort === 'reviews')    $order = "d.review_count DESC";
if ($sort === 'name')       $order = "d.name ASC";

$doctors = mysqli_query($conn, "
    SELECT d.*, h.name AS hospital_name, h.city
    FROM doctors d
    LEFT JOIN hospitals h ON d.hospital_id = h.id
    $where
    ORDER BY $order
");

$total = mysqli_num_rows($doctors);
$specs = mysqli_query($conn, "SELECT DISTINCT speciality FROM doctors ORDER BY speciality ASC");
$avatars = ['👨‍⚕️','👩‍⚕️','🧑‍⚕️','👨‍⚕️','👩‍⚕️','🧑‍⚕️','👨‍⚕️','👩‍⚕️'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Doctors — MediBook</title>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/medibook/assets/css/style.css">
    <style>
        /* ── PAGE HEADER ── */
        .page-header {
            background: var(--bg);
            border-bottom: 1px solid var(--border);
            padding: 48px 24px 36px;
        }

        .page-header-inner { max-width: 1160px; margin: auto; }

        .page-header h1 {
            font-size: 32px;
            font-weight: 700;
            letter-spacing: -0.03em;
            color: var(--navy);
        }

        .page-header p { font-size: 15px; color: var(--muted); margin-top: 6px; }

        /* Search */
        .search-wrap {
            margin-top: 20px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: white;
            display: flex;
            align-items: center;
            max-width: 560px;
            overflow: hidden;
            transition: border-color 0.2s;
        }

        .search-wrap:focus-within { border-color: var(--blue); }

        .search-field {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 16px;
            flex: 1;
        }

        .search-field svg { color: var(--muted); flex-shrink: 0; }

        .search-input {
            border: none;
            outline: none;
            font-size: 14px;
            font-family: var(--font);
            color: var(--navy);
            background: transparent;
            padding: 13px 0;
            width: 100%;
        }

        .search-input::placeholder { color: var(--muted); }

        .search-btn {
            margin: 6px;
            padding: 9px 18px;
            background: var(--navy);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: 600;
            font-family: var(--font);
            cursor: pointer;
            transition: opacity 0.15s;
        }

        .search-btn:hover { opacity: 0.85; }

        /* ── LAYOUT ── */
        .doctors-layout {
            max-width: 1160px;
            margin: 40px auto;
            padding: 0 24px;
            display: grid;
            grid-template-columns: 220px 1fr;
            gap: 32px;
            align-items: start;
        }

        /* ── SIDEBAR ── */
        .sidebar { position: sticky; top: 84px; }

        .filter-box {
            background: white;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 20px;
        }

        .filter-title {
            font-size: 11.5px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 12px;
        }

        .filter-list { display: flex; flex-direction: column; gap: 3px; }

        .filter-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 10px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13.5px;
            color: var(--body);
            text-decoration: none;
            transition: background 0.15s, color 0.15s;
        }

        .filter-item:hover { background: var(--bg); color: var(--navy); }

        .filter-item.active {
            background: var(--blue-bg);
            color: var(--blue);
            font-weight: 600;
        }

        .filter-count {
            font-size: 11.5px;
            color: var(--muted);
            background: var(--bg);
            padding: 1px 7px;
            border-radius: 10px;
        }

        .filter-item.active .filter-count { background: white; color: var(--blue); }

        /* ── RESULTS HEADER ── */
        .results-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .results-count { font-size: 14px; color: var(--muted); }
        .results-count strong { color: var(--navy); }

        .sort-wrap { display: flex; align-items: center; gap: 8px; font-size: 13.5px; color: var(--muted); }

        .sort-select {
            padding: 7px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 13px;
            font-family: var(--font);
            color: var(--navy);
            background: white;
            outline: none;
            cursor: pointer;
        }

        /* ── BIG DOCTOR CARD ── */
        .doc-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            overflow: hidden;
            margin-bottom: 16px;
            display: grid;
            grid-template-columns: 200px 1fr;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .doc-card:hover {
            border-color: var(--blue-bd);
            box-shadow: 0 8px 28px rgba(0,0,0,0.06);
        }

        /* Image column */
        .doc-card-img-col {
            position: relative;
            background: var(--blue-bg);
            border-right: 1px solid var(--border);
            overflow: hidden;
        }

        .doc-card-img-col img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: top center;
            display: block;
        }

        .doc-card-img-placeholder {
            width: 100%;
            height: 100%;
            min-height: 220px;
            display: grid;
            place-items: center;
            font-size: 64px;
        }

        /* Content column */
        .doc-card-content {
            padding: 24px 28px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .doc-card-top { flex: 1; }

        .doc-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 6px;
        }

        .doc-card-name { font-size: 19px; font-weight: 700; color: var(--navy); letter-spacing: -0.02em; }

        .doc-card-spec { font-size: 14px; color: var(--blue); font-weight: 500; margin-top: 3px; }

        .doc-card-rating {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-shrink: 0;
        }

        .rating-badge {
            font-size: 13px;
            font-weight: 700;
            color: #92400E;
            background: #FEF3C7;
            padding: 4px 10px;
            border-radius: 6px;
        }

        .review-count { font-size: 12.5px; color: var(--muted); }

        /* Stats row */
        .doc-card-stats {
            display: flex;
            gap: 24px;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid var(--border);
        }

        .doc-stat { text-align: left; }

        .doc-stat-num {
            font-size: 18px;
            font-weight: 700;
            color: var(--navy);
            letter-spacing: -0.02em;
        }

        .doc-stat-lbl { font-size: 11.5px; color: var(--muted); margin-top: 2px; }

        /* Expertise tags */
        .doc-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            margin-top: 14px;
        }

        .doc-tag {
            font-size: 12px;
            color: var(--body);
            background: var(--bg);
            border: 1px solid var(--border);
            padding: 4px 12px;
            border-radius: 6px;
        }

        /* Hospital info — locked */
        .doc-hospital {
            margin-top: 14px;
            font-size: 13px;
            color: var(--muted);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .doc-hospital-locked {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 12.5px;
            color: var(--muted);
            background: var(--bg);
            border: 1px solid var(--border);
            padding: 4px 12px;
            border-radius: 6px;
        }

        /* Card footer */
        .doc-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 18px;
            padding-top: 18px;
            border-top: 1px solid var(--border);
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 80px 24px;
            color: var(--muted);
        }

        .empty-state h3 { font-size: 18px; font-weight: 600; color: var(--navy); margin-top: 16px; }
        .empty-state p { font-size: 14px; margin-top: 8px; }

        /* ── RESPONSIVE ── */
        @media (max-width: 900px) {
            .doctors-layout { grid-template-columns: 1fr; }
            .sidebar { position: static; }
            .doc-card { grid-template-columns: 160px 1fr; }
        }

        @media (max-width: 600px) {
            .doc-card { grid-template-columns: 1fr; }
            .doc-card-img-col { height: 220px; }
            .doc-card-img-col img { height: 220px; }
        }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<main>

<!-- ── PAGE HEADER ── -->
<div class="page-header">
    <div class="page-header-inner">
        <h1>Find a Doctor</h1>
        <p>Browse <?= $total ?> verified specialists across India</p>

        <form method="GET" action="doctors.php">
            <?php if ($speciality): ?>
            <input type="hidden" name="speciality" value="<?= htmlspecialchars($speciality) ?>">
            <?php endif; ?>
            <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">

            <div class="search-wrap">
                <div class="search-field">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                    </svg>
                    <input type="text" name="search" class="search-input"
                        placeholder="Doctor, speciality, or symptom..."
                        value="<?= htmlspecialchars($search) ?>">
                </div>
                <button type="submit" class="search-btn">Search</button>
            </div>
        </form>
    </div>
</div>

<!-- ── MAIN LAYOUT ── -->
<div class="doctors-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="filter-box">
            <p class="filter-title">Speciality</p>
            <div class="filter-list">
                <a href="doctors.php?search=<?= urlencode($search) ?>&sort=<?= $sort ?>"
                   class="filter-item <?= $speciality === '' ? 'active' : '' ?>">
                    All Doctors
                    <span class="filter-count"><?= mysqli_num_rows(mysqli_query($conn, "SELECT id FROM doctors")) ?></span>
                </a>
                <?php while ($spec = mysqli_fetch_assoc($specs)):
                    $spec_name  = $spec['speciality'];
                    $spec_count = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM doctors WHERE speciality = '" . mysqli_real_escape_string($conn, $spec_name) . "'"));
                ?>
                <a href="doctors.php?speciality=<?= urlencode($spec_name) ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>"
                   class="filter-item <?= $speciality === $spec_name ? 'active' : '' ?>">
                    <?= htmlspecialchars($spec_name) ?>
                    <span class="filter-count"><?= $spec_count ?></span>
                </a>
                <?php endwhile; ?>
            </div>

            <div class="divider"></div>

            <p class="filter-title">Availability</p>
            <div class="filter-list">
                <a href="doctors.php?search=<?= urlencode($search) ?>&sort=<?= $sort ?>" class="filter-item active">
                    Available Now
                </a>
            </div>
        </div>
    </aside>

    <!-- RESULTS -->
    <div>
        <div class="results-header">
            <p class="results-count">
                <strong><?= $total ?></strong> doctor<?= $total !== 1 ? 's' : '' ?> found
                <?php if ($search): ?> for "<strong><?= htmlspecialchars($search) ?></strong>"<?php endif; ?>
                <?php if ($speciality): ?> in <strong><?= htmlspecialchars($speciality) ?></strong><?php endif; ?>
            </p>

            <form method="GET" class="sort-wrap">
                <?php if ($search):    ?><input type="hidden" name="search"     value="<?= htmlspecialchars($search) ?>"><?php endif; ?>
                <?php if ($speciality):?><input type="hidden" name="speciality" value="<?= htmlspecialchars($speciality) ?>"><?php endif; ?>
                <span>Sort by</span>
                <select name="sort" class="sort-select" onchange="this.form.submit()">
                    <option value="rating"     <?= $sort === 'rating'     ? 'selected' : '' ?>>Top Rated</option>
                    <option value="experience" <?= $sort === 'experience' ? 'selected' : '' ?>>Most Experienced</option>
                    <option value="reviews"    <?= $sort === 'reviews'    ? 'selected' : '' ?>>Most Reviewed</option>
                    <option value="name"       <?= $sort === 'name'       ? 'selected' : '' ?>>Name A–Z</option>
                </select>
            </form>
        </div>

        <?php if ($total === 0): ?>
        <div class="empty-state">
            <div style="font-size:48px">🔍</div>
            <h3>No doctors found</h3>
            <p>Try a different search term or browse all specialities.</p>
            <a href="doctors.php" class="btn btn-ghost" style="margin-top:20px;display:inline-flex;">Clear filters</a>
        </div>

        <?php else:
            $i = 0;
            while ($doc = mysqli_fetch_assoc($doctors)):
                $tags = array_map('trim', explode(',', $doc['expertise'] ?? ''));
        ?>

        <div class="doc-card fade" style="transition-delay:<?= $i * 0.05 ?>s">

            <!-- Image -->
            <div class="doc-card-img-col">
                <?php if (!empty($doc['image'])): ?>
                <img src="/medibook/assets/images/<?= htmlspecialchars($doc['image']) ?>"
                     alt="<?= htmlspecialchars($doc['name']) ?>">
                <?php else: ?>
                <div class="doc-card-img-placeholder"><?= $avatars[$i % count($avatars)] ?></div>
                <?php endif; ?>
            </div>

            <!-- Content -->
            <div class="doc-card-content">
                <div class="doc-card-top">

                    <div class="doc-card-header">
                        <div>
                            <div class="doc-card-name"><?= htmlspecialchars($doc['name']) ?></div>
                            <div class="doc-card-spec"><?= htmlspecialchars($doc['speciality']) ?></div>
                        </div>
                        <div class="doc-card-rating">
                            <span class="rating-badge">★ <?= $doc['rating'] ?></span>
                            <span class="review-count"><?= number_format($doc['review_count']) ?> reviews</span>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="doc-card-stats">
                        <div class="doc-stat">
                            <div class="doc-stat-num"><?= $doc['experience'] ?>+</div>
                            <div class="doc-stat-lbl">Years Exp</div>
                        </div>
                        <div class="doc-stat">
                            <div class="doc-stat-num"><?= number_format($doc['review_count']) ?></div>
                            <div class="doc-stat-lbl">Patients</div>
                        </div>
                        <div class="doc-stat">
                            <div class="doc-stat-num">★ <?= $doc['rating'] ?></div>
                            <div class="doc-stat-lbl">Rating</div>
                        </div>
                        <div class="doc-stat">
                            <?php if ($doc['available']): ?>
                            <div class="doc-stat-num" style="color:var(--green);">●&nbsp;Yes</div>
                            <?php else: ?>
                            <div class="doc-stat-num" style="color:var(--muted);">●&nbsp;No</div>
                            <?php endif; ?>
                            <div class="doc-stat-lbl">Available</div>
                        </div>
                    </div>

                    <!-- Expertise tags -->
                    <div class="doc-tags">
                        <?php foreach (array_slice($tags, 0, 4) as $tag): ?>
                        <span class="doc-tag"><?= htmlspecialchars($tag) ?></span>
                        <?php endforeach; ?>
                    </div>

                    <!-- Hospital — locked for non-subscribers -->
                    <div style="margin-top:14px;">
                        <?php if ($is_subscribed): ?>
                        <div class="doc-hospital">
                            🏥 <?= htmlspecialchars($doc['hospital_name'] ?? '—') ?>, <?= htmlspecialchars($doc['city'] ?? '') ?>
                        </div>
                        <?php else: ?>
                        <div class="doc-hospital-locked">
                            🔒 Hospital details — <a href="/medibook/subscribe.php" style="color:var(--blue);font-weight:600;">Subscribe to view</a>
                        </div>
                        <?php endif; ?>
                    </div>

                </div>

                <!-- Footer -->
                <div class="doc-card-footer">
                    <div style="font-size:13px;color:var(--muted);">
                        <?php if ($doc['available']): ?>
                        <span style="color:var(--green);font-weight:600;">● Available today</span>
                        <?php else: ?>
                        <span style="color:var(--muted);">Unavailable</span>
                        <?php endif; ?>
                    </div>
                    <div style="display:flex;gap:8px;">
                        <a href="/medibook/doctor-profile.php?id=<?= $doc['id'] ?>" class="btn btn-ghost btn-sm">View Profile</a>
                        <a href="/medibook/doctor-profile.php?id=<?= $doc['id'] ?>" class="btn btn-blue btn-sm">Book Now</a>
                    </div>
                </div>
            </div>
        </div>

        <?php $i++; endwhile; endif; ?>
    </div>
</div>
</main>

<?php include 'includes/footer.php'; ?>

</body>
</html>
