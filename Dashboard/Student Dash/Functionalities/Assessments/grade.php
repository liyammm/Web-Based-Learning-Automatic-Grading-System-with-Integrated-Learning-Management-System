<?php
if (isset($_GET['score']) && isset($_GET['total_points'])) {
    $score = htmlspecialchars($_GET['score']);
    $total_points = htmlspecialchars($_GET['total_points']);
    $message = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : 'Assessment completed!';
    
    // Calculate percentage
    $percentage = ($score / $total_points) * 100;
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
    <link rel="stylesheet" href="grade.css">
    <title>Assessment Result</title>
</head>
<body>
    <div class="result-card">
        <h1>Assessment Result</h1>
        <p class="message"><?php echo $message; ?></p>
        <p class="score">
            <strong>Your Score: </strong><?php echo $score; ?> / <?php echo $total_points; ?>
        </p>
        <p class="percentage">
            Percentage: <?php echo number_format($percentage, 1); ?>%
        </p>
        <a href="../../index.php" class="back-button">Back to Assessments</a>
    </div>
</body>
</html>