
<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_access_code();

$success = '';
$error = '';

$categories = [
    'ID / Cards',
    'Electronics',
    'Bags',
    'Clothing',
    'Books / School supplies',
    'Keys',
    'Wallet / Money',
    'Accessories'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'report') {
    $name = trim($_POST['reporter_name'] ?? '');
    $item = trim($_POST['item_name'] ?? '');
    $type = $_POST['item_type'] ?? 'Lost';
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $date = trim($_POST['date_reported'] ?? '');
    $contact = trim($_POST['contact_info'] ?? '');

    if (
        !$name || !$item ||
        !in_array($type, ['Lost', 'Found'], true) ||
        !in_array($category, $categories, true) ||
        !$description || !$location || !$contact || !$date
    ) {
        $error = 'Please complete all required fields.';
    } else {
        $dateObject = DateTime::createFromFormat('!Y-m-d', $date);

        if (!$dateObject || $dateObject->format('Y-m-d') !== $date) {
            $error = 'Please select a valid date.';
        } else {
            $stmt = $conn->prepare(
                'INSERT INTO lost_found_items
                (reporter_name, item_name, item_type, category,
                description, location, date_reported, contact_info, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );

            if ($stmt) {
                $status = $type;

                $stmt->bind_param(
                    'sssssssss',
                    $name,
                    $item,
                    $type,
                    $category,
                    $description,
                    $location,
                    $date,
                    $contact,
                    $status
                );

                if ($stmt->execute()) {
                    $success = 'Your item report has been submitted successfully.';
                    $_POST = [];
                } else {
                    $error = 'Unable to submit your report. Please try again.';
                }

                $stmt->close();
            } else {
                $error = 'Unable to prepare your report. Please try again.';
            }
        }
    }
}

$q = trim($_GET['q'] ?? '');
$filter = $_GET['type'] ?? 'All';

$sql = 'SELECT id, item_name, item_type, category, description,
        location, date_reported, status
        FROM lost_found_items WHERE 1=1';

$types = '';
$params = [];

if ($q !== '') {
    $sql .= ' AND (item_name LIKE ? OR description LIKE ?
              OR category LIKE ? OR location LIKE ?)';

    $like = '%' . $q . '%';
    $types .= 'ssss';
    array_push($params, $like, $like, $like, $like);
}

if (in_array($filter, ['Lost', 'Found', 'Returned'], true)) {
    $sql .= ' AND status=?';
    $types .= 's';
    $params[] = $filter;
}

$sql .= ' ORDER BY created_at DESC LIMIT 100';

$stmt = $conn->prepare($sql);

if ($stmt) {
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $items = $stmt->get_result();
} else {
    $items = false;
}

$counts = $conn->query(
    "SELECT COUNT(*) total,
    SUM(status='Lost') lost_count,
    SUM(status='Found') found_count,
    SUM(status='Returned') returned_count
    FROM lost_found_items"
)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

<title>Lost &amp; Found Dashboard</title>

<link rel="stylesheet" href="assets/style.css">
<link rel="stylesheet" href="assets/dashboard-theme.css">

<style>
* {
    box-sizing: border-box;
}

html {
    width: 100%;
    max-width: 100%;
    overflow-x: clip;
    scroll-behavior: smooth;
}

body {
    width: 100%;
    max-width: 100%;
    min-width: 0 !important;
    margin: 0;
    overflow-x: clip;
    -webkit-text-size-adjust: 100%;
}

img, video, iframe {
    max-width: 100%;
}

input, select, textarea, button {
    font: inherit;
    max-width: 100%;
}

input, select, textarea {
    min-width: 0;
    font-size: 16px;
}

button, .btn {
    min-height: 44px;
    touch-action: manipulation;
}

/* HEADER */
.topbar {
    width: 100%;
    max-width: 100%;
    min-width: 0 !important;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    padding: 14px 24px;
}

.brand {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
    max-width: 100%;
    text-decoration: none;
}

.brand > span:last-child {
    min-width: 0;
    overflow-wrap: anywhere;
}

.brand small {
    display: block;
}

.top-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 10px;
    min-width: 0;
}

/* MAIN */
.main-wrap,
.main-wrap.wide {
    width: 100% !important;
    max-width: 1400px !important;
    min-width: 0 !important;
    margin: 0 auto;
    padding: 24px;
}

.page-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 20px;
}

.page-heading > div {
    min-width: 0;
    max-width: 100%;
}

