<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

//if a visitor is already logged in, offer s shortcut to their dashboard
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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, BlinkMacSystemFont, 'Roboto', 'Helvetica Neue', Arial, sans-serif;
            background: #f0fdfa;
            color: #134e4a;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;

            /* Background image + soft overlay */
            background-image:
            linear-gradient(rgba(15, 118, 110, 0.72), rgba(15, 118, 110, 0.78)),
            url('assets/images/hero.png');
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
            position: relative;
            z-index: 10;
            backdrop-filter: blur(6px);
        }

        header h2 {
            font-size: 1.35rem;
            font-weight: 700;
            letter-spacing: -0.3px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 8px;
            transition: background 0.25s ease, transform 0.2s ease;
        }

        .nav-links a:hover {
            background: rgba(255, 255, 255, 0.18);
            transform: translateY(-1px);
        }

        .nav-links .btn-nav {
            background: white;
            color: #0f766e;
            font-weight: 600;
        }

        .nav-links .btn-nav:hover {
            background: #ccfbf1;
            color: #0f766e;
        }

        /* ---------- HERO ---------- */
        .hero {
            text-align: center;
            padding: 90px 20px 70px;
            position: relative;
            overflow: hidden;
        }

        .hero h1 {
            color: #ffffff;
            font-size: clamp(1.95rem, 4.2vw, 2.7rem);
            font-weight: 700;
            margin-bottom: 18px;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.25);
            opacity: 0;
            transform: translateY(25px);
            animation: fadeUp 0.8s ease forwards 0.15s;
        }

        .hero p {
            color: rgba(255, 255, 255, 0.92);
            max-width: 640px;
            margin: 0 auto 38px;
            font-size: 1.1rem;
            line-height: 1.7;
            text-shadow: 0 1px 6px rgba(0, 0, 0, 0.2);
            opacity: 0;
            transform: translateY(25px);
            animation: fadeUp 0.8s ease forwards 0.35s;
        }

        .hero-actions {
            opacity: 0;
            transform: translateY(25px);
            animation: fadeUp 0.8s ease forwards 0.55s;
        }

        /* ---------- BUTTONS ---------- */
        .btn {
            display: inline-block;
            background: #0f766e;
            color: white;
            padding: 13px 28px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.98rem;
            margin: 6px;
            border: 2px solid transparent;
            transition: all 0.25s ease;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);
        }

        .btn:hover {
            background: #0d9488;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

        .btn.light {
            background: rgba(255, 255, 255, 0.95);
            color: #0f766e;
            border: 2px solid white;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
        }

        .btn.light:hover {
            background: #ffffff;
            color: #0f766e;
            transform: translateY(-2px);
        }

        /* ---------- CARDS ---------- */
        .cards {
            max-width: 1100px;
            margin: 0 auto 60px;
            padding: 0 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px 26px;
            border-radius: 14px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(8px);
            transition: all 0.3s ease;
            opacity: 0;
            transform: translateY(30px);
        }

        .card:nth-child(1) { animation: fadeUp 0.7s ease forwards 0.7s; }
        .card:nth-child(2) { animation: fadeUp 0.7s ease forwards 0.85s; }
        .card:nth-child(3) { animation: fadeUp 0.7s ease forwards 1s; }

        .card:hover {
            transform: translateY(-6px);
            box-shadow: 0 14px 32px rgba(0, 0, 0, 0.18);
            background: #ffffff;
        }

        .card h3 {
            color: #0f766e;
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .card p {
            color: #475569;
            font-size: 0.97rem;
            line-height: 1.65;
        }

        /* ---------- FOOTER ---------- */
        footer {
            text-align: center;
            padding: 26px 20px;
            color: rgba(255, 255, 255, 0.85);
            font-size: 0.9rem;
            margin-top: auto;
            background: rgba(15, 118, 110, 0.85);
            backdrop-filter: blur(6px);
        }

        /* ---------- ANIMATIONS ---------- */
        @keyframes fadeUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ---------- RESPONSIVE ---------- */
        @media (max-width: 640px) {
            header {
                padding: 14px 5%;
                flex-direction: column;
                align-items: flex-start;
            }

            header h2 {
                font-size: 1.2rem;
            }

            .hero {
                padding: 60px 16px 50px;
            }

            .hero h1 {
                font-size: 1.75rem;
            }

            .cards {
                gap: 18px;
            }
        }
    </style>
</head>
<body>

<header>
    <h2>Campus Attachment Management System</h2>
    <nav class="nav-links">
        <?php if ($dashboard): ?>
            <a class="btn-nav" href="<?= htmlspecialchars($dashboard) ?>">My Dashboard</a>
        <?php else: ?>
            <a href="authentication/login.php">Login</a>
            <a class="btn-nav" href="authentication/register.php">Register</a>
        <?php endif; ?>
    </nav>
</header>

<div class="hero">
    <h1>Find and Manage Industrial Attachments in One Place</h1>
    <p>
        A central platform where students discover and apply for industrial attachment
        opportunities, and organizations publish positions and review applications.
    </p>

    <div class="hero-actions">
        <?php if ($dashboard): ?>
            <a class="btn" href="<?= htmlspecialchars($dashboard) ?>">Go to My Dashboard</a>
        <?php else: ?>
            <a class="btn" href="authentication/login.php">Login</a>
            <a class="btn light" href="authentication/register.php">Register</a>
        <?php endif; ?>
    </div>
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
