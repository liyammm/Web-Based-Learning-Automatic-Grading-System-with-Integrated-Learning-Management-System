<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/Dashboard/Classes/Dashboard.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/Dashboard/Classes/Assessment.php';

if (!isset($_SESSION['user_id'])) {
    die("User not logged in.");
}

$teacherDash = new Dashboard();
$assessment = new Assessment();

$user_id = $_SESSION['user_id'];
$teacher_name = htmlspecialchars($teacherDash->getUsername($user_id));
$courses = $teacherDash->getCourses($user_id);

// Get the course_id and assessment_id from the URL
$course_id = $_GET['course_id'] ?? null;
$assessment_id = $_GET['assessment_id'] ?? null;  // Make sure you pass the assessment_id as a URL parameter
$course_details = null;
$edit_assessment = null;

if ($course_id) {
    $stmt = $teacherDash->getCourseDetails($course_id);
    $course_details = $stmt->fetch_assoc();
}

// Fetch assessment details if assessment_id is provided
if ($assessment_id) {
    $edit_assessment = $assessment->getAssessmentDetails($assessment_id);  // Assuming this function exists
}

// Handling form submission for updating assessment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve POST data directly
    $assessment_name = $_POST['assessment_name'];
    $description = $_POST['description'];
    $due_date = $_POST['due_date'];
    $assignment_grade = $_POST['assessment_grade'];  // Use 'assessment_grade' instead of 'total_points'
    $assessment_id = $_POST['assessment_id'];
    $course_id = $_POST['course_id'];  // Ensure course_id is passed via the form

    // Basic validation
    if (empty($assessment_name) || empty($due_date) || empty($assignment_grade)) {
        $message = "All fields are required.";
    } else {
        try {
            // Update the assignment with the provided data
            $assessment->updateAssessment($assessment_id, $assessment_name, $description, $due_date, $assignment_grade);
            header("Location: create_assessment.php?course_id=" . $course_id);
            exit;
        } catch (Exception $e) {
            $message = "Error updating assessment: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Assessment</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="dashboard">
        <?php include '../../Includes/sidebar.php'; ?>

        <main class="main-content">
            <?php include '../../Includes/header.php'; ?>
            <section id="course-details" class="section">
                <?php if ($course_details): ?>
                    <h1>
                        <a href="../../index.php?course_id=<?php echo $course_id; ?>" class="back-button">&#8617;</a>
                        Manage Course: <?php echo htmlspecialchars($course_details['course_name']); ?>
                    </h1>

                    <?php if ($edit_assessment): ?>
                        <h2>Edit Assessment: <?php echo htmlspecialchars($edit_assessment['assessment_name']); ?></h2>
                    <?php else: ?>
                        <h2>Assessment not found.</h2>
                    <?php endif; ?>

                    <?php if (isset($message)): ?>
                        <p class="message"><?php echo $message; ?></p>
                    <?php endif; ?>

                    <form action="edit_assessment.php" method="POST" enctype="multipart/form-data">
                        <?php if ($edit_assessment): ?>
                            <input type="hidden" name="assessment_id" value="<?php echo $edit_assessment['assessment_id']; ?>">
                            <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course_id); ?>">

                            <label for="assessment_name">Assessment Name:</label>
                            <input type="text" id="assessment_name" name="assessment_name"
                                value="<?php echo htmlspecialchars($edit_assessment['assessment_name']); ?>" required><br>

                            <label for="description">Description (Optional):</label>
                            <textarea id="description" name="description"><?php echo htmlspecialchars($edit_assessment['description']); ?></textarea><br>

                            <label for="due_date">Due Date:</label>
                            <input type="datetime-local" id="due_date" name="due_date"
                                value="<?php echo date("Y-m-d\TH:i", strtotime($edit_assessment['due_date'])); ?>" required><br>

                            <label for="grade">Grade:</label>
                            <input type="number" id="grade" name="assessment_grade" step="0.01" min="0" max="100"
                                value="<?php echo htmlspecialchars($edit_assessment['total_points']); ?>" required><br>

                            <input type="submit" value="Update Assignment">
                        <?php else: ?>
                            <p>Assessment details are not available.</p>
                        <?php endif; ?>
                    </form>

                <?php else: ?>
                    <p>Select a course to view its details and management options.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>

</html>