.page-heading h1,
.page-heading p {
    overflow-wrap: anywhere;
}

/* STATISTICS */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    width: 100%;
    max-width: 100%;
    min-width: 0;
    margin-bottom: 24px;
}

.stat {
    min-width: 0;
    max-width: 100%;
    padding: 18px;
    overflow-wrap: anywhere;
}

.stat span {
    display: block;
    font-size: 13px;
    margin-bottom: 8px;
}

.stat b {
    display: block;
    font-size: clamp(22px, 3vw, 30px);
    overflow-wrap: anywhere;
}

/* FORM */
.section-panel {
    width: 100%;
    max-width: 100%;
    min-width: 0;
    padding: 24px;
    margin-bottom: 24px;
}

.section-panel h2 {
    margin-top: 0;
    overflow-wrap: anywhere;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
    min-width: 0;
    max-width: 100%;
}

.form-grid > div {
    min-width: 0;
    max-width: 100%;
}

.form-grid .full-span {
    grid-column: 1 / -1;
}

label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 8px;
}

input, select, textarea {
    display: block;
    width: 100%;
    max-width: 100%;
    min-height: 46px;
    padding: 11px 12px;
    border-radius: 8px;
}

textarea {
    min-height: 100px;
    resize: vertical;
    line-height: 1.5;
}

/* DATE */
.date-input-wrap {
    display: block;
    position: relative;
    width: 100%;
    min-width: 0;
}

.date-input-wrap input[type="date"] {
    display: block;
    width: 100%;
    max-width: 100%;
    min-width: 0;
    min-height: 46px;
    margin: 0;
    padding: 11px 12px;
    box-sizing: border-box;
    font-size: 16px;
    appearance: auto;
    -webkit-appearance: auto;
}

/* NOTIFICATIONS */
.notice, .error {
    padding: 14px;
    border-radius: 9px;
    margin: 14px 0;
    overflow-wrap: anywhere;
}

.notice {
    background: #123c35;
    color: #9cf0d0;
}

.error {
    background: #48202d;
    color: #ffc2d0;
}

/* SEARCH */
.search-form {
    display: flex;
    align-items: flex-end;
    flex-wrap: wrap;
    gap: 12px;
}

.search-field {
    flex: 1 1 220px;
    min-width: 0;
}

.filter-field {
    flex: 0 1 180px;
    min-width: 0;
}

.search-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

/* ITEM LISTINGS */
.items-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
    width: 100%;
    max-width: 100%;
    min-width: 0;
}

.item-card {
    min-width: 0;
    max-width: 100%;
    padding: 18px;
    overflow-wrap: anywhere;
    word-break: break-word;
}

.item-card h3 {
    margin: 10px 0;
    font-size: 18px;
    overflow-wrap: anywhere;
}

.item-card p {
    line-height: 1.6;
    overflow-wrap: anywhere;
}

.item-meta {
    font-size: 12px;
    margin: 7px 0;
}

.status {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 99px;
    font-size: 11px;
    font-weight: 700;
}

.status.lost {
    background: #48202d;
    color: #ffb4c5;
}

.status.found {
    background: #123c35;
    color: #8df0d1;
}

.status.returned {
    background: #1d3150;
    color: #9fc5ff;
}

