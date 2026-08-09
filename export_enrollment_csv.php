<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
requireLogin();

$crId = currentCrId();
$courseId = $_GET['course_id'] ?? null;

$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND cr_id = ?");
$stmt->execute([$courseId, $crId]);
$course = $stmt->fetch();
if (!$course) { die('Course not found.'); }

$stmt = $pdo->prepare("
    SELECT s.roll_no, s.name
    FROM enrollments e
    JOIN students s ON s.id = e.student_id
    WHERE e.course_id = ? AND e.status IN ('active', 'repeater')
    ORDER BY s.roll_no
");
$stmt->execute([$courseId]);
$rows = $stmt->fetchAll();

$filename = preg_replace('/[^A-Za-z0-9_-]/', '_', $course['course_name']) . '_enrollment.xls';
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo "\xEF\xBB\xBF";
?>
<html>
<head><meta charset="UTF-8"></head>
<body>
<table border="0" cellspacing="0" cellpadding="8" style="border-collapse:collapse;font-family:Calibri,Arial,sans-serif;">
    <tr><td colspan="3" style="background:#2563eb;color:#ffffff;font-size:16px;font-weight:bold;padding:12px;">Enrollment List</td></tr>
    <tr><td colspan="3" style="padding:6px 12px;color:#555555;font-size:12px;">
        Section: <?= clean($_SESSION['section_name']) ?> &nbsp;|&nbsp;
        Course: <?= clean($course['course_name']) ?> &nbsp;|&nbsp;
        Teacher: <?= clean($course['teacher_name']) ?> &nbsp;|&nbsp;
        Date: <?= date('M j, Y') ?> &nbsp;|&nbsp;
        Total: <?= count($rows) ?> students
    </td></tr>
    <tr><td colspan="3" style="padding:4px;"></td></tr>
    <tr>
        <th style="background:#1e40af;color:#ffffff;padding:8px 12px;text-align:center;border:1px solid #ffffff;">#</th>
        <th style="background:#1e40af;color:#ffffff;padding:8px 12px;text-align:left;border:1px solid #ffffff;">Roll No</th>
        <th style="background:#1e40af;color:#ffffff;padding:8px 12px;text-align:left;border:1px solid #ffffff;">Name</th>
    </tr>
    <?php foreach ($rows as $i => $r): ?>
    <tr style="background:<?= $i % 2 === 0 ? '#f6f8fc' : '#ffffff' ?>;">
        <td style="padding:7px 12px;text-align:center;border:1px solid #e5e7eb;"><?= $i + 1 ?></td>
        <td style="padding:7px 12px;border:1px solid #e5e7eb;"><?= clean($r['roll_no']) ?></td>
        <td style="padding:7px 12px;border:1px solid #e5e7eb;"><?= clean($r['name']) ?></td>
    </tr>
    <?php endforeach; ?>
</table>
</body>
</html>