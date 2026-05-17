<?php
declare(strict_types=1);

require_once 'includes/helpers.php';
require_login();
require_once 'config/db.php';

$teacherId = current_teacher_id();
$classId = (int) ($_GET['class_id'] ?? $_POST['class_id'] ?? 0);

if ($classId <= 0) {
    flash('error', 'Class ID is required.');
    redirect('view_classes.php');
}

$class = require_teacher_class($conn, $classId, $teacherId);
$selectedWeek = (int) ($_GET['week'] ?? $_POST['week'] ?? 1);
$selectedDay = (int) ($_GET['day'] ?? $_POST['day'] ?? 1);
$totalWeeks = (int) $class['total_weeks'];

if ($selectedWeek < 1 || $selectedWeek > $totalWeeks) {
    $selectedWeek = 1;
}

if ($selectedDay < 1 || $selectedDay > 2) {
    $selectedDay = 1;
}

$stmt = $conn->prepare('SELECT id, name, email, roll_number FROM students WHERE teacher_id = ? AND class_id = ? ORDER BY name ASC');
$stmt->bind_param('ii', $teacherId, $classId);
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$validStudentIds = [];
foreach ($students as $student) {
    $validStudentIds[(int) $student['id']] = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mark_attendance') {
    $attendanceRows = $_POST['attendance'] ?? [];

    if (!$attendanceRows || !$validStudentIds) {
        flash('warning', 'No students were available to mark.');
        redirect('mark_attendance.php?class_id=' . $classId);
    }

    $conn->begin_transaction();
    $stmt = $conn->prepare(
        'INSERT INTO attendance (student_id, class_id, week, day, attendance)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE attendance = VALUES(attendance)'
    );
    $saved = 0;

    foreach ($attendanceRows as $studentId => $status) {
        $studentId = (int) $studentId;
        if (!isset($validStudentIds[$studentId])) {
            continue;
        }

        $attendanceValue = $status === 'present' ? 1 : 0;
        $stmt->bind_param('iiiii', $studentId, $classId, $selectedWeek, $selectedDay, $attendanceValue);
        $stmt->execute();
        $saved++;
    }

    $stmt->close();
    $conn->commit();

    if ($saved > 0) {
        flash('success', 'Attendance saved for week ' . $selectedWeek . ', class ' . $selectedDay . '.');
    } else {
        flash('warning', 'No valid students were submitted for attendance.');
    }

    redirect('mark_attendance.php?class_id=' . $classId . '&week=' . $selectedWeek . '&day=' . $selectedDay);
}

$existingAttendance = [];
$stmt = $conn->prepare('SELECT student_id, attendance FROM attendance WHERE class_id = ? AND week = ? AND day = ?');
$stmt->bind_param('iii', $classId, $selectedWeek, $selectedDay);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $existingAttendance[(int) $row['student_id']] = (int) $row['attendance'];
}
$stmt->close();

page_header('Mark Attendance', 'classes');
?>

<section class="page-title">
    <div>
        <h1>Mark Attendance</h1>
        <p><?php echo e($class['class_name']); ?>, <?php echo e($class['semester']); ?>. Update one week and class session at a time.</p>
    </div>
    <div class="toolbar">
        <a class="button button-secondary" href="view_classes.php">Back to Classes</a>
        <a class="button" href="attendance_report.php?class_id=<?php echo $classId; ?>">View Report</a>
    </div>
</section>

<section class="panel">
    <form method="GET" action="mark_attendance.php" class="form-grid filter-form">
        <input type="hidden" name="class_id" value="<?php echo $classId; ?>">
        <div class="field">
            <label for="week">Week</label>
            <select name="week" id="week">
                <?php for ($i = 1; $i <= $totalWeeks; $i++) { ?>
                    <option value="<?php echo $i; ?>" <?php echo $selectedWeek === $i ? 'selected' : ''; ?>>Week <?php echo $i; ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="field">
            <label for="day">Class Session</label>
            <select name="day" id="day">
                <option value="1" <?php echo $selectedDay === 1 ? 'selected' : ''; ?>>Class 1</option>
                <option value="2" <?php echo $selectedDay === 2 ? 'selected' : ''; ?>>Class 2</option>
            </select>
        </div>
        <div class="form-actions field-full">
            <button type="submit" class="button-secondary">Load Session</button>
        </div>
    </form>

    <?php if ($students) { ?>
        <form method="POST" action="mark_attendance.php">
            <input type="hidden" name="class_id" value="<?php echo $classId; ?>">
            <input type="hidden" name="week" value="<?php echo $selectedWeek; ?>">
            <input type="hidden" name="day" value="<?php echo $selectedDay; ?>">
            <input type="hidden" name="action" value="mark_attendance">

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Email</th>
                            <th>Roll Number</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student) {
                            $studentId = (int) $student['id'];
                            $isPresent = ($existingAttendance[$studentId] ?? 1) === 1;
                            ?>
                            <tr>
                                <td><strong><?php echo e($student['name']); ?></strong></td>
                                <td><?php echo e($student['email']); ?></td>
                                <td><?php echo e($student['roll_number']); ?></td>
                                <td>
                                    <select class="attendance-select" name="attendance[<?php echo $studentId; ?>]">
                                        <option value="present" <?php echo $isPresent ? 'selected' : ''; ?>>Present</option>
                                        <option value="absent" <?php echo !$isPresent ? 'selected' : ''; ?>>Absent</option>
                                    </select>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <div class="form-actions action-gap">
                <button type="submit">Save Attendance</button>
            </div>
        </form>
    <?php } else { ?>
        <div class="empty-state">
            This class has no students yet.
            <div class="empty-action">
                <a class="button" href="add_students.php?class_id=<?php echo $classId; ?>">Add Students</a>
            </div>
        </div>
    <?php } ?>
</section>

<?php page_footer(); ?>
