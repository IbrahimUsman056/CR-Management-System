<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
requireLogin();

$crId = currentCrId();
$pageTitle = 'Marks';
$error = '';
$saved = false;

// CR's courses
$courses = $pdo->prepare("SELECT id, course_name FROM courses WHERE cr_id = ? ORDER BY course_name");
$courses->execute([$crId]);
$courses = $courses->fetchAll();

$courseId = $_GET['course_id'] ?? ($_POST['course_id'] ?? null);
$quizId = $_GET['quiz_id'] ?? ($_POST['quiz_id'] ?? null);

// Verify course belongs to CR
$course = null;
if ($courseId) {
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND cr_id = ?");
    $stmt->execute([$courseId, $crId]);
    $course = $stmt->fetch();
    if (!$course) { $courseId = null; }
}

// Add new quiz
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_quiz']) && $course) {
    $title = trim($_POST['title'] ?? '');
    $totalMarks = trim($_POST['total_marks'] ?? '');
    $quizDate = $_POST['quiz_date'] ?? date('Y-m-d');

    if ($title === '' || !is_numeric($totalMarks) || $totalMarks <= 0) {
        $error = 'Enter a valid quiz title and total marks.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO quizzes (course_id, title, total_marks, quiz_date) VALUES (?, ?, ?, ?)");
        $stmt->execute([$courseId, $title, $totalMarks, $quizDate]);
        redirect('/cr_portal/marks.php?course_id=' . $courseId . '&quiz_id=' . $pdo->lastInsertId());
    }
}

// Verify quiz belongs to this course
$quiz = null;
if ($quizId && $course) {
    $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ? AND course_id = ?");
    $stmt->execute([$quizId, $courseId]);
    $quiz = $stmt->fetch();
    if (!$quiz) { $quizId = null; }
}

// Save marks
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_marks']) && $quiz) {
    $marksInput = $_POST['marks'] ?? []; // [student_id => marks]

    $upsert = $pdo->prepare("
        INSERT INTO quiz_marks (quiz_id, student_id, marks_obtained)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE marks_obtained = VALUES(marks_obtained)
    ");

    foreach ($marksInput as $studentId => $marks) {
        if ($marks === '') continue; // skip blank = not entered yet
        if (!is_numeric($marks) || $marks < 0 || $marks > $quiz['total_marks']) continue;
        $upsert->execute([$quizId, $studentId, $marks]);
    }
    $saved = true;
}

// Quizzes list for this course
$quizzes = [];
if ($course) {
    $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE course_id = ? ORDER BY quiz_date DESC");
    $stmt->execute([$courseId]);
    $quizzes = $stmt->fetchAll();
}

