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
$assessment = new Assessment();

// Get teacher info
$user_id = $_SESSION['user_id'];
$teacher_name = htmlspecialchars($teacherDash->getUsername($user_id));

// Get courses for sidebar
$courses = $teacherDash->getCourses($user_id);

// Get current course info
$course_id = isset($_GET['course_id']) ? $_GET['course_id'] : null;
$course_details = null;
$assessments = [];

if ($course_id) {
    $stmt = $teacherDash->getCourseDetails($course_id);
    $course_details = $stmt->fetch_assoc();
    $assessments = $assessment->fetchAssessment($course_id);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    try {
        if (empty($_POST['due_date']) || empty($_POST['due_time'])) {
            throw new Exception("Due date and time are required");
        }

        // Combine date and time into date
        $date = strtotime($_POST['due_date'] . ' ' . $_POST['due_time']);
        if ($date === false) {
            throw new Exception("Invalid date or time format");
        }

        $assessment_data = [
            'course_id' => $_POST['course_id'],
            'name' => $_POST['assessment_name'],
            'type' => $_POST['assessment_type'],
            'description' => $_POST['description'],
            'due_date' => date('Y-m-d H:i:s', $date),
            'duration_minutes' => $_POST['duration_minutes'],
            'question_count' => $_POST['question_count'],
            'total_points' => 0,
        ];

        $assessment->createAssessment(
            $assessment_data['course_id'],
            $assessment_data['name'],
            $assessment_data['type'],
            $assessment_data['description'],
            $assessment_data['due_date'],
            $assessment_data['duration_minutes'],
            $assessment_data['total_points'],
            $assessment_data['question_count'],
        );

        $_SESSION['message'] = "Assessment successfully created!";
        header("Location: create_assessment.php?course_id=" . $course_id);
        exit();
    } catch (Exception $e) {
        $_SESSION['message'] = "Error creating assessment: " . $e->getMessage();
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
                        <form action="" method="POST" class="assessment">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course_id); ?>">

                            <div class="form-group">
                                <label for="assessment_name"><strong>Assessment Name:</strong></label>
                                <input type="text" id="assessment_name" name="assessment_name" required>
                            </div>

                            <div class="form-group">
                                <label for="assessment_type"><strong>Assessment Type:</strong></label>
                                <select id="assessment_type" name="assessment_type" required>
                                    <option value="quiz">Quiz</option>
                                    <option value="exam">Exam</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="description"><strong>Description:</strong></label>
                                <textarea id="description" name="description" rows="4"></textarea>
                            </div>

                            <div class="form-group">
                                <label for="due_date"><strong>Due Date:</strong></label>
                                <input type="date" id="due_date" name="due_date" required>
                            </div>

                            <div class="form-group">
                                <label for="due_time"><strong>Due Time:</strong></label>
                                <input type="time" id="due_time" name="due_time" required>
                            </div>

                            <div class="form-group">
                                <label for="duration_minutes"><strong>Duration (minutes):</strong></label>
                                <input type="number" id="duration_minutes" name="duration_minutes" step="1" min="0" required>
                            </div>

                            <div class="form-group">
                                <label for="question_count"><strong>Number of Questions:</strong></label>
                                <input type="number" id="question_count" name="question_count" min="1" required>
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
                                                echo date('F j, Y, g:i A', strtotime($asses['due_date']));
                                            } else {
                                                echo '<span class="no-due-date">Not Set</span>';
                                            }
                                            ?>
                                        </p>

                                        <p class="duration">
                                            <strong>Duration: </strong>
                                            <?php
                                            if (!empty($asses['duration_minutes'])) {
                                                $duration_minutes = (int)$asses['duration_minutes'];
                                                $hours = floor($duration_minutes / 60);
                                                $minutes = $duration_minutes % 60;
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
                                            <?php 
                                            // Calculate total points dynamically
                                            $total_points = 0;
                                            $questions = $assessment->getQuestions($asses['assessment_id']);
                                            foreach ($questions as $question) {
                                                $total_points += $question['points'];
                                            }
                                            echo number_format($total_points, 2); 
                                            ?>
                                        </p>

                                        <div class="assessment-actions">
                                            <a href="edit_assessment.php?assessment_id=<?php echo $asses['assessment_id']; ?>&course_id=<?php echo $course_id; ?>" 
                                               class="edit-btn">Edit</a>
                                            
                                            <form action="delete_assessment.php" method="POST" 
                                                  onsubmit="return confirm('Are you sure you want to delete this assessment?');" 
                                                  style="display:inline;">
                                                <input type="hidden" name="assessment_id" value="<?php echo $asses['assessment_id']; ?>">
                                                <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course_id); ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="delete-btn">Delete</button>
                                            </form>

                                            <a href="create_question.php?assessment_id=<?php echo $asses['assessment_id']; ?>&course_id=<?php echo $course_id; ?>" 
                                               class="edit-btn">Create Question</a>
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