<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
requireLogin();

$crId = currentCrId();
$quizId = $_GET['quiz_id'] ?? null;

$stmt = $pdo->prepare("
    SELECT q.*, c.course_name, c.teacher_name, c.cr_id
    FROM quizzes q
    JOIN courses c ON c.id = q.course_id
    WHERE q.id = ?
");
$stmt->execute([$quizId]);
$quiz = $stmt->fetch();
if (!$quiz || $quiz['cr_id'] != $crId) { die('Quiz not found.'); }

$stmt = $pdo->prepare("
    SELECT s.roll_no, s.name, qm.marks_obtained
    FROM enrollments e
    JOIN students s ON s.id = e.student_id
    LEFT JOIN quiz_marks qm ON qm.student_id = s.id AND qm.quiz_id = ?
    WHERE e.course_id = ? AND e.status IN ('active', 'repeater')
    ORDER BY s.roll_no
");
$stmt->execute([$quizId, $quiz['course_id']]);
$rows = $stmt->fetchAll();

$entered = array_filter($rows, fn($r) => $r['marks_obtained'] !== null);
$average = count($entered) > 0 ? round(array_sum(array_column($entered, 'marks_obtained')) / count($entered), 1) : null;

$filename = preg_replace('/[^A-Za-z0-9_-]/', '_', $quiz['title']) . '_marks.xls';
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo "\xEF\xBB\xBF";
?>
<html>
<head><meta charset="UTF-8"></head>
<body>
<table border="0" cellspacing="0" cellpadding="8" style="border-collapse:collapse;font-family:Calibri,Arial,sans-serif;">
    <tr><td colspan="4" style="background:#2563eb;color:#ffffff;font-size:16px;font-weight:bold;padding:12px;"><?= clean($quiz['title']) ?></td></tr>
    <tr><td colspan="4" style="padding:6px 12px;color:#555555;font-size:12px;">
        Section: <?= clean($_SESSION['section_name']) ?> &nbsp;|&nbsp;
        Course: <?= clean($quiz['course_name']) ?> &nbsp;|&nbsp;
        Teacher: <?= clean($quiz['teacher_name']) ?> &nbsp;|&nbsp;
        Date: <?= date('M j, Y', strtotime($quiz['quiz_date'])) ?> &nbsp;|&nbsp;
        Total Marks: <?= $quiz['total_marks'] ?>
        <?php if ($average !== null): ?> &nbsp;|&nbsp; Average: <?= $average ?>/<?= $quiz['total_marks'] ?><?php endif; ?>
    </td></tr>
    <tr><td colspan="4" style="padding:4px;"></td></tr>
    <tr>
        <th style="background:#1e40af;color:#ffffff;padding:8px 12px;text-align:center;border:1px solid #ffffff;">#</th>
        <th style="background:#1e40af;color:#ffffff;padding:8px 12px;text-align:left;border:1px solid #ffffff;">Roll No</th>
        <th style="background:#1e40af;color:#ffffff;padding:8px 12px;text-align:left;border:1px solid #ffffff;">Name</th>
        <th style="background:#1e40af;color:#ffffff;padding:8px 12px;text-align:center;border:1px solid #ffffff;">Marks</th>
    </tr>
    <?php foreach ($rows as $i => $r): ?>
    <tr style="background:<?= $i % 2 === 0 ? '#f6f8fc' : '#ffffff' ?>;">
        <td style="padding:7px 12px;text-align:center;border:1px solid #e5e7eb;"><?= $i + 1 ?></td>
        <td style="padding:7px 12px;border:1px solid #e5e7eb;"><?= clean($r['roll_no']) ?></td>
        <td style="padding:7px 12px;border:1px solid #e5e7eb;"><?= clean($r['name']) ?></td>
        <td style="padding:7px 12px;text-align:center;border:1px solid #e5e7eb;"><?= $r['marks_obtained'] !== null ? clean($r['marks_obtained']) . '/' . $quiz['total_marks'] : '—' ?></td>
    </tr>
    <?php endforeach; ?>
</table>
</body>
</html>