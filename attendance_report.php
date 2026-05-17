<?php
declare(strict_types=1);

require_once 'includes/helpers.php';
require_login();
require_once 'config/db.php';

$teacherId = current_teacher_id();
$classId = (int) ($_GET['class_id'] ?? 0);

if ($classId <= 0) {
    flash('error', 'Class ID is required.');
    redirect('view_classes.php');
}

$class = require_teacher_class($conn, $classId, $teacherId);
$totalWeeks = (int) $class['total_weeks'];
$weekFilter = (int) ($_GET['week'] ?? 0);
$dayFilter = (int) ($_GET['day'] ?? 0);

if ($weekFilter < 0 || $weekFilter > $totalWeeks) {
    $weekFilter = 0;
}

if ($dayFilter < 0 || $dayFilter > 2) {
    $dayFilter = 0;
}

$stmt = $conn->prepare(
    'SELECT s.id,
            s.name,
            s.email,
            s.roll_number,
            a.week,
            a.day,
            a.attendance
     FROM students s
     LEFT JOIN attendance a
        ON a.student_id = s.id
        AND a.class_id = s.class_id
        AND (? = 0 OR a.week = ?)
        AND (? = 0 OR a.day = ?)
     WHERE s.teacher_id = ? AND s.class_id = ?
     ORDER BY s.name ASC, a.week ASC, a.day ASC'
);
$stmt->bind_param('iiiiii', $weekFilter, $weekFilter, $dayFilter, $dayFilter, $teacherId, $classId);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$studentIds = [];
$presentCount = 0;
$absentCount = 0;
$markedCount = 0;

foreach ($rows as $row) {
    $studentIds[(int) $row['id']] = true;

    if ($row['attendance'] !== null) {
        $markedCount++;
        if ((int) $row['attendance'] === 1) {
            $presentCount++;
        } else {
            $absentCount++;
        }
    }
}

$studentCount = count($studentIds);
$presentRate = $markedCount > 0 ? round(($presentCount / $markedCount) * 100) : 0;

page_header('Attendance Report', 'classes');
?>

<section class="page-title">
    <div>
        <h1>Attendance Report</h1>
        <p><?php echo e($class['class_name']); ?>, <?php echo e($class['semester']); ?>. Filter attendance by week and session.</p>
    </div>
    <div class="toolbar">
        <a class="button button-secondary" href="view_classes.php">Back to Classes</a>
        <a class="button" href="mark_attendance.php?class_id=<?php echo $classId; ?>">Mark Attendance</a>
    </div>
</section>

<section class="report-summary">
    <div class="metric">
        <strong><?php echo $studentCount; ?></strong>
        <span>Students in class</span>
    </div>
    <div class="metric">
        <strong><?php echo $markedCount; ?></strong>
        <span>Marked sessions</span>
    </div>
    <div class="metric">
        <strong><?php echo $presentRate; ?>%</strong>
        <span>Present rate</span>
    </div>
</section>

<section class="panel">
    <form method="GET" action="attendance_report.php" class="form-grid filter-form">
        <input type="hidden" name="class_id" value="<?php echo $classId; ?>">
        <div class="field">
            <label for="week">Week</label>
            <select name="week" id="week">
                <option value="0">All Weeks</option>
                <?php for ($i = 1; $i <= $totalWeeks; $i++) { ?>
                    <option value="<?php echo $i; ?>" <?php echo $weekFilter === $i ? 'selected' : ''; ?>>Week <?php echo $i; ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="field">
            <label for="day">Class Session</label>
            <select name="day" id="day">
                <option value="0">All Sessions</option>
                <option value="1" <?php echo $dayFilter === 1 ? 'selected' : ''; ?>>Class 1</option>
                <option value="2" <?php echo $dayFilter === 2 ? 'selected' : ''; ?>>Class 2</option>
            </select>
        </div>
        <div class="form-actions field-full">
            <button type="submit">Filter Report</button>
        </div>
    </form>

    <?php if ($rows) { ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Roll Number</th>
                        <th>Week</th>
                        <th>Session</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row) {
                        $hasAttendance = $row['attendance'] !== null;
                        $statusClass = !$hasAttendance ? 'badge-warning' : ((int) $row['attendance'] === 1 ? 'badge-success' : 'badge-danger');
                        $statusText = !$hasAttendance ? 'Not marked' : ((int) $row['attendance'] === 1 ? 'Present' : 'Absent');
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo e($row['name']); ?></strong><br>
                                <small><?php echo e($row['email']); ?></small>
                            </td>
                            <td><?php echo e($row['roll_number']); ?></td>
                            <td><?php echo $hasAttendance ? 'Week ' . (int) $row['week'] : '-'; ?></td>
                            <td><?php echo $hasAttendance ? 'Class ' . (int) $row['day'] : '-'; ?></td>
                            <td><span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } else { ?>
        <div class="empty-state">
            No students are assigned to this class yet.
            <div class="empty-action">
                <a class="button" href="add_students.php?class_id=<?php echo $classId; ?>">Add Students</a>
            </div>
        </div>
    <?php } ?>
</section>

<?php page_footer(); ?>
