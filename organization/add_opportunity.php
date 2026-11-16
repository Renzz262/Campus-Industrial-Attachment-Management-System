<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "../config/database.php";

// 1. Organization-only access
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'organization') {
    header("Location: ../authentication/login.php");
    exit;
}
$orgId = (int) $_SESSION['user_id'];

// 2. CSRF token
if (empty($_SESSION['add_opp_csrf'])) {
    $_SESSION['add_opp_csrf'] = bin2hex(random_bytes(32));
}

$error = "";
$success = "";
$title = $description = $requirements = $location = $duration = "";

// 3. Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token        = $_POST['csrf_token'] ?? '';
    $title        = trim($_POST['title'] ?? '');
    $description  = trim($_POST['description'] ?? '');
    $requirements = trim($_POST['requirements'] ?? '');
    $location     = trim($_POST['location'] ?? '');
    $duration     = trim($_POST['duration'] ?? '');

    if (!hash_equals($_SESSION['add_opp_csrf'], $token)) {
        $error = "Invalid form submission. Please try again.";
    } elseif ($title === '' || $description === '' || $location === '' || $duration === '') {
        $error = "Title, description, location and duration are required.";
    } elseif (strlen($title) > 150 || strlen($location) > 150 || strlen($duration) > 100) {
        $error = "Title and location must be 150 characters or fewer, duration 100 or fewer.";
    } elseif (strlen($description) > 5000 || strlen($requirements) > 5000) {
        $error = "Description and requirements must be 5000 characters or fewer.";
    } else {
        try {
            // organization_id comes from the SESSION, never from the form
            $stmt = $pdo->prepare(
                "INSERT INTO opportunities
                 (organization_id, title, description, requirements, location, duration)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $orgId,
                $title,
                $description,
                $requirements !== '' ? $requirements : null,
                $location,
                $duration
            ]);
            $success = "Opportunity posted successfully.";
            $title = $description = $requirements = $location = $duration = "";
        } catch (PDOException $e) {
            $error = "Something went wrong. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Post Opportunity</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <div style="max-width:700px; margin:40px auto; padding:0 16px;">
        <p>
            <a href="dashboard.php">&larr; Dashboard</a> |
            <a href="opportunities.php">My Opportunities</a>
        </p>

        <h1>Post an Attachment Opportunity</h1>

        <?php if ($error): ?>
            <p style="color:#b00020;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p style="color:#1b7a2e;"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <form method="post" action="add_opportunity.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['add_opp_csrf']) ?>">

            <label for="title">Title *</label><br>
            <input type="text" id="title" name="title" maxlength="150" style="width:100%;"
                   value="<?= htmlspecialchars($title) ?>" required><br><br>

            <label for="description">Description *</label><br>
            <textarea id="description" name="description" rows="6" style="width:100%;"
                      required><?= htmlspecialchars($description) ?></textarea><br><br>

            <label for="requirements">Requirements</label><br>
            <textarea id="requirements" name="requirements" rows="4"
                      style="width:100%;"><?= htmlspecialchars($requirements) ?></textarea><br><br>

            <label for="location">Location *</label><br>
            <input type="text" id="location" name="location" maxlength="150" style="width:100%;"
                   value="<?= htmlspecialchars($location) ?>" required><br><br>

            <label for="duration">Duration * (e.g. 3 months)</label><br>
            <input type="text" id="duration" name="duration" maxlength="100" style="width:100%;"
                   value="<?= htmlspecialchars($duration) ?>" required><br><br>

            <button type="submit">Post Opportunity</button>
        </form>
    </div>
</body>
</html>