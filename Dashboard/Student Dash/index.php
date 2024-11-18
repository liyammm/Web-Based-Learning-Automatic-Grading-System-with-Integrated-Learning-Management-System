<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/db.php';  // Corrected the path here
require_once '../Classes/queries.php';


if (!isset($_SESSION['user_id'])) {
    die("User not logged in.");
}

$teacherDash = new Dashboard();
$teacher_id = $_SESSION['user_id'];
$teacher_name = $teacherDash->getTeacherName($teacher_id);
$courses = $teacherDash->getCourses($teacher_id);

// Check if course_id is provided in the URL
$course_id = isset($_GET['course_id']) ? $_GET['course_id'] : null;
$course_details = null;

if ($course_id) {
    // Fetch course details
    $course_details = $teacherDash->getCourseDetails($course_id)->fetch_assoc(); // Ensure we get the first result as an associative array
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
        <?php include 'Includes/sidebar.php'; ?> <!--Includes header for our student dash -->

        <main class="main-content">
            <?php include 'Includes/header.php'; ?> <!--Includes header for our student dash -->

            <section id="course-details" class="section">
                <?php if ($course_details): ?>
                    <h1>Manage Course: <?php echo htmlspecialchars($course_details['course_name']); ?></h1>

                    <div class="course-management-links">
                        <a href="Functionalities/enroll_students.php?course_id=<?php echo $course_id; ?>">Students</a>
                        <a href="Functionalities/learning_materials.php?course_id=<?php echo $course_id; ?>">Learning Materials</a>
                        <a href="manage_assignment.php?course_id=<?php echo $course_id; ?>">Assignments</a>
                        <a href="manage_quizzes.php?course_id=<?php echo $course_id; ?>">Quizzes</a>
                        <a href="manage_exam.php?course_id=<?php echo $course_id; ?>">Exams</a>

                        <!-- Form for Deleting Course -->
                        <form action="Functionalities/delete_course.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this course?');">
                            <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
                            <button type="submit">Delete Course</button>
                        </form>
                    </div>

                <?php else: ?>
                    <p>Select a course to view its details and management options.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>

</html>
