<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/Dashboard/Classes/Dashboard.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/Dashboard/Classes/Assessment.php';

// Verify teacher access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: /FinalProj/login.php");
    exit();
}

// Initialize objects
$teacherDash = new Dashboard();
$assessment = new Assessment(); // Keep this as an object

// Get teacher info
$user_id = $_SESSION['user_id'];
$teacher_name = htmlspecialchars($teacherDash->getUsername($user_id));

// Get courses for sidebar
$courses = $teacherDash->getCourses($user_id);

// Get current course info
$course_id = isset($_GET['course_id']) ? $_GET['course_id'] : null;
$course_details = null;
$assessments = []; // Initialize the variable to hold the assessments

if ($course_id) {
    // Get course details
    $stmt = $teacherDash->getCourseDetails($course_id);
    $course_details = $stmt->fetch_assoc();

    // Fetch assessments for this course
    $assessments = $assessment->fetchAssessment($course_id);
}

// Handle form submission for creating an assessment
// Inside your form submission handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    
    try {
        // Validate required fields
        if (empty($_POST['due_date'])) {
            throw new Exception("Due date is required");
        }

        // Prepare assessment data
        $assessment_data = [
            'name' => $_POST['assessment_name'],
            'type' => $_POST['assessment_type'],
            'description' => isset($_POST['description']) ? $_POST['description'] : null,
            'due_date' => $_POST['due_date'], // Will be in YYYY-MM-DDTHH:MM format
            'course_id' => $_POST['course_id'],
            'total_points' => $_POST['total_points']
        ];

        // Convert due_date from 'YYYY-MM-DDTHH:MM' to 'YYYY-MM-DD HH:MM:SS'
        $due_date = new DateTime($assessment_data['due_date']);
        $formatted_due_date = $due_date->format('Y-m-d H:i:s'); // This is the MySQL-compatible format

        // Get the current time (time when the assessment is created)
        $current_time = new DateTime(); // This is the current time when the form is submitted

        // Calculate the duration in minutes between now and the due date
        if ($due_date > $current_time) {
            $interval = $current_time->diff($due_date); // Get the difference between the current time and the due date
            // Convert the difference into minutes
            $duration_minutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
        } else {
            throw new Exception("Due date must be in the future.");
        }

        // Now, create the assessment using the $assessment object and the calculated duration
        $assessment->createAssessment(
            $assessment_data['course_id'],
            $assessment_data['name'],
            $assessment_data['type'],
            $assessment_data['description'],
            $assessment_data['due_date'],  // Ensure this is the correct format
            $duration_minutes,  // Store the calculated duration in minutes
            $assessment_data['total_points']
        );
        
        // Redirect after successful creation
        header("Location: create_assessment.php?course_id=" . $course_id);
        exit();
    } catch (Exception $e) {
        // Capture any errors
        $error_message = $e->getMessage();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Assessment</title>
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
                Manage Assessment: <?php echo htmlspecialchars($course_details['course_name']); ?>
            </h1>

            <?php if (isset($error_message)): ?>
                <div class="error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <!-- Add Assessment Form -->
            <div class="form-section">
                <h2>Add Assessment</h2>
                <form action="" method="POST" class="assessment" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course_id); ?>">

                    <div class="form-group">
                        <label for="assessment_name">Assessment Name:</label>
                        <input type="text" id="assessment_name" name="assessment_name" required>
                    </div>

                    <div class="form-group">
                        <label for="assessment_type">Assessment Type:</label>
                        <select id="assessment_type" name="assessment_type" required>
                            <option value="quiz">Quiz</option>
                            <option value="exam">Exam</option>
                        </select>
                    </div>



                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea id="description" name="description" rows="4"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="due_date">Due Date:</label>
                        <input type="datetime-local" id="due_date" name="due_date" required>
                    </div>


                    <div class="form-group">
                        <label for="total_points">Total Points:</label>
                        <input type="number" id="total_points" name="total_points" 
                               step="0.01" min="0" required>
                    </div>

                    <button type="submit" class="submit-btn">Add Assessment</button>
                </form>
            </div>

            <!-- Display Existing Assessments -->
            <div class="existing-assessments">
                <h2>Existing Assessments</h2>
                <?php if (!empty($assessments)): ?>
                    <div class="assessment-grid">
                        <?php foreach ($assessments as $asses): ?>
                            <div class="assessment-card">
                                <h3><?php echo htmlspecialchars($asses['assessment_name']); ?></h3>
                                
                                <p class="type">
                                    <strong>Type:</strong> 
                                    <?php echo htmlspecialchars($asses['assessment_type']); ?>
                                </p>

                                
                                <p class="due-date">
                                    <strong>Due Date:</strong>
                                    <?php 
                                    if (!empty($asses['due_date'])) {
                                        // Create a DateTime object from the 'due_date' string
                                        $date_obj = new DateTime($asses['due_date']);
                                        echo $date_obj->format('F j, Y, g:i A'); // Format as 'Month day, Year, Hour:Minute AM/PM'
                                    } else {
                                        echo '<span class="no-due-date">Not Set</span>';
                                    }
                                    ?>
                                </p>


                                <p class="duration">
                                    <strong>Duration: </strong> 
                                    <?php
                                        // Check if 'duration_minutes' is available
                                        if (!empty($asses['duration_minutes'])) {
                                            $duration_minutes = (int)$asses['duration_minutes'];
                                            $hours = floor($duration_minutes / 60);
                                            $minutes = $duration_minutes % 60;
                                            
                                            // Display the duration in hours and minutes format
                                            echo $hours > 0 ? "{$hours} hours {$minutes} minutes" : "{$minutes} minutes";
                                        } else {
                                            echo '<span class="no-duration">Duration not set</span>';
                                        }
                                    ?>
                                </p>
                                <p class="description">
                                    <strong>Description:</strong> 
                                    <?php echo !empty($asses['description']) 
                                        ? htmlspecialchars($asses['description']) 
                                        : '<span class="no-description">No description provided</span>'; ?>
                                </p>
                                
                                <p class="grade">
                                    <strong>Total Points:</strong> 
                                    <?php echo number_format($asses['total_points'], 2); ?>
                                </p>

                                <div class="assessment-actions">
                                    <a href="edit_assessment.php?assessment_id=<?php echo $asses['assessment_id']; ?>&course_id=<?php echo $course_id; ?>" 
                                       class="edit-btn">Edit</a>

                                    <form action="delete_assessment.php" method="POST" 
                                          onsubmit="return confirm('Are you sure you want to delete this assessment?');" 
                                          style="display:inline;">
                                        <input type="hidden" name="assessment_id" 
                                               value="<?php echo $asses['assessment_id']; ?>">
                                        <input type="hidden" name="course_id" 
                                               value="<?php echo htmlspecialchars($course_id); ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="delete-btn">Delete</button>

                                        
                                    <a href="create_question.php?assessment_id=<?php echo $asses['assessment_id']; ?>&course_id=<?php echo $course_id; ?>" 
                                       class="edit-btn">Create</a>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="no-assessment">No assessments available for this course.</p>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <p>Select a course to manage assessment.</p>
        <?php endif; ?>
    </section>
</main>
</div>

</body>
</html>
