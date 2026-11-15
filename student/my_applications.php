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
    <title>My Applications</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <div style="max-width:900px; margin:40px auto; padding:0 16px;">
        <p>
            <a href="dashboard.php">Dashboard</a> |
            <a href="opportunities.php">Browse Opportunities</a> |
            <a href="../authentication/logout.php">Logout</a>
        </p>

        <h1>My Applications</h1>

        <?php if (empty($applications)): ?>
            <p>You have not applied for any opportunities yet.
               <a href="opportunities.php">Browse opportunities</a>.</p>
        <?php else: ?>
            <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="text-align:left; border-bottom:2px solid #174a83;">
                        <th style="padding:10px;">Opportunity</th>
                        <th style="padding:10px;">Organization</th>
                        <th style="padding:10px;">Location</th>
                        <th style="padding:10px;">Date Applied</th>
                        <th style="padding:10px;">Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($applications as $app): ?>
                    <tr style="border-bottom:1px solid #ddd;">
                        <td style="padding:10px;"><?= htmlspecialchars($app['title']) ?></td>
                        <td style="padding:10px;"><?= htmlspecialchars($app['organization_name']) ?></td>
                        <td style="padding:10px;"><?= htmlspecialchars($app['location']) ?></td>
                        <td style="padding:10px;"><?= htmlspecialchars(date('d M Y', strtotime($app['date_applied']))) ?></td>
                        <td style="padding:10px;">
                            <span style="color:white; padding:4px 12px; border-radius:12px;
                                         background:<?= $statusColors[$app['status']] ?? '#555' ?>;">
                                <?= htmlspecialchars(ucfirst($app['status'])) ?>
                            </span>
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