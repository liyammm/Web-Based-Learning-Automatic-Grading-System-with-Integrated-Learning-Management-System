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

$assessment_details = null;
$assessments = []; // Initialize the variable to hold the assessments

if ($course_id) {
    // Get course details
    $stmt = $teacherDash->getCourseDetails($course_id);
    $course_details = $stmt->fetch_assoc();

    // Fetch assessments for this course
    $assessments = $assessment->fetchAssessment($course_id);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_question') {
    try {
        // Validate required fields
        if (empty($_POST['question_text']) || empty($_POST['question_type']) || empty($_POST['points'])) {
            throw new Exception("All fields are required.");
        }

        // Prepare question data
        $question_data = [
            'assessment_id' => $assessment_id,
            'question_text' => $_POST['question_text'],
            'question_type' => $_POST['question_type'],
            'points' => $_POST['points'],
            'question_order' => $_POST['question_order'],
        ];

        // Insert the question into the database
        $question_id = $assessment->addQuestion(
            $question_data['assessment_id'],
            $question_data['question_text'],
            $question_data['question_type'],
            $question_data['points'],
            $question_data['question_order']
        );

      

        // Check if the question type is multiple choice
        if ($question_data['question_type'] === 'multiple_choice' && isset($_POST['options'])) {
            if (isset($_POST['correct_option'])) {
                foreach ($_POST['options'] as $index => $option) {
                    // Determine if the option is correct (based on the index)
                    $is_correct = ($_POST['correct_option'] == $index) ? 1 : 0;

                    // Validate option text before inserting (ensuring it's not empty)
                    if (empty($option)) {
                        throw new Exception("Option " . ($index + 1) . " cannot be empty.");
                    }

                    // Insert the option into the question_options table
                    $assessment->addOptions($question_id, $option, $is_correct, $index + 1);
                }
            } else {
                throw new Exception("Correct option not selected for multiple choice question.");
            }
        }

        // Check if the question type is identification
        if ($question_data['question_type'] === 'identification' && isset($_POST['correct_answer'])) {
            $correct_answer = $_POST['correct_answer'];

            // Validate the correct answer
            if (empty($correct_answer)) {
                throw new Exception("Correct answer for identification question cannot be empty.");
            }

            echo $question_id. $correct_answer;
            $assessment->addAnswer($question_id, $correct_answer);
            
        }

        // Redirect to the same page with success message
        header("Location: create_question.php?assessment_id=$assessment_id&course_id=$course_id&success=true");
        exit();

    } catch (Exception $e) {
        // Display the error message
        $error_message = $e->getMessage();
        echo "Error: " . $error_message;
    }
}

// Fetch assessment details (for display purposes)
$assessment_details = $assessment->getAssessmentDetails($assessment_id);

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Question</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="dashboard">
<?php include '../../Includes/sidebar.php'; ?>

<main class="main-content">
    <?php include '../../Includes/header.php'; ?>
    
    <section id="assessment-details" class="section">
        <h1>
            <a href="../../index.php?course_id=<?php echo $course_id; ?>" class="back-button">&#8617;</a>
            Create Questions for Assessment: <?php echo htmlspecialchars($assessment_details['assessment_name']); ?>
        </h1>

        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['success']) && $_GET['success'] == 'true'): ?>
            <div class="success-message">Question added successfully!</div>
        <?php endif; ?>

        <div class="form-section">
            <h2>Add Question</h2>
            <form action="" method="POST" class="question-form">
                <input type="hidden" name="action" value="add_question">
                <input type="hidden" name="assessment_id" value="<?php echo htmlspecialchars($assessment_id); ?>">
                
                <div class="form-group">
                    <label for="question_text">Question Text:</label>
                    <textarea id="question_text" name="question_text" rows="4" required></textarea>
                </div>

                <div class="form-group">
                    <label for="question_type">Question Type:</label>
                    <select id="question_type" name="question_type"  required>
                        <option value="multiple_choice">Multiple Choice</option>
                        <option value="identification">Identification</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="points">Points:</label>
                    <input type="number" id="points" name="points" step="0.01" min="0" required>
                </div>

                <div class="form-group">
                    <label for="question_order">Question Order:</label>
                    <input type="number" id="question_order" name="question_order" min="1" required>
                </div>

                <!-- Options for Multiple Choice -->
                <div class="form-group" id="options-section" style="display:none;">
                    <label>Options (Multiple Choice):</label>
                    <div id="options">
                        <input type="text" name="options[]" placeholder="Option 1" required><br>
                        <input type="text" name="options[]" placeholder="Option 2" required><br>
                    </div>
                    <button type="button" id="add-option">Add Option</button>
                    <label for="correct_option">Correct Option:</label>
                    <select id="correct_option" name="correct_option">
                        <option value="0">Option 1</option>
                        <option value="1">Option 2</option>
                    </select>
                </div>

              <!-- Correct Answer for Identification -->
            <div class="form-group" id="answer-section" style="display:none;">
                <label for="correct_answer">Correct Answer (Identification):</label>
                <input type="text" id="correct_answer" name="correct_answer" placeholder="Enter Correct Answer" required>
            </div>

                <button type="submit" class="submit-btn">Add Question</button>

                <div class="assessment-actions">                  
                <a href="display_question.php?assessment_id=<?php echo $assessment_details['assessment_id']; ?>&course_id=<?php echo $course_id; ?>" class="edit-btn">See Form</a>

            </form>
        </div>

    </section>
