<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/Dashboard/Classes/Dashboard.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/Dashboard/Classes/Assignment.php';

// Verify teacher access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: /FinalProj/login.php");
    exit();
}

// Initialize objects
$teacherDash = new Dashboard();
$assignment = new Assignment();

// Get teacher info 
$user_id = $_SESSION['user_id'];
$teacher_name = htmlspecialchars($teacherDash->getUsername($user_id));

// Get courses for sidebar
$courses = $teacherDash->getCourses($user_id);

// Get current course info
$course_id = isset($_GET['course_id']) ? $_GET['course_id'] : null;
$course_details = null;

if ($course_id) {
    $stmt = $teacherDash->getCourseDetails($course_id);
    $course_details = $stmt->fetch_assoc();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    try {
        // Validate required fields
        if (empty($_POST['due_date'])) {
            throw new Exception("Due date is required");
        }

        $assignment_data = [
            'name' => $_POST['assignment_name'],
            'type' => $_POST['assignment_type'],
            'description' => isset($_POST['description']) ? $_POST['description'] : null,
            'due_date' => $_POST['due_date'],
            'course_id' => $_POST['course_id'],
            'total_points' => $_POST['total_points']
        ];

        // Create assignment
        $assignment->createAssignment(
            $assignment_data['course_id'],
            $assignment_data['name'],
            $assignment_data['type'],
            $assignment_data['description'],
            $assignment_data['due_date'],
            $assignment_data['total_points']
        );

        $_SESSION['message'] = "Assignment successfully created!";
        header("Location: create_assignment.php?course_id=" . $course_id);
        exit();
    } catch (Exception $e) {
        $_SESSION['message'] = "Error creating assignment: " . $e->getMessage();
        $error_message = $e->getMessage();
    }
}

// Fetch existing assignments
$assignments = [];
if ($course_id) {
    $fetch_assignments = $assignment->fetchAssignment($course_id);
    while ($row = $fetch_assignments->fetch_assoc()) {
        $assignments[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Assignments</title>
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
                        Manage Assignments: <?php echo htmlspecialchars($course_details['course_name']); ?>
                    </h1>
                    <br>
                    <?php
                    if (isset($_SESSION['message'])): ?>
                        <div class="message"><?php echo $_SESSION['message']; ?></div>
                        <?php unset($_SESSION['message']); ?>
                    <?php endif; ?>

                    <?php if (isset($error_message)): ?>
                        <div class="error-message"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    <br>
                    <!-- Add Assignment Form -->
                    <div class="form-section">
                        <h2>Add Assignment</h2>
                        <form action="" method="POST" class="assignment-form" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course_id); ?>">

                            <div class="form-group">
                                <label for="assignment_name"><strong>Assignment Name:</strong></label>
                                <input type="text" id="assignment_name" name="assignment_name" required>
                            </div>

                            <div class="form-group">
                                <label for="assignment_type"><strong>Assignment Type:</strong>  </label>
                                <select id="assignment_type" name="assignment_type" required onchange="toggleFileUpload()">
                                    <option value="text">Text-Based</option>
                                    <option value="file">File Upload</option>
                                </select>
                            </div>

                            <div class="form-group" id="file_upload_section" style="display: none;">
                                <label for="assignment_file"><strong>Assignment File:</strong></label>
                                <input type="file" id="assignment_file" name="assignment_file">
                            </div>

                            <div class="form-group">
                                <label for="description"><strong>Description:</strong></label>
                                <textarea id="description" name="description" rows="4"></textarea>
                            </div>

                            <div class="form-group">
                                <label for="due_date"><strong>Due Date:</strong></label>
                                <input type="datetime-local" id="due_date" name="due_date" required>
                            </div>

                            <div class="form-group">
                                <label for="total_points"><strong>Total Points:</strong></label>
                                <input type="number" id="total_points" name="total_points"
                                    step="0.01" min="0" required>
                            </div>

                            <button type="submit" class="submit-btn">Add Assignment</button>
                        </form>
                    </div> <br>

                    <!-- Display Existing Assignments -->
                    <div class="existing-assignments">
                        <h2>Existing Assignments</h2>
                        <?php if (!empty($assignments)): ?>
                            <div class="assignment-grid"> <br>
                                <?php foreach ($assignments as $assign): ?>
                                    <div class="assignment-card">
                                        <h3><?php echo htmlspecialchars($assign['assignment_name']); ?></h3>

                                        <p class="type">
                                            <strong>Type:</strong>
                                            <?php echo htmlspecialchars($assign['assignment_type']); ?>
                                        </p>

                                        <p class="upload-date">
                                            <strong>Created:</strong>
                                            <?php echo date('F j, Y, g:i A', strtotime($assign['upload_date'])); ?>
                                        </p>

                                        <p class="due-date">
                                            <strong>Due Date:</strong>
                                            <?php
                                            if (!empty($assign['due_date'])) {
                                                echo date('F j, Y, g:i A', strtotime($assign['due_date']));
                                            } else {
                                                echo '<span class="no-due-date">Not Set</span>';
                                            }
                                            ?>
                                        </p>

                                        <p class="description">
                                            <strong>Description:</strong>
                                            <?php echo !empty($assign['description'])
                                                ? htmlspecialchars($assign['description'])
                                                : '<span class="no-description">No description provided</span>'; ?>
                                        </p>

                                        <p class="grade">
                                            <strong>Total Points:</strong>
                                            <?php echo number_format($assign['total_points'], 2); ?>
                                        </p>

                                        <div class="assignment-actions">
                                            <a href="edit_assignment.php?assignment_id=<?php echo $assign['assignment_id']; ?>&course_id=<?php echo $course_id; ?>"
                                                class="edit-btn">Edit</a>

                                            <form action="delete_assignment.php" method="POST"
                                                onsubmit="return confirm('Are you sure you want to delete this assignment?');"
                                                style="display:inline;">
                                                <input type="hidden" name="assignment_id"
                                                    value="<?php echo $assign['assignment_id']; ?>">
                                                <input type="hidden" name="course_id"
                                                    value="<?php echo htmlspecialchars($course_id); ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="delete-btn">Delete</button>
                                            </form>
                                        </div>
                                    </div> <br>
                                <?php endforeach; ?>
                            </div> 
                        <?php else: ?>
                            <p class="no-assignments">No assignments available for this course.</p>
                        <?php endif; ?>
                    </div>

                <?php else: ?>
                    <p>Select a course to manage assignments.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <!-- JS para sa toggle ng file upload -->
    <script>
        function toggleFileUpload() {
            const assignmentType = document.getElementById('assignment_type');
            const fileUploadSection = document.getElementById('file_upload_section');

            if (assignmentType.value === 'file') {
                fileUploadSection.style.display = 'block';
            } else {
                fileUploadSection.style.display = 'none';
            }
        }
    </script>
</body>

</html>