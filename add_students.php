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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_student') {
        $studentName = trim((string) ($_POST['student_name'] ?? ''));
        $studentEmail = trim((string) ($_POST['student_email'] ?? ''));
        $studentRollNumber = trim((string) ($_POST['student_roll_number'] ?? ''));

        if ($studentName === '' || $studentEmail === '' || $studentRollNumber === '') {
            flash('error', 'Student name, email, and roll number are required.');
            redirect('add_students.php?class_id=' . $classId);
        }

        if (!filter_var($studentEmail, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Please enter a valid student email address.');
            redirect('add_students.php?class_id=' . $classId);
        }

        $stmt = $conn->prepare('INSERT INTO students (teacher_id, name, email, roll_number, class_id) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('isssi', $teacherId, $studentName, $studentEmail, $studentRollNumber, $classId);
        $stmt->execute();
        $stmt->close();

        flash('success', 'Student created and added to this class.');
        redirect('add_students.php?class_id=' . $classId);
    }

    if ($action === 'assign_students') {
        $studentIds = $_POST['students'] ?? [];

        if (!$studentIds) {
            flash('warning', 'Choose at least one unassigned student.');
            redirect('add_students.php?class_id=' . $classId);
        }

        $stmt = $conn->prepare('UPDATE students SET class_id = ? WHERE id = ? AND teacher_id = ? AND class_id IS NULL');
        $assigned = 0;

        foreach ($studentIds as $studentId) {
            $studentId = (int) $studentId;
            $stmt->bind_param('iii', $classId, $studentId, $teacherId);
            $stmt->execute();
            $assigned += $stmt->affected_rows;
        }

        $stmt->close();
        flash('success', $assigned . ' student(s) added to this class.');
        redirect('add_students.php?class_id=' . $classId);
    }
}

$stmt = $conn->prepare('SELECT id, name, email, roll_number FROM students WHERE teacher_id = ? AND class_id IS NULL ORDER BY name ASC');
$stmt->bind_param('i', $teacherId);
$stmt->execute();
$availableStudents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $conn->prepare('SELECT id, name, email, roll_number FROM students WHERE teacher_id = ? AND class_id = ? ORDER BY name ASC');
$stmt->bind_param('ii', $teacherId, $classId);
$stmt->execute();
$classStudents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

page_header('Manage Students', 'classes');
?>

<section class="page-title">
    <div>
        <h1><?php echo e($class['class_name']); ?> Students</h1>
        <p>Add new students directly to this class or assign existing unassigned students.</p>
    </div>
    <div class="toolbar">
        <a class="button button-secondary" href="view_classes.php">Back to Classes</a>
        <a class="button" href="mark_attendance.php?class_id=<?php echo $classId; ?>">Mark Attendance</a>
    </div>
</section>

<section class="grid grid-two">
    <div class="panel">
        <div class="panel-header">
            <div>
                <h2>Create Student</h2>
                <p class="panel-subtitle">Student will be attached to <?php echo e($class['class_name']); ?>.</p>
            </div>
        </div>
        <form method="POST" action="add_students.php" class="form-grid">
            <input type="hidden" name="class_id" value="<?php echo $classId; ?>">
            <input type="hidden" name="action" value="create_student">
            <div class="field">
                <label for="student_name">Student Name</label>
                <input type="text" id="student_name" name="student_name" placeholder="Student name" required>
            </div>
            <div class="field">
                <label for="student_roll_number">Roll Number</label>
                <input type="text" id="student_roll_number" name="student_roll_number" placeholder="Roll number" required>
            </div>
            <div class="field field-full">
                <label for="student_email">Student Email</label>
                <input type="email" id="student_email" name="student_email" placeholder="student@example.com" required>
            </div>
            <div class="form-actions field-full">
                <button type="submit">Create Student</button>
            </div>
        </form>
    </div>

    <div class="panel">
        <div class="panel-header">
            <div>
                <h2>Assign Existing Students</h2>
                <p class="panel-subtitle">Only your unassigned students are shown here.</p>
            </div>
        </div>
        <?php if ($availableStudents) { ?>
            <form method="POST" action="add_students.php">
                <input type="hidden" name="class_id" value="<?php echo $classId; ?>">
                <input type="hidden" name="action" value="assign_students">
                <div class="check-list">
                    <?php foreach ($availableStudents as $student) { ?>
                        <label class="check-row">
                            <input type="checkbox" name="students[]" value="<?php echo (int) $student['id']; ?>">
                            <span>
                                <strong><?php echo e($student['name']); ?></strong>
                                <small><?php echo e($student['roll_number']); ?>, <?php echo e($student['email']); ?></small>
                            </span>
                        </label>
                    <?php } ?>
                </div>
                <div class="form-actions action-gap">
                    <button type="submit">Add Selected</button>
                </div>
            </form>
        <?php } else { ?>
            <div class="empty-state">No unassigned students are available.</div>
        <?php } ?>
    </div>
</section>

<section class="panel section-gap">
    <div class="panel-header">
        <div>
            <h2>Class Roster</h2>
            <p class="panel-subtitle"><?php echo count($classStudents); ?> student(s) currently assigned.</p>
        </div>
    </div>

    <?php if ($classStudents) { ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Roll Number</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($classStudents as $student) { ?>
                        <tr>
                            <td><?php echo e($student['name']); ?></td>
                            <td><?php echo e($student['email']); ?></td>
                            <td><?php echo e($student['roll_number']); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } else { ?>
        <div class="empty-state">This class has no students yet.</div>
    <?php } ?>
</section>

<?php page_footer(); ?>
