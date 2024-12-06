<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/db.php';
require_once '../Classes/Dashboard.php';
require_once '../Classes/Student.php';
require_once '../Classes/Material.php';
require_once '../Classes/Assignment.php';
require_once '../Classes/Assessment.php';

if (!isset($_SESSION['user_id'])) {
    die("User not logged in.");
}

// Initialize objects and variables
$studentDash = new Dashboard();
$stud = new Student();
$learningMaterial = new Material();
$assignment = new Assignment();
$assessment = new Assessment();
$user_id = $_SESSION['user_id'];
$student_name = htmlspecialchars($studentDash->getUsername($user_id));

// Get current course details
$course_id = isset($_GET['course_id']) ? $_GET['course_id'] : null;
$current_course = null;

// Get courses
$courses = $studentDash->getStudentCourse($user_id);
$coursesList = [];
while ($row = $courses->fetch_assoc()) {
    $coursesList[] = $row;
}

// If no course_id is set, use the first course

if (!$course_id && !empty($coursesList)) {
    $course_id = $coursesList[0]['course_id'];
}

// Get the current course details
if ($course_id) {
    foreach ($coursesList as $course) {
        if ($course['course_id'] == $course_id) {
            $current_course = $course;
            break;
        }
    }
}

// Get assignments and materials for current course
$courseAssignments = [];
$courseMaterials = [];
$courseAssessments = [];
if ($course_id) {
    $fetch_assignments = $assignment->getAssignmentByCourse($course_id);
    while ($row = $fetch_assignments->fetch_assoc()) {
        $courseAssignments[] = $row;
    }

    $fetch_materials = $learningMaterial->getLearningMaterialsByCourse($course_id);
    while ($row = $fetch_materials->fetch_assoc()) {
        $courseMaterials[] = $row;
    }

    $fetch_assessments = $assessment->getAssessmentByCourse($course_id);
    while ($row = $fetch_assessments->fetch_assoc()) {
        $courseAssessments[] = $row;
    }
}

$assign = $assignment->fetchAssignment($course_id);

