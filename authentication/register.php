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
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<div class="register-container">

    <div class="register-header">
        <h1>Create an Account</h1>
        <p>Campus Industrial Attachment Management System</p>
    </div>

    <?php if ($success): ?>
        <div class="success-message">
            <?= htmlspecialchars($success) ?>
            <p>You can now proceed to the login page once it is available.</p>
        </div>
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
            <input type="tel" name="phone">

            <label>Password</label>
            <input type="password" name="password"
                   minlength="8" required>

            <button type="submit">Create Account</button>

        </div>

    </form>

</div>

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
