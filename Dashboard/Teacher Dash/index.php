<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/db.php';
require_once '../Classes/Dashboard.php';
require_once '../Classes/Assignment.php';
require_once '../Classes/Material.php';
require_once '../Classes/Assessment.php';

// Verify teacher access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: /FinalProj/login.php");
    exit();
}

// Initialize objects
$teacherDash = new Dashboard();
$assignment = new Assignment();
$learningMaterial = new Material();
$assessment = new Assessment();

// Get teacher info
$user_id = $_SESSION['user_id'];
$teacher_name = htmlspecialchars($teacherDash->getUsername($user_id));

// Get courses for this teacher
$courses = $teacherDash->getCourses($user_id);

// Get current course details if course_id is set
$course_id = isset($_GET['course_id']) ? $_GET['course_id'] : null;
$course_details = null;
$assignments = [];
$materials = [];
$assessments = [];

if ($course_id) {
    $stmt = $teacherDash->getCourseDetails($course_id);
    $course_details = $stmt->fetch_assoc();

    // Fetch assignments for this course
    $fetch_assignments = $assignment->fetchAssignment($course_id);
    while ($row = $fetch_assignments->fetch_assoc()) {
        $assignments[] = $row;
    }

    // Fetch materials for this course
    $fetch_materials = $learningMaterial->getLearningMaterialsByCourse($course_id);
    while ($row = $fetch_materials->fetch_assoc()) {
        $materials[] = $row;
    }

    // Fetch assessments
    $fetch_assessments = $assessment->getAssessmentByCourse($course_id);
    while ($row = $fetch_assessments->fetch_assoc()) {
        $assessments[] = $row;
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
        <?php include 'Includes/sidebar.php'; ?>

        <main class="main-content">
            <?php include 'Includes/header.php'; ?>

            <section id="course-details" class="section">
                <?php if ($course_details): ?>
                    <h2><?php echo htmlspecialchars($course_details['course_name']); ?></h2>

                    <!-- Course Management Links -->
                    <div class="course-management">
                        <a href="Functionalities/Students/enroll-unenrollStudents.php?course_id=<?php echo $course_id; ?>" class="management-link">Manage Students</a>
                        <a href="Functionalities/Learning Material/learning_material.php?course_id=<?php echo $course_id; ?>" class="management-link">Manage Materials</a>
                        <a href="Functionalities/Assignments/create_assignment.php?course_id=<?php echo $course_id; ?>" class="management-link">Manage Assignments</a>
                        <a href="Functionalities/Assessment/create_assessment.php?course_id=<?php echo $course_id; ?>" class="management-link">Manage Assessment</a>
                        <a href="Functionalities/Grades/view_final_grades.php?course_id=<?php echo $course_id; ?>" class="management-link">View Final Grades</a>
                        <a href="Functionalities/Course/course_settings.php?course_id=<?php echo $course_id; ?>" class="management-link">Course Settings</a>
                    </div>

                    <!-- Display Assignments -->
                    <div class="assignments-section">
                        <h3>Assignments</h3>
                        <?php if (!empty($assignments)): ?>
                            <div class="assignment-list">
                                <?php foreach ($assignments as $assign): ?>
                                    <div class="assignment-item">
                                        <div class="assignment-content">
                                            <h4><?php echo htmlspecialchars($assign['assignment_name']); ?></h4>
                                            <p class="due-date">
                                                <?php if (!empty($assign['due_date'])): ?>
                                                    <strong>Due: </strong> <?php echo date('F j, Y, g:i A', strtotime($assign['due_date'])); ?>
                                                <?php else: ?>
                                                    <span class="no-due-date">No due date set</span>
                                                <?php endif; ?>
                                            </p><br>
                                        </div>
                                        <div class="assignment-actions">
                                            <a href="Functionalities/Assignments/view_submission.php?assignment_id=<?php echo $assign['assignment_id']; ?>" class="button">View Submissions</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p>No assignments created yet.</p>
                        <?php endif; ?>
                    </div> <br>
                    <hr> <br>

                    <!-- Display Assessments -->
                    <div class="assessments-section">
                        <h3>Assessments</h3>
                        <?php if (!empty($assessments)): ?>
                            <div class="assessment-list">
                                <?php foreach ($assessments as $assess): ?>
                                    <div class="assessment-item">
                                        <div class="assessment-content">
                                            <h4><?php echo htmlspecialchars($assess['assessment_name']); ?></h4>
                                            <p class="due-date">
                                                <?php if (!empty($assess['due_date'])): ?><strong>Due:</strong>
                                                    <?php echo date('F j, Y, g:i A', strtotime($assess['due_date'])); ?>
                                                <?php else: ?>
                                                    <span class="no-due-date">No due date set</span>
                                                <?php endif; ?>
                                            </p>
                                            <p class="total-points"><strong>Total Points: </strong><?php echo htmlspecialchars($assess['total_points']) ?></p>

                                        </div>
                                        <div class="assessment-actions">
                                            <a href="Functionalities/Assessment/view_responses.php?assessment_id=<?php echo $assess['assessment_id']; ?>" class="button">View Responses</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p>No assessments created yet.</p>
                        <?php endif; ?>
                    </div> <br>
                    <hr>

                    <!-- Display Materials -->
                    <div class="materials-section">
                        <h3>Learning Materials</h3>
                        <?php if (!empty($materials)): ?>
                            <div class="material-list">
                                <?php foreach ($materials as $material): ?>
                                    <div class="material-item">
                                        <div class="material-content">
                                            <span><strong><?php echo htmlspecialchars($material['file_name']); ?></strong></span>
                                            <span class="description"><strong>Description: </strong><?php echo htmlspecialchars($material['description']); ?></span>
                                            <span class="uploaded"><strong>Uploaded: </strong><?php echo htmlspecialchars($material['upload_date']); ?></span>
                                        </div>
                                        <div class="material-actions">
                                            <a href="Functionalities/Learning Material/download_material.php?material_id=<?php echo $material['learning_materials_id']; ?>"
                                                class="button">
                                                Download
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p>No materials uploaded yet.</p>
                        <?php endif; ?>
                    </div>


                <?php else: ?>
                    <p>Select a course from the sidebar to view details.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>

</html>