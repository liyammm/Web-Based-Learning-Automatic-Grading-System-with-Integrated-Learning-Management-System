<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/Dashboard/Classes/UserSettings.php';

if (!isset($_SESSION['user_id'])) {
    die("User not logged in.");
}

$course_id = isset($_GET['course_id']) ? $_GET['course_id'] : null;

$user_id = $_SESSION['user_id'];
$user = new UserSettings();

// Fetch current user data
$current_user_data = $user->getUserData($user_id);

// Handle form submission for updating user details
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_first_name'])) {
    $new_first_name = $_POST['new_first_name'];
    $new_last_name = $_POST['new_last_name'];
    $new_username = $_POST['new_username'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Check if passwords match
    if ($new_password === $confirm_password) {
        $user->updateDetails($new_first_name, $new_last_name, $new_username, $new_password, $user_id);
        header("Location: user_settings.php");
        exit();
    } else {
        $message = "Passwords do not match!";
    }
}

// Handle account deletion
if (isset($_POST['delete_account'])) {
    $delete = $user->deleteAccount($user_id);
    if ($delete) {
        session_destroy();
        header('Location: /FinalProj/Authentication/login.php');
        exit();
    } else {
        $message = "Failed to delete account. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Settings Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="dashboard">
        <header>
            <a class="back-button" href="../../index.php?course_id=<?php echo $course_id; ?>">&#8617;</a>
            <h1>User Settings</h1>
        </header>

        <section class="user-info">
            <h2>Your Information</h2>
            <p><strong>Name:</strong> <span id="user-name"><?php echo htmlspecialchars($current_user_data['first_name'] . ' ' . $current_user_data['last_name']); ?></span></p>
            <p><strong>Email:</strong> <span id="user-email"><?php echo htmlspecialchars($current_user_data['email']); ?></span></p>
            <p><strong>Username:</strong> <span id="user-username"><?php echo htmlspecialchars($current_user_data['username']); ?></span></p>
        </section>

        <section class="edit-info">
            <h2>Edit Your Information</h2>
            <form action="user_settings.php" method="POST">
                <label for="new-first-name">New First Name:</label>
                <input type="text" id="new-first-name" name="new_first_name" value="<?php echo htmlspecialchars($current_user_data['first_name']); ?>" required>

                <label for="new-last-name">New Last Name:</label>
                <input type="text" id="new-last-name" name="new_last_name" value="<?php echo htmlspecialchars($current_user_data['last_name']); ?>" required>

                <label for="new-username">New Username:</label>
                <input type="text" id="new-username" name="new_username" value="<?php echo htmlspecialchars($current_user_data['username']); ?>" required>

                <label for="new-password">New Password:</label>
                <input type="password" id="new-password" name="new_password">

                <label for="confirm-password">Confirm New Password:</label>
                <input type="password" id="confirm-password" name="confirm_password">

                <button type="submit">Update Information</button>
            </form>
        </section>

        <section class="delete-account">
            <h2>Delete Account</h2>
            <p>Deleting your account will remove all of your data. This action cannot be undone.</p>
            <form action="user_settings.php" method="POST" onsubmit="return confirm('Are you sure you want to delete your account?')";>
                <button type="submit" name="delete_account" class="delete-btn">Delete Account</button>
            </form>
            <?php if (isset($message)) : ?>
                <p class="error-message"><?php echo htmlspecialchars($message); ?></p>
            <?php endif; ?>
        </section>
    </div>
</body>

</html>
