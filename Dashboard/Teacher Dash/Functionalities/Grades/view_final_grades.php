<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/Dashboard/Classes/Grade.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/Dashboard/Classes/Course.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/FinalProj/Dashboard/Classes/chart.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    die("Unauthorized access");
}

$gradeCalculator = new GradeCalculator();
$course = new CourseSettings();
$chart = new Chart();

$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

// Get course details and weights
$courseDetails = $course->getCourseDetails($course_id);

// Calculate grades using the GradeCalculator class
$grades = $gradeCalculator->calculateGrades($course_id);

// Update final grades in the database
$courseWeights = [
    'assignment_weight' => $courseDetails['assignment_weight'],
    'quiz_weight' => $courseDetails['quiz_weight'],
    'exam_weight' => $courseDetails['exam_weight']
];

$grades = $gradeCalculator->updateFinalGrades($course_id, $grades, $courseWeights);

// Get statistics/data using the new methods
$assignmentStats = $chart->getCourseAssignmentStats($course_id);
$quizStats = $chart->getCourseQuizStats($course_id);
$examStats = $chart->getCourseExamStats($course_id);
$finalStats = $chart->getCourseFinalGradeStats($course_id);

// Convert statistics to JSON for JavaScript use
$chartData = [
    'assignment' => $assignmentStats,
    'quiz' => $quizStats,
    'exam' => $examStats,
    'final' => $finalStats
];

$totalStudents = count($grades); // number of enrolled students(iba lang ng way maximixing sa nakuha ang data)
$suggestedMax = max(40, ceil($totalStudents / 10) * 10); // Rounding up to the nearest 10
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Final Grades</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <div class="grades-container">
        <h2>Final Grades - <?php echo htmlspecialchars($courseDetails['course_name']); ?></h2>

        <div class="weights-info">
            <p>Assignment Weight: <?php echo $courseDetails['assignment_weight']; ?>%</p>
            <p>Quiz Weight: <?php echo $courseDetails['quiz_weight']; ?>%</p>
            <p>Exam Weight: <?php echo $courseDetails['exam_weight']; ?>%</p>
        </div>

        <table class="grades-table">
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Assignment Average</th>
                    <th>Quiz Average</th>
                    <th>Exam Average</th>
                    <th>Final Grade</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($grades as $grade): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($grade['first_name'] . ' ' . $grade['last_name']); ?></td>
                        <td><?php echo round($grade['assignment_avg'], 2); ?>%</td>
                        <td><?php echo round($grade['quiz_avg'], 2); ?>%</td>
                        <td><?php echo round($grade['exam_avg'], 2); ?>%</td>
                        <td class="final-grade"><?php echo $grade['final_grade']; ?>%</td>
                        <td class="<?php echo strtolower($grade['status']); ?>"><?php echo $grade['status']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <br><hr>

        <div class="charts-container">
            <div class="chart-wrapper">
                <canvas id="componentsChart"></canvas>
            </div>
            <div class="chart-wrapper">
                <canvas id="finalGradeChart" style="max-width: 400px; max-height: 400px;"></canvas>
            </div>
        </div>

        <a href="../../index.php?course_id=<?php echo htmlspecialchars($courseDetails['course_id']); ?>" class="btn back-btn">Back to Dashboard</a>
    </div>

    <style>
        .charts-container {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            margin: 20px 0;
        }

        .chart-wrapper {
            width: 45%;
            min-width: 300px;
            margin: 10px;
            display: flex;
            justify-content: center;
        }
    </style>

    <script>
        // Components Chart (Assignment, Quiz, Exam)
        new Chart(document.getElementById('componentsChart'), {
            type: 'bar',
            data: {
                labels: ['Assignment', 'Quiz', 'Exam'],
                datasets: [{
                    label: 'Passed',
                    data: [
                        <?php echo $chartData['assignment']['passed']; ?>,
                        <?php echo $chartData['quiz']['passed']; ?>,
                        <?php echo $chartData['exam']['passed']; ?>
                    ],
                    backgroundColor: 'rgba(75, 192, 192, 0.8)'
                }, {
                    label: 'Failed',
                    data: [
                        <?php echo $chartData['assignment']['failed']; ?>,
                        <?php echo $chartData['quiz']['failed']; ?>,
                        <?php echo $chartData['exam']['failed']; ?>
                    ],
                    backgroundColor: 'rgba(255, 99, 132, 0.8)'
                }, {
                    label: 'Not Comply',
                    data: [
                        <?php echo $chartData['assignment']['not_comply']; ?>,
                        <?php echo $chartData['quiz']['not_comply']; ?>,
                        <?php echo $chartData['exam']['not_comply']; ?>
                    ],
                    backgroundColor: 'rgba(201, 203, 207, 0.8)'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: <?php echo $suggestedMax; ?>,  // Dynamic sya max based is 40 but will inceremnt based on enrolled students
                        ticks: {
                            stepSize: 10
                        }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Component Performance Distribution'
                    }
                }
            }
        });

        // Final Grade Chart
        new Chart(document.getElementById('finalGradeChart'), {
            type: 'pie',
            data: {
                labels: ['Passed', 'Failed'],
                datasets: [{
                    data: [
                        <?php echo $chartData['final']['passed']; ?>,
                        <?php echo $chartData['final']['failed']; ?>
                    ],
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(255, 99, 132, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: 1,  // for it to be perfectly square
                plugins: {
                    title: {
                        display: true,
                        text: 'Overall Grade Distribution'
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>

</html>