// Add this helper function at the top of the file, after the initializations
function isExpired($dueDate)
{
    return strtotime($dueDate) < time();
}

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="dashboard">
        <?php include 'Includes/sidebar.php'; ?>

        <main class="main-content">
            <?php include 'Includes/header.php'; ?>
            <section id="course-details" class="section">
                <?php if (!empty($coursesList)): ?>
                    <h2><?php echo htmlspecialchars($current_course['course_name']); ?></h2>

                    <!-- Learning Materials Section -->
                    <div class="materials-section">
                        <h3>Learning Materials</h3>
                        <?php if (!empty($courseMaterials)): ?>
                            <div class="material-list">
                                <?php foreach ($courseMaterials as $material): ?>
                                    <div class="material-item">
                                        <div class="material-content">
                                            <span><strong><?php echo htmlspecialchars($material['file_name']); ?></strong></span>
                                            <span class="description"><strong>Description: </strong><?php echo htmlspecialchars($material['description'] ?? ''); ?></span>
                                            <span class="uploaded"><strong>Uploaded: </strong><?php echo htmlspecialchars($material['upload_date'] ?? ''); ?></span>
                                        </div>
                                        <div class="material-actions">
                                            <a href="Functionalities/Materials/download_material.php?id=<?php echo $material['learning_materials_id']; ?>" class="button">Download</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p>No learning materials available.</p>
                        <?php endif; ?>
                    </div>
                    <br>
                    <hr><br>

                    <!-- Assignments Section -->
                    <div class="assignments-section">
                        <h3>Assignments</h3>
                        <?php if (!empty($courseAssignments)): ?>
                            <div class="assignment-list">
                                <?php foreach ($courseAssignments as $assignmentData): ?>
                                    <?php
                                    $submission = $assignment->getStudentSubmission($assignmentData['assignment_id'], $user_id);
                                    $isGraded = $submission && isset($submission['grade']);
                                    ?>
                                    <div class="assignment-item">
                                        <div class="assignment-content">
                                            <h4><?php echo htmlspecialchars($assignmentData['assignment_name']); ?></h4>
                                            <p class="due-date">
                                                <strong>Due: </strong>
                                                <?php echo date('F j, Y, g:i A', strtotime($assignmentData['due_date'])); ?>
                                            </p>
                                            <p class="description">
                                                <strong>Instructions: </strong>
                                                <?php echo htmlspecialchars($assignmentData['description'] ?: 'No instructions provided'); ?>
                                            </p>
                                            <p class="uploaded-date">
                                                <strong>Upload Date: </strong>
                                                <?php echo htmlspecialchars($assignmentData['upload_date']); ?>
                                            </p>
                                            <?php if ($submission): ?>
                                                <div class="submission-status">
                                                    <p><strong>Status: </strong>Submitted on <?php echo date('F j, Y, g:i A', strtotime($submission['submission_date'])); ?></p>
                                                    <?php if ($isGraded): ?>
                                                        <p class="grade">
                                                        <p><strong>Score: </strong><?php echo htmlspecialchars($submission['grade']); ?>/<?php echo htmlspecialchars($assignmentData['total_points']); ?></p>
                                                        <?php if (!empty($submission['feedback'])): ?>
                                                            <br><strong>Feedback: </strong><?php echo htmlspecialchars($submission['feedback']); ?>
                                                        <?php endif; ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!$isGraded): ?>
                                            <div class="assignment-actions">
                                                <?php if (isExpired($assignmentData['due_date'])): ?>
                                                    <br><br><br><br><p class="expired-notice">Submission period has ended</p>
                                                <?php else: ?>
                                                    <br> <br> <br> <br><a href="Functionalities/Assignments/assignment_submission.php?assignment_id=<?php echo $assignmentData['assignment_id']; ?><?php echo $submission ? '&edit=1' : ''; ?>"
                                                        class="button">
                                                        <?php echo $submission ? 'Modify Submission' : 'Add Submission'; ?>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p>No assignments available for this course.</p>
                        <?php endif; ?>
                    </div>
                    <br>
                    <hr><br>

                    <!-- Assessments Section -->
                    <div class="assessments-section">
                        <h3>Assessments</h3>
                        <?php if (!empty($courseAssessments)): ?>
                            <div class="assessment-list">
                                <?php foreach ($courseAssessments as $assessmentData): ?>
                                    <?php
                                    $assessmentResult = $assessment->getStudentAssessmentResult($assessmentData['assessment_id'], $user_id);
                                    $isCompleted = $assessmentResult && isset($assessmentResult['score']);
                                    ?>
                                    <div class="assessment-item">
                                        <div class="assessment-content">
                                            <h4><?php echo htmlspecialchars($assessmentData['assessment_name']); ?></h4>
                                            <p class="due-date">
                                                <strong>Due: </strong>
                                                <?php echo date('F j, Y, g:i A', strtotime($assessmentData['due_date'])); ?>
                                            </p>
                                            <p class="total-points">
                                                <strong>Total Points: </strong>
                                                <?php echo htmlspecialchars($assessmentData['total_points']); ?>
                                            </p>
                                            <p class="duration">
                                                <strong>Duration: </strong>
                                                <?php echo htmlspecialchars($assessmentData['duration_minutes']) .' minute/s'; ?>
                                            </p>
                                            <?php if ($isCompleted): ?>
                                                <p class="score"><strong>Score: </strong><?php echo htmlspecialchars($assessmentResult['score']) ?> <?php echo '/' .htmlspecialchars($assessmentData['total_points']); ?></p>
                                                <p class="status"><strong>Status: </strong>Completed</p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="assessment-actions">
                                            <?php if (isExpired($assessmentData['due_date'])): ?>
                                                <br><br><br><br><p class="expired-notice">Assessment period has ended</p>
                                            <?php elseif (!$isCompleted): ?>
                                                <a href="Functionalities/Assessments/assessment_submission.php?assessment_id=<?php echo $assessmentData['assessment_id']; ?>"
                                                    class="button">Take Assessment</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p>No assessments available for this course.</p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p>You are not enrolled in any courses.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>

</html>