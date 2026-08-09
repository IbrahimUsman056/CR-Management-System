<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
requireLogin();

$crId = currentCrId();

$groups = $pdo->prepare("SELECT * FROM project_groups WHERE cr_id = ? ORDER BY created_at");
$groups->execute([$crId]);
$groups = $groups->fetchAll();

$memberStmt = $pdo->prepare("
    SELECT s.roll_no, s.name
    FROM project_group_members m
    JOIN students s ON s.id = m.student_id
    WHERE m.group_id = ?
    ORDER BY s.roll_no
");

$maxMembers = 0;
foreach ($groups as &$g) {
    $memberStmt->execute([$g['id']]);
    $g['members'] = $memberStmt->fetchAll();
    $maxMembers = max($maxMembers, count($g['members']));
}
unset($g);

header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="project_groups.xls"');
echo "\xEF\xBB\xBF";

$colCount = 2 + $maxMembers + 1;
?>
<html>
<head><meta charset="UTF-8"></head>
<body>
<table border="0" cellspacing="0" cellpadding="8" style="border-collapse:collapse;font-family:Calibri,Arial,sans-serif;">
    <tr><td colspan="<?= $colCount ?>" style="background:#2563eb;color:#ffffff;font-size:16px;font-weight:bold;padding:12px;">Project Groups</td></tr>
    <tr><td colspan="<?= $colCount ?>" style="padding:6px 12px;color:#555555;font-size:12px;">
        Section: <?= clean($_SESSION['section_name']) ?> &nbsp;|&nbsp;
        Date: <?= date('M j, Y') ?> &nbsp;|&nbsp;
        Total Groups: <?= count($groups) ?>
    </td></tr>
    <tr><td colspan="<?= $colCount ?>" style="padding:4px;"></td></tr>
    <tr>
        <th style="background:#1e40af;color:#ffffff;padding:8px 12px;text-align:center;border:1px solid #ffffff;">#</th>
        <th style="background:#1e40af;color:#ffffff;padding:8px 12px;text-align:left;border:1px solid #ffffff;">Group Name</th>
        <?php for ($m = 1; $m <= $maxMembers; $m++): ?>
            <th style="background:#1e40af;color:#ffffff;padding:8px 12px;text-align:left;border:1px solid #ffffff;">Member <?= $m ?></th>
        <?php endfor; ?>
        <th style="background:#1e40af;color:#ffffff;padding:8px 12px;text-align:left;border:1px solid #ffffff;">Project Name</th>
    </tr>
    <?php foreach ($groups as $i => $g): ?>
    <tr style="background:<?= $i % 2 === 0 ? '#f6f8fc' : '#ffffff' ?>;">
        <td style="padding:7px 12px;text-align:center;border:1px solid #e5e7eb;"><?= $i + 1 ?></td>
        <td style="padding:7px 12px;border:1px solid #e5e7eb;"><?= clean($g['group_name']) ?></td>
        <?php for ($m = 0; $m < $maxMembers; $m++): ?>
            <td style="padding:7px 12px;border:1px solid #e5e7eb;"><?= isset($g['members'][$m]) ? clean($g['members'][$m]['name']) : '—' ?></td>
        <?php endfor; ?>
        <td style="padding:7px 12px;border:1px solid #e5e7eb;"><?= $g['project_name'] ? clean($g['project_name']) : '—' ?></td>
    </tr>
    <?php endforeach; ?>
</table>
</body>
</html>