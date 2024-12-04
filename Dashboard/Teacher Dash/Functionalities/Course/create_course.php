<?php
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/Dashboard/Classes/Dashboard.php';

if (!isset($_SESSION['user_id'])) {
    die("User not logged in.");
}
$teacherDash = new Dashboard();

$user_id = $_SESSION['user_id'];
$teacher_name = htmlspecialchars($teacherDash->getUsername($user_id));
$courses = $teacherDash->getCourses($user_id);

$course_id = isset($_GET['course_id']) ? $_GET['course_id'] : null;
$course_details = null;

// Initialize error message
$error_message = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_name = $_POST['course_name'];
    $assignment_weight = (float) $_POST['assignment_weight'];
    $quiz_weight = (float) $_POST['quiz_weight'];
    $exam_weight = (float) $_POST['exam_weight'];

    $total_weight = $assignment_weight + $quiz_weight + $exam_weight;

    if ($total_weight != 100) {
        $error_message = "The total weight must equal 100%. You entered $total_weight%.";
    } else {
        if ($teacherDash->createCourse($course_name, $user_id, $assignment_weight, $quiz_weight, $exam_weight)) {
            header('Location: ../../index.php');
            exit;
        } else {
            $error_message = "Error: Failed to create the course.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>
    <link rel="stylesheet" href="../../style.css">
</head>

<body>
    <div class="dashboard">
        <?php include '../../Includes/sidebar.php'; ?>

        <main class="main-content">
            <?php include '../../Includes/header.php'; ?>
            <section id="course-details" class="section">
                <h2>
                    <a href="../../index.php?course_id=<?php echo $course_id; ?>" class="back-button">&#8617;</a>
                    Create New Course
                </h2>

                <?php if (!empty($error_message)): ?>
                    <p style="color: red;"><?php echo htmlspecialchars($error_message); ?></p>
                <?php endif; ?>

                <form action="create_course.php" method="POST">
                    <label for="course_name">Course Name:</label>
                    <input type="text" id="course_name" name="course_name" required><br>

                    <label for="assignment_weight">Assignment Weight (%):</label>
                    <input type="number" id="assignment_weight" name="assignment_weight" required><br>

                    <label for="quiz_weight">Quiz Weight (%):</label>
                    <input type="number" id="quiz_weight" name="quiz_weight" required><br>

                    <label for="exam_weight">Exam Weight (%):</label>
                    <input type="number" id="exam_weight" name="exam_weight" required><br>

                    <input type="submit" value="Create Course">
                </form>
            </section>
        </main>
    </div>
</body>

</html>