</main>


<script>
            // Add an event listener to the 'question_type' dropdown (for when the user changes the selected question type)
            document.getElementById('question_type').addEventListener('change', function() {
                var questionType = this.value;
                var optionsSection = document.getElementById('options-section');
                var answerSection = document.getElementById('answer-section');
                var correctAnswerInput = document.getElementById('correct_answer');
                var optionInputs = document.querySelectorAll('input[name="options[]"]');

                if (questionType === 'multiple_choice') {
                    optionsSection.style.display = 'block';
                    answerSection.style.display = 'none';
                    correctAnswerInput.removeAttribute('required');
                    optionInputs.forEach(input => input.setAttribute('required', ''));
                } else if (questionType === 'identification') {
                    optionsSection.style.display = 'none';
                    answerSection.style.display = 'block';
                    correctAnswerInput.setAttribute('required', '');
                    optionInputs.forEach(input => input.removeAttribute('required'));
                }
            });

            // Add an event listener to the "Add Option" button (to dynamically add new options for multiple choice questions)
            document.getElementById('add-option').addEventListener('click', function() {
                var optionsDiv = document.getElementById('options'); // Get the div that contains the options for multiple choice
                var newOptionInput = document.createElement('input'); // Create a new input element for the new option
                newOptionInput.type = 'text'; // Set the input type to text
                newOptionInput.name = 'options[]'; // Set the name attribute to 'options[]' (which will store an array of options in the form)
                newOptionInput.placeholder = 'Option'; // Set a placeholder text for the input field

                // Append the new option input element to the 'options' div and add a line break
                optionsDiv.appendChild(newOptionInput); // Append the new input element for the option
                optionsDiv.appendChild(document.createElement('br')); // Append a line break (to separate the new option from previous options)

                // Dynamically add the new option to the "Correct Option" dropdown list
                var correctOptionSelect = document.getElementById('correct_option'); // Get the dropdown that lets the user select the correct option
                var newOptionIndex = correctOptionSelect.options.length; // Get the current number of options in the dropdown (i.e., the index for the new option)
                var newOption = document.createElement('option'); // Create a new option element for the dropdown
                newOption.value = newOptionIndex; // Set the value of the new option to the current index
                newOption.text = 'Option ' + (newOptionIndex + 1); // Set the text of the new option to display as "Option 3", "Option 4", etc.
                correctOptionSelect.appendChild(newOption); // Append the new option to the dropdown
            });

            // Initialize the correct form section based on the selected question type when the page loads
            window.addEventListener('load', function() {
                var questionType = document.getElementById('question_type').value;
                var optionsSection = document.getElementById('options-section');
                var answerSection = document.getElementById('answer-section');
                var correctAnswerInput = document.getElementById('correct_answer');
                var optionInputs = document.querySelectorAll('input[name="options[]"]');

                if (questionType === 'multiple_choice') {
                    optionsSection.style.display = 'block';
                    answerSection.style.display = 'none';
                    correctAnswerInput.removeAttribute('required');
                    optionInputs.forEach(input => input.setAttribute('required', ''));
                } else {
                    optionsSection.style.display = 'none';
                    answerSection.style.display = 'block';
                    correctAnswerInput.setAttribute('required', '');
                    optionInputs.forEach(input => input.removeAttribute('required'));
                }
            });
        </script>

</body>
</html>
