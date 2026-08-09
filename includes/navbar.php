</main>

    <nav class="bottom-nav">
        <a href="/cr_portal/dashboard.php" class="nav-item <?= isActive('dashboard.php') ?>">
            <span class="nav-icon">🏠</span>
            <span class="nav-label">Home</span>
        </a>
        <a href="/cr_portal/courses.php" class="nav-item <?= isActive('courses.php') ?>">
            <span class="nav-icon">📚</span>
            <span class="nav-label">Courses</span>
        </a>
        <a href="/cr_portal/attendance.php" class="nav-item <?= isActive('attendance.php') ?>">
            <span class="nav-icon">✅</span>
            <span class="nav-label">Attendance</span>
        </a>
        <a href="/cr_portal/marks.php" class="nav-item <?= isActive('marks.php') ?>">
            <span class="nav-icon">📝</span>
            <span class="nav-label">Marks</span>
        </a>
        <a href="/cr_portal/tasks.php" class="nav-item <?= isActive('tasks.php') ?>">
            <span class="nav-icon">📌</span>
            <span class="nav-label">Tasks</span>
        </a>
    </nav>
</div>