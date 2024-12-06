<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/db.php';
require_once '../../../Classes/Assessment.php';
require_once '../../../Classes/chart.php';

// Verify teacher access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: /FinalProj/login.php");
    exit();
}

$assessment_id = isset($_GET['assessment_id']) ? $_GET['assessment_id'] : null;
if (!$assessment_id) {
    die("Assessment ID not provided");
}

$assessment = new Assessment();
$chart = new Chart();

// Get assessment details and attempts
$assessmentDetails = $assessment->getAssessmentDetails($assessment_id);
$attempts = $assessment->getAllAttempts($assessment_id);

// For chart data
$gradedCount = $chart->countGradedAttempts($assessment_id);
$notGradedCount = $chart->countNotGradedAttempts($assessment_id);
$notAttemptedCount = $chart->countNotAttemptedAssessments($assessment_id);
$passCount = $chart->countPassedAttempts($assessment_id, $assessmentDetails['total_points']);
$failCount = $chart->countFailedAttempts($assessment_id, $assessmentDetails['total_points']);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Submissions</title>
    <link rel="stylesheet" href="view_responses.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <div class="container">
        <h2>Attempts for: <?php echo htmlspecialchars($assessmentDetails['assessment_name']); ?></h2>

        <?php if ($attempts && $attempts->num_rows > 0): ?>
            <table class="submissions-table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Score</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($attempt = $attempts->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($attempt['student_name']); ?></td>
                            <td><?php echo date('F j, Y, g:i A', strtotime($attempt['start_time'])); ?></td>
                            <td><?php
                                if ($attempt['status'] === 'completed' && $attempt['end_time']) {
                                    echo date('F j, Y, g:i A', strtotime($attempt['end_time']));
                                } else {
                                    echo 'In Progress';
                                }
                                ?></td>
                            <td>
                                <?php
                                echo isset($attempt['score'])
                                    ? $attempt['score'] . '/' . $assessmentDetails['total_points']
                                    : 'Not graded';
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($attempt['status']); ?></td>
                            <td>
                                <?php
                                if (isset($attempt['score']) && $attempt['status'] === 'completed') {
                                    $isPassed = $attempt['score'] >= ($assessmentDetails['total_points'] * 0.75); // 75% passing threshold
                                    echo '<span class="status-badge ' . ($isPassed ? 'passed' : 'failed') . '">';
                                    echo $isPassed ? 'Passed' : 'Failed';
                                    echo '</span>';
                                } else {
                                    echo '<span class="status-badge pending">Pending</span>';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No attempts found for this assessment.</p>
        <?php endif; ?>

        <a href="../../index.php?course_id=<?php echo htmlspecialchars($assessmentDetails['course_id']); ?>"
            class="btn back-btn">Back to Dashboard</a>
    </div>

    <div class="charts" style="display: flex; justify-content: center; gap: 20px; margin: 20px; flex-wrap: wrap;">
        <div class="chart-container" style="width: 45%; min-width: 300px; height: 400px; background-color: white; border: 2px solid #007bff; border-radius: 10px; padding: 20px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);">
            <h3 style="text-align: left; color: black;">Chart Analysis For Assessment</h3>
            <canvas id="assessmentRadarChart"></canvas>
        </div>

        <div class="chart-container" style="width: 45%; min-width: 300px; height: 400px; background-color: white; border: 2px solid #007bff; border-radius: 10px; padding: 20px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);">
            <h3 style="text-align: left; color: black;">Pass and Fail Analysis For Assessment</h3>
            <canvas id="passFailDoughnutChart"></canvas>
        </div>
    </div>

    <script>
        const submissionData = {
            labels: ['Graded', 'Not Graded', 'Not Attempted'],
            datasets: [{
                label: 'Assessment Status',
                data: [<?php echo $gradedCount; ?>, <?php echo $notGradedCount; ?>, <?php echo $notAttemptedCount; ?>],
                backgroundColor: 'rgba(0, 123, 255, 0.2)',
                borderColor: '#007bff',
                borderWidth: 2,
                pointBackgroundColor: [
                    'rgba(24, 230, 86, 0.8)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(255, 99, 132, 1)'
                ],
                pointBorderColor: [
                    'rgba(24, 230, 86, 0.8)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(255, 99, 132, 1)'
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
                        suggestedMax: Math.max(<?php echo $gradedCount; ?>, <?php echo $notGradedCount; ?>, <?php echo $notAttemptedCount; ?>) + 2,
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
            const ctxRadar = document.getElementById('assessmentRadarChart').getContext('2d');
            new Chart(ctxRadar, config);

            const ctxDoughnut = document.getElementById('passFailDoughnutChart').getContext('2d');
            new Chart(ctxDoughnut, doughnutConfig);
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
    </script>



</body>

</html>
?>