/* TABLET */
@media (max-width: 1000px) {
    .main-wrap,
    .main-wrap.wide {
        padding: 20px;
    }

    .stats-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .items-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

/* MOBILE */
@media (max-width: 600px) {
    html, body {
        width: 100% !important;
        max-width: 100% !important;
        min-width: 0 !important;
        overflow-x: clip !important;
    }

    .topbar {
        width: 100% !important;
        padding: 12px 10px !important;
        gap: 12px;
    }

    .brand {
        flex: 1 1 100%;
    }

    .brand b {
        font-size: 15px;
    }

    .brand small {
        font-size: 11px;
        overflow-wrap: anywhere;
    }

    .top-actions {
        width: 100%;
        justify-content: space-between;
    }

    .main-wrap,
    .main-wrap.wide {
        width: 100% !important;
        max-width: 100% !important;
        min-width: 0 !important;
        padding: 14px 10px !important;
        margin: 0 !important;
    }

    .page-heading {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
    }

    .page-heading h1 {
        font-size: 25px;
        line-height: 1.25;
    }

    .page-heading .btn {
        display: flex;
        width: 100%;
        max-width: 100%;
        justify-content: center;
        text-align: center;
    }

    .stats-grid {
        width: 100% !important;
        max-width: 100% !important;
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 10px;
    }

    .stat {
        padding: 12px 10px;
        min-width: 0;
    }

    .stat span {
        font-size: 12px;
    }

    .stat b {
        font-size: 25px;
    }

    .section-panel {
        width: 100% !important;
        max-width: 100% !important;
        min-width: 0 !important;
        padding: 16px 12px;
    }

    .section-panel h2 {
        font-size: 19px;
        line-height: 1.4;
    }

    .form-grid {
        grid-template-columns: minmax(0, 1fr) !important;
        gap: 15px;
    }

    .form-grid > div,
    .form-grid .full-span {
        width: 100%;
        max-width: 100%;
        min-width: 0;
        grid-column: 1 / -1;
    }

    input, select, textarea {
        width: 100% !important;
        max-width: 100% !important;
        min-width: 0 !important;
        font-size: 16px;
    }

    .date-input-wrap,
    .date-input-wrap input[type="date"] {
        width: 100% !important;
        max-width: 100% !important;
        min-width: 0 !important;
    }

    .section-panel form > button {
        width: 100%;
    }

    .search-form {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) !important;
        gap: 14px;
    }

    .search-field,
    .filter-field {
        width: 100%;
        max-width: 100%;
        min-width: 0;
    }

    .search-buttons {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        width: 100%;
        min-width: 0;
    }

    .search-buttons > * {
        width: 100%;
        min-width: 0;
        text-align: center;
        justify-content: center;
    }

    .items-grid {
        width: 100% !important;
        max-width: 100% !important;
        min-width: 0 !important;
        grid-template-columns: minmax(0, 1fr) !important;
        gap: 12px;
    }

    .item-card {
        width: 100%;
        max-width: 100%;
        min-width: 0;
        padding: 16px;
    }

    footer {
        padding: 22px 8px !important;
        line-height: 1.7;
        overflow-wrap: anywhere;
    }
}

/* EXTRA SMALL PHONES */
@media (max-width: 360px) {
    .main-wrap,
    .main-wrap.wide {
        padding-left: 8px !important;
        padding-right: 8px !important;
    }

    .topbar {
        padding-left: 8px !important;
        padding-right: 8px !important;
    }

    .stats-grid {
        gap: 8px;
    }

    .stat {
        padding: 10px 8px;
    }

    .stat b {
        font-size: 22px;
    }

    .section-panel {
        padding: 13px 9px;
    }

    .search-buttons {
        grid-template-columns: minmax(0, 1fr);
    }
}
</style>
</head>

<body>

<header class="topbar">
    <a class="brand" href="report.php">
        <span class="brand-icon">⌕</span>
        <span>
            <b>School Lost &amp; Found</b>
            <small>Campus item reporting system</small>
        </span>
    </a>

    <div class="top-actions">
        <span class="muted small">Access granted</span>
        <a class="btn secondary" href="logout.php">Sign out</a>
    </div>
</header>

