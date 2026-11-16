<?php
// organization/profile.php - Organization edits its OWN profile
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "../config/database.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'organization') {
    header("Location: ../authentication/login.php");
    exit;
}
$orgId = (int) $_SESSION['user_id'];

if (empty($_SESSION['profile_csrf'])) {
    $_SESSION['profile_csrf'] = bin2hex(random_bytes(32));
}

$stmt = $pdo->prepare(
    "SELECT organization_name, email, phone, address, description
     FROM organizations WHERE organization_id = ?"
);
$stmt->execute([$orgId]);
$org = $stmt->fetch();

if (!$org) {
    header("Location: ../authentication/logout.php");
    exit;
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token   = $_POST['csrf_token'] ?? '';
    $name    = trim($_POST['organization_name'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $desc    = trim($_POST['description'] ?? '');

    if (!hash_equals($_SESSION['profile_csrf'], $token)) {
        $error = "Invalid form submission. Please try again.";
    } elseif ($name === '' || strlen($name) > 150) {
        $error = "Organization name is required (maximum 150 characters).";
    } elseif ($phone !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
        $error = "Enter a valid phone number (7 to 20 digits).";
    } elseif (strlen($address) > 255) {
        $error = "Address must be 255 characters or fewer.";
    } elseif (strlen($desc) > 5000) {
        $error = "Description must be 5000 characters or fewer.";
    } else {
        try {
            $update = $pdo->prepare(
                "UPDATE organizations
                 SET organization_name = ?, phone = ?, address = ?, description = ?
                 WHERE organization_id = ?"
            );
            $update->execute([
                $name,
                $phone !== '' ? $phone : null,
                $address !== '' ? $address : null,
                $desc !== '' ? $desc : null,
                $orgId
            ]);

            $_SESSION['name'] = $name;
            $success = "Profile updated successfully.";
        } catch (PDOException $e) {
            $error = "Something went wrong. Please try again.";
        }
    }

    $org['organization_name'] = $name;
    $org['phone'] = $phone;
    $org['address'] = $address;
    $org['description'] = $desc;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Organization Profile</title>
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
</p>        <h1>Organization Profile</h1>

        <?php if ($error): ?><p style="color:#b00020;"><?= htmlspecialchars($error) ?></p><?php endif; ?>
        <?php if ($success): ?><p style="color:#1b7a2e;"><?= htmlspecialchars($success) ?></p><?php endif; ?>

        <form method="post" action="profile.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['profile_csrf']) ?>">

            <label>Email (cannot be changed)</label><br>
            <input type="email" value="<?= htmlspecialchars($org['email']) ?>"
                   style="width:100%;" disabled><br><br>

            <label for="organization_name">Organization name *</label><br>
            <input type="text" id="organization_name" name="organization_name" maxlength="150"
                   style="width:100%;" value="<?= htmlspecialchars($org['organization_name']) ?>" required><br><br>

            <label for="phone">Phone</label><br>
            <input type="text" id="phone" name="phone" maxlength="20" style="width:100%;"
                   value="<?= htmlspecialchars($org['phone'] ?? '') ?>"><br><br>

            <label for="address">Address</label><br>
            <input type="text" id="address" name="address" maxlength="255" style="width:100%;"
                   value="<?= htmlspecialchars($org['address'] ?? '') ?>"><br><br>

            <label for="description">Description</label><br>
            <textarea id="description" name="description" rows="6"
                      style="width:100%;"><?= htmlspecialchars($org['description'] ?? '') ?></textarea><br><br>

            <button type="submit">Save Changes</button>
        </form>
    </div>
</body>
</html>