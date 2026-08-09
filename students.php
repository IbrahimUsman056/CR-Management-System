<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
requireLogin();

$crId = currentCrId();
$pageTitle = 'Students';
$error = '';

// Add student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
    $rollNo = trim($_POST['roll_no'] ?? '');
    $name = trim($_POST['name'] ?? '');

    if ($rollNo === '' || $name === '') {
        $error = 'Roll number and name are required.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO students (cr_id, roll_no, name) VALUES (?, ?, ?)");
            $stmt->execute([$crId, $rollNo, $name]);
            redirect('/cr_portal/students.php');
        } catch (PDOException $e) {
            $error = ($e->getCode() == 23000) ? 'That roll number already exists.' : 'Something went wrong.';
        }
    }
}

// Delete student
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM students WHERE id = ? AND cr_id = ?");
    $stmt->execute([$_GET['delete'], $crId]);
    redirect('/cr_portal/students.php');
}

// Fetch roster
$students = $pdo->prepare("SELECT * FROM students WHERE cr_id = ? ORDER BY roll_no");
$students->execute([$crId]);
$students = $students->fetchAll();

require_once 'includes/header.php';
?>

<div class="page-header">
    <h2>Class Roster</h2>
    <span class="page-count"><?= count($students) ?> students</span>
</div>

<?php if ($error): ?>
    <div class="alert alert-error"><?= clean($error) ?></div>
<?php endif; ?>

<div class="form-card">
    <form method="POST" class="inline-form">
        <input type="hidden" name="add_student" value="1">
        <input type="text" name="roll_no" placeholder="Roll No" required>
        <input type="text" name="name" placeholder="Full Name" required>
        <button type="submit" class="btn-primary btn-sm">Add</button>
    </form>
</div>

<div class="list-block">
    <?php if (empty($students)): ?>
        <p class="empty-state">No students added yet.</p>
    <?php else: ?>
        <?php foreach ($students as $s): ?>
            <div class="list-item">
                <div>
                    <span class="list-item-title"><?= clean($s['name']) ?></span>
                    <span class="list-item-sub"><?= clean($s['roll_no']) ?></span>
                </div>
                <a href="?delete=<?= $s['id'] ?>" class="btn-delete" data-confirm="Remove this student? This deletes their attendance, marks, and group membership records too.">✕</a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
require_once 'includes/navbar.php';
require_once 'includes/footer.php';
?>