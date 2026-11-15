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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f3f6fb;
            color: #263445;
        }

        header {
            background: #174a83;
            color: white;
            padding: 20px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        header h2 {
            font-size: 20px;
        }

        .back-link {
            color: #174a83;
            background: white;
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 6px;
            font-weight: bold;
        }

        .container {
            max-width: 1100px;
            margin: 35px auto;
            padding: 0 20px;
        }

        .page-heading {
            margin-bottom: 25px;
        }

        .page-heading h1 {
            color: #174a83;
            margin-bottom: 10px;
        }

        .page-heading p {
            color: #68778a;
        }

        .search-box {
            background: white;
            padding: 22px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.05);
        }

        .search-form {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 12px;
        }

        .search-form input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccd5e0;
            border-radius: 6px;
            font-size: 14px;
        }

        .search-form button {
            background: #174a83;
            color: white;
            border: none;
            padding: 12px 22px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }

        .search-form button:hover {
            background: #10365f;
        }

        .clear-link {
            display: inline-block;
            margin-top: 12px;
            color: #174a83;
            text-decoration: none;
            font-size: 14px;
        }

        .results-heading {
            margin-bottom: 18px;
            color: #174a83;
        }

        .opportunity-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.05);
        }

        .opportunity-card h2 {
            color: #174a83;
            margin-bottom: 12px;
        }

        .organization {
            font-weight: bold;
            margin-bottom: 12px;
        }

        .opportunity-card p {
            line-height: 1.6;
            margin-bottom: 12px;
        }

        .details {
            background: #f3f6fb;
            padding: 14px;
            border-radius: 7px;
            margin-top: 15px;
        }

        .details p {
            margin-bottom: 7px;
        }

        .details p:last-child {
            margin-bottom: 0;
        }

        .no-results {
            background: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            color: #68778a;
        }

        footer {
            text-align: center;
            padding: 25px;
            color: #68778a;
            font-size: 13px;
        }

        @media (max-width: 650px) {
            .search-form {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<header>
    <h2>Campus Attachment Management System</h2>

    <a class="back-link" href="dashboard.php">
        Back to Dashboard
    </a>
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
    <a href="apply.php?id=<?php echo (int)$opportunity['opportunity_id']; ?>"
       style="display:inline-block; background:#174a83; color:white; padding:12px 22px;
              text-decoration:none; border-radius:6px; font-weight:bold;">
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
