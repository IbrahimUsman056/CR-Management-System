<?php
require_once 'config/db.php';

// ============================================================
// EDIT THESE IF NEEDED, THEN VISIT THIS FILE ONCE IN BROWSER,
// THEN DELETE IT FROM THE SERVER.
// ============================================================
$CR_NAME     = 'Muhammad Ibrahim Usman';
$CR_ROLL     = '243506';
$CR_EMAIL    = '243506@students.au.edu.pk';
$CR_PASSWORD = '123456'; // change after first login if you want
$SECTION     = 'BSCS-F24-A';

mt_srand(42); // fixed seed so re-running gives the same realistic-looking data

// Full class roster: [roll_no, name]
$roster = [
    ['241849', 'Haider'],
    ['243504', 'Abdulrafay Jahangir'],
    ['243506', 'Muhammad Ibrahim Usman'],
    ['243507', 'Sufyan'],
    ['243508', 'Hammad Ahmad'],
    ['243510', 'Zainab Asif'],
    ['243512', 'Arooj Fatima Siddiqui'],
    ['243514', 'Muhammad Zukhruf Naveed'],
    ['243516', 'Rao Muhammad Aashir Hayat'],
    ['243522', 'Zainab Rehman'],
    ['243524', 'Muhammad Shahiq Jawad'],
    ['243526', 'Abdul-Fatir'],
    ['243528', 'Muhammad Saad Shafeeq'],
    ['243530', 'Wamiq Ejaz'],
    ['243532', 'Rija Nadeem'],
    ['243534', 'Ali Hassan'],
    ['243538', 'Faiz Ur Rehman'],
    ['243542', 'Kaif Nawaz'],
    ['243544', 'Abdul Ghani'],
    ['243546', 'Hajra Mughees'],
    ['243548', 'Muhammad Ali'],
    ['243550', 'Ahmed Faraz'],
    ['243552', 'Ayesha Zafar'],
    ['243554', 'Abdullah Arshad'],
    ['243556', 'Rohemah Asghar'],
    ['243558', 'Urooj Fatima'],
    ['243560', 'Savera Nadeem'],
    ['243562', 'Ayesha'],
    ['243564', 'Muhammad Khubaib Malik'],
    ['243566', 'Umme Roman'],
    ['243568', 'Muhammad Ashaar'],
    ['243570', 'Muzdalfa Rehman'],
    ['243572', 'Imtasal Fatima'],
    ['243574', 'Zainab Sohail'],
    ['243576', 'Anusha Ahmad'],
    ['243578', 'Muhammad Sohaib Romi'],
    ['243580', 'Shanzay Masood'],
    ['243582', 'Muhammad Hassaan'],
    ['243586', 'Syed Hussain Mehdi'],
    ['243588', 'Muhammed Shafi'],
    ['243590', 'Hooria Sajjad Khan'],
    ['243592', 'Waleed Abdullah'],
    ['243596', 'Maryam Bakhat'],
    ['243604', 'Muhammad Usman'],
    ['243606', 'Muhammad Abdullah'],
    ['243608', 'Muhammad Abdullah'],
    ['243610', 'Hassan Iqbal'],
    ['243618', 'Ahtasham Ul Haq'],
    ['243781', 'Maheen Akram'],
    ['243782', 'Fizza Naeem'],
    ['243914', 'Manal Shoaib Chatha'],
];

// Courses: [name, teacher]
$courses = [
    ['FullStack', 'Sir Rashaf'],
    ['COAL',      'Mam Mamoona'],
    ['SE',        'Sir Mateen'],
    ['Civics',    'Sir Waqar'],
    ['MVC',       'Mam Ammara'],
    ['CN',        'Sir Saqib'],
];

// Sample project names (some groups will get "optional" = none)
$projectNames = [
    'Smart Attendance System',
    'Campus Navigation App',
    'E-Learning Portal',
    'Inventory Management System',
    'Food Delivery App',
    'Library Automation System',
    'Event Management Platform',
    'Online Voting System',
    null, // no project name yet
    null,
];

