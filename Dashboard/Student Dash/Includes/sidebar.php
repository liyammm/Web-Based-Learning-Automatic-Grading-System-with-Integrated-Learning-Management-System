<aside class="sidebar">
    <h2>Courses</h2>
    <nav>
        <ul>
            <?php foreach ($coursesList as $course): ?>
                <li>
                    <a href="index.php?course_id=<?php echo $course['course_id']; ?>">
                        <?php echo htmlspecialchars($course['course_name']); ?>
                    </a>
                    <hr>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
    <div class="user-settings">
        <a href="Functionalities/UserSettings/user_settings.php">User Settings</a>
    </div>
</aside>

<!--change values to pang student ha -->