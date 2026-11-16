<?php
// organization/applications.php - Organization reviews applications to ITS OWN opportunities
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "../config/database.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'organization') {
    header("Location: ../authentication/login.php");
    exit;
}
$orgId = (int) $_SESSION['user_id'];

if (empty($_SESSION['org_csrf'])) {
    $_SESSION['org_csrf'] = bin2hex(random_bytes(32));
}

$error = "";
$success = "";

// Handle Accept / Reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token  = $_POST['csrf_token'] ?? '';
    $appId  = (int) ($_POST['application_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    // Whitelist: only these two actions are allowed, mapped to real status values
    $map = ['accept' => 'accepted', 'reject' => 'rejected'];

    if (!hash_equals($_SESSION['org_csrf'], $token)) {
        $error = "Invalid form submission. Please try again.";
    } elseif (!isset($map[$action])) {
        $error = "Invalid action.";
    } else {
        // JOIN to opportunities so we can check the application belongs to THIS organization
        $stmt = $pdo->prepare(
            "UPDATE applications a
             JOIN opportunities o ON a.opportunity_id = o.opportunity_id
             SET a.status = ?
             WHERE a.application_id = ? AND o.organization_id = ? AND a.status = 'pending'"
        );
        $stmt->execute([$map[$action], $appId, $orgId]);

        if ($stmt->rowCount() > 0) {
            $success = "Application " . $map[$action] . ".";
        } else {
            $error = "That application could not be updated (not found, not yours, or already decided).";
        }
    }
}

// Fetch applications for this organization's opportunities only
$stmt = $pdo->prepare(
    "SELECT a.application_id, a.cover_letter, a.status, a.date_applied,
            o.title,
            s.full_name, s.email, s.phone, s.course, s.year_of_study
     FROM applications a
     JOIN opportunities o ON a.opportunity_id = o.opportunity_id
     JOIN students s ON a.student_id = s.student_id
     WHERE o.organization_id = ?
     ORDER BY (a.status = 'pending') DESC, a.date_applied DESC"
);
$stmt->execute([$orgId]);
$applications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Applications Received</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <div style="max-width:900px; margin:40px auto; padding:0 16px;">
        <p>
            <a href="dashboard.php">&larr; Dashboard</a> |
            <a href="opportunities.php">My Opportunities</a>
        </p>

        <h1>Applications Received</h1>

        <?php if ($error): ?><p style="color:#b00020;"><?= htmlspecialchars($error) ?></p><?php endif; ?>
        <?php if ($success): ?><p style="color:#1b7a2e;"><?= htmlspecialchars($success) ?></p><?php endif; ?>

        <?php if (empty($applications)): ?>
            <p>No applications have been submitted yet.</p>
        <?php else: ?>
            <?php foreach ($applications as $app): ?>
                <div style="border:1px solid #ccd6e0; border-left:5px solid #174a83;
                            border-radius:6px; padding:16px; margin-bottom:16px;">
                    <h3 style="margin-top:0;"><?= htmlspecialchars($app['full_name']) ?>
                        <span style="font-weight:normal;"> applied for </span>
                        <?= htmlspecialchars($app['title']) ?></h3>

                    <p><strong>Email:</strong> <?= htmlspecialchars($app['email']) ?>
                       | <strong>Phone:</strong> <?= htmlspecialchars($app['phone'] ?? '') ?></p>
                    <p><strong>Course:</strong> <?= htmlspecialchars($app['course'] ?? '') ?>
                       | <strong>Year:</strong> <?= htmlspecialchars((string) ($app['year_of_study'] ?? '')) ?></p>
                    <p><strong>Applied:</strong> <?= htmlspecialchars(date('d M Y', strtotime($app['date_applied']))) ?>
                       | <strong>Status:</strong> <?= htmlspecialchars(ucfirst($app['status'])) ?></p>

                    <p><strong>Cover letter:</strong><br>
                        <?= !empty($app['cover_letter'])
                            ? nl2br(htmlspecialchars($app['cover_letter']))
                            : '<em>No cover letter provided.</em>' ?>
                    </p>

                    <?php if ($app['status'] === 'pending'): ?>
                        <form method="post" action="applications.php" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['org_csrf']) ?>">
                            <input type="hidden" name="application_id" value="<?= (int) $app['application_id'] ?>">
                            <button type="submit" name="action" value="accept">Accept</button>
                            <button type="submit" name="action" value="reject">Reject</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>