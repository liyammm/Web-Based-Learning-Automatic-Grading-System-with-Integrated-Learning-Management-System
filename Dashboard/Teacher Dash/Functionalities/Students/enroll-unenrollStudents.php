<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/Dashboard/Classes/Dashboard.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/Dashboard/Classes/Student.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    die('User not logged in.');
}

$teacherDash = new Dashboard();
$stud = new Student();

$user_id = $_SESSION['user_id'];
$teacher_name = htmlspecialchars($teacherDash->getUsername($user_id));
$courses = $teacherDash->getCourses($user_id);
$course_id = isset($_GET['course_id']) ? $_GET['course_id'] : null;
$course_details = null;

if ($course_id) {
    $stmt = $teacherDash->getCourseDetails($course_id);
    $course_details = $stmt->fetch_assoc();
}

// Enrolling a student
if (isset($_POST['add_student'])) {
    $student_emailUsername = $_POST['student_emailUsername'];
    $course_id = $_POST['course_id']; // course_id is passed as GET parameter

    // Check if the user is a student
    $user = $stud->getUserByUsername($student_emailUsername);
    if ($user && $user['role'] == 'student') {
        // Check if the student is already enrolled in this course
        $isEnrolled = $stud->checkStudentEnrolled($user['user_id'], $course_id);

        if ($isEnrolled) {
            $_SESSION['error'] = 'Student is already enrolled in this course.';
        } else {
            $addResult = $stud->enrollStudents($student_emailUsername, $course_id);
            if ($addResult) {
                $_SESSION['message'] = 'Student successfully added to the course';
            } else {
                $_SESSION['error'] = 'Error adding student to the course';
            }
        }
    } else {
        $_SESSION['error'] = 'Cannot find students or only students can be added to this course';
    }

    header("Location: enroll-unenrollStudents.php?course_id=" . htmlspecialchars($course_id));
    exit();
}

// Unenrolling a student
if (isset($_POST['unenroll_student'])) {
    $student_id = $_POST['student_id']; // student_id is passed in the form
    $unenrollResult = $stud->unenrollStudent($student_id, $course_id);
    if ($unenrollResult) {
        $_SESSION['message'] = 'Student successfully unenrolled from the course';
    } else {
        $_SESSION['error'] = 'Error unenrolling student from the course';
    }

    header("Location: enroll-unenrollStudents.php?course_id=" . htmlspecialchars($course_id));
    exit();
}


// Get the list of enrolled students (after form submission or page load)
$students = $stud->getEnrolledStudents($course_id);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students</title>
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
                        Manage Course: <?php echo htmlspecialchars($course_details['course_name']); ?>
                    </h1>
                    <br> <br>

                    <!-- Message display after actions like adding/unenrolling students -->
                    <?php if (isset($_SESSION['message'])): ?>
                        <p class="success"><?php echo $_SESSION['message'];
                                            unset($_SESSION['message']); ?></p>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['error'])): ?>
                        <p class="error"><?php echo $_SESSION['error'];
                                            unset($_SESSION['error']); ?></p>
                    <?php endif; ?>

                    <h2>Enrolled Students</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($students && $students->num_rows > 0):
                                while ($student = $students->fetch_assoc()):
                            ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['first_name']) . ' ' . htmlspecialchars($student['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($student['username']); ?></td>
                                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                                        <td>
                                            <!-- Unenroll button -->
                                            <form action="" method="POST" style="display:inline;">
                                                <input type="hidden" name="student_id" value="<?php echo $student['user_id']; ?>">
                                                <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
                                                <button type="submit" name="unenroll_student" onclick="return confirm('Are you sure you want to unenroll this student?');">Unenroll</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php
                                endwhile;
                            else:
                                ?>
                                <tr>
                                    <td colspan="4">No students enrolled yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <h2>Add Student</h2> <br>
                    <form action="" method="POST">
                        <label for="student_identifier">Username or Email:</label>
                        <input type="text" id="student_identifier" name="student_emailUsername" placeholder="Enter username or email" required>
                        <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
                        <button type="submit" name="add_student">Add Student</button>
                    </form>

                <?php else: ?>
                    <p>Select a course to view its details and management options.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>

</html>
