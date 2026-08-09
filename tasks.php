<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
requireLogin();

$crId = currentCrId();
$pageTitle = 'Tasks';
$error = '';

// CR's courses (for the dropdown)
$courses = $pdo->prepare("SELECT id, course_name FROM courses WHERE cr_id = ? ORDER BY course_name");
$courses->execute([$crId]);
$courses = $courses->fetchAll();

// Add task
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_task'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $courseId = $_POST['course_id'] ?: null;
    $dueDate = $_POST['due_date'] ?: null;

    if ($title === '') {
        $error = 'Task title is required.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO tasks (cr_id, course_id, title, description, due_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$crId, $courseId, $title, $description, $dueDate]);
        redirect('/cr_portal/tasks.php');
    }
}

// Toggle done/pending
if (isset($_GET['toggle'])) {
    $stmt = $pdo->prepare("SELECT status FROM tasks WHERE id = ? AND cr_id = ?");
    $stmt->execute([$_GET['toggle'], $crId]);
    $t = $stmt->fetch();
    if ($t) {
        $newStatus = $t['status'] === 'pending' ? 'done' : 'pending';
        $upd = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ? AND cr_id = ?");
        $upd->execute([$newStatus, $_GET['toggle'], $crId]);
    }
    redirect('/cr_portal/tasks.php' . (isset($_GET['filter']) ? '?filter=' . $_GET['filter'] : ''));
}

// Delete task
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ? AND cr_id = ?");
    $stmt->execute([$_GET['delete'], $crId]);
    redirect('/cr_portal/tasks.php');
}

// Filter (default: pending first)
$filter = $_GET['filter'] ?? 'pending';

if ($filter === 'done') {
    $sql = "SELECT t.*, c.course_name FROM tasks t LEFT JOIN courses c ON c.id = t.course_id
            WHERE t.cr_id = ? AND t.status = 'done' ORDER BY t.due_date DESC";
} elseif ($filter === 'all') {
    $sql = "SELECT t.*, c.course_name FROM tasks t LEFT JOIN courses c ON c.id = t.course_id
            WHERE t.cr_id = ? ORDER BY t.status ASC, t.due_date ASC";
} else {
    $sql = "SELECT t.*, c.course_name FROM tasks t LEFT JOIN courses c ON c.id = t.course_id
            WHERE t.cr_id = ? AND t.status = 'pending' ORDER BY t.due_date ASC";
}
$stmt = $pdo->prepare($sql);
$stmt->execute([$crId]);
$tasks = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<div class="page-header">
    <h2>Tasks</h2>
</div>

<?php if ($error): ?>
    <div class="alert alert-error"><?= clean($error) ?></div>
<?php endif; ?>

<div class="form-card">
    <form method="POST" class="stacked-form">
        <input type="hidden" name="add_task" value="1">
        <input type="text" name="title" placeholder="Task (e.g. Collect assignment files)" required>
        <textarea name="description" placeholder="Details (optional)" rows="2"></textarea>
        <select name="course_id" class="status-select">
            <option value="">General (no specific course)</option>
            <?php foreach ($courses as $c): ?>
                <option value="<?= $c['id'] ?>"><?= clean($c['course_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="due_date">
        <button type="submit" class="btn-primary btn-sm">Add Task</button>
    </form>
</div>

<div class="tab-filter">
    <a href="?filter=pending" class="tab-btn <?= $filter === 'pending' ? 'active' : '' ?>">Pending</a>
    <a href="?filter=done" class="tab-btn <?= $filter === 'done' ? 'active' : '' ?>">Done</a>
    <a href="?filter=all" class="tab-btn <?= $filter === 'all' ? 'active' : '' ?>">All</a>
</div>

<div class="list-block">
    <?php if (empty($tasks)): ?>
        <p class="empty-state">Nothing here.</p>
    <?php else: ?>
        <?php foreach ($tasks as $t): ?>
            <?php
                $isOverdue = $t['due_date'] && $t['status'] === 'pending' && strtotime($t['due_date']) < strtotime(date('Y-m-d'));
            ?>
            <div class="task-card <?= $t['status'] === 'done' ? 'task-done' : '' ?>">
                <a href="?toggle=<?= $t['id'] ?>&filter=<?= $filter ?>" class="task-checkbox">
                    <?= $t['status'] === 'done' ? '✅' : '⬜' ?>
                </a>
                <div class="task-card-body">
                    <span class="list-item-title"><?= clean($t['title']) ?></span>
                    <?php if ($t['description']): ?>
                        <span class="task-desc"><?= clean($t['description']) ?></span>
                    <?php endif; ?>
                    <div class="task-meta">
                        <?php if ($t['course_name']): ?>
                            <span class="task-course"><?= clean($t['course_name']) ?></span>
                        <?php endif; ?>
                        <?php if ($t['due_date']): ?>
                            <span class="<?= $isOverdue ? 'task-overdue' : 'task-due' ?>">
                                <?= $isOverdue ? 'Overdue: ' : 'Due ' ?><?= date('M j', strtotime($t['due_date'])) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <a href="?delete=<?= $t['id'] ?>" class="btn-delete" data-confirm="Delete this task?">✕</a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
require_once 'includes/navbar.php';
require_once 'includes/footer.php';
?>