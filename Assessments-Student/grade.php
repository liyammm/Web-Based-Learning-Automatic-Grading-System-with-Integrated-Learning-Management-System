<?php
if (isset($_GET['score']) && isset($_GET['total_points'])) {
    $score = htmlspecialchars($_GET['score']);
    $total_points = htmlspecialchars($_GET['total_points']);
    $message = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : 'Assessment completed!';
} else {
    // Handle the case if parameters are missing
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assessment Result</title>
</head>
<body>
    <h1>Assessment Result</h1>
    <p><?php echo $message; ?></p>
    <p><strong>Your Score: </strong><?php echo $score; ?> / <?php echo $total_points; ?></p> <!-- Display score out of total points -->
</body>
</html>
