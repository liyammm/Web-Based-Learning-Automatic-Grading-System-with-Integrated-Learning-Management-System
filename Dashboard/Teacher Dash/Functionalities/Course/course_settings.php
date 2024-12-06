<?php
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/Dashboard/Classes/Dashboard.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/Dashboard/Classes/Course.php';

if (!isset($_SESSION['user_id'])) {
    die("User not logged in.");
}

$teacherDash = new Dashboard();
$courseSettings = new CourseSettings();

$user_id = $_SESSION['user_id'];
$teacher_name = htmlspecialchars($teacherDash->getUsername($user_id));
$courses = $teacherDash->getCourses($user_id);

$course_id = isset($_GET['course_id']) ? $_GET['course_id'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_course'])) {
        $course_id = $_POST['course_id'];
        $course_name = $_POST['course_name'];
        $assignment_weight = $_POST['assignment_weight'];
        $quiz_weight = $_POST['quiz_weight'];
        $exam_weight = $_POST['exam_weight'];

        $total_weight = $assignment_weight + $quiz_weight + $exam_weight;
        if ($total_weight !== 100) {
            $_SESSION['error'] = "The total weight must equal 100%. Current total: {$total_weight}%";
            header("Location: course_settings.php?course_id=$course_id");
            exit();
        }

        $courseSettings->updateCourse($course_id, $course_name, $assignment_weight, $quiz_weight, $exam_weight);
        $_SESSION['message'] = "Course settings updated successfully!";
        header("Location: course_settings.php?course_id=$course_id");
        exit();
    }

    if (isset($_POST['delete_course'])) {
        $course_id = $_POST['course_id'];
        $courseSettings->deleteCourse($course_id);
        header("Location: ../../index.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="dashboard">
        <?php include '../../Includes/sidebar.php'; ?>

        <main class="main-content">
            <?php include '../../Includes/header.php'; ?>
            <section id="course-details" class="section">
                <h2>
                    <a href="../../index.php?course_id=<?php echo $course_id; ?>" class="back-button">&#8617;</a>
                    Course Settings
                </h2>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="error-message">
                        <?php
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="success-message">
                        <?php
                        echo $_SESSION['message'];
                        unset($_SESSION['message']);
                        ?>
                    </div>
                <?php endif; ?>
                <?php if ($course_id): ?>
                    <?php $courseDetails = $courseSettings->getCourseDetails($course_id); ?>

                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                        <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">

                        <label for="course_name">Course Name:</label>
                        <input type="text" id="course_name" name="course_name" value="<?php echo htmlspecialchars($courseDetails['course_name']); ?>" required>

                        <label for="assignment_weight">Assignment Weight (%):</label>
                        <input type="number" id="assignment_weight" name="assignment_weight" value="<?php echo $courseDetails['assignment_weight']; ?>" required>

                        <label for="quiz_weight">Quiz Weight (%):</label>
                        <input type="number" id="quiz_weight" name="quiz_weight" value="<?php echo $courseDetails['quiz_weight']; ?>" required>

                        <label for="exam_weight">Exam Weight (%):</label>
                        <input type="number" id="exam_weight" name="exam_weight" value="<?php echo $courseDetails['exam_weight']; ?>" required>

                        <button type="submit" name="update_course" class="update-btn">Update Course</button>
                        <button type="submit" name="delete_course" class="delete-btn" onclick="return confirm('Are you sure you want to delete this course?');">Delete Course</button>
                    </form>
                <?php else: ?>
                    <p>No course selected.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>

</html>