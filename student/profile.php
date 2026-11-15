<?php
// student/profile.php - Student edits their OWN profile
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "../config/database.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
    header("Location: ../authentication/login.php");
    exit;
}
$studentId = (int) $_SESSION['user_id'];

if (empty($_SESSION['profile_csrf'])) {
    $_SESSION['profile_csrf'] = bin2hex(random_bytes(32));
}

// Load the logged-in student's record
$stmt = $pdo->prepare(
    "SELECT full_name, email, phone, course, year_of_study FROM students WHERE student_id = ?"
);
$stmt->execute([$studentId]);
$student = $stmt->fetch();

if (!$student) {
    header("Location: ../authentication/logout.php");
    exit;
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token  = $_POST['csrf_token'] ?? '';
    $name   = trim($_POST['full_name'] ?? '');
    $phone  = trim($_POST['phone'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $year   = filter_var($_POST['year_of_study'] ?? '', FILTER_VALIDATE_INT,
                         ['options' => ['min_range' => 1, 'max_range' => 6]]);

    if (!hash_equals($_SESSION['profile_csrf'], $token)) {
        $error = "Invalid form submission. Please try again.";
    } elseif ($name === '' || strlen($name) > 100) {
        $error = "Full name is required (maximum 100 characters).";
    } elseif ($phone !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
        $error = "Enter a valid phone number (7 to 20 digits).";
    } elseif ($course === '' || strlen($course) > 100) {
        $error = "Course is required (maximum 100 characters).";
    } elseif ($year === false) {
        $error = "Year of study must be a number from 1 to 6.";
    } else {
        try {
            // WHERE student_id comes from the SESSION, so only YOUR row can change
            $update = $pdo->prepare(
                "UPDATE students SET full_name = ?, phone = ?, course = ?, year_of_study = ?
                 WHERE student_id = ?"
            );
            $update->execute([$name, $phone !== '' ? $phone : null, $course, $year, $studentId]);

            $_SESSION['name'] = $name;   // keep the session in sync
            $success = "Profile updated successfully.";
        } catch (PDOException $e) {
            $error = "Something went wrong. Please try again.";
        }
    }

    // Re-display what the user typed
    $student['full_name'] = $name;
    $student['phone'] = $phone;
    $student['course'] = $course;
    $student['year_of_study'] = $_POST['year_of_study'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <div style="max-width:600px; margin:40px auto; padding:0 16px;">
<p>
    <a href="dashboard.php"
       style="display:inline-block; background:#174a83; color:white; padding:10px 18px;
              text-decoration:none; border-radius:6px; font-weight:bold;">
        &larr; Back to Dashboard
    </a>
</p>        <h1>My Profile</h1>

        <?php if ($error): ?><p style="color:#b00020;"><?= htmlspecialchars($error) ?></p><?php endif; ?>
        <?php if ($success): ?><p style="color:#1b7a2e;"><?= htmlspecialchars($success) ?></p><?php endif; ?>

        <form method="post" action="profile.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['profile_csrf']) ?>">

            <label>Email (cannot be changed)</label><br>
            <input type="email" value="<?= htmlspecialchars($student['email']) ?>"
                   style="width:100%;" disabled><br><br>

            <label for="full_name">Full name *</label><br>
            <input type="text" id="full_name" name="full_name" maxlength="100" style="width:100%;"
                   value="<?= htmlspecialchars($student['full_name']) ?>" required><br><br>

            <label for="phone">Phone</label><br>
            <input type="text" id="phone" name="phone" maxlength="20" style="width:100%;"
                   value="<?= htmlspecialchars($student['phone'] ?? '') ?>"><br><br>

            <label for="course">Course *</label><br>
            <input type="text" id="course" name="course" maxlength="100" style="width:100%;"
                   value="<?= htmlspecialchars($student['course'] ?? '') ?>" required><br><br>

            <label for="year_of_study">Year of study * (1 to 6)</label><br>
            <input type="number" id="year_of_study" name="year_of_study" min="1" max="6" style="width:100%;"
                   value="<?= htmlspecialchars((string) ($student['year_of_study'] ?? '')) ?>" required><br><br>

            <button type="submit">Save Changes</button>
        </form>
    </div>
</body>
</html>