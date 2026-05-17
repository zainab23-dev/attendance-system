<?php
declare(strict_types=1);

require_once 'includes/helpers.php';
require_login();
require_once 'config/db.php';

$teacherId = current_teacher_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_student') {
    $studentName = trim((string) ($_POST['student_name'] ?? ''));
    $studentEmail = trim((string) ($_POST['student_email'] ?? ''));
    $studentRollNumber = trim((string) ($_POST['student_roll_number'] ?? ''));
    $classId = (int) ($_POST['class_id'] ?? 0);
    $classIdValue = null;

    if ($studentName === '' || $studentEmail === '' || $studentRollNumber === '') {
        flash('error', 'Student name, email, and roll number are required.');
        redirect('dashboard.php');
    }

    if (!filter_var($studentEmail, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'Please enter a valid student email address.');
        redirect('dashboard.php');
    }

    if ($classId > 0) {
        require_teacher_class($conn, $classId, $teacherId);
        $classIdValue = $classId;
    }

    if ($classIdValue === null) {
        $stmt = $conn->prepare('INSERT INTO students (teacher_id, name, email, roll_number) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('isss', $teacherId, $studentName, $studentEmail, $studentRollNumber);
    } else {
        $stmt = $conn->prepare('INSERT INTO students (teacher_id, name, email, roll_number, class_id) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('isssi', $teacherId, $studentName, $studentEmail, $studentRollNumber, $classIdValue);
    }

    $stmt->execute();
    $stmt->close();

    flash('success', 'Student added successfully.');
    redirect('dashboard.php');
}

$stmt = $conn->prepare('SELECT COUNT(*) AS total FROM students WHERE teacher_id = ?');
$stmt->bind_param('i', $teacherId);
$stmt->execute();
$studentCount = (int) $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare('SELECT COUNT(*) AS total FROM classes WHERE teacher_id = ?');
$stmt->bind_param('i', $teacherId);
$stmt->execute();
$classCount = (int) $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare(
    'SELECT COUNT(*) AS total,
            COALESCE(SUM(CASE WHEN a.attendance = 1 THEN 1 ELSE 0 END), 0) AS present_total
     FROM attendance a
     INNER JOIN classes c ON c.id = a.class_id
     WHERE c.teacher_id = ?'
);
$stmt->bind_param('i', $teacherId);
$stmt->execute();
$attendanceStats = $stmt->get_result()->fetch_assoc();
$markedSessions = (int) $attendanceStats['total'];
$presentSessions = (int) $attendanceStats['present_total'];
$attendanceRate = $markedSessions > 0 ? round(($presentSessions / $markedSessions) * 100) : 0;
$stmt->close();

$stmt = $conn->prepare('SELECT id, class_name FROM classes WHERE teacher_id = ? ORDER BY created_at DESC, class_name ASC');
$stmt->bind_param('i', $teacherId);
$stmt->execute();
$classes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $conn->prepare(
    'SELECT s.name, s.email, s.roll_number, COALESCE(c.class_name, "Unassigned") AS class_name
     FROM students s
     LEFT JOIN classes c ON c.id = s.class_id AND c.teacher_id = s.teacher_id
     WHERE s.teacher_id = ?
     ORDER BY s.created_at DESC
     LIMIT 8'
);
$stmt->bind_param('i', $teacherId);
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

page_header('Teacher Dashboard', 'dashboard');
?>

<section class="page-title">
    <div>
        <h1>Dashboard</h1>
        <p>Track class activity, add students, and jump into attendance tasks from one place.</p>
    </div>
    <div class="toolbar">
        <a class="button button-secondary" href="view_classes.php">View Classes</a>
        <a class="button" href="create_class.php">Create Class</a>
    </div>
</section>

<section class="grid metrics-grid" aria-label="Dashboard metrics">
    <div class="metric">
        <strong><?php echo $classCount; ?></strong>
        <span>Classes</span>
    </div>
    <div class="metric">
        <strong><?php echo $studentCount; ?></strong>
        <span>Students</span>
    </div>
    <div class="metric">
        <strong><?php echo $markedSessions; ?></strong>
        <span>Attendance records</span>
    </div>
    <div class="metric">
        <strong><?php echo $attendanceRate; ?>%</strong>
        <span>Present rate</span>
    </div>
</section>

<section class="grid grid-two section-gap">
    <div class="panel">
        <div class="panel-header">
            <div>
                <h2>Add Student</h2>
                <p class="panel-subtitle">Create a student and optionally assign a class.</p>
            </div>
        </div>
        <form method="POST" action="dashboard.php" class="form-grid">
            <input type="hidden" name="action" value="add_student">
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
            <div class="field field-full">
                <label for="class_id">Assign Class</label>
                <select id="class_id" name="class_id">
                    <option value="0">Keep unassigned</option>
                    <?php foreach ($classes as $class) { ?>
                        <option value="<?php echo (int) $class['id']; ?>"><?php echo e($class['class_name']); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-actions field-full">
                <button type="submit">Add Student</button>
            </div>
        </form>
    </div>

    <div class="panel">
        <div class="panel-header">
            <div>
                <h2>Recent Students</h2>
                <p class="panel-subtitle">Latest student records for your account.</p>
            </div>
        </div>
        <?php if ($students) { ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Roll Number</th>
                            <th>Class</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student) { ?>
                            <tr>
                                <td><?php echo e($student['name']); ?></td>
                                <td><?php echo e($student['email']); ?></td>
                                <td><?php echo e($student['roll_number']); ?></td>
                                <td><span class="badge"><?php echo e($student['class_name']); ?></span></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } else { ?>
            <div class="empty-state">No students yet. Add your first student to begin.</div>
        <?php } ?>
    </div>
</section>

<?php page_footer(); ?>
