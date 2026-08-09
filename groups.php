<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
requireLogin();

$crId = currentCrId();
$pageTitle = 'Project Groups';
$error = '';

// Create group
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_group'])) {
    $groupName = trim($_POST['group_name'] ?? '');
    $projectName = trim($_POST['project_name'] ?? '');
    $memberIds = $_POST['members'] ?? [];

    if ($groupName === '') {
        $error = 'Group name is required.';
    } elseif (empty($memberIds)) {
        $error = 'Select at least one member.';
    } else {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO project_groups (cr_id, group_name, project_name) VALUES (?, ?, ?)");
        $stmt->execute([$crId, $groupName, $projectName ?: null]);
        $groupId = $pdo->lastInsertId();

        $memberStmt = $pdo->prepare("INSERT INTO project_group_members (group_id, student_id) VALUES (?, ?)");
        foreach (array_unique($memberIds) as $studentId) {
            $memberStmt->execute([$groupId, $studentId]);
        }
        $pdo->commit();
        redirect('/cr_portal/groups.php');
    }
}

// Update project name for an existing group
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_project'])) {
    $groupId = $_POST['group_id'] ?? null;
    $projectName = trim($_POST['project_name'] ?? '');

    $check = $pdo->prepare("SELECT id FROM project_groups WHERE id = ? AND cr_id = ?");
    $check->execute([$groupId, $crId]);
    if ($check->fetch()) {
        $upd = $pdo->prepare("UPDATE project_groups SET project_name = ? WHERE id = ?");
        $upd->execute([$projectName ?: null, $groupId]);
    }
    redirect('/cr_portal/groups.php');
}

// Delete group
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM project_groups WHERE id = ? AND cr_id = ?");
    $stmt->execute([$_GET['delete'], $crId]);
    redirect('/cr_portal/groups.php');
}

// Remove a single member from a group
if (isset($_GET['remove_member']) && isset($_GET['group_id'])) {
    $check = $pdo->prepare("SELECT id FROM project_groups WHERE id = ? AND cr_id = ?");
    $check->execute([$_GET['group_id'], $crId]);
    if ($check->fetch()) {
        $stmt = $pdo->prepare("DELETE FROM project_group_members WHERE group_id = ? AND student_id = ?");
        $stmt->execute([$_GET['group_id'], $_GET['remove_member']]);
    }
    redirect('/cr_portal/groups.php');
}

