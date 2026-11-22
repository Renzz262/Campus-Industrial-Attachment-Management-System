<?php
// student/my_applications.php - Student views their own applications and statuses
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "../config/database.php";

// 1. Student-only access
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
    header("Location: ../authentication/login.php");
    exit;
}
$studentId = (int) $_SESSION['user_id'];
if (empty($_SESSION['student_csrf'])) {
    $_SESSION['student_csrf'] = bin2hex(random_bytes(32));
}

$error = "";
$success = "";

// Handle "Withdraw" (state-changing, so POST + CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $appId = (int) ($_POST['application_id'] ?? 0);

    if (!hash_equals($_SESSION['student_csrf'], $token)) {
        $error = "Invalid form submission. Please try again.";
    } else {
        // Ownership + state check: must be THIS student's application, and still pending
        $stmt = $pdo->prepare(
            "DELETE FROM applications
             WHERE application_id = ? AND student_id = ? AND status = 'pending'"
        );
        $stmt->execute([$appId, $studentId]);

        if ($stmt->rowCount() > 0) {
            $success = "Application withdrawn. You can apply again if you change your mind.";
        } else {
            $error = "That application could not be withdrawn (it may already have been decided).";
        }
    }
}
// 2. Fetch ONLY this student's applications (WHERE student_id = ?)
$stmt = $pdo->prepare(
    "SELECT a.application_id, a.status, a.date_applied,
            o.title, o.location,
            org.organization_name
     FROM applications a
     JOIN opportunities o ON a.opportunity_id = o.opportunity_id
     JOIN organizations org ON o.organization_id = org.organization_id
     WHERE a.student_id = ?
     ORDER BY a.date_applied DESC"
);
$stmt->execute([$studentId]);
$applications = $stmt->fetchAll();

