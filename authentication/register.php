<?php
session_start();

require_once "../config/database.php";

$success = "";
$error = "";

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $error = "Invalid form submission. Please refresh and try again.";
    } else {

        $role = $_POST['role'] ?? '';
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $phone = trim($_POST['phone'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";

        } elseif (strlen($password) < 8) {
            $error = "Your password must contain at least 8 characters.";
                } elseif ($phone !== '' && !preg_match('/^0[0-9]{9}$/', $phone)) {
            $error = "Phone number must be exactly 10 digits, for example 0712345678.";
        } elseif (!in_array($role, ['student', 'organization'], true)) {
            $error = "Please select a valid account type.";

        } else {

            try {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                if ($role === "student") {

                    $fullName = trim($_POST['full_name'] ?? '');
                    $course = trim($_POST['course'] ?? '');
                    $year = filter_var(
                        $_POST['year_of_study'] ?? '',
                        FILTER_VALIDATE_INT
                    );

                    if ($fullName === '' || $course === '' || !$year || $year < 1) {
                        $error = "Please complete all required Student details.";
                    } else {

                        $check = $pdo->prepare(
                            "SELECT student_id FROM students WHERE email = ?"
                        );
                        $check->execute([$email]);

                        if ($check->fetch()) {
                            $error = "A Student account with this email already exists.";
                        } else {
                            $insert = $pdo->prepare(
                                "INSERT INTO students
                                (full_name, email, password_hash, phone, course, year_of_study)
                                VALUES (?, ?, ?, ?, ?, ?)"
                            );

                            $insert->execute([
                                $fullName,
                                $email,
                                $passwordHash,
                                $phone,
                                $course,
                                $year
                            ]);

                            $success = "Student account created successfully!";
                        }
                    }

                } else {

                    $organizationName = trim($_POST['organization_name'] ?? '');
                    $address = trim($_POST['address'] ?? '');
                    $description = trim($_POST['description'] ?? '');

                    if ($organizationName === '') {
                        $error = "Please enter your organization name.";
                    } else {

                        $check = $pdo->prepare(
                            "SELECT organization_id FROM organizations WHERE email = ?"
                        );
                        $check->execute([$email]);

                        if ($check->fetch()) {
                            $error = "An Organization account with this email already exists.";
                        } else {
                            $insert = $pdo->prepare(
                                "INSERT INTO organizations
                                (organization_name, email, password_hash, phone, address, description)
                                VALUES (?, ?, ?, ?, ?, ?)"
                            );

                            $insert->execute([
                                $organizationName,
                                $email,
                                $passwordHash,
                                $phone,
                                $address,
                                $description
                            ]);

                            $success = "Organization account created successfully!";
                        }
                    }
                }

                if ($success !== "") {
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                }

            } catch (PDOException $e) {
                $error = "Registration could not be completed. Please try again.";
                error_log($e->getMessage());
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Campus Attachment System</title>

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
            max-width: 540px;
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

        .register-container h3 {
            color: #0f766e; font-size: 1.05rem; margin: 20px 0 12px;
            padding-bottom: 6px; border-bottom: 2px solid #ccfbf1;
        }

        label {
            display: block; font-weight: 600; color: #134e4a;
            margin-bottom: 6px; font-size: 0.92rem;
        }

        input, select, textarea {
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
        input:focus, select:focus, textarea:focus {
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

        .switch-link { text-align: center; margin-top: 18px; color: #475569; font-size: 0.93rem; }
        .switch-link a { color: #0f766e; font-weight: 600; text-decoration: none; }
        .switch-link a:hover { text-decoration: underline; }

        /* ---------- MESSAGES ---------- */
        .success-message, .error-message {
            padding: 14px 16px; border-radius: 8px; margin-bottom: 18px;
            font-weight: 600; font-size: 0.95rem;
        }
        .success-message { background: #e6f4ea; color: #1b7a2e; border-left: 4px solid #1b7a2e; }
        .success-message p { font-weight: 400; margin-top: 4px; }
        .error-message { background: #fdecea; color: #b00020; border-left: 4px solid #b00020; }

        /* Used by the role switcher script. Keep this rule. */
        .hidden { display: none; }

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
        <a class="btn-nav" href="login.php">Login</a>
    </nav>
</header>

<div class="auth-wrap">
    <div class="register-container">

        <div class="register-header">
            <h1>Create an Account</h1>
            <p>Campus Industrial Attachment Management System</p>
        </div>

        <?php if ($success): ?>
            <div class="success-message">
                <?= htmlspecialchars($success) ?>
                <p>You will be redirected to the login page shortly.</p>
            </div>

            <script>
                setTimeout(function () {
                    window.location.href = "login.php";
                }, 3000);
            </script>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-message">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
            >

            <label for="role">Register as</label>
            <select name="role" id="role" required>
                <option value="">Select account type</option>
                <option value="student">Student</option>
                <option value="organization">Organization</option>
            </select>

            <div id="student-fields" class="role-fields hidden">

                <h3>Student Information</h3>

                <label>Full Name</label>
                <input type="text" name="full_name">

                <label>Course</label>
                <input type="text" name="course">

                <label>Year of Study</label>
                <input type="number" name="year_of_study" min="1">

            </div>

            <div id="organization-fields" class="role-fields hidden">

                <h3>Organization Information</h3>

                <label>Organization Name</label>
                <input type="text" name="organization_name">

                <label>Address</label>
                <input type="text" name="address">

                <label>Description</label>
                <textarea name="description" rows="3"></textarea>

            </div>

            <div id="common-fields" class="hidden">

                <h3>Account Information</h3>

                <label>Email Address</label>
                <input type="email" name="email">

                <label>Phone Number</label>
                <input type="tel" name="phone" maxlength="10" pattern="0[0-9]{9}"
                       title="Exactly 10 digits, for example 0712345678">

                <label>Password</label>
                <input type="password" name="password"
                       minlength="8" required>

                <button type="submit">Create Account</button>

            </div>

        </form>

        <p class="switch-link">Already have an account? <a href="login.php">Login</a></p>

    </div>
</div>

<footer>
    &copy; <?= date('Y') ?> Campus Industrial Attachment Management System
</footer>

<script>
const roleSelect = document.getElementById("role");

const studentFields = document.getElementById("student-fields");
const organizationFields = document.getElementById("organization-fields");
const commonFields = document.getElementById("common-fields");

function updateRegistrationForm() {

    studentFields.classList.add("hidden");
    organizationFields.classList.add("hidden");
    commonFields.classList.add("hidden");

    if (roleSelect.value === "student") {
        studentFields.classList.remove("hidden");
        commonFields.classList.remove("hidden");
    }

    if (roleSelect.value === "organization") {
        organizationFields.classList.remove("hidden");
        commonFields.classList.remove("hidden");
    }
}

roleSelect.addEventListener("change", updateRegistrationForm);
</script>

</body>
</html>