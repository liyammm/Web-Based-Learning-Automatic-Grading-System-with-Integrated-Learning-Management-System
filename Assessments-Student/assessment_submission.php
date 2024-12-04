<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/db.php';
require_once '../../../Classes/Dashboard.php';
require_once '../../../Classes/Student.php';
require_once '../../../Classes/Assessment.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['assessment_id'])) {
    header('Location: index.php');
    exit();
}

$studentDash = new Dashboard();
$assessment = new Assessment();

$user_id = $_SESSION['user_id'];
$assessment_id = $_GET['assessment_id'];

$student_name = htmlspecialchars($studentDash->getUsername($user_id));  // Get the student's name
$courses = $studentDash->getStudentCourse($user_id);
$coursesList = [];
while ($row = $courses->fetch_assoc()) {
    $coursesList[] = $row;
}

// Fetch the specific assignment details
$assessmentDetails = $assessment->getAssessmentDetails($assessment_id);

if (!$assessmentDetails) {
    header('Location: ../../index.php');
    exit();
}

$attempt_id = $assessment->startAssessmentAttempt($assessment_id, $user_id);
$questions = $assessment->getAssessmentQuestions($assessment_id);

// Check if questions are valid (array and not empty)
if (!is_array($questions) || empty($questions)) {
    echo "Error: No questions available.";
    exit();
}
if (isset($_POST['submit_assessment'])) {
    $student_responses = [];
    $total_score = 0; // Initialize total score
    $assessment_id = $_GET['assessment_id']; // Assuming assessment_id is in the URL
    
    // Fetch all the questions for this assessment from the database
    $questions = $assessment->getAssessmentQuestions($assessment_id);

    // Initialize total possible points from the database
    $total_possible_points = 0;

    foreach ($questions as $question) {
        // Accumulate the points for the total possible points
        $total_possible_points += $question['points'];

        // Process responses as usual (grading logic here)
        $response = [
            'question_id' => $question['question_id'],
            'selected_option_id' => $_POST['option_' . $question['question_id']] ?? null,
            'text_response' => $_POST['text_response_' . $question['question_id']] ?? null,
            'is_correct' => null, // Logic to determine if the response is correct
            'points_earned' => 0 // Logic to calculate points earned
        ];

        // Add grading logic based on question type (multiple choice, identification, etc.)
        if ($question['question_type'] == 'multiple_choice') {
            // Example logic for multiple choice grading
            $correct_answer = $assessment->getAnswer($question['question_id']);
            if ($response['selected_option_id']) {
                // Check if the selected option matches the correct answer
                $options = $assessment->getAssessmentOptions($question['question_id']);
                foreach ($options as $option) {
                    if ($option['option_id'] == $response['selected_option_id'] && $option['option_text'] == $correct_answer) {
                        $response['points_earned'] = $question['points'];
                        $response['is_correct'] = true;
                        break;
                    }
                }
            }
        }

        // Grading logic for identification (text-based) questions
        if ($question['question_type'] == 'identification') {
            $correct_answer = $assessment->getAnswerIdentification($question['question_id']);
            if (strcasecmp(trim($response['text_response']), $correct_answer) == 0) {
                $response['points_earned'] = $question['points'];
                $response['is_correct'] = true;
            }
        }

        // Add the response and earned points to the total score
        $student_responses[] = $response;
        $total_score += $response['points_earned'];
    }

    // Submit the student's responses and store them
    $assessment->submitAssessmentAttempt($attempt_id, $student_responses, $questions);

    // Update the assessment attempt status to completed
    $assessment->updateAssessmentAttemptStatus($attempt_id, 'completed');

    // Update the total score in the database
    $assessment->updateAttemptTotalScore($attempt_id, $total_score);

    // Redirect to grade.php with score and total possible points
    header('Location: grade.php?score=' . $total_score . '&total_points=' . $total_possible_points . '&message=' . urlencode('Assessment completed!'));
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assessment</title>
</head>
<body>
    <h1>Assessment: <?php echo htmlspecialchars($assessmentDetails['assessment_name']); ?></h1>
    <p><strong>Student: </strong><?php echo htmlspecialchars($student_name); ?></p> <!-- Student Identification -->

    <form method="POST" action="">
        <?php
        // Loop through the questions
        foreach ($questions as $question) {
            echo "<div class='question'>";
            echo "<h3>" . htmlspecialchars($question['question_text']) . "</h3>";

            // If the question type is 'multiple choice'
            if ($question['question_type'] == 'multiple_choice') {
                // Fetch the options for this question
                $options = $assessment->getAssessmentOptions($question['question_id']);
                
                // Check if options are available
                if (is_array($options) && count($options) > 0) {
                    foreach ($options as $option) {
                        echo "<label>";
                        echo "<input type='radio' name='option_" . $question['question_id'] . "' value='" . $option['option_id'] . "'>";
                        echo htmlspecialchars($option['option_text']);
                        echo "</label><br>";
                    }
                } else {
                    echo "No options available for this question.";
                }
            }

            // If the question type is 'identification' (or text-based response)
            if ($question['question_type'] == 'identification') {
                echo "<textarea name='text_response_" . $question['question_id'] . "' placeholder='Enter your response here'></textarea>";
            }

            echo "</div>";
        }
        ?>

        
        <button type="submit" name="submit_assessment">Submit Assessment</button>
    </form>
</body>
</html>
