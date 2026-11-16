<?php
// organization/edit_opportunity.php - Organization edits one of ITS OWN opportunities
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

$oppId = (int) ($_GET['id'] ?? $_POST['opportunity_id'] ?? 0);

// Load the opportunity ONLY if it belongs to the logged-in organization
$stmt = $pdo->prepare(
    "SELECT opportunity_id, title, description, requirements, location, duration, status
     FROM opportunities
     WHERE opportunity_id = ? AND organization_id = ?"
);
$stmt->execute([$oppId, $orgId]);
$opp = $stmt->fetch();

$error = "";
$success = "";

if ($opp && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $token        = $_POST['csrf_token'] ?? '';
    $title        = trim($_POST['title'] ?? '');
    $description  = trim($_POST['description'] ?? '');
    $requirements = trim($_POST['requirements'] ?? '');
    $location     = trim($_POST['location'] ?? '');
    $duration     = trim($_POST['duration'] ?? '');

    if (!hash_equals($_SESSION['org_csrf'], $token)) {
        $error = "Invalid form submission. Please try again.";
    } elseif ($title === '' || $description === '' || $location === '' || $duration === '') {
        $error = "Title, description, location and duration are required.";
    } elseif (strlen($title) > 150 || strlen($location) > 150 || strlen($duration) > 100) {
        $error = "Title and location must be 150 characters or fewer, duration 100 or fewer.";
    } elseif (strlen($description) > 5000 || strlen($requirements) > 5000) {
        $error = "Description and requirements must be 5000 characters or fewer.";
    } else {
        try {
            $update = $pdo->prepare(
                "UPDATE opportunities
                 SET title = ?, description = ?, requirements = ?, location = ?, duration = ?
                 WHERE opportunity_id = ? AND organization_id = ?"
            );
            $update->execute([
                $title, $description,
                $requirements !== '' ? $requirements : null,
                $location, $duration,
                $oppId, $orgId
            ]);
            $success = "Opportunity updated successfully.";
        } catch (PDOException $e) {
            $error = "Something went wrong. Please try again.";
        }
    }

    // Show what the user typed (or what was saved) in the form
    $opp['title'] = $title;
    $opp['description'] = $description;
    $opp['requirements'] = $requirements;
    $opp['location'] = $location;
    $opp['duration'] = $duration;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Opportunity</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <div style="max-width:700px; margin:40px auto; padding:0 16px;">
        <p><a href="opportunities.php">&larr; Back to My Opportunities</a></p>

        <?php if (!$opp): ?>
            <h1>Opportunity not found</h1>
            <p>This opportunity does not exist or does not belong to your organization.</p>
        <?php else: ?>
            <h1>Edit Opportunity</h1>
            <p>Status: <strong><?= htmlspecialchars(ucfirst($opp['status'])) ?></strong></p>

            <?php if ($error): ?><p style="color:#b00020;"><?= htmlspecialchars($error) ?></p><?php endif; ?>
            <?php if ($success): ?><p style="color:#1b7a2e;"><?= htmlspecialchars($success) ?></p><?php endif; ?>

            <form method="post" action="edit_opportunity.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['org_csrf']) ?>">
                <input type="hidden" name="opportunity_id" value="<?= (int) $opp['opportunity_id'] ?>">

                <label for="title">Title *</label><br>
                <input type="text" id="title" name="title" maxlength="150" style="width:100%;"
                       value="<?= htmlspecialchars($opp['title']) ?>" required><br><br>

                <label for="description">Description *</label><br>
                <textarea id="description" name="description" rows="6" style="width:100%;"
                          required><?= htmlspecialchars($opp['description']) ?></textarea><br><br>

                <label for="requirements">Requirements</label><br>
                <textarea id="requirements" name="requirements" rows="4"
                          style="width:100%;"><?= htmlspecialchars($opp['requirements'] ?? '') ?></textarea><br><br>

                <label for="location">Location *</label><br>
                <input type="text" id="location" name="location" maxlength="150" style="width:100%;"
                       value="<?= htmlspecialchars($opp['location']) ?>" required><br><br>

                <label for="duration">Duration *</label><br>
                <input type="text" id="duration" name="duration" maxlength="100" style="width:100%;"
                       value="<?= htmlspecialchars($opp['duration']) ?>" required><br><br>

                <button type="submit">Save Changes</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>