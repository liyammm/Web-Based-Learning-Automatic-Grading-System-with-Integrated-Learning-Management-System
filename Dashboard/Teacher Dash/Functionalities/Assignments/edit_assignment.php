<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/Dashboard/Classes/Dashboard.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/Dashboard/Classes/Assignment.php';

if (!isset($_SESSION['user_id'])) {
    die("User not logged in.");
}

// Instantiate objects
$teacherDash = new Dashboard();
$assignment = new Assignment();

if (!isset($_SESSION['user_id'])) {
    die("User not logged in.");
}

$user_id = $_SESSION['user_id'];
$teacher_name = htmlspecialchars($teacherDash->getUsername($user_id));
$courses = $teacherDash->getCourses($user_id);

// Get the course_id from the URL
$course_id = $_GET['course_id'] ?? null;
$course_details = null;

if ($course_id) {
    $stmt = $teacherDash->getCourseDetails($course_id);
    $course_details = $stmt->fetch_assoc();
}

// Check for assignment_id in the URL
$edit_assignment = null;
if (isset($_GET['assignment_id'])) {
    $assignment_id = $_GET['assignment_id'];
    $edit_assignment = $assignment->getAssignmentData($assignment_id);  // Fetch assignment data

    if (!$edit_assignment) {
        die('Assignment not found.');
    }
}

// Handling form submission for updating assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve POST data directly
    $assignment_name = $_POST['assignment_name'];
    $description = $_POST['description'];
    $due_date = $_POST['due_date'];
    $assignment_grade = $_POST['assignment_grade'];
    $assignment_id = $_POST['assignment_id'];
    $course_id = $_POST['course_id'];  // Ensure course_id is passed via the form

    try {
        // Update the assignment with the provided data
        $assignment->updateAssignment($assignment_id, $assignment_name, $description, $due_date, $assignment_grade);
        $_SESSION['message'] = "Assignment successfully updated!";
        header("Location: create_assignment.php?course_id=" . $course_id);
        exit;
    } catch (Exception $e) {
        $message = "Error updating assignment: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Assignment</title>
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

                    <h2>Edit Assignment: <?php echo htmlspecialchars($edit_assignment['assignment_name']); ?></h2>

                    <?php if (isset($message)): ?>
                        <p class="message"><?php echo $message; ?></p>
                    <?php endif; ?>

                    <form action="edit_assignment.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="assignment_id" value="<?php echo $edit_assignment['assignment_id']; ?>">
                        <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course_id); ?>">

                        <label for="assignment_name">Assignment Name:</label>
                        <input type="text" id="assignment_name" name="assignment_name"
                            value="<?php echo htmlspecialchars($edit_assignment['assignment_name']); ?>" ><br>

                        <label for="description">Description (Optional):</label>
                        <textarea id="description" name="description"><?php echo htmlspecialchars($edit_assignment['description']); ?></textarea><br>

                        <label for="due_date">Due Date:</label>
                        <input type="datetime-local" id="due_date" name="due_date"
                            value="<?php echo date("Y-m-d\TH:i", strtotime($edit_assignment['due_date'])); ?>"><br>

                        <label for="grade">Grade:</label>
                        <input type="number" id="grade" name="assignment_grade" step="0.01" min="0" max="100"
                            value="<?php echo htmlspecialchars($edit_assignment['total_points']); ?>" ><br>

                        <input class="submit-btn" type="submit" value="Update Assignment">
                    </form>


                <?php else: ?>
                    <p>Select a course to view its details and management options.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>

</html>