<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
requireLogin();

$crId = currentCrId();
$pageTitle = 'Attendance';

$courses = $pdo->prepare("SELECT id, course_name FROM courses WHERE cr_id = ? ORDER BY course_name");
$courses->execute([$crId]);
$courses = $courses->fetchAll();

$courseId = $_GET['course_id'] ?? ($_POST['course_id'] ?? null);
$date = $_GET['date'] ?? ($_POST['date'] ?? date('Y-m-d'));
$saved = false;

$course = null;
if ($courseId) {
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND cr_id = ?");
    $stmt->execute([$courseId, $crId]);
    $course = $stmt->fetch();
    if (!$course) { $courseId = null; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance']) && $course) {
    $statuses = $_POST['status'] ?? [];

    $upsert = $pdo->prepare("
        INSERT INTO attendance_records (course_id, student_id, attendance_date, status)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE status = VALUES(status)
    ");

    foreach ($statuses as $studentId => $status) {
        if (!in_array($status, ['present', 'absent'])) continue;
        $upsert->execute([$courseId, $studentId, $date, $status]);
    }
    $saved = true;
}

$enrolled = [];
if ($course) {
    $stmt = $pdo->prepare("
        SELECT s.id, s.roll_no, s.name, e.status AS enroll_status, a.status AS attendance_status
        FROM enrollments e
        JOIN students s ON s.id = e.student_id
        LEFT JOIN attendance_records a ON a.student_id = s.id AND a.course_id = ? AND a.attendance_date = ?
        WHERE e.course_id = ? AND e.status IN ('active', 'repeater')
        ORDER BY s.roll_no
    ");
    $stmt->execute([$courseId, $date, $courseId]);
    $enrolled = $stmt->fetchAll();
}

require_once 'includes/header.php';
?>

<div class="page-header">
    <h2>Attendance</h2>
</div>

<div class="form-card">
    <form method="GET" class="stacked-form">
        <label class="field-label">Course</label>
        <select name="course_id" class="status-select" onchange="this.form.submit()">
            <option value="">Select a course</option>
            <?php foreach ($courses as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $courseId == $c['id'] ? 'selected' : '' ?>>
                    <?= clean($c['course_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label class="field-label">Date</label>
        <input type="date" name="date" value="<?= clean($date) ?>" onchange="this.form.submit()">
    </form>
</div>

<?php if ($saved): ?>
    <div class="alert alert-success">Attendance saved for <?= date('M j, Y', strtotime($date)) ?>.</div>
<?php endif; ?>

<?php if (!$course): ?>
    <p class="empty-state">Pick a course above to mark attendance.</p>
<?php elseif (empty($enrolled)): ?>
    <p class="empty-state">No students enrolled in this course yet. <a href="course_detail.php?id=<?= $courseId ?>">Manage enrollment</a>.</p>
<?php else: ?>

    <div class="export-bar">
        <a href="export_attendance_csv.php?course_id=<?= $courseId ?>&date=<?= clean($date) ?>" class="export-btn">⬇ Excel</a>
        <button type="button" class="export-btn" onclick="exportAsImage('attendance-capture', '<?= clean($course['course_name']) ?>_<?= clean($date) ?>')">📷 JPG</button>
    </div>

    <?php
        $present = array_values(array_filter($enrolled, fn($s) => ($s['attendance_status'] ?? null) === 'present'));
        $absent  = array_values(array_filter($enrolled, fn($s) => ($s['attendance_status'] ?? null) === 'absent'));
        $unmarked = array_values(array_filter($enrolled, fn($s) => empty($s['attendance_status'])));
        $rowNum = 0;
    ?>
    <!-- Offscreen: used only for image/Excel export, not shown on the page -->
    <div id="attendance-capture" class="report-card capture-offscreen">
        <div class="report-header">
            <div class="report-title">Attendance Report</div>
            <div class="report-subtitle"><?= clean($_SESSION['section_name']) ?></div>
            <div class="report-meta">
                <span class="report-meta-item"><strong>Course:</strong> <?= clean($course['course_name']) ?></span>
                <span class="report-meta-item"><strong>Teacher:</strong> <?= clean($course['teacher_name']) ?></span>
                <span class="report-meta-item"><strong>Date:</strong> <?= date('M j, Y', strtotime($date)) ?></span>
                <span class="report-meta-item"><strong>Total:</strong> <?= count($enrolled) ?> students</span>
            </div>
        </div>
        <table class="report-table">
            <thead><tr><th class="report-count-col">#</th><th>Roll No</th><th>Name</th></tr></thead>
            <tbody>
                <?php if (!empty($present)): ?>
                    <tr class="report-section-header present"><td colspan="3">Present (<?= count($present) ?>)</td></tr>
                    <?php foreach ($present as $s): $rowNum++; ?>
                        <tr><td class="report-count-col"><?= $rowNum ?></td><td><?= clean($s['roll_no']) ?></td><td><?= clean($s['name']) ?></td></tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php if (!empty($absent)): ?>
                    <tr class="report-section-header absent"><td colspan="3">Absent (<?= count($absent) ?>)</td></tr>
                    <?php foreach ($absent as $s): $rowNum++; ?>
                        <tr><td class="report-count-col"><?= $rowNum ?></td><td><?= clean($s['roll_no']) ?></td><td><?= clean($s['name']) ?></td></tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php if (!empty($unmarked)): ?>
                    <tr class="report-section-header unmarked"><td colspan="3">Not Marked (<?= count($unmarked) ?>)</td></tr>
                    <?php foreach ($unmarked as $s): $rowNum++; ?>
                        <tr><td class="report-count-col"><?= $rowNum ?></td><td><?= clean($s['roll_no']) ?></td><td><?= clean($s['name']) ?></td></tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <form method="POST">
        <input type="hidden" name="save_attendance" value="1">
        <input type="hidden" name="course_id" value="<?= $courseId ?>">
        <input type="hidden" name="date" value="<?= clean($date) ?>">

        <div class="attendance-toolbar">
            <button type="button" class="btn-mini" onclick="markAll('present')">Mark All Present</button>
            <button type="button" class="btn-mini" onclick="markAll('absent')">Mark All Absent</button>
        </div>

        <div class="attendance-list">
            <?php foreach ($enrolled as $s): ?>
                <?php $current = $s['attendance_status'] ?? 'present'; ?>
                <div class="attendance-row">
                    <div class="attendance-student">
                        <span class="list-item-title"><?= clean($s['name']) ?></span>
                        <span class="list-item-sub">
                            <?= clean($s['roll_no']) ?>
                            <?php if ($s['enroll_status'] === 'repeater'): ?>
                                <span class="tag-repeater">repeater</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="status-toggle" data-student="<?= $s['id'] ?>">
                        <label class="toggle-btn present <?= $current === 'present' ? 'active' : '' ?>">
                            <input type="radio" name="status[<?= $s['id'] ?>]" value="present" <?= $current === 'present' ? 'checked' : '' ?>>P
                        </label>
                        <label class="toggle-btn absent <?= $current === 'absent' ? 'active' : '' ?>">
                            <input type="radio" name="status[<?= $s['id'] ?>]" value="absent" <?= $current === 'absent' ? 'checked' : '' ?>>A
                        </label>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <button type="submit" class="btn-primary btn-block">Save Attendance (<?= count($enrolled) ?> students)</button>
    </form>
<?php endif; ?>

<?php
require_once 'includes/navbar.php';
require_once 'includes/footer.php';
?>