<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/db.php';
require_once '../../../Classes/Assignment.php';
require_once '../../../Classes/chart.php';

// Verify teacher access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: /FinalProj/login.php");
    exit();
}

$assignment_id = isset($_GET['assignment_id']) ? $_GET['assignment_id'] : null;
if (!$assignment_id) {
    die("Assignment ID not provided");
}

$assignment = new Assignment();
$chart = new Chart();

// Get assignment details and submissions
$assignmentDetails = $assignment->getAssignmentDetails($assignment_id);
$submissions = $assignment->getAllSubmissions($assignment_id);

//for chart
// Count graded submissions
$gradedCount = $chart->countGradedSubmissions($assignment_id);

// Count not graded submissions
$notGradedCount = $chart->countNotGradedSubmissions($assignment_id);

// Count not submitted
$notSubmittedCount = $chart->countNotSubmittedSubmissions($assignment_id);

// Count passed and failed submissions
$passCount = $chart->countPassedSubmissions($assignment_id, $assignmentDetails['total_points']);
$failCount = $chart->countFailedSubmissions($assignment_id, $assignmentDetails['total_points']);


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Submissions</title>
    <link rel="stylesheet" href="submission.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <div class="container">
        <h2>Submissions for: <?php echo htmlspecialchars($assignmentDetails['assignment_name']); ?></h2>

        <?php if ($submissions && $submissions->num_rows > 0): ?>
            <table class="submissions-table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Submission Date</th>
                        <th>Submission Text</th>
                        <th>File</th>
                        <th>Grade</th>
                        <th>Feedback</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($submission = $submissions->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($submission['student_name']); ?></td>
                            <td><?php echo date('F j, Y, g:i A', strtotime($submission['submission_date'])); ?></td>
                            <td><?php echo htmlspecialchars($submission['submission_text']); ?></td>
                            <td>
                                <?php if ($submission['file_name']): ?>
                                    <a href="download_submission.php?submission_id=<?php echo $submission['submission_id']; ?>"
                                        class="btn download-btn">Download <?php echo htmlspecialchars($submission['file_name']); ?></a>
                                <?php else: ?>
                                    No file submitted
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                echo isset($submission['grade'])
                                    ? $submission['grade'] . '/' . $assignmentDetails['total_points']
                                    : 'Not graded';
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($submission['feedback'] ?? ''); ?></td>
                            <td>
                                <form action="grade_submission.php" method="POST" style="display: inline;">
                                    <input type="hidden" name="assignment_id" value="<?php echo htmlspecialchars($assignment_id); ?>">
                                    <input type="hidden" name="submission_id" value="<?php echo htmlspecialchars($submission['submission_id']); ?>">
                                    <input type="number" name="grade" min="0" placeholder="Add Grade" max="<?php echo htmlspecialchars($assignmentDetails['total_points']); ?>"
                                        step="0.01" style="width: 70px;" required
                                        value="<?php echo isset($submission['grade']) ? htmlspecialchars($submission['grade']) : ''; ?>">
                                    <input type="text" name="feedback" placeholder="Add feedback"
                                        value="<?php echo htmlspecialchars($submission['feedback'] ?? ''); ?>">
                                    <hr>
                                    <input type="submit" value="Save Grade" class="btn grade-btn">
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No submissions found for this assignment.</p>
        <?php endif; ?>

        <a href="../../index.php?course_id=<?php echo htmlspecialchars($assignmentDetails['course_id']); ?>" class="btn back-btn">Back to Dashboard</a>
    </div>

    <div class="charts" style="display: flex; justify-content: center; gap: 20px; margin: 20px; flex-wrap: wrap;">
        <div class="chart-container" style="width: 45%; min-width: 300px; height: 400px; background-color: white; border: 2px solid #007bff; border-radius: 10px; padding: 20px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); ">
            <h3 style="text-align: left; color: black;">Chart Analysis For Assignment</h3>
            <canvas id="assignmentRadarChart"></canvas>
        </div>

        <div class="chart-container" style="width: 45%; min-width: 300px; height: 400px; background-color: white; border: 2px solid #007bff; border-radius: 10px; padding: 20px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);">
            <h3 style="text-align: left; color: black;">Pass and Fail Analysis For Assignment</h3>
            <canvas id="passFailDoughnutChart"></canvas>
        </div>
    </div>

    <script>
        const submissionData = {
            labels: ['Graded', 'Not Graded', 'Not Submitted'],
            datasets: [{
                label: 'Assignment Status',
                data: [<?php echo $gradedCount; ?>, <?php echo $notGradedCount; ?>, <?php echo $notSubmittedCount; ?>],
                backgroundColor: 'rgba(0, 123, 255, 0.2)', // Single green tint for the area
                borderColor: '#007bff', // Single green for the border
                borderWidth: 2,
                pointBackgroundColor: [
                    'rgba(24, 230, 86, 0.8)', // Graded - Green
                    'rgba(255, 206, 86, 1)', // Not Graded - Yellow
                    'rgba(255, 99, 132, 1)' // Not Submitted - Red
                ],
                pointBorderColor: [
                    'rgba(24, 230, 86, 0.8)', // Graded - Green
                    'rgba(255, 206, 86, 1)', // Not Graded - Yellow
                    'rgba(255, 99, 132, 1)' // Not Submitted - Red
                ],
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        };

        const config = {
            type: 'radar',
            data: submissionData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top', // 'top'
                        labels: {
                            color: '#000000',
                            font: {
                                size: 14,
                                weight: 'bold'
                            }
                        },
                        padding: 10 //  padding
                    }
                },
                scales: {
                    r: {
                        suggestedMin: 0,
                        suggestedMax: Math.max(<?php echo $gradedCount; ?>, <?php echo $notGradedCount; ?>, <?php echo $notSubmittedCount; ?>) + 2,
                        ticks: {
                            display: false
                        },
                        pointLabels: {
                            color: '#000000',
                            font: {
                                size: 14,
                                weight: 'bold'
                            }
                        },
                        grid: {
                            color: 'rgba(88, 156, 171, 0.8)' // Blue grid lines
                        },
                        angleLines: {
                            color: 'rgba(88, 156, 171, 0.8)' // Blue angle lines
                        }
                    }
                },
                layout: {
                    padding: {
                        left: 50,
                        right: 50,
                        top: 0, // Reduced top padding
                        bottom: 20
                    }
                }
            }
        };

        window.onload = function() {
            const ctx = document.getElementById('assignmentRadarChart').getContext('2d');
            new Chart(ctx, config);
        };
    </script>

    <script>
        const passFailData = {
            labels: ['Passed', 'Failed'],
            datasets: [{
                label: 'Pass/Fail Status',
                data: [<?php echo $passCount; ?>, <?php echo $failCount; ?>],
                backgroundColor: [
                    'rgba(75, 192, 192, 0.8)', // Passed - Blue
                    'rgba(255, 99, 132, 0.8)' // Failed - Red
                ],
                borderColor: [
                    'rgba(75, 192, 192, 1)',
                    'rgba(255, 99, 132, 1)'
                ],
                borderWidth: 1
            }]
        };

        const doughnutConfig = {
            type: 'doughnut',
            data: passFailData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            color: '#000000',
                            font: {
                                size: 14,
                                weight: 'bold'
                            },
                            padding: 20
                        }
                    }
                },
                layout: {
                    padding: {
                        top: 0,
                        bottom: 30,
                        left: 0,
                        right: 0
                    }
                },
                radius: '80%' // Control the overall size of the doughnut
            }
        };

        window.onload = function() {
            // Radar chart
            const ctxRadar = document.getElementById('assignmentRadarChart').getContext('2d');
            new Chart(ctxRadar, config);

            // Doughnut chart
            const ctxDoughnut = document.getElementById('passFailDoughnutChart').getContext('2d');
            new Chart(ctxDoughnut, doughnutConfig);
        };
    </script>



</body>

</html>