// 3. Colours for each status badge
$statusColors = [
    'pending'  => '#b7791f',
    'accepted' => '#1b7a2e',
    'rejected' => '#b00020',
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications | Campus Attachment System</title>

        <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, BlinkMacSystemFont, 'Roboto', 'Helvetica Neue', Arial, sans-serif;
            color: #134e4a;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-color: #0f766e;
            background-image:
                linear-gradient(rgba(15, 118, 110, 0.72), rgba(15, 118, 110, 0.78)),
                url('../assets/images/hero.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }

        /* ---------- HEADER ---------- */
        header {
            background: rgba(15, 118, 110, 0.92);
            color: white;
            padding: 16px 6%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            backdrop-filter: blur(6px);
        }
        header h2 { font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; }
        .logout {
            background: white; color: #0f766e; padding: 9px 18px;
            text-decoration: none; border-radius: 8px; font-weight: 600;
            transition: background 0.25s ease, transform 0.2s ease;
        }
        .logout:hover { background: #ccfbf1; transform: translateY(-1px); }

        /* ---------- LAYOUT ---------- */
        .container {
            width: 100%; max-width: 1100px; margin: 35px auto; padding: 0 20px; flex: 1;
            animation: fadeUp 0.6s ease backwards;
        }
        .welcome { margin-bottom: 25px; }
        .welcome h1 {
            color: #ffffff; font-size: clamp(1.6rem, 3.5vw, 2.1rem); margin-bottom: 8px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.25);
        }
        .welcome h1 .badge { margin-left: 8px; vertical-align: middle; }
        .welcome p { color: rgba(255, 255, 255, 0.92); text-shadow: 0 1px 6px rgba(0, 0, 0, 0.2); }

        .actions { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; margin-bottom: 25px; }
        .actions:has(.left) { justify-content: space-between; }
        .actions .left { display: flex; gap: 12px; flex-wrap: wrap; }

        /* ---------- BUTTONS ---------- */
        .btn, button.btn {
            display: inline-block; background: #0f766e; color: white; border: none;
            padding: 12px 20px; text-decoration: none; border-radius: 8px;
            font-weight: 600; font-size: 14px; cursor: pointer; font-family: inherit;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.2);
            transition: background 0.25s ease, transform 0.2s ease;
        }
        .btn:hover, button.btn:hover { background: #0d9488; transform: translateY(-1px); }
        .btn.small, button.btn.small { padding: 8px 14px; font-size: 13px; box-shadow: none; }
        .btn.danger, button.btn.danger, .btn.reject, button.btn.reject { background: #b00020; }
        .btn.success, button.btn.success, .btn.accept, button.btn.accept { background: #1b7a2e; }
        .btn.danger:hover, button.btn.danger:hover, .btn.reject:hover, button.btn.reject:hover { background: #8e001a; }
        .btn.success:hover, button.btn.success:hover, .btn.accept:hover, button.btn.accept:hover { background: #155f24; }

        /* ---------- MESSAGES ---------- */
        .banner {
            padding: 14px 18px; border-radius: 8px; margin-bottom: 20px;
            font-weight: 600; box-shadow: 0 3px 10px rgba(0, 0, 0, 0.12);
        }
        .banner.success { background: #e6f4ea; color: #1b7a2e; border-left: 4px solid #1b7a2e; }
        .banner.error { background: #fdecea; color: #b00020; border-left: 4px solid #b00020; }

        .notice {
            background: #fff8e6; border-left: 5px solid #b7791f; color: #6b4a10;
            padding: 18px 22px; border-radius: 10px; margin-bottom: 20px; line-height: 1.6;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.12);
        }
        .notice strong { display: block; font-size: 17px; margin-bottom: 6px; }

        /* ---------- GLASS CARDS ---------- */
        .card, .profile, .details, .form-card, .table-card, .empty, .app-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 14px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(8px);
        }

        .cards {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px; margin-bottom: 30px;
        }
        .card { padding: 25px; transition: transform 0.25s ease, box-shadow 0.25s ease; }
        .card:hover { transform: translateY(-4px); box-shadow: 0 14px 30px rgba(0, 0, 0, 0.2); }
        .card h3 { color: #475569; font-size: 15px; margin-bottom: 15px; }
        .card p { font-size: 30px; font-weight: bold; color: #0f766e; }

        .profile { padding: 25px; }
        .profile h2 { color: #0f766e; margin-bottom: 20px; }
        .profile p { margin-bottom: 12px; line-height: 1.5; }

        .details { padding: 22px 25px; margin-bottom: 20px; border-left: 5px solid #0f766e; }
        .details p { margin-bottom: 8px; line-height: 1.5; }
        .details p:last-child { margin-bottom: 0; }

        .empty { padding: 25px; }
        .empty a { color: #0f766e; font-weight: 600; }

        /* ---------- FORMS ---------- */
        .form-card { padding: 28px; }
        .form-card label {
            display: block; font-weight: 600; color: #134e4a;
            margin-bottom: 6px; font-size: 14px;
        }
        .form-card input[type="text"], .form-card input[type="email"],
        .form-card input[type="number"], .form-card textarea {
            width: 100%; padding: 11px 12px; border: 1px solid #b7d9d5;
            border-radius: 8px; font-family: inherit; font-size: 14px;
            margin-bottom: 18px; color: #134e4a; background: white;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .form-card input:focus, .form-card textarea:focus {
            outline: none; border-color: #0f766e; box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.18);
        }
        .form-card input:disabled { background: #eef2f7; color: #68778a; }

        /* ---------- TABLES ---------- */
        .table-card { padding: 10px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th {
            text-align: left; padding: 14px 12px; color: #475569; font-size: 14px;
            border-bottom: 2px solid #0f766e;
        }
        td { padding: 14px 12px; border-bottom: 1px solid #e0efed; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr.closed-row { background: #fff7f7; }
        .title-cell { font-weight: bold; color: #0f766e; }
        .title-cell .badge { margin-left: 8px; }

        /* ---------- BADGES (class names come from database values) ---------- */
        .badge {
            display: inline-block; padding: 4px 14px; border-radius: 12px;
            font-size: 12px; font-weight: bold; color: white;
        }
        .badge.open, .badge.accepted { background: #1b7a2e; }
        .badge.closed, .badge.rejected { background: #b00020; }
        .badge.pending { background: #b7791f; }

        /* ---------- APPLICATION CARDS ---------- */
        .app-card { padding: 25px; margin-bottom: 20px; border-left: 5px solid #0f766e; }
        .app-top {
            display: flex; justify-content: space-between; align-items: flex-start;
            flex-wrap: wrap; gap: 10px; margin-bottom: 15px;
        }
        .app-top h3 { color: #0f766e; font-size: 18px; }
        .app-top h3 span { color: #68778a; font-weight: normal; font-size: 15px; }
        .app-card p { margin-bottom: 8px; line-height: 1.5; }
        .letter {
            background: #f0fdfa; padding: 14px; border-radius: 8px;
            margin: 12px 0 18px; line-height: 1.6;
        }
        .decision { display: flex; gap: 10px; }

        /* ---------- FOOTER ---------- */
        footer {
            text-align: center; padding: 26px 20px;
            color: rgba(255, 255, 255, 0.85); font-size: 0.9rem;
            background: rgba(15, 118, 110, 0.85);
            backdrop-filter: blur(6px);
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
        }

        @media (max-width: 640px) {
            header { padding: 14px 5%; }
            header h2 { font-size: 1.15rem; }
            .form-card { padding: 22px 18px; }
        }
                /* ---------- HEADER NAVIGATION ---------- */
        .nav-links { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .nav-links a {
            color: white; text-decoration: none; font-size: 0.95rem; font-weight: 500;
            padding: 8px 14px; border-radius: 8px;
            transition: background 0.25s ease, transform 0.2s ease;
        }
        .nav-links a:hover { background: rgba(255, 255, 255, 0.18); transform: translateY(-1px); }
        .nav-links a.active { background: rgba(255, 255, 255, 0.22); font-weight: 600; }
        .nav-links a.logout { background: white; color: #0f766e; font-weight: 600; }
        .nav-links a.logout:hover { background: #ccfbf1; }

        @media (max-width: 640px) {
            header { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>

<?php $current = basename($_SERVER['PHP_SELF']); ?>
<header>
    <h2>Campus Attachment Management System</h2>

    <nav class="nav-links">
        <a href="dashboard.php" class="<?= $current === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
        <a href="opportunities.php" class="<?= in_array($current, ['opportunities.php', 'apply.php']) ? 'active' : '' ?>">Browse Opportunities</a>
        <a href="my_applications.php" class="<?= $current === 'my_applications.php' ? 'active' : '' ?>">My Applications</a>
        <a href="profile.php" class="<?= $current === 'profile.php' ? 'active' : '' ?>">Profile</a>
        <a class="logout" href="../authentication/logout.php">Logout</a>
    </nav>
</header>

<div class="container">

    <div class="welcome">
        <h1>My Applications</h1>
        <p>Track the status of every attachment you have applied for.</p>
    </div>



        <?php if ($error): ?><div class="banner error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="banner success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <?php if (empty($applications)): ?>
        <div class="empty">
            You have not applied for any opportunities yet.
            <a href="opportunities.php">Browse opportunities</a>.
        </div>
    <?php else: ?>
        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Opportunity</th>
                        <th>Organization</th>
                        <th>Location</th>
                        <th>Date Applied</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($applications as $app): ?>
                    <tr>
                        <td class="title-cell"><?= htmlspecialchars($app['title']) ?></td>
                        <td><?= htmlspecialchars($app['organization_name']) ?></td>
                        <td><?= htmlspecialchars($app['location']) ?></td>
                        <td><?= htmlspecialchars(date('d M Y', strtotime($app['date_applied']))) ?></td>
                        <td>
                            <span class="badge"
                                  style="background:<?= $statusColors[$app['status']] ?? '#555' ?>;">
                                <?= htmlspecialchars(ucfirst($app['status'])) ?>
                            </span>
                        </td>
                                                <td>
                            <?php if ($app['status'] === 'pending'): ?>
                                <form method="post" action="my_applications.php" style="display:inline;"
                                      onsubmit="return confirm('Withdraw this application? You can apply again later.');">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['student_csrf']) ?>">
                                    <input type="hidden" name="application_id" value="<?= (int) $app['application_id'] ?>">
                                    <button type="submit" class="btn small danger">Withdraw</button>
                                </form>
                            <?php else: ?>
                                <span style="color:#68778a;">&mdash;</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</div>

<footer>
    &copy; <?= date('Y') ?> Campus Industrial Attachment Management System
</footer>

</body>
</html>