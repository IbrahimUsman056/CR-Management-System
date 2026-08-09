<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
requireLogin();

$crId = currentCrId();
$courseId = $_GET['course_id'] ?? null;
$date = $_GET['date'] ?? null;

$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND cr_id = ?");
$stmt->execute([$courseId, $crId]);
$course = $stmt->fetch();
if (!$course || !$date) { die('Invalid request.'); }

$stmt = $pdo->prepare("
    SELECT s.roll_no, s.name, a.status
    FROM enrollments e
    JOIN students s ON s.id = e.student_id
    LEFT JOIN attendance_records a ON a.student_id = s.id AND a.course_id = ? AND a.attendance_date = ?
    WHERE e.course_id = ? AND e.status IN ('active', 'repeater')
    ORDER BY s.roll_no
");
$stmt->execute([$courseId, $date, $courseId]);
$all = $stmt->fetchAll();

$present  = array_values(array_filter($all, fn($s) => $s['status'] === 'present'));
$absent   = array_values(array_filter($all, fn($s) => $s['status'] === 'absent'));
$unmarked = array_values(array_filter($all, fn($s) => empty($s['status'])));

$filename = preg_replace('/[^A-Za-z0-9_-]/', '_', $course['course_name']) . '_attendance_' . $date . '.xls';
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo "\xEF\xBB\xBF";

function attendanceRows($students, &$counter) {
    $html = '';
    foreach ($students as $s) {
        $counter++;
        $bg = $counter % 2 === 0 ? '#f6f8fc' : '#ffffff';
        $html .= '<tr style="background:' . $bg . ';">'
               . '<td style="padding:7px 12px;text-align:center;border:1px solid #e5e7eb;">' . $counter . '</td>'
               . '<td style="padding:7px 12px;border:1px solid #e5e7eb;">' . htmlspecialchars($s['roll_no']) . '</td>'
               . '<td style="padding:7px 12px;border:1px solid #e5e7eb;">' . htmlspecialchars($s['name']) . '</td>'
               . '</tr>';
    }
    return $html;
}
$counter = 0;
?>
<html>
<head><meta charset="UTF-8"></head>
<body>
<table border="0" cellspacing="0" cellpadding="8" style="border-collapse:collapse;font-family:Calibri,Arial,sans-serif;">
    <tr><td colspan="3" style="background:#2563eb;color:#ffffff;font-size:16px;font-weight:bold;padding:12px;">Attendance Report</td></tr>
    <tr><td colspan="3" style="padding:6px 12px;color:#555555;font-size:12px;">
        Section: <?= clean($_SESSION['section_name']) ?> &nbsp;|&nbsp;
        Course: <?= clean($course['course_name']) ?> &nbsp;|&nbsp;
        Teacher: <?= clean($course['teacher_name']) ?> &nbsp;|&nbsp;
        Date: <?= date('M j, Y', strtotime($date)) ?> &nbsp;|&nbsp;
        Total: <?= count($all) ?> students
    </td></tr>
    <tr><td colspan="3" style="padding:4px;"></td></tr>
    <tr>
        <th style="background:#1e40af;color:#ffffff;padding:8px 12px;text-align:center;border:1px solid #ffffff;">#</th>
        <th style="background:#1e40af;color:#ffffff;padding:8px 12px;text-align:left;border:1px solid #ffffff;">Roll No</th>
        <th style="background:#1e40af;color:#ffffff;padding:8px 12px;text-align:left;border:1px solid #ffffff;">Name</th>
    </tr>
    <?php if (!empty($present)): ?>
        <tr><td colspan="3" style="background:#dcfce7;color:#16a34a;font-weight:bold;padding:8px 12px;">PRESENT (<?= count($present) ?>)</td></tr>
        <?= attendanceRows($present, $counter) ?>
    <?php endif; ?>
    <?php if (!empty($absent)): ?>
        <tr><td colspan="3" style="background:#fee2e2;color:#dc2626;font-weight:bold;padding:8px 12px;">ABSENT (<?= count($absent) ?>)</td></tr>
        <?= attendanceRows($absent, $counter) ?>
    <?php endif; ?>
    <?php if (!empty($unmarked)): ?>
        <tr><td colspan="3" style="background:#f3f4f6;color:#6b7280;font-weight:bold;padding:8px 12px;">NOT MARKED (<?= count($unmarked) ?>)</td></tr>
        <?= attendanceRows($unmarked, $counter) ?>
    <?php endif; ?>
</table>
</body>
</html>