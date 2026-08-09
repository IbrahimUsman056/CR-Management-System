<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
requireLogin();

$crId = currentCrId();
$pageTitle = 'Courses';
$error = '';

// Add course
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_course'])) {
    $courseName = trim($_POST['course_name'] ?? '');
    $teacherName = trim($_POST['teacher_name'] ?? '');
    $teacherContact = trim($_POST['teacher_contact'] ?? '');

    if ($courseName === '' || $teacherName === '') {
        $error = 'Course name and teacher name are required.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO courses (cr_id, course_name, teacher_name, teacher_contact) VALUES (?, ?, ?, ?)");
        $stmt->execute([$crId, $courseName, $teacherName, $teacherContact]);
        redirect('/cr_portal/courses.php');
    }
}

// Delete course
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ? AND cr_id = ?");
    $stmt->execute([$_GET['delete'], $crId]);
    redirect('/cr_portal/courses.php');
}

// Fetch courses with enrollment counts
$courses = $pdo->prepare("
    SELECT c.*, 
           COUNT(CASE WHEN e.status = 'active' THEN 1 END) AS active_count,
           COUNT(CASE WHEN e.status = 'repeater' THEN 1 END) AS repeater_count
    FROM courses c
    LEFT JOIN enrollments e ON e.course_id = c.id
    WHERE c.cr_id = ?
    GROUP BY c.id
    ORDER BY c.course_name
");
$courses->execute([$crId]);
$courses = $courses->fetchAll();

require_once 'includes/header.php';
?>

<div class="page-header">
    <h2>Courses</h2>
    <span class="page-count"><?= count($courses) ?> courses</span>
</div>

<?php if ($error): ?>
    <div class="alert alert-error"><?= clean($error) ?></div>
<?php endif; ?>

<div class="form-card">
    <form method="POST" class="stacked-form">
        <input type="hidden" name="add_course" value="1">
        <input type="text" name="course_name" placeholder="Course Name (e.g. Data Structures)" required>
        <input type="text" name="teacher_name" placeholder="Teacher Name" required>
        <input type="text" name="teacher_contact" placeholder="Teacher Contact (optional)">
        <button type="submit" class="btn-primary btn-sm">Add Course</button>
    </form>
</div>

<div class="list-block">
    <?php if (empty($courses)): ?>
        <p class="empty-state">No courses added yet.</p>
    <?php else: ?>
        <?php foreach ($courses as $c): ?>
            <div class="list-item">
                <a href="course_detail.php?id=<?= $c['id'] ?>" class="list-item-link">
                    <span class="list-item-title"><?= clean($c['course_name']) ?></span>
                    <span class="list-item-sub"><?= clean($c['teacher_name']) ?> · <?= $c['active_count'] + $c['repeater_count'] ?> enrolled</span>
                </a>
                <a href="?delete=<?= $c['id'] ?>" class="btn-delete" data-confirm="Delete this course? This removes all its enrollment, attendance, and quiz data.">✕</a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
require_once 'includes/navbar.php';
require_once 'includes/footer.php';
?>