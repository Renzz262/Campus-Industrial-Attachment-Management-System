<?php
// student/apply.php - Student applies for one opportunity
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "../config/database.php";

// 1. Only logged-in students may use this page
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
    header("Location: ../authentication/login.php");
    exit;
}
$studentId = (int) $_SESSION['user_id'];

// 2. CSRF token (a "ticket stub": the form carries one half, the session keeps the other)
if (empty($_SESSION['apply_csrf'])) {
    $_SESSION['apply_csrf'] = bin2hex(random_bytes(32));
}

// 3. Which opportunity? First visit uses the URL, form submission uses the hidden field
$opportunityId = (int) ($_GET['id'] ?? $_POST['opportunity_id'] ?? 0);

// 4. Load the opportunity, but ONLY if it exists and is still open
$stmt = $pdo->prepare(
    "SELECT o.opportunity_id, o.title, o.location, o.duration, org.organization_name
     FROM opportunities o
     JOIN organizations org ON o.organization_id = org.organization_id
     WHERE o.opportunity_id = ? AND o.status = 'open'"
);
$stmt->execute([$opportunityId]);
$opportunity = $stmt->fetch();

$error = "";
$success = "";

// 5. Has this student already applied?
$alreadyApplied = false;
if ($opportunity) {
    $check = $pdo->prepare(
        "SELECT 1 FROM applications WHERE student_id = ? AND opportunity_id = ?"
    );
    $check->execute([$studentId, $opportunityId]);
    $alreadyApplied = (bool) $check->fetchColumn();
}

// 6. Handle form submission
if ($opportunity && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $coverLetter = trim($_POST['cover_letter'] ?? '');

    if (!hash_equals($_SESSION['apply_csrf'], $token)) {
        $error = "Invalid form submission. Please try again.";
    } elseif ($alreadyApplied) {
        $error = "You have already applied for this opportunity.";
    } elseif (strlen($coverLetter) > 5000) {
        $error = "Cover letter is too long (maximum 5000 characters).";
    } else {
        try {
            $insert = $pdo->prepare(
                "INSERT INTO applications (student_id, opportunity_id, cover_letter)
                 VALUES (?, ?, ?)"
            );
            $insert->execute([
                $studentId,
                $opportunityId,
                $coverLetter !== "" ? $coverLetter : null
            ]);
            $success = "Your application has been submitted successfully.";
            $alreadyApplied = true;
        } catch (PDOException $e) {
            // 1062 = duplicate entry (the UNIQUE constraint caught a double-apply)
            if (($e->errorInfo[1] ?? 0) == 1062) {
                $error = "You have already applied for this opportunity.";
                $alreadyApplied = true;
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Apply for Opportunity</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <div style="max-width:700px; margin:40px auto; padding:0 16px;">
        <p><a href="opportunities.php">&larr; Back to opportunities</a></p>

        <?php if (!$opportunity): ?>
            <h1>Opportunity not available</h1>
            <p>This opportunity does not exist or is no longer open.</p>
        <?php else: ?>
            <h1>Apply: <?= htmlspecialchars($opportunity['title']) ?></h1>
            <p><strong>Organization:</strong> <?= htmlspecialchars($opportunity['organization_name']) ?></p>
            <p><strong>Location:</strong> <?= htmlspecialchars($opportunity['location']) ?>
               | <strong>Duration:</strong> <?= htmlspecialchars($opportunity['duration']) ?></p>

            <?php if ($error): ?>
                <p style="color:#b00020;"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
            <?php if ($success): ?>
                <p style="color:#1b7a2e;"><?= htmlspecialchars($success) ?></p>
            <?php endif; ?>

            <?php if ($alreadyApplied): ?>
                <p>You have applied for this opportunity.</p>
            <?php else: ?>
                <form method="post" action="apply.php">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['apply_csrf']) ?>">
                    <input type="hidden" name="opportunity_id" value="<?= (int) $opportunityId ?>">

                    <label for="cover_letter">Cover letter (optional)</label><br>
                    <textarea id="cover_letter" name="cover_letter" rows="10"
                              style="width:100%;"></textarea><br><br>

                    <button type="submit">Submit Application</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>