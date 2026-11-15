
<?php
session_start();

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'student'
) {
    header("Location: ../authentication/login.php");
    exit();
}
?>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'student'
) {
    header("Location: ../authentication/login.php");
    exit();
}

require_once "../config/database.php";

$studentId = $_SESSION['user_id'];

// Retrieve student information
$stmt = $pdo->prepare(
    "SELECT full_name, email, course, year_of_study
     FROM students
     WHERE student_id = ?"
);
$stmt->execute([$studentId]);
$student = $stmt->fetch();

if (!$student) {
    session_destroy();
    header("Location: ../authentication/login.php");
    exit();
}

// Count open opportunities
$stmt = $pdo->query(
    "SELECT COUNT(*) FROM opportunities WHERE status = 'open'"
);
$openOpportunities = $stmt->fetchColumn();

// Count applications submitted by this student
$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM applications WHERE student_id = ?"
);
$stmt->execute([$studentId]);
$totalApplications = $stmt->fetchColumn();

// Count pending applications
$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM applications
     WHERE student_id = ? AND status = 'pending'"
);
$stmt->execute([$studentId]);
$pendingApplications = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | Campus Attachment System</title>

    <style>
        .btn {
    display: inline-block;
    background: #174a83;
    color: white;
    padding: 12px 20px;
    text-decoration: none;
    border-radius: 6px;
    font-weight: bold;
}
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
            padding: 22px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        header h2 {
            font-size: 21px;
        }

        .logout {
            background: white;
            color: #174a83;
            padding: 10px 18px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
        }

        .container {
            max-width: 1100px;
            margin: 35px auto;
            padding: 0 20px;
        }

        .welcome {
            margin-bottom: 30px;
        }

        .welcome h1 {
            color: #174a83;
            margin-bottom: 10px;
        }

        .welcome p {
            color: #68778a;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.05);
        }

        .card h3 {
            color: #68778a;
            font-size: 15px;
            margin-bottom: 15px;
        }

        .card p {
            font-size: 30px;
            font-weight: bold;
            color: #174a83;
        }

        .profile {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.05);
        }

        .profile h2 {
            color: #174a83;
            margin-bottom: 20px;
        }

        .profile p {
            margin-bottom: 12px;
            line-height: 1.5;
        }

        footer {
            text-align: center;
            padding: 25px;
            color: #68778a;
            font-size: 13px;
        }
    </style>
</head>

<body>

<header>
    <h2>Campus Attachment Management System</h2>

    <a class="logout" href="../authentication/logout.php">
        Logout
    </a>
</header>

<div class="container">

    <section class="welcome">
        <h1>
            Welcome, <?php echo htmlspecialchars($student['full_name']); ?>!
        </h1>

        <p>
            Welcome to your Student Dashboard. Manage your attachment
            journey from here.
        </p>
    </section>
    <div style="margin-bottom: 25px;">
  <div style="display:flex; justify-content:space-between; align-items:center; margin:20px 0;">
    <a
        href="opportunities.php"
        style="
            display: inline-block;
            background: #174a83;
            color: white;
            padding: 12px 20px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
        "
    >
        Browse Attachment Opportunities
    </a>

    <a
        href="my_applications.php"
        style="
            display: inline-block;
            background: #174a83;
            color: white;
            padding: 12px 20px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
        "
    >
        View My Applications
    </a>
    
</div>
</div>

    <section class="cards">

        <div class="card">
            <h3>Available Opportunities</h3>
            <p><?php echo $openOpportunities; ?></p>
        </div>

        <div class="card">
            <h3>Applications Submitted</h3>
            <p><?php echo $totalApplications; ?></p>
        </div>

        <div class="card">
            <h3>Pending Applications</h3>
            <p><?php echo $pendingApplications; ?></p>
        </div>

    </section>

    <section class="profile">

        <h2>My Student Information</h2>

        <p>
            <strong>Full Name:</strong>
            <?php echo htmlspecialchars($student['full_name']); ?>
        </p>

        <p>
            <strong>Email:</strong>
            <?php echo htmlspecialchars($student['email']); ?>
        </p>

        <p>
            <strong>Course:</strong>
            <?php echo htmlspecialchars($student['course']); ?>
        </p>

        <p>
            <strong>Year of Study:</strong>
            <?php echo htmlspecialchars($student['year_of_study']); ?>
        </p>
        
        <p style="margin-top:20px;"><a class="btn" href="profile.php">Edit Profile</a></p>
         
        
    </section>
    
</div>



<footer>
    Campus Industrial Attachment Management System
</footer>

</body>
</html>