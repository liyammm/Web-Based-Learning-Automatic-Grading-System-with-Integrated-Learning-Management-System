<aside class="sidebar">
    <h2>Courses</h2>
    <div class="create-course">
        <a href="/FinalProj/Dashboard/Teacher Dash/Functionalities/Course/create_course.php">Create Course</a>
    </div>
    <nav>
        <ul>
            <?php while ($row = $courses->fetch_assoc()) { ?>
                <li>
                    <a href="<?php echo '/FinalProj/Dashboard/Teacher Dash/index.php?course_id=' . htmlspecialchars($row['course_id']); ?>">
                        <?php echo htmlspecialchars($row['course_name']); ?>
                    </a>
                    <hr>
                </li>
            <?php } ?>
        </ul>
    </nav>
    <div class="user-settings">
        <a href="/FinalProj/Dashboard/Teacher Dash/Functionalities/UserSettings/user_settings.php">User Settings</a>
    </div>
</aside>
