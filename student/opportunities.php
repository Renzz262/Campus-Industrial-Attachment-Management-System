<?php
session_start();

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'student'
) {
    header("Location: ../authentication/login.php");
    exit();
}

require_once "../config/database.php";

// Get search and location filter values
$search = trim($_GET['search'] ?? '');
$location = trim($_GET['location'] ?? '');

// Build the database query
$sql = "
    SELECT
        o.opportunity_id,
        o.title,
        o.description,
        o.requirements,
        o.location,
        o.duration,
        o.date_posted,
        org.organization_name,
        org.address AS organization_address
    FROM opportunities o
    INNER JOIN organizations org
        ON o.organization_id = org.organization_id
    WHERE o.status = 'open'
";

$params = [];

// Search by opportunity title
if ($search !== '') {
    $sql .= " AND o.title LIKE ?";
    $params[] = "%" . $search . "%";
}

// Filter by opportunity location
if ($location !== '') {
    $sql .= " AND o.location LIKE ?";
    $params[] = "%" . $location . "%";
}

$sql .= " ORDER BY o.date_posted DESC";

// Execute the query
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$opportunities = $stmt->fetchAll();

// Helper function for safe HTML output
function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Browse Opportunities | Campus Attachment System</title>

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

        .back-link {
            background: white; color: #0f766e; padding: 9px 18px;
            text-decoration: none; border-radius: 8px; font-weight: 600;
            transition: background 0.25s ease, transform 0.2s ease;
        }
        .back-link:hover { background: #ccfbf1; transform: translateY(-1px); }

        /* ---------- LAYOUT ---------- */
        .container {
            width: 100%; max-width: 1100px; margin: 35px auto; padding: 0 20px; flex: 1;
            animation: fadeUp 0.6s ease backwards;
        }

        .page-heading { margin-bottom: 25px; }
        .page-heading h1 {
            color: #ffffff; font-size: clamp(1.6rem, 3.5vw, 2.1rem); margin-bottom: 8px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.25);
        }
        .page-heading p { color: rgba(255, 255, 255, 0.92); text-shadow: 0 1px 6px rgba(0, 0, 0, 0.2); }

        /* ---------- GLASS CARDS ---------- */
        .search-box, .opportunity-card, .no-results {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 14px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(8px);
        }

        /* ---------- SEARCH ---------- */
        .search-box { padding: 22px; margin-bottom: 30px; }

        .search-form { display: grid; grid-template-columns: 1fr 1fr auto; gap: 12px; }

        .search-form input {
            width: 100%; padding: 12px; border: 1px solid #b7d9d5;
            border-radius: 8px; font-family: inherit; font-size: 14px;
            color: #134e4a; background: white;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .search-form input:focus {
            outline: none; border-color: #0f766e; box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.18);
        }

        .search-form button {
            background: #0f766e; color: white; border: none; padding: 12px 24px;
            border-radius: 8px; cursor: pointer; font-weight: 600; font-family: inherit;
            font-size: 14px; transition: background 0.25s ease, transform 0.2s ease;
        }
        .search-form button:hover { background: #0d9488; transform: translateY(-1px); }

        .clear-link {
            display: inline-block; margin-top: 12px; color: #0f766e;
            text-decoration: none; font-size: 14px; font-weight: 600;
        }
        .clear-link:hover { text-decoration: underline; }

        .results-heading {
            margin-bottom: 18px; color: #ffffff; font-size: 1.3rem;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
        }

        /* ---------- OPPORTUNITY CARDS ---------- */
        .opportunity-card {
            padding: 25px; margin-bottom: 20px;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }
        .opportunity-card:hover { transform: translateY(-3px); box-shadow: 0 14px 30px rgba(0, 0, 0, 0.2); }

        .opportunity-card h2 { color: #0f766e; margin-bottom: 12px; }
        .organization { font-weight: bold; color: #134e4a; margin-bottom: 12px; }
        .opportunity-card p { line-height: 1.6; margin-bottom: 12px; }

        .details {
            background: #f0fdfa; padding: 14px; border-radius: 8px; margin-top: 15px;
            border-left: 4px solid #0f766e;
        }
        .details p { margin-bottom: 7px; }
        .details p:last-child { margin-bottom: 0; }

        .btn {
            display: inline-block; background: #0f766e; color: white;
            padding: 12px 22px; text-decoration: none; border-radius: 8px;
            font-weight: 600; font-size: 14px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.2);
            transition: background 0.25s ease, transform 0.2s ease;
        }
        .btn:hover { background: #0d9488; transform: translateY(-1px); }

        .no-results { padding: 30px; text-align: center; color: #475569; }
        .no-results h3 { color: #0f766e; margin-bottom: 8px; }

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

        @media (max-width: 650px) {
            header { padding: 14px 5%; }
            header h2 { font-size: 1.15rem; }
            .search-form { grid-template-columns: 1fr; }
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

    <div class="page-heading">
        <h1>Browse Attachment Opportunities</h1>

        <p>
            Discover available industrial attachment opportunities
            posted by organizations.
        </p>
    </div>

    <!-- Search and location filter -->
    <section class="search-box">

        <form method="GET" action="opportunities.php" class="search-form">

            <input
                type="text"
                name="search"
                placeholder="Search by opportunity title"
                value="<?php echo e($search); ?>"
            >

            <input
                type="text"
                name="location"
                placeholder="Filter by location"
                value="<?php echo e($location); ?>"
            >

            <button type="submit">Search</button>

        </form>

        <a class="clear-link" href="opportunities.php">
            Clear search and filters
        </a>

    </section>

    <h2 class="results-heading">
        Available Opportunities (<?php echo count($opportunities); ?>)
    </h2>

    <?php if (count($opportunities) > 0): ?>

        <?php foreach ($opportunities as $opportunity): ?>

            <article class="opportunity-card">

                <h2>
                    <?php echo e($opportunity['title']); ?>
                </h2>

                <p class="organization">
                    Organization:
                    <?php echo e($opportunity['organization_name']); ?>
                </p>

                <p>
                    <strong>Description:</strong><br>
                    <?php echo nl2br(e($opportunity['description'])); ?>
                </p>

                <p>
                    <strong>Requirements:</strong><br>
                    <?php
                    echo $opportunity['requirements'] !== null
                        && $opportunity['requirements'] !== ''
                        ? nl2br(e($opportunity['requirements']))
                        : 'No specific requirements provided.';
                    ?>
                </p>

                <div class="details">

                    <p>
                        <strong>Location:</strong>
                        <?php echo e($opportunity['location'] ?: 'Not specified'); ?>
                    </p>

                    <p>
                        <strong>Duration:</strong>
                        <?php echo e($opportunity['duration'] ?: 'Not specified'); ?>
                    </p>

                    <p>
                        <strong>Organization Address:</strong>
                        <?php echo e($opportunity['organization_address'] ?: 'Not specified'); ?>
                    </p>

                    <p>
                        <strong>Date Posted:</strong>
                        <?php echo e($opportunity['date_posted']); ?>
                    </p>

                </div>

                <div style="margin-top: 20px;">
                    <a class="btn" href="apply.php?id=<?php echo (int)$opportunity['opportunity_id']; ?>">
                        Apply Now
                    </a>
                </div>

            </article>

        <?php endforeach; ?>

    <?php else: ?>

        <div class="no-results">

            <h3>No opportunities found</h3>

            <p>
                There are currently no open opportunities matching
                your search. Try another title or location.
            </p>

        </div>

    <?php endif; ?>

</div>

<footer>
    Campus Industrial Attachment Management System
</footer>

</body>
</html>