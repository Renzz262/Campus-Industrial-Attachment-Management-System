<?php
// organization/dashboard.php - Organization home page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "../config/database.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'organization') {
    header("Location: ../authentication/login.php");
    exit;
}
$orgId = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare(
    "SELECT organization_name, email, phone, address, description
     FROM organizations WHERE organization_id = ?"
);
$stmt->execute([$orgId]);
$org = $stmt->fetch();

if (!$org) {
    header("Location: ../authentication/logout.php");
    exit;
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM opportunities WHERE organization_id = ?");
$stmt->execute([$orgId]);
$totalOpportunities = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM applications a
     JOIN opportunities o ON a.opportunity_id = o.opportunity_id
     WHERE o.organization_id = ?"
);
$stmt->execute([$orgId]);
$totalApplications = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM applications a
     JOIN opportunities o ON a.opportunity_id = o.opportunity_id
     WHERE o.organization_id = ? AND a.status = 'pending'"
);
$stmt->execute([$orgId]);
$pendingApplications = (int) $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Dashboard | Campus Attachment System</title>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body { font-family: Arial, sans-serif; background: #f3f6fb; color: #263445; }

        header {
            background: #174a83; color: white; padding: 22px 7%;
            display: flex; justify-content: space-between; align-items: center;
            flex-wrap: wrap; gap: 15px;
        }
        header h2 { font-size: 21px; }

        .logout {
            background: white; color: #174a83; padding: 10px 18px;
            text-decoration: none; border-radius: 6px; font-weight: bold;
        }

        .container { max-width: 1100px; margin: 35px auto; padding: 0 20px; }

        .welcome { margin-bottom: 30px; }
        .welcome h1 { color: #174a83; margin-bottom: 10px; }
        .welcome p { color: #68778a; }

        .actions {
            display: flex; justify-content: space-between; align-items: center;
            flex-wrap: wrap; gap: 12px; margin-bottom: 30px;
        }
        .actions .left { display: flex; gap: 12px; flex-wrap: wrap; }
        .btn {
            display: inline-block; background: #174a83; color: white;
            padding: 12px 20px; text-decoration: none; border-radius: 6px; font-weight: bold;
        }

        .cards {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px; margin-bottom: 30px;
        }
        .card {
            background: white; padding: 25px; border-radius: 10px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.05);
        }
        .card h3 { color: #68778a; font-size: 15px; margin-bottom: 15px; }
        .card p { font-size: 30px; font-weight: bold; color: #174a83; }

        .profile {
            background: white; padding: 25px; border-radius: 10px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.05);
        }
        .profile h2 { color: #174a83; margin-bottom: 20px; }
        .profile p { margin-bottom: 12px; line-height: 1.5; }

        footer { text-align: center; padding: 25px; color: #68778a; font-size: 13px; }
    </style>
</head>

<body>

<header>
    <h2>Campus Attachment Management System</h2>
    <a class="logout" href="../authentication/logout.php">Logout</a>
</header>

<div class="container">

    <div class="welcome">
        <h1>Welcome, <?= htmlspecialchars($org['organization_name']) ?></h1>
        <p>Manage your attachment opportunities and review student applications.</p>
    </div>

    <div class="actions">
        <div class="left">
            <a class="btn" href="add_opportunity.php">Post Opportunity</a>
            <a class="btn" href="opportunities.php">My Opportunities</a>
        </div>
        <a class="btn" href="applications.php">View Applications</a>
    </div>

    <div class="cards">
        <div class="card">
            <h3>Opportunities Published</h3>
            <p><?= $totalOpportunities ?></p>
        </div>
        <div class="card">
            <h3>Applications Received</h3>
            <p><?= $totalApplications ?></p>
        </div>
        <div class="card">
            <h3>Pending Applications</h3>
            <p><?= $pendingApplications ?></p>
        </div>
    </div>

    <div class="profile">
        <h2>Organization Profile</h2>
        <p><strong>Name:</strong> <?= htmlspecialchars($org['organization_name']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($org['email']) ?></p>
        <p><strong>Phone:</strong> <?= htmlspecialchars($org['phone'] ?? '') ?></p>
        <p><strong>Address:</strong> <?= htmlspecialchars($org['address'] ?? '') ?></p>
        <?php if (!empty($org['description'])): ?>
            <p><strong>About:</strong> <?= nl2br(htmlspecialchars($org['description'])) ?></p>
        <?php endif; ?>
        <p style="margin-top:20px;"><a class="btn" href="profile.php">Edit Profile</a></p>
    </div>

</div>

<footer>
    &copy; <?= date('Y') ?> Campus Industrial Attachment Management System
</footer>

</body>
</html>