// Students already assigned to ANY group (excluded from the picker)
$assignedIds = $pdo->prepare("
    SELECT DISTINCT m.student_id
    FROM project_group_members m
    JOIN project_groups g ON g.id = m.group_id
    WHERE g.cr_id = ?
");
$assignedIds->execute([$crId]);
$assignedIds = array_column($assignedIds->fetchAll(), 'student_id');

$placeholders = empty($assignedIds) ? '' : ' AND id NOT IN (' . implode(',', array_fill(0, count($assignedIds), '?')) . ')';
$rosterStmt = $pdo->prepare("SELECT id, roll_no, name FROM students WHERE cr_id = ?" . $placeholders . " ORDER BY roll_no");
$rosterStmt->execute(array_merge([$crId], $assignedIds));
$availableStudents = $rosterStmt->fetchAll();

$groups = $pdo->prepare("SELECT * FROM project_groups WHERE cr_id = ? ORDER BY created_at DESC");
$groups->execute([$crId]);
$groups = $groups->fetchAll();

$memberFetch = $pdo->prepare("
    SELECT s.id, s.name, s.roll_no
    FROM project_group_members pm
    JOIN students s ON s.id = pm.student_id
    WHERE pm.group_id = ?
    ORDER BY s.roll_no
");
foreach ($groups as &$g) {
    $memberFetch->execute([$g['id']]);
    $g['members'] = $memberFetch->fetchAll();
}
unset($g);

$maxMembers = 0;
foreach ($groups as $g) {
    $maxMembers = max($maxMembers, count($g['members']));
}

require_once 'includes/header.php';
?>

<div class="page-header">
    <h2>Project Groups</h2>
    <span class="page-count"><?= count($groups) ?> groups</span>
</div>

<?php if (!empty($groups)): ?>
    <div class="export-bar">
        <a href="export_groups_csv.php" class="export-btn">⬇ Excel</a>
        <button type="button" class="export-btn" onclick="exportAsImage('groups-capture', 'project_groups')">📷 JPG</button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?= clean($error) ?></div>
<?php endif; ?>

<section class="section-block">
    <h3>Create New Group</h3>
    <div class="form-card">
        <?php if (empty($availableStudents)): ?>
            <p class="empty-state">
                <?= empty($assignedIds) ? 'Add students to your roster first.' : 'All students are already assigned to a group.' ?>
            </p>
        <?php else: ?>
            <form method="POST" class="stacked-form">
                <input type="hidden" name="add_group" value="1">
                <input type="text" name="group_name" placeholder="Group Name (e.g. Group A)" required>
                <input type="text" name="project_name" placeholder="Project Name (optional)">

                <label class="field-label">Members (<?= count($availableStudents) ?> available)</label>
                <div class="member-checklist">
                    <?php foreach ($availableStudents as $s): ?>
                        <label class="member-check">
                            <input type="checkbox" name="members[]" value="<?= $s['id'] ?>">
                            <?= clean($s['name']) ?> <span class="member-roll">(<?= clean($s['roll_no']) ?>)</span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <button type="submit" class="btn-primary btn-sm">Create Group</button>
            </form>
        <?php endif; ?>
    </div>
</section>

<?php if (!empty($groups)): ?>
<!-- Offscreen: used only for image/Excel export, not shown on the page -->
<div id="groups-capture" class="report-card capture-offscreen">
    <div class="report-header">
        <div class="report-title">Project Groups</div>
        <div class="report-subtitle"><?= clean($_SESSION['section_name']) ?></div>
        <div class="report-meta">
            <span class="report-meta-item"><strong>Date:</strong> <?= date('M j, Y') ?></span>
            <span class="report-meta-item"><strong>Total Groups:</strong> <?= count($groups) ?></span>
        </div>
    </div>
    <table class="report-table">
        <thead>
            <tr>
                <th class="report-count-col">#</th>
                <th>Group Name</th>
                <?php for ($m = 1; $m <= $maxMembers; $m++): ?>
                    <th>Member <?= $m ?></th>
                <?php endfor; ?>
                <th>Project Name</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($groups as $i => $g): ?>
                <tr>
                    <td class="report-count-col"><?= $i + 1 ?></td>
                    <td><?= clean($g['group_name']) ?></td>
                    <?php for ($m = 0; $m < $maxMembers; $m++): ?>
                        <td><?= isset($g['members'][$m]) ? clean($g['members'][$m]['name']) : '—' ?></td>
                    <?php endfor; ?>
                    <td><?= $g['project_name'] ? clean($g['project_name']) : '—' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<section class="section-block">
    <h3>Manage Groups</h3>
    <?php if (empty($groups)): ?>
        <p class="empty-state">No groups created yet.</p>
    <?php else: ?>
        <div class="list-block">
            <?php foreach ($groups as $g): ?>
                <div class="group-card">
                    <div class="group-card-header">
                        <div>
                            <span class="list-item-title"><?= clean($g['group_name']) ?></span>
                        </div>
                        <a href="?delete=<?= $g['id'] ?>" class="btn-delete" data-confirm="Delete this whole group? This cannot be undone.">✕</a>
                    </div>

                    <div class="group-members">
                        <?php foreach ($g['members'] as $m): ?>
                            <span class="member-chip">
                                <?= clean($m['name']) ?>
                                <a href="?remove_member=<?= $m['id'] ?>&group_id=<?= $g['id'] ?>" class="member-chip-x" data-confirm="Remove <?= clean($m['name']) ?> from this group?">✕</a>
                            </span>
                        <?php endforeach; ?>
                    </div>

                    <form method="POST" class="project-edit-form">
                        <input type="hidden" name="update_project" value="1">
                        <input type="hidden" name="group_id" value="<?= $g['id'] ?>">
                        <input type="text" name="project_name" value="<?= clean($g['project_name'] ?? '') ?>" placeholder="Project name">
                        <button type="submit" class="btn-mini-save">Save</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php
require_once 'includes/navbar.php';
require_once 'includes/footer.php';
?>