// Enrolled students + their marks for this quiz
$enrolled = [];
$classAverage = null;
if ($quiz) {
    $stmt = $pdo->prepare("
        SELECT s.id, s.roll_no, s.name, qm.marks_obtained
        FROM enrollments e
        JOIN students s ON s.id = e.student_id
        LEFT JOIN quiz_marks qm ON qm.student_id = s.id AND qm.quiz_id = ?
        WHERE e.course_id = ? AND e.status IN ('active', 'repeater')
        ORDER BY s.roll_no
    ");
    $stmt->execute([$quizId, $courseId]);
    $enrolled = $stmt->fetchAll();

    $entered = array_filter($enrolled, fn($s) => $s['marks_obtained'] !== null);
    if (count($entered) > 0) {
        $classAverage = round(array_sum(array_column($entered, 'marks_obtained')) / count($entered), 1);
    }
}

require_once 'includes/header.php';
?>

<div class="page-header">
    <h2>Marks</h2>
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
    </form>
</div>

<?php if ($course && !$quiz): ?>
    <section class="section-block">
        <h3>Add New Quiz/Assessment</h3>
        <div class="form-card">
            <form method="POST" class="stacked-form">
                <input type="hidden" name="add_quiz" value="1">
                <input type="hidden" name="course_id" value="<?= $courseId ?>">
                <?php if ($error): ?><div class="alert alert-error"><?= clean($error) ?></div><?php endif; ?>
                <input type="text" name="title" placeholder="Quiz Title (e.g. Quiz 1)" required>
                <input type="number" name="total_marks" placeholder="Total Marks" min="1" required>
                <input type="date" name="quiz_date" value="<?= date('Y-m-d') ?>">
                <button type="submit" class="btn-primary btn-sm">Create Quiz</button>
            </form>
        </div>
    </section>

    <section class="section-block">
        <h3>Existing Quizzes</h3>
        <?php if (empty($quizzes)): ?>
            <p class="empty-state">No quizzes added for this course yet.</p>
        <?php else: ?>
            <div class="list-block">
                <?php foreach ($quizzes as $q): ?>
                    <a href="marks.php?course_id=<?= $courseId ?>&quiz_id=<?= $q['id'] ?>" class="list-item">
                        <div class="list-item-link">
                            <span class="list-item-title"><?= clean($q['title']) ?></span>
                            <span class="list-item-sub">Out of <?= $q['total_marks'] ?> · <?= date('M j', strtotime($q['quiz_date'])) ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
<?php elseif ($quiz): ?>
    <div class="quiz-header-card">
        <div>
            <span class="list-item-title"><?= clean($quiz['title']) ?></span>
            <span class="list-item-sub">Out of <?= $quiz['total_marks'] ?> marks</span>
        </div>
        <a href="marks.php?course_id=<?= $courseId ?>" class="btn-mini-link">Change Quiz</a>
    </div>

    <?php if ($saved): ?>
        <div class="alert alert-success">Marks saved.</div>
    <?php endif; ?>

    <?php if ($classAverage !== null): ?>
        <div class="stats-grid" style="grid-template-columns: 1fr;">
            <div class="stat-card">
                <span class="stat-number"><?= $classAverage ?> / <?= $quiz['total_marks'] ?></span>
                <span class="stat-label">Class Average</span>
            </div>
        </div>
    <?php endif; ?>

    <?php if (empty($enrolled)): ?>
        <p class="empty-state">No students enrolled in this course. <a href="course_detail.php?id=<?= $courseId ?>">Manage enrollment</a>.</p>
    <?php else: ?>
        <div class="export-bar">
            <a href="export_marks_csv.php?quiz_id=<?= $quizId ?>" class="export-btn">⬇ Excel</a>
            <button type="button" class="export-btn" onclick="exportAsImage('marks-capture', '<?= clean($quiz['title']) ?>_marks')">📷 JPG</button>
        </div>
        <div id="marks-capture" class="report-card capture-offscreen">
            <div class="report-header">
                <div class="report-title"><?= clean($quiz['title']) ?></div>
                <div class="report-subtitle"><?= clean($_SESSION['section_name']) ?></div>
                <div class="report-meta">
                    <span class="report-meta-item"><strong>Course:</strong> <?= clean($course['course_name']) ?></span>
                    <span class="report-meta-item"><strong>Teacher:</strong> <?= clean($course['teacher_name']) ?></span>
                    <span class="report-meta-item"><strong>Date:</strong> <?= date('M j, Y', strtotime($quiz['quiz_date'])) ?></span>
                    <span class="report-meta-item"><strong>Total Marks:</strong> <?= $quiz['total_marks'] ?></span>
                    <?php if ($classAverage !== null): ?>
                        <span class="report-meta-item"><strong>Average:</strong> <?= $classAverage ?>/<?= $quiz['total_marks'] ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (empty($enrolled)): ?>
                <div class="report-empty-note">No students enrolled yet.</div>
            <?php else: ?>
            <table class="report-table">
                <thead><tr><th class="report-count-col">#</th><th>Roll No</th><th>Name</th><th>Marks</th></tr></thead>
                <tbody>
                    <?php foreach ($enrolled as $i => $s): ?>
                        <tr>
                            <td class="report-count-col"><?= $i + 1 ?></td>
                            <td><?= clean($s['roll_no']) ?></td>
                            <td><?= clean($s['name']) ?></td>
                            <td><?= $s['marks_obtained'] !== null ? clean($s['marks_obtained']) . '/' . $quiz['total_marks'] : '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <form method="POST">
            <input type="hidden" name="save_marks" value="1">
            <input type="hidden" name="course_id" value="<?= $courseId ?>">
            <input type="hidden" name="quiz_id" value="<?= $quizId ?>">

            <div class="marks-list">
                <?php foreach ($enrolled as $s): ?>
                    <div class="marks-row">
                        <div class="attendance-student">
                            <span class="list-item-title"><?= clean($s['name']) ?></span>
                            <span class="list-item-sub"><?= clean($s['roll_no']) ?></span>
                        </div>
                        <input type="number" step="0.5" min="0" max="<?= $quiz['total_marks'] ?>"
                               name="marks[<?= $s['id'] ?>]"
                               value="<?= $s['marks_obtained'] !== null ? $s['marks_obtained'] : '' ?>"
                               class="marks-input" placeholder="—">
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="submit" class="btn-primary btn-block">Save Marks</button>
        </form>
    <?php endif; ?>
<?php else: ?>
    <p class="empty-state">Pick a course above to manage quizzes and marks.</p>
<?php endif; ?>

<?php
require_once 'includes/navbar.php';
require_once 'includes/footer.php';
?>