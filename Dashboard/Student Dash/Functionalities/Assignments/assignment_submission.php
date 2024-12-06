<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/db.php';
require_once '../../../Classes/Dashboard.php';
require_once '../../../Classes/Student.php';
require_once '../../../Classes/Assignment.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['assignment_id'])) {
    header('Location: index.php');
    exit();
}

$studentDash = new Dashboard();
$assignment = new Assignment();

$user_id = $_SESSION['user_id'];
$assignment_id = $_GET['assignment_id'];

$student_name = htmlspecialchars($studentDash->getUsername($user_id));
$courses = $studentDash->getStudentCourse($user_id);
$coursesList = [];
while ($row = $courses->fetch_assoc()) {
    $coursesList[] = $row;
}

// Fetch the specific assignment details
$assignmentDetails = $assignment->getAssignmentById($assignment_id);

if (!$assignmentDetails) {
    header('Location: ../../index.php');
    exit();
}

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_assignment'])) {
    $student_id = $_SESSION['user_id'];
    $assignment_id = $_POST['assignment_id'];
    $submission_text = $_POST['submission_text'];
    
    // Handle file upload
    $file_name = null;
    $file_data = null;
    
    if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] === UPLOAD_ERR_OK) {
        $file_name = $_FILES['assignment_file']['name'];
        $file_data = file_get_contents($_FILES['assignment_file']['tmp_name']);
    }

    // Check if student already has a submission
    if ($assignment->hasExistingSubmission($assignment_id, $student_id)) {
        // Update existing submission
        $assignment->updateSubmission($assignment_id, $student_id, $submission_text, $file_name, $file_data);
    } else {
        // Create new submission
        $assignment->submitAssignment($assignment_id, $student_id, $submission_text, $file_name, $file_data);
    }
    
    // Redirect back to student dashboard after submission
    header('Location: ../../index.php');
    exit();
}

// Get existing submission if any
$existingSubmission = $assignment->getStudentSubmission($assignment_id, $user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Assignment</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard">
        <?php include '../../Includes/sidebar.php'; ?>

        <main class="main-content">
            <?php include '../../Includes/header.php'; ?>
            
            <section class="section">
                <div class="assignment-submission">
                    <h2>Submit Assignment</h2>
                    
                    <div class="assignment-details">
                        <h3><?php echo htmlspecialchars($assignmentDetails['title'] ?? $assignmentDetails['file_name']); ?></h3>
                        <p class="description">
                            <strong>Instructions:</strong> 
                            <?php echo htmlspecialchars($assignmentDetails['description']); ?>
                        </p>
                        <p class="due-date">
                            <strong>Due Date:</strong> 
                            <?php echo date('F j, Y \a\t g:i A', strtotime($assignmentDetails['due_date'])); ?>
                        </p>
                    </div>

                    <form method="POST" enctype="multipart/form-data" class="submission-form">
                        <input type="hidden" name="assignment_id" value="<?php echo $assignment_id; ?>">
                        
                        <div class="form-group">
                            <label for="submission_text">Your Answer:</label>
                            <textarea 
                                id="submission_text"
                                name="submission_text" 
                                rows="4" 
                                cols="50"
                                placeholder="Type your answer or any comments about your submission here..."
                                required><?php echo htmlspecialchars($existingSubmission['submission_text'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="assignment_file">
                                Upload Your Work:
                                <span class="file-types">(Accepted formats: PDF, DOC, DOCX, TXT)</span>
                            </label>
                            <?php if (!empty($existingSubmission['file_name'])): ?>
                                <p class="current-file">Current file: <?php echo htmlspecialchars($existingSubmission['file_name']); ?></p>
                            <?php endif; ?>
                            <input 
                                type="file" 
                                id="assignment_file"
                                name="assignment_file"
                                accept=".pdf,.doc,.docx,.txt">
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="submit_assignment" class="submit-btn">
                                <?php echo $existingSubmission ? 'Update Submission' : 'Submit Assignment'; ?>
                            </button>
                            <a href="../../../Student Dash/index.php" class="cancel-btn">Cancel</a>
                        </div>
                    </form>
                </div>
            </section>
        </main>
    </div>
</body>
</html>