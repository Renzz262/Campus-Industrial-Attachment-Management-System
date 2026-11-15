
<?php
session_start();

require_once "../config/database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $role = $_POST['role'] ?? '';
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (!in_array($role, ['student', 'organization'], true)) {
        $error = "Please select a valid account type.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";

    } elseif ($password === '') {
        $error = "Please enter your password.";

    } else {

        if ($role === "student") {

            $stmt = $pdo->prepare(
                "SELECT student_id, full_name, email, password_hash
                 FROM students WHERE email = ?"
            );

        } else {

            $stmt = $pdo->prepare(
                "SELECT organization_id, organization_name, email, password_hash
                 FROM organizations WHERE email = ?"
            );
        }

        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {

            session_regenerate_id(true);

            if ($role === "student") {

                $_SESSION['user_id'] = $user['student_id'];
                $_SESSION['role'] = 'student';
                $_SESSION['name'] = $user['full_name'];

                header("Location: ../student/dashboard.php");
                exit();

            } else {

                $_SESSION['user_id'] = $user['organization_id'];
                $_SESSION['role'] = 'organization';
                $_SESSION['name'] = $user['organization_name'];

                header("Location: ../organization/dashboard.php");
                exit();
            }

        } else {
            $error = "Incorrect email or password.";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Campus Attachment System</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>

<body>

<div class="register-container">

    <div class="register-header">
        <h1>Welcome Back</h1>
        <p>Login to your Campus Industrial Attachment account</p>
    </div>

    <?php if ($error !== ""): ?>
        <div class="error-message">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php">

        <label for="role">Account Type</label>
        <select name="role" id="role" required>
            <option value="">Select Account Type</option>
            <option value="student">Student</option>
            <option value="organization">Organization</option>
        </select>

        <label for="email">Email Address</label>
        <input
            type="email"
            name="email"
            id="email"
            placeholder="Enter your email"
            required
        >

        <label for="password">Password</label>
        <input
            type="password"
            name="password"
            id="password"
            placeholder="Enter your password"
            required
        >

        <button type="submit">Login</button>

    </form>

    <p style="text-align:center; margin-top:20px;">
        Don't have an account?
        <a href="register.php">Register here</a>
    </p>

</div>

</body>
</html>
