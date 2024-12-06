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
if (isset($_POST['auto_submit']) || isset($_POST['submit_assessment'])) {
    unset($_SESSION['assessment_start_time']);
    $student_responses = [];
    $total_score = 0;
    $total_possible_points = 0;

    foreach ($questions as $question) {
        $total_possible_points += $question['points'];
        $response = [
            'question_id' => $question['question_id'],
            'selected_option_id' => $_POST['option_' . $question['question_id']] ?? null,
            'text_response' => $_POST['text_response_' . $question['question_id']] ?? null,
            'is_correct' => null,
            'points_earned' => 0,
        ];

        // Grading logic
        if ($question['question_type'] == 'multiple_choice') {
            $correct_answer = $assessment->getAnswer($question['question_id']);
            if ($response['selected_option_id']) {
                $options = $assessment->getAssessmentOptions($question['question_id']);
                foreach ($options as $option) {
                    if ($option['option_id'] == $response['selected_option_id'] && $option['option_text'] == $correct_answer) {
                        $response['points_earned'] = $question['points'];
                        $response['is_correct'] = true;
                        break;
                    }
                }
            }
        } elseif ($question['question_type'] == 'identification') {
            $correct_answer = $assessment->getAnswerIdentification($question['question_id']);
            if (strcasecmp(trim($response['text_response']), $correct_answer) == 0) {
                $response['points_earned'] = $question['points'];
                $response['is_correct'] = true;
            }
        }

        $student_responses[] = $response;
        $total_score += $response['points_earned'];
    }

    $assessment->submitAssessmentAttempt($attempt_id, $student_responses, $questions);
    $assessment->updateAssessmentAttemptStatus($attempt_id, 'completed');
    $assessment->updateAttemptTotalScore($attempt_id, $total_score);
    $assessment->completeAssessmentAttempt($attempt_id);

    header('Location: grade.php?score=' . $total_score . '&total_points=' . $total_possible_points . '&message=' . urlencode('Assessment completed!'));
    exit();
}


$duration_minutes = $assessmentDetails['duration_minutes']; // Fetch the duration from the assessment details

if (!isset($_SESSION['assessment_start_time'])) {
    $_SESSION['assessment_start_time'] = time();
}

$elapsed_time = time() - $_SESSION['assessment_start_time'];
$remaining_seconds = ($duration_minutes * 60) - $elapsed_time;

// If time has expired, auto-submit
if ($remaining_seconds <= 0) {
    echo "<script>document.addEventListener('DOMContentLoaded', function() { 
        document.querySelector('form').submit();
    });</script>";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Assessment</title>
    <script>
    window.onload = function() {
        var remainingSeconds = <?php echo max(0, $remaining_seconds); ?>;
        var display = document.querySelector('#time');
        startTimer(remainingSeconds, display);
    };

    function startTimer(duration, display) {
        var timer = duration, minutes, seconds;
        var interval = setInterval(function () {
            minutes = parseInt(timer / 60, 10);
            seconds = parseInt(timer % 60, 10);

            minutes = minutes < 10 ? "0" + minutes : minutes;
            seconds = seconds < 10 ? "0" + seconds : seconds;

            display.textContent = minutes + ":" + seconds;

            if (--timer < 0) {
                clearInterval(interval); // Stop the timer
                autoSubmitForm(); // Trigger form submission
            }
        }, 1000);
    }

    function autoSubmitForm() {
        var form = document.querySelector('form');
        if (form) {
            // Add a hidden input to indicate auto-submit
            var autoSubmitInput = document.createElement('input');
            autoSubmitInput.type = 'hidden';
            autoSubmitInput.name = 'auto_submit';
            autoSubmitInput.value = 'true';
            form.appendChild(autoSubmitInput);

            // Submit the form
            form.submit();
        }
    }
</script>

</head>
<body>
    <h1>Assessment: <?php echo htmlspecialchars($assessmentDetails['assessment_name']); ?></h1>
    <p><strong>Student: </strong><?php echo htmlspecialchars($student_name); ?></p>
    <p><strong>Time Remaining: </strong><span id="time"></span></p> <!-- Timer display -->

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