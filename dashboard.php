<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
requireLogin();

$crId = currentCrId();
$pageTitle = 'Dashboard';

$totalStudents = $pdo->prepare("SELECT COUNT(*) FROM students WHERE cr_id = ?");
$totalStudents->execute([$crId]);
$totalStudents = $totalStudents->fetchColumn();

$totalCourses = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE cr_id = ?");
$totalCourses->execute([$crId]);
$totalCourses = $totalCourses->fetchColumn();

$courseStats = $pdo->prepare("
    SELECT c.id, c.course_name, c.teacher_name,
           COUNT(CASE WHEN e.status = 'active' THEN 1 END) AS active_count,
           COUNT(CASE WHEN e.status = 'repeater' THEN 1 END) AS repeater_count
    FROM courses c
    LEFT JOIN enrollments e ON e.course_id = c.id
    WHERE c.cr_id = ?
    GROUP BY c.id
    ORDER BY c.course_name
");
$courseStats->execute([$crId]);
$courseStats = $courseStats->fetchAll();

$upcomingTasks = $pdo->prepare("
    SELECT t.*, c.course_name
    FROM tasks t
    LEFT JOIN courses c ON c.id = t.course_id
    WHERE t.cr_id = ? AND t.status = 'pending'
      AND (t.due_date IS NULL OR t.due_date <= DATE_ADD(CURDATE(), INTERVAL 5 DAY))
    ORDER BY t.due_date ASC
    LIMIT 5
");
$upcomingTasks->execute([$crId]);
$upcomingTasks = $upcomingTasks->fetchAll();

require_once 'includes/header.php';
?>

<div class="greeting">
    <h2>Hi, <?= clean($_SESSION['cr_name']) ?> 👋</h2>
    <p>Here's what's happening in <?= clean($_SESSION['section_name']) ?></p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-number"><?= $totalStudents ?></span>
        <span class="stat-label">Total Students</span>
    </div>
    <div class="stat-card">
        <span class="stat-number"><?= $totalCourses ?></span>
        <span class="stat-label">Courses</span>
    </div>
</div>

<section class="section-block">
    <h3>Courses & Enrollments</h3>
    <?php if (empty($courseStats)): ?>
        <p class="empty-state">No courses added yet. <a href="courses.php">Add your first course</a>.</p>
    <?php else: ?>
        <div class="course-list">
            <?php foreach ($courseStats as $c): ?>
                <?php $totalEnrolled = $c['active_count'] + $c['repeater_count']; ?>
                <a href="course_detail.php?id=<?= $c['id'] ?>" class="course-card">
                    <div class="course-card-main">
                        <span class="course-name"><?= clean($c['course_name']) ?></span>
                        <span class="course-teacher"><?= clean($c['teacher_name']) ?></span>
                    </div>
                    <div class="course-card-count">
                        <span class="count-number"><?= $totalEnrolled ?></span>
                        <span class="count-label">enrolled</span>
                        <?php if ($c['repeater_count'] > 0): ?>
                            <span class="count-repeater"><?= $c['repeater_count'] ?> repeating</span>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="section-block">
    <h3>📌 Tasks Due Soon</h3>
    <?php if (empty($upcomingTasks)): ?>
        <p class="empty-state">Nothing pending. <a href="tasks.php">Add a task</a>.</p>
    <?php else: ?>
        <div class="task-list">
            <?php foreach ($upcomingTasks as $t): ?>
                <div class="task-item">
                    <div>
                        <span class="task-title"><?= clean($t['title']) ?></span>
                        <?php if ($t['course_name']): ?>
                            <span class="task-course"><?= clean($t['course_name']) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($t['due_date']): ?>
                        <span class="task-due"><?= date('M j', strtotime($t['due_date'])) ?></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php
require_once 'includes/navbar.php';
require_once 'includes/footer.php';
?>