<main class="main-wrap wide">

    <div class="page-heading">
        <div>
            <p class="eyebrow">CAMPUS SERVICES / OVERVIEW</p>
            <h1>Lost &amp; Found Dashboard</h1>
            <p class="muted">Report an item or search the latest campus listings.</p>
        </div>

        <a href="#report-form" class="btn primary">＋ Report an item</a>
    </div>

    <div class="stats-grid">
        <div class="stat">
            <span>Total reports</span>
            <b><?= (int)($counts['total'] ?? 0) ?></b>
        </div>

        <div class="stat">
            <span>Lost items</span>
            <b><?= (int)($counts['lost_count'] ?? 0) ?></b>
        </div>

        <div class="stat">
            <span>Found items</span>
            <b><?= (int)($counts['found_count'] ?? 0) ?></b>
        </div>

        <div class="stat">
            <span>Returned items</span>
            <b><?= (int)($counts['returned_count'] ?? 0) ?></b>
        </div>
    </div>

    <section class="panel section-panel" id="report-form">
        <h2>Report a lost or found item</h2>
        <p class="muted">Provide clear details so the item can be identified.</p>

        <?php if ($success): ?>
            <div class="notice"><?= e($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" id="lost-found-form">
            <input type="hidden" name="action" value="report">

            <div class="form-grid">

                <div>
                    <label for="reporter_name">Your name *</label>
                    <input
                        id="reporter_name"
                        name="reporter_name"
                        required
                        maxlength="120"
                        autocomplete="name"
                        value="<?= e($_POST['reporter_name'] ?? '') ?>"
                    >
                </div>

                <div>
                    <label for="item_name">Item name *</label>
                    <input
                        id="item_name"
                        name="item_name"
                        required
                        maxlength="150"
                        placeholder="e.g. Black wallet"
                        value="<?= e($_POST['item_name'] ?? '') ?>"
                    >
                </div>

                <div>
                    <label for="item_type">Report type *</label>
                    <select id="item_type" name="item_type" required>
                        <option value="Lost" <?= ($_POST['item_type'] ?? 'Lost') === 'Lost' ? 'selected' : '' ?>>
                            I lost an item
                        </option>
                        <option value="Found" <?= ($_POST['item_type'] ?? '') === 'Found' ? 'selected' : '' ?>>
                            I found an item
                        </option>
                    </select>
                </div>

                <div>
                    <label for="category">Category *</label>
                    <select id="category" name="category" required>
                        <option value="">Choose category</option>

                        <?php foreach ($categories as $c): ?>
                            <option
                                value="<?= e($c) ?>"
                                <?= ($_POST['category'] ?? '') === $c ? 'selected' : '' ?>
                            >
                                <?= e($c) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="location">Location *</label>
                    <input
                        id="location"
                        name="location"
                        required
                        maxlength="255"
                        placeholder="Where was it lost or found?"
                        value="<?= e($_POST['location'] ?? '') ?>"
                    >
                </div>

                <div>
                    <label for="date_reported">Date *</label>
                    <div class="date-input-wrap">
                        <input
                            id="date_reported"
                            type="date"
                            name="date_reported"
                            value="<?= e($_POST['date_reported'] ?? '') ?>"
                            required
                        >
                    </div>
                </div>

                <div class="full-span">
                    <label for="description">Description *</label>
                    <textarea
                        id="description"
                        name="description"
                        rows="3"
                        required
                        placeholder="Color, brand, distinguishing features..."
                    ><?= e($_POST['description'] ?? '') ?></textarea>
                </div>

                <div class="full-span">
                    <label for="contact_info">Contact information *</label>
                    <input
                        id="contact_info"
                        name="contact_info"
                        required
                        maxlength="255"
                        placeholder="Email address or phone number"
                        value="<?= e($_POST['contact_info'] ?? '') ?>"
                    >
                </div>

            </div>

            <button class="btn primary" type="submit" style="margin-top:18px">
                Submit report
            </button>
        </form>
    </section>

    <div class="page-heading">
        <div>
            <p class="eyebrow">RECENT ACTIVITY</p>
            <h1>Item listings</h1>
        </div>
    </div>

    <form method="get" class="panel section-panel search-form">
        <div class="search-field">
            <label for="search-query">Search listings</label>
            <input
                id="search-query"
                name="q"
                value="<?= e($q) ?>"
                placeholder="Name, description, category, location"
            >
        </div>

        <div class="filter-field">
            <label for="status-filter">Status</label>
            <select id="status-filter" name="type">
                <option value="All" <?= $filter === 'All' ? 'selected' : '' ?>>All</option>

                <?php foreach (['Lost', 'Found', 'Returned'] as $f): ?>
                    <option value="<?= e($f) ?>" <?= $filter === $f ? 'selected' : '' ?>>
                        <?= e($f) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="search-buttons">
            <button class="btn primary" type="submit">Search</button>
            <a class="btn secondary" href="report.php">Reset</a>
        </div>
    </form>

    <div class="items-grid">
        <?php if (!$items || $items->num_rows === 0): ?>
            <div class="panel item-card">
                <p class="muted">No matching reports found.</p>
            </div>
        <?php else: ?>
            <?php while ($it = $items->fetch_assoc()): ?>
                <article class="panel item-card">
                    <span class="status <?= e(strtolower($it['status'])) ?>">
                        <?= e($it['status']) ?>
                    </span>

                    <h3><?= e($it['item_name']) ?></h3>

                    <p class="item-meta">
                        <?= e($it['category']) ?> · <?= e($it['date_reported']) ?>
                    </p>

                    <p><?= nl2br(e($it['description'])) ?></p>

                    <p class="item-meta">⌖ <?= e($it['location']) ?></p>
                </article>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <footer class="muted small" style="padding:28px 0;text-align:center">
        School Lost &amp; Found · Help return belongings to their owners.
    </footer>

</main>

</body>
</html>
