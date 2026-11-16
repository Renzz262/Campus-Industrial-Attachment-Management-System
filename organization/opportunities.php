<?php
// organization/opportunities.php - Organization lists and closes its own opportunities
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

// Handle "Close" (state-changing, so POST + CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $oppId = (int) ($_POST['opportunity_id'] ?? 0);

    if (!hash_equals($_SESSION['org_csrf'], $token)) {
        $error = "Invalid form submission. Please try again.";
    } else {
        // The WHERE clause checks OWNERSHIP: organization_id must be the logged-in org
        $stmt = $pdo->prepare(
            "UPDATE opportunities SET status = 'closed'
             WHERE opportunity_id = ? AND organization_id = ? AND status = 'open'"
        );
        $stmt->execute([$oppId, $orgId]);

        if ($stmt->rowCount() > 0) {
            $success = "Opportunity closed. Students can no longer apply.";
        } else {
            $error = "That opportunity could not be closed (not found, not yours, or already closed).";
        }
    }
}

// Fetch only this organization's opportunities, with an application count for each
$stmt = $pdo->prepare(
    "SELECT o.opportunity_id, o.title, o.location, o.duration, o.status, o.date_posted,
            (SELECT COUNT(*) FROM applications a
             WHERE a.opportunity_id = o.opportunity_id) AS application_count
     FROM opportunities o
     WHERE o.organization_id = ?
     ORDER BY o.date_posted DESC"
);
$stmt->execute([$orgId]);
$opportunities = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Opportunities</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <div style="max-width:1000px; margin:40px auto; padding:0 16px;">
        <p>
            <a href="dashboard.php">&larr; Dashboard</a> |
            <a href="add_opportunity.php">Post Opportunity</a> |
            <a href="applications.php">View Applications</a>
        </p>

        <h1>My Opportunities</h1>

        <?php if ($error): ?><p style="color:#b00020;"><?= htmlspecialchars($error) ?></p><?php endif; ?>
        <?php if ($success): ?><p style="color:#1b7a2e;"><?= htmlspecialchars($success) ?></p><?php endif; ?>

        <?php if (empty($opportunities)): ?>
            <p>You have not posted any opportunities yet.</p>
        <?php else: ?>
            <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="text-align:left; border-bottom:2px solid #174a83;">
                        <th style="padding:10px;">Title</th>
                        <th style="padding:10px;">Location</th>
                        <th style="padding:10px;">Duration</th>
                        <th style="padding:10px;">Posted</th>
                        <th style="padding:10px;">Applications</th>
                        <th style="padding:10px;">Status</th>
                        <th style="padding:10px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($opportunities as $opp): ?>
                    <tr style="border-bottom:1px solid #ddd;">
                        <td style="padding:10px;"><?= htmlspecialchars($opp['title']) ?></td>
                        <td style="padding:10px;"><?= htmlspecialchars($opp['location']) ?></td>
                        <td style="padding:10px;"><?= htmlspecialchars($opp['duration']) ?></td>
                        <td style="padding:10px;"><?= htmlspecialchars(date('d M Y', strtotime($opp['date_posted']))) ?></td>
                        <td style="padding:10px;"><?= (int) $opp['application_count'] ?></td>
                        <td style="padding:10px;"><?= htmlspecialchars(ucfirst($opp['status'])) ?></td>
                        <td style="padding:10px;">
                            <a href="edit_opportunity.php?id=<?= (int) $opp['opportunity_id'] ?>">Edit</a>
                            <?php if ($opp['status'] === 'open'): ?>
                                <form method="post" action="opportunities.php" style="display:inline;"
                                      onsubmit="return confirm('Close this opportunity? Students will no longer be able to apply.');">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['org_csrf']) ?>">
                                    <input type="hidden" name="opportunity_id" value="<?= (int) $opp['opportunity_id'] ?>">
                                    <button type="submit">Close</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>