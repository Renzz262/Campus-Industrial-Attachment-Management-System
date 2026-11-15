<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If the visitor is already logged in, offer a shortcut to their dashboard
$dashboard = '';
if (isset($_SESSION['user_id'], $_SESSION['role'])) {
    $dashboard = ($_SESSION['role'] === 'organization')
        ? 'organization/dashboard.php'
        : 'student/dashboard.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Industrial Attachment Management System</title>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f3f6fb; color: #263445; }

        header {
            background: #174a83; color: white; padding: 22px 7%;
            display: flex; justify-content: space-between; align-items: center;
            flex-wrap: wrap; gap: 15px;
        }
        header h2 { font-size: 21px; }

        .hero { text-align: center; padding: 70px 20px 50px; }
        .hero h1 { color: #174a83; font-size: 34px; margin-bottom: 15px; }
        .hero p { color: #68778a; max-width: 650px; margin: 0 auto 30px; line-height: 1.6; }

        .btn {
            display: inline-block; background: #174a83; color: white;
            padding: 12px 24px; text-decoration: none; border-radius: 6px;
            font-weight: bold; margin: 5px;
        }
        .btn.light { background: white; color: #174a83; border: 2px solid #174a83; }

        .cards {
            max-width: 1100px; margin: 0 auto 50px; padding: 0 20px;
            display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px;
        }
        .card {
            background: white; padding: 25px; border-radius: 10px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.05);
        }
        .card h3 { color: #174a83; margin-bottom: 12px; }
        .card p { color: #68778a; line-height: 1.5; }

        footer { text-align: center; padding: 25px; color: #68778a; font-size: 13px; }
    </style>
</head>
<body>

<header>
    <h2>Campus Attachment Management System</h2>
</header>

<div class="hero">
    <h1>Find and Manage Industrial Attachments in One Place</h1>
    <p>
        A central platform where students discover and apply for industrial attachment
        opportunities, and organizations publish positions and review applications.
    </p>

    <?php if ($dashboard): ?>
        <a class="btn" href="<?= htmlspecialchars($dashboard) ?>">Go to My Dashboard</a>
    <?php else: ?>
        <a class="btn" href="authentication/login.php">Login</a>
        <a class="btn light" href="authentication/register.php">Register</a>
    <?php endif; ?>
</div>

<div class="cards">
    <div class="card">
        <h3>For Students</h3>
        <p>Browse open attachments, search by title, filter by location, apply with a cover letter and track every application's status.</p>
    </div>
    <div class="card">
        <h3>For Organizations</h3>
        <p>Post attachment opportunities, edit or close them, and review student applications with a single click to accept or reject.</p>
    </div>
    <div class="card">
        <h3>Simple and Secure</h3>
        <p>Separate student and organization accounts, hashed passwords and role-based access keep everyone's data protected.</p>
    </div>
</div>

<footer>
    &copy; <?= date('Y') ?> Campus Industrial Attachment Management System
</footer>

</body>
</html>