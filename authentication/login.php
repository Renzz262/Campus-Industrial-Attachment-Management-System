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

        .nav-links { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .nav-links a {
            color: white; text-decoration: none; font-size: 0.95rem; font-weight: 500;
            padding: 8px 16px; border-radius: 8px;
            transition: background 0.25s ease, transform 0.2s ease;
        }
        .nav-links a:hover { background: rgba(255, 255, 255, 0.18); transform: translateY(-1px); }
        .nav-links .btn-nav { background: white; color: #0f766e; font-weight: 600; }
        .nav-links .btn-nav:hover { background: #ccfbf1; color: #0f766e; }

        /* ---------- FORM CARD ---------- */
        .auth-wrap {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 16px;
        }

        .register-container {
            width: 100%;
            max-width: 460px;
            background: rgba(255, 255, 255, 0.95);
            padding: 34px 30px;
            border-radius: 14px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.18);
            border: 1px solid rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(8px);
            animation: fadeUp 0.7s ease backwards;
        }

        .register-header { text-align: center; margin-bottom: 24px; }
        .register-header h1 { color: #0f766e; font-size: 1.7rem; margin-bottom: 6px; }
        .register-header p { color: #475569; font-size: 0.95rem; }

        label {
            display: block; font-weight: 600; color: #134e4a;
            margin-bottom: 6px; font-size: 0.92rem;
        }

        input, select {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #b7d9d5;
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.95rem;
            margin-bottom: 16px;
            color: #134e4a;
            background: white;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #0f766e;
            box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.18);
        }

        button[type="submit"] {
            width: 100%;
            background: #0f766e;
            color: white;
            border: none;
            padding: 13px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            font-family: inherit;
            cursor: pointer;
            margin-top: 4px;
            transition: background 0.25s ease, transform 0.2s ease;
        }
        button[type="submit"]:hover { background: #0d9488; transform: translateY(-1px); }

        .switch-link { text-align: center; margin-top: 20px; color: #475569; font-size: 0.93rem; }
        .switch-link a { color: #0f766e; font-weight: 600; text-decoration: none; }
        .switch-link a:hover { text-decoration: underline; }

        /* ---------- MESSAGES ---------- */
        .error-message {
            padding: 14px 16px; border-radius: 8px; margin-bottom: 18px;
            font-weight: 600; font-size: 0.95rem;
            background: #fdecea; color: #b00020; border-left: 4px solid #b00020;
        }

        /* ---------- FOOTER ---------- */
        footer {
            text-align: center; padding: 26px 20px;
            color: rgba(255, 255, 255, 0.85); font-size: 0.9rem;
            background: rgba(15, 118, 110, 0.85);
            backdrop-filter: blur(6px);
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(25px); }
        }

        @media (max-width: 640px) {
            header { padding: 14px 5%; flex-direction: column; align-items: flex-start; }
            header h2 { font-size: 1.2rem; }
            .register-container { padding: 26px 20px; }
        }
    </style>
</head>

<body>

<header>
    <h2>Campus Attachment Management System</h2>
    <nav class="nav-links">
        <a href="../index.php">Home</a>
        <a class="btn-nav" href="register.php">Register</a>
    </nav>
</header>

<div class="auth-wrap">
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

        <p class="switch-link">
            Don't have an account?
            <a href="register.php">Register here</a>
        </p>

    </div>
</div>

<footer>
    &copy; <?= date('Y') ?> Campus Industrial Attachment Management System
</footer>

</body>
</html>