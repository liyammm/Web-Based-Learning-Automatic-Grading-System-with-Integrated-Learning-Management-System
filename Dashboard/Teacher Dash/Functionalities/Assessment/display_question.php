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

$teacherDash = new Dashboard();
$assessment = new Assessment(); // Keep this as an object

// Get teacher info
$user_id = $_SESSION['user_id'];
$teacher_name = htmlspecialchars($teacherDash->getUsername($user_id));

// Get courses for sidebar
$courses = $teacherDash->getCourses($user_id);

// Get current course info
$course_id = isset($_GET['course_id']) ? $_GET['course_id'] : null;
$assessment_id = isset($_GET['assessment_id']) ? $_GET['assessment_id'] : null;
$course_details = null;
$assessments = []; // Initialize the variable to hold the assessments
$questions = $assessment->fetchQuestions($assessment_id);

if ($assessment_id) {
    // Fetch questions for the specific assessment
    $questions = $assessment->fetchQuestions($assessment_id);
}


if ($course_id) {
    // Get course details
    $stmt = $teacherDash->getCourseDetails($course_id);
    $course_details = $stmt->fetch_assoc();

    // Fetch assessments for this course
    $assessments = $assessment->fetchAssessment($course_id);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Display Questions</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="display_question.css">
</head>

<body>

    <div class="dashboard">
        <?php include '../../Includes/sidebar.php'; ?>

        <main class="main-content">
            <?php include '../../Includes/header.php'; ?>

            <section id="assessment-details" class="section">
                <h1>Questions for Assessment</h1>

                <?php if (empty($questions)): ?>
                    <p>No questions found for this assessment.</p>
                <?php else: ?>
                    <?php foreach ($questions as $question): ?>
                        <div class="question">
                            <!-- Displaying Question Type -->
                            <p class="question-type">
                                <strong>Question Type:</strong> <?php echo htmlspecialchars($question['question_type']); ?>
                            </p>

                            <!-- Displaying Question Text -->
                            <p><strong>Question:</strong> <?php echo htmlspecialchars($question['question_text']); ?></p>

                            <!-- Displaying Points -->
                            <p><strong>Points:</strong> <span class="points"><?php echo htmlspecialchars($question['points']); ?></span></p>

                            <?php
                            // Fetch the options for the current question (only for multiple choice)
                            if ($question['question_type'] === 'multiple_choice') {
                                $options = $assessment->fetchOption($question['question_id']);
                            ?>
                                <p><strong>Options:</strong>
                                    <!-- Displaying Options for Multiple Choice -->
                                    <?php if (!empty($options)): ?>
                                <ul>
                                    <?php foreach ($options as $option): ?>
                                        <li> <?php echo htmlspecialchars($option['option_text']); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p>No options found for this question.</p>
                            <?php endif; ?>
                        <?php } ?>

                        <!-- Displaying Correct Answer -->
                        <div class="correct-answer">
                            <strong>Correct Answer:</strong>
                            <?php
                            if ($question['question_type'] === 'multiple_choice') {
                                // For multiple choice, fetch the correct answer from the options
                                $correct_answer = $assessment->getAnswer($question['question_id']);
                                if ($correct_answer):
                            ?>
                                    <p><?php echo htmlspecialchars($correct_answer); ?></p>
                                <?php else: ?>
                                    <p>No correct answer found.</p>
                                <?php endif; ?>
                                <?php } elseif ($question['question_type'] === 'identification') {
                                // For identification questions, directly fetch and display the correct answer
                                $correct_answer = $assessment->getAnswerIdentification($question['question_id']);
                                if ($correct_answer):
                                ?>
                                    <p><?php echo htmlspecialchars($correct_answer); ?></p>
                                <?php else: ?>
                                    <p>No correct answer found.</p>
                                <?php endif; ?>
                            <?php } ?>
                        </div>

                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <a href="create_question.php?course_id=<?php echo $course_id; ?>&assessment_id=<?php echo $assessment_id; ?>" class="edit-btn">
                    Back to Add Question
                </a>
            </section>

        </main>
    </div>

</body>

</html>