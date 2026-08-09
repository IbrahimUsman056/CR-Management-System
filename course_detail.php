<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
requireLogin();

$crId = currentCrId();
$courseId = $_GET['id'] ?? null;

// Verify course belongs to this CR
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND cr_id = ?");
$stmt->execute([$courseId, $crId]);
$course = $stmt->fetch();

if (!$course) {
    redirect('/cr_portal/courses.php');
}

$pageTitle = $course['course_name'];

// Update enrollments
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_enrollments'])) {
    $selections = $_POST['status'] ?? []; // [student_id => status]

    $upsert = $pdo->prepare("
        INSERT INTO enrollments (course_id, student_id, status)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE status = VALUES(status)
    ");

    foreach ($selections as $studentId => $status) {
        if (!in_array($status, ['active', 'repeater', 'dropped'])) continue;
        $upsert->execute([$courseId, $studentId, $status]);
    }
    redirect('/cr_portal/course_detail.php?id=' . $courseId);
}

// Full roster + their current enrollment status in this course
$roster = $pdo->prepare("
    SELECT s.id, s.roll_no, s.name, e.status
    FROM students s
    LEFT JOIN enrollments e ON e.student_id = s.id AND e.course_id = ?
    WHERE s.cr_id = ?
    ORDER BY s.roll_no
");
$roster->execute([$courseId, $crId]);
$roster = $roster->fetchAll();

require_once 'includes/header.php';
?>

<div class="page-header">
    <h2><?= clean($course['course_name']) ?></h2>
    <span class="page-count"><?= clean($course['teacher_name']) ?></span>
</div>

<div class="quick-links">
    <a href="attendance.php?course_id=<?= $courseId ?>" class="quick-link-btn">✅ Take Attendance</a>
    <a href="marks.php?course_id=<?= $courseId ?>" class="quick-link-btn">📝 Enter Marks</a>
</div>

<section class="section-block">
    <h3>Manage Enrollment</h3>
    <p class="hint-text">Set each student's status for this course. "Not enrolled" excludes them from attendance and marks here.</p>

    <?php if (empty($roster)): ?>
        <p class="empty-state">Add students to your roster first.</p>
    <?php else: ?>
        <div class="export-bar">
            <a href="export_enrollment_csv.php?course_id=<?= $courseId ?>" class="export-btn">⬇ Excel</a>
            <button type="button" class="export-btn" onclick="exportAsImage('enroll-capture', '<?= preg_replace('/[^A-Za-z0-9_-]/', '_', $course['course_name']) ?>_students')">📷 JPG</button>
        </div>
        <?php $enrolledOnly = array_values(array_filter($roster, fn($s) => $s['status'] === 'active' || $s['status'] === 'repeater')); ?>
        <div id="enroll-capture" class="report-card capture-offscreen">
            <div class="report-header">
                <div class="report-title">Enrollment List</div>
                <div class="report-subtitle"><?= clean($_SESSION['section_name']) ?></div>
                <div class="report-meta">
                    <span class="report-meta-item"><strong>Course:</strong> <?= clean($course['course_name']) ?></span>
                    <span class="report-meta-item"><strong>Teacher:</strong> <?= clean($course['teacher_name']) ?></span>
                    <span class="report-meta-item"><strong>Date:</strong> <?= date('M j, Y') ?></span>
                    <span class="report-meta-item"><strong>Total:</strong> <?= count($enrolledOnly) ?> students</span>
                </div>
            </div>
            <?php if (empty($enrolledOnly)): ?>
                <div class="report-empty-note">No students enrolled yet.</div>
            <?php else: ?>
            <table class="report-table">
                <thead><tr><th class="report-count-col">#</th><th>Roll No</th><th>Name</th></tr></thead>
                <tbody>
                    <?php foreach ($enrolledOnly as $i => $s): ?>
                        <tr>
                            <td class="report-count-col"><?= $i + 1 ?></td>
                            <td><?= clean($s['roll_no']) ?></td>
                            <td><?= clean($s['name']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <form method="POST">
            <input type="hidden" name="update_enrollments" value="1">
            <div class="enroll-list">
                <?php foreach ($roster as $s): ?>
                    <div class="enroll-row">
                        <div>
                            <span class="list-item-title"><?= clean($s['name']) ?></span>
                            <span class="list-item-sub"><?= clean($s['roll_no']) ?></span>
                        </div>
                        <select name="status[<?= $s['id'] ?>]" class="status-select">
                            <option value="dropped" <?= (!$s['status'] || $s['status'] === 'dropped') ? 'selected' : '' ?>>Not Enrolled</option>
                            <option value="active" <?= $s['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="repeater" <?= $s['status'] === 'repeater' ? 'selected' : '' ?>>Repeater</option>
                        </select>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="btn-primary btn-block">Save Enrollment</button>
        </form>
    <?php endif; ?>
</section>

<?php
require_once 'includes/navbar.php';
require_once 'includes/footer.php';
?>