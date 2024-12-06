<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/Dashboard/Classes/Dashboard.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/Dashboard/Classes/Material.php';

if (!isset($_SESSION['user_id'])) {
    die("User not logged in.");
}

$teacherDash = new Dashboard();
$material = new Material();

$user_id = $_SESSION['user_id'];
$teacher_name = htmlspecialchars($teacherDash->getUsername($user_id));
$courses = $teacherDash->getCourses($user_id);
$course_id = isset($_GET['course_id']) ? $_GET['course_id'] : null;
$course_details = null;

if ($course_id) {
    $stmt = $teacherDash->getCourseDetails($course_id);
    $course_details = $stmt->fetch_assoc();
}

$learning_materials = [];
if ($course_id) {
    // Fetch learning materials
    $fetch_learningMaterials = $material->getLearningMaterials($course_id);

    // Fetch all the learning materials
    while ($row = $fetch_learningMaterials->fetch_assoc()) {
        $learning_materials[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle file upload
    if (isset($_FILES['file'])) {
        $file_name = $_FILES['file']['name'];
        $description = $_POST['description'];
        $file_data = file_get_contents($_FILES['file']['tmp_name']);

        if ($material->uploadLearningMaterial($course_id, $file_name, $description, $file_data)) {
            $_SESSION['message'] = "File uploaded successfully.";
            header("Location: learning_material.php?course_id=" . htmlspecialchars($course_id));
            exit();
        } else {
            $_SESSION['error'] = "Failed to upload file.";
            header("Location: learning_material.php?course_id=" . htmlspecialchars($course_id));
            exit();
        }
    }

    // Handle material deletion
    if (isset($_POST['material_id'])) {
        $material_id = $_POST['material_id'];
        $course_id = $_POST['course_id'];

        if ($material->deleteLearningMaterial($material_id)) {
            $_SESSION['message'] = "Material deleted successfully.";
            header("Location: learning_material.php?course_id=" . htmlspecialchars($course_id));
            exit();
        } else {
            $_SESSION['error'] = "Failed to delete material.";
        }

        header("Location: learning_material.php?course_id=" . htmlspecialchars($course_id));
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Learning Materials</title>
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

                    <!-- Display success or error messages -->
                    <?php if (isset($_SESSION['message'])): ?>
                        <p class="success"><?php echo $_SESSION['message'];
                                            unset($_SESSION['message']); ?></p>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['error'])): ?>
                        <p class="error"><?php echo $_SESSION['error'];
                                            unset($_SESSION['error']); ?></p>
                    <?php endif; ?>

                    <br>
                    <h2>Upload Learning Material</h2>
                    <div class="upload-section">
                        <form action="learning_material.php?course_id=<?php echo htmlspecialchars($course_id); ?>" method="POST" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="file">File:</label>
                                <input type="file" id="file" name="file" required>
                            </div>

                            <div class="form-group">
                                <label for="description">Description:</label>
                                <textarea id="description" name="description" rows="4" placeholder="Enter a description..." required></textarea>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn">Upload</button>
                            </div>
                        </form>
                    </div><br>
                    <hr><br>

                    <!-- Inside the material list loop -->
                    <h2>Existing Learning Materials</h2> <br>
                    <div class="material-list">
                        <?php if (count($learning_materials) > 0): ?>
                            <ul>
                                <?php foreach ($learning_materials as $material_item): ?>
                                    <li>
                                        <p><strong><?php echo htmlspecialchars($material_item['file_name']); ?></strong></p>
                                        <p><strong>Description:</strong> <?php echo htmlspecialchars($material_item['description']); ?></p>

                                        <!-- Add Download Link -->
                                        <a class="download-link" href="download_material.php?material_id=<?php echo $material_item['learning_materials_id']; ?>">Download File</a>

                                        <!-- Form for deleting material -->
                                        <form action="learning_material.php?course_id=<?php echo htmlspecialchars($course_id); ?>" method="POST" onsubmit="return confirm('Are you sure you want to delete this material?');">
                                            <input type="hidden" name="material_id" value="<?php echo $material_item['learning_materials_id']; ?>">
                                            <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course_id); ?>">
                                            <button type="submit" class="btn btn-delete">Delete</button>
                                        </form>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p>No learning materials available for this course.</p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p>Select a course to view its details and management options.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>

</html>