try {
    $pdo->beginTransaction();

    // ---------- 1. WIPE EXISTING DATA ----------
    // Deleting from cr cascades through students, courses, enrollments,
    // attendance_records, quizzes, quiz_marks, tasks, project_groups,
    // project_group_members automatically (all FKs are ON DELETE CASCADE).
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("DELETE FROM quiz_marks");
    $pdo->exec("DELETE FROM quizzes");
    $pdo->exec("DELETE FROM attendance_records");
    $pdo->exec("DELETE FROM enrollments");
    $pdo->exec("DELETE FROM project_group_members");
    $pdo->exec("DELETE FROM project_groups");
    $pdo->exec("DELETE FROM tasks");
    $pdo->exec("DELETE FROM courses");
    $pdo->exec("DELETE FROM students");
    $pdo->exec("DELETE FROM cr");
    $pdo->exec("ALTER TABLE cr AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE students AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE courses AUTO_INCREMENT = 1");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // ---------- 2. CREATE CR ACCOUNT ----------
    $hashed = password_hash($CR_PASSWORD, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO cr (name, email, password, section_name) VALUES (?, ?, ?, ?)");
    $stmt->execute([$CR_NAME, $CR_EMAIL, $hashed, $SECTION]);
    $crId = $pdo->lastInsertId();

    // ---------- 3. INSERT STUDENTS ----------
    $studentIds = []; // roll_no => id
    $stmt = $pdo->prepare("INSERT INTO students (cr_id, roll_no, name) VALUES (?, ?, ?)");
    foreach ($roster as [$roll, $name]) {
        $stmt->execute([$crId, $roll, $name]);
        $studentIds[$roll] = $pdo->lastInsertId();
    }
    $allStudentIds = array_values($studentIds);

    // ---------- 4. INSERT COURSES ----------
    $courseIds = []; // course_name => id
    $stmt = $pdo->prepare("INSERT INTO courses (cr_id, course_name, teacher_name) VALUES (?, ?, ?)");
    foreach ($courses as [$cname, $teacher]) {
        $stmt->execute([$crId, $cname, $teacher]);
        $courseIds[$cname] = $pdo->lastInsertId();
    }

    // ---------- 5. ENROLLMENTS (different count per course) ----------
    $enrollStmt = $pdo->prepare("INSERT INTO enrollments (course_id, student_id, status) VALUES (?, ?, ?)");
    $courseEnrollment = []; // course_name => [ [student_id, status], ... ]

    foreach ($courses as [$cname, $teacher]) {
        $courseEnrollment[$cname] = [];
        foreach ($allStudentIds as $sid) {
            $roll = 0;
            $chance = mt_rand(1, 100);
            if ($chance <= 78) {
                $status = 'active';
            } elseif ($chance <= 85) {
                $status = 'repeater';
            } else {
                continue; // not enrolled in this course (pre-req not met / elective / etc.)
            }
            $enrollStmt->execute([$courseIds[$cname], $sid, $status]);
            $courseEnrollment[$cname][] = [$sid, $status];
        }
    }

    // ---------- 6. ATTENDANCE: 10 weekdays per course ----------
    $attendanceDates = [];
    $cursor = new DateTime('today');
    $cursor->modify('-1 day'); // start from yesterday, walk backwards
    while (count($attendanceDates) < 10) {
        $dow = (int) $cursor->format('N'); // 6 = Sat, 7 = Sun
        if ($dow < 6) {
            $attendanceDates[] = $cursor->format('Y-m-d');
        }
        $cursor->modify('-1 day');
    }
    $attendanceDates = array_reverse($attendanceDates); // chronological order

    $attStmt = $pdo->prepare("INSERT INTO attendance_records (course_id, student_id, attendance_date, status) VALUES (?, ?, ?, ?)");
    foreach ($courses as [$cname, $teacher]) {
        foreach ($attendanceDates as $date) {
            foreach ($courseEnrollment[$cname] as [$sid, $enrollStatus]) {
                $chance = mt_rand(1, 100);
                if ($chance <= 80) {
                    $status = 'present';
                } elseif ($chance <= 95) {
                    $status = 'absent';
                } else {
                    $status = 'leave';
                }
                $attStmt->execute([$courseIds[$cname], $sid, $date, $status]);
            }
        }
    }

    // ---------- 7. QUIZZES: 3 per course, with marks ----------
    $quizStmt = $pdo->prepare("INSERT INTO quizzes (course_id, title, total_marks, quiz_date) VALUES (?, ?, ?, ?)");
    $markStmt = $pdo->prepare("INSERT INTO quiz_marks (quiz_id, student_id, marks_obtained) VALUES (?, ?, ?)");
    $quizTotals = [10, 15, 20];

    foreach ($courses as [$cname, $teacher]) {
        for ($q = 1; $q <= 3; $q++) {
            $total = $quizTotals[$q - 1];
            // spread quiz dates across the same 10-day window
            $quizDate = $attendanceDates[min(($q - 1) * 3, count($attendanceDates) - 1)];

            $quizStmt->execute([$courseIds[$cname], "Quiz $q", $total, $quizDate]);
            $quizId = $pdo->lastInsertId();

            foreach ($courseEnrollment[$cname] as [$sid, $enrollStatus]) {
                // ~90% of students have marks entered, rest left blank (not graded yet)
                if (mt_rand(1, 100) <= 90) {
                    $pct = mt_rand(40, 100) / 100;
                    $marks = round($total * $pct, 1);
                    $markStmt->execute([$quizId, $sid, $marks]);
                }
            }
        }
    }

    // ---------- 8. PROJECT GROUPS (all students placed, groups of ~5) ----------
    $shuffled = $allStudentIds;
    shuffle($shuffled); // uses mt_rand internally, still deterministic due to mt_srand above
    $groupChunks = array_chunk($shuffled, 5);

    $groupStmt = $pdo->prepare("INSERT INTO project_groups (cr_id, group_name, project_name) VALUES (?, ?, ?)");
    $memberStmt = $pdo->prepare("INSERT INTO project_group_members (group_id, student_id) VALUES (?, ?)");

    foreach ($groupChunks as $i => $memberIds) {
        $groupLetter = chr(65 + $i); // A, B, C...
        $projectName = $projectNames[$i % count($projectNames)];
        $groupStmt->execute([$crId, "Group $groupLetter", $projectName]);
        $groupId = $pdo->lastInsertId();

        foreach ($memberIds as $sid) {
            $memberStmt->execute([$groupId, $sid]);
        }
    }

    $pdo->commit();

    // ---------- SUMMARY ----------
    echo "<h2>Seed completed successfully</h2>";
    echo "<p><strong>Login:</strong><br>Email: $CR_EMAIL<br>Password: $CR_PASSWORD</p>";
    echo "<p><strong>Section:</strong> $SECTION</p>";
    echo "<p><strong>Students:</strong> " . count($roster) . "</p>";
    echo "<p><strong>Courses:</strong> " . count($courses) . " (";
    foreach ($courses as [$cname, $teacher]) {
        echo count($courseEnrollment[$cname]) . " in $cname, ";
    }
    echo ")</p>";
    echo "<p><strong>Attendance dates used:</strong> " . implode(', ', $attendanceDates) . "</p>";
    echo "<p><strong>Project groups:</strong> " . count($groupChunks) . "</p>";
    echo "<p style='color:red;font-weight:bold;'>Delete seed.php from the server now.</p>";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "Seeding failed: " . $e